<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\StoresDataTable;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
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
