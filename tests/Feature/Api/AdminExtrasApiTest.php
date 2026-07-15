<?php

namespace Tests\Feature\Api;

use App\Enums\TableStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminExtrasApiTest extends TestCase
{
    use RefreshDatabase;

    private MenuItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::ADMIN, 'active' => true]));

        $category = Category::create(['name' => 'Mains', 'is_active' => true]);
        $this->item = MenuItem::create([
            'name' => 'Burger', 'price' => 100, 'category_id' => $category->id,
            'category' => 'Mains', 'available' => true,
        ]);
    }

    public function test_modifier_crud(): void
    {
        $created = $this->postJson("/api/v1/menu-items/{$this->item->id}/modifiers", [
            'name' => 'Extra cheese', 'price' => 20,
        ]);
        $created->assertCreated()->assertJsonPath('data.name', 'Extra cheese');

        $modId = $created->json('data.id');

        $this->patchJson("/api/v1/modifiers/{$modId}", ['price' => 25])
            ->assertOk()
            ->assertJsonPath('data.price', 25);

        // appears nested on the menu item
        $this->getJson("/api/v1/menu-items/{$this->item->id}")
            ->assertOk()
            ->assertJsonPath('data.modifiers.0.name', 'Extra cheese');

        $this->deleteJson("/api/v1/modifiers/{$modId}")->assertOk();
        $this->assertDatabaseMissing('menu_item_modifiers', ['id' => $modId]);
    }

    public function test_table_bulk_create_skips_existing(): void
    {
        Table::create(['number' => 51, 'capacity' => 2, 'status' => TableStatus::FREE]);

        $this->postJson('/api/v1/tables/bulk', [
            'start_number' => 50, 'quantity' => 3, 'capacity' => 4,
        ])->assertCreated(); // 50, 52 created; 51 skipped

        $this->assertTrue(Table::whereIn('number', [50, 51, 52])->count() === 3);
    }

    public function test_table_grouping_and_ungrouping(): void
    {
        $t1 = Table::create(['number' => 61, 'capacity' => 2, 'status' => TableStatus::FREE]);
        $t2 = Table::create(['number' => 62, 'capacity' => 2, 'status' => TableStatus::FREE]);

        $grouped = $this->postJson('/api/v1/tables/group', [
            'table_ids' => [$t1->id, $t2->id],
            'primary_id' => $t1->id,
        ]);
        $grouped->assertOk();

        $groupId = $t1->fresh()->group_id;
        $this->assertNotNull($groupId);
        $this->assertTrue($t1->fresh()->is_primary);
        $this->assertFalse($t2->fresh()->is_primary);

        $this->postJson('/api/v1/tables/ungroup', ['group_id' => $groupId])->assertOk();
        $this->assertNull($t1->fresh()->group_id);
    }

    public function test_grouping_rejects_occupied_tables(): void
    {
        $t1 = Table::create(['number' => 71, 'capacity' => 2, 'status' => TableStatus::FREE]);
        $t2 = Table::create(['number' => 72, 'capacity' => 2, 'status' => TableStatus::OCCUPIED]);

        $this->postJson('/api/v1/tables/group', [
            'table_ids' => [$t1->id, $t2->id],
            'primary_id' => $t1->id,
        ])->assertStatus(422);
    }

    public function test_table_status_update(): void
    {
        $table = Table::create(['number' => 81, 'capacity' => 2, 'status' => TableStatus::FREE]);

        $this->patchJson("/api/v1/tables/{$table->id}/status", ['status' => 'out_of_service'])
            ->assertOk()
            ->assertJsonPath('data.status', 'out_of_service');
    }

    public function test_purchase_order_items_and_receiving_updates_stock(): void
    {
        $supplier = Supplier::create(['name' => 'Fresh Farms']);
        $flour = Ingredient::create(['name' => 'Flour', 'unit' => 'kg', 'stock' => 5, 'min_level' => 2]);

        $po = PurchaseOrder::create(['supplier_id' => $supplier->id, 'status' => 'pending', 'total_cost' => 0]);

        $this->postJson("/api/v1/purchase-orders/{$po->id}/items", [
            'ingredient_id' => $flour->id, 'quantity' => 10, 'unit_cost' => 3,
        ])->assertCreated()->assertJsonPath('data.line_total', 30);

        // total recalculated on the PO
        $this->getJson("/api/v1/purchase-orders/{$po->id}")
            ->assertOk()
            ->assertJsonPath('data.total_cost', 30);

        // receiving adds 10 to flour's stock (5 -> 15) and locks the PO
        $this->postJson("/api/v1/purchase-orders/{$po->id}/receive")
            ->assertOk()
            ->assertJsonPath('data.status', 'received');
        $this->assertEqualsWithDelta(15.0, (float) $flour->fresh()->stock, 0.001);

        // second receive is rejected
        $this->postJson("/api/v1/purchase-orders/{$po->id}/receive")->assertStatus(422);
    }

    public function test_reports_analytics_shape(): void
    {
        $this->getJson('/api/v1/reports/analytics')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'summary' => ['revenue' => ['value', 'change'], 'orders', 'average_order_value'],
                    'daily_trend',
                    'category_distribution',
                    'top_items',
                ],
            ]);
    }
}
