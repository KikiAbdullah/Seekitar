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
            ->with([
                'user:id,name,phone,verified_at,avatar_url',
                'category:id,name',
                'acceptedOffer:id,store_id,price,additional_cost,status',
                'acceptedOffer.store:id,name',
            ])
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
            ->select(['id', 'user_id', 'title', 'description', 'category_id', 'status',
                      'budget_min', 'budget_max', 'radius_km', 'images',
                      'required_date', 'expires_at', 'extended_at', 'extension_count',
                      'accepted_offer_id', 'created_at'])
            ->withCount('offers');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        return DataTables::eloquent($query)
            // Sel kaya dirender lewat partial bersama supaya tabel dan halaman
            // detail berbicara dengan bahasa visual yang sama.
            ->editColumn('title', fn (CustomerRequest $r) => view('admin.requests._judul', ['request' => $r])->render())
            ->addColumn('buyer_name', fn (CustomerRequest $r) => view('admin.requests._pembeli', ['request' => $r])->render())
            ->addColumn('category_name', fn (CustomerRequest $r) => $r->category?->name ?? '—')
            ->addColumn('budget', fn (CustomerRequest $r) => view('admin.requests._budget', ['request' => $r])->render())
            ->editColumn('offers_count', fn (CustomerRequest $r) => view('admin.requests._penawaran', ['request' => $r])->render())
            ->editColumn('expires_at', fn (CustomerRequest $r) => view('admin.requests._berakhir', ['request' => $r])->render())
            ->editColumn('status', fn (CustomerRequest $r) => view('admin.requests._status', ['request' => $r])->render())
            ->addColumn('status_value', fn (CustomerRequest $r) => $r->status?->value ?? '')
            ->editColumn('created_at', fn (CustomerRequest $r) => $r->created_at?->format('d M Y'))
            ->rawColumns(['title', 'buyer_name', 'category_name', 'budget', 'offers_count', 'expires_at', 'status'])
            ->toJson();
    }
}
