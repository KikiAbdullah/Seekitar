<?php

namespace App\DataTables;

use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class StoresDataTable
{
    public function json(Request $request): JsonResponse
    {
        $query = Store::query()
            // Relasinya bernama owner(), BUKAN user(). Memakai 'user' di sini
            // melempar "Call to undefined relationship [user] on model
            // [App\Models\Store]" begitu halaman dibuka.
            ->with('owner:id,name')
            ->select(['id', 'user_id', 'name', 'regency', 'status',
                      'rating_avg', 'total_reviews', 'is_active', 'created_at']);

        // Filter kedudukan: nilainya persis nilai enum StoreStatus, jadi
        // tidak ada pemetaan kedua yang bisa menyimpang.
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return DataTables::eloquent($query)
            ->addColumn('owner', fn (Store $s) => $s->owner?->name ?? '—')
            // Nama (+ ikon centang untuk terverifikasi) dirender Blade —
            // kolom ini & lencana masuk rawColumns demi HTML-nya; sisanya
            // TETAP lolos escaping DataTables.
            ->editColumn('name', fn (Store $s) => view('admin.stores._nama', ['store' => $s])->render())
            ->editColumn('status', fn (Store $s) => view('admin.stores._status', ['store' => $s])->render())
            // ★ teks, bukan HTML — lolos escaping apa adanya.
            ->addColumn('rating', fn (Store $s) => (int) $s->total_reviews > 0
                ? sprintf('★ %s (%d)', number_format((float) $s->rating_avg, 1, ',', '.'), $s->total_reviews)
                : '—')
            ->addColumn('action', fn (Store $s) => view('admin.stores._actions', ['store' => $s])->render())
            ->editColumn('created_at', fn (Store $s) => $s->created_at?->format('d M Y'))
            ->rawColumns(['name', 'status', 'action'])
            ->toJson();
    }
}
