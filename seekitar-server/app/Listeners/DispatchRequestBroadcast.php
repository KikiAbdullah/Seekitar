<?php

namespace App\Listeners;

use App\Events\CustomerRequestCreated;
use App\Jobs\BroadcastRequestJob;

/**
 * Melempar penyiaran ke antrian.
 *
 * Listener ini sengaja TIDAK ShouldQueue: pekerjaan beratnya sudah ada di
 * dalam job. Mengantrikan listener yang hanya memanggil dispatch() berarti
 * dua kali antrian untuk satu pekerjaan.
 */
class DispatchRequestBroadcast
{
    public function handle(CustomerRequestCreated $event): void
    {
        BroadcastRequestJob::dispatch($event->request->id);
    }
}
