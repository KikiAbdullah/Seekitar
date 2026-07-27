<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Query berbasis lokasi di MySQL 8.
 *
 * SELALU dua tahap: MBRContains (memakai SPATIAL INDEX) lalu
 * ST_Distance_Sphere (akurat). ST_Distance_Sphere sendirian TIDAK memakai
 * indeks sama sekali — lihat DATABASE.md §11.
 *
 * ────────────────────────────────────────────────────────────────────────
 *  URUTAN SUMBU: 'axis-order=long-lat' WAJIB, BUKAN GAYA PENULISAN
 * ────────────────────────────────────────────────────────────────────────
 * Untuk SRID 4326 MySQL mengikuti definisi EPSG: `AXIS["Lat",NORTH],
 * AXIS["Lon",EAST]` — sumbu pertama LATITUDE. Jadi `POINT(107.6 -6.9)`
 * dibaca sebagai latitude 107,6 dan langsung ditolak:
 *
 *     ERROR 3617 (22S03): Latitude 107.600000 is out of range in function
 *     st_geomfromtext. It must be within [-90.000000, 90.000000].
 *
 * Seluruh Indonesia berada di bujur 95°–141° BT — semuanya di luar rentang
 * ±90 — sehingga SETIAP penulisan titik akan gagal bila urutannya tidak
 * dinyatakan. Opsi `'axis-order=long-lat'` membuat MySQL membaca WKT dalam
 * urutan (longitude latitude), sesuai konvensi GeoJSON yang dipakai API.
 *
 * Alternatifnya menulis POINT(lat lng) tanpa opsi. Itu ditolak karena
 * membuat WKT di kode berbeda urutan dari payload API, dan kesalahan
 * seperti itu tidak memicu error — hanya lokasi yang salah diam-diam.
 */
trait HasLocation
{
    /** Opsi wajib untuk semua WKT bersistem koordinat geografis. */
    private const AXIS = 'axis-order=long-lat';

    /** Derajat lintang per meter (jarak antar-lintang praktis konstan). */
    private const METER_PER_LAT_DEGREE = 111320;

    /**
     * Saring baris dalam radius (km) dari sebuah titik.
     *
     * Tahap 1 memakai kotak pembatas agar SPATIAL INDEX terpakai; tahap 2
     * membuang sudut-sudut kotak yang sebenarnya di luar lingkaran.
     */
    public function scopeNearby(Builder $q, float $lat, float $lng, float $radiusKm): Builder
    {
        $meter  = $radiusKm * 1000;
        $latDeg = $meter / self::METER_PER_LAT_DEGREE;
        // Jarak antar-bujur menyempit mendekati kutub, jadi dikoreksi cos(lat).
        $lngDeg = $meter / (self::METER_PER_LAT_DEGREE * cos(deg2rad($lat)));

        $bbox = sprintf(
            'POLYGON((%1$F %2$F, %1$F %4$F, %3$F %4$F, %3$F %2$F, %1$F %2$F))',
            $lng - $lngDeg, $lat - $latDeg,
            $lng + $lngDeg, $lat + $latDeg
        );

        return $q
            ->whereRaw(
                'MBRContains(ST_GeomFromText(?, 4326, ?), location)',
                [$bbox, self::AXIS]
            )
            ->whereRaw(
                'ST_Distance_Sphere(location, ST_GeomFromText(?, 4326, ?)) <= ?',
                [self::wkt($lat, $lng), self::AXIS, $meter]
            );
    }

    /** Tambahkan kolom `distance_km` ke hasil query. */
    public function scopeWithDistance(Builder $q, float $lat, float $lng): Builder
    {
        return $q->select('*')->selectRaw(
            'ST_Distance_Sphere(location, ST_GeomFromText(?, 4326, ?)) / 1000 AS distance_km',
            [self::wkt($lat, $lng), self::AXIS]
        );
    }

    public function scopeOrderByDistance(Builder $q, string $dir = 'asc'): Builder
    {
        return $q->orderBy('distance_km', $dir);
    }

    /**
     * Sertakan latitude & longitude sebagai kolom biasa.
     *
     * Kolom POINT mentah berupa WKB biner yang tidak berguna di PHP, jadi
     * dibaca lewat ST_Latitude()/ST_Longitude() (MySQL 8.0.12+) yang tidak
     * bergantung pada urutan sumbu sama sekali.
     */
    public function scopeWithCoordinates(Builder $q, string $column = 'location'): Builder
    {
        return $q->select('*')->selectRaw(
            "ST_Latitude(`$column`) AS latitude, ST_Longitude(`$column`) AS longitude"
        );
    }

    /**
     * Satu-satunya cara menulis koordinat.
     *
     * Dipusatkan di sini supaya opsi axis-order tidak mungkin terlupakan di
     * salah satu pemanggil.
     */
    public function setLocation(float $lat, float $lng, string $column = 'location'): static
    {
        $this->{$column} = DB::raw(sprintf(
            "ST_GeomFromText('%s', 4326, '%s')",
            self::wkt($lat, $lng),
            self::AXIS
        ));

        return $this;
    }

    /**
     * Koordinat sebagai GeoJSON (API §12.3).
     *
     * GeoJSON selalu [longitude, latitude] — lihat RFC 7946. Nilainya
     * diambil dari kolom hasil scopeWithCoordinates().
     */
    public function coordinates(): ?array
    {
        $lat = $this->getAttribute('latitude');
        $lng = $this->getAttribute('longitude');

        if ($lat === null || $lng === null) {
            return null;
        }

        return ['type' => 'Point', 'coordinates' => [(float) $lng, (float) $lat]];
    }

    /** WKT POINT dalam urutan (longitude latitude), berpasangan dengan self::AXIS. */
    private static function wkt(float $lat, float $lng): string
    {
        return sprintf('POINT(%F %F)', $lng, $lat);
    }
}
