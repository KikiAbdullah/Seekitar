<?php

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\User;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshesDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_list_notifications(): void
    {
        Notification::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_unread_count(): void
    {
        Notification::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications/unread-count');

        $response->assertOk()
            ->assertJsonPath('data.count', 3);
    }

    public function test_mark_all_as_read(): void
    {
        Notification::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->patchJson('/api/v1/notifications/read-all');

        $response->assertOk()
            ->assertJsonPath('message', 'Semua notifikasi telah dibaca.');

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);
    }

    public function test_mark_notification_as_read(): void
    {
        $notification = Notification::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('notifications', [
            'id'      => $notification->id,
            'read_at' => now()->format('Y-m-d H:i'),
        ]);
    }

    public function test_mark_others_notification_returns_404(): void
    {
        $other = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(404);
    }

    public function test_list_notifications_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(401);
    }
}
