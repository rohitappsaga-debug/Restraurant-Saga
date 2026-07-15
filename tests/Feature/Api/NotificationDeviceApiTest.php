<?php

namespace Tests\Feature\Api;

use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationDeviceApiTest extends TestCase
{
    use RefreshDatabase;

    private User $waiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->waiter = User::factory()->create(['role' => UserRole::WAITER, 'active' => true]);
        Sanctum::actingAs($this->waiter);
    }

    public function test_lists_own_notifications_only(): void
    {
        $other = User::factory()->create(['role' => UserRole::WAITER, 'active' => true]);

        Notification::create(['type' => NotificationType::ALERT, 'message' => 'mine', 'user_id' => $this->waiter->id, 'read' => false]);
        Notification::create(['type' => NotificationType::ALERT, 'message' => 'also mine', 'user_id' => $this->waiter->id, 'read' => true]);
        Notification::create(['type' => NotificationType::ALERT, 'message' => 'not mine', 'user_id' => $other->id, 'read' => false]);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertOk();
        $messages = collect($response->json('data'))->pluck('message');
        $this->assertEqualsCanonicalizing(['mine', 'also mine'], $messages->all());

        $unread = $this->getJson('/api/v1/notifications?unread_only=1');
        $this->assertEqualsCanonicalizing(['mine'], collect($unread->json('data'))->pluck('message')->all());
    }

    public function test_mark_read_endpoints(): void
    {
        $mine = Notification::create(['type' => NotificationType::ALERT, 'message' => 'mine', 'user_id' => $this->waiter->id, 'read' => false]);
        $other = Notification::create([
            'type' => NotificationType::ALERT, 'message' => 'not mine',
            'user_id' => User::factory()->create()->id, 'read' => false,
        ]);

        $this->patchJson("/api/v1/notifications/{$mine->id}/read")
            ->assertOk()
            ->assertJsonPath('data.read', true);

        $this->patchJson("/api/v1/notifications/{$other->id}/read")->assertForbidden();

        Notification::create(['type' => NotificationType::ALERT, 'message' => 'bulk', 'user_id' => $this->waiter->id, 'read' => false]);
        $this->postJson('/api/v1/notifications/read-all')->assertOk();

        $this->assertSame(0, Notification::where('user_id', $this->waiter->id)->where('read', false)->count());
    }

    public function test_device_registration_is_idempotent_per_token(): void
    {
        $payload = ['token' => 'fcm-token-abc', 'platform' => 'android'];

        $this->postJson('/api/v1/devices', $payload)->assertCreated();
        $this->postJson('/api/v1/devices', $payload)->assertCreated();

        $this->assertDatabaseCount('device_tokens', 1);

        $this->deleteJson('/api/v1/devices', ['token' => 'fcm-token-abc'])->assertOk();
        $this->assertDatabaseCount('device_tokens', 0);
    }

    public function test_device_registration_validates_platform(): void
    {
        $this->postJson('/api/v1/devices', ['token' => 'x', 'platform' => 'windows'])->assertStatus(422);
    }
}
