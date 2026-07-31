<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupPendingOrders extends Command
{
    protected $signature = 'orders:cleanup-pending';
    protected $description = 'Cleanup stale pending orders';

    public function handle(): int
    {
        $count = Order::query()
            ->where('status', OrderStatus::MenungguKonfirmasi)
            ->where('created_at', '<', now()->subDays(7))
            ->update(['status' => OrderStatus::Dibatalkan]);

        Log::info("Cancelled {$count} stale pending orders.");
        $this->info("Cancelled {$count} stale pending orders.");

        return self::SUCCESS;
    }
}
