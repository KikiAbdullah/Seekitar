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

    /** Warna lencana panel admin — dipasangkan dengan icon() di bawah. */
    public function color(): string
    {
        return match ($this) {
            self::Product => 'primary',
            self::Service => 'warning',
            self::Rental  => 'info',
        };
    }

    /** Ikon Font Awesome yang bermakna sekilas: paket, perkakas, kalender sewa. */
    public function icon(): string
    {
        return match ($this) {
            self::Product => 'fa-regular fa-clipboard',
            self::Service => 'fa-regular fa-lightbulb',
            self::Rental  => 'fa-regular fa-calendar-days',
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
