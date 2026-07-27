<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * Tipe toko — bentuk JAMAK karena kolomnya SET (menampung kombinasi):
 * satu toko bisa 'goods,services'. Berbeda dari ListingType yang tunggal.
 * Lihat DATABASE.md §4.2.
 */
enum StoreType: string
{
    use HasValues;

    case Goods    = 'goods';
    case Services = 'services';
    case Rental   = 'rental';

    public function label(): string
    {
        return match ($this) {
            self::Goods    => 'Barang',
            self::Services => 'Jasa',
            self::Rental   => 'Sewa',
        };
    }

    /** Listing tipe apa saja yang boleh dipasang toko dengan tipe ini. */
    public function allowsListingType(ListingType $type): bool
    {
        return match ($this) {
            self::Goods    => $type === ListingType::Product,
            self::Services => $type === ListingType::Service,
            self::Rental   => $type === ListingType::Rental,
        };
    }
}
