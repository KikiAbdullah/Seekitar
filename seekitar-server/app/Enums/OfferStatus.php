<?php

namespace App\Enums;

/**
 * Status penawaran. Tidak ada nilai 'expired': penawaran lewat waktu
 * ditandai 'rejected' agar ENUM tetap ringkas (DATABASE.md §4.6).
 */
enum OfferStatus: string
{
    case Pending  = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Menunggu',
            self::Accepted => 'Diterima',
            self::Rejected => 'Ditolak',
        };
    }
}
