<?php

namespace App\Services\WhatsApp;

use App\Exceptions\OtpDeliveryException;
use App\Services\Contracts\WhatsAppGateway;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Fallback lokal: OTP tetap diminta dengan nomor telepon, tetapi dikirim ke
 * inbox email pengembang/tester agar alur mobile tidak perlu dibongkar.
 */
class EmailOtpGateway implements WhatsAppGateway
{
    public function sendOtp(string $phone, string $code): void
    {
        $to = (string) config('services.otp_mail.to');

        if (blank($to)) {
            throw OtpDeliveryException::fromProvider('Mail OTP', 'Konfigurasi services.otp_mail.to kosong.');
        }

        try {
            Mail::raw($this->message($phone, $code), function ($message) use ($to, $phone): void {
                $message
                    ->to($to)
                    ->subject("OTP Seekitar untuk {$phone}");
            });
        } catch (Throwable $e) {
            throw OtpDeliveryException::fromProvider('Mail OTP', $e->getMessage(), $e);
        }
    }

    private function message(string $phone, string $code): string
    {
        $minutes = (int) (\App\Services\OtpService::TTL_SECONDS / 60);

        return implode("\n", [
            'Kode OTP Seekitar (fallback email lokal)',
            '',
            "Nomor tujuan: {$phone}",
            "Kode OTP: {$code}",
            "Berlaku: {$minutes} menit",
            '',
            'Email ini dikirim karena gateway WhatsApp belum dipakai di environment ini.',
        ]);
    }
}
