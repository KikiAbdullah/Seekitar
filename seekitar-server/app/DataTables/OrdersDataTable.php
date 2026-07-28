<?php

namespace App\DataTables;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class OrdersDataTable
{
    public function json(Request $request): JsonResponse
    {
        $query = Order::query()
            ->with('store:id,name')
            ->select(['id', 'order_number', 'store_id', 'order_type', 'total_amount',
                      'status', 'delivery_method', 'created_at']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return DataTables::eloquent($query)
            ->addColumn('store_name', fn (Order $o) => $o->store?->name ?? '—')
            // Label kontekstual: "dikirim" tampil beda untuk pickup vs
            // delivery, dan "Disewa" untuk sewa (PRD §5.4).
            ->addColumn('status_label', fn (Order $o) => $o->status
                ?->contextualLabel($o->order_type, $o->delivery_method))
            ->editColumn('status', fn (Order $o) => $o->status?->value)
            ->editColumn('order_type', fn (Order $o) => $o->order_type?->value)
            ->editColumn('total_amount', fn (Order $o) => 'Rp '.number_format((int) $o->total_amount, 0, ',', '.'))
            ->editColumn('created_at', fn (Order $o) => $o->created_at?->format('d M Y H:i'))
            ->addColumn('action', fn (Order $o) => view('admin.orders._actions', ['order' => $o])->render())
            ->rawColumns(['action'])
            ->orderColumn('action', fn ($q, $dir) => $q)
            ->toJson();
    }
}
