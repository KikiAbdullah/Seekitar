<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\CustomerRequestsDataTable;
use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomerRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerRequestController extends Controller
{
    public function index(): View
    {
        return view('admin.requests.index');
    }

    public function data(Request $request, CustomerRequestsDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    public function show(CustomerRequest $customerRequest): View
    {
        return view('admin.requests.show', [
            'request' => $customerRequest->load(['user', 'category', 'offers.store']),
        ]);
    }

    /**
     * Memperpanjang permintaan 24 jam (§9.7).
     *
     * Gunanya untuk permintaan yang kedaluwarsa akibat gangguan sistem, bukan
     * karena pembeli membiarkannya. Admin TIDAK dibatasi kuota 2 kali seperti
     * pembeli (DATABASE.md §4.5) — tetapi setiap perpanjangan tetap menambah
     * `extension_count`, sehingga penyalahgunaannya terlihat di data.
     */
    public function extend(Request $request, CustomerRequest $customerRequest): RedirectResponse
    {
        // Permintaan yang sudah ditutup berarti pembeli telah memilih
        // penyedia. Membukanya kembali akan mengundang penawaran untuk
        // pekerjaan yang sudah berjalan.
        if ($customerRequest->status === RequestStatus::Closed) {
            return back()->with('error', 'Permintaan yang sudah ditutup tidak dapat diperpanjang.');
        }

        $customerRequest->forceFill([
            // Status dikembalikan ke Open: memperpanjang yang berstatus
            // `expired` tanpa mengubah statusnya hanya menggeser tanggal pada
            // baris yang tetap tidak muncul di pencarian.
            'status'          => RequestStatus::Open,
            'expires_at'      => now()->addHours(24),
            'extended_at'     => now(),
            'extension_count' => $customerRequest->extension_count + 1,
        ])->save();

        logger()->info('Permintaan diperpanjang admin', [
            'request_id' => $customerRequest->id,
            'admin_id'   => $request->user()->id,
            'ke'         => $customerRequest->expires_at?->toIso8601String(),
        ]);

        return back()->with('success', 'Permintaan diperpanjang 24 jam.');
    }
}
