<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * Arah ulasan. Ulasan dua arah diwajibkan PRD §5.5, dan HANYA arah
 * buyer_to_store yang memengaruhi stores.rating_avg (DATABASE.md §4.8).
 */
enum ReviewDirection: string
{
    use HasValues;

    case BuyerToStore = 'buyer_to_store';
    case StoreToBuyer = 'store_to_buyer';

    /**
     * Label antarmuka.
     *
     * Enum ini satu-satunya yang sebelumnya tidak punya label(), sehingga
     * panel admin menampilkan nilai mentah `buyer_to_store` — bocornya
     * istilah basis data ke layar pengguna.
     */
    public function label(): string
    {
        return match ($this) {
            self::BuyerToStore => 'Pembeli → Toko',
            self::StoreToBuyer => 'Toko → Pembeli',
        };
    }

    public function affectsStoreRating(): bool
    {
        return $this === self::BuyerToStore;
    }
}
