<?php

namespace App\Jobs;

use App\Models\CustomerRequest;
use App\Services\BroadcastService;
use App\Services\Contracts\NotificationSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Menyiarkan permintaan baru ke penyedia yang cocok.
 *
 * Dijalankan di ANTRIAN, bukan inline: pencocokan spasial + pengiriman FCM ke
 * puluhan perangkat bisa memakan detik, dan pembeli tidak boleh menunggu
 * selama itu untuk mendapat respons HTTP.
 */
class BroadcastRequestJob implements ShouldQueue
{
    use Queueable;

    /** Percobaan ulang bila provider notifikasi sedang bermasalah. */
    public int $tries = 3;

    /** Jeda menaik: 10 detik, lalu 30, lalu 60. */
    public array $backoff = [10, 30, 60];

    public function __construct(public readonly string $requestId) {}

    public function handle(BroadcastService $broadcast, NotificationSender $notifier): void
    {
        $request = CustomerRequest::withCoordinates()->find($this->requestId);

        // Permintaan bisa saja sudah dibatalkan atau kedaluwarsa sebelum job
        // ini sempat dijalankan — mengirim notifikasi untuknya hanya spam.
        if ($request === null || ! $request->isOpen()) {
            return;
        }

        $stores = $broadcast->matchingStores($request);

        if ($stores->isEmpty()) {
            return;
        }

        $notifier->notifyStoresOfRequest($stores, $request);
    }
}
