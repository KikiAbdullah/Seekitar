<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum DisputeStatus: string
{
    use HasValues;

    case Open     = 'open';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open     => 'Terbuka',
            self::Resolved => 'Selesai',
        };
    }
}
