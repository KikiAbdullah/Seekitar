<?php

namespace App\Console\Commands;

use App\Enums\OfferStatus;
use App\Models\Offer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PurgeExpiredOffers extends Command
{
    protected $signature = 'offers:purge-expired';
    protected $description = 'Reject expired offers';

    public function handle(): int
    {
        $count = Offer::query()
            ->where('expires_at', '<', now())
            ->where('status', OfferStatus::Pending)
            ->update(['status' => OfferStatus::Rejected]);

        Log::info("Purged {$count} expired offers.");
        $this->info("Rejected {$count} expired offers.");

        return self::SUCCESS;
    }
}
