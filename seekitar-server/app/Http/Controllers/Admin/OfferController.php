<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\OffersDataTable;
use App\Exports\DataTableExport;
use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Manajemen penawaran (Server_Implementation_Guide.md §9.8).
 *
 * HANYA BACA — tidak ada edit maupun hapus, dan itu disengaja. Penawaran
 * adalah pernyataan harga yang mengikat antara penyedia dan pembeli; admin
 * yang mengubahnya berarti mengubah kesepakatan pihak lain tanpa jejak.
 * Penawaran bermasalah ditangani lewat penangguhan toko atau penyelesaian
 * laporan, yang keduanya meninggalkan catatan alasan.
 *
 * Permission `manage-offers` sudah ada di §6.2 sejak awal, tetapi sebelumnya
 * tidak punya satu pun halaman — izinnya diberikan ke role `admin` dan
 * `super-admin` namun tidak membuka apa pun.
 */
class OfferController extends Controller
{
    public function index(): View
    {
        return view('admin.offers.index');
    }

    public function show(Offer $offer): View
    {
        $offer->load(['store', 'request.user', 'request.category']);

        return view('admin.offers.show', compact('offer'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $offers = Offer::query()
            ->with(['store:id,name', 'request:id,title'])
            ->select(['id', 'request_id', 'store_id', 'price', 'additional_cost',
                      'estimation_time', 'estimated_hours', 'notes', 'status', 'expires_at', 'created_at'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->get();

        $headers = ['ID', 'Permintaan', 'Toko', 'Harga', 'Biaya Tambahan', 'Total', 'Estimasi', 'Jam', 'Status', 'Kedaluwarsa', 'Dibuat'];
        $rows = $offers->map(fn (Offer $o) => [
            $o->id,
            $o->request?->title ?? '—',
            $o->store?->name ?? '—',
            number_format((int) $o->price, 0, ',', '.'),
            number_format((int) $o->additional_cost, 0, ',', '.'),
            number_format((int) $o->price + (int) $o->additional_cost, 0, ',', '.'),
            $o->estimation_time ?? '—',
            $o->estimated_hours ?? '—',
            $o->status?->label() ?? '—',
            $o->expires_at?->format('d M Y H:i') ?? '—',
            $o->created_at?->format('d M Y H:i') ?? '—',
        ]);

        return app(DataTableExport::class)->csv('penawaran-'.now()->format('Y-m-d').'.csv', $headers, $rows);
    }

    public function data(Request $request, OffersDataTable $table): JsonResponse
    {
        return $table->json($request);
    }
}
