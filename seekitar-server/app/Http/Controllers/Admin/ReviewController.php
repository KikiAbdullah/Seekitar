<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\ReviewsDataTable;
use App\Http\Controllers\Controller;
use App\Jobs\RecalculateStoreRatingJob;
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
     * Rating toko WAJIB dihitung ulang setelahnya (API §10.4) — kalau tidak,
     * `rating_avg` tetap memuat nilai ulasan yang sudah tidak ada.
     * ReviewObserver sudah menanganinya, dan job ini jaring pengaman kedua
     * seandainya observer dilewati.
     */
    public function destroy(Review $review): RedirectResponse
    {
        $storeId = $review->store_id;

        $review->delete();

        if ($storeId !== null) {
            RecalculateStoreRatingJob::dispatch($storeId);
        }

        return redirect()
            ->route('admin.reviews.index')
            ->with('success', 'Ulasan dihapus & rating toko dihitung ulang.');
    }
}
