<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/** Status verifikasi toko — KATEGORI (setara), bukan tingkatan. */
enum VerificationStatus: string
{
    use HasValues;

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
