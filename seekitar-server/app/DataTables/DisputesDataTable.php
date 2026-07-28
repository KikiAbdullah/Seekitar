<?php

namespace App\DataTables;

use App\Models\Dispute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DisputesDataTable
{
    public function json(Request $request): JsonResponse
    {
        $query = Dispute::query()
            ->with('order:id,order_number')
            ->select(['id', 'order_id', 'reason', 'status',
                      'response_deadline', 'resolved_at', 'created_at']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return DataTables::eloquent($query)
            ->addColumn('order_number', fn (Dispute $d) => $d->order?->order_number ?? '—')
            ->editColumn('reason', fn (Dispute $d) => $d->reason?->value)
            ->editColumn('status', fn (Dispute $d) => $d->status?->value)
            // Penanda SLA terlampaui — inilah alasan tabel ini ada.
            ->addColumn('overdue', fn (Dispute $d) => $d->resolved_at === null
                && $d->response_deadline?->isPast() ? 'YA' : '')
            ->editColumn('response_deadline', fn (Dispute $d) => $d->response_deadline?->format('d M Y H:i'))
            ->addColumn('action', fn (Dispute $d) => view('admin.disputes._actions', ['dispute' => $d])->render())
            ->rawColumns(['action'])
            ->toJson();
    }
}
