@extends('admin.layout')
@section('title', 'Listing')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item">Manajemen Data</li>
    <li class="breadcrumb-item active" aria-current="page">Listing</li>
@endsection

@section('content')
    @include('admin.partials.table-page', [
        'judul'    => 'Listing',
        'tableId'  => 'listings-table',
        'ajax'     => route('admin.listings.data'),
        'order'    => [[5, 'desc']],
        'filterView' => 'admin.listings._filter',
        'columns'  => [
            ['data' => 'title',        'label' => 'Judul'],
            ['data' => 'store_name',   'label' => 'Toko', 'orderable' => false],
            ['data' => 'listing_type', 'label' => 'Tipe'],
            ['data' => 'price',        'label' => 'Harga'],
            ['data' => 'status',       'label' => 'Status'],
            ['data' => 'created_at',   'label' => 'Dibuat'],
        ],
    ])
@endsection
