<?php

namespace App\Support;

/**
 * Normalisasi nomor telepon Indonesia ke bentuk `62xxxxxxxxxx`.
 *
 * KENAPA SATU KELAS, BUKAN METHOD DI TIAP FormRequest
 * ---------------------------------------------------
 * `Server_Implementation_Guide.md` §18A.6 mewajibkan normalisasi terjadi di
 * SATU tempat. Tanpa itu, satu orang bisa membuat beberapa akun dari nomor
 * yang sama — `08123456789`, `+628123456789`, dan `628123456789` semuanya
 * lolos `UNIQUE(users.phone)` karena stringnya memang berbeda.
 *
 * Menyalin method privat ke tiap FormRequest berarti jalur masuk berikutnya
 * (job, command artisan, seeder, webhook) pasti ada yang lupa.
 */
final class PhoneNumber
{
    /** Panjang kolom `users.phone` (DATABASE.md §4.1). */
    public const MAX_LENGTH = 15;

    public static function normalize(?string $input): ?string
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $input) ?? '';

        if ($digits === '') {
            return null;
        }

        return match (true) {
            str_starts_with($digits, '0')  => '62'.substr($digits, 1),
            str_starts_with($digits, '62') => $digits,
            default                        => '62'.$digits,
        };
    }

    /**
     * Bentuk ramah-baca untuk UI & pesan WhatsApp: `+62 812-3456-789`.
     * Tidak pernah dipakai untuk menyimpan atau mencari.
     */
    public static function forDisplay(?string $stored): ?string
    {
        $normalized = self::normalize($stored);

        if ($normalized === null) {
            return null;
        }

        $national = substr($normalized, 2);
        $chunks   = [substr($national, 0, 3), substr($national, 3, 4), substr($national, 7)];

        return '+62 '.rtrim(implode('-', array_filter($chunks, static fn ($c) => $c !== '')), '-');
    }

    /**
     * Sensor untuk log & tampilan publik: `+62 812-****-789`.
     * Nomor telepon adalah data pribadi (UU PDP) — jangan bocor di log.
     */
    public static function mask(?string $stored): ?string
    {
        $normalized = self::normalize($stored);

        if ($normalized === null) {
            return null;
        }

        $national = substr($normalized, 2);

        if (strlen($national) <= 5) {
            return '+62 '.str_repeat('*', strlen($national));
        }

        return '+62 '.substr($national, 0, 3).'-****-'.substr($national, -3);
    }
}
