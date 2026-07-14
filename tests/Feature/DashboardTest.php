<?php

namespace Tests\Feature;

use App\Enums\TableStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\Dashboard;
use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN, 'active' => true]);
    }

    public function test_dashboard_component_renders(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(Dashboard::class)->assertOk();
    }

    public function test_occupancy_reports_occupied_and_total_table_counts(): void
    {
        $this->actingAs($this->admin);

        Table::create(['number' => 1, 'capacity' => 4, 'status' => TableStatus::OCCUPIED]);
        Table::create(['number' => 2, 'capacity' => 2, 'status' => TableStatus::OCCUPIED]);
        Table::create(['number' => 3, 'capacity' => 6, 'status' => TableStatus::FREE]);
        Table::create(['number' => 4, 'capacity' => 2, 'status' => TableStatus::FREE]);

        $component = Livewire::test(Dashboard::class)->instance();

        $this->assertSame(2, $component->occupiedTables);
        $this->assertSame(4, $component->totalTables);
        $this->assertEquals(50, $component->occupancyRate);
    }

    public function test_occupancy_is_zero_with_no_tables(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(Dashboard::class)->instance();

        $this->assertSame(0, $component->occupiedTables);
        $this->assertSame(0, $component->totalTables);
        $this->assertEquals(0, $component->occupancyRate);
    }

    public function test_today_revenue_counts_paid_orders(): void
    {
        $this->actingAs($this->admin);

        Order::create(['status' => 'served', 'created_by' => $this->admin->id, 'total' => 120.00, 'is_paid' => true]);
        Order::create(['status' => 'served', 'created_by' => $this->admin->id, 'total' => 80.00, 'is_paid' => true]);

        $component = Livewire::test(Dashboard::class)->instance();

        $this->assertGreaterThan(0, $component->dailyRevenue);
        $this->assertGreaterThanOrEqual(2, $component->todayOrders);
    }
}
