<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\StoresDataTable;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(): View
    {
        return view('admin.stores.index');
    }

    public function data(Request $request, StoresDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    /**
     * Detail satu toko.
     *
     * Seluruh konteks keputusan ada di satu layar: identitas & status KTP
     * pemilik, jejak audit persetujuan (verified_by/at), rating bintang,
     * serta peta lokasi + lingkaran radius — tanpa berpindah antarmenu.
     */
    public function show(Store $store): View
    {
        $toko = Store::query()
            // latitude/longitude dari kolom POINT lewat ST_Latitude/
            // ST_Longitude — properti biasa berisi WKB biner (HasLocation).
            ->withCoordinates()
            ->with([
                // Stempel KTP pemilik — dasar status layak, tanpa label level.
                'owner:id,name,phone,verified2_at',
                'verifiedBy:id,name',
            ])
            ->withCount(['listings', 'offers', 'orders', 'reviews'])
            ->findOrFail($store->getKey());

        return view('admin.stores.show', [
            'store'    => $toko,
            // Nama kategori di-resolve dari category_ids (JSON array id).
            'kategori' => Category::query()
                ->whereIn('id', $toko->category_ids ?? [])
                ->orderBy('name')
                ->pluck('name'),
        ]);
    }

    public function approve(Request $request, Store $store): RedirectResponse
    {
        $store->verification_status = VerificationStatus::Verified;
        $store->rejected_reason     = null;
        $store->verified_at         = now();
        $store->verified_by         = $request->user()->id;
        $store->save();

        return back()->with('success', "Toko {$store->name} disetujui.");
    }

    public function reject(Request $request, Store $store): RedirectResponse
    {
        $data = $request->validate([
            // Alasan wajib: tanpa itu pemilik tidak tahu apa yang salah.
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $store->verification_status = VerificationStatus::Rejected;
        $store->rejected_reason     = $data['reason'];
        $store->save();

        return back()->with('success', "Toko {$store->name} ditolak.");
    }
}
