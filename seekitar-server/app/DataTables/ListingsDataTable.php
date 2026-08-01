<?php

namespace App\DataTables;

use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/**
 * Sumber JSON untuk tabel Etalase di panel admin.
 *
 * Dua keputusan yang menjaga halaman ini cepat:
 *
 * 1. Eager-load `store` dengan kolom seperlunya. Kolom toko dirender per
 *    BARIS di server; tanpa eager-load, satu halaman 25 baris berarti 25
 *    query tambahan (N+1) hanya untuk nama & tanda centang toko.
 *
 * 2. Angka favorit dihitung `withCount` — SATU subquery terindeks di SQL
 *    yang sama, bukan menghitung ulang di PHP per baris.
 *
 * Setiap sel yang bukan teks polos dirender lewat partial bersama
 * (_judul/_toko/_tipe/_status) supaya tabel, kartu, dan detail berbicara
 * dengan bahasa visual yang sama.
 */
class ListingsDataTable
{
    public function json(Request $request): JsonResponse
    {
        // Urutan penting: select() DULU baru withCount(). select() menimpa
        // daftar kolom — withCount yang ditulis sebelumnya ikut terhapus
        // dan favorites_count hilang dari JSON (ditangkap check-datatables).
        $query = Listing::query()
            ->select(['id', 'store_id', 'title', 'listing_type', 'price',
                      'stock_qty', 'slot', 'images', 'status', 'created_at'])
            ->with(['store:id,name,status'])
            ->withCount('favorites');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('listing_type')) {
            $query->where('listing_type', $request->string('listing_type'));
        }

        return DataTables::eloquent($query)
            ->editColumn('title', fn (Listing $l) => view('admin.listings._judul', ['listing' => $l])->render())
            ->addColumn('store_name', fn (Listing $l) => view('admin.listings._toko', ['listing' => $l])->render())
            ->editColumn('listing_type', fn (Listing $l) => view('admin.listings._tipe', ['listing' => $l])->render())
            ->editColumn('status', fn (Listing $l) => view('admin.listings._status', ['listing' => $l])->render())
            // Rupiah tanpa desimal (API §12.3) — pemisah Indonesia.
            ->editColumn('price', fn (Listing $l) => $l->price === null
                ? '—' : 'Rp '.number_format((float) $l->price, 0, ',', '.'))
            ->editColumn('favorites_count', fn (Listing $l) => '<span class="d-inline-flex align-items-center text-nowrap">'
                .'<i class="fa-regular fa-heart text-danger me-1" aria-hidden="true"></i>'
                .number_format((int) $l->favorites_count, 0, ',', '.').'</span>')
            ->addColumn('action', fn (Listing $l) => view('admin.listings._actions', ['listing' => $l])->render())
            ->editColumn('created_at', fn (Listing $l) => $l->created_at?->format('d M Y'))
            ->rawColumns(['title', 'store_name', 'listing_type', 'status', 'favorites_count', 'action'])
            ->toJson();
    }
}
