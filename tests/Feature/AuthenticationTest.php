<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'active' => true,
        ]);
    }

    public function test_login_pages_render(): void
    {
        $this->get('/admin/login')->assertOk();
        $this->get('/waiter/login')->assertOk();
        $this->get('/kitchen/login')->assertOk();
    }

    public function test_home_role_selection_page_renders(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $user = $this->makeUser(UserRole::ADMIN);

        Livewire::test(\App\Livewire\Admin\Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = $this->makeUser(UserRole::ADMIN);

        Livewire::test(\App\Livewire\Admin\Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }

    public function test_waiter_cannot_access_admin_area(): void
    {
        $waiter = $this->makeUser(UserRole::WAITER);

        $this->actingAs($waiter)
            ->get('/admin/dashboard')
            ->assertRedirect(route('home'));
    }

    public function test_kitchen_user_cannot_access_admin_area(): void
    {
        $kitchen = $this->makeUser(UserRole::KITCHEN);

        $this->actingAs($kitchen)
            ->get('/admin/dashboard')
            ->assertRedirect(route('home'));
    }

    public function test_waiter_can_access_waiter_dashboard(): void
    {
        $waiter = $this->makeUser(UserRole::WAITER);

        $this->actingAs($waiter)
            ->get('/waiter/dashboard')
            ->assertOk();
    }

    public function test_kitchen_user_can_access_kitchen_dashboard(): void
    {
        $kitchen = $this->makeUser(UserRole::KITCHEN);

        $this->actingAs($kitchen)
            ->get('/kitchen/dashboard')
            ->assertOk();
    }

    public function test_admin_cannot_access_waiter_dashboard(): void
    {
        $admin = $this->makeUser(UserRole::ADMIN);

        $this->actingAs($admin)
            ->get('/waiter/dashboard')
            ->assertRedirect(route('home'));
    }
}
