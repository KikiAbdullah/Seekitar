<?php

namespace App\Support;

/**
 * Aritmetika jarak bumi — satu-satunya tempat rumusnya ditulis.
 *
 * PERANNYA SETELAH KOLOM POINT TOKO DIPENSIUNKAN
 * ----------------------------------------------
 * stores.latitude/longitude adalah kolom DECIMAL biasa, jadi pencarian
 * radius bekerja DUA TAHAP, keduanya tanpa SQL mentah:
 *
 *   1. SQL: kotak pembatas lewat `whereBetween` berindeks (Eloquent murni)
 *      — menyaring ribuan baris ke puluhan kandidat dalam milidetik.
 *      Kotaknya dihitung kotak() di sini.
 *   2. PHP: lingkaran akurat lewat haversineKm() — membuang sudut-sudut
 *      kotak yang sebenarnya di luar radius.
 *
 * Untuk skala SATU kabupaten ini lebih cepat daripada ST_Distance_Sphere
 * di atas POINT: tidak ada deserialisasi geometri per baris, tidak ada
 * konversi WKT, dan indeks DECIMAL biasa lebih ringan daripada SPATIAL
 * INDEX untuk rentang kecil.
 */
final class Jarak
{
    /** Derajat lintang per meter — jarak antar-lintang praktis konstan. */
    private const METER_PER_LAT_DEGREE = 111320;

    /** Radius rata-rata bumi dalam km (IUGG 1976) untuk haversine. */
    private const R_BUMI_KM = 6371.0088;

    /**
     * Kotak pembatas [latMin, latMax, lngMin, lngMax] untuk radius (km)
     * dari sebuah titik.
     *
     * Jarak antar-bujur menyempit mendekati kutub, jadi derajatnya
     * dikoreksi cos(latitude). Di khatulistiwa koreksinya nol; di kutub
     * kotaknya sebesar dunia — dan itu BENAR, bukan bug.
     *
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    public static function kotak(float $lat, float $lng, float $radiusKm): array
    {
        $meter  = $radiusKm * 1000;
        $latDeg = $meter / self::METER_PER_LAT_DEGREE;

        $cos    = cos(deg2rad($lat));
        $lngDeg = abs($cos) > 1e-9 ? $meter / (self::METER_PER_LAT_DEGREE * $cos) : 180.0;

        return [$lat - $latDeg, $lat + $latDeg, $lng - $lngDeg, $lng + $lngDeg];
    }

    /**
     * Jarak lingkaran-besar dua titik (km), rumus haversine.
     *
     * Akurat hingga kesalahan bentuk bumi ≈0,3% — jauh di bawah lebar satu
     * gang, jadi lebih dari cukup untuk pencocokan "dalam radius X km".
     */
    public static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::R_BUMI_KM * 2 * asin(min(1.0, sqrt($a)));
    }
}
