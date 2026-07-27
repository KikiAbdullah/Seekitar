<?php

namespace App\Services\Contracts;

/**
 * Satu pintu ke penyedia WhatsApp.
 *
 * Tidak ada paket resmi Laravel untuk provider lokal (Kirim WA, Wablas), dan
 * providernya kemungkinan berganti saat harga berubah. Interface ini menjaga
 * agar pergantian itu tidak menyentuh kode pemanggil sama sekali —
 * `Server_Implementation_Guide.md` §15.2.
 */
interface WhatsAppGateway
{
    /**
     * @throws \App\Exceptions\OtpDeliveryException bila provider menolak.
     */
    public function sendOtp(string $phone, string $code): void;
}
