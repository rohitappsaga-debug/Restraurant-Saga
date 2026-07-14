<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\Table;
use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Dashboard extends Component
{
    public $period = 'today';
    public $dailyRevenue = 0;
    public $todayOrders = 0;
    public $occupancyRate = 0;
    public $occupiedTables = 0;
    public $totalTables = 0;
    public $hourlySales = [];
    public $chartLabels = [];
    public $chartTitle = 'Hourly Volume';
    public $chartSubTitle = 'Sales trends throughout the day';
    public $peakHour = '--:--';
    public $peakLabel = 'Peak Hour';

    public function mount()
    {
        $this->loadStats();
        $this->loadChartData();
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.admin.dashboard');
    }

    public function updatedPeriod()
    {
        $this->loadStats();
        $this->loadChartData();
    }

    private function loadStats()
    {
        $cacheKey = "admin.dashboard.stats.{$this->period}";
        
        $stats = Cache::remember($cacheKey, 300, function () {
            $revenueQuery = Order::where('is_paid', true);
            $orderQuery = Order::query();

            switch ($this->period) {
                case 'today':
                    $revenueQuery->whereDate('created_at', Carbon::today());
                    $orderQuery->whereDate('created_at', Carbon::today());
                    break;
                case 'week':
                    $revenueQuery->where('created_at', '>=', now()->startOfWeek());
                    $orderQuery->where('created_at', '>=', now()->startOfWeek());
                    break;
                case 'month':
                    $revenueQuery->where('created_at', '>=', now()->startOfMonth());
                    $orderQuery->where('created_at', '>=', now()->startOfMonth());
                    break;
            }

            return [
                'revenue' => (float) $revenueQuery->sum('total'),
                'orders' => $orderQuery->count(),
            ];
        });

        $this->dailyRevenue = $stats['revenue'];
        $this->todayOrders = $stats['orders'];

        // Occupancy (Always Live, cached for 1 min)
        $occupancy = Cache::remember('admin.dashboard.occupancy_stats', 60, function () {
            $stats = Table::selectRaw('COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as occupied', [TableStatus::OCCUPIED])->first();
            return [
                'total' => (int) ($stats->total ?? 0),
                'occupied' => (int) ($stats->occupied ?? 0),
            ];
        });

        $this->totalTables = $occupancy['total'];
        $this->occupiedTables = $occupancy['occupied'];
        $this->occupancyRate = $occupancy['total'] > 0 ? round(($occupancy['occupied'] / $occupancy['total']) * 100) : 0;
    }

    private function loadChartData()
    {
        switch ($this->period) {
            case 'today':
                $this->loadHourlySales();
                $this->chartTitle = 'Hourly Volume';
                $this->chartSubTitle = 'Sales trends throughout the day';
                $this->peakLabel = 'Peak Hour';
                break;
            case 'week':
                $this->loadDailySales(7);
                $this->chartTitle = 'Weekly Volume';
                $this->chartSubTitle = 'Sales performance this week';
                $this->peakLabel = 'Best Day';
                break;
            case 'month':
                $this->loadDailySales(30);
                $this->chartTitle = 'Monthly Volume';
                $this->chartSubTitle = 'Sales performance this month';
                $this->peakLabel = 'Best Day';
                break;
            case 'all':
                $this->loadMonthlySales();
                $this->chartTitle = 'All-Time Volume';
                $this->chartSubTitle = 'Sales performance by month';
                $this->peakLabel = 'Best Month';
                break;
        }
    }

    private function loadHourlySales()
    {
        $cacheKey = "admin.dashboard.charts.hourly." . Carbon::today()->format('Y-m-d');
        
        $sales = Cache::remember($cacheKey, 300, function () {
            return Order::whereDate('created_at', Carbon::today())
                ->where('is_paid', true)
                ->select(
                    DB::raw("EXTRACT(HOUR FROM created_at) as hour"),
                    DB::raw("SUM(total) as total")
                )
                ->groupBy('hour')
                ->get()
                ->pluck('total', 'hour')
                ->toArray();
        });

        $this->hourlySales = array_fill(0, 24, 0);
        $this->chartLabels = array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23));
        
        $maxSales = 0;
        $peak = -1;

        foreach ($sales as $hour => $total) {
            $h = (int)$hour;
            $this->hourlySales[$h] = (float)$total;
            if ($total > $maxSales) {
                $maxSales = $total;
                $peak = $h;
            }
        }

        $this->peakHour = $peak !== -1 ? sprintf('%02d:00', $peak) : '--:--';
    }

    private function loadDailySales($days)
    {
        $startDate = now()->subDays($days - 1)->startOfDay();
        $cacheKey = "admin.dashboard.charts.daily.{$days}." . Carbon::today()->format('Y-m-d');
        
        $sales = Cache::remember($cacheKey, 3600, function () use ($startDate) {
            return Order::where('created_at', '>=', $startDate)
                ->where('is_paid', true)
                ->select(
                    DB::raw("DATE(created_at) as date"),
                    DB::raw("SUM(total) as total")
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('total', 'date')
                ->toArray();
        });

        $this->hourlySales = [];
        $this->chartLabels = [];
        $maxSales = 0;
        $peak = '';

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $label = $startDate->copy()->addDays($i)->format('D, d');
            $total = (float) ($sales[$date] ?? 0);
            
            $this->hourlySales[] = $total;
            $this->chartLabels[] = $label;

            if ($total > $maxSales) {
                $maxSales = $total;
                $peak = $label;
            }
        }

        $this->peakHour = $peak ?: '--:--';
    }

    private function loadMonthlySales()
    {
        $startDate = now()->subMonths(11)->startOfMonth();
        $cacheKey = "admin.dashboard.charts.monthly." . Carbon::today()->format('Y-m');
        
        $sales = Cache::remember($cacheKey, 86400, function () use ($startDate) {
            return Order::where('created_at', '>=', $startDate)
                ->where('is_paid', true)
                ->select(
                    DB::raw("EXTRACT(YEAR FROM created_at) as year"),
                    DB::raw("EXTRACT(MONTH FROM created_at) as month"),
                    DB::raw("SUM(total) as total")
                )
                ->groupBy('year', 'month')
                ->get()
                ->mapWithKeys(function($item) {
                    return [sprintf('%d-%02d', $item->year, $item->month) => (float)$item->total];
                })
                ->toArray();
        });

        $this->hourlySales = [];
        $this->chartLabels = [];
        $maxSales = 0;
        $peak = '';

        for ($i = 0; $i < 12; $i++) {
            $current = $startDate->copy()->addMonths($i);
            $key = $current->format('Y-m');
            $label = $current->format('M Y');
            $total = (float) ($sales[$key] ?? 0);
            
            $this->hourlySales[] = $total;
            $this->chartLabels[] = $label;

            if ($total > $maxSales) {
                $maxSales = $total;
                $peak = $label;
            }
        }

        $this->peakHour = $peak ?: '--:--';
    }
}
