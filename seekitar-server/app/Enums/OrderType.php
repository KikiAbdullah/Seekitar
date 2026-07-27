<?php

namespace App\Enums;

/**
 * Tipe pesanan. Nilainya IDENTIK dengan ListingType karena disalin
 * langsung saat pesanan dibuat dari listing (DATABASE.md §4.7).
 */
enum OrderType: string
{
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

    public static function fromListingType(ListingType $type): self
    {
        // Aman karena kedua enum memakai nilai yang sama persis.
        return self::from($type->value);
    }
}
