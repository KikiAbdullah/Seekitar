@extends('admin.layout')
@section('title', 'Listing')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item active" aria-current="page">Listing</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Listing</h1>

    <div class="card"><div class="card-body">
        @include('admin._datatable', [
            'tableId' => 'listings-table',
            'ajax'    => route('admin.listings.data'),
            'order'   => [[5, 'desc']],
            'columns' => [
                ['data' => 'title',        'label' => 'Judul'],
                ['data' => 'store_name',   'label' => 'Toko', 'orderable' => false],
                ['data' => 'listing_type', 'label' => 'Tipe'],
                ['data' => 'price',        'label' => 'Harga'],
                ['data' => 'status',       'label' => 'Status'],
                ['data' => 'created_at',   'label' => 'Dibuat'],
                ['data' => 'action',       'label' => 'Aksi', 'orderable' => false, 'searchable' => false],
            ],
        ])
    </div></div>
@endsection
