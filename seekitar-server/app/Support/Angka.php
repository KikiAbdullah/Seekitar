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

    /**
     * Bilangan berkoma dengan presisi tetap: -7.2508 → "-7,250800".
     *
     * Dipakai untuk koordinat & radius di tampilan. Bagian DESIMALnya tidak
     * boleh jatuh ke titik Inggris: "-7.250800" dibaca orang Indonesia sebagai
     * tujuh ribu sekian, padahal yang dimaksud minus tujuh koma dua lima.
     * Angka yang dioper ke JavaScript (peta, tautan Maps) TIDAK lewat sini —
     * ia butuh titik mentahnya; pemanggil memakai $model->latitude apa adanya.
     */
    public static function desimal(int|float|string|null $nilai, int $presisi): string
    {
        return number_format((float) $nilai, $presisi, ',', '.');
    }
}
