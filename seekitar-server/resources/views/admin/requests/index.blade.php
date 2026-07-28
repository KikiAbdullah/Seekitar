@extends('admin.layout')
@section('title', 'Permintaan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item">Manajemen Data</li>
    <li class="breadcrumb-item active" aria-current="page">Permintaan</li>
@endsection

@section('content')
    @include('admin.partials.table-page', [
        'judul'    => 'Permintaan Pembeli',
        'tableId'  => 'requests-table',
        'ajax'     => route('admin.requests.data'),
        'order'    => [[5, 'desc']],
        'petunjuk' => 'Pilih baris untuk melihat penawaran yang masuk atau memperpanjang masa berlaku.',
        'filter'   => view('admin.requests._filter'),
        'columns'  => [
            ['data' => 'title',        'label' => 'Judul'],
            ['data' => 'buyer',        'label' => 'Pembeli', 'orderable' => false],
            ['data' => 'status',       'label' => 'Status'],
            ['data' => 'offers_count', 'label' => 'Penawaran', 'searchable' => false],
            ['data' => 'expires_at',   'label' => 'Kedaluwarsa'],
            ['data' => 'created_at',   'label' => 'Dibuat'],
        ],
    ])
@endsection
