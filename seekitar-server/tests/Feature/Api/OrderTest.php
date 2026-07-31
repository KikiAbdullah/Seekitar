<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Dispute;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Review;
use App\Models\Store;
use App\Models\User;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshesDatabase;

    private User $buyer;
    private User $seller;
    private Store $store;
    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->verified()->create(['name' => 'Pembeli', 'address' => 'Alamat']);
        $this->buyer->setLocation(-7.6, 112.7)->save();

        $this->seller = User::factory()->verified()->create(['name' => 'Penjual', 'address' => 'Alamat 2']);
        $this->seller->setLocation(-7.6, 112.7)->save();

        $this->store = Store::factory()->terverifikasi()->create(['user_id' => $this->seller->id]);

        $this->listing = Listing::factory()->aktif()->produk()->create([
            'store_id'  => $this->store->id,
            'price'     => 75000,
            'stock_qty' => 10,
        ]);

        Category::factory()->create(['id' => 1]);
    }

    public function test_create_order(): void
    {
        $response = $this->actingAs($this->buyer)
            ->postJson('/api/v1/orders', [
                'listing_id'      => $this->listing->id,
                'quantity'        => 2,
                'payment_method'  => 'cod',
                'delivery_method' => 'pickup',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['order' => ['id', 'status', 'total_amount']]]);

        $this->assertDatabaseHas('orders', [
            'buyer_id'     => $this->buyer->id,
            'listing_id'   => $this->listing->id,
            'total_amount' => 150000,
        ]);
    }

    public function test_create_order_fails_when_insufficient_stock(): void
    {
        $response = $this->actingAs($this->buyer)
            ->postJson('/api/v1/orders', [
                'listing_id'      => $this->listing->id,
                'quantity'        => 100,
                'payment_method'  => 'cod',
                'delivery_method' => 'pickup',
            ]);

        $response->assertStatus(422);
    }

    public function test_create_order_validates_required_fields(): void
    {
        $response = $this->actingAs($this->buyer)
            ->postJson('/api/v1/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['listing_id', 'payment_method', 'delivery_method']);
    }

    public function test_list_orders_as_buyer(): void
    {
        Order::factory()->count(3)->create(['buyer_id' => $this->buyer->id, 'store_id' => $this->store->id]);

        $response = $this->actingAs($this->buyer)
            ->getJson('/api/v1/orders');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_list_orders_as_seller(): void
    {
        Order::factory()->count(3)->create(['buyer_id' => User::factory(), 'store_id' => $this->store->id]);

        $response = $this->actingAs($this->seller)
            ->getJson('/api/v1/orders?role=seller');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_show_order(): void
    {
        $order = Order::factory()->create(['buyer_id' => $this->buyer->id, 'store_id' => $this->store->id]);

        $response = $this->actingAs($this->buyer)
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['order' => ['id', 'status']]]);
    }

    public function test_show_order_forbidden_for_non_party(): void
    {
        $stranger = User::factory()->create();
        $order = Order::factory()->create(['buyer_id' => $this->buyer->id, 'store_id' => $this->store->id]);

        $response = $this->actingAs($stranger)
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(403);
    }

    public function test_update_order_status(): void
    {
        $order = Order::factory()->menungguKonfirmasi()->create([
            'buyer_id' => $this->buyer->id,
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->seller)
            ->patchJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'diproses',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.order.status', 'diproses');
    }

    public function test_update_order_status_fails_for_invalid_transition(): void
    {
        $order = Order::factory()->menungguKonfirmasi()->create([
            'buyer_id' => $this->buyer->id,
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->seller)
            ->patchJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'selesai',
            ]);

        $response->assertStatus(422);
    }

    public function test_review_order(): void
    {
        $order = Order::factory()->selesaiBaru()->create([
            'buyer_id' => $this->buyer->id,
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/v1/orders/{$order->id}/review", [
                'rating'  => 5,
                'comment' => 'Bagus sekali!',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('reviews', [
            'order_id'    => $order->id,
            'reviewer_id' => $this->buyer->id,
            'rating'      => 5,
        ]);
    }

    public function test_review_order_fails_when_not_eligible(): void
    {
        $order = Order::factory()->menungguKonfirmasi()->create([
            'buyer_id' => $this->buyer->id,
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/v1/orders/{$order->id}/review", [
                'rating'  => 5,
                'comment' => 'Bagus!',
            ]);

        $response->assertStatus(403);
    }

    public function test_review_order_fails_for_duplicate_review(): void
    {
        $order = Order::factory()->selesaiBaru()->create([
            'buyer_id' => $this->buyer->id,
            'store_id' => $this->store->id,
        ]);

        Review::factory()->keToko($this->store, $this->buyer, $this->seller)
            ->create(['order_id' => $order->id]);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/v1/orders/{$order->id}/review", [
                'rating'  => 4,
                'comment' => 'Cukup bagus',
            ]);

        $response->assertStatus(422);
    }

    public function test_dispute_order(): void
    {
        $order = Order::factory()->menungguKonfirmasi()->create([
            'buyer_id' => $this->buyer->id,
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/v1/orders/{$order->id}/disputes", [
                'reason'      => 'barang_tidak_sesuai',
                'description' => 'Barang yang datang beda merek.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['dispute' => ['id', 'reason', 'status']]]);

        $this->assertDatabaseHas('disputes', [
            'order_id'    => $order->id,
            'reported_by' => $this->buyer->id,
            'reason'      => 'barang_tidak_sesuai',
        ]);
    }

    public function test_dispute_order_fails_for_duplicate(): void
    {
        $order = Order::factory()->menungguKonfirmasi()->create([
            'buyer_id' => $this->buyer->id,
            'store_id' => $this->store->id,
        ]);

        Dispute::factory()->create(['order_id' => $order->id]);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/v1/orders/{$order->id}/disputes", [
                'reason'      => 'barang_tidak_sesuai',
                'description' => 'Barang beda.',
            ]);

        $response->assertStatus(422);
    }

    public function test_create_order_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/orders', []);

        $response->assertStatus(401);
    }
}
