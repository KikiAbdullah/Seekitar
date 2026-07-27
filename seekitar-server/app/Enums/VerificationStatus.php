<?php

namespace App\Enums;

/** Status verifikasi toko — KATEGORI (setara), bukan tingkatan. */
enum VerificationStatus: string
{
    case Pending  = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Menunggu Peninjauan',
            self::Verified => 'Terverifikasi',
            self::Rejected => 'Ditolak',
        };
    }
}
