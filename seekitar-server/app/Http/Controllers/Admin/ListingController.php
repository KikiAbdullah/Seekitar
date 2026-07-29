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
        // Toko dimuati dengan seluruh kolom yang dipakai kartu relasi:
        // centang terverifikasi (status), rating (rating_avg/total_reviews),
        // dan domisili (regency) — tanpa menyentuh basis data lagi di view.
        $listing->load(['store:id,user_id,name,photo,regency,status,rating_avg,total_reviews'])
                ->loadCount('favorites');

        /*
         * Statistik performa. Tiga query agregat kecil — COUNT, COUNT,
         * SUM — masing-masing terindeks listing_id. Ini pilihan sadar:
         * konvensi proyek melarang SQL mentah (selectRaw CASE-WHEN yang
         * pernah dipakai di sini), dan pada skala satu listing ketiga
         * query ini sama murahnya dengan satu query raksasa.
         */
        $basisPesanan = fn () => Order::query()->where('listing_id', $listing->id);

        $statistik = [
            'total'   => $basisPesanan()->count(),
            'selesai' => $basisPesanan()->where('status', OrderStatus::Selesai)->count(),
            'omzet'   => (int) $basisPesanan()->where('status', OrderStatus::Selesai)->sum('total_amount'),
        ];

        // Riwayat pesanan dibatasi ringkas — halaman detail bukan laporan.
        $pesanan = Order::query()
            ->with('buyer:id,name,phone')
            ->where('listing_id', $listing->id)
            ->latest()
            ->limit(8)
            ->get(['id', 'order_number', 'buyer_id', 'order_type', 'delivery_method',
                   'quantity', 'total_amount', 'status', 'created_at']);

        // Wajah-wajah yang memfavoritkan: relasi ini menghangatkan angka
        // favorit menjadi orang yang nyata.
        $penggemar = Favorite::query()
            ->with('user:id,name')
            ->where('listing_id', $listing->id)
            ->latest()
            ->limit(5)
            ->get(['id', 'listing_id', 'user_id']);

        return view('admin.listings.show', compact('listing', 'statistik', 'pesanan', 'penggemar'));
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
