<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TransactionService
{
    private int $depth = 0;

    public function begin(): void
    {
        if ($this->depth === 0) {
            DB::beginTransaction();
        } else {
            DB::statement('SAVEPOINT trans_point_'.$this->depth);
        }
        $this->depth++;
    }

    public function commit(): void
    {
        $this->depth--;
        if ($this->depth === 0) {
            DB::commit();
        }
    }

    public function rollback(): void
    {
        $this->depth--;
        if ($this->depth === 0) {
            DB::rollBack();
        } else {
            DB::statement('ROLLBACK TO SAVEPOINT trans_point_'.$this->depth);
        }
    }

    public function execute(callable $callback, string $context = 'unknown')
    {
        $this->begin();
        try {
            $result = $callback();
            $this->commit();
            return $result;
        } catch (Throwable $e) {
            $this->rollback();
            Log::error("Transaction failed: {$context}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
