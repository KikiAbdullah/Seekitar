<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ListingType: string
{
    use HasValues;

    case Product = 'product';
    case Service = 'service';
    case Rental  = 'rental';

    public function label(): string
    {
        return match ($this) {
            self::Product => 'Barang',
            self::Service => 'Jasa',
            self::Rental  => 'Sewa',
        };
    }

    /** Product & rental wajib punya stok; service memakai slot. */
    public function requiresStock(): bool
    {
        return $this !== self::Service;
    }

    public function requiresPrice(): bool
    {
        return $this !== self::Service;
    }
}
