<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TopUpWalletRequest;
use App\Http\Requests\Api\WithdrawWalletRequest;
use App\Http\Resources\WalletResource;
use App\Http\Resources\WalletTransactionResource;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class WalletController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $request->user()->id]
        );

        return $this->ok(['wallet' => new WalletResource($wallet)]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $wallet = Wallet::where('user_id', $request->user()->id)->firstOrFail();

        return $this->paginated(
            $wallet->transactions()->latest()->paginate($this->perPage()),
            WalletTransactionResource::class,
        );
    }

    public function topup(TopUpWalletRequest $request): JsonResponse
    {
        $idempotencyKey = $this->idempotencyKey($request);
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $request->user()->id]
        );

        // TIDAK mengkredit langsung — buat transaksi pending dengan reference
        // unik. Saldo dikredit admin/gateway setelah pembayaran diverifikasi.
        $transaction = $wallet->createTopupPending(
            $request->validated('amount'),
            $request->validated('method', 'transfer'),
            $idempotencyKey,
        );

        return $this->ok(
            ['transaction' => new WalletTransactionResource($transaction)],
            'Top up diajukan. Saldo dikredit setelah pembayaran diverifikasi.',
        );
    }

    public function withdraw(WithdrawWalletRequest $request): JsonResponse
    {
        $idempotencyKey = $this->idempotencyKey($request);
        $wallet = Wallet::where('user_id', $request->user()->id)->firstOrFail();

        try {
            $transaction = $wallet->requestWithdrawal(
                $request->validated('amount'),
                $request->validated('bank_name'),
                $request->validated('bank_account'),
                $idempotencyKey,
            );
        } catch (RuntimeException) {
            return $this->fail('Saldo tidak mencukupi untuk penarikan.', 422);
        }

        return $this->ok(
            ['transaction' => new WalletTransactionResource($transaction)],
            'Permintaan penarikan diajukan. Saldo di-hold sampai payout diproses.',
        );
    }

    /** Kunci wajib agar retry jaringan tidak membuat mutasi keuangan kedua. */
    private function idempotencyKey(Request $request): string
    {
        $key = (string) $request->header('Idempotency-Key');
        abort_unless(preg_match('/^[A-Za-z0-9._:-]{16,128}$/', $key) === 1, 422,
            'Header Idempotency-Key wajib diisi (16–128 karakter aman).');

        return $key;
    }
}
