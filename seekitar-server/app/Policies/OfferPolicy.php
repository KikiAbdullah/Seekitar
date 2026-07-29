<?php

namespace App\Policies;

use App\Enums\OfferStatus;
use App\Enums\StoreStatus;
use App\Models\CustomerRequest;
use App\Models\Offer;
use App\Models\Store;
use App\Models\User;

class OfferPolicy
{
    /**
     * Penyedia boleh menawar bila: tokonya sendiri, toko terverifikasi,
     * permintaan masih terbuka, dan BUKAN permintaannya sendiri.
     *
     * Syarat terakhir mencegah penawaran palsu untuk mengerek reputasi.
     */
    public function createFor(User $user, Store $store, CustomerRequest $request): bool
    {
        // isOpen() sudah mencakup status DAN kedaluwarsa sekaligus.
        return $store->user_id === $user->id
            && $store->status === StoreStatus::Verified
            && $request->isOpen()
            && $request->user_id !== $user->id;
    }

    public function view(User $user, Offer $offer): bool
    {
        return $offer->store?->user_id === $user->id
            || $offer->request?->user_id === $user->id;
    }

    /**
     * Hanya pemilik permintaan yang boleh menerima penawaran, dan hanya
     * sekali: setelah satu diterima, permintaan tertutup.
     */
    public function accept(User $user, Offer $offer): bool
    {
        return $offer->request?->user_id === $user->id
            && $offer->status === OfferStatus::Pending
            && (bool) $offer->request?->isOpen();
    }
}
