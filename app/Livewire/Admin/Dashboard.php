<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\Table;
use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Dashboard extends Component
{
    public $period = 'today';
    public $dailyRevenue = 0;
    public $todayOrders = 0;
    public $occupancyRate = 0;
    public $hourlySales = [];
    public $chartLabels = [];
    public $chartTitle = 'Hourly Volume';
    public $chartSubTitle = 'Sales trends throughout the day';
    public $peakHour = '--:--';
    public $peakLabel = 'Peak Hour';

    #[Layout('layouts.admin')]
    public function render()
    {
        $this->loadStats();
        $this->loadChartData();

        $liveOrders = Order::with(['table', 'orderItems.menuItem'])
            ->whereIn('status', [OrderStatus::PENDING, OrderStatus::PREPARING, OrderStatus::READY])
            ->where('created_at', '>=', now()->subHours(12))
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('livewire.admin.dashboard', [
            'liveOrders' => $liveOrders
        ]);
    }

    public function updatedPeriod()
    {
        // Stats will be reloaded on next render
    }

    private function loadStats()
    {
        $query = Order::where('is_paid', true);

        switch ($this->period) {
            case 'today':
                $query->whereDate('created_at', Carbon::today());
                break;
            case 'week':
                $query->where('created_at', '>=', now()->startOfWeek());
                break;
            case 'month':
                $query->where('created_at', '>=', now()->startOfMonth());
                break;
        }

        // Revenue
        $this->dailyRevenue = (float) $query->sum('total');

        // Orders (including unpaid ones for volume)
        $orderQuery = Order::query();
        switch ($this->period) {
            case 'today':
                $orderQuery->whereDate('created_at', Carbon::today());
                break;
            case 'week':
                $orderQuery->where('created_at', '>=', now()->startOfWeek());
                break;
            case 'month':
                $orderQuery->where('created_at', '>=', now()->startOfMonth());
                break;
        }
        $this->todayOrders = $orderQuery->count();

        // Occupancy Rate (Always Live)
        $totalTables = Table::count();
        $occupiedTables = Table::where('status', TableStatus::OCCUPIED)->count();
        $this->occupancyRate = $totalTables > 0 ? round(($occupiedTables / $totalTables) * 100) : 0;
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
        $sales = Order::whereDate('created_at', Carbon::today())
            ->where('is_paid', true)
            ->select(
                DB::raw("EXTRACT(HOUR FROM created_at) as hour"),
                DB::raw("SUM(total) as total")
            )
            ->groupBy('hour')
            ->get()
            ->pluck('total', 'hour')
            ->toArray();

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
        
        $sales = Order::where('created_at', '>=', $startDate)
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
        
        $sales = Order::where('created_at', '>=', $startDate)
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
