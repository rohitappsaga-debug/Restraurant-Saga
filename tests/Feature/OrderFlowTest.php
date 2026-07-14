<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use App\Services\KOTService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $waiter;
    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->waiter = User::factory()->create(['role' => UserRole::WAITER, 'active' => true]);
        $this->orderService = app(OrderService::class);
    }

    private function makeTables(int ...$numbers): array
    {
        return array_map(
            fn ($n) => Table::create(['number' => $n, 'capacity' => 4, 'status' => TableStatus::FREE]),
            $numbers
        );
    }

    public function test_order_can_span_multiple_tables(): void
    {
        [$t5, $t6] = $this->makeTables(5, 6);

        $order = $this->orderService->createOrder([$t5->id, $t6->id], $this->waiter->id);

        $this->assertCount(2, $order->tables);
        $this->assertSame('5+6', $order->table_label);
        $this->assertSame(5, $order->table_number, 'primary table is the lowest number');

        foreach ([$t5, $t6] as $table) {
            $table->refresh();
            $this->assertSame(TableStatus::OCCUPIED, $table->status);
            $this->assertSame($order->id, $table->current_order_id);
        }
    }

    public function test_table_with_open_order_cannot_join_another_order(): void
    {
        [$t1, $t2] = $this->makeTables(1, 2);

        $this->orderService->createOrder([$t1->id], $this->waiter->id);

        $this->expectException(\DomainException::class);

        try {
            $this->orderService->createOrder([$t1->id, $t2->id], $this->waiter->id);
        } finally {
            // The failed attempt must not create anything or occupy table 2
            $this->assertSame(1, Order::count());
            $this->assertSame(TableStatus::FREE, $t2->fresh()->status);
            $this->assertNull($t2->fresh()->current_order_id);
        }
    }

    public function test_cleaning_table_cannot_be_ordered(): void
    {
        $table = Table::create(['number' => 3, 'capacity' => 2, 'status' => TableStatus::CLEANING]);

        $this->expectException(\DomainException::class);
        $this->orderService->createOrder([$table->id], $this->waiter->id);
    }

    public function test_open_order_for_table_resolves_and_self_heals(): void
    {
        [$table] = $this->makeTables(7);
        $order = $this->orderService->createOrder([$table->id], $this->waiter->id);

        $this->assertTrue($this->orderService->openOrderForTable($table->fresh())->is($order));

        // Stale pointer: order got paid but pointer left behind
        $order->update(['is_paid' => true]);
        $stale = $table->fresh();
        $this->assertNull($this->orderService->openOrderForTable($stale));
        $this->assertNull($stale->fresh()->current_order_id, 'stale pointer is cleared');
    }

    public function test_each_kitchen_round_creates_a_new_kot_batch(): void
    {
        [$table] = $this->makeTables(8);
        $order = $this->orderService->createOrder([$table->id], $this->waiter->id);

        $category = Category::create(['name' => 'Test', 'is_active' => true]);
        $menuItem = MenuItem::create([
            'name' => 'Test Dish', 'price' => 10, 'category_id' => $category->id,
            'category' => 'Test', 'available' => true, 'is_veg' => true,
        ]);

        $kotService = app(KOTService::class);

        foreach ([1, 2] as $round) {
            $order->orderItems()->create([
                'menu_item_id' => $menuItem->id,
                'quantity' => $round,
                'status' => OrderStatus::PENDING,
            ]);
            $kotService->sendToKitchen($order);
        }

        $this->assertSame(2, $order->kots()->count());
        $this->assertSame([1, 2], $order->kots()->orderBy('batch_number')->pluck('batch_number')->all());
        $this->assertSame(0, $order->orderItems()->where('status', OrderStatus::PENDING)->count(), 'items moved to SENT');
    }

    public function test_release_tables_frees_all_tables_of_the_order(): void
    {
        [$t5, $t6] = $this->makeTables(5, 6);
        $order = $this->orderService->createOrder([$t5->id, $t6->id], $this->waiter->id);

        $this->orderService->releaseTables($order);

        foreach ([$t5, $t6] as $table) {
            $table->refresh();
            $this->assertSame(TableStatus::CLEANING, $table->status);
            $this->assertNull($table->current_order_id);
        }

        $this->assertCount(2, $order->tables()->get(), 'pivot rows remain as history');
    }
}
