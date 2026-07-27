<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ListingStatus: string
{
    use HasValues;

    case Active = 'active';
    case Sold   = 'sold';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Sold   => 'Terjual',
            self::Hidden => 'Disembunyikan',
        };
    }

    public function isVisible(): bool
    {
        return $this === self::Active;
    }
}
