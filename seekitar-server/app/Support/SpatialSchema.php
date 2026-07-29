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

        // SRID 4326 di definisi kolom hanya didukung MySQL >= 8.0,
        // sementara MariaDB mengabaikannya. Pada MariaDB, SRID tetap
        // dipakai lewat ST_GeomFromText(..., 4326) dan SPATIAL INDEX
        // tetap bisa dibuat tanpa atribut SRID di kolom.
        $srid = self::isMariaDb() ? '' : 'SRID 4326';

        DB::statement("ALTER TABLE `$table` ADD COLUMN `$column` POINT $null $srid $pos");
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
     * Deteksi apakah koneksi saat ini adalah MariaDB.
     */
    public static function isMariaDb(?string $connection = null): bool
    {
        return str_contains(DB::connection($connection)->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION), 'MariaDB');
    }

    /**
     * SQL fragment untuk ST_GeomFromText dengan SRID 4326.
     *
     * MySQL butuh `'axis-order=long-lat'` (parameter ke-3), MariaDB tidak.
     * Placeholder bisa `?` (binding) atau WKT literal.
     */
    public static function geomFromTextSql(string $wktPlaceholder = '?'): string
    {
        $wkt = self::isMariaDb()
            ? "ST_GeomFromText({$wktPlaceholder}, 4326)"
            : "ST_GeomFromText({$wktPlaceholder}, 4326, 'axis-order=long-lat')";

        return $wkt;
    }

    /**
     * Ekspresi SQL untuk menulis koordinat.
     *
     * Opsi `axis-order=long-lat` hanya didukung MySQL >= 8.0.
     * MariaDB menggunakan bentuk `ST_GeomFromText(wkt, srid)`.
     * Untuk SRID 4326, titik ditulis sebagai `POINT(longitude latitude)`.
     */
    public static function pointExpression(float $lat, float $lng): string
    {
        $wkt = sprintf("'POINT(%F %F)'", $lng, $lat);

        if (self::isMariaDb()) {
            return "ST_GeomFromText({$wkt}, 4326)";
        }

        return "ST_GeomFromText({$wkt}, 4326, 'axis-order=long-lat')";
    }
}
