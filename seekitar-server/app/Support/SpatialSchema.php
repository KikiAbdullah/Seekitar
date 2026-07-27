<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Operasi geospasial MySQL.
 *
 * KENAPA HANYA MYSQL: skema Seekitar bergantung pada tipe `POINT SRID 4326`,
 * `SPATIAL INDEX`, dan `ST_Distance_Sphere` (DATABASE.md §7.3 & §11).
 * Tidak ada engine lain yang dipakai proyek ini, sehingga tidak ada lagi
 * jalur alternatif lat/lng seperti versi sebelumnya.
 *
 * Dulu kelas ini menyediakan fallback SQLite agar test bisa jalan tanpa
 * server MySQL. Fallback itu dihapus karena menyimpan DUA definisi skema
 * yang berbeda: test hijau di SQLite tidak membuktikan apa pun tentang
 * perilaku SRID, SPATIAL INDEX, atau `MBRContains` di produksi.
 */
final class SpatialSchema
{
    /**
     * Pastikan koneksi memang MySQL.
     *
     * Dipanggil di awal setiap operasi supaya kesalahan konfigurasi ketahuan
     * saat migrasi — bukan berupa SQL error yang membingungkan di tengah jalan.
     */
    public static function assertMySql(?string $connection = null): void
    {
        $driver = DB::connection($connection)->getDriverName();

        if ($driver !== 'mysql') {
            throw new RuntimeException(
                "Seekitar hanya mendukung MySQL, koneksi aktif memakai driver '{$driver}'. "
                .'Skema ini butuh POINT SRID 4326, SPATIAL INDEX, dan ST_Distance_Sphere. '
                .'Lihat DATABASE.md §1.'
            );
        }
    }

    /**
     * Tambahkan kolom POINT ke tabel yang sudah ada.
     *
     * `$column` bisa diganti (mis. `shipping_location` di `orders`) sehingga
     * satu tabel boleh menyimpan lebih dari satu titik.
     */
    public static function addLocationColumn(
        string $table,
        bool $nullable = false,
        ?string $after = null,
        string $column = 'location',
    ): void {
        self::assertMySql();

        $null = $nullable ? 'NULL' : 'NOT NULL';
        $pos  = $after ? "AFTER `$after`" : '';

        // SRID WAJIB melekat di kolom. Tanpa itu MySQL menolak SPATIAL INDEX
        // dengan "A SPATIAL index may only contain a geometrical type column".
        DB::statement("ALTER TABLE `$table` ADD COLUMN `$column` POINT $null SRID 4326 $pos");
    }

    /**
     * Buat SPATIAL INDEX.
     *
     * MySQL hanya mengizinkannya pada kolom NOT NULL — memanggil ini untuk
     * kolom nullable akan gagal, dan itu memang disengaja.
     */
    public static function addSpatialIndex(string $table, string $indexName, string $column = 'location'): void
    {
        self::assertMySql();

        DB::statement("ALTER TABLE `$table` ADD SPATIAL INDEX `$indexName` (`$column`)");
    }

    /**
     * Ekspresi SQL untuk menulis koordinat.
     *
     * Opsi `axis-order=long-lat` WAJIB: untuk SRID 4326 MySQL mengikuti EPSG
     * yang menempatkan LATITUDE di sumbu pertama. Tanpa opsi ini, bujur
     * Indonesia (95°–141° BT) dibaca sebagai lintang dan ditolak dengan
     * `ERROR 3617: Latitude ... is out of range`.
     */
    public static function pointExpression(float $lat, float $lng): string
    {
        return sprintf("ST_GeomFromText('POINT(%F %F)', 4326, 'axis-order=long-lat')", $lng, $lat);
    }
}
