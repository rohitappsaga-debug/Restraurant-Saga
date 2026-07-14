<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public static function adminRoutes(): array
    {
        return [
            'dashboard' => ['/admin/dashboard'],
            'categories' => ['/admin/categories'],
            'menu' => ['/admin/menu'],
            'tables' => ['/admin/tables'],
            'orders' => ['/admin/orders'],
            'reports' => ['/admin/reports'],
            'users' => ['/admin/users'],
            'billing' => ['/admin/billing'],
            'settings' => ['/admin/settings'],
            'audit-logs' => ['/admin/audit-logs'],
            'suppliers' => ['/admin/suppliers'],
            'inventory' => ['/admin/inventory'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_admin_page_renders_for_admin(string $uri): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'active' => true]);

        $this->actingAs($admin)->get($uri)->assertOk();
    }

    #[DataProvider('adminRoutes')]
    public function test_admin_page_blocked_for_guests(string $uri): void
    {
        $this->get($uri)->assertRedirect(route('login'));
    }
}
