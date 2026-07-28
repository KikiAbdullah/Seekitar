<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\ListingsDataTable;
use App\Http\Controllers\Controller;
use App\Models\Listing;
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
        return view('admin.listings.show', ['listing' => $listing->load('store')]);
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
