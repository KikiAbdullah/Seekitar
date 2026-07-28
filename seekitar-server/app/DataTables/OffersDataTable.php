<?php

namespace App\DataTables;

use App\Models\Offer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class OffersDataTable
{
    public function json(Request $request): JsonResponse
    {
        $query = Offer::query()
            ->with(['store:id,name', 'request:id,title'])
            ->select(['id', 'request_id', 'store_id', 'price', 'additional_cost',
                      'estimation_time', 'status', 'expires_at', 'created_at']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return DataTables::eloquent($query)
            ->addColumn('request_title', fn (Offer $o) => $o->request?->title ?? '—')
            ->addColumn('store_name', fn (Offer $o) => $o->store?->name ?? '—')
            ->editColumn('price', fn (Offer $o) => 'Rp '.number_format((int) $o->price, 0, ',', '.'))
            // Yang mengikat adalah TOTAL, bukan price saja. Menampilkan harga
            // tanpa ongkos membuat penawaran tampak lebih murah dari yang
            // sebenarnya dibayar pembeli (DATABASE.md §4.6).
            ->addColumn('total', fn (Offer $o) => 'Rp '.number_format(
                (int) $o->price + (int) $o->additional_cost, 0, ',', '.'
            ))
            ->editColumn('status', fn (Offer $o) => $o->status?->label() ?? '—')
            ->editColumn('expires_at', fn (Offer $o) => $o->expires_at?->format('d M Y H:i'))
            ->editColumn('created_at', fn (Offer $o) => $o->created_at?->format('d M Y'))
            // Tidak ada kolom aksi: halaman ini hanya baca (§9.8).
            ->toJson();
    }
}
