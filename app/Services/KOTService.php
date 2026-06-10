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

            // Fire event for kitchen broadcasting OUTSIDE transaction
            broadcast(new KOTCreated($kot))->toOthers();
        }

        return $kot;
    }
}
