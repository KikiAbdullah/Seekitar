<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class FeeController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    public function index(): View
    {
        $total = Order::sum('service_fee');
        $count = Order::where('service_fee', '>', 0)->count();
        $bulan = Order::where('created_at', '>=', now()->startOfMonth())
            ->sum('service_fee');

        return view('admin.fees.index', [
            'total' => $total,
            'count' => $count,
            'bulan' => $bulan,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $q = Order::where('service_fee', '>', 0)
            ->with(['buyer:id,name', 'store:id,name']);

        return DataTables::of($q)
            ->addColumn('buyer_name', fn (Order $o) => $o->buyer?->name ?? '—')
            ->addColumn('store_name', fn (Order $o) => $o->store?->name ?? '—')
            ->addColumn('order_url', fn (Order $o) => route('admin.orders.show', $o))
            ->editColumn('service_fee', fn (Order $o) => 'Rp ' . number_format((int) $o->service_fee, 0, ',', '.'))
            ->editColumn('total_amount', fn (Order $o) => 'Rp ' . number_format((int) $o->total_amount, 0, ',', '.'))
            ->editColumn('created_at', fn (Order $o) => $o->created_at?->format('d M Y H:i'))
            ->make(true);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service_fee_enabled' => ['required', 'in:0,1'],
            'service_fee_amount'  => ['required', 'integer', 'min:0', 'max:100000'],
            'pro_monthly_price'   => ['required', 'integer', 'min:0'],
            'boost_listing_price' => ['required', 'integer', 'min:0'],
            'banner_price_per_day'=> ['required', 'integer', 'min:0'],
        ]);

        $this->settings->set([
            'service_fee_enabled'  => $data['service_fee_enabled'],
            'service_fee_amount'   => $data['service_fee_amount'],
            'pro_monthly_price'    => $data['pro_monthly_price'],
            'boost_listing_price'  => $data['boost_listing_price'],
            'banner_price_per_day' => $data['banner_price_per_day'],
        ]);

        return redirect()->route('admin.fees.index')
            ->with('success', 'Pengaturan biaya disimpan.');
    }
}
