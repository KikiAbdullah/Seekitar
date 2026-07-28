<?php

namespace App\DataTables;

use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ReviewsDataTable
{
    public function json(Request $request): JsonResponse
    {
        $query = Review::query()
            ->with(['store:id,name', 'reviewer:id,name'])
            ->select(['id', 'order_id', 'store_id', 'reviewer_id',
                      'direction', 'rating', 'comment', 'created_at']);

        if ($request->filled('rating')) {
            $query->where('rating', $request->integer('rating'));
        }

        return DataTables::eloquent($query)
            ->addColumn('store_name', fn (Review $r) => $r->store?->name ?? '—')
            // Nama pengulas disingkat, sama seperti yang dilihat pengguna
            // lain di aplikasi (PRD §5.2.3).
            ->addColumn('reviewer_name', fn (Review $r) => $r->reviewer?->displayName() ?? '—')
            ->editColumn('direction', fn (Review $r) => $r->direction?->value)
            ->editColumn('created_at', fn (Review $r) => $r->created_at?->format('d M Y'))
            ->addColumn('action', fn (Review $r) => view('admin.reviews._actions', ['review' => $r])->render())
            ->rawColumns(['action'])
            ->toJson();
    }
}
