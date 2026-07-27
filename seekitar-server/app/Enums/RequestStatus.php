<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Open    = 'open';
    case Closed  = 'closed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Open    => 'Terbuka',
            self::Closed  => 'Selesai',
            self::Expired => 'Kedaluwarsa',
        };
    }

    public function acceptsOffers(): bool
    {
        return $this === self::Open;
    }
}
