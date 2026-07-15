<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_user(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::WAITER,
            'active' => true,
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
            'device_name' => 'pixel-8',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'waiter')
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'role'], 'token']]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'pixel-8',
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create(['active' => true, 'password' => 'secret123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    public function test_login_rejects_inactive_user(): void
    {
        $user = User::factory()->create(['active' => false, 'password' => 'secret123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertForbidden();
    }

    public function test_me_returns_profile(): void
    {
        $user = User::factory()->create(['role' => UserRole::KITCHEN, 'active' => true]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', 'kitchen');
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create(['active' => true, 'password' => 'secret123']);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->json('data.token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unauthenticated_requests_get_json_401(): void
    {
        $this->getJson('/api/v1/orders')->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    public function test_change_password_requires_correct_current_password(): void
    {
        $user = User::factory()->create(['active' => true, 'password' => 'secret123']);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'nope',
            'password' => 'new-password-9',
            'password_confirmation' => 'new-password-9',
        ])->assertStatus(422);

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'secret123',
            'password' => 'new-password-9',
            'password_confirmation' => 'new-password-9',
        ])->assertOk();
    }
}
