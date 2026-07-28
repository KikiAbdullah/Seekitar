<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\OrdersDataTable;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(): View
    {
        return view('admin.orders.index');
    }

    public function data(Request $request, OrdersDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    /**
     * Admin hanya MELIHAT pesanan.
     *
     * Tidak ada aksi ubah status di sini: transisi milik pembeli & penjual,
     * dan campur tangan admin hanya sah lewat penyelesaian dispute agar
     * jejak alasannya selalu tercatat.
     */
    public function show(Order $order): View
    {
        return view('admin.orders.show', [
            'order' => $order->load(['store', 'listing', 'disputes']),
        ]);
    }
}
