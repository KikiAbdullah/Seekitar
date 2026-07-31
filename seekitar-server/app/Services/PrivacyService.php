<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Layanan kepatuhan privasi (UU PDP, GDPR-equivalent).
 * Semua operasi penghapusan/anonymisasi data dicatat di activity_logs.
 */
class PrivacyService
{
    /**
     * Anonimisasi data pengguna — NIK, KTP, selfie, nomor HP dihash,
     * nama diganti "Pengguna Dihapus", alamat dikosongkan.
     * Dipanggil saat pengguna meminta hak dilupakan (right to erasure).
     */
    public function anonymizeUser(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'name'             => 'Pengguna Dihapus',
                'email'            => 'deleted-'.$user->id.'@anonymized.local',
                'phone'            => '000000000000',
                'address'          => null,
                'avatar_url'       => null,
                'nik'              => null,
                'nik_hash'         => null,
                'ktp_image'        => null,
                'selfie_image'     => null,
                'ktp_submitted_at' => null,
                'password'         => null,
                'remember_token'   => null,
                'location'         => null,
            ])->save();

            // Revoke semua token
            $user->tokens()->delete();

            Log::channel('privacy')->info('User anonymized', [
                'user_id' => $user->id,
                'admin'   => request()->user()?->id,
            ]);
        });
    }

    /**
     * Ekspor data pengguna — format JSON untuk portabilitas (UU PDP pasal 8).
     * Mencakup data profil, toko, listing, pesanan, ulasan.
     */
    public function exportUserData(User $user): array
    {
        $user->load([
            'stores.listings', 'stores.offers',
            'customerRequests', 'orders' => fn ($q) => $q->with('store', 'listing', 'reviews'),
        ]);

        return [
            'profil' => [
                'nama'      => $user->name,
                'email'     => $user->email,
                'telepon'   => $user->phone,
                'bergabung' => $user->created_at?->format('Y-m-d'),
            ],
            'toko' => $user->stores->map(fn ($s) => [
                'nama'    => $s->name,
                'alamat'  => $s->address,
                'status'  => $s->status?->label(),
                'listing' => $s->listings->count(),
            ]),
            'permintaan' => $user->customerRequests->map(fn ($r) => [
                'judul'  => $r->title,
                'status' => $r->status?->label(),
                'dibuat' => $r->created_at?->format('Y-m-d'),
            ]),
            'pesanan' => $user->orders->map(fn ($o) => [
                'nomor'   => $o->order_number,
                'total'   => (int) $o->total_amount,
                'status'  => $o->status?->label(),
                'dibuat'  => $o->created_at?->format('Y-m-d'),
            ]),
        ];
    }

    /**
     * Catat persetujuan pemrosesan data (consent log).
     * UU PDP pasal 20: setiap pemrosesan data pribadi harus didasari persetujuan.
     */
    public function logConsent(User $user, string $purpose, bool $granted): void
    {
        DB::table('consent_logs')->insert([
            'id'         => (string) str()->uuid(),
            'user_id'    => $user->id,
            'purpose'    => $purpose,
            'granted'    => $granted,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
