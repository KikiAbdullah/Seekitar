<?php

namespace Database\Seeders;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\RequestStatus;
use App\Enums\StoreType;
use App\Enums\VerificationLevel;
use App\Enums\VerificationStatus;
use App\Models\Category;
use App\Models\CustomerRequest;
use App\Models\Listing;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Data contoh untuk pengembangan & pengujian manual.
 *
 * HANYA dipanggil `DatabaseSeeder` saat environment `local`/`testing`.
 * Penjagaan itu diulang di sini supaya seeder tetap aman meski dijalankan
 * langsung lewat `db:seed --class=DummyDataSeeder`.
 *
 * Semua koordinat berada di sekitar Bangil, Kabupaten Pasuruan — cukup
 * berdekatan agar pencarian radius benar-benar menghasilkan sesuatu, dan
 * cukup berjauhan agar pengurutan "terdekat" bisa dibedakan.
 */
class DummyDataSeeder extends Seeder
{
    /** Titik acuan: alun-alun Bangil. */
    private const CENTER_LAT = -7.5966;
    private const CENTER_LNG = 112.8203;

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException(
                'DummyDataSeeder hanya boleh jalan di local/testing. '
                .'Environment saat ini: '.app()->environment()
            );
        }

        $sembako = Category::where('slug', 'sembako')->firstOrFail();
        $servisAc = Category::where('slug', 'servis-elektronik')->firstOrFail();

        $penjual = $this->user('628111000001', 'Yanto Wijaya', VerificationLevel::Pro);
        $pembeli = $this->user('628111000002', 'Budi Santoso', VerificationLevel::Basic);

        // Pembeli juga punya lokasi — dibutuhkan middleware EnsureProfileComplete.
        $pembeli->setLocation(self::CENTER_LAT, self::CENTER_LNG)->save();

        $toko = $this->store($penjual, 'Toko Sembako Barokah', [$sembako->id],
            [StoreType::Goods], self::CENTER_LAT + 0.004, self::CENTER_LNG + 0.003);

        $bengkel = $this->store($penjual, 'Servis Elektronik Jaya', [$servisAc->id],
            [StoreType::Services], self::CENTER_LAT - 0.011, self::CENTER_LNG + 0.009);

        // Produk: WAJIB punya harga & stok, slot harus NULL
        // (CHECK listings_price_required_chk & listings_qty_slot_chk).
        Listing::updateOrCreate(
            ['store_id' => $toko->id, 'title' => 'Beras Premium 5 kg'],
            [
                'description'  => 'Beras pulen kemasan 5 kg, stok harian.',
                'listing_type' => ListingType::Product,
                'price'        => 68000,
                'stock_qty'    => 40,
                'slot'         => null,
                'images'       => ['https://cdn.seekitar.id/contoh/beras.jpg'],
                'status'       => ListingStatus::Active,
            ],
        );

        // Jasa: slot WAJIB terisi, stock_qty harus NULL.
        Listing::updateOrCreate(
            ['store_id' => $bengkel->id, 'title' => 'Servis AC 1 PK'],
            [
                'description'  => 'Cuci AC + isi freon, datang ke rumah.',
                'listing_type' => ListingType::Service,
                'price'        => 150000,
                'stock_qty'    => null,
                'slot'         => 4,
                'images'       => ['https://cdn.seekitar.id/contoh/ac.jpg'],
                'status'       => ListingStatus::Active,
            ],
        );

        $this->request($pembeli, $servisAc, 'Cari tukang servis AC', 100000, 200000);
    }

    private function user(string $phone, string $name, VerificationLevel $level): User
    {
        return User::firstOrCreate(
            ['phone' => $phone],
            // Kedudukan + stempel yang selaras: Basic dibiarkan default
            // (menunggu, tanpa berkas); Verified/Pro = identitas disetujui.
            // Lencana Pro sendiri baru tampil setelah salah satu tokonya
            // disetujui — diisi store(), bukan di sini.
            ['name' => $name] + ($level === VerificationLevel::Basic ? [] : [
                'status'      => \App\Enums\UserStatus::Terverifikasi,
                'verified_at' => now(),
            ]),
        );
    }

    /**
     * @param  array<int,int>        $categoryIds
     * @param  array<int,StoreType>  $types
     */
    private function store(User $owner, string $name, array $categoryIds, array $types, float $lat, float $lng): Store
    {
        $store = Store::firstOrNew(['name' => $name, 'regency' => config('seekitar.regency')]);

        /*
         * verified_by ikut diisi: kontrak pasangan verified_at/verified_by
         * berlaku juga untuk data contoh — kalau tidak, halaman detail toko
         * menampilkan tanggal setuju tanpa penyetuju (DATABASE.md §4.2).
         */
        $admin = User::role('super-admin')->first() ?? $owner;

        $store->fill([
            'user_id'             => $owner->id,
            'regency_code'        => config('seekitar.regency_code'),
            'store_type'          => $types,
            'category_ids'        => $categoryIds,
            'address'             => 'Jl. Raya Bangil, Kabupaten Pasuruan',
            'service_radius_km'   => 5,
            'accepts_cod'         => true,
            'allows_pickup'       => true,
            'verification_status' => VerificationStatus::Verified,
            'verified_at'         => now(),
            'verified_by'         => $admin->id,
        ]);

        // Kolom POINT tidak bisa mass-assign; setLocation() memastikan opsi
        // axis-order ikut terpasang.
        $store->setLocation($lat, $lng)->save();

        return $store;
    }

    private function request(User $buyer, Category $category, string $title, int $min, int $max): CustomerRequest
    {
        $request = CustomerRequest::firstOrNew(['user_id' => $buyer->id, 'title' => $title]);

        $request->fill([
            'description' => 'Butuh secepatnya, wilayah Bangil dan sekitarnya.',
            'category_id' => $category->id,
            'budget_min'  => $min,
            'budget_max'  => $max,
            'radius_km'   => 15,
            'status'      => RequestStatus::Open,
            'expires_at'  => now()->addDay(),
        ]);

        $request->setLocation(self::CENTER_LAT, self::CENTER_LNG)->save();

        return $request;
    }
}
