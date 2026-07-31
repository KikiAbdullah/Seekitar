<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Listing;
use App\Models\Store;
use App\Models\User;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class ListingTest extends TestCase
{
    use RefreshesDatabase;

    private User $owner;
    private User $buyer;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->verified()->create(['name' => 'Pemilik', 'address' => 'Alamat']);
        $this->owner->setLocation(-7.6, 112.7)->save();

        $this->buyer = User::factory()->verified()->create(['name' => 'Pembeli', 'address' => 'Alamat 2']);
        $this->buyer->setLocation(-7.6, 112.7)->save();

        $this->store = Store::factory()->terverifikasi()->create(['user_id' => $this->owner->id]);

        Category::factory()->create(['id' => 1]);
    }

    public function test_create_listing(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/listings', [
                'store_id'     => $this->store->id,
                'title'        => 'Beras Premium 5 kg Baru',
                'description'  => 'Beras kualitas terbaik langsung dari petani.',
                'listing_type' => 'product',
                'price'        => 75000,
                'stock_qty'    => 50,
                'images'       => ['https://picsum.photos/800/600'],
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['listing' => ['id', 'title', 'price']]]);
    }

    public function test_create_listing_fails_for_unverified_store(): void
    {
        $pendingStore = Store::factory()->menunggu()->create(['user_id' => $this->owner->id]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/listings', [
                'store_id'     => $pendingStore->id,
                'title'        => 'Tidak Boleh',
                'description'  => 'Toko belum terverifikasi.',
                'listing_type' => 'product',
                'price'        => 50000,
                'stock_qty'    => 10,
                'images'       => ['https://picsum.photos/800/600'],
            ]);

        $response->assertStatus(403);
    }

    public function test_create_listing_validates_required_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/listings', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['store_id', 'title', 'description', 'listing_type', 'images']);
    }

    public function test_search_listings(): void
    {
        Listing::factory()->aktif()->count(5)->create();

        $response = $this->actingAs($this->buyer)
            ->getJson('/api/v1/listings?lat=-7.6&lng=112.7&radius=50');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_search_listings_requires_coordinates(): void
    {
        $response = $this->actingAs($this->buyer)
            ->getJson('/api/v1/listings');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lat', 'lng']);
    }

    public function test_show_listing(): void
    {
        $listing = Listing::factory()->aktif()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->buyer)
            ->getJson("/api/v1/listings/{$listing->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['listing' => ['id', 'title']]]);
    }

    public function test_show_listing_returns_404_for_hidden(): void
    {
        $store = Store::factory()->menunggu()->create(['user_id' => $this->owner->id]);
        $listing = Listing::factory()->create(['store_id' => $store->id]);

        $response = $this->actingAs($this->buyer)
            ->getJson("/api/v1/listings/{$listing->id}");

        $response->assertStatus(404);
    }

    public function test_update_listing(): void
    {
        $listing = Listing::factory()->aktif()->create([
            'store_id' => $this->store->id,
            'title'    => 'Judul Lama',
        ]);

        $response = $this->actingAs($this->owner)
            ->putJson("/api/v1/listings/{$listing->id}", [
                'title' => 'Judul Baru',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.listing.title', 'Judul Baru');
    }

    public function test_update_listing_forbidden_for_non_owner(): void
    {
        $listing = Listing::factory()->aktif()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->buyer)
            ->putJson("/api/v1/listings/{$listing->id}", [
                'title' => 'Dicuri',
            ]);

        $response->assertStatus(403);
    }

    public function test_delete_listing(): void
    {
        $listing = Listing::factory()->aktif()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/v1/listings/{$listing->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('listings', ['id' => $listing->id]);
    }

    public function test_delete_listing_forbidden_for_non_owner(): void
    {
        $listing = Listing::factory()->aktif()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->buyer)
            ->deleteJson("/api/v1/listings/{$listing->id}");

        $response->assertStatus(403);
    }

    public function test_favorite_listing(): void
    {
        $listing = Listing::factory()->aktif()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/v1/listings/{$listing->id}/favorite");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('favorites', [
            'user_id'    => $this->buyer->id,
            'listing_id' => $listing->id,
        ]);
    }

    public function test_unfavorite_listing(): void
    {
        $listing = Listing::factory()->aktif()->create(['store_id' => $this->store->id]);
        Favorite::factory()->create([
            'user_id'    => $this->buyer->id,
            'listing_id' => $listing->id,
        ]);

        $response = $this->actingAs($this->buyer)
            ->deleteJson("/api/v1/listings/{$listing->id}/favorite");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('favorites', [
            'user_id'    => $this->buyer->id,
            'listing_id' => $listing->id,
        ]);
    }
}
