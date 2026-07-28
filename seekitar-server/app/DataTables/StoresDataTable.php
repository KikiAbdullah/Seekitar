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
            ->with('user:id,name')
            ->select(['id', 'user_id', 'name', 'regency', 'verification_status',
                      'rating_avg', 'total_reviews', 'is_active', 'created_at']);

        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->string('verification_status'));
        }

        return DataTables::eloquent($query)
            ->addColumn('owner', fn (Store $s) => $s->user?->name ?? '—')
            ->editColumn('verification_status', fn (Store $s) => $s->verification_status?->value)
            ->editColumn('created_at', fn (Store $s) => $s->created_at?->format('d M Y'))
            ->addColumn('action', fn (Store $s) => view('admin.stores._actions', ['store' => $s])->render())
            ->rawColumns(['action'])
            ->orderColumn('action', fn ($q, $dir) => $q)
            ->toJson();
    }
}
