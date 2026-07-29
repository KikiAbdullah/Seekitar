<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * Kedudukan akun pengguna (DATABASE.md §4.1) — SATU kata per keadaan,
 * saling eksklusif, disimpan di kolom users.status.
 *
 * Transisinya tidak bebas kemana-mana (lihat komentar migrasi users):
 *   menunggu ──(admin setuju)──▶ terverifikasi
 *   menunggu ──(admin tolak)───▶ ditolak ──(kirim berkas ulang)──▶ menunggu
 *   terverifikasi ──(ganti berkas sendiri lewat API)──▶ menunggu
 *   {menunggu|terverifikasi|ditolak} ──(admin blokir)──▶ diblokir
 *   diblokir ──(blokir dicabut)──▶ terverifikasi bila stempel verified_at
 *                                  masih ada, kalau tidak kembali menunggu.
 *
 * status() di User adalah SUMBER KEBENARAN tampilan; jangan menurunkan
 * kedudukan dari stempel-stempelnya sendiri di tempat lain.
 */
enum UserStatus: string
{
    use HasValues;

    case Menunggu      = 'menunggu';
    case Terverifikasi = 'terverifikasi';
    case Ditolak       = 'ditolak';
    case Diblokir      = 'diblokir';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu      => 'Menunggu Verifikasi',
            self::Terverifikasi => 'Terverifikasi',
            self::Ditolak       => 'Ditolak',
            self::Diblokir      => 'Diblokir',
        };
    }

    /**
     * Warna lencana Bootstrap (subtle) — dipakai seluruh panel admin.
     * Kuning diberi teks gelap bawaan *-subtle: latar terang + teks
     * kuning polos tidak terbaca (pola yang sama dengan OrderStatus).
     */
    public function color(): string
    {
        return match ($this) {
            self::Menunggu      => 'warning',
            self::Terverifikasi => 'success',
            self::Ditolak       => 'danger',
            self::Diblokir      => 'dark',
        };
    }
}
