<?php

namespace Database\Factories;

use App\Enums\StoreType;
use App\Enums\VerificationStatus;
use App\Models\Store;
use App\Models\User;
use Database\Factories\Support\Wilayah;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Store>
 *
 * CATATAN PENTING soal kolom `location`:
 * kolom POINT tidak bisa diisi lewat mass-assignment biasa — nilainya harus
 * ekspresi SQL `ST_GeomFromText(..., 'axis-order=long-lat')`. Karena itu
 * factory ini memakai `afterMaking`/`afterCreating` yang memanggil
 * `setLocation()`, satu-satunya tempat opsi axis-order dipasang.
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

            'address'           => fake('id_ID')->streetAddress().', Kec. '.Wilayah::namaKecamatan(),
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

            'verification_status' => VerificationStatus::Pending,
        ];
    }

    /**
     * Mengisi kolom POINT lewat setLocation().
     *
     * KENAPA afterMaking, BUKAN definition()
     * --------------------------------------
     * Kolom POINT tidak bisa diisi nilai biasa; isinya harus ekspresi SQL
     * `ST_GeomFromText(..., 'axis-order=long-lat')`. Satu-satunya tempat
     * ekspresi itu dirakit adalah `HasLocation::setLocation()`.
     *
     * afterMaking (bukan afterCreating) karena `location` NOT NULL — insert
     * akan gagal bila kolomnya baru diisi setelah baris tersimpan.
     *
     * ⚠️ Koordinat TIDAK boleh disimpan sebagai atribut semu (mis. `_lat`)
     * lalu dipindahkan di sini. Factory membuat model lewat
     * `new Model($attributes)` yang menghormati $fillable, dan atribut di
     * luar daftar itu melempar MassAssignmentException — bukan diabaikan
     * diam-diam. Diverifikasi langsung.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Store $store): void {
            // CHECK stores_fulfilment_chk: minimal satu cara pemenuhan.
            if (! $store->offers_delivery && ! $store->allows_pickup) {
                $store->allows_pickup = true;
            }

            // Sudah dipasang state diTitik()? Jangan ditimpa.
            if (! isset($store->getAttributes()['location'])) {
                [$lat, $lng] = Wilayah::acak();
                $store->setLocation($lat, $lng);
            }
        });
    }

    public function terverifikasi(): static
    {
        return $this->state(fn () => [
            'verification_status' => VerificationStatus::Verified,
            'verified_at'         => now()->subDays(fake()->numberBetween(1, 180)),
        ]);
    }

    public function menunggu(): static
    {
        return $this->state([
            'verification_status' => VerificationStatus::Pending,
            'verified_at'         => null,
        ]);
    }

    public function ditolak(): static
    {
        return $this->state(fn () => [
            'verification_status' => VerificationStatus::Rejected,
            'rejected_reason'     => fake()->randomElement([
                'Foto tempat usaha tidak jelas.',
                'Alamat di luar wilayah layanan Kabupaten Pasuruan.',
                'Nama toko tidak sesuai dengan dokumen usaha.',
            ]),
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
     * Menempatkan toko pada koordinat tertentu.
     *
     * Memakai afterMaking sendiri, BUKAN properti factory: `newInstance()`
     * merakit ulang objek factory dari daftar properti tetap (count, states,
     * afterMaking, …) sehingga properti kustom apa pun HILANG begitu state
     * lain dirangkai sesudahnya. Closure afterMaking ikut disalin, jadi ia
     * selamat. Diverifikasi dengan membaca newInstance() di framework.
     */
    public function diTitik(float $lat, float $lng): static
    {
        return $this->afterMaking(fn (Store $store) => $store->setLocation($lat, $lng));
    }

    /**
     * Jam buka per hari.
     *
     * Sebagian toko sengaja tutup di hari Minggu — data yang selalu buka
     * 7 hari membuat logika "sedang tutup" tidak pernah teruji.
     */
    private function jamOperasional(): array
    {
        $buka  = fake()->randomElement(['07:00', '07:30', '08:00', '09:00']);
        $tutup = fake()->randomElement(['16:00', '17:00', '20:00', '21:00']);

        $jam = [];
        foreach (['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'] as $hari) {
            $jam[$hari] = ($hari === 'minggu' && fake()->boolean(40))
                ? ['buka' => null, 'tutup' => null]
                : ['buka' => $buka, 'tutup' => $tutup];
        }

        return $jam;
    }
}
