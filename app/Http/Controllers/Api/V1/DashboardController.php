<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends ApiController
{
    /** Admin/manager KPI summary for the mobile dashboard. */
    public function summary(): JsonResponse
    {
        $today = now()->startOfDay();

        $revenueToday = (float) PaymentTransaction::where('status', 'completed')
            ->where('created_at', '>=', $today)
            ->sum('amount');

        $ordersToday = Order::where('created_at', '>=', $today)->count();

        $openOrders = Order::open()->whereHas('orderItems')->count();

        $occupiedTables = Table::where('status', TableStatus::OCCUPIED)->count();
        $totalTables = Table::count();

        $topItems = OrderItem::query()
            ->where('order_items.created_at', '>=', $today)
            ->where('order_items.status', '!=', OrderStatus::CANCELLED)
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->groupBy('menu_items.id', 'menu_items.name')
            ->select('menu_items.name', DB::raw('SUM(order_items.quantity) as quantity'))
            ->orderByDesc('quantity')
            ->limit(5)
            ->get();

        return $this->respond([
            'revenue_today' => $revenueToday,
            'orders_today' => $ordersToday,
            'open_orders' => $openOrders,
            'average_order_value' => $ordersToday > 0 ? round($revenueToday / max(1, $ordersToday), 2) : 0,
            'tables' => [
                'occupied' => $occupiedTables,
                'total' => $totalTables,
            ],
            'top_items_today' => $topItems,
        ]);
    }
}
