<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * Status penawaran. Tidak ada nilai 'expired': penawaran lewat waktu
 * ditandai 'rejected' agar ENUM tetap ringkas (DATABASE.md §4.6).
 */
enum OfferStatus: string
{
    use HasValues;

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
