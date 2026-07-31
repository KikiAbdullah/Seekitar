@extends('admin.layout')
@section('title', 'Langganan')

@section('content')
    @include('admin.partials.table-page', [
        'judul'    => 'Langganan & Boost',
        'tableId'  => 'subscriptions-table',
        'ajax'     => route('admin.subscriptions.data'),
        'order'    => [[7, 'desc']],
        'filterView' => 'admin.subscriptions._filter',
        'columns'  => [
            ['data' => 'user',          'label' => 'Pengguna', 'orderable' => false],
            ['data' => 'plan_label',    'label' => 'Paket'],
            ['data' => 'amount',        'label' => 'Jumlah'],
            ['data' => 'status_badge',  'label' => 'Status', 'orderable' => false],
            ['data' => 'payment_ref',   'label' => 'Ref. Bayar'],
            ['data' => 'starts_at',     'label' => 'Mulai'],
            ['data' => 'ends_at',       'label' => 'Berakhir'],
            ['data' => 'created_at',    'label' => 'Dibuat'],
        ],
    ])
@endsection
