<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\Table;
use App\Models\User;
use App\Services\BillingService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BillingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $waiter;
    private OrderService $orderService;
    private BillingService $billingService;
    private MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->waiter = User::factory()->create(['role' => UserRole::WAITER, 'active' => true]);
        $this->orderService = app(OrderService::class);
        $this->billingService = app(BillingService::class);

        Setting::create(['restaurant_name' => 'Test', 'currency' => '₹', 'tax_enabled' => true, 'tax_rate' => 5]);

        $category = Category::create(['name' => 'Test', 'is_active' => true]);
        $this->menuItem = MenuItem::create([
            'name' => 'Test Dish', 'price' => 100, 'category_id' => $category->id,
            'category' => 'Test', 'available' => true, 'is_veg' => true,
        ]);
    }

    private function makeOrderWithItems(int $quantity = 2): \App\Models\Order
    {
        $table = Table::create(['number' => rand(100, 999), 'capacity' => 4, 'status' => TableStatus::FREE]);
        $order = $this->orderService->createOrder([$table->id], $this->waiter->id);
        $order->orderItems()->create([
            'menu_item_id' => $this->menuItem->id,
            'quantity' => $quantity,
            'status' => OrderStatus::SERVED,
        ]);

        return $order->fresh();
    }

    public function test_order_totals_math_with_tax(): void
    {
        $order = $this->makeOrderWithItems(2); // 2 x 100

        $totals = $this->billingService->calculateOrderTotals($order);

        $this->assertEqualsWithDelta(200.00, $totals['subtotal'], 0.001);
        $this->assertEqualsWithDelta(10.00, $totals['taxTotal'], 0.001); // 5%
        $this->assertEqualsWithDelta(210.00, $totals['grandTotal'], 0.001);
        $this->assertEqualsWithDelta(210.00, $totals['remainingDue'], 0.001);
    }

    public function test_order_totals_respect_stored_discount(): void
    {
        $order = $this->makeOrderWithItems(2);
        $order->update(['discount_type' => 'fixed', 'discount_value' => 50]);

        $totals = $this->billingService->calculateOrderTotals($order->fresh());

        $this->assertEqualsWithDelta(50.00, $totals['discountTotal'], 0.001);
        $this->assertEqualsWithDelta(157.50, $totals['grandTotal'], 0.001); // (200-50) * 1.05
    }

    public function test_partial_payment_reduces_remaining_due(): void
    {
        $order = $this->makeOrderWithItems(2); // grand total 210

        $order->paymentTransactions()->create(['amount' => 100, 'method' => 'cash', 'status' => 'completed']);

        $totals = $this->billingService->calculateOrderTotals($order->fresh());

        $this->assertEqualsWithDelta(100.00, $totals['alreadyPaid'], 0.001);
        $this->assertEqualsWithDelta(110.00, $totals['remainingDue'], 0.001);
    }

    public function test_settle_order_records_transaction_and_frees_tables(): void
    {
        $order = $this->makeOrderWithItems(2);
        $tables = $order->tables;

        $this->orderService->settleOrder($order, 'card', 210.00);

        $order->refresh();
        $this->assertTrue($order->is_paid);
        $this->assertSame(OrderStatus::DELIVERED, $order->status);
        $this->assertSame(1, $order->paymentTransactions()->count());
        $this->assertEqualsWithDelta(210.00, (float) $order->paymentTransactions()->first()->amount, 0.001);

        foreach ($tables as $table) {
            $table->refresh();
            $this->assertSame(TableStatus::CLEANING, $table->status);
            $this->assertNull($table->current_order_id);
        }
    }

    public function test_admin_billing_mark_as_paid_creates_transaction_and_frees_tables(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'active' => true]);
        $order = $this->makeOrderWithItems(1); // 100 + 5% = 105
        $table = $order->tables->first();

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Billing::class)
            ->call('markAsPaid', $order->id, 'upi');

        $order->refresh();
        $this->assertTrue($order->is_paid);
        $this->assertSame(1, $order->paymentTransactions()->count());
        $this->assertEqualsWithDelta(105.00, (float) $order->paymentTransactions()->first()->amount, 0.001);
        $this->assertSame(TableStatus::CLEANING, $table->fresh()->status);
    }

    public function test_admin_pending_bills_lists_open_orders(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'active' => true]);
        $open = $this->makeOrderWithItems(1);
        $paid = $this->makeOrderWithItems(1);
        $this->orderService->settleOrder($paid, 'cash', 105.00);

        $component = Livewire::actingAs($admin)->test(\App\Livewire\Admin\Billing::class);
        $pending = $component->instance()->pendingOrders;

        $this->assertTrue($pending->contains('id', $open->id));
        $this->assertFalse($pending->contains('id', $paid->id));
    }
}
