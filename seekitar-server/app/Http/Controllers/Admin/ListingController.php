<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\ListingsDataTable;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Listing;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function index(): View
    {
        return view('admin.listings.index');
    }

    public function data(Request $request, ListingsDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    public function show(Listing $listing): View
    {
        $listing->load('store:id,name,photo');

        /*
         * Seluruh statistik pesanan listing ini dihitung dalam SATU query
         * agregat — bukan tiga query terpisah — karena halaman ini bisa
         * sering dibuka admin saat meninjau konten. Nilai enum
         * diinterpolasi sebagai literal konstanta aplikasi (bukan input
         * pengguna) sehingga aman tanpa binding.
         */
        $selesai = OrderStatus::Selesai->value;
        $statistik = Order::query()
            ->where('listing_id', $listing->id)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN status = '{$selesai}' THEN 1 ELSE 0 END) AS selesai")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = '{$selesai}' THEN total_amount END), 0) AS omzet")
            ->first();

        $favorit = Favorite::query()->where('listing_id', $listing->id)->count();

        // Riwayat pesanan dibatasi ringkas — halaman detail bukan laporan.
        $pesanan = Order::query()
            ->with('buyer:id,name,phone')
            ->where('listing_id', $listing->id)
            ->latest()
            ->limit(8)
            ->get(['id', 'order_number', 'buyer_id', 'order_type', 'delivery_method',
                   'quantity', 'total_amount', 'status', 'created_at']);

        return view('admin.listings.show', compact('listing', 'statistik', 'favorit', 'pesanan'));
    }

    /** Menghapus konten bermasalah (§6.2 permission manage-listings). */
    public function destroy(Listing $listing): RedirectResponse
    {
        $listing->delete();

        return redirect()
            ->route('admin.listings.index')
            ->with('success', 'Listing dihapus.');
    }
}
