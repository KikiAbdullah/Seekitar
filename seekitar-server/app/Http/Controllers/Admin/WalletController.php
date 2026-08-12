<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Verifikasi pembayaran wallet (admin).
 *
 * Top up TIDAK mengkredit saldo otomatis (anti money-minting); admin
 * mengonfirmasi setelah pembayaran transfer terlihat. Penarikan sudah
 * di-hold saat diajukan; admin menyelesaikan payout atau menolak (refund).
 */
class WalletController extends Controller
{
    public function index(): View
    {
        $topups = WalletTransaction::query()
            ->where('type', 'topup')
            ->where('status', 'pending')
            ->with('wallet.user')
            ->latest('created_at')
            ->paginate(20);

        $withdrawals = WalletTransaction::query()
            ->where('type', 'withdrawal')
            ->where('status', 'pending')
            ->with('wallet.user')
            ->latest('created_at')
            ->paginate(20);

        return view('admin.wallet.index', compact('topups', 'withdrawals'));
    }

    public function confirmTopup(WalletTransaction $transaction): RedirectResponse
    {
        try {
            $transaction->wallet->confirmTopup($transaction);

            return back()->with('success', 'Top up dikonfirmasi. Saldo dikredit.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancelTopup(WalletTransaction $transaction): RedirectResponse
    {
        try {
            $transaction->wallet->cancelTopup($transaction);

            return back()->with('success', 'Top up dibatalkan.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function completeWithdrawal(WalletTransaction $transaction): RedirectResponse
    {
        try {
            $transaction->wallet->completeWithdrawal($transaction);

            return back()->with('success', 'Penarikan ditandai selesai.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function rejectWithdrawal(WalletTransaction $transaction): RedirectResponse
    {
        try {
            $transaction->wallet->rejectWithdrawal($transaction);

            return back()->with('success', 'Penarikan ditolak. Dana dikembalikan ke saldo.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
