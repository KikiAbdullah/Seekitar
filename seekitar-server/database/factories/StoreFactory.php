<?php

namespace Database\Factories;

use App\Enums\StoreStatus;
use App\Enums\StoreType;
use App\Models\Store;
use App\Models\User;
use App\Support\PlaceholderImg;
use Database\Factories\Support\Wilayah;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Store>
 *
 * Koordinat BUKAN lagi pekerjaan khusus: latitude/longitude adalah kolom
 * DECIMAL biasa sejak skema 2.3, jadi ikut mass-assignment seperti kolom
 * lain — tidak ada lagi afterMaking ST_GeomFromText dan jebakan urutan
 * sumbu yang menyertainya.
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    /** Nama toko yang terdengar wajar di Jawa Timur, bukan lorem ipsum. */
    private const AWALAN = ['Toko', 'UD', 'Warung', 'Kios', 'CV', 'Sentra', 'Gudang', 'Depot'];

    private const INTI = [
        'Barokah', 'Rejeki', 'Makmur', 'Sejahtera', 'Jaya Abadi', 'Sumber Urip',
        'Mandiri', 'Berkah Tani', 'Amanah', 'Sinar Baru', 'Karya Muda', 'Lancar',
        'Sentosa', 'Mulia', 'Harapan', 'Bina Usaha', 'Tunas Jaya', 'Cahaya',
    ];

    public function definition(): array
    {
        [$lat, $lng] = Wilayah::acak();

        return [
            'id'      => (string) Str::uuid7(),
            'user_id' => User::factory()->verified(),

            // Nama diberi akhiran acak: UNIQUE-nya (name, regency) dan
            // puluhan toko contoh mudah bertabrakan tanpa pembeda.
            'name' => fake()->randomElement(self::AWALAN).' '
                      .fake()->randomElement(self::INTI).' '
                      .Str::upper(Str::random(3)),

            'regency'      => config('seekitar.regency'),
            'regency_code' => config('seekitar.regency_code'),

            // SET MySQL: satu toko boleh menjual barang + jasa sekaligus.
            'store_type'   => [fake()->randomElement(StoreType::cases())],
            'category_ids' => [],   // diisi seeder yang tahu kategori nyata

            'address'   => fake('id_ID')->streetAddress().', Kec. '.Wilayah::namaKecamatan(),
            'latitude'  => $lat,
            'longitude' => $lng,

            'service_radius_km' => fake()->randomElement([3, 5, 7, 10, 15]),

            'accepts_cod'     => fake()->boolean(80),
            'offers_delivery' => fake()->boolean(55),
            // WAJIB true bila offers_delivery false: CHECK stores_fulfilment_chk
            // menuntut minimal satu cara pemenuhan. Diselaraskan di
            // configure() supaya kombinasi false/false tidak pernah lahir.
            'allows_pickup'   => true,

            'operating_hours' => $this->jamOperasional(),
            'rating_avg'      => 0,
            'total_reviews'   => 0,
            'is_active'       => true,

            'status' => StoreStatus::Pending,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Store $store): void {
            // CHECK stores_fulfilment_chk: minimal satu cara pemenuhan.
            if (! $store->offers_delivery && ! $store->allows_pickup) {
                $store->allows_pickup = true;
            }
        });
    }

    public function terverifikasi(): static
    {
        return $this->state(fn () => [
            'status'      => StoreStatus::Verified,
            'verified_at' => now()->subDays(fake()->numberBetween(1, 180)),
        ]);
    }

    public function menunggu(): static
    {
        // Foto ikut dipasang: antrian memerlukan foto benar-benar terunggah
        // (syarat persetujuan membaca kolom mentahnya), dan toko pending
        // TANPA foto adalah antrian yang tidak pernah bisa selesai.
        return $this->state(fn () => [
            'status'      => StoreStatus::Pending,
            'verified_at' => null,
            'photo'       => PlaceholderImg::url('toko-'.Str::lower(Str::random(8)), 600, 400, 'Foto Toko'),
        ]);
    }

    public function ditolak(): static
    {
        return $this->state(fn () => [
            'status'          => StoreStatus::Rejected,
            'rejected_at'     => now()->subDays(fake()->numberBetween(1, 30)),
            'rejected_reason' => fake()->randomElement([
                'Foto tempat usaha tidak jelas.',
                'Alamat di luar wilayah layanan Kabupaten Pasuruan.',
                'Nama toko tidak sesuai dengan dokumen usaha.',
            ]),
        ]);
    }

    /**
     * Diblokir BERSAMA pemiliknya — bukan keadaan yang lahir sendiri.
     * Pemiliknya sengaja dibuat ikut diblokir supaya pasangan data ini
     * menggambarkan keadaan yang benar-benar bisa terjadi di produksi.
     */
    public function diblokir(): static
    {
        return $this->state(fn () => [
            'user_id'        => User::factory()->diblokir(),
            'status'         => StoreStatus::Blocked,
            'blocked_at'     => now()->subDays(fake()->numberBetween(1, 30)),
            'blocked_reason' => 'Pemilik diblokir — penipuan berulang.',
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(['is_active' => false]);
    }

    /** @param array<int, StoreType> $types */
    public function tipe(array $types): static
    {
        return $this->state(['store_type' => $types]);
    }

    /**
     * Menempatkan toko pada koordinat tertentu — kolom biasa, jadi
     * cukup state(), tanpa afterMaking khusus.
     */
    public function diTitik(float $lat, float $lng): static
    {
        return $this->state(['latitude' => $lat, 'longitude' => $lng]);
    }

    /**
     * Jam operasional realistis: sebagian hari bisa tutup (null), yang
     * buka selalu tutup setelah buka.
     *
     * @return array<string, ?array{open: string, close: string}>
     */
    private function jamOperasional(): array
    {
        $hari = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

        return collect($hari)->mapWithKeys(fn (string $h): array => [
            $h => fake()->boolean(12)
                ? null
                : ['open' => '07:00', 'close' => fake()->randomElement(['17:00', '21:00', '22:00'])],
        ])->all();
    }
}
