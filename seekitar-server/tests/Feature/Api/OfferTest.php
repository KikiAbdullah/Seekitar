<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\CustomerRequest;
use App\Models\Offer;
use App\Models\Store;
use App\Models\User;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class OfferTest extends TestCase
{
    use RefreshesDatabase;

    private User $buyer;
    private User $seller;
    private Store $store;
    private CustomerRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->verified()->create(['name' => 'Pembeli', 'address' => 'Alamat']);
        $this->buyer->setLocation(-7.6, 112.7)->save();

        $this->seller = User::factory()->verified()->create(['name' => 'Penjual', 'address' => 'Alamat 2']);
        $this->seller->setLocation(-7.6, 112.7)->save();

        $this->store = Store::factory()->terverifikasi()->create(['user_id' => $this->seller->id]);

        Category::factory()->create(['id' => 1]);

        $this->request = CustomerRequest::factory()->terbuka()->create(['user_id' => $this->buyer->id]);
    }

    public function test_create_offer(): void
    {
        $response = $this->actingAs($this->seller)
            ->postJson("/api/v1/requests/{$this->request->id}/offers", [
                'store_id'        => $this->store->id,
                'price'           => 150000,
                'estimation_time' => 'Hari ini juga',
                'estimated_hours' => 4,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['offer' => ['id', 'price', 'status']]]);
    }

    public function test_create_offer_fails_for_own_request(): void
    {
        $response = $this->actingAs($this->buyer)
            ->postJson("/api/v1/requests/{$this->request->id}/offers", [
                'store_id'        => $this->store->id,
                'price'           => 150000,
                'estimation_time' => 'Hari ini juga',
            ]);

        $response->assertStatus(403);
    }

    public function test_create_offer_fails_for_duplicate(): void
    {
        Offer::factory()->create([
            'request_id' => $this->request->id,
            'store_id'   => $this->store->id,
        ]);

        $response = $this->actingAs($this->seller)
            ->postJson("/api/v1/requests/{$this->request->id}/offers", [
                'store_id'        => $this->store->id,
                'price'           => 200000,
                'estimation_time' => 'Besok',
            ]);

        $response->assertStatus(422);
    }

    public function test_create_offer_validates_required_fields(): void
    {
        $response = $this->actingAs($this->seller)
            ->postJson("/api/v1/requests/{$this->request->id}/offers", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['store_id', 'price', 'estimation_time']);
    }

    public function test_show_offer(): void
    {
        $offer = Offer::factory()->create([
            'request_id' => $this->request->id,
            'store_id'   => $this->store->id,
        ]);

        $response = $this->actingAs($this->buyer)
            ->getJson("/api/v1/offers/{$offer->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['offer' => ['id', 'price', 'status']]]);
    }

    public function test_show_offer_forbidden_for_non_party(): void
    {
        $stranger = User::factory()->create();
        $offer = Offer::factory()->create([
            'request_id' => $this->request->id,
            'store_id'   => $this->store->id,
        ]);

        $response = $this->actingAs($stranger)
            ->getJson("/api/v1/offers/{$offer->id}");

        $response->assertStatus(403);
    }

    public function test_accept_offer_creates_order(): void
    {
        $offer = Offer::factory()->create([
            'request_id' => $this->request->id,
            'store_id'   => $this->store->id,
        ]);

        $response = $this->actingAs($this->buyer)
            ->patchJson("/api/v1/offers/{$offer->id}/accept");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['order' => ['id', 'status']]]);

        $this->assertDatabaseHas('offers', [
            'id'     => $offer->id,
            'status' => 'accepted',
        ]);

        $this->assertDatabaseHas('orders', [
            'offer_id' => $offer->id,
            'buyer_id' => $this->buyer->id,
        ]);
    }

    public function test_accept_offer_fails_for_seller(): void
    {
        $offer = Offer::factory()->create([
            'request_id' => $this->request->id,
            'store_id'   => $this->store->id,
        ]);

        $response = $this->actingAs($this->seller)
            ->patchJson("/api/v1/offers/{$offer->id}/accept");

        $response->assertStatus(403);
    }

    public function test_accept_offer_fails_when_already_accepted(): void
    {
        $offer = Offer::factory()->diterima()->create([
            'request_id' => $this->request->id,
            'store_id'   => $this->store->id,
        ]);

        $response = $this->actingAs($this->buyer)
            ->patchJson("/api/v1/offers/{$offer->id}/accept");

        $response->assertStatus(403);
    }

    public function test_create_offer_requires_authentication(): void
    {
        $response = $this->postJson("/api/v1/requests/{$this->request->id}/offers", []);

        $response->assertStatus(401);
    }
}
