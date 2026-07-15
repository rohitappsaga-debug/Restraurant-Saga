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

class OrderLifecycleApiTest extends TestCase
{
    use RefreshDatabase;

    private User $waiter;
    private User $kitchen;
    private MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->waiter = User::factory()->create(['role' => UserRole::WAITER, 'active' => true]);
        $this->kitchen = User::factory()->create(['role' => UserRole::KITCHEN, 'active' => true]);

        Setting::create(['restaurant_name' => 'Test', 'currency' => '₹', 'tax_enabled' => true, 'tax_rate' => 5]);

        $category = Category::create(['name' => 'Mains', 'is_active' => true]);
        $this->menuItem = MenuItem::create([
            'name' => 'Paneer Tikka', 'price' => 100, 'category_id' => $category->id,
            'category' => 'Mains', 'available' => true, 'is_veg' => true,
        ]);
    }

    private function makeTables(int ...$numbers): array
    {
        return array_map(
            fn ($n) => Table::create(['number' => $n, 'capacity' => 4, 'status' => TableStatus::FREE]),
            $numbers
        );
    }

    public function test_waiter_can_create_a_multi_table_order_with_items(): void
    {
        [$t5, $t6] = $this->makeTables(5, 6);
        Sanctum::actingAs($this->waiter);

        $response = $this->postJson('/api/v1/orders', [
            'table_ids' => [$t5->id, $t6->id],
            'items' => [
                ['menu_item_id' => $this->menuItem->id, 'quantity' => 2, 'notes' => 'extra spicy'],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.table_label', '5+6')
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.status', 'sent');

        $orderId = $response->json('data.id');

        // Tables are locked to the order and a KOT batch exists
        $this->assertSame(TableStatus::OCCUPIED, $t5->fresh()->status);
        $this->assertSame($orderId, $t6->fresh()->current_order_id);
        $this->assertDatabaseHas('kots', ['order_id' => $orderId, 'batch_number' => 1]);
    }

    public function test_occupied_table_cannot_be_ordered_again(): void
    {
        [$table] = $this->makeTables(3);
        Sanctum::actingAs($this->waiter);

        $this->postJson('/api/v1/orders', ['table_ids' => [$table->id]])->assertCreated();

        $this->postJson('/api/v1/orders', ['table_ids' => [$table->id]])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_adding_items_creates_a_second_kot_batch(): void
    {
        [$table] = $this->makeTables(7);
        Sanctum::actingAs($this->waiter);

        $orderId = $this->postJson('/api/v1/orders', [
            'table_ids' => [$table->id],
            'items' => [['menu_item_id' => $this->menuItem->id, 'quantity' => 1]],
        ])->json('data.id');

        $this->postJson("/api/v1/orders/{$orderId}/items", [
            'items' => [['menu_item_id' => $this->menuItem->id, 'quantity' => 3]],
        ])->assertOk();

        $this->assertDatabaseHas('kots', ['order_id' => $orderId, 'batch_number' => 2]);
        $this->assertSame(2, Order::find($orderId)->orderItems()->count());
    }

    public function test_full_lifecycle_kitchen_ready_waiter_serves_and_settles(): void
    {
        [$table] = $this->makeTables(9);
        Sanctum::actingAs($this->waiter);

        $orderId = $this->postJson('/api/v1/orders', [
            'table_ids' => [$table->id],
            'items' => [['menu_item_id' => $this->menuItem->id, 'quantity' => 2]],
        ])->json('data.id');

        $itemId = Order::find($orderId)->orderItems()->first()->id;

        // Kitchen: preparing → ready
        Sanctum::actingAs($this->kitchen);
        $this->patchJson("/api/v1/kitchen/items/{$itemId}/status", ['status' => 'preparing'])
            ->assertOk()
            ->assertJsonPath('data.status', 'preparing');
        $this->patchJson("/api/v1/kitchen/items/{$itemId}/status", ['status' => 'ready'])
            ->assertOk();

        // The waiter got a persistent "order ready" notification
        $this->assertDatabaseHas('notifications', ['user_id' => $this->waiter->id, 'read' => false]);

        // Waiter serves everything and checks the bill
        Sanctum::actingAs($this->waiter);
        $this->postJson("/api/v1/orders/{$orderId}/serve-all")->assertOk();

        $bill = $this->getJson("/api/v1/orders/{$orderId}/bill");
        $bill->assertOk();
        $this->assertEqualsWithDelta(200.0, $bill->json('data.totals.subtotal'), 0.001);
        $this->assertEqualsWithDelta(210.0, $bill->json('data.totals.grandTotal'), 0.001); // 5% tax

        // Settle by card
        $pay = $this->postJson("/api/v1/orders/{$orderId}/payments", ['method' => 'card']);
        $pay->assertOk()->assertJsonPath('data.order.is_paid', true);
        $this->assertEqualsWithDelta(210.0, $pay->json('data.paid'), 0.001);

        // Table released for cleaning, then marked clean
        $this->assertSame(TableStatus::CLEANING, $table->fresh()->status);
        $this->assertNull($table->fresh()->current_order_id);

        $this->postJson("/api/v1/tables/{$table->id}/clean")->assertOk();
        $this->assertSame(TableStatus::FREE, $table->fresh()->status);
    }

    public function test_percentage_discount_is_applied_at_payment(): void
    {
        [$table] = $this->makeTables(11);
        Sanctum::actingAs($this->waiter);

        $orderId = $this->postJson('/api/v1/orders', [
            'table_ids' => [$table->id],
            'items' => [['menu_item_id' => $this->menuItem->id, 'quantity' => 2]],
        ])->json('data.id');

        $this->postJson("/api/v1/orders/{$orderId}/serve-all")->assertOk();

        // 10% of 200 = 20 discount → taxable 180 → +5% tax = 189
        $pay = $this->postJson("/api/v1/orders/{$orderId}/payments", [
            'method' => 'cash',
            'discount_type' => 'percentage',
            'discount_value' => 10,
        ]);

        $pay->assertOk();
        $this->assertEqualsWithDelta(189.0, $pay->json('data.paid'), 0.001);
        $this->assertDatabaseHas('payment_transactions', ['order_id' => $orderId, 'status' => 'completed']);
    }

    public function test_paying_twice_is_rejected(): void
    {
        [$table] = $this->makeTables(13);
        Sanctum::actingAs($this->waiter);

        $orderId = $this->postJson('/api/v1/orders', [
            'table_ids' => [$table->id],
            'items' => [['menu_item_id' => $this->menuItem->id, 'quantity' => 1]],
        ])->json('data.id');

        $this->postJson("/api/v1/orders/{$orderId}/serve-all");
        $this->postJson("/api/v1/orders/{$orderId}/payments", ['method' => 'cash'])->assertOk();
        $this->postJson("/api/v1/orders/{$orderId}/payments", ['method' => 'cash'])->assertStatus(422);
    }

    public function test_cancelling_an_order_voids_items_and_frees_tables(): void
    {
        [$table] = $this->makeTables(15);
        Sanctum::actingAs($this->waiter);

        $orderId = $this->postJson('/api/v1/orders', [
            'table_ids' => [$table->id],
            'items' => [['menu_item_id' => $this->menuItem->id, 'quantity' => 1]],
        ])->json('data.id');

        $this->postJson("/api/v1/orders/{$orderId}/cancel", ['reason' => 'Guest left'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $order = Order::find($orderId);
        $this->assertSame(OrderStatus::CANCELLED, $order->status);
        $this->assertSame(OrderStatus::CANCELLED, $order->orderItems()->first()->status);
        $this->assertSame(TableStatus::CLEANING, $table->fresh()->status);
    }

    public function test_unavailable_item_cannot_be_ordered(): void
    {
        [$table] = $this->makeTables(17);
        $this->menuItem->update(['available' => false]);
        Sanctum::actingAs($this->waiter);

        $this->postJson('/api/v1/orders', [
            'table_ids' => [$table->id],
            'items' => [['menu_item_id' => $this->menuItem->id, 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_open_order_lookup_by_table(): void
    {
        [$table] = $this->makeTables(19);
        Sanctum::actingAs($this->waiter);

        $orderId = $this->postJson('/api/v1/orders', [
            'table_ids' => [$table->id],
            'items' => [['menu_item_id' => $this->menuItem->id, 'quantity' => 1]],
        ])->json('data.id');

        $this->getJson("/api/v1/tables/{$table->id}/open-order")
            ->assertOk()
            ->assertJsonPath('data.id', $orderId);
    }

    public function test_validation_rejects_bad_order_payloads(): void
    {
        Sanctum::actingAs($this->waiter);

        $this->postJson('/api/v1/orders', ['table_ids' => []])->assertStatus(422);
        $this->postJson('/api/v1/orders', ['table_ids' => ['not-a-uuid']])->assertStatus(422);
    }
}
