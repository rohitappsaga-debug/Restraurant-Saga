<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Kot;
use App\Enums\OrderStatus;
use App\Events\KOTCreated;
use Illuminate\Support\Facades\DB;

class KOTService
{
    /**
     * Send pending items to the kitchen as a new KOT.
     */
    public function sendToKitchen(Order $order): ?Kot
    {
        $kot = DB::transaction(function () use ($order) {
            $pendingItems = $order->orderItems()->where('status', OrderStatus::PENDING)->get();

            if ($pendingItems->isEmpty()) {
                return null;
            }

            // Get next batch number for this order
            $nextBatch = ($order->kots()->max('batch_number') ?? 0) + 1;

            $kot = Kot::create([
                'order_id' => $order->id,
                'batch_number' => $nextBatch,
                'sent_at' => now(),
            ]);

            OrderItem::whereIn('id', $pendingItems->pluck('id'))->update([
                'kot_id' => $kot->id,
                'status' => OrderStatus::SENT,
            ]);

            return $kot;
        });

        if ($kot) {
            // Sync order status
            (new \App\Services\OrderService())->syncOrderStatus($order);

            // Log for debugging
            \Illuminate\Support\Facades\Log::info("KOT Created: ID {$kot->id} for Order {$order->id}");

            // Fire event for kitchen broadcasting OUTSIDE transaction.
            // Fail-safe: a down websocket server must never block orders.
            try {
                broadcast(new KOTCreated($kot))->toOthers();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Kitchen broadcast failed: " . $e->getMessage());
            }
        }

        return $kot;
    }

    /**
     * Kitchen-side item transition (preparing / ready). Mirrors the kitchen
     * dashboard: broadcasts the change, alerts the waiter when food is
     * ready, and re-syncs the parent order status.
     */
    public function updateItemStatus(OrderItem $item, string $status): OrderItem
    {
        if (!in_array($status, [OrderStatus::PREPARING->value, OrderStatus::READY->value], true)) {
            throw new \DomainException('Kitchen can only move items to preparing or ready.');
        }

        if (in_array($item->status, [OrderStatus::SERVED, OrderStatus::CANCELLED], true)) {
            throw new \DomainException('This item has already been served or cancelled.');
        }

        $item->update(['status' => $status]);

        try {
            \App\Events\ItemStatusUpdated::dispatch($item);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Item status broadcast failed: " . $e->getMessage());
        }

        if ($status === OrderStatus::READY->value) {
            \App\Models\Notification::create([
                'type' => \App\Enums\NotificationType::ALERT,
                'message' => "Order Ready: {$item->menuItem->name} for Table {$item->order->table_label}",
                'user_id' => $item->order->created_by,
                'read' => false,
            ]);
        }

        (new \App\Services\OrderService())->syncOrderStatus($item->order);

        return $item->fresh(['menuItem', 'order']);
    }
}
