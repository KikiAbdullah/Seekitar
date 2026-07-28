<?php

namespace App\Support;

/**
 * Format angka Indonesia.
 *
 * KENAPA ADA
 * ----------
 * `number_format($n)` tanpa argumen memakai pemisah Inggris — `4,812` berarti
 * "empat koma delapan satu dua" bagi pembaca Indonesia, bukan "empat ribu
 * delapan ratus dua belas". Kesalahan ini tidak menimbulkan error dan lolos
 * semua pemeriksaan sintaks; ia hanya salah dibaca.
 *
 * Sebelum kelas ini ada, tiap pemanggilan mengulang `number_format($n, 0, ',', '.')`
 * — dan satu tempat yang lupa menuliskannya langsung menghasilkan format yang
 * berbeda dari sekitarnya. Ditegakkan oleh tools/dev/check-admin-menu.mjs.
 */
final class Angka
{
    /** Bilangan bulat: 4812 → "4.812". */
    public static function bulat(int|float|string|null $nilai): string
    {
        return number_format((float) $nilai, 0, ',', '.');
    }

    /**
     * Rupiah tanpa desimal: 15000 → "Rp 15.000".
     *
     * Tanpa sen, sesuai API_DOCUMENTATION.md §12.3 — harga di Seekitar selalu
     * bulat, dan menampilkan ",00" hanya menambah lebar kolom tabel.
     */
    public static function rupiah(int|float|string|null $nilai): string
    {
        return 'Rp '.self::bulat($nilai);
    }
}
