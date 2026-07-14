<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\Reports;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN, 'active' => true]);
    }

    private function makeOrder(float $total, string $status = 'served', bool $paid = true): Order
    {
        return Order::create([
            'status' => $status,
            'created_by' => $this->admin->id,
            'total' => $total,
            'is_paid' => $paid,
        ]);
    }

    public function test_reports_component_renders(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(Reports::class)->assertOk();
    }

    public function test_summary_totals_revenue_and_orders(): void
    {
        $this->actingAs($this->admin);

        $this->makeOrder(100.00);
        $this->makeOrder(250.50);

        $component = Livewire::test(Reports::class);
        $summary = $component->instance()->summary;

        $this->assertEqualsWithDelta(350.50, $summary['revenue']['value'], 0.001);
        $this->assertSame(2, $summary['orders']['value']);
    }

    public function test_cancelled_orders_are_excluded_from_summary(): void
    {
        $this->actingAs($this->admin);

        $this->makeOrder(100.00);
        $this->makeOrder(999.00, status: 'cancelled');

        $summary = Livewire::test(Reports::class)->instance()->summary;

        $this->assertEqualsWithDelta(100.00, $summary['revenue']['value'], 0.001);
        $this->assertSame(1, $summary['orders']['value']);
    }

    /**
     * Regression: period stats used to cache Eloquent models, which failed to
     * unserialize from a shared cache store and 500'd the whole reports page.
     */
    public function test_period_stats_cache_stores_plain_arrays_not_objects(): void
    {
        $this->actingAs($this->admin);

        $this->makeOrder(75.00);

        $component = Livewire::test(Reports::class)->instance();
        $component->summary; // triggers getPeriodStats + caching

        $from = $component->fromDate;
        $to = $component->toDate;

        $cached = Cache::get("admin.reports.stats.{$from}.{$to}");

        $this->assertIsArray($cached);
        $this->assertArrayHasKey('revenue', $cached);
        $this->assertArrayHasKey('orders', $cached);
        $this->assertIsFloat($cached['revenue']);
        $this->assertIsInt($cached['orders']);
    }

    public function test_daily_trend_cache_stores_plain_arrays(): void
    {
        $this->actingAs($this->admin);

        $this->makeOrder(60.00);

        $component = Livewire::test(Reports::class)->instance();
        $trend = $component->dailyTrend;

        $this->assertIsArray($trend);
        $this->assertArrayHasKey('labels', $trend);
        $this->assertArrayHasKey('values', $trend);

        $cached = Cache::get("admin.reports.daily_trend.{$component->fromDate}.{$component->toDate}");
        $this->assertIsArray($cached);
    }

    public function test_average_order_value_is_computed(): void
    {
        $this->actingAs($this->admin);

        $this->makeOrder(100.00);
        $this->makeOrder(200.00);

        $summary = Livewire::test(Reports::class)->instance()->summary;

        $this->assertEqualsWithDelta(150.00, $summary['avgValue']['value'], 0.001);
    }

    public function test_date_presets_update_range(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(Reports::class)
            ->call('setPreset', 'today');

        $this->assertSame(now()->format('Y-m-d'), $component->instance()->fromDate);
        $this->assertSame(now()->format('Y-m-d'), $component->instance()->toDate);

        $component->call('setPreset', '30d');
        $this->assertSame(now()->subDays(29)->format('Y-m-d'), $component->instance()->fromDate);
    }
}
