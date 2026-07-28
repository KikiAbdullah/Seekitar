<?php

namespace App\DataTables;

use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ListingsDataTable
{
    public function json(Request $request): JsonResponse
    {
        $query = Listing::query()
            ->with('store:id,name')
            ->select(['id', 'store_id', 'title', 'listing_type', 'price',
                      'stock_qty', 'slot', 'status', 'created_at']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('listing_type')) {
            $query->where('listing_type', $request->string('listing_type'));
        }

        return DataTables::eloquent($query)
            ->addColumn('store_name', fn (Listing $l) => $l->store?->name ?? '—')
            ->editColumn('listing_type', fn (Listing $l) => $l->listing_type?->value)
            ->editColumn('status', fn (Listing $l) => $l->status?->value)
            // Rupiah tanpa desimal (API §12.3).
            ->editColumn('price', fn (Listing $l) => $l->price === null
                ? '—' : 'Rp '.number_format((int) $l->price, 0, ',', '.'))
            ->editColumn('created_at', fn (Listing $l) => $l->created_at?->format('d M Y'))
            ->addColumn('action', fn (Listing $l) => view('admin.listings._actions', ['listing' => $l])->render())
            ->rawColumns(['action'])
            ->toJson();
    }
}
