<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN, 'active' => true]);
        Sanctum::actingAs($this->admin);
    }

    public function test_dashboard_summary_reports_todays_activity(): void
    {
        Setting::create(['restaurant_name' => 'Test', 'tax_enabled' => false]);
        $category = Category::create(['name' => 'Mains', 'is_active' => true]);
        $item = MenuItem::create([
            'name' => 'Dish', 'price' => 50, 'category_id' => $category->id,
            'category' => 'Mains', 'available' => true,
        ]);
        $table = Table::create(['number' => 1, 'capacity' => 4, 'status' => TableStatus::FREE]);

        $orderId = $this->postJson('/api/v1/orders', [
            'table_ids' => [$table->id],
            'items' => [['menu_item_id' => $item->id, 'quantity' => 2]],
        ])->json('data.id');
        $this->postJson("/api/v1/orders/{$orderId}/serve-all");
        $this->postJson("/api/v1/orders/{$orderId}/payments", ['method' => 'upi']);

        $summary = $this->getJson('/api/v1/dashboard');

        $summary->assertOk()
            ->assertJsonPath('data.orders_today', 1)
            ->assertJsonPath('data.open_orders', 0);
        $this->assertEqualsWithDelta(100.0, $summary->json('data.revenue_today'), 0.001);
        $this->assertSame('Dish', $summary->json('data.top_items_today.0.name'));
    }

    public function test_sales_report_aggregates_paid_orders_by_day(): void
    {
        Order::create([
            'table_number' => 1, 'status' => OrderStatus::DELIVERED, 'created_by' => $this->admin->id,
            'total' => 150, 'is_paid' => true, 'payment_method' => 'cash',
        ]);
        Order::create([
            'table_number' => 2, 'status' => OrderStatus::DELIVERED, 'created_by' => $this->admin->id,
            'total' => 250, 'is_paid' => true, 'payment_method' => 'card',
        ]);
        // Unpaid orders must not count
        Order::create([
            'table_number' => 3, 'status' => OrderStatus::PENDING, 'created_by' => $this->admin->id,
            'total' => 999, 'is_paid' => false,
        ]);

        $report = $this->getJson('/api/v1/reports/sales?from=' . now()->toDateString() . '&to=' . now()->toDateString());

        $report->assertOk()->assertJsonPath('data.totals.orders', 2);
        $this->assertEqualsWithDelta(400.0, $report->json('data.totals.sales'), 0.001);
    }

    public function test_user_crud_and_self_deactivation_guard(): void
    {
        $created = $this->postJson('/api/v1/users', [
            'name' => 'New Waiter',
            'email' => 'waiter@example.com',
            'password' => 'password123',
            'role' => 'waiter',
        ]);
        $created->assertCreated()->assertJsonPath('data.role', 'waiter');

        $userId = $created->json('data.id');

        $this->patchJson("/api/v1/users/{$userId}", ['name' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed');

        // destroy deactivates instead of deleting
        $this->deleteJson("/api/v1/users/{$userId}")
            ->assertOk()
            ->assertJsonPath('data.active', false);
        $this->assertDatabaseHas('users', ['id' => $userId]);

        // an admin cannot deactivate their own account
        $this->deleteJson("/api/v1/users/{$this->admin->id}")->assertStatus(422);
    }

    public function test_menu_item_crud_with_validation(): void
    {
        $category = Category::create(['name' => 'Starters', 'is_active' => true]);

        $this->postJson('/api/v1/menu-items', ['name' => 'No price'])->assertStatus(422);

        $created = $this->postJson('/api/v1/menu-items', [
            'name' => 'Spring Rolls',
            'category_id' => $category->id,
            'price' => 120.50,
            'is_veg' => true,
        ]);
        $created->assertCreated()->assertJsonPath('data.price', 120.5);

        $itemId = $created->json('data.id');

        $this->postJson("/api/v1/menu-items/{$itemId}/toggle-availability")
            ->assertOk()
            ->assertJsonPath('data.available', false);

        $this->deleteJson("/api/v1/menu-items/{$itemId}")->assertOk();
        $this->assertDatabaseMissing('menu_items', ['id' => $itemId]);
    }

    public function test_supplier_and_ingredient_management(): void
    {
        $supplier = $this->postJson('/api/v1/suppliers', ['name' => 'Fresh Farms', 'phone' => '12345']);
        $supplier->assertCreated();

        $ingredient = $this->postJson('/api/v1/ingredients', [
            'name' => 'Tomato', 'unit' => 'kg', 'stock' => 10, 'min_level' => 2,
        ]);
        $ingredient->assertCreated();

        $id = $ingredient->json('data.id');

        $this->postJson("/api/v1/ingredients/{$id}/adjust-stock", ['delta' => -4])
            ->assertOk()
            ->assertJsonPath('data.stock', 6);

        $this->postJson("/api/v1/ingredients/{$id}/adjust-stock", ['delta' => -100])
            ->assertStatus(422);
    }
}
