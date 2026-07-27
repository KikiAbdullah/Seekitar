<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Provider WhatsApp menolak atau gagal mengirim OTP.
 *
 * Dibedakan dari kegagalan validasi biasa supaya bisa ditangani berbeda:
 * pengguna perlu diberi tahu "gagal mengirim, coba lagi" — bukan "nomor
 * salah" — dan kejadiannya wajib masuk pemantauan karena berarti pengguna
 * tidak bisa masuk sama sekali.
 */
class OtpDeliveryException extends RuntimeException
{
    public static function fromProvider(string $provider, string $detail): self
    {
        // Detail dari provider sengaja tidak diteruskan ke pengguna akhir;
        // isinya bisa memuat token atau nomor tujuan.
        return new self("Gagal mengirim OTP lewat {$provider}: {$detail}");
    }
}
