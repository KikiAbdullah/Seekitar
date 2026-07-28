<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\ReviewsDataTable;
use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(): View
    {
        return view('admin.reviews.index');
    }

    public function data(Request $request, ReviewsDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    /**
     * Menghapus ulasan yang melanggar.
     *
     * Reputasi WAJIB dihitung ulang setelahnya (API §10.4) — kalau tidak,
     * `rating_avg` tetap memuat nilai ulasan yang sudah tidak ada. Itu
     * ditangani ReviewObserver::deleted secara sinkron untuk KEDUA arah:
     * rating toko (buyer_to_store) maupun rating pembeli (store_to_buyer).
     */
    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return redirect()
            ->route('admin.reviews.index')
            ->with('success', 'Ulasan dihapus dan reputasi dihitung ulang.');
    }
}
