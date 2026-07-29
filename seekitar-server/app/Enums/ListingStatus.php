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

    /**
     * Warna lencana Bootstrap-Subtle untuk panel admin.
     *
     * Satu sumber kebenaran: sebelum ini tiap Blade menulis if-else warna
     * sendiri, dan ketika status baru lahir cabangnya jatuh ke "else
     * abu-abu" secara diam-diam. Di enum, status baru tanpa warna
     * adalah error kompilasi yang langsung terlihat.
     */
    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Sold   => 'info',
            self::Hidden => 'secondary',
        };
    }

    public function isVisible(): bool
    {
        return $this === self::Active;
    }
}
