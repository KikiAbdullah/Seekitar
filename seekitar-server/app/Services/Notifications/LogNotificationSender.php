<?php

namespace App\Services\Notifications;

use App\Models\CustomerRequest;
use App\Models\Offer;
use App\Models\Order;
use App\Services\Contracts\NotificationSender;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Implementasi pengembangan: notifikasi ditulis ke log.
 *
 * Integrasi FCM sungguhan membutuhkan kredensial Firebase dan perangkat
 * nyata untuk diverifikasi. Menulis kelas yang tidak bisa diuji sama sekali
 * lebih berisiko daripada menyediakan implementasi jujur ini lebih dulu —
 * kontraknya sudah tetap, jadi penggantinya tinggal di-bind.
 */
class LogNotificationSender implements NotificationSender
{
    public function notifyStoresOfRequest(Collection $stores, CustomerRequest $request): void
    {
        Log::info('Siaran permintaan', [
            'request_id' => $request->id,
            'title'      => $request->title,
            'recipients' => $stores->pluck('id')->all(),
        ]);
    }

    public function notifyOfferAccepted(Offer $offer, Order $order): void
    {
        Log::info('Penawaran diterima', [
            'offer_id' => $offer->id,
            'order_id' => $order->id,
            'store_id' => $offer->store_id,
        ]);
    }

    public function notifyOrderStatusChanged(Order $order): void
    {
        Log::info('Status pesanan berubah', [
            'order_id' => $order->id,
            'status'   => $order->status?->value,
        ]);
    }
}
