<?php

namespace Database\Factories;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Listing;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Listing>
 *
 * DUA CHECK CONSTRAINT MENGATUR TABEL INI (DATABASE.md §4.4):
 *
 *   listings_price_required_chk : price WAJIB kecuali listing_type='service'
 *   listings_qty_slot_chk       : product|rental -> stock_qty ADA & slot NULL
 *                                 service       -> slot ADA & stock_qty NULL
 *
 * Karena itu tipe listing TIDAK boleh diacak terpisah dari stok/slot-nya.
 * Semua state di bawah selalu menyetel ketiganya bersamaan.
 */
class ListingFactory extends Factory
{
    protected $model = Listing::class;

    /** Katalog nyata per tipe: [judul, harga min, harga maks]. */
    private const KATALOG = [
        'product' => [
            ['Beras Premium 5 kg', 62000, 78000],
            ['Minyak Goreng 2 L', 32000, 40000],
            ['Gula Pasir 1 kg', 15000, 19000],
            ['Telur Ayam 1 kg', 26000, 32000],
            ['Gas LPG 3 kg (isi ulang)', 20000, 25000],
            ['Semen 50 kg', 52000, 62000],
            ['Pasir Cor 1 Pickup', 350000, 480000],
            ['Batako Press (100 pcs)', 280000, 350000],
            ['Pupuk Urea 50 kg', 240000, 300000],
            ['Pakan Ayam Broiler 50 kg', 380000, 450000],
            ['Bibit Cabai Rawit (100 batang)', 45000, 70000],
            ['Kopi Bubuk Robusta 250 g', 22000, 35000],
            ['Kerupuk Puli 1 kg', 18000, 26000],
            ['Sepatu Bekas Original', 120000, 350000],
            ['Sepeda Lipat Bekas', 700000, 1500000],
            ['Meja Belajar Kayu Jati', 450000, 900000],
        ],
        'service' => [
            ['Servis AC 1 PK (cuci + freon)', 120000, 200000],
            ['Servis Kulkas Panggilan', 100000, 250000],
            ['Servis Mesin Cuci', 90000, 220000],
            ['Tukang Bangunan Harian', 130000, 180000],
            ['Jasa Cat Rumah per m²', 25000, 45000],
            ['Bersih Rumah Menyeluruh', 150000, 350000],
            ['Sedot WC', 350000, 700000],
            ['Servis Motor Rutin', 60000, 150000],
            ['Tambal Ban Panggilan', 25000, 60000],
            ['Potong Rambut ke Rumah', 35000, 75000],
            ['Jasa Las Pagar per meter', 200000, 400000],
            ['Instalasi Listrik Rumah', 250000, 800000],
        ],
        'rental' => [
            ['Sewa Tenda Pesta 4x6', 350000, 600000],
            ['Sewa Kursi Plastik (per 100)', 200000, 350000],
            ['Sewa Sound System Hajatan', 500000, 1500000],
            ['Sewa Terop + Panggung', 800000, 2000000],
            ['Sewa Mobil Pickup Harian', 250000, 400000],
            ['Sewa Molen Semen', 150000, 250000],
            ['Sewa Scaffolding per set', 35000, 60000],
            ['Sewa Genset 5000 Watt', 300000, 550000],
        ],
    ];

    public function definition(): array
    {
        // Tipe ditentukan lebih dulu; stok/slot menyesuaikan agar CHECK lolos.
        $tipe = fake()->randomElement(ListingType::cases());

        return array_merge(
            [
                'id'       => (string) Str::uuid7(),
                'store_id' => Store::factory()->terverifikasi(),
                'images'   => $this->gambar(),
                'status'   => fake()->randomElement([
                    ListingStatus::Active, ListingStatus::Active,
                    ListingStatus::Active, ListingStatus::Sold, ListingStatus::Hidden,
                ]),
            ],
            $this->atributTipe($tipe),
        );
    }

    /**
     * Judul, harga, stok, dan slot yang konsisten dengan tipenya.
     *
     * Satu tempat untuk ketiganya — kalau dipisah, mudah lahir kombinasi
     * yang ditolak CHECK (mis. service dengan stock_qty).
     */
    private function atributTipe(ListingType $tipe): array
    {
        [$judul, $min, $maks] = fake()->randomElement(self::KATALOG[$tipe->value]);

        // Harga dibulatkan ke ribuan — harga seperti "Rp 63.417" tidak realistis.
        $harga = round(fake()->numberBetween($min, $maks) / 500) * 500;

        return [
            'title'        => $judul,
            'description'  => $this->deskripsi($tipe, $judul),
            'listing_type' => $tipe,
            'price'        => $harga,

            // CHECK listings_qty_slot_chk — persis satu dari dua kolom terisi.
            'stock_qty' => $tipe === ListingType::Service ? null : fake()->numberBetween(1, 120),
            'slot'      => $tipe === ListingType::Service ? fake()->numberBetween(1, 8) : null,
        ];
    }

    private function deskripsi(ListingType $tipe, string $judul): string
    {
        $umum = 'Wilayah layanan Kabupaten Pasuruan dan sekitarnya. Hubungi lewat aplikasi untuk ketersediaan.';

        return match ($tipe) {
            ListingType::Product => "{$judul}. Stok harian, bisa COD atau ambil di tempat. {$umum}",
            ListingType::Service => "{$judul}. Dikerjakan teknisi berpengalaman, garansi pengerjaan 7 hari. {$umum}",
            ListingType::Rental  => "{$judul}. Sewa harian, antar-jemput tersedia dengan biaya tambahan. {$umum}",
        };
    }

    /** @return array<int, string> */
    private function gambar(): array
    {
        return collect(range(1, fake()->numberBetween(1, 3)))
            ->map(fn (int $i) => 'listings/'.Str::uuid7().'.jpg')
            ->all();
    }

    public function produk(): static
    {
        return $this->state(fn () => $this->atributTipe(ListingType::Product));
    }

    public function jasa(): static
    {
        return $this->state(fn () => $this->atributTipe(ListingType::Service));
    }

    public function sewa(): static
    {
        return $this->state(fn () => $this->atributTipe(ListingType::Rental));
    }

    public function aktif(): static
    {
        return $this->state(['status' => ListingStatus::Active]);
    }

    public function terjual(): static
    {
        return $this->state(['status' => ListingStatus::Sold]);
    }

    public function disembunyikan(): static
    {
        return $this->state(['status' => ListingStatus::Hidden]);
    }

    /** Stok habis — dipakai menguji tampilan "tidak tersedia". */
    public function stokHabis(): static
    {
        return $this->state(fn (array $atribut) => $atribut['listing_type'] === ListingType::Service
            ? ['slot' => 0]
            : ['stock_qty' => 0]);
    }
}
