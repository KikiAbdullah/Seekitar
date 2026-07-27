<?php

namespace App\Services\WhatsApp;

use App\Exceptions\OtpDeliveryException;
use App\Services\Contracts\WhatsAppGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Klien Kirim WA — provider WhatsApp untuk produksi.
 *
 * Template pesan mengikuti `Server_Implementation_Guide.md` §15.2.
 */
class KirimWaGateway implements WhatsAppGateway
{
    private const TIMEOUT_SECONDS = 10;
    private const RETRY_TIMES = 2;
    private const RETRY_DELAY_MS = 500;

    public function sendOtp(string $phone, string $code): void
    {
        try {
            $response = Http::withToken((string) config('services.kirimwa.token'))
                ->timeout(self::TIMEOUT_SECONDS)
                ->retry(self::RETRY_TIMES, self::RETRY_DELAY_MS, throw: false)
                ->post(rtrim((string) config('services.kirimwa.url'), '/').'/send', [
                    'phone'   => $phone,
                    'message' => $this->message($code),
                ]);
        } catch (ConnectionException $e) {
            // Provider tidak terjangkau sama sekali (DNS, TLS, timeout).
            throw OtpDeliveryException::fromProvider('Kirim WA', $e->getMessage());
        }

        if ($response->failed()) {
            throw OtpDeliveryException::fromProvider('Kirim WA', $response->body());
        }
    }

    /**
     * Isi pesan OTP.
     *
     * Peringatan "jangan bagikan" bukan basa-basi: penipuan paling umum di
     * Indonesia adalah meminta korban membacakan kode yang baru diterimanya.
     */
    private function message(string $code): string
    {
        $minutes = (int) (\App\Services\OtpService::TTL_SECONDS / 60);

        return "Kode OTP Seekitar Anda: {$code}. Berlaku {$minutes} menit. "
             .'Jangan bagikan kode ini kepada siapa pun.';
    }
}
