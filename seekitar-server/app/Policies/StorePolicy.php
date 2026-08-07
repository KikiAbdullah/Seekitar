<?php

namespace App\Policies;

use App\Enums\VerificationLevel;
use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    /**
     * Membuka toko butuh verifikasi KTP (Level 2) — PRD §5.3.2.
     * Tanpa syarat ini, akun anonim bisa berjualan tanpa jejak identitas.
     *
     * PLUS aturan SATU toko per pengguna: toko yang sudah pernah dibuat
     * (status apa pun — pending/ditolak/diblokir tetap miliknya) menghalangi
     * pembuatan toko baru.
     */
    public function create(User $user): bool
    {
        // Pemilik toko harus SUDAH terverifikasi (canOpenStore = stempel KTP
        // tahap 2 terisi). Penting: syarat ini diperiksa ULANG di titik
        // persetujuan admin (VerificationController::approveStore), karena
        // level bisa turun setelah pengajuan masuk antrian.
        if (! $user->canOpenStore()) {
            return false;
        }

        // 1 pengguna = 1 toko (termasuk toko yang masih menunggu/ditolak).
        return ! Store::where('user_id', $user->id)->exists();
    }

    public function update(User $user, Store $store): bool
    {
        return $store->user_id === $user->id;
    }

    public function delete(User $user, Store $store): bool
    {
        return $store->user_id === $user->id;
    }

    /** Menonaktifkan toko bermasalah adalah wewenang admin. */
    public function deactivate(User $user, Store $store): bool
    {
        return $user->can('manage-stores');
    }
}
