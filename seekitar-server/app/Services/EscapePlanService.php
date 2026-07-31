<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class EscapePlanService
{
    const MAX_RETRIES = 3;

    public function transactional(callable $callback, string $context = 'unknown')
    {
        $attempts = 0;

        while ($attempts < self::MAX_RETRIES) {
            try {
                return DB::transaction($callback);
            } catch (Throwable $e) {
                $attempts++;

                Log::warning("Transaksi gagal (percobaan {$attempts})", [
                    'context' => $context,
                    'error'   => $e->getMessage(),
                ]);

                if ($attempts >= self::MAX_RETRIES) {
                    Log::error("Transaksi gagal setelah {$attempts} percobaan", [
                        'context' => $context,
                        'error'   => $e->getMessage(),
                        'trace'   => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }

                usleep(100_000 * $attempts);
            }
        }
    }

    public function compensate(string $action, array $context, callable $rollback): void
    {
        try {
            $rollback();
            Log::info("Kompensasi berhasil: {$action}", $context);
        } catch (Throwable $e) {
            Log::critical("Kompensasi GAGAL: {$action}", [
                'context' => $context,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function degrade(string $feature, string $reason): void
    {
        cache()->set("degraded:{$feature}", true, 300);
        Log::warning("Degradasi fitur: {$feature}", [
            'reason' => $reason,
            'time'   => now()->toIso8601String(),
        ]);
    }

    public function isDegraded(string $feature): bool
    {
        return (bool) cache()->get("degraded:{$feature}", false);
    }

    public static function callExternal(callable $call, string $service, int $maxRetries = 2): mixed
    {
        $attempts = 0;
        while ($attempts <= $maxRetries) {
            try {
                return $call();
            } catch (Throwable $e) {
                $attempts++;
                if ($attempts > $maxRetries) {
                    Log::error("External service {$service} unreachable after {$maxRetries} retries");
                    throw $e;
                }
                usleep(200_000 * $attempts);
            }
        }
    }
}
