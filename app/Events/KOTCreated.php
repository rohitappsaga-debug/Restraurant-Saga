<?php

namespace App\Events;

use App\Models\Kot;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KOTCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $kot;

    /**
     * Create a new event instance.
     */
    public function __construct(Kot $kot)
    {
        $this->kot = $kot->load(['order', 'items.menuItem']);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('kitchen'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'KOTCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'kot_id' => $this->kot->id,
            'order_id' => $this->kot->order_id,
            'table_number' => $this->kot->order->table_number,
            'items' => $this->kot->items->map(fn($i) => [
                'name' => $i->menuItem->name,
                'status' => $i->status,
                'quantity' => $i->quantity,
            ]),
        ];
    }
}
