<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $amount;
    public $method;

    public function __construct(Order $order, $amount, $method)
    {
        $this->order = $order;
        $this->amount = $amount;
        $this->method = $method;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('payments'),
        ];
    }
}
