<?php

namespace App\Console\Commands;

use App\Enums\RequestStatus;
use App\Models\CustomerRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PurgeExpiredRequests extends Command
{
    protected $signature = 'requests:purge-expired';
    protected $description = 'Mark expired requests';

    public function handle(): int
    {
        $count = CustomerRequest::query()
            ->where('expires_at', '<', now())
            ->where('status', RequestStatus::Open)
            ->update(['status' => RequestStatus::Expired]);

        Log::info("Marked {$count} requests as expired.");
        $this->info("Expired {$count} requests.");

        return self::SUCCESS;
    }
}
