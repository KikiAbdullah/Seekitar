<?php

namespace App\Support;

/*
 * Placeholder foto untuk spot yang seharusnya menampilkan gambar tetapi
 * belum punya unggahan (umumnya data dummy selama pengembangan).
 *
 * Dua keputusan sengaja:
 *
 * 1. Memakai URL BERSEED (picsum.photos/seed/{seed}/{w}/{h}), bukan URL
 *    polos. Versi polos memilih foto acak di SETIAP kali dimuat — satu
 *    baris tabel yang "tanpa foto" berganti-ganti gambarnya setiap refresh,
 *    dan tidak ada yang bisa di-cache peramban. Seed (di sini id record)
 *    menguncinya: entitas yang sama selalu mendapat foto yang sama.
 *
 * 2. HANYA untuk foto tampilan (avatar, toko, listing, bukti bayar).
 *    Dokumen verifikasi (KTP/selfie) sengaja tidak diberi placeholder —
 *    foto acak di sana adalah bukti identitas palsu di mata admin, jadi
 *    bagian itu tetap menampilkan status "Belum diunggah" yang jujur.
 */
final class PlaceholderImg
{
    public static function url(string $seed, int $width, int $height): string
    {
        return sprintf(
            'https://picsum.photos/seed/%s/%d/%d',
            rawurlencode($seed),
            $width,
            $height,
        );
    }
}
