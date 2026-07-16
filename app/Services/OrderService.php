<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Enums\TableStatus;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create an order spanning one or more tables. Locks the table rows
     * (sorted, to avoid deadlocks) and rejects tables that already belong
     * to an open order or are out of commission.
     */
    public function createOrder(array $tableIds, string $waiterId): Order
    {
        return DB::transaction(function () use ($tableIds, $waiterId) {
            $tables = Table::whereIn('id', $tableIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($tables->count() !== count(array_unique($tableIds))) {
                throw new \DomainException('One or more selected tables no longer exist.');
            }

            foreach ($tables as $table) {
                if ($table->current_order_id) {
                    throw new \DomainException("Table {$table->number} already has an open order.");
                }
                if (in_array($table->status, [TableStatus::CLEANING, TableStatus::OUT_OF_SERVICE], true)) {
                    throw new \DomainException("Table {$table->number} is not available right now.");
                }
            }

            $order = Order::create([
                'table_number' => $tables->min('number'),
                'status' => OrderStatus::PENDING,
                'created_by' => $waiterId,
                'total' => 0,
            ]);

            $order->tables()->attach($tables->pluck('id')->all());

            Table::whereIn('id', $tables->pluck('id'))->update([
                'status' => TableStatus::OCCUPIED,
                'current_order_id' => $order->id,
            ]);

            return $order;
        });
    }

    /**
     * Resolve the open order a table belongs to, self-healing stale pointers.
     */
    public function openOrderForTable(Table $table): ?Order
    {
        if (!$table->current_order_id) {
            return null;
        }

        $order = $table->currentOrder;

        if ($order && !$order->is_paid && $order->status !== OrderStatus::CANCELLED) {
            return $order;
        }

        $table->update(['current_order_id' => null]);

        return null;
    }

    /**
     * Single payment path for waiter and admin: record the transaction,
     * mark the order settled, and free its tables.
     */
    public function settleOrder(Order $order, string $method, float $amountDue): void
    {
        if ($amountDue > 0) {
            $order->paymentTransactions()->create([
                'amount' => $amountDue,
                'method' => $method,
                'status' => 'completed',
            ]);
        }

        $order->update([
            'is_paid' => true,
            'payment_method' => $method,
            'status' => OrderStatus::DELIVERED,
        ]);

        $this->releaseTables($order);
    }

    /**
     * Free every table attached to this order. Pivot rows stay as history.
     */
    public function releaseTables(Order $order): void
    {
        Table::where('current_order_id', $order->id)->update([
            'status' => TableStatus::CLEANING,
            'current_order_id' => null,
        ]);
    }

    /**
     * Synchronize order status based on item statuses.
     */
    public function syncOrderStatus(Order $order): void
    {
        $items = $order->relationLoaded('orderItems') ? $order->orderItems : $order->orderItems()->get();
        $itemStatuses = $items->pluck('status')->map(fn($s) => $s instanceof OrderStatus ? $s->value : $s)->toArray();
        
        if (empty($itemStatuses)) return;

        $newStatus = $order->status;

        if (in_array(OrderStatus::PREPARING->value, $itemStatuses)) {
             $newStatus = OrderStatus::PREPARING;
        } elseif (in_array(OrderStatus::SENT->value, $itemStatuses) || in_array(OrderStatus::PENDING->value, $itemStatuses)) {
             $newStatus = OrderStatus::PENDING;
        } elseif (collect($itemStatuses)->every(fn($s) => in_array($s, [OrderStatus::SERVED->value, OrderStatus::DELIVERED->value, OrderStatus::CANCELLED->value]))) {
             $newStatus = OrderStatus::SERVED;
        } elseif (collect($itemStatuses)->every(fn($s) => in_array($s, [OrderStatus::READY->value, OrderStatus::SERVED->value, OrderStatus::DELIVERED->value, OrderStatus::CANCELLED->value]))) {
             $newStatus = OrderStatus::READY;
        }

        if ($newStatus !== $order->status) {
            $order->update(['status' => $newStatus]);
        }
    }

    /**
     * Add items to an open order as a new pending round and send them to
     * the kitchen as a fresh KOT. Modifier snapshots are stored the same
     * way the waiter dashboard stores them.
     */
    public function addItems(Order $order, array $items, KOTService $kotService): Order
    {
        DB::transaction(function () use ($order, $items) {
            $order = Order::lockForUpdate()->findOrFail($order->id);

            if ($order->is_paid || $order->status === OrderStatus::CANCELLED) {
                throw new \DomainException('Cannot add items to a settled or cancelled order.');
            }

            foreach ($items as $data) {
                $menuItem = \App\Models\MenuItem::with('modifiers')->findOrFail($data['menu_item_id']);

                if (!$menuItem->available) {
                    throw new \DomainException("{$menuItem->name} is currently out of stock.");
                }

                $modifiers = [];
                if (!empty($data['modifier_ids'])) {
                    $modifiers = $menuItem->modifiers()
                        ->whereIn('id', $data['modifier_ids'])
                        ->get()
                        ->toArray();
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $data['quantity'],
                    'notes' => $data['notes'] ?? null,
                    'modifiers' => $modifiers,
                    'status' => OrderStatus::PENDING,
                ]);
            }
        });

        $kotService->sendToKitchen($order->fresh());

        return $order->fresh(['orderItems.menuItem', 'kots.items', 'tables']);
    }

    public function serveItem(OrderItem $item, string $userId): OrderItem
    {
        if (!in_array($item->status, [OrderStatus::SENT, OrderStatus::PREPARING, OrderStatus::READY], true)) {
            throw new \DomainException('Only items in progress can be marked as served.');
        }

        $item->update([
            'status' => OrderStatus::SERVED,
            'served_at' => now(),
            'served_by' => $userId,
        ]);

        $this->syncOrderStatus($item->order);

        return $item->fresh('menuItem');
    }

    public function serveAll(Order $order, string $userId): Order
    {
        DB::transaction(function () use ($order, $userId) {
            OrderItem::where('order_id', $order->id)
                ->whereIn('status', [OrderStatus::READY, OrderStatus::SENT, OrderStatus::PREPARING])
                ->update([
                    'status' => OrderStatus::SERVED,
                    'served_at' => now(),
                    'served_by' => $userId,
                ]);

            $this->syncOrderStatus($order);
        });

        return $order->fresh(['orderItems.menuItem', 'tables']);
    }

    /**
     * Persist a discount on the order. Percentage discounts are converted
     * to a fixed value against the current subtotal, matching the waiter
     * dashboard behaviour.
     */
    public function applyDiscount(Order $order, string $type, float $value, BillingService $billing): Order
    {
        if ($order->is_paid) {
            throw new \DomainException('Cannot change the discount on a settled order.');
        }

        $totals = $billing->calculateOrderTotals($order);
        $finalValue = $type === 'percentage'
            ? $totals['subtotal'] * ($value / 100)
            : $value;

        $order->update([
            'discount_type' => $type,
            'discount_value' => round(min($finalValue, $totals['subtotal']), 2),
        ]);

        return $order->fresh(['orderItems.menuItem', 'paymentTransactions']);
    }

    /**
     * Settle an open order: optionally apply a discount, record the payment
     * for the remaining due, free the tables, and stamp the final total.
     * Returns the settled order plus the totals snapshot used for payment.
     */
    public function payOrder(Order $order, string $method, BillingService $billing, ?string $discountType = null, float $discountValue = 0): array
    {
        $paidAmount = 0;
        $totals = [];

        DB::transaction(function () use ($order, $method, $billing, $discountType, $discountValue, &$paidAmount, &$totals) {
            $order = Order::lockForUpdate()->findOrFail($order->id);

            if ($order->is_paid) {
                throw new \DomainException('Order is already settled.');
            }
            if ($order->status === OrderStatus::CANCELLED) {
                throw new \DomainException('Cannot take payment for a cancelled order.');
            }

            if ($discountValue > 0) {
                $this->applyDiscount($order, $discountType ?? 'fixed', $discountValue, $billing);
                $order->refresh();
            }

            $totals = $billing->calculateOrderTotals($order->fresh(['orderItems.menuItem', 'paymentTransactions']));
            $paidAmount = $totals['remainingDue'];

            $this->settleOrder($order, $method, $paidAmount);
            $order->update(['total' => $totals['grandTotal']]);
        });

        $order = $order->fresh(['orderItems.menuItem', 'tables', 'paymentTransactions']);

        try {
            event(new \App\Events\PaymentReceived($order, $paidAmount, $method));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Payment broadcast failed: " . $e->getMessage());
        }

        return ['order' => $order, 'totals' => $totals, 'paid' => $paidAmount];
    }

    /**
     * Cancel an open order: void every unserved item, mark the order
     * cancelled with a reason, and free its tables.
     */
    public function cancelOrder(Order $order, ?string $reason, string $userId): Order
    {
        DB::transaction(function () use ($order, $reason, $userId) {
            $order = Order::lockForUpdate()->findOrFail($order->id);

            if ($order->is_paid) {
                throw new \DomainException('Cannot cancel a settled order.');
            }

            OrderItem::where('order_id', $order->id)
                ->whereNotIn('status', [OrderStatus::SERVED, OrderStatus::CANCELLED])
                ->update([
                    'status' => OrderStatus::CANCELLED,
                    'cancelled_at' => now(),
                    'cancelled_by' => $userId,
                    'cancel_reason' => $reason,
                ]);

            $order->update([
                'status' => OrderStatus::CANCELLED,
                'cancel_reason' => $reason,
            ]);

            $this->releaseTables($order);
        });

        return $order->fresh(['orderItems.menuItem', 'tables']);
    }

    /** Toggle the "hold" flag on an open order (e.g. guest stepped away). */
    public function toggleHold(Order $order): Order
    {
        if ($order->is_paid || $order->status === OrderStatus::CANCELLED) {
            throw new \DomainException('Only an active order can be put on hold.');
        }

        $order->update(['hold_status' => !$order->hold_status]);

        return $order->fresh(['orderItems.menuItem', 'tables']);
    }

    /**
     * Kitchen "force close": push every unserved item to served and mark
     * the order served, clearing it from the kitchen display. Mirrors the
     * kitchen dashboard's forceCloseOrder.
     */
    public function forceClose(Order $order): Order
    {
        DB::transaction(function () use ($order) {
            OrderItem::where('order_id', $order->id)
                ->whereNotIn('status', [OrderStatus::SERVED, OrderStatus::CANCELLED])
                ->update(['status' => OrderStatus::SERVED, 'served_at' => now()]);

            $order->update(['status' => OrderStatus::SERVED]);
        });

        return $order->fresh(['orderItems.menuItem', 'tables']);
    }
}