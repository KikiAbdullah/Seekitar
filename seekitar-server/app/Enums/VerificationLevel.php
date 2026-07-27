<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * Level verifikasi pengguna (DATABASE.md §4.1). Hanya 1-3.
 * TIDAK ADA level 0 dan tidak ada level 4 di MVP.
 */
enum VerificationLevel: int
{
    use HasValues;

    case Basic    = 1;  // Nomor HP terverifikasi
    case Verified = 2;  // KTP diverifikasi — syarat membuka toko
    case Pro      = 3;  // Usaha tervalidasi, prioritas broadcast lebih tinggi

    public function label(): string
    {
        return match ($this) {
            self::Basic    => 'Nomor Terverifikasi',
            self::Verified => 'Identitas Terverifikasi',
            self::Pro      => 'Usaha Terverifikasi',
        };
    }

    public function canOpenStore(): bool
    {
        return $this->value >= self::Verified->value;
    }
}
