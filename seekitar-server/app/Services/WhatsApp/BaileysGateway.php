<?php

namespace App\Services\WhatsApp;

use App\Exceptions\OtpDeliveryException;
use App\Services\Contracts\WhatsAppGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Gateway WhatsApp Web lokal memakai Baileys (Node.js sidecar).
 *
 * Service Node-nya hidup di `seekitar-server/whatsapp-gateway` dan diekspos
 * lewat HTTP kecil:
 *
 *   GET  /api/status  -> online/offline + nomor tersambung
 *   GET  /api/qr      -> QR code (data URL PNG) untuk scan di panel admin
 *   POST /api/logout  -> cabut sesi
 *   POST /api/send    -> kirim pesan teks (dipakai OTP)
 *
 * Admin panel memakai status/qr/logout lewat `Admin\WhatsAppController`;
 * pengiriman OTP lewat `sendOtp()` di bawah. Template pesan identik dengan
 * KirimWaGateway (Server_Implementation_Guide.md §15.2).
 *
 * ⚠️ Baileys bukan API resmi WhatsApp — lihat catatan di service Node.
 */
class BaileysGateway implements WhatsAppGateway
{
    private const TIMEOUT_SECONDS = 10;
    private const RETRY_TIMES = 2;
    private const RETRY_DELAY_MS = 500;

    private function timeout(): int
    {
        return (int) config('whatsapp.baileys.timeout', self::TIMEOUT_SECONDS);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('whatsapp.baileys.url', 'http://127.0.0.1:3001'), '/');
    }

    private function token(): string
    {
        return (string) config('whatsapp.baileys.token', '');
    }

    /** Header otorisasi; token kosong = tanpa auth (dev saja). */
    private function headers(): array
    {
        $token = $this->token();

        return $token === '' ? [] : ['Authorization' => 'Bearer '.$token];
    }

    /** @return array{success: bool, data?: array, message?: string} */
    private function call(string $method, string $path, array $body = []): array
    {
        try {
            $request = Http::withHeaders($this->headers())
                ->timeout($this->timeout())
                ->retry(self::RETRY_TIMES, self::RETRY_DELAY_MS, throw: false);

            $response = match (strtoupper($method)) {
                'POST'   => $request->post($this->baseUrl().$path, $body),
                default  => $request->get($this->baseUrl().$path),
            };
        } catch (ConnectionException $e) {
            // cURL 28 = timeout. Service Node ada tapi tidak membalas —
            // biasanya koneksi WhatsApp mati diam-diam / sendMessage menggantung.
            if (str_contains($e->getMessage(), '28') || str_contains(strtolower($e->getMessage()), 'timed out')) {
                throw OtpDeliveryException::fromProvider(
                    'Baileys',
                    'Gateway WhatsApp tidak merespons (timeout). Pastikan service Node berjalan (npm start) dan koneksi WhatsApp online.'
                );
            }

            throw OtpDeliveryException::fromProvider('Baileys', $e->getMessage());
        }

        return $this->decode($response);
    }

    private function decode(Response $response): array
    {
        $json = $response->json() ?? [];

        if ($response->failed()) {
            $message = is_array($json) ? ($json['message'] ?? $response->body()) : $response->body();

            throw OtpDeliveryException::fromProvider('Baileys', (string) $message);
        }

        return $json;
    }

    /**
     * Kirim OTP — kontrak WhatsAppGateway.
     *
     * Jalur cepat (SOCKET): bila `whatsapp.baileys.redis_url` diisi, pesan
     * di-publish ke channel Redis yang disubscribe gateway Node. Redis
     * pub/sub memakai koneksi socket PERSISTEN — tanpa handshake HTTP per
     * pesan — sehingga OTP terkirim dalam hitungan milidetik dari worker.
     * Fire-and-forget: gateway yang membalas lewat channel `result`.
     *
     * Fallback: HTTP POST /api/send (handshake per panggilan, lebih lambat
     * sedikit tapi tetap andal — dipakai bila Redis tidak dikonfigurasi).
     */
    public function sendOtp(string $phone, string $code): void
    {
        $redisUrl = (string) config('whatsapp.baileys.redis_url', '');

        if ($redisUrl !== '') {
            \Illuminate\Support\Facades\Redis::publish(
                (string) config('whatsapp.baileys.channel_send', 'seekitar:wa:send'),
                json_encode([
                    'to'   => $phone,
                    'text' => $this->message($code),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            return;
        }

        $this->call('POST', '/api/send', [
            'to'   => $phone,
            'text' => $this->message($code),
        ]);
    }

    /** Kirim pesan bebas (dipakai menu admin untuk uji kirim). */
    public function send(string $phone, string $text): void
    {
        $this->call('POST', '/api/send', ['to' => $phone, 'text' => $text]);
    }

    /** @return array{online: bool, phone: ?string, last_connected_at: ?string} */
    public function status(): array
    {
        $json = $this->call('GET', '/api/status');
        $data = $json['data'] ?? [];

        return [
            'online'            => (bool) ($data['online'] ?? false),
            'phone'             => $data['phone'] ?? null,
            'last_connected_at' => $data['last_connected_at'] ?? null,
        ];
    }

    /** QR code sebagai data URL PNG; null bila tidak tersedia. */
    public function qr(): ?string
    {
        $json = $this->call('GET', '/api/qr');

        return $json['data']['qr'] ?? null;
    }

    public function logout(): void
    {
        $this->call('POST', '/api/logout');
    }

    /**
     * Reset sesi total — hapus kredensial & minta QR BARU seketika.
     * Dipakai dari panel admin saat QR macet / sesi korup.
     */
    public function resetSession(): void
    {
        $this->call('POST', '/api/reset');
    }

    private function message(string $code): string
    {
        $minutes = (int) (\App\Services\OtpService::TTL_SECONDS / 60);

        return "Kode OTP Seekitar Anda: {$code}. Berlaku {$minutes} menit. "
             .'Jangan bagikan kode ini kepada siapa pun.';
    }
}
