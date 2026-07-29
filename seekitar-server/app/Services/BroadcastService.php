<?php

namespace App\Services;

use App\Enums\StoreStatus;
use App\Support\Jarak;
use App\Models\CustomerRequest;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;

/**
 * Pencocokan penyedia untuk sebuah permintaan pembeli.
 *
 * Aturannya cukup rumit dan dipakai dari tiga tempat (API buat permintaan,
 * siar ulang oleh admin, dan job antrian), jadi harus hidup di satu kelas —
 * lihat `Server_Implementation_Guide.md` §4.1.
 */
class BroadcastService
{
    /**
     * Batas penerima siaran.
     *
     * Bukan angka sembarang: tanpa batas, satu permintaan di pusat kota bisa
     * memicu ribuan notifikasi sekaligus dan membanjiri antrian FCM.
     */
    public const MAX_RECIPIENTS = 50;

    public function __construct(private readonly GeolocationService $geo) {}

    /**
     * Toko yang berhak menerima siaran permintaan ini.
     *
     * Kriteria dari PRD §5.2.2, dengan satu tambahan penting: pencocokan
     * bersifat DUA ARAH.
     *
     * @return Collection<int, Store>
     */
    public function matchingStores(CustomerRequest $request): Collection
    {
        [$lat, $lng] = $this->coordinatesOf($request);

        $this->geo->assertValidCoordinates($lat, $lng);

        /*
         * Tanpa SQL mentah (keputusan skema 2.3 — lokasi toko adalah kolom
         * DECIMAL berindeks): kotak pembatas disaring engine lewat
         * whereBetween, lalu KEDUA arah lingkaran akuratnya diputus PHP
         * lewat satu perhitungan jarak per kandidat. Kandidat kotak pada
         * skala satu kabupaten berjumlah puluhan — satu query, satu
         * perhitungan, selesai.
         */
        return Store::query()
            ->where('is_active', true)
            ->where('status', StoreStatus::Verified)

            // Toko tidak boleh menawar pada permintaannya sendiri.
            ->where('user_id', '!=', $request->user_id)

            // Kategori toko memuat kategori permintaan. `category_ids` adalah
            // JSON, bukan FK, jadi tidak bisa di-JOIN (DATABASE.md §4.2) —
            // whereJsonContains milik Eloquent, bukan JSON_CONTAINS mentah.
            ->whereJsonContains('category_ids', $request->category_id)

            // Kotak kasar SQL-murni untuk KEDUA arah: saring radius yang
            // LEBIH BESAR dari dua radius, supaya tidak ada kandidat sah
            // yang terbuang sebelum dihitung akurat.
            ->withinBox($lat, $lng, max((float) $request->radius_km, 50.0))
            ->get()
            ->map(function (Store $s) use ($lat, $lng): Store {
                $s->setAttribute('distance_km', Jarak::haversineKm(
                    $lat, $lng, (float) $s->latitude, (float) $s->longitude
                ));
                return $s;
            })

            // ARAH 1 (akurat) — toko berada dalam radius yang dipilih pembeli.
            ->filter(fn (Store $s) => $s->distance_km <= (float) $request->radius_km)

            // ARAH 2 (akurat) — pembeli berada dalam radius layanan toko.
            ->filter(fn (Store $s) => $s->distance_km <= (float) $s->service_radius_km)

            // Prioritas: rating tertinggi dulu, lalu yang terdekat.
            ->sortBy([
                ['rating_avg', 'desc'],
                ['distance_km', 'asc'],
            ])
            ->values()
            ->take(self::MAX_RECIPIENTS);
    }

    /** Jumlah calon penerima, tanpa menarik seluruh barisnya. */
    public function countMatchingStores(CustomerRequest $request): int
    {
        return $this->matchingStores($request)->count();
    }

    /**
     * Koordinat permintaan.
     *
     * Kolom `location` bertipe POINT dan isinya WKB biner yang tidak berguna
     * di PHP, jadi lat/lng dibaca lewat scope `withCoordinates()`. Contoh di
     * dokumen sempat memakai `$request->longitude` langsung — itu hanya
     * bekerja bila query pemanggilnya kebetulan memakai scope tersebut.
     *
     * @return array{0: float, 1: float}  [lat, lng]
     */
    private function coordinatesOf(CustomerRequest $request): array
    {
        $lat = $request->getAttribute('latitude');
        $lng = $request->getAttribute('longitude');

        if ($lat === null || $lng === null) {
            $fresh = CustomerRequest::query()
                ->withCoordinates()
                ->whereKey($request->getKey())
                ->firstOrFail();

            $lat = $fresh->getAttribute('latitude');
            $lng = $fresh->getAttribute('longitude');
        }

        return [(float) $lat, (float) $lng];
    }
}
