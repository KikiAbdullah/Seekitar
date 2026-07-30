<?php

namespace App\Support;

/*
 * Placeholder foto untuk spot yang seharusnya menampilkan gambar tetapi
 * belum punya unggahan (umumnya data dummy selama pengembangan).
 *
 * Dua keputusan sengaja:
 *
 * 1. Memakai placehold.co DENGAN SEED, bukan URL polos tanpa identitas.
 *    Seed (id record) dipetakan secara deterministik ke satu dari beberapa
 *    pasangan warna palet, jadi sifat yang dulu dijaga foto berseed tetap
 *    berlaku: entitas yang sama SELALU mendapat warna yang sama, antrian
 *    berisi kartu yang tidak seragam, dan peramban bisa meng-cache URL-nya.
 *
 *    placehold.co diganti kepadanannya karena ia statis & cepat — bukan
 *    galeri foto acak — sehingga tabel tidak "bernapas" setiap refresh.
 *
 * 2. HANYA untuk foto tampilan (avatar, toko, listing, bukti bayar).
 *    Dokumen verifikasi (KTP/selfie) sengaja tidak diberi placeholder —
 *    foto acak di sana adalah bukti identitas palsu di mata admin, jadi
 *    bagian itu tetap menampilkan status "Belum diunggah" yang jujur.
 */
final class PlaceholderImg
{
    /**
     * Pasangan [latar, teks] — dipilih dari seed. Empat nada hijau brand
     * untuk foto tampilan, satu abu netral untuk dokumen seperti bukti bayar.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const PALET = [
        ['E7F6EC', '168A4A'],
        ['DCFCE7', '166534'],
        ['F0FDF4', '15803D'],
        ['16A34A', 'FFFFFF'],
        ['F3F4F6', '6B7280'],
    ];

    public static function url(string $seed, int $width, int $height, ?string $text = null): string
    {
        [$bg, $fg] = self::PALET[abs(crc32($seed)) % count(self::PALET)];

        return sprintf(
            'https://placehold.co/%dx%d/%s/%s?text=%s',
            $width,
            $height,
            $bg,
            $fg,
            rawurlencode($text ?? 'Seekitar'),
        );
    }
}
