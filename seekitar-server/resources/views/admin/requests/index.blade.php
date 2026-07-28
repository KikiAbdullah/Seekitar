@extends('admin.layout')
@section('title', 'Permintaan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item active" aria-current="page">Permintaan</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Permintaan Pembeli</h1>

    <div class="card"><div class="card-body">
        @include('admin._datatable', [
            'tableId' => 'requests-table',
            'ajax'    => route('admin.requests.data'),
            'order'   => [[5, 'desc']],
            'columns' => [
                ['data' => 'title',        'label' => 'Judul'],
                ['data' => 'buyer',        'label' => 'Pembeli', 'orderable' => false],
                ['data' => 'status',       'label' => 'Status'],
                ['data' => 'offers_count', 'label' => 'Penawaran', 'searchable' => false],
                ['data' => 'expires_at',   'label' => 'Kedaluwarsa'],
                ['data' => 'created_at',   'label' => 'Dibuat'],
                ['data' => 'action',       'label' => 'Aksi', 'orderable' => false, 'searchable' => false],
            ],
        ])
    </div></div>
@endsection
