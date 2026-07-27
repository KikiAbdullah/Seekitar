<?php

namespace App\Services\WhatsApp;

use App\Services\Contracts\WhatsAppGateway;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Log;

/**
 * Gateway untuk pengembangan: OTP ditulis ke log, tidak dikirim ke mana pun.
 *
 * Tanpa ini, setiap pengembang butuh kredensial provider berbayar hanya untuk
 * bisa login di mesin sendiri.
 *
 * Nomor tujuan disensor karena log sering ikut terkirim ke agregator pihak
 * ketiga, sedangkan nomor telepon adalah data pribadi (UU PDP). Kodenya
 * sendiri memang ditulis utuh — itulah gunanya gateway ini — dan karena itu
 * ia TIDAK BOLEH dipakai di produksi. `AppServiceProvider` yang menjaganya.
 */
class LogWhatsAppGateway implements WhatsAppGateway
{
    public function sendOtp(string $phone, string $code): void
    {
        Log::channel(config('logging.default'))->info('OTP (dev)', [
            'phone' => PhoneNumber::mask($phone),
            'code'  => $code,
        ]);
    }
}
