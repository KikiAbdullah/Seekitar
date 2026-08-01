<?php

namespace App\DataTables;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/**
 * Sumber JSON untuk tabel Pesanan di panel admin.
 *
 * Kedua belah pihak (pembeli & toko) ditampilkan dengan tanda
 * terverifikasinya masing-masing, jadi keduanya WAJIB di-eager-load
 * dengan kolom seperlunya — tanpa itu, 25 baris berarti 50 query
 * tambahan (N+1 ganda) di setiap gambar ulang tabel.
 *
 * Sel kaya (nomor, pembeli, toko, status) dirender lewat partial bersama
 * supaya bahasa visualnya konsisten dengan halaman detail & menu lain.
 */
class OrdersDataTable
{
    public function json(Request $request): JsonResponse
    {
        $query = Order::query()
            ->select(['id', 'order_number', 'buyer_id', 'store_id', 'order_type',
                      'delivery_method', 'quantity', 'total_amount', 'status', 'created_at'])
            ->with([
                'buyer:id,name,phone,verified_at',
                'store:id,name,status',
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('order_type')) {
            $query->where('order_type', $request->string('order_type'));
        }
        if ($request->filled('delivery_method')) {
            $query->where('delivery_method', $request->string('delivery_method'));
        }

        return DataTables::eloquent($query)
            ->editColumn('order_number', fn (Order $o) => view('admin.orders._nomor', ['order' => $o])->render())
            ->addColumn('buyer_name', fn (Order $o) => view('admin.orders._pembeli', ['order' => $o])->render())
            ->addColumn('store_name', fn (Order $o) => view('admin.orders._toko', ['order' => $o])->render())
            // Lencana penuh warna: label kontekstualnya ("Siap Diambil",
            // "Disewa") menempel pada statusLabel() di model.
            ->addColumn('status_label', fn (Order $o) => view('admin.partials._order_badge', ['order' => $o])->render())
            ->editColumn('total_amount', fn (Order $o) => '<span class="fw-semibold text-nowrap">Rp '
                .number_format((float) $o->total_amount, 0, ',', '.').'</span>')
            ->editColumn('created_at', fn (Order $o) => $o->created_at?->format('d M Y H:i'))
            ->rawColumns(['order_number', 'buyer_name', 'store_name', 'status_label', 'total_amount'])
            ->toJson();
    }
}
