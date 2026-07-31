<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshesDatabase;

    private User $owner;
    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->verified()->create(['name' => 'Pemilik Toko', 'address' => 'Jl. Test 1']);
        $this->owner->setLocation(-7.6, 112.7)->save();

        $this->other = User::factory()->verified()->create(['name' => 'Pengguna Lain', 'address' => 'Jl. Test 2']);
        $this->other->setLocation(-7.7, 112.8)->save();

        Category::factory()->create(['id' => 1]);
    }

    public function test_create_store(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/stores', [
                'name'         => 'Toko Barokah Test',
                'store_type'   => ['goods', 'services'],
                'category_ids' => [1],
                'latitude'     => -7.6,
                'longitude'    => 112.7,
                'address'      => 'Jl. Merdeka No. 1',
                'photo'        => UploadedFile::fake()->image('toko.jpg'),
                'accepts_cod'  => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['store' => ['id', 'name', 'store_type', 'address']]]);
    }

    public function test_create_store_fails_when_not_verified(): void
    {
        $user = User::factory()->create(['name' => 'Test', 'address' => 'Alamat']);
        $user->setLocation(-7.6, 112.7)->save();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/stores', [
                'name'         => 'Toko Gagal',
                'store_type'   => ['goods'],
                'category_ids' => [1],
                'latitude'     => -7.6,
                'longitude'    => 112.7,
                'address'      => 'Jl. Gagal',
            ]);

        $response->assertStatus(403);
    }

    public function test_create_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/stores', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'store_type', 'category_ids', 'latitude', 'longitude']);
    }

    public function test_list_own_stores(): void
    {
        Store::factory()->count(3)->create(['user_id' => $this->owner->id]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/stores/mine');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_show_store(): void
    {
        $store = Store::factory()->terverifikasi()->create(['user_id' => $this->owner->id]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/stores/{$store->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['store' => ['id', 'name']]]);
    }

    public function test_show_store_returns_404_for_hidden_store(): void
    {
        $store = Store::factory()->menunggu()->create(['user_id' => $this->other->id]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/stores/{$store->id}");

        $response->assertStatus(404);
    }

    public function test_update_store(): void
    {
        $store = Store::factory()->create(['user_id' => $this->owner->id, 'name' => 'Nama Lama']);

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/v1/stores/{$store->id}", [
                'name' => 'Nama Baru',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.store.name', 'Nama Baru');
    }

    public function test_update_store_forbidden_for_non_owner(): void
    {
        $store = Store::factory()->create(['user_id' => $this->owner->id]);

        $response = $this->actingAs($this->other)
            ->patchJson("/api/v1/stores/{$store->id}", [
                'name' => 'Dicuri',
            ]);

        $response->assertStatus(403);
    }

    public function test_nearby_stores(): void
    {
        Store::factory()->terverifikasi()->count(5)->create();

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/stores/nearby?lat=-7.6&lng=112.7&radius=50');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_nearby_stores_validates_coordinates(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/stores/nearby');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lat', 'lng']);
    }

    public function test_create_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/stores', []);

        $response->assertStatus(401);
    }
}
