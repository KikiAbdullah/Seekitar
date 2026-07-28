@extends('admin.layout')
@section('title', 'Pesanan')

@section('content')
    @include('admin.partials.table-page', [
        'judul'    => 'Pesanan',
        'tableId'  => 'orders-table',
        'ajax'     => route('admin.orders.data'),
        'order'    => [[5, 'desc']],
        'filterView' => 'admin.orders._filter',
        'columns'  => [
            ['data' => 'order_number', 'label' => 'Nomor'],
            ['data' => 'store_name',   'label' => 'Toko', 'orderable' => false],
            ['data' => 'order_type',   'label' => 'Tipe'],
            ['data' => 'total_amount', 'label' => 'Total'],
            ['data' => 'status_label', 'label' => 'Status', 'orderable' => false, 'searchable' => false],
            ['data' => 'created_at',   'label' => 'Dibuat'],
        ],
    ])
@endsection
