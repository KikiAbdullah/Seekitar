<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DisputeStatus;
use App\Enums\OrderStatus;
use App\Enums\VerificationLevel;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'users'           => User::count(),
                'stores'          => Store::count(),
                'orders_today'    => Order::whereDate('created_at', today())->count(),
                'pending_ktp'     => User::whereNotNull('ktp_submitted_at')
                    ->where('verification_level', VerificationLevel::Basic)->count(),
                'pending_stores'  => Store::where('verification_status', VerificationStatus::Pending)->count(),
                // Yang paling mendesak: laporan yang SLA-nya sudah lewat.
                'overdue_disputes' => Dispute::where('status', DisputeStatus::Open)
                    ->where('response_deadline', '<', now())->count(),
            ],
        ]);
    }

    /** Data grafik pesanan 14 hari terakhir (§9.1). */
    public function chartData(): JsonResponse
    {
        $rows = Order::selectRaw('DATE(created_at) AS day, COUNT(*) AS total')
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return response()->json([
            'labels' => $rows->pluck('day'),
            'data'   => $rows->pluck('total'),
        ]);
    }
}
