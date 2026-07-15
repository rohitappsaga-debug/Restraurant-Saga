<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends ApiController
{
    /** Daily sales between two dates (defaults to the last 7 days). */
    public function sales(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $from = isset($validated['from']) ? \Carbon\Carbon::parse($validated['from'])->startOfDay() : now()->subDays(6)->startOfDay();
        $to = isset($validated['to']) ? \Carbon\Carbon::parse($validated['to'])->endOfDay() : now()->endOfDay();

        $daily = Order::query()
            ->where('is_paid', true)
            ->whereBetween('created_at', [$from, $to])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total) as total_sales'),
                DB::raw('ROUND(AVG(total), 2) as average_order_value')
            )
            ->orderBy('date')
            ->get();

        return $this->respond([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'totals' => [
                'sales' => (float) $daily->sum('total_sales'),
                'orders' => (int) $daily->sum('total_orders'),
            ],
            'daily' => $daily,
        ]);
    }

    /**
     * Rich analytics for the admin dashboard: period summary with
     * period-over-period change, daily revenue trend, category revenue
     * split, and top items. Mirrors the web Reports page.
     */
    public function analytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $from = isset($validated['from']) ? \Carbon\Carbon::parse($validated['from'])->startOfDay() : now()->subDays(6)->startOfDay();
        $to = isset($validated['to']) ? \Carbon\Carbon::parse($validated['to'])->endOfDay() : now()->endOfDay();

        $current = $this->periodStats($from, $to);

        // Immediately-preceding window of the same length, for trend arrows
        $days = $from->diffInDays($to) + 1;
        $prevTo = (clone $from)->subDay()->endOfDay();
        $prevFrom = (clone $from)->subDays($days)->startOfDay();
        $previous = $this->periodStats($prevFrom, $prevTo);

        $currentAvg = $current['orders'] > 0 ? $current['revenue'] / $current['orders'] : 0;
        $previousAvg = $previous['orders'] > 0 ? $previous['revenue'] / $previous['orders'] : 0;

        return $this->respond([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'summary' => [
                'revenue' => ['value' => $current['revenue'], 'change' => $this->change($current['revenue'], $previous['revenue'])],
                'orders' => ['value' => $current['orders'], 'change' => $this->change($current['orders'], $previous['orders'])],
                'average_order_value' => ['value' => round($currentAvg, 2), 'change' => $this->change($currentAvg, $previousAvg)],
            ],
            'daily_trend' => $this->dailyTrend($from, $to),
            'category_distribution' => $this->categoryDistribution($from, $to),
            'top_items' => $this->topItems($from, $to),
        ]);
    }

    private function periodStats(\Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        $stats = Order::whereBetween('created_at', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->selectRaw('COALESCE(SUM(total), 0) as revenue, COUNT(*) as orders')
            ->first();

        return ['revenue' => (float) $stats->revenue, 'orders' => (int) $stats->orders];
    }

    private function change(float $current, float $previous): float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    private function dailyTrend(\Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        return Order::whereBetween('created_at', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->get()
            ->map(fn ($r) => ['date' => $r->date, 'total' => (float) $r->total])
            ->all();
    }

    private function categoryDistribution(\Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('categories', 'menu_items.category_id', '=', 'categories.id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('categories.name')
            ->selectRaw('categories.name, SUM(menu_items.price * order_items.quantity) as revenue')
            ->get()
            ->map(fn ($r) => ['category' => $r->name, 'revenue' => (float) $r->revenue])
            ->all();
    }

    private function topItems(\Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('categories', 'menu_items.category_id', '=', 'categories.id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('menu_items.name', 'categories.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->selectRaw('menu_items.name, categories.name as category, SUM(order_items.quantity) as qty, SUM(menu_items.price * order_items.quantity) as revenue')
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'category' => $r->category,
                'qty' => (int) $r->qty,
                'revenue' => (float) $r->revenue,
            ])
            ->all();
    }
}
