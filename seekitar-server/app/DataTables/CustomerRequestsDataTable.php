<?php

namespace App\DataTables;

use App\Models\CustomerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CustomerRequestsDataTable
{
    public function json(Request $request): JsonResponse
    {
        $query = CustomerRequest::query()
            ->with('user:id,name')
            /*
             * select() HARUS mendahului withCount().
             *
             * withCount() menambahkan subquery ke daftar SELECT. Memanggil
             * select() SESUDAHNYA menimpa seluruh daftar itu, dan subquerynya
             * hilang tanpa error apa pun — SQL-nya tetap sah, hanya saja
             * kolom `offers_count` tidak pernah ada.
             *
             * Akibatnya Datatables menolak barisnya dengan:
             *   "Requested unknown parameter 'offers_count' for row 0, column 3"
             *
             * Diverifikasi lewat toSql():
             *   withCount()->select()  → tanpa offers_count
             *   select()->withCount()  → dengan (select count(*) …) as offers_count
             */
            ->select(['id', 'user_id', 'title', 'category_id', 'status',
                      'expires_at', 'extension_count', 'created_at'])
            ->withCount('offers');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return DataTables::eloquent($query)
            // Nama pembeli disingkat, konsisten dengan yang dilihat penyedia
            // sebelum penawaran diterima (PRD §5.2.3).
            ->addColumn('buyer', fn (CustomerRequest $r) => $r->user?->displayName() ?? '—')
            ->editColumn('status', fn (CustomerRequest $r) => $r->status?->label() ?? '—')
            ->editColumn('expires_at', fn (CustomerRequest $r) => $r->expires_at?->format('d M Y H:i'))
            ->editColumn('created_at', fn (CustomerRequest $r) => $r->created_at?->format('d M Y'))
            // Nilai dipastikan ada meski subquery gagal: null di kolom yang
            // diminta Datatables tetap memicu "Requested unknown parameter".
            ->editColumn('offers_count', fn (CustomerRequest $r) => (int) ($r->offers_count ?? 0))
            ->addColumn('action', fn (CustomerRequest $r) => view('admin.requests._actions', ['request' => $r])->render())
            ->rawColumns(['action'])
            ->toJson();
    }
}
