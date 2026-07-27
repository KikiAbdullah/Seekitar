<?php

namespace App\Models\Concerns;

use App\Support\SpatialSchema;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query berbasis lokasi. Di MySQL memakai indeks spasial; di SQLite
 * (pengujian) memakai Haversine pada kolom lat/lng.
 *
 * SELALU dua tahap di MySQL: MBRContains (pakai indeks) lalu
 * ST_Distance_Sphere (akurat). ST_Distance_Sphere sendirian tidak
 * memakai indeks sama sekali — lihat DATABASE.md §11.
 */
trait HasLocation
{
    public function scopeNearby(Builder $q, float $lat, float $lng, float $radiusKm): Builder
    {
        $meter  = $radiusKm * 1000;
        $latDeg = $meter / 111320;
        $lngDeg = $meter / (111320 * cos(deg2rad($lat)));

        if (! SpatialSchema::isMySql()) {
            // Bounding box + Haversine, setara secara logika untuk pengujian.
            return $q->whereBetween('latitude',  [$lat - $latDeg, $lat + $latDeg])
                     ->whereBetween('longitude', [$lng - $lngDeg, $lng + $lngDeg])
                     ->whereRaw(
                         '(6371000 * 2 * ASIN(SQRT(
                             POWER(SIN(RADIANS(? - latitude) / 2), 2) +
                             COS(RADIANS(latitude)) * COS(RADIANS(?)) *
                             POWER(SIN(RADIANS(? - longitude) / 2), 2)
                         ))) <= ?',
                         [$lat, $lat, $lng, $meter]
                     );
        }

        $bbox = sprintf(
            'POLYGON((%1$F %2$F, %1$F %4$F, %3$F %4$F, %3$F %2$F, %1$F %2$F))',
            $lng - $lngDeg, $lat - $latDeg,
            $lng + $lngDeg, $lat + $latDeg
        );

        return $q
            ->whereRaw('MBRContains(ST_GeomFromText(?, 4326), location)', [$bbox])
            ->whereRaw(
                'ST_Distance_Sphere(location, ST_GeomFromText(?, 4326)) <= ?',
                ["POINT($lng $lat)", $meter]
            );
    }

    public function scopeWithDistance(Builder $q, float $lat, float $lng): Builder
    {
        if (! SpatialSchema::isMySql()) {
            return $q->select('*')->selectRaw(
                '(6371 * 2 * ASIN(SQRT(
                    POWER(SIN(RADIANS(? - latitude) / 2), 2) +
                    COS(RADIANS(latitude)) * COS(RADIANS(?)) *
                    POWER(SIN(RADIANS(? - longitude) / 2), 2)
                ))) AS distance_km',
                [$lat, $lat, $lng]
            );
        }

        return $q->select('*')->selectRaw(
            'ST_Distance_Sphere(location, ST_GeomFromText(?, 4326)) / 1000 AS distance_km',
            ["POINT($lng $lat)"]
        );
    }

    public function scopeOrderByDistance(Builder $q, string $dir = 'asc'): Builder
    {
        return $q->orderBy('distance_km', $dir);
    }

    /**
     * Di SQLite koordinat adalah kolom biasa (lat/lng) sehingga bisa
     * langsung mass-assign. Di MySQL kolomnya POINT dan diisi lewat
     * setLocation() / booted hook.
     */
    public function initializeHasLocation(): void
    {
        if (! SpatialSchema::isMySql()) {
            $this->mergeFillable(['latitude', 'longitude']);
        }
    }

    /**
     * Satu-satunya cara menulis koordinat. Memusatkannya di sini mencegah
     * kesalahan urutan POINT(longitude latitude) yang terbalik dari
     * kebiasaan menulis "lat, lng" — kesalahan itu tidak memicu error,
     * hanya hasil yang salah diam-diam.
     */
    public function setLocation(float $lat, float $lng): static
    {
        if (SpatialSchema::isMySql()) {
            $this->location = \Illuminate\Support\Facades\DB::raw(
                sprintf("ST_GeomFromText('POINT(%F %F)', 4326)", $lng, $lat)
            );
        } else {
            $this->latitude  = $lat;
            $this->longitude = $lng;
        }

        return $this;
    }

    /** Koordinat sebagai GeoJSON [longitude, latitude] (API §12.3). */
    public function coordinates(): ?array
    {
        if (SpatialSchema::isMySql()) {
            return $this->location ? null : null;   // dibaca lewat accessor terpisah
        }

        return $this->latitude === null
            ? null
            : ['type' => 'Point', 'coordinates' => [(float) $this->longitude, (float) $this->latitude]];
    }
}
