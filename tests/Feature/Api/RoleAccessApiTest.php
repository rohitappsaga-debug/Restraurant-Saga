<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleAccessApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(UserRole $role): User
    {
        $user = User::factory()->create(['role' => $role, 'active' => true]);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_kitchen_cannot_touch_waiter_endpoints(): void
    {
        $this->actingAsRole(UserRole::KITCHEN);

        $this->getJson('/api/v1/tables')->assertForbidden();
        $this->getJson('/api/v1/orders')->assertForbidden();
        $this->postJson('/api/v1/orders', ['table_ids' => ['x']])->assertForbidden();
    }

    public function test_waiter_cannot_touch_kitchen_or_admin_endpoints(): void
    {
        $this->actingAsRole(UserRole::WAITER);

        $this->getJson('/api/v1/kitchen/queue')->assertForbidden();
        $this->getJson('/api/v1/dashboard')->assertForbidden();
        $this->getJson('/api/v1/users')->assertForbidden();
        $this->postJson('/api/v1/categories', ['name' => 'X'])->assertForbidden();
        $this->getJson('/api/v1/reports/sales')->assertForbidden();
    }

    public function test_admin_can_reach_everything(): void
    {
        $this->actingAsRole(UserRole::ADMIN);

        $this->getJson('/api/v1/tables')->assertOk();
        $this->getJson('/api/v1/orders')->assertOk();
        $this->getJson('/api/v1/kitchen/queue')->assertOk();
        $this->getJson('/api/v1/dashboard')->assertOk();
        $this->getJson('/api/v1/users')->assertOk();
        $this->getJson('/api/v1/suppliers')->assertOk();
    }

    public function test_manager_can_reach_admin_endpoints(): void
    {
        $this->actingAsRole(UserRole::MANAGER);

        $this->getJson('/api/v1/dashboard')->assertOk();
        $this->getJson('/api/v1/reports/sales')->assertOk();
    }

    public function test_all_roles_can_browse_the_menu(): void
    {
        foreach ([UserRole::WAITER, UserRole::KITCHEN, UserRole::ADMIN] as $role) {
            $this->actingAsRole($role);
            $this->getJson('/api/v1/menu-items')->assertOk();
            $this->getJson('/api/v1/categories')->assertOk();
        }
    }
}
