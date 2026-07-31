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
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $request->user()->id]
        );

        $transaction = $wallet->addBalance(
            $request->validated('amount'),
            'topup',
            'Top up saldo',
        );

        return $this->ok(
            ['transaction' => new WalletTransactionResource($transaction)],
            'Top up berhasil',
        );
    }

    public function withdraw(WithdrawWalletRequest $request): JsonResponse
    {
        $wallet = Wallet::where('user_id', $request->user()->id)->firstOrFail();

        $balanceBefore = $wallet->balance;

        $transaction = $wallet->transactions()->create([
            'type'           => 'withdrawal',
            'amount'         => -$request->validated('amount'),
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceBefore,
            'description'    => sprintf(
                'Penarikan saldo ke %s (%s)',
                $request->validated('bank_account'),
                $request->validated('bank_name'),
            ),
            'status'         => 'pending',
        ]);

        return $this->ok(
            ['transaction' => new WalletTransactionResource($transaction)],
            'Permintaan penarikan berhasil diajukan',
        );
    }
}
