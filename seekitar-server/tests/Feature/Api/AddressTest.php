<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserAddress;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshesDatabase;

    private User $user;
    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->verified()->create(['name' => 'Pemilik', 'address' => 'Alamat']);
        $this->user->setLocation(-7.6, 112.7)->save();

        $this->other = User::factory()->create();
    }

    public function test_create_address(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/addresses', [
                'label'          => 'Rumah',
                'address'        => 'Jl. Merdeka No. 1, Bangil',
                'recipient_name' => 'Budi Santoso',
                'recipient_phone'=> '081234567890',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'label', 'address']]);
    }

    public function test_create_address_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/addresses', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['label', 'address']);
    }

    public function test_list_addresses(): void
    {
        UserAddress::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/addresses');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);
    }

    public function test_update_address(): void
    {
        $address = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'label'   => 'Lama',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/addresses/{$address->id}", [
                'label' => 'Baru',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.label', 'Baru');
    }

    public function test_update_address_returns_404_for_wrong_user(): void
    {
        $address = UserAddress::factory()->create(['user_id' => $this->other->id]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/addresses/{$address->id}", [
                'label' => 'Dicuri',
            ]);

        $response->assertStatus(404);
    }

    public function test_delete_address(): void
    {
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/addresses/{$address->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('user_addresses', ['id' => $address->id]);
    }

    public function test_delete_address_returns_404_for_wrong_user(): void
    {
        $address = UserAddress::factory()->create(['user_id' => $this->other->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/addresses/{$address->id}");

        $response->assertStatus(404);
    }

    public function test_set_default_address(): void
    {
        $address = UserAddress::factory()->create([
            'user_id'    => $this->user->id,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/addresses/{$address->id}/default");

        $response->assertOk()
            ->assertJsonPath('data.is_default', true);
    }

    public function test_set_default_address_returns_404_for_wrong_user(): void
    {
        $address = UserAddress::factory()->create(['user_id' => $this->other->id]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/addresses/{$address->id}/default");

        $response->assertStatus(404);
    }

    public function test_create_address_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/addresses', []);

        $response->assertStatus(401);
    }
}
