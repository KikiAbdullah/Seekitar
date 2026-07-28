@extends('admin.layout')
@section('title', 'Pesanan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item active" aria-current="page">Pesanan</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Pesanan</h1>

    <div class="card"><div class="card-body">
        @include('admin._datatable', [
            'tableId' => 'orders-table',
            'ajax'    => route('admin.orders.data'),
            'order'   => [[5, 'desc']],
            'columns' => [
                ['data' => 'order_number', 'label' => 'Nomor'],
                ['data' => 'store_name',   'label' => 'Toko', 'orderable' => false],
                ['data' => 'order_type',   'label' => 'Tipe'],
                ['data' => 'total_amount', 'label' => 'Total'],
                ['data' => 'status_label', 'label' => 'Status', 'orderable' => false],
                ['data' => 'created_at',   'label' => 'Dibuat'],
                ['data' => 'action',       'label' => 'Aksi', 'orderable' => false, 'searchable' => false],
            ],
        ])
    </div></div>
@endsection
