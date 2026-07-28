<?php

namespace App\Jobs;

use App\Enums\ReviewDirection;
use App\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Hitung ulang rating toko dari seluruh ulasannya.
 *
 * ReviewObserver sudah memperbarui rating secara inkremental; job ini untuk
 * perbaikan menyeluruh — mis. setelah admin menghapus ulasan massal, atau
 * saat angka terlanjur melenceng.
 */
class RecalculateStoreRatingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly string $storeId) {}

    public function handle(): void
    {
        $stats = DB::table('reviews')
            ->selectRaw('COUNT(*) AS total, COALESCE(AVG(rating), 0) AS average')
            ->where('store_id', $this->storeId)
            // HANYA arah buyer_to_store; penilaian penjual terhadap pembeli
            // tidak boleh mengerek rating toko (DATABASE.md §4.8).
            ->where('direction', ReviewDirection::BuyerToStore->value)
            ->first();

        Store::whereKey($this->storeId)->update([
            'rating_avg'    => round((float) $stats->average, 2),
            'total_reviews' => (int) $stats->total,
        ]);
    }
}
