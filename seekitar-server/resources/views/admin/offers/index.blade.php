@extends('admin.layout')
@section('title', 'Penawaran')

@section('content')
    <div class="card bg-light-info shadow-none border-0 mb-4">
        <div class="card-body py-3 px-4">
            <div class="d-flex align-items-start gap-3">
                <i class="fa-regular fa-circle-question fs-6 text-info mt-1" aria-hidden="true"></i>
                <p class="mb-0 fs-3">Halaman ini <strong>hanya baca</strong>. Penawaran adalah kesepakatan harga antara penyedia dan pembeli. Untuk penawaran bermasalah, tangani lewat toko terkait atau penyelesaian laporan agar alasannya tercatat.</p>
            </div>
        </div>
    </div>

    @include('admin.partials.table-page', [
        'judul'   => 'Penawaran',
        'tableId' => 'offers-table',
        'ajax'    => route('admin.offers.data'),
        'order'   => [[7, 'desc']],
        'exportRoute' => 'admin.offers.export',
        'filterView' => 'admin.offers._filter',
        'columns' => [
            ['data' => 'request_title',   'label' => 'Permintaan'],
            ['data' => 'store_name',      'label' => 'Toko', 'orderable' => false],
            ['data' => 'price',           'label' => 'Harga'],
            // Kolom hasil hitung tidak punya padanan di SQL, jadi tidak bisa
            // diurutkan maupun dicari lewat query.
            ['data' => 'total',           'label' => 'Total', 'orderable' => false, 'searchable' => false],
            ['data' => 'estimation_time', 'label' => 'Estimasi'],
            ['data' => 'status',          'label' => 'Status'],
            ['data' => 'expires_at',      'label' => 'Kedaluwarsa'],
            ['data' => 'created_at',      'label' => 'Dibuat'],
            ['data' => 'action',          'label' => '', 'orderable' => false, 'searchable' => false],
        ],
    ])
@endsection
