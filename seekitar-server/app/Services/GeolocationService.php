<?php

namespace App\Services;

use App\Models\Concerns\HasLocation;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query radius terpusat.
 *
 * Sebagian besar kebutuhan sudah dilayani trait `HasLocation` lewat scope
 * (`nearby`, `withDistance`). Kelas ini untuk hal yang tidak cocok jadi scope:
 * perhitungan jarak antar dua titik, kotak pembatas, dan validasi koordinat.
 *
 * ⚠️ Urutan `POINT(longitude latitude)` terbalik dari kebiasaan menulis
 * "lat, lng", dan MySQL membaca SRID 4326 sebagai (lat lng) sesuai EPSG —
 * karena itu setiap WKT WAJIB menyertakan `axis-order=long-lat`. Lihat
 * `DATABASE.md` §11.
 */
class GeolocationService
{
    /** Meter per derajat lintang; praktis konstan di seluruh bumi. */
    private const METER_PER_LAT_DEGREE = 111320;

    /** Radius bumi dalam meter (WGS 84 mean radius). */
    private const EARTH_RADIUS_M = 6371000;

    /**
     * Saring query ke radius tertentu.
     *
     * Delegasi ke scope `nearby()` supaya pola dua tahap (MBRContains lalu
     * ST_Distance_Sphere) hanya ditulis di satu tempat — lihat HasLocation.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    public function withinRadius(Builder $query, float $lat, float $lng, float $radiusKm): Builder
    {
        $this->assertValidCoordinates($lat, $lng);

        return $query->nearby($lat, $lng, $radiusKm);
    }

    /** @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query */
    public function selectDistance(Builder $query, float $lat, float $lng): Builder
    {
        $this->assertValidCoordinates($lat, $lng);

        return $query->withDistance($lat, $lng);
    }

    /**
     * Jarak great-circle antara dua titik, dalam kilometer.
     *
     * Dipakai saat perhitungan dibutuhkan di PHP (mis. menyusun payload
     * notifikasi) tanpa perlu bolak-balik ke basis data.
     */
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $this->assertValidCoordinates($lat1, $lng1);
        $this->assertValidCoordinates($lat2, $lng2);

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return (self::EARTH_RADIUS_M * 2 * asin(min(1.0, sqrt($a)))) / 1000;
    }

    /**
     * Kotak pembatas mengelilingi sebuah titik.
     *
     * @return array{min_lat: float, max_lat: float, min_lng: float, max_lng: float}
     */
    public function boundingBox(float $lat, float $lng, float $radiusKm): array
    {
        $this->assertValidCoordinates($lat, $lng);

        $meter  = $radiusKm * 1000;
        $latDeg = $meter / self::METER_PER_LAT_DEGREE;
        // Jarak antar-bujur menyempit mendekati kutub, jadi dikoreksi cos(lat).
        $lngDeg = $meter / (self::METER_PER_LAT_DEGREE * cos(deg2rad($lat)));

        return [
            'min_lat' => $lat - $latDeg,
            'max_lat' => $lat + $latDeg,
            'min_lng' => $lng - $lngDeg,
            'max_lng' => $lng + $lngDeg,
        ];
    }

    /**
     * Apakah titik masih di dalam radius?
     *
     * Dipakai pencocokan dua arah di `BroadcastService`, di mana radius toko
     * dan radius pembeli harus keduanya terpenuhi.
     */
    public function isWithinRadius(float $lat1, float $lng1, float $lat2, float $lng2, float $radiusKm): bool
    {
        return $this->distanceKm($lat1, $lng1, $lat2, $lng2) <= $radiusKm;
    }

    /**
     * Tolak koordinat di luar rentang.
     *
     * Ini bukan formalitas: lintang > 90 membuat MySQL menolak seluruh query
     * dengan `ERROR 3617`, dan tertukarnya lat/lng adalah cara paling umum
     * hal itu terjadi. Gagal di sini memberi pesan yang jauh lebih jelas.
     */
    public function assertValidCoordinates(float $lat, float $lng): void
    {
        if ($lat < -90 || $lat > 90) {
            throw new \InvalidArgumentException(
                "Latitude {$lat} di luar rentang [-90, 90]. "
                .'Kemungkinan lat & lng tertukar — urutan WKT adalah POINT(longitude latitude).'
            );
        }

        if ($lng < -180 || $lng > 180) {
            throw new \InvalidArgumentException("Longitude {$lng} di luar rentang [-180, 180].");
        }
    }
}
