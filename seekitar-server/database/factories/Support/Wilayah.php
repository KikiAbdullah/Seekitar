<?php

namespace Database\Factories\Support;

/**
 * Titik-titik nyata di Kabupaten Pasuruan.
 *
 * KENAPA BUKAN KOORDINAT ACAK
 * ---------------------------
 * Seekitar dikunci pada satu kabupaten (PRD §2) dan seluruh pencarian
 * memakai radius. Koordinat acak sedunia membuat data contoh tidak berguna:
 * `scopeNearby()` tidak akan pernah mengembalikan apa pun, sehingga fitur
 * utama produk justru tidak bisa dicoba.
 *
 * Titik di bawah adalah kecamatan sungguhan di Kabupaten Pasuruan, dengan
 * jarak antar-titik 3–35 km — cukup dekat untuk masuk radius 15–25 km, dan
 * cukup jauh untuk membuktikan pengurutan "terdekat" benar-benar bekerja.
 *
 * ⚠️ Urutan penulisan di sini adalah [lat, lng] — sesuai kebiasaan manusia.
 * Konversi ke WKT (yang butuh long-lat) ditangani `HasLocation::setLocation()`,
 * satu-satunya tempat opsi `axis-order=long-lat` dipasang.
 */
final class Wilayah
{
    /** Pusat acuan: alun-alun Bangil. */
    public const PUSAT_LAT = -7.5966;
    public const PUSAT_LNG = 112.8203;

    /**
     * Kecamatan => [lat, lng].
     *
     * @var array<string, array{0: float, 1: float}>
     */
    public const KECAMATAN = [
        'Bangil'      => [-7.5966, 112.8203],
        'Rembang'     => [-7.6183, 112.7761],
        'Kraton'      => [-7.6489, 112.8712],
        'Pohjentrek'  => [-7.6708, 112.9089],
        'Gondangwetan'=> [-7.6822, 112.9394],
        'Rejoso'      => [-7.6553, 112.9847],
        'Winongan'    => [-7.7169, 112.9331],
        'Grati'       => [-7.7003, 113.0192],
        'Nguling'     => [-7.6931, 113.0894],
        'Beji'        => [-7.5731, 112.7503],
        'Bangil Kota' => [-7.6031, 112.8156],
        'Pandaan'     => [-7.6528, 112.6942],
        'Sukorejo'    => [-7.7089, 112.7175],
        'Purwosari'   => [-7.7431, 112.7089],
        'Prigen'      => [-7.7000, 112.6167],
        'Gempol'      => [-7.5497, 112.6994],
        'Kejayan'     => [-7.7469, 112.8386],
        'Wonorejo'    => [-7.7331, 112.7797],
        'Purwodadi'   => [-7.7936, 112.7364],
    ];

    /**
     * Satu titik acak di dalam kabupaten, sedikit digeser.
     *
     * Pergeseran ±0,004° (±450 m) mencegah puluhan toko menumpuk di satu
     * koordinat identik — tumpukan seperti itu membuat pengurutan jarak
     * tampak "berfungsi" padahal semua jaraknya sama.
     *
     * @return array{0: float, 1: float} [lat, lng]
     */
    public static function acak(): array
    {
        [$lat, $lng] = self::KECAMATAN[array_rand(self::KECAMATAN)];

        return [
            round($lat + (mt_rand(-40, 40) / 10000), 6),
            round($lng + (mt_rand(-40, 40) / 10000), 6),
        ];
    }

    /** Titik acak dalam radius tertentu dari pusat, untuk uji radius. */
    public static function dekatPusat(float $maksKm = 8.0): array
    {
        $sudut = mt_rand(0, 359) * M_PI / 180;
        $jarak = (mt_rand(5, (int) ($maksKm * 100)) / 100);

        // 1 derajat lintang ≈ 111,32 km; bujur dikoreksi cos(lat).
        $dLat = ($jarak * cos($sudut)) / 111.32;
        $dLng = ($jarak * sin($sudut)) / (111.32 * cos(deg2rad(self::PUSAT_LAT)));

        return [
            round(self::PUSAT_LAT + $dLat, 6),
            round(self::PUSAT_LNG + $dLng, 6),
        ];
    }

    /** Nama kecamatan acak — dipakai untuk alamat yang masuk akal. */
    public static function namaKecamatan(): string
    {
        return (string) array_rand(self::KECAMATAN);
    }
}
