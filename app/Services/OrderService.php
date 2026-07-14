<?php

namespace App\Services;

use App\Models\Order;
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
        $itemStatuses = $order->orderItems()->pluck('status')->map(fn($s) => $s instanceof OrderStatus ? $s->value : $s)->toArray();
        
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
}