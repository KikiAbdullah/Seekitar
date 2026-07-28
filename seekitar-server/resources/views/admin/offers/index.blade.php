@extends('admin.layout')
@section('title', 'Penawaran')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item">Manajemen Data</li>
    <li class="breadcrumb-item active" aria-current="page">Penawaran</li>
@endsection

@section('content')
    <div class="alert alert-info py-2 d-flex align-items-center gap-2" role="alert">
        <i class="ti ti-info-circle" aria-hidden="true"></i>
        <span>
            Halaman ini <strong>hanya baca</strong> (§9.8). Penawaran adalah kesepakatan
            harga antara penyedia dan pembeli; admin tidak mengubahnya. Untuk penawaran
            bermasalah, tangani lewat toko terkait atau penyelesaian laporan agar
            alasannya tercatat.
        </span>
    </div>

    @include('admin.partials.table-page', [
        'judul'   => 'Penawaran',
        'tableId' => 'offers-table',
        'ajax'    => route('admin.offers.data'),
        'order'   => [[7, 'desc']],
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
        ],
    ])
@endsection
