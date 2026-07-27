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

    public function affectsStoreRating(): bool
    {
        return $this === self::BuyerToStore;
    }
}
