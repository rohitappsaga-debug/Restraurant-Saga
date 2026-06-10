<?php

namespace App\Events;

use App\Models\MenuItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MenuAvailabilityUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $menuItemId;
    public $isAvailable;

    public function __construct(MenuItem $item)
    {
        $this->menuItemId = $item->id;
        $this->isAvailable = $item->available;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('menu-updates'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MenuAvailabilityUpdated';
    }
}
