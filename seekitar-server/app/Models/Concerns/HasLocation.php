<?php

namespace App\Models\Concerns;

use App\Support\SpatialSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Query berbasis lokasi.
 */
trait HasLocation
{
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
                'MBRContains('.SpatialSchema::geomFromTextSql().', location)',
                [$bbox]
            )
            ->whereRaw(
                'ST_Distance_Sphere(location, '.SpatialSchema::geomFromTextSql().') <= ?',
                [self::wkt($lat, $lng), $meter]
            );
    }

    /** Tambahkan kolom `distance_km` ke hasil query. */
    public function scopeWithDistance(Builder $q, float $lat, float $lng): Builder
    {
        return $q
            ->select($this->baseSelect($q))
            ->selectRaw(
                'ST_Distance_Sphere(location, '.SpatialSchema::geomFromTextSql().') / 1000 AS distance_km',
                [self::wkt($lat, $lng)]
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
        return $q
            ->select($this->baseSelect($q))
            ->selectRaw(SpatialSchema::latSql($column).' AS latitude, '.SpatialSchema::lngSql($column).' AS longitude');
    }

    /**
     * Kolom yang sudah dipilih, atau `*` bila belum ada.
     *
     * KENAPA PERLU: `select('*')` MENIMPA daftar kolom sebelumnya. Menulis
     * `->withCoordinates()->withDistance()` tanpa ini membuat scope kedua
     * membuang `latitude`/`longitude` yang baru saja ditambahkan scope
     * pertama — hilang diam-diam, tanpa error, dan baru ketahuan saat
     * pencocokan siaran mendapat koordinat NULL.
     *
     * @return array<int, mixed>
     */
    private function baseSelect(Builder $q): array
    {
        return $q->getQuery()->columns ?: ['*'];
    }

    /**
     * Satu-satunya cara menulis koordinat.
     *
     * Dipusatkan di sini supaya opsi axis-order tidak mungkin terlupakan di
     * salah satu pemanggil.
     */
    public function setLocation(float $lat, float $lng, string $column = 'location'): static
    {
        $this->{$column} = DB::raw(SpatialSchema::pointExpression($lat, $lng));

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
