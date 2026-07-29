<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * Kedudukan toko (status, BUKAN verification_status lagi) — empat
 * keadaan saling eksklusif, selaras dengan kedudukan pengguna.
 *
 * Transisinya dijaga VerifikasiTokoService (tulis-sekali satu arah):
 *   pending ──(admin setuju)──▶ verified
 *   pending ──(admin tolak)───▶ rejected ──(pemilik ajukan ulang)──▶ pending
 *   {pending|verified|rejected} ──(pemilik diblokir)──▶ blocked
 *   blocked ──(blokir pemilik dicabut)──▶ keadaan sebelum blokir
 *     (verified_at terisi → verified; rejected_at terisi → rejected;
 *      selain itu → pending).
 *
 * "Blocked" pada toko SELALU berarti "pemiliknya bermasalah" — toko tidak
 * diblokir sendirian; jejaknya di triplet blocked_*.
 */
enum StoreStatus: string
{
    use HasValues;

    case Pending  = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Blocked  = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Menunggu Peninjauan',
            self::Verified => 'Terverifikasi',
            self::Rejected => 'Ditolak',
            self::Blocked  => 'Diblokir',
        };
    }

    /**
     * Warna lencana Bootstrap (subtle) — palet yang sama dengan UserStatus
     * supaya admin membaca kedudukan dengan bahasa visual yang konsisten.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending  => 'warning',
            self::Verified => 'success',
            self::Rejected => 'danger',
            self::Blocked  => 'dark',
        };
    }
}
