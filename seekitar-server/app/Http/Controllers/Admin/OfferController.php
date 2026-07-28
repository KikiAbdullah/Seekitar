<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\OffersDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manajemen penawaran (Server_Implementation_Guide.md §9.8).
 *
 * HANYA BACA — tidak ada edit maupun hapus, dan itu disengaja. Penawaran
 * adalah pernyataan harga yang mengikat antara penyedia dan pembeli; admin
 * yang mengubahnya berarti mengubah kesepakatan pihak lain tanpa jejak.
 * Penawaran bermasalah ditangani lewat penangguhan toko atau penyelesaian
 * laporan, yang keduanya meninggalkan catatan alasan.
 *
 * Permission `manage-offers` sudah ada di §6.2 sejak awal, tetapi sebelumnya
 * tidak punya satu pun halaman — izinnya diberikan ke role `admin` dan
 * `super-admin` namun tidak membuka apa pun.
 */
class OfferController extends Controller
{
    public function index(): View
    {
        return view('admin.offers.index');
    }

    public function data(Request $request, OffersDataTable $table): JsonResponse
    {
        return $table->json($request);
    }
}
