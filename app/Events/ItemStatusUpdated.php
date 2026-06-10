<?php

namespace App\Events;

use App\Models\OrderItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItemStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $item;
    public $tableNumber;

    /**
     * Create a new event instance.
     */
    public function __construct(OrderItem $item)
    {
        $this->item = $item->load('menuItem');
        $this->tableNumber = $item->order->table_number;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('orders'), // Global dashboard updates
            new Channel('waiter.' . $this->item->order->created_by), // Specific waiter alerts
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'item_id' => $this->item->id,
            'status' => $this->item->status,
            'item_name' => $this->item->menuItem->name,
            'table_number' => $this->tableNumber,
        ];
    }
}
