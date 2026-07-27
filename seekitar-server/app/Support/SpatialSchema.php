<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pembungkus operasi geospasial agar migrasi & query berjalan di MySQL
 * (produksi) maupun SQLite (pengujian lokal).
 *
 * KENAPA INI ADA: DATABASE.md §7.3 memakai POINT SRID 4326, SPATIAL INDEX,
 * dan ST_Distance_Sphere — semuanya TIDAK ADA di SQLite. Tanpa pembungkus
 * ini, seluruh test suite mustahil dijalankan tanpa server MySQL.
 *
 * Di SQLite kolom POINT digantikan sepasang kolom desimal (lat/lng) dan
 * jarak dihitung dengan Haversine. Hasilnya setara untuk pengujian logika;
 * verifikasi performa & indeks tetap WAJIB dilakukan di MySQL.
 */
final class SpatialSchema
{
    public static function isMySql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    /**
     * Tambahkan kolom lokasi ke tabel yang sudah ada.
     *
     * `$column` bisa diganti (mis. `shipping_location` di `orders`), sehingga
     * satu tabel boleh punya lebih dari satu titik. Di SQLite kolom POINT
     * dipecah jadi `<column>_latitude` / `<column>_longitude`; khusus nama
     * baku `location` dipakai `latitude`/`longitude` tanpa awalan agar
     * kompatibel dengan HasLocation dan migrasi yang sudah ada.
     */
    public static function addLocationColumn(
        string $table,
        bool $nullable = false,
        ?string $after = null,
        string $column = 'location',
    ): void {
        if (self::isMySql()) {
            $null  = $nullable ? 'NULL' : 'NOT NULL';
            $pos   = $after ? "AFTER `$after`" : '';
            // SRID WAJIB melekat di kolom, kalau tidak SPATIAL INDEX ditolak.
            DB::statement("ALTER TABLE `$table` ADD COLUMN `$column` POINT $null SRID 4326 $pos");
            return;
        }

        [$latCol, $lngCol] = self::sqliteColumns($column);

        Schema::table($table, function ($t) use ($nullable, $after, $latCol, $lngCol) {
            $lat = $t->decimal($latCol, 10, 7);
            $lng = $t->decimal($lngCol, 10, 7);
            if ($nullable) { $lat->nullable(); $lng->nullable(); }
            if ($after)    { $lat->after($after); }
        });
    }

    /** Nama sepasang kolom pengganti POINT di SQLite. */
    public static function sqliteColumns(string $column = 'location'): array
    {
        return $column === 'location'
            ? ['latitude', 'longitude']
            : ["{$column}_latitude", "{$column}_longitude"];
    }

    /** Indeks spasial hanya bisa dibuat di MySQL dan pada kolom NOT NULL. */
    public static function addSpatialIndex(string $table, string $indexName, string $column = 'location'): void
    {
        if (! self::isMySql()) {
            return;   // SQLite: dilewati, tidak ada padanannya
        }

        DB::statement("ALTER TABLE `$table` ADD SPATIAL INDEX `$indexName` (`$column`)");
    }

    /** Ekspresi SQL untuk menyimpan koordinat. */
    public static function pointExpression(float $lat, float $lng): string
    {
        return self::isMySql()
            ? "ST_GeomFromText('POINT($lng $lat)', 4326)"
            : "NULL";
    }
}
