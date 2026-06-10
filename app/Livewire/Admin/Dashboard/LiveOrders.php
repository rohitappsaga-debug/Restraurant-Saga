<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class LiveOrders extends Component
{
    public function render()
    {
        $liveOrders = Order::with(['table', 'orderItems.menuItem'])
            ->whereIn('status', [OrderStatus::PENDING, OrderStatus::PREPARING, OrderStatus::READY])
            ->where('created_at', '>=', now()->subHours(12))
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('livewire.admin.dashboard.live-orders', [
            'liveOrders' => $liveOrders
        ]);
    }
}
