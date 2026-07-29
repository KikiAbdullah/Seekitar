@extends('admin.layout')
@section('title', 'Listing')

@section('content')
    @include('admin.partials.table-page', [
        'judul'    => 'Listing',
        'tableId'  => 'listings-table',
        'ajax'     => route('admin.listings.data'),
        'order'    => [[6, 'desc']],
        'filterView' => 'admin.listings._filter',
        'columns'  => [
            ['data' => 'title',           'label' => 'Listing'],
            ['data' => 'store_name',      'label' => 'Toko', 'orderable' => false],
            ['data' => 'listing_type',    'label' => 'Tipe'],
            ['data' => 'price',           'label' => 'Harga'],
            ['data' => 'favorites_count', 'label' => 'Favorit'],
            ['data' => 'status',          'label' => 'Status'],
            ['data' => 'created_at',      'label' => 'Dibuat'],
        ],
    ])
@endsection
