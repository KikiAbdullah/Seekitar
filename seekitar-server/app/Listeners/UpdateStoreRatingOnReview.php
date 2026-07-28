<?php

namespace App\Listeners;

use App\Enums\ReviewDirection;
use App\Events\ReviewSubmitted;
use App\Jobs\RecalculateStoreRatingJob;

class UpdateStoreRatingOnReview
{
    public function handle(ReviewSubmitted $event): void
    {
        $review = $event->review;

        // Hanya ulasan pembeli ke toko yang memengaruhi rating.
        if ($review->direction !== ReviewDirection::BuyerToStore || $review->store_id === null) {
            return;
        }

        RecalculateStoreRatingJob::dispatch($review->store_id);
    }
}
