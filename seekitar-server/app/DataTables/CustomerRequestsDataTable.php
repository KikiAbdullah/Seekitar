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
            ->withCount('offers')
            ->select(['id', 'user_id', 'title', 'category_id', 'status',
                      'expires_at', 'extension_count', 'created_at']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return DataTables::eloquent($query)
            // Nama pembeli disingkat, konsisten dengan yang dilihat penyedia
            // sebelum penawaran diterima (PRD §5.2.3).
            ->addColumn('buyer', fn (CustomerRequest $r) => $r->user?->displayName() ?? '—')
            ->editColumn('status', fn (CustomerRequest $r) => $r->status?->value)
            ->editColumn('expires_at', fn (CustomerRequest $r) => $r->expires_at?->format('d M Y H:i'))
            ->editColumn('created_at', fn (CustomerRequest $r) => $r->created_at?->format('d M Y'))
            ->addColumn('action', fn (CustomerRequest $r) => view('admin.requests._actions', ['request' => $r])->render())
            ->rawColumns(['action'])
            ->orderColumn('action', fn ($q, $dir) => $q)
            ->toJson();
    }
}
