<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesAsUtc;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Wallet extends Model
{
    use HasFactory, HasUuids, SerializesDatesAsUtc;

    protected $fillable = [
        'user_id',
        'balance',
        'total_earned',
        'total_withdrawn',
    ];

    protected function casts(): array
    {
        return [
            'balance'         => 'decimal:2',
            'total_earned'    => 'decimal:2',
            'total_withdrawn' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function canWithdraw(int|float $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function addBalance(
        int|float $amount,
        string $type,
        ?string $description = null,
        ?Model $reference = null,
    ): WalletTransaction {
        return DB::transaction(function () use ($amount, $type, $description, $reference) {
            $balanceBefore = $this->balance;

            $this->increment('balance', $amount);
            $this->increment('total_earned', $amount);

            return $this->transactions()->create([
                'type'           => $type,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $this->fresh()->balance,
                'description'    => $description,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id'   => $reference?->getKey(),
                'status'         => 'completed',
            ]);
        });
    }

    public function deductBalance(
        int|float $amount,
        string $type,
        ?string $description = null,
        ?Model $reference = null,
    ): WalletTransaction {
        if (! $this->canWithdraw($amount)) {
            throw new RuntimeException('Saldo tidak mencukupi.');
        }

        return DB::transaction(function () use ($amount, $type, $description, $reference) {
            $balanceBefore = $this->balance;

            $this->decrement('balance', $amount);
            $this->increment('total_withdrawn', $amount);

            return $this->transactions()->create([
                'type'           => $type,
                'amount'         => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $this->fresh()->balance,
                'description'    => $description,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id'   => $reference?->getKey(),
                'status'         => 'completed',
            ]);
        });
    }
}
