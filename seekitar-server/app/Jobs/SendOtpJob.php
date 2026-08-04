<?php

namespace App\Jobs;

use App\Exceptions\OtpDeliveryException;
use App\Services\Contracts\WhatsAppGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Kirim OTP lewat gateway WhatsApp DI BELAKANG LAYAR (queue).
 *
 * KENAPA DI-QUEUE, BUKAN LANGSUNG DI REQUEST
 * -------------------------------------------
 * Request `POST /auth/request-otp` harus membalas secepat mungkin —
 * pengguna tidak perlu menunggu WhatsApp (yang bisa lambat/gagal). Dengan
 * queue, controller cukup mendispatch job lalu mengembalikan respons;
 * worker (`php artisan queue:work redis --queue=high,default`) yang
 * mengirim. Kalau gateway sedang bermasalah, job di-retry dengan backoff
 * dan akhirnya masuk `failed_jobs` — jauh lebih andal daripada mengirim
 * sinkron di dalam request.
 */
class SendOtpJob implements ShouldQueue
{
    use Queueable;

    /** Coba ulang dengan jeda; total maksimal 3 percobaan. */
    public int $tries = 3;

    /** @var array<int, int> jeda antar percobaan (detik). */
    public array $backoff = [2, 10];

    public function __construct(
        public readonly string $phone,
        public readonly string $code,
    ) {
        // Prioritas: OTP masuk antrean `high` (Server_Implementation_Guide §14).
        $this->onQueue('high');
    }

    /** @throws OtpDeliveryException — diteruskan agar retry & failed_jobs bekerja. */
    public function handle(WhatsAppGateway $whatsapp): void
    {
        try {
            $whatsapp->sendOtp($this->phone, $this->code);
        } catch (OtpDeliveryException $e) {
            Log::channel('security')->warning('OTP gagal dikirim via queue', [
                'phone'  => \App\Support\PhoneNumber::mask($this->phone),
                'error'  => $e->getMessage(),
            ]);

            throw $e; // biarkan retry/backoff bekerja
        }
    }
}
