<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingsProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_are_readable_by_every_role_and_seed_defaults(): void
    {
        // No Setting row yet — the endpoint should create defaults
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::WAITER, 'active' => true]));

        $response = $this->getJson('/api/v1/settings');

        $response->assertOk()
            ->assertJsonPath('data.currency', '₹')
            ->assertJsonPath('data.tax_enabled', true);
        $this->assertEqualsCanonicalizing(['cash', 'card', 'upi'], $response->json('data.enabled_payment_methods'));
        $this->assertDatabaseCount('settings', 1);
    }

    public function test_kitchen_can_read_but_not_update_settings(): void
    {
        Setting::create(['restaurant_name' => 'Test', 'currency' => '₹', 'tax_enabled' => true, 'tax_rate' => 5]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::KITCHEN, 'active' => true]));

        $this->getJson('/api/v1/settings')->assertOk();
        $this->patchJson('/api/v1/settings', ['tax_rate' => 12])->assertForbidden();
    }

    public function test_admin_updates_settings(): void
    {
        Setting::create(['restaurant_name' => 'Old', 'currency' => '₹', 'tax_enabled' => true, 'tax_rate' => 5]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::ADMIN, 'active' => true]));

        $this->patchJson('/api/v1/settings', [
            'restaurant_name' => 'New Bistro',
            'tax_rate' => 8,
            'enabled_payment_methods' => ['cash', 'upi'],
            'discount_presets' => [5, 25],
        ])->assertOk()
            ->assertJsonPath('data.restaurant_name', 'New Bistro')
            ->assertJsonPath('data.tax_rate', 8);

        $this->assertDatabaseHas('settings', ['restaurant_name' => 'New Bistro']);
    }

    public function test_settings_update_validates(): void
    {
        Setting::create(['restaurant_name' => 'Test', 'tax_rate' => 5]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::ADMIN, 'active' => true]));

        $this->patchJson('/api/v1/settings', ['tax_rate' => 150])->assertStatus(422);
        $this->patchJson('/api/v1/settings', ['enabled_payment_methods' => ['bitcoin']])->assertStatus(422);
    }

    public function test_user_updates_own_profile(): void
    {
        $user = User::factory()->create(['role' => UserRole::WAITER, 'active' => true, 'theme' => 'light', 'notifications_enabled' => true]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/auth/profile', [
            'theme' => 'dark',
            'notifications_enabled' => false,
        ])->assertOk()
            ->assertJsonPath('data.theme', 'dark')
            ->assertJsonPath('data.notifications_enabled', false);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'theme' => 'dark']);
    }

    public function test_profile_update_rejects_bad_theme(): void
    {
        Sanctum::actingAs(User::factory()->create(['active' => true]));

        $this->patchJson('/api/v1/auth/profile', ['theme' => 'neon'])->assertStatus(422);
    }
}
