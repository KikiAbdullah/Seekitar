<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Taksonomi kategori — WAJIB dijalankan saat deploy awal.
 *
 * `customer_requests.category_id` bersifat NOT NULL dan ber-FK RESTRICT, jadi
 * tanpa seeder ini tidak ada satu pun permintaan yang bisa dibuat.
 *
 * SUMBER DAFTAR: `BRANDING-GUIDELINE.md` §3.7 menyebut "Set 24 ikon" dengan
 * delapan kategori induk — Makanan & Harian, Jasa Rumah, Servis & Bengkel,
 * Material Bangunan, Pertanian & Ternak, Sewa Acara, Barang Bekas, dan UMKM.
 * Delapan induk itu dipakai apa adanya; tiap induk diberi dua subkategori sehingga
 * totalnya pas 24 baris (8 induk + 16 anak) (`Server_Implementation_Guide.md` §19.2).
 *
 * Kedalaman DIBATASI DUA LEVEL, sesuai `PRD.md` §5.1: "Kategori wajib (sampai
 * level 2, mis. Elektronik > AC)".
 *
 * IDEMPOTEN: memakai `updateOrCreate` dengan `slug` sebagai kunci, karena
 * seeder ini ikut dijalankan setiap deploy dan tidak boleh menggandakan data
 * atau melempar error unique.
 */
class CategorySeeder extends Seeder
{
    /**
     * Struktur: slug induk => [nama, ikon, [anak...]].
     *
     * Nama ikon mengikuti Heroicons v2 outline, sesuai keputusan
     * `BRANDING-GUIDELINE.md` §3.7.1 untuk ikon sistem.
     */
    private const TAXONOMY = [
        ['makanan-harian', 'Makanan & Harian', 'shopping-bag', [
            ['sembako', 'Sembako', 'archive-box'],
            ['makanan-siap-saji', 'Makanan Siap Saji', 'cake'],
        ]],
        ['jasa-rumah', 'Jasa Rumah', 'home-modern', [
            ['kebersihan', 'Kebersihan', 'sparkles'],
            ['tukang-bangunan', 'Tukang Bangunan', 'wrench-screwdriver'],
        ]],
        ['servis-bengkel', 'Servis & Bengkel', 'cog-6-tooth', [
            ['servis-elektronik', 'Servis Elektronik', 'tv'],
            ['bengkel-kendaraan', 'Bengkel Kendaraan', 'truck'],
        ]],
        ['material-bangunan', 'Material Bangunan', 'building-office-2', [
            ['semen-pasir', 'Semen & Pasir', 'cube'],
            ['besi-kayu', 'Besi & Kayu', 'squares-2x2'],
        ]],
        ['pertanian-ternak', 'Pertanian & Ternak', 'sun', [
            ['hasil-panen', 'Hasil Panen', 'shopping-cart'],
            ['pakan-bibit', 'Pakan & Bibit', 'beaker'],
        ]],
        ['sewa-acara', 'Sewa Acara', 'calendar-days', [
            ['tenda-kursi', 'Tenda & Kursi', 'rectangle-group'],
            ['sound-system', 'Sound System', 'speaker-wave'],
        ]],
        ['barang-bekas', 'Barang Bekas', 'arrow-path', [
            ['elektronik-bekas', 'Elektronik Bekas', 'device-phone-mobile'],
            ['perabot-bekas', 'Perabot Bekas', 'home'],
        ]],
        ['umkm', 'UMKM & Kerajinan', 'gift', [
            ['kerajinan-tangan', 'Kerajinan Tangan', 'scissors'],
            ['konveksi-jahit', 'Konveksi & Jahit', 'swatch'],
        ]],
    ];

    public function run(): void
    {
        $sort = 0;

        foreach (self::TAXONOMY as [$slug, $name, $icon, $children]) {
            $parent = Category::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'icon' => $icon, 'parent_id' => null, 'sort_order' => $sort++],
            );

            foreach ($children as [$childSlug, $childName, $childIcon]) {
                Category::updateOrCreate(
                    ['slug' => $childSlug],
                    [
                        'name'       => $childName,
                        'icon'       => $childIcon,
                        'parent_id'  => $parent->id,
                        'sort_order' => $sort++,
                    ],
                );
            }
        }
    }

    /** Jumlah baris yang seharusnya dihasilkan — dipakai test & checker. */
    public static function expectedCount(): int
    {
        return array_sum(array_map(
            static fn (array $group): int => 1 + count($group[3]),
            self::TAXONOMY,
        ));
    }
}
