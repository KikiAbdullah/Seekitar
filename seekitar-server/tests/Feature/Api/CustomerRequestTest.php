<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\CustomerRequest;
use App\Models\User;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class CustomerRequestTest extends TestCase
{
    use RefreshesDatabase;

    private User $buyer;
    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->verified()->create(['name' => 'Pembeli', 'address' => 'Alamat']);
        $this->buyer->setLocation(-7.6, 112.7)->save();

        $this->other = User::factory()->verified()->create(['name' => 'Lain', 'address' => 'Alamat Lain']);
        $this->other->setLocation(-7.7, 112.8)->save();

        Category::factory()->create(['id' => 1]);
    }

    public function test_create_request(): void
    {
        $response = $this->actingAs($this->buyer)
            ->postJson('/api/v1/requests', [
                'title'       => 'Cari tukang servis AC panggilan',
                'description' => 'Butuh servis AC untuk 3 unit di rumah. Lokasi Kecamatan Bangil.',
                'category_id' => 1,
                'latitude'    => -7.6,
                'longitude'   => 112.7,
                'budget_min'  => 100000,
                'budget_max'  => 250000,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['request' => ['id', 'title', 'status']]]);
    }

    public function test_create_request_validates_required_fields(): void
    {
        $response = $this->actingAs($this->buyer)
            ->postJson('/api/v1/requests', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'category_id', 'latitude', 'longitude']);
    }

    public function test_search_requests(): void
    {
        CustomerRequest::factory()->terbuka()->count(3)->create(
            fn () => ['user_id' => User::factory()->verified()]
        );

        $response = $this->actingAs($this->other)
            ->getJson('/api/v1/requests?lat=-7.6&lng=112.7&radius=50');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_search_requests_excludes_own(): void
    {
        CustomerRequest::factory()->terbuka()->create(['user_id' => $this->buyer->id]);

        $response = $this->actingAs($this->buyer)
            ->getJson('/api/v1/requests?lat=-7.6&lng=112.7&radius=50');

        $response->assertOk();

        $this->assertCount(0, $response->json('data'));
    }

    public function test_search_requests_requires_coordinates(): void
    {
        $response = $this->actingAs($this->buyer)
            ->getJson('/api/v1/requests');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lat', 'lng']);
    }

    public function test_list_own_requests(): void
    {
        CustomerRequest::factory()->count(3)->create(['user_id' => $this->buyer->id]);

        $response = $this->actingAs($this->buyer)
            ->getJson('/api/v1/requests/mine');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_show_request(): void
    {
        $request = CustomerRequest::factory()->create(['user_id' => $this->buyer->id]);

        $response = $this->actingAs($this->buyer)
            ->getJson("/api/v1/requests/{$request->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['request' => ['id', 'title']]]);
    }

    public function test_update_request(): void
    {
        $request = CustomerRequest::factory()->create([
            'user_id' => $this->buyer->id,
            'title'   => 'Judul Lama',
        ]);

        $response = $this->actingAs($this->buyer)
            ->patchJson("/api/v1/requests/{$request->id}", [
                'title' => 'Judul Baru',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.request.title', 'Judul Baru');
    }

    public function test_update_request_forbidden_for_non_owner(): void
    {
        $request = CustomerRequest::factory()->create(['user_id' => $this->buyer->id]);

        $response = $this->actingAs($this->other)
            ->patchJson("/api/v1/requests/{$request->id}", [
                'title' => 'Dicuri',
            ]);

        $response->assertStatus(403);
    }

    public function test_delete_request(): void
    {
        $request = CustomerRequest::factory()->create(['user_id' => $this->buyer->id]);

        $response = $this->actingAs($this->buyer)
            ->deleteJson("/api/v1/requests/{$request->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('customer_requests', ['id' => $request->id]);
    }

    public function test_delete_closed_request_fails(): void
    {
        $request = CustomerRequest::factory()->ditutup()->create(['user_id' => $this->buyer->id]);

        $response = $this->actingAs($this->buyer)
            ->deleteJson("/api/v1/requests/{$request->id}");

        $response->assertStatus(422);
    }

    public function test_delete_request_forbidden_for_non_owner(): void
    {
        $request = CustomerRequest::factory()->create(['user_id' => $this->buyer->id]);

        $response = $this->actingAs($this->other)
            ->deleteJson("/api/v1/requests/{$request->id}");

        $response->assertStatus(403);
    }

    public function test_extend_request(): void
    {
        $request = CustomerRequest::factory()->terbuka()->create([
            'user_id'         => $this->buyer->id,
            'extension_count' => 0,
            'expires_at'      => now()->addHour(),
        ]);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/v1/requests/{$request->id}/extend");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('customer_requests', [
            'id'              => $request->id,
            'extension_count' => 1,
        ]);
    }

    public function test_extend_request_fails_when_max_reached(): void
    {
        $request = CustomerRequest::factory()->create([
            'user_id'         => $this->buyer->id,
            'extension_count' => 2,
            'expires_at'      => now()->addHour(),
        ]);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/v1/requests/{$request->id}/extend");

        $response->assertStatus(422);
    }

    public function test_extend_request_forbidden_for_non_owner(): void
    {
        $request = CustomerRequest::factory()->terbuka()->create([
            'user_id'         => $this->buyer->id,
            'extension_count' => 0,
        ]);

        $response = $this->actingAs($this->other)
            ->postJson("/api/v1/requests/{$request->id}/extend");

        $response->assertStatus(403);
    }
}
