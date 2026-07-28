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
     */
    public function create(User $user): bool
    {
        return $user->canOpenStore();
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
