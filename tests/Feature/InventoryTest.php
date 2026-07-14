<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\Inventory;
use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN, 'active' => true]);
        $this->actingAs($this->admin);
    }

    public function test_inventory_component_renders(): void
    {
        Livewire::test(Inventory::class)->assertOk();
    }

    public function test_stats_expose_all_expected_keys(): void
    {
        Ingredient::create(['name' => 'Tomatoes', 'unit' => 'kg', 'stock' => 10, 'min_level' => 2]);
        Ingredient::create(['name' => 'Olive Oil', 'unit' => 'l', 'stock' => 1, 'min_level' => 3]);
        Ingredient::create(['name' => 'Flour', 'unit' => 'kg', 'stock' => 20, 'min_level' => 5]);

        $view = Livewire::test(Inventory::class)->viewData('stats');

        $this->assertSame(3, $view['total']);
        $this->assertSame(1, $view['low'], 'Olive Oil is at/below min level');
        $this->assertSame(2, $view['units'], 'kg and l are the distinct units');
        $this->assertSame(2, $view['healthy']);
    }

    public function test_low_stock_filter_only_returns_depleted_ingredients(): void
    {
        Ingredient::create(['name' => 'Tomatoes', 'unit' => 'kg', 'stock' => 10, 'min_level' => 2]);
        Ingredient::create(['name' => 'Olive Oil', 'unit' => 'l', 'stock' => 1, 'min_level' => 3]);

        $component = Livewire::test(Inventory::class)->set('filterLowStock', true);

        $ingredients = $component->instance()->ingredients;

        $this->assertCount(1, $ingredients);
        $this->assertSame('Olive Oil', $ingredients->first()->name);
    }

    public function test_search_filters_by_name(): void
    {
        Ingredient::create(['name' => 'Tomatoes', 'unit' => 'kg', 'stock' => 10, 'min_level' => 2]);
        Ingredient::create(['name' => 'Basil', 'unit' => 'g', 'stock' => 500, 'min_level' => 100]);

        $component = Livewire::test(Inventory::class)->set('search', 'Toma');

        $ingredients = $component->instance()->ingredients;

        $this->assertCount(1, $ingredients);
        $this->assertSame('Tomatoes', $ingredients->first()->name);
    }
}
