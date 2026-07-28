@extends('admin.layout')
@section('title', 'Permintaan')

@section('content')
    @include('admin.partials.table-page', [
        'judul'    => 'Permintaan Pembeli',
        'tableId'  => 'requests-table',
        'ajax'     => route('admin.requests.data'),
        'order'    => [[5, 'desc']],
        'filterView' => 'admin.requests._filter',
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
