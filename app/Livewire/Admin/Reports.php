<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Category;
use App\Models\Setting;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Reports extends Component
{
    public $fromDate;
    public $toDate;
    public $settings;
    public $datePreset = '7d';

    public function mount()
    {
        $this->setPreset('7d');
        $this->settings = Setting::first()?->toArray() ?? ['currency' => '₹'];
    }

    public function setPreset($preset)
    {
        $this->datePreset = $preset;
        $this->toDate = Carbon::now()->format('Y-m-d');
        
        $this->fromDate = match($preset) {
            'today' => Carbon::now()->format('Y-m-d'),
            '7d' => Carbon::now()->subDays(6)->format('Y-m-d'),
            '30d' => Carbon::now()->subDays(29)->format('Y-m-d'),
            default => $this->fromDate,
        };

        $this->dispatch('refreshCharts');
    }

    public function updatedFromDate() { $this->dispatch('refreshCharts'); }
    public function updatedToDate() { $this->dispatch('refreshCharts'); }

    public function getSummaryProperty()
    {
        $current = $this->getPeriodStats($this->fromDate, $this->toDate);
        
        // Calculate previous period for trend
        $diff = Carbon::parse($this->fromDate)->diffInDays(Carbon::parse($this->toDate)) + 1;
        $prevFrom = Carbon::parse($this->fromDate)->subDays($diff)->format('Y-m-d');
        $prevTo = Carbon::parse($this->fromDate)->subDay()->format('Y-m-d');
        
        $previous = $this->getPeriodStats($prevFrom, $prevTo);

        return [
            'revenue' => [
                'value' => $current['revenue'],
                'change' => $this->calculateChange($current['revenue'], $previous['revenue'])
            ],
            'orders' => [
                'value' => $current['orders'],
                'change' => $this->calculateChange($current['orders'], $previous['orders'])
            ],
            'avgValue' => [
                'value' => $current['orders'] > 0 ? $current['revenue'] / $current['orders'] : 0,
                'change' => $this->calculateChange(
                    ($current['orders'] > 0 ? $current['revenue'] / $current['orders'] : 0),
                    ($previous['orders'] > 0 ? $previous['revenue'] / $previous['orders'] : 0)
                )
            ]
        ];
    }

    protected function getPeriodStats($from, $to)
    {
        $stats = Order::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('status', '!=', 'cancelled') // Assuming cancelled orders don't count towards revenue
            ->select(DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as orders'))
            ->first();

        return [
            'revenue' => (float)$stats->revenue,
            'orders' => (int)$stats->orders
        ];
    }

    protected function calculateChange($current, $previous)
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return (($current - $previous) / $previous) * 100;
    }

    public function getDailyTrendProperty()
    {
        $data = Order::whereBetween('created_at', [$this->fromDate . ' 00:00:00', $this->toDate . ' 23:59:59'])
            ->where('status', '!=', 'cancelled')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d'))->toArray(),
            'values' => $data->pluck('total')->map(fn($v) => (float)$v)->toArray(),
        ];
    }

    public function getCategoryDistributionProperty()
    {
        $data = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('categories', 'menu_items.category_id', '=', 'categories.id')
            ->whereBetween('orders.created_at', [$this->fromDate . ' 00:00:00', $this->toDate . ' 23:59:59'])
            ->where('orders.status', '!=', 'cancelled')
            ->select('categories.name', DB::raw('SUM(menu_items.price * order_items.quantity) as revenue'))
            ->groupBy('categories.name')
            ->get();

        return [
            'labels' => $data->pluck('name')->toArray(),
            'values' => $data->pluck('revenue')->map(fn($v) => (float)$v)->toArray(),
        ];
    }

    public function getTopItemsProperty()
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('categories', 'menu_items.category_id', '=', 'categories.id')
            ->whereBetween('orders.created_at', [$this->fromDate . ' 00:00:00', $this->toDate . ' 23:59:59'])
            ->where('orders.status', '!=', 'cancelled')
            ->select(
                'menu_items.name',
                'categories.name as category',
                DB::raw('SUM(order_items.quantity) as qty'),
                DB::raw('SUM(menu_items.price * order_items.quantity) as revenue'),
                DB::raw('AVG(menu_items.price) as avg_price')
            )
            ->groupBy('menu_items.name', 'categories.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.reports', [
            'summary' => $this->summary,
            'dailyTrend' => $this->dailyTrend,
            'categoryDistribution' => $this->categoryDistribution,
            'topItems' => $this->topItems,
            'settings' => $this->settings,
        ])->layout('layouts.admin');
    }
}
