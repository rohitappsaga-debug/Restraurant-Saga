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

class KitchenOrderActionsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $waiter;
    private MenuItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->waiter = User::factory()->create(['role' => UserRole::WAITER, 'active' => true]);
        Setting::create(['restaurant_name' => 'Test', 'currency' => '₹', 'tax_enabled' => false, 'tax_rate' => 0]);

        $category = Category::create(['name' => 'Mains', 'is_active' => true]);
        $this->item = MenuItem::create([
            'name' => 'Dish', 'price' => 100, 'category_id' => $category->id,
            'category' => 'Mains', 'available' => true,
        ]);
    }

    private function makeOrder(int $number): string
    {
        $table = Table::create(['number' => $number, 'capacity' => 4, 'status' => TableStatus::FREE]);
        Sanctum::actingAs($this->waiter);

        return $this->postJson('/api/v1/orders', [
            'table_ids' => [$table->id],
            'items' => [['menu_item_id' => $this->item->id, 'quantity' => 2]],
        ])->json('data.id');
    }

    public function test_waiter_can_toggle_hold(): void
    {
        $orderId = $this->makeOrder(31);

        $this->postJson("/api/v1/orders/{$orderId}/hold")
            ->assertOk()
            ->assertJsonPath('data.hold_status', true);

        $this->postJson("/api/v1/orders/{$orderId}/hold")
            ->assertOk()
            ->assertJsonPath('data.hold_status', false);
    }

    public function test_kitchen_force_close_serves_all_items(): void
    {
        $orderId = $this->makeOrder(32);

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::KITCHEN, 'active' => true]));
        $this->postJson("/api/v1/kitchen/orders/{$orderId}/force-close")
            ->assertOk()
            ->assertJsonPath('data.status', 'served');

        $order = Order::find($orderId);
        $this->assertSame(OrderStatus::SERVED, $order->orderItems()->first()->status);
    }

    public function test_kitchen_dismiss_marks_order_served(): void
    {
        $orderId = $this->makeOrder(33);

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::KITCHEN, 'active' => true]));
        $this->postJson("/api/v1/kitchen/orders/{$orderId}/dismiss")
            ->assertOk()
            ->assertJsonPath('data.status', 'served');
    }

    public function test_waiter_cannot_force_close(): void
    {
        $orderId = $this->makeOrder(34);

        Sanctum::actingAs($this->waiter);
        $this->postJson("/api/v1/kitchen/orders/{$orderId}/force-close")->assertForbidden();
    }
}
