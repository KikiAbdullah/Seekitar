<?php

namespace Database\Seeders;

use App\Enums\StoreType;
use App\Enums\VerificationStatus;
use App\Models\Category;
use App\Models\Store;
use App\Models\User;
use Database\Factories\Support\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * 50 toko bertitik pasti untuk halaman Peta Toko (§9.2).
 *
 * KENAPA SEEDER SENDIRI, BUKAN MENAIKKAN VOLUME DemoDataSeeder
 * ------------------------------------------------------------
 * DemoDataSeeder memakai `Wilayah::acak()` yang memilih kecamatan secara
 * acak. Pada 60 toko, sebaran acak itu WAJAR meninggalkan beberapa kecamatan
 * kosong — dan peta yang bolong membuat orang menyimpulkan "belum ada toko di
 * Tosari" padahal itu sekadar akibat undian.
 *
 * Seeder ini menjamin SETIAP kecamatan kebagian minimal satu toko lebih dulu,
 * baru sisanya dibagi ke kecamatan berpenduduk padat. Hasilnya peta yang
 * merepresentasikan kabupaten secara utuh, bukan gumpalan acak.
 *
 * DETERMINISTIK: `mt_srand()` dipasang di awal supaya dua kali seed
 * menghasilkan titik yang sama persis. Tanpa itu, tangkapan layar peta hari
 * ini tidak bisa dibandingkan dengan besok saat menelusuri regresi tampilan.
 */
class StoreMapSeeder extends Seeder
{
    /** Diminta eksplisit: 50 toko. */
    private const JUMLAH = 50;

    /**
     * Kecamatan yang mendapat toko tambahan setelah pembagian rata.
     *
     * Bukan daftar sembarangan: ini kecamatan dengan penduduk & aktivitas
     * niaga terbesar di Kabupaten Pasuruan (Bangil ibu kota kabupaten;
     * Pandaan–Gempol koridor industri; Grati–Lekok pusat perikanan timur).
     * Tanpa pembobotan ini, Tosari yang berpenduduk 18 ribu akan punya toko
     * sebanyak Pandaan yang berpenduduk lebih dari 100 ribu.
     */
    private const PADAT = [
        'Bangil', 'Pandaan', 'Gempol', 'Beji', 'Purwosari',
        'Sukorejo', 'Grati', 'Kraton', 'Rembang', 'Gondangwetan',
    ];

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException(
                'StoreMapSeeder hanya boleh jalan di local/testing. '
                .'Environment saat ini: '.app()->environment()
            );
        }

        // Deterministik — lihat catatan kelas.
        mt_srand(3514);

        $kategori = Category::query()->pluck('id');

        if ($kategori->isEmpty()) {
            throw new RuntimeException(
                'CategorySeeder harus dijalankan lebih dulu — toko tanpa kategori '
                .'tidak akan muncul di pencarian mana pun.'
            );
        }

        foreach ($this->rencana() as $i => $kecamatan) {
            [$lat, $lng] = $this->titikDi($kecamatan);

            /*
             * Status sengaja BUKAN acak murni: peta harus memperlihatkan
             * ketiga warna legenda. Pola tetap menjamin ±80% terverifikasi
             * (hijau), sisanya menunggu & ditolak, apa pun urutan undiannya.
             */
            $status = match (true) {
                $i % 10 === 4 => VerificationStatus::Pending,
                $i % 10 === 9 => VerificationStatus::Rejected,
                default       => VerificationStatus::Verified,
            };

            $factory = match ($status) {
                VerificationStatus::Pending  => Store::factory()->menunggu(),
                VerificationStatus::Rejected => Store::factory()->ditolak(),
                default                      => Store::factory()->terverifikasi(),
            };

            $factory
                ->diTitik($lat, $lng)
                ->tipe([$this->tipe($i)])
                ->create([
                    'user_id'      => $this->pemilik($i)->id,
                    'name'         => $this->nama($kecamatan, $i),
                    'address'      => $this->alamat($kecamatan),
                    'category_ids' => $kategori->random(min(2, $kategori->count()))->values()->all(),

                    // Sebagian nonaktif: peta membedakannya lewat opasitas,
                    // dan tanpa contoh nyata pembedaan itu tidak teruji.
                    'is_active'    => $i % 12 !== 7,
                ]);
        }

        $this->command?->info('StoreMapSeeder: '.self::JUMLAH.' toko di '
            .count(Wilayah::KECAMATAN).' kecamatan Kabupaten Pasuruan.');
    }

    /**
     * Daftar kecamatan untuk ke-50 toko.
     *
     * Tahap 1 memberi SATU toko ke setiap kecamatan (24 toko), tahap 2
     * membagi sisanya (26) ke kecamatan padat secara bergilir.
     *
     * @return list<string>
     */
    private function rencana(): array
    {
        $kecamatan = array_keys(Wilayah::KECAMATAN);
        $rencana   = $kecamatan;   // tahap 1: semua kebagian

        $sisa = self::JUMLAH - count($rencana);

        if ($sisa < 0) {
            throw new RuntimeException(
                'Jumlah toko ('.self::JUMLAH.') lebih sedikit daripada jumlah kecamatan ('
                .count($kecamatan).') — sebagian kecamatan pasti kosong di peta.'
            );
        }

        for ($i = 0; $i < $sisa; $i++) {
            $rencana[] = self::PADAT[$i % count(self::PADAT)];
        }

        return $rencana;
    }

    /**
     * Batas resmi Kabupaten Pasuruan (BPK Jatim):
     * 112°33'55"–113°30'37" BT, 7°32'34"–8°30'20" LS.
     */
    private const BATAS_LAT = [-8.5056, -7.5428];
    private const BATAS_LNG = [112.5653, 113.5103];

    /**
     * Titik di dalam kecamatan, digeser acak ±0,010° (±1,1 km).
     *
     * Pergeseran WAJIB: 50 toko yang duduk tepat di koordinat kantor
     * kecamatan akan bertumpuk menjadi satu penanda di peta, dan kabupaten
     * yang sebenarnya terisi tampak nyaris kosong.
     *
     * ⚠️ HASILNYA DIJEPIT ke batas kabupaten, dan itu bukan kehati-hatian
     * berlebihan. Gempol berpusat di lintang -7,5497 sementara batas utara
     * kabupaten ada di -7,5428 — jaraknya hanya 0,0069°. Tanpa penjepitan,
     * geseran +0,010° melempar toko 343 m ke luar wilayah, dan di peta ia
     * muncul mengambang di Kabupaten Sidoarjo. Ditemukan dengan menjalankan
     * seeder ini terhadap Wilayah::KECAMATAN sungguhan, bukan salinannya.
     *
     * @return array{0: float, 1: float}
     */
    private function titikDi(string $kecamatan): array
    {
        [$lat, $lng] = Wilayah::KECAMATAN[$kecamatan];

        return [
            round($this->jepit($lat + (mt_rand(-100, 100) / 10000), self::BATAS_LAT), 6),
            round($this->jepit($lng + (mt_rand(-100, 100) / 10000), self::BATAS_LNG), 6),
        ];
    }

    /**
     * @param  array{0: float, 1: float}  $batas
     */
    private function jepit(float $nilai, array $batas): float
    {
        // Margin 0,001° (±110 m) supaya titik tidak duduk PERSIS di garis
        // batas, yang secara visual tampak seperti kesalahan pembulatan.
        return max($batas[0] + 0.001, min($batas[1] - 0.001, $nilai));
    }

    /** Pemilik dipakai ulang setiap 5 toko — satu orang boleh punya beberapa toko. */
    private function pemilik(int $i): User
    {
        static $cache = [];

        $slot = intdiv($i, 5);

        // pro() = penjual berstempel KTP "matang". Lencana Pro-nya sendiri
        // tidak ditulis — turunan dari toko tervalidasi yang ia miliki.
        return $cache[$slot] ??= User::factory()
            ->pro()
            ->create(['name' => 'Pemilik Toko '.($slot + 1)]);
    }

    private function tipe(int $i): StoreType
    {
        return match ($i % 4) {
            0, 1    => StoreType::Goods,      // mayoritas toko barang
            2       => StoreType::Services,
            default => StoreType::Rental,
        };
    }

    private function nama(string $kecamatan, int $i): string
    {
        $awalan = ['Toko', 'UD', 'Warung', 'Kios', 'Depot', 'Sentra'];
        $inti   = ['Barokah', 'Rejeki', 'Makmur', 'Sejahtera', 'Amanah', 'Sentosa', 'Mulia', 'Lancar'];

        return $awalan[$i % count($awalan)].' '
            .$inti[$i % count($inti)].' '
            .$kecamatan.' '
            .Str::upper(Str::random(2));
    }

    private function alamat(string $kecamatan): string
    {
        $jalan = ['Jl. Raya', 'Jl. Pasar', 'Jl. Diponegoro', 'Jl. Sudirman', 'Jl. A. Yani'];

        return $jalan[mt_rand(0, count($jalan) - 1)].' '.$kecamatan
            .' No. '.mt_rand(1, 180).', Kec. '.$kecamatan;
    }
}
