<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\OrdersDataTable;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        /*
         * Muat ulang lewat query agar titik tujuan antar (kolom POINT
         * biner) terbaca sebagai latitude/longitude biasa, dan seluruh
         * relasi yang ditampilkan diambil sekaligus (anti N+1). Titik
         * bisa NULL — pesanan ambil-di-tempat tidak punya tujuan antar.
         */
        $order = Order::query()
            ->withCoordinates('shipping_location')
            ->with(['buyer', 'store', 'listing', 'offer.request', 'disputes',
                    'reviews.reviewer', 'cancelledBy', 'disputes.reporter'])
            ->findOrFail($order->id);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Sajikan bukti bayar dari disk privat (local).
     *
     * Berkas tidak bisa diakses lewat URL publik — route ini adalah
     * satu-satunya jalan untuk melihatnya di panel admin.
     */
    public function paymentProofMedia(Order $order): StreamedResponse
    {
        $path = $order->getRawOriginal('payment_proof_url');

        abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

        $response = Storage::disk('local')->response($path);
        $response->headers->set('Content-Type', Storage::disk('local')->mimeType($path));
        $response->headers->set('Content-Disposition', 'inline');

        return $response;
    }
}
