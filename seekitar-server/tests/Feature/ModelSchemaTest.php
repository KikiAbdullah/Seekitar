<?php

namespace Tests\Feature;

use App\Enums\DeliveryMethod;
use App\Enums\ListingType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentMethod as Pay;
use App\Enums\RequestStatus;
use App\Enums\ReviewDirection;
use App\Enums\StoreType;
use App\Enums\VerificationLevel;
use App\Enums\VerificationStatus;
use App\Models\Category;
use App\Models\CustomerRequest;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Review;
use App\Models\Store;
use App\Models\User;
use App\Support\SpatialSchema;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Schema;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class ModelSchemaTest extends TestCase
{
    use RefreshesDatabase;

    private function seller(): User
    {
        return User::create([
            'phone' => '628222222222', 'name' => 'Yanto Wijaya',
            'verification_level' => VerificationLevel::Verified,
        ]);
    }

    private function buyer(): User
    {
        return User::create([
            'phone' => '628111111111', 'name' => 'Budi Santoso',
            'verification_level' => VerificationLevel::Basic,
        ]);
    }

    private function store(User $owner, Category $cat): Store
    {
        return Store::create([
            'user_id' => $owner->id, 'name' => 'Bengkel AC Yanto', 'regency' => 'Sidoarjo',
            'store_type' => [StoreType::Services], 'category_ids' => [$cat->id],
            'verification_status' => VerificationStatus::Verified,
            'latitude' => -7.2575, 'longitude' => 112.7521,
        ]);
    }

    public function test_store_type_bulak_balik_antara_array_dan_set(): void
    {
        $cat = Category::create(['name' => 'AC', 'slug' => 'ac']);
        $store = $this->store($this->seller(), $cat);

        // API memakai array...
        $this->assertSame([StoreType::Services], $store->store_type);

        // ...tetapi kolomnya disimpan sebagai SET (string dipisah koma).
        $this->assertSame('services',
            \DB::table('stores')->where('id', $store->id)->value('store_type'));
    }

    public function test_toko_menolak_listing_di_luar_tipenya(): void
    {
        $cat = Category::create(['name' => 'AC', 'slug' => 'ac']);
        $store = $this->store($this->seller(), $cat);

        $this->assertTrue($store->handles(ListingType::Service));
        $this->assertFalse($store->handles(ListingType::Product));
    }

    public function test_nama_pembeli_disamarkan_untuk_penyedia(): void
    {
        // PRD §5.2.3: penyedia hanya melihat nama depan sebelum offer diterima.
        $this->assertSame('Budi S.', $this->buyer()->displayName());
    }

    public function test_data_pribadi_tidak_bocor_ke_json(): void
    {
        $user = $this->seller();
        $user->update(['nik' => '3578012345670001', 'ktp_image' => 'ktp/x.jpg']);

        $json = $user->fresh()->toArray();
        $this->assertArrayNotHasKey('nik', $json);
        $this->assertArrayNotHasKey('ktp_image', $json);

        // Tersimpan terenkripsi, bukan plaintext.
        $this->assertNotSame('3578012345670001',
            \DB::table('users')->where('id', $user->id)->value('nik'));
        $this->assertSame('3578012345670001', $user->fresh()->nik);
    }

    public function test_total_penawaran_adalah_harga_plus_ongkos(): void
    {
        $cat = Category::create(['name' => 'AC', 'slug' => 'ac']);
        $store = $this->store($this->seller(), $cat);
        $req = CustomerRequest::create([
            'user_id' => $this->buyer()->id, 'title' => 'Servis AC',
            'description' => 'AC tidak dingin', 'category_id' => $cat->id,
            'budget_min' => 100000, 'budget_max' => 200000,
            'expires_at' => now()->addDay(), 'latitude' => -7.26, 'longitude' => 112.755,
        ]);

        $offer = Offer::create([
            'request_id' => $req->id, 'store_id' => $store->id,
            'price' => 150000, 'additional_cost' => 15000,
            'estimation_time' => '2 jam', 'expires_at' => now()->addHours(48),
        ]);

        // Pengurutan "termurah" memakai total agar penyedia yang jujur
        // mencantumkan ongkos tidak dirugikan.
        $this->assertSame(165000.0, $offer->total_amount);
        $this->assertTrue($req->acceptsPrice($offer->total_amount));
        $this->assertFalse($req->acceptsPrice(250000));
    }

    public function test_default_kolom_terisi_pada_instance_baru(): void
    {
        $cat = Category::create(['name' => 'AC', 'slug' => 'ac']);
        $store = $this->store($this->seller(), $cat);

        $this->assertSame('15.00', (string) CustomerRequest::make()->radius_km);
        $this->assertSame(RequestStatus::Open, CustomerRequest::make()->status);
        $this->assertSame(OrderStatus::MenungguKonfirmasi, Order::make()->status);
        $this->assertTrue($store->is_active);
    }

    public function test_ulasan_dua_arah_pada_satu_pesanan(): void
    {
        $cat = Category::create(['name' => 'AC', 'slug' => 'ac']);
        $buyer = $this->buyer();
        $seller = $this->seller();
        $store = $this->store($seller, $cat);

        $listing = Listing::create([
            'store_id' => $store->id, 'title' => 'Servis AC 1 PK',
            'description' => 'Cuci + freon', 'listing_type' => ListingType::Service,
            'price' => 150000, 'slot' => 3, 'images' => ['https://cdn/a.jpg'],
        ]);

        $order = Order::create([
            'order_number' => 'SKT-20260727-0001',
            'buyer_id' => $buyer->id, 'store_id' => $store->id, 'listing_id' => $listing->id,
            'order_type' => OrderType::fromListingType($listing->listing_type),
            'total_amount' => 150000, 'payment_method' => Pay::Cod,
            'delivery_method' => DeliveryMethod::Pickup,
            'status' => OrderStatus::Selesai, 'completed_at' => now(),
        ]);

        Review::create(['order_id' => $order->id, 'reviewer_id' => $buyer->id,
            'reviewee_id' => $seller->id, 'store_id' => $store->id,
            'direction' => ReviewDirection::BuyerToStore, 'rating' => 5]);

        Review::create(['order_id' => $order->id, 'reviewer_id' => $seller->id,
            'reviewee_id' => $buyer->id, 'store_id' => null,
            'direction' => ReviewDirection::StoreToBuyer, 'rating' => 4]);

        $this->assertSame(2, Review::where('order_id', $order->id)->count());

        // Bug #30: hanya arah buyer_to_store yang menghitung rating toko.
        $this->assertSame(1, $store->reviews()->count());
        $this->assertSame(5.0, (float) $store->reviews()->avg('rating'));
    }

    public function test_ulasan_ganda_pada_arah_yang_sama_ditolak(): void
    {
        $cat = Category::create(['name' => 'AC', 'slug' => 'ac']);
        $buyer = $this->buyer();
        $seller = $this->seller();
        $store = $this->store($seller, $cat);

        $order = Order::create([
            'order_number' => 'SKT-20260727-0002',
            'buyer_id' => $buyer->id, 'store_id' => $store->id,
            'order_type' => OrderType::Service, 'total_amount' => 100000,
            'payment_method' => Pay::Cod, 'status' => OrderStatus::Selesai,
            'completed_at' => now(),
        ]);

        $data = ['order_id' => $order->id, 'reviewer_id' => $buyer->id,
                 'reviewee_id' => $seller->id, 'store_id' => $store->id,
                 'direction' => ReviewDirection::BuyerToStore, 'rating' => 5];

        Review::create($data);

        $this->expectException(UniqueConstraintViolationException::class);
        Review::create($data);
    }

    public function test_jendela_ulasan_tujuh_hari(): void
    {
        $cat = Category::create(['name' => 'AC', 'slug' => 'ac']);
        $store = $this->store($this->seller(), $cat);

        $order = Order::make([
            'order_number' => 'SKT-X', 'buyer_id' => $this->buyer()->id,
            'store_id' => $store->id, 'order_type' => OrderType::Service,
            'total_amount' => 1, 'payment_method' => Pay::Cod,
        ]);

        $order->status = OrderStatus::Selesai;
        $order->completed_at = now()->subDays(3);
        $this->assertTrue($order->acceptsReview());

        $order->completed_at = now()->subDays(9);
        $this->assertFalse($order->acceptsReview(), 'lewat 7 hari harus ditolak');
    }

    /**
     * `shipping_location` terdokumentasi di DATABASE.md §4.7 tetapi sempat
     * tidak pernah dimigrasikan sama sekali. Tanpa test ini, kolomnya bisa
     * hilang lagi tanpa ada yang sadar sampai fitur navigasi penjual dibuat.
     */
    public function test_orders_punya_kolom_koordinat_tujuan_antar(): void
    {
        // Nama kolom diambil dari sumber kebenarannya, bukan ditulis ulang:
        // di MySQL satu kolom POINT `shipping_location`, di SQLite sepasang
        // kolom desimal. Menuliskannya manual di test pernah membuat test ini
        // gagal padahal migrasinya benar.
        $expected = SpatialSchema::isMySql()
            ? ['shipping_location']
            : SpatialSchema::sqliteColumns('shipping_location');

        $this->assertTrue(
            Schema::hasColumns('orders', $expected),
            'orders wajib menyimpan koordinat tujuan: ' . implode(', ', $expected)
        );

        $cat = Category::create(['name' => 'Beras', 'slug' => 'beras']);
        $store = $this->store($this->seller(), $cat);

        $order = Order::create([
            'order_number' => 'SKT-20260727-0003',
            'buyer_id' => $this->buyer()->id, 'store_id' => $store->id,
            'order_type' => OrderType::Product, 'quantity' => 3,
            'total_amount' => 45000, 'payment_method' => Pay::Transfer,
            'delivery_method' => DeliveryMethod::Delivery,
            'shipping_address' => 'Jl. Melati 12, Bangil',
            'notes' => 'Titip di pos satpam',
        ]);

        // quantity & notes juga sempat ada di migrasi tanpa terdokumentasi.
        $this->assertSame(3, $order->fresh()->quantity);
        $this->assertSame('Titip di pos satpam', $order->fresh()->notes);
    }

    /**
     * API_DOCUMENTATION.md §8: ulasan tidak bisa diubah setelah dikirim.
     * Karena itu tabelnya tanpa `updated_at`; kalau model lupa menyetel
     * UPDATED_AT = null, Eloquent menulis kolom yang tidak ada dan gagal.
     */
    public function test_ulasan_tidak_punya_kolom_updated_at(): void
    {
        $this->assertFalse(
            Schema::hasColumn('reviews', 'updated_at'),
            'ulasan bersifat permanen, updated_at menyiratkan bisa disunting'
        );

        $cat = Category::create(['name' => 'AC', 'slug' => 'ac']);
        $buyer = $this->buyer();
        $seller = $this->seller();
        $store = $this->store($seller, $cat);

        $order = Order::create([
            'order_number' => 'SKT-20260727-0004',
            'buyer_id' => $buyer->id, 'store_id' => $store->id,
            'order_type' => OrderType::Service, 'total_amount' => 100000,
            'payment_method' => Pay::Cod, 'status' => OrderStatus::Selesai,
            'completed_at' => now(),
        ]);

        // Pembuatan harus lolos tanpa error "no such column: updated_at".
        $review = Review::create([
            'order_id' => $order->id, 'reviewer_id' => $buyer->id,
            'reviewee_id' => $seller->id, 'store_id' => $store->id,
            'direction' => ReviewDirection::BuyerToStore, 'rating' => 5,
        ]);

        $this->assertNotNull($review->created_at);
    }
}
