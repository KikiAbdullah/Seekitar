<?php

namespace App\Services\Contracts;

use App\Models\CustomerRequest;
use App\Models\Order;
use App\Models\Offer;
use Illuminate\Support\Collection;

/**
 * Satu pintu ke pengiriman notifikasi (FCM).
 *
 * Interface, bukan kelas konkret: integrasi Firebase tidak bisa diuji tanpa
 * kredensial dan jaringan, jadi test memakai implementasi tiruan sementara
 * produksi memakai FCM sungguhan.
 */
interface NotificationSender
{
    /** @param  Collection<int, \App\Models\Store>  $stores */
    public function notifyStoresOfRequest(Collection $stores, CustomerRequest $request): void;

    public function notifyOfferAccepted(Offer $offer, Order $order): void;

    public function notifyOrderStatusChanged(Order $order): void;
}
