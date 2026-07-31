<?php

namespace App\Http\Concerns;

use App\Services\EscapePlanService;
use Illuminate\Http\JsonResponse;
use Throwable;

trait HasEscapePlan
{
    protected function safely(callable $callback, string $context = 'unknown'): mixed
    {
        $plan = app(EscapePlanService::class);
        return $plan->transactional($callback, $context);
    }

    protected function degradedResponse(string $feature): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Fitur sedang tidak tersedia. Silakan coba lagi nanti.',
            'errors'  => ['service' => ['degraded']],
        ], 503);
    }
}
