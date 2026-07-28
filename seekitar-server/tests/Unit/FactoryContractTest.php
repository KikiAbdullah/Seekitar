<?php

namespace Tests\Unit;

use App\Enums\ListingType;
use App\Enums\ReviewDirection;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Review;
use App\Models\Store;
use App\Models\User;
use Tests\TestCase;

/**
 * Kontrak factory yang bisa diperiksa TANPA basis data.
 *
 * Semua aturan di bawah ditegakkan CHECK constraint MySQL. Melanggarnya tidak
 * memunculkan error di PHP — barisnya baru ditolak engine saat `create()`,
 * seringkali di tengah seeding ratusan baris sehingga sebabnya sulit dilacak.
 *
 * Uji end-to-end yang benar-benar MENJALANKAN seeder ada di
 * tools/dev/run-seeders.php (dipanggil check-seeders.mjs).
 */
class FactoryContractTest extends TestCase
{
    /*
     * Memakai Tests\TestCase (bukan PHPUnit\Framework\TestCase) karena factory
     * membutuhkan container Laravel untuk fake() dan config().
     *
     * Bootstrap manual di setUp() sempat dicoba dan ditolak PHPUnit sebagai
     * "risky test": aplikasi yang dibangun sendiri memasang error & exception
     * handler yang tidak pernah dilepas. TestCase bawaan Laravel menangani
     * pembongkarannya.
     *
     * Test ini TIDAK menyentuh basis data — semuanya memakai make(), bukan
     * create() — sehingga tetap jalan tanpa MySQL.
     */

    /**
     * CHECK listings_qty_slot_chk:
     *   product|rental -> stock_qty ADA, slot NULL
     *   service        -> slot ADA, stock_qty NULL
     */
    public function test_listing_selalu_memenuhi_check_qty_slot(): void
    {
        for ($i = 0; $i < 120; $i++) {
            $l = Listing::factory()->make(['store_id' => $this->uuid()]);

            if ($l->listing_type === ListingType::Service) {
                $this->assertNotNull($l->slot, 'Jasa wajib punya slot.');
                $this->assertNull($l->stock_qty, 'Jasa tidak boleh punya stock_qty.');
            } else {
                $this->assertNotNull($l->stock_qty, 'Barang/sewa wajib punya stock_qty.');
                $this->assertNull($l->slot, 'Barang/sewa tidak boleh punya slot.');
            }
        }
    }

    /** CHECK listings_price_required_chk: harga wajib kecuali jasa. */
    public function test_listing_non_jasa_selalu_berharga(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $l = Listing::factory()->produk()->make(['store_id' => $this->uuid()]);
            $this->assertNotNull($l->price);
        }
    }

    /** CHECK stores_fulfilment_chk: minimal satu cara pemenuhan. */
    public function test_toko_selalu_bisa_dijangkau(): void
    {
        for ($i = 0; $i < 120; $i++) {
            $s = Store::factory()->make(['user_id' => $this->uuid()]);

            $this->assertTrue(
                (bool) $s->offers_delivery || (bool) $s->allows_pickup,
                'Toko wajib melayani antar ATAU ambil di tempat.',
            );
        }
    }

    /** Kolom POINT NOT NULL — insert gagal bila tidak terisi saat making. */
    public function test_toko_selalu_punya_lokasi_dengan_axis_order(): void
    {
        $s = Store::factory()->make(['user_id' => $this->uuid()]);
        $lokasi = $s->getAttributes()['location'] ?? null;

        $this->assertNotNull($lokasi, 'stores.location NOT NULL tetapi tidak diisi factory.');

        $sql = (string) $lokasi->getValue(app('db')->connection()->getQueryGrammar());

        // Tanpa opsi ini, bujur Indonesia (95°–141° BT) ditolak MySQL dengan
        // ERROR 3617 karena SRID 4326 menempatkan LATITUDE di sumbu pertama.
        $this->assertStringContainsString('axis-order=long-lat', $sql);
    }

    /** CHECK orders_shipping_chk: diantar wajib punya alamat. */
    public function test_pesanan_diantar_selalu_punya_alamat(): void
    {
        for ($i = 0; $i < 40; $i++) {
            $o = Order::factory()->diantar()->make([
                'buyer_id' => $this->uuid(), 'store_id' => $this->uuid(),
            ]);

            $this->assertNotEmpty($o->shipping_address);
        }
    }

    public function test_pesanan_diambil_tidak_punya_alamat(): void
    {
        $o = Order::factory()->diambil()->make([
            'buyer_id' => $this->uuid(), 'store_id' => $this->uuid(),
        ]);

        $this->assertNull($o->shipping_address);
    }

    /**
     * `completed_at` wajib terisi pada pesanan selesai.
     *
     * `Order::acceptsReview()` memakainya untuk jendela ulasan 7 hari; tanpa
     * itu tidak ada ulasan yang sah dan data contoh tidak bisa dipakai
     * menguji alur ulasan sama sekali.
     */
    public function test_pesanan_selesai_punya_completed_at(): void
    {
        $o = Order::factory()->selesaiBaru()->make([
            'buyer_id' => $this->uuid(), 'store_id' => $this->uuid(),
        ]);

        $this->assertNotNull($o->completed_at);
        $this->assertTrue($o->acceptsReview(), 'Pesanan baru selesai harus masih bisa diulas.');
    }

    /**
     * CHECK reviews_store_direction_chk.
     *
     * store_id HANYA untuk arah buyer_to_store — inilah yang mencegah
     * penilaian penjual terhadap pembeli ikut menaikkan rating toko.
     */
    public function test_ulasan_store_id_sesuai_arah(): void
    {
        $r = Review::factory()->make([
            'order_id' => $this->uuid(), 'reviewer_id' => $this->uuid(),
            'reviewee_id' => $this->uuid(), 'store_id' => $this->uuid(),
        ]);

        $this->assertSame(ReviewDirection::BuyerToStore, $r->direction);
        $this->assertNotNull($r->store_id);
    }

    /** UserFactory bawaan Laravel tidak mengisi `phone` yang NOT NULL. */
    public function test_pengguna_selalu_punya_nomor_telepon_unik(): void
    {
        $nomor = [];

        for ($i = 0; $i < 50; $i++) {
            $u = User::factory()->make();

            $this->assertNotEmpty($u->phone, 'users.phone NOT NULL tetapi kosong.');
            $this->assertLessThanOrEqual(15, strlen($u->phone), 'phone melebihi varchar(15).');
            $this->assertStringStartsWith('62', $u->phone, 'Format E.164 tanpa plus.');

            $nomor[] = $u->phone;
        }

        $this->assertSame(count($nomor), count(array_unique($nomor)), 'Nomor telepon bertabrakan.');
    }

    /** Penjual wajib Level 2+ agar StorePolicy::create() mengizinkan toko. */
    public function test_pengguna_terverifikasi_boleh_membuka_toko(): void
    {
        $this->assertTrue(User::factory()->verified()->make()->canOpenStore());
        $this->assertTrue(User::factory()->pro()->make()->canOpenStore());
        $this->assertFalse(User::factory()->basic()->make()->canOpenStore());
    }

    /**
     * Antrian verifikasi = berkas dikirim TAPI level masih Basic.
     *
     * Definisi ini dipakai VerificationController, dasbor, dan lencana
     * sidebar — kalau factory-nya menyimpang, ketiganya menampilkan nol.
     */
    public function test_state_menunggu_ktp_sesuai_definisi_antrian(): void
    {
        $u = User::factory()->menungguKtp()->make();

        $this->assertNotNull($u->ktp_submitted_at);
        $this->assertSame(\App\Enums\VerificationLevel::Basic, $u->verification_level);
    }

    private function uuid(): string
    {
        return (string) \Illuminate\Support\Str::uuid7();
    }
}
