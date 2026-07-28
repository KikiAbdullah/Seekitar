<?php

namespace App\Observers;

use App\Enums\ReviewDirection;
use App\Models\Review;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Menjaga rating_avg & total_reviews tetap akurat — milik TOKO maupun PEMBELI.
 *
 * Dua arah ulasan, dua reputasi berbeda:
 *   buyer_to_store → stores.rating_avg    (nilai tokonya)
 *   store_to_buyer → users.rating_avg     (nilai pembelinya, reviewee)
 *
 * Keduanya dihitung dari COUNT/AVG penuh, bukan penambahan inkremental:
 * satu ulasan yang terhapus atau tertulis dua kali tidak membuat selisih
 * permanen — angka selalu lahir kembali dari sumbernya.
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

    private function recalculate(Review $review): void
    {
        /*
         * Rating TOKO — HANYA dari arah buyer_to_store.
         * Tanpa filter ini, penilaian penjual terhadap pembeli ikut terhitung
         * sebagai rating toko — dan penjual bisa mengerek nilainya sendiri
         * (DATABASE.md §4.8).
         */
        if ($review->direction === ReviewDirection::BuyerToStore && $review->store_id !== null) {
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

        /*
         * Rating PEMBELI — HANYA dari arah store_to_buyer, ditujukan ke
         * reviewee (pembelinya). CHECK reviews_store_direction_chk menjamin
         * store_id NULL pada arah ini, jadi tidak mungkin tercampur.
         */
        if ($review->direction === ReviewDirection::StoreToBuyer) {
            $stats = DB::table('reviews')
                ->selectRaw('COUNT(*) AS total, COALESCE(AVG(rating), 0) AS average')
                ->where('reviewee_id', $review->reviewee_id)
                ->where('direction', ReviewDirection::StoreToBuyer->value)
                ->first();

            User::whereKey($review->reviewee_id)->update([
                'rating_avg'    => round((float) $stats->average, 2),
                'total_reviews' => (int) $stats->total,
            ]);
        }
    }
}
