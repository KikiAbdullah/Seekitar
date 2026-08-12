<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesAsUtc;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    /**
     * Ajukan top up — TIDAK mengkredit saldo.
     *
     * Mencatat permintaan top up sebagai transaksi `pending` dengan reference
     * unik. Saldo HANYA dikredit lewat [confirmTopup] setelah pembayaran
     * diverifikasi (admin/gateway). Ini mencegah "money minting": user tidak
     * bisa mengisi saldonya sendiri tanpa pembayaran sungguhan.
     */
    public function createTopupPending(int|float $amount, string $method = 'transfer'): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $method) {
            $wallet = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $balance = (float) $wallet->balance;

            return $wallet->transactions()->create([
                'type'           => 'topup',
                'amount'         => $amount,
                'balance_before' => $balance,
                'balance_after'  => $balance, // belum dikredit — menunggu konfirmasi
                'description'    => "Top up saldo ({$method})",
                'reference'      => 'TOPT-'.Str::uuid(),
                'status'         => 'pending',
            ]);
        });
    }

    /**
     * Konfirmasi top up yang sudah dibayar — satu-satunya jalur kredit saldo.
     * Idempoten: transaksi yang bukan `topup`/`pending` ditolak.
     */
    public function confirmTopup(WalletTransaction $tx): WalletTransaction
    {
        return DB::transaction(function () use ($tx) {
            $tx = WalletTransaction::whereKey($tx->id)->lockForUpdate()->firstOrFail();
            if ($tx->wallet_id !== $this->getKey() || $tx->type !== 'topup' || $tx->status !== 'pending') {
                throw new RuntimeException('Transaksi top up tidak valid untuk dikonfirmasi.');
            }

            $wallet = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $amount = (float) $tx->amount;
            $balanceBefore = (float) $wallet->balance;

            $wallet->increment('balance', $amount);
            $wallet->increment('total_earned', $amount);
            $tx->update([
                'status'         => 'completed',
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceBefore + $amount,
            ]);

            return $tx;
        });
    }

    /** Batalkan top up yang belum dikonfirmasi (saldo tidak berubah). */
    public function cancelTopup(WalletTransaction $tx): WalletTransaction
    {
        return DB::transaction(function () use ($tx) {
            $tx = WalletTransaction::whereKey($tx->id)->lockForUpdate()->firstOrFail();
            if ($tx->wallet_id !== $this->getKey() || $tx->type !== 'topup' || $tx->status !== 'pending') {
                throw new RuntimeException('Transaksi top up tidak valid.');
            }
            $tx->update(['status' => 'rejected']);

            return $tx;
        });
    }

    /**
     * Ajukan penarikan: cek saldo (terkunci), lalu HOLD dana.
     *
     * Saldo DIPOTONG segera dan transaksi `pending` — dana tidak bisa
     * ditarik ganda/overdraw. Payout diselesaikan admin lewat
     * [completeWithdrawal] atau [rejectWithdrawal] (refund).
     */
    public function requestWithdrawal(
        int|float $amount,
        string $bankName,
        string $bankAccount,
    ): WalletTransaction {
        return DB::transaction(function () use ($amount, $bankName, $bankAccount) {
            $wallet = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if (! $wallet->canWithdraw($amount)) {
                throw new RuntimeException('Saldo tidak mencukupi.');
            }

            $balanceBefore = (float) $wallet->balance;
            $wallet->decrement('balance', $amount);

            return $wallet->transactions()->create([
                'type'           => 'withdrawal',
                'amount'         => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceBefore - $amount,
                'description'    => "Penarikan saldo ke {$bankName} ({$bankAccount})",
                'reference'      => 'WD-'.Str::uuid(),
                'status'         => 'pending',
            ]);
        });
    }

    /** Selesaikan payout penarikan (dana sudah di-hold saat pengajuan). */
    public function completeWithdrawal(WalletTransaction $tx): WalletTransaction
    {
        return DB::transaction(function () use ($tx) {
            $tx = WalletTransaction::whereKey($tx->id)->lockForUpdate()->firstOrFail();
            if ($tx->wallet_id !== $this->getKey() || $tx->type !== 'withdrawal' || $tx->status !== 'pending') {
                throw new RuntimeException('Transaksi penarikan tidak valid.');
            }

            $wallet = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $wallet->increment('total_withdrawn', abs((float) $tx->amount));
            $tx->update(['status' => 'completed']);

            return $tx;
        });
    }

    /** Tolak payout → kembalikan dana yang di-hold ke saldo. */
    public function rejectWithdrawal(WalletTransaction $tx): WalletTransaction
    {
        return DB::transaction(function () use ($tx) {
            $tx = WalletTransaction::whereKey($tx->id)->lockForUpdate()->firstOrFail();
            if ($tx->wallet_id !== $this->getKey() || $tx->type !== 'withdrawal' || $tx->status !== 'pending') {
                throw new RuntimeException('Transaksi penarikan tidak valid.');
            }

            $wallet = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $wallet->increment('balance', abs((float) $tx->amount));
            $tx->update(['status' => 'rejected']);

            return $tx;
        });
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
