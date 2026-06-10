<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Table;
use App\Models\TableSession;
use App\Enums\TableStatus;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Start a new guest session for a table.
     */
    public function startSession(string $tableId, string $waiterId): TableSession
    {
        return DB::transaction(function () use ($tableId, $waiterId) {
            $table = Table::findOrFail($tableId);

            if ($table->status !== TableStatus::FREE) {
                // In production, we'd check for same-waiter or manager override
            }

            $session = TableSession::create([
                'table_id' => $tableId,
                'waiter_id' => $waiterId,
                'status' => 'active',
                'started_at' => now(),
            ]);

            $table->update([
                'status' => TableStatus::OCCUPIED,
                'current_session_id' => $session->id,
            ]);

            return $session;
        });
    }

    /**
     * Create or retrieve the active order for a session.
     */
    public function getActiveOrder(TableSession $session): Order
    {
        return Order::firstOrCreate(
            ['session_id' => $session->id, 'status' => OrderStatus::PENDING, 'is_paid' => false],
            [
                'id' => Str::uuid(),
                'table_number' => $session->table->number,
                'created_by' => $session->waiter_id,
                'total' => 0,
            ]
        );
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