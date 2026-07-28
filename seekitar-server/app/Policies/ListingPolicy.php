<?php

namespace App\Policies;

use App\Enums\VerificationStatus;
use App\Models\Listing;
use App\Models\Store;
use App\Models\User;

class ListingPolicy
{
    /**
     * Hanya pemilik toko TERVERIFIKASI yang boleh memasang listing.
     * Toko yang masih `pending` belum boleh muncul di katalog.
     */
    public function createFor(User $user, Store $store): bool
    {
        return $store->user_id === $user->id
            && $store->verification_status === VerificationStatus::Verified;
    }

    public function update(User $user, Listing $listing): bool
    {
        return $listing->store?->user_id === $user->id;
    }

    /** Admin boleh menghapus konten bermasalah. */
    public function delete(User $user, Listing $listing): bool
    {
        return $listing->store?->user_id === $user->id
            || $user->can('manage-listings');
    }
}
