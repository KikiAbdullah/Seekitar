<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Category;
use App\Models\CustomerRequest;
use App\Models\User;
use Database\Factories\Support\Wilayah;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomerRequest>
 *
 * Papan Kebutuhan adalah fitur unggulan Seekitar (PRD §5.2), jadi data
 * contohnya harus terasa nyata — bukan "lorem ipsum" yang membuat pengujian
 * pencocokan siaran tidak berarti.
 */
class CustomerRequestFactory extends Factory
{
    protected $model = CustomerRequest::class;

    /** [judul, anggaran minimum, anggaran maksimum]. */
    private const KEBUTUHAN = [
        ['Cari tukang servis AC panggilan', 100000, 250000],
        ['Butuh jasa bersih rumah setelah renovasi', 200000, 500000],
        ['Cari tukang las pagar besi', 500000, 1500000],
        ['Butuh sewa tenda untuk hajatan 200 orang', 1000000, 2500000],
        ['Cari supplier beras 50 kg rutin bulanan', 500000, 700000],
        ['Butuh tukang cat rumah 2 lantai', 1500000, 4000000],
        ['Cari jasa sedot WC segera', 300000, 700000],
        ['Butuh pasir cor 2 pickup', 600000, 1000000],
        ['Cari servis mesin cuci tidak berputar', 100000, 300000],
        ['Butuh katering nasi kotak 100 porsi', 1500000, 2500000],
        ['Cari sewa mobil pickup untuk pindahan', 250000, 450000],
        ['Butuh bibit cabai dan pupuk untuk 1 hektar', 1000000, 2000000],
        ['Cari tukang kayu buat lemari custom', 1200000, 3000000],
        ['Butuh jasa potong rambut ke rumah', 35000, 100000],
        ['Cari bengkel motor panggilan, motor mogok', 50000, 200000],
        ['Butuh sound system untuk pengajian', 400000, 1200000],
        ['Cari jasa instalasi listrik rumah baru', 800000, 2500000],
        ['Butuh gas LPG 3 kg antar ke rumah', 20000, 30000],
        ['Cari tukang taman untuk perawatan bulanan', 300000, 800000],
        ['Butuh servis kulkas tidak dingin', 150000, 400000],
    ];

    public function definition(): array
    {
        [$judul, $min, $maks] = fake()->randomElement(self::KEBUTUHAN);

        // Sebagian pembeli tidak menetapkan anggaran — kolomnya NULL-able,
        // dan alur "tanpa anggaran" harus ikut terwakili di data contoh.
        $pakaiAnggaran = fake()->boolean(75);

        return [
            'id'          => (string) Str::uuid7(),
            'user_id'     => User::factory(),
            'title'       => $judul,
            'description' => $this->deskripsi(),

            /*
             * Kategori dioper pemanggil, BUKAN diambil dari basis data.
             *
             * Versi pertama memakai `Category::query()->inRandomOrder()`, dan
             * itu dua kali salah: `make()` jadi mustahil dipakai tanpa koneksi
             * (definition() langsung membuka PDO), dan tiap baris menambah
             * satu query acak — 500 permintaan berarti 500 kali ORDER BY RAND().
             *
             * Seeder yang tahu kategori nyata menyetelnya lewat
             * `->untukKategori()`; nilai 1 hanyalah cadangan agar factory tetap
             * bisa dipakai test yang tidak peduli kategori.
             */
            'category_id' => 1,

            'budget_min' => $pakaiAnggaran ? $min : null,
            'budget_max' => $pakaiAnggaran ? $maks : null,

            'images'    => fake()->boolean(40)
                ? ['requests/'.Str::uuid7().'.jpg']
                : null,

            // Default PRD: 15 km untuk siaran permintaan (berbeda dari radius
            // layanan toko yang 5 km).
            'radius_km' => fake()->randomElement([10, 15, 15, 20, 25]),

            'required_date'   => fake()->boolean(35)
                ? now()->addDays(fake()->numberBetween(1, 14))
                : null,
            'expires_at'      => now()->addHours(24),
            'extension_count' => 0,
            'status'          => RequestStatus::Open,
        ];
    }

    /** Lokasi WAJIB (kolom NOT NULL) — lihat catatan di StoreFactory. */
    public function configure(): static
    {
        return $this->afterMaking(function (CustomerRequest $request): void {
            if (! isset($request->getAttributes()['location'])) {
                [$lat, $lng] = Wilayah::acak();
                $request->setLocation($lat, $lng);
            }
        });
    }

    public function diTitik(float $lat, float $lng): static
    {
        return $this->afterMaking(fn (CustomerRequest $r) => $r->setLocation($lat, $lng));
    }

    /** Masih menerima penawaran. */
    public function terbuka(): static
    {
        return $this->state(fn () => [
            'status'     => RequestStatus::Open,
            'expires_at' => now()->addHours(fake()->numberBetween(2, 24)),
        ]);
    }

    /** Pembeli sudah memilih penyedia; tidak menerima penawaran lagi. */
    public function ditutup(): static
    {
        return $this->state(['status' => RequestStatus::Closed]);
    }

    /**
     * Lewat masa berlaku.
     *
     * `expires_at` di masa lalu DAN status expired — menyetel salah satunya
     * saja menghasilkan baris yang tidak konsisten dengan `isOpen()`.
     */
    public function kedaluwarsa(): static
    {
        return $this->state(fn () => [
            'status'     => RequestStatus::Expired,
            'expires_at' => now()->subHours(fake()->numberBetween(1, 72)),
        ]);
    }

    /** Sudah diperpanjang pembeli (maks 2 kali, DATABASE.md §4.5). */
    public function diperpanjang(int $kali = 1): static
    {
        return $this->state(fn () => [
            'extension_count' => min($kali, 2),
            'extended_at'     => now()->subHours(fake()->numberBetween(1, 12)),
            'expires_at'      => now()->addHours(fake()->numberBetween(2, 24)),
        ]);
    }

    /** Menyetel kategori — dipakai seeder yang tahu taksonomi sungguhan. */
    public function untukKategori(int|Category $kategori): static
    {
        return $this->state([
            'category_id' => $kategori instanceof Category ? $kategori->id : $kategori,
        ]);
    }

    public function mendesak(): static
    {
        return $this->state(fn () => [
            'required_date' => now()->addDay(),
            'expires_at'    => now()->addHours(6),
        ]);
    }

    private function deskripsi(): string
    {
        $pembuka = fake()->randomElement([
            'Butuh secepatnya',
            'Mohon bantuan warga sekitar',
            'Sedang mencari penyedia terpercaya',
            'Perlu dikerjakan minggu ini',
        ]);

        $penutup = fake()->randomElement([
            'Lokasi di Kec. '.Wilayah::namaKecamatan().'. Bisa nego harga.',
            'Wilayah '.Wilayah::namaKecamatan().'. Tolong sertakan estimasi waktu.',
            'Utamakan yang bisa datang hari ini. Area '.Wilayah::namaKecamatan().'.',
            'Mohon penawaran beserta rincian biayanya. Kec. '.Wilayah::namaKecamatan().'.',
        ]);

        return "{$pembuka}. {$penutup}";
    }
}
