<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk publik entitas User (API §2.2).
 *
 * Resource ini adalah SATU-SATUNYA jalan keluar data pengguna ke klien.
 * Mengembalikan model mentah akan membocorkan `nik`, `nik_hash`, dan kolom
 * pemblokiran — semuanya data pribadi (UU PDP).
 *
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'phone'              => $this->phone,
            'name'               => $this->name,
            'avatar_url'         => $this->avatar_url,
            'address'            => $this->address,
            // HANYA-BACA dan dihitung sistem (User::verificationLevel):
            // 1 = OTP, 2 = KTP disetujui, 3 = punya toko tervalidasi.
            // Tidak ada kolom pengatur — klien tidak akan menemukan cara
            // menuliskannya, memang sengaja.
            'verification_level' => $this->verification_level->value,
            'verified_at'        => $this->verified_at?->toIso8601ZuluString(),
            // Status berkas KTP untuk UI mobile (wajib setelah daftar):
            // null = belum unggah, terisi = menunggu/sudah ditinjau.
            'ktp_submitted_at'   => $this->ktp_submitted_at?->toIso8601ZuluString(),
            'status'             => $this->status?->value ?? $this->status,

            // Reputasi sebagai PEMBELI — dari ulasan store_to_buyer (cermin
            // toko↔pembeli; ReviewObserver yang menjaga angkanya).
            'rating_avg'    => (float) $this->rating_avg,
            'total_reviews' => (int) $this->total_reviews,

            // GeoJSON [longitude, latitude] — urutannya terbalik dari
            // kebiasaan menulis "lat, lng" (API §12.3, RFC 7946).
            'location'   => $this->coordinates(),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
        ];
    }
}
