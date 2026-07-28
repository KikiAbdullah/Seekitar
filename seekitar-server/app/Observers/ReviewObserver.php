<?php

namespace App\Observers;

use App\Enums\ReviewDirection;
use App\Models\Review;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

/**
 * Menjaga `rating_avg` & `total_reviews` toko tetap akurat.
 */
class ReviewObserver
{
    public function created(Review $review): void
    {
        $this->recalculate($review);
    }

    public function deleted(Review $review): void
    {
        // Ulasan yang dihapus admin memicu perhitungan ulang (API §10.4).
        $this->recalculate($review);
    }

    /**
     * HANYA arah buyer_to_store yang memengaruhi rating toko.
     *
     * Tanpa filter ini, penilaian penjual terhadap pembeli ikut terhitung
     * sebagai rating toko — dan penjual bisa mengerek nilainya sendiri
     * (DATABASE.md §4.8).
     */
    private function recalculate(Review $review): void
    {
        if ($review->direction !== ReviewDirection::BuyerToStore || $review->store_id === null) {
            return;
        }

        $stats = DB::table('reviews')
            ->selectRaw('COUNT(*) AS total, COALESCE(AVG(rating), 0) AS average')
            ->where('store_id', $review->store_id)
            ->where('direction', ReviewDirection::BuyerToStore->value)
            ->first();

        Store::whereKey($review->store_id)->update([
            'rating_avg'    => round((float) $stats->average, 2),
            'total_reviews' => (int) $stats->total,
        ]);
    }
}
