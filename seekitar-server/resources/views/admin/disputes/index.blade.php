@extends('admin.layout')
@section('title', 'Laporan Masalah')

@section('content')
    @include('admin.partials.table-page', [
        'judul'    => 'Laporan Masalah',
        'tableId'  => 'disputes-table',
        // Default: yang paling dekat melewati SLA lebih dulu.
        'order'    => [[3, 'asc']],
        'ajax'     => route('admin.disputes.data'),
        'filterView' => 'admin.disputes._filter',
        'columns'  => [
            ['data' => 'order_number',      'label' => 'Pesanan', 'orderable' => false],
            ['data' => 'reason',            'label' => 'Alasan'],
            ['data' => 'status',            'label' => 'Status'],
            ['data' => 'response_deadline', 'label' => 'Batas SLA'],
            ['data' => 'overdue',           'label' => 'Lewat SLA', 'orderable' => false, 'searchable' => false],
        ],
    ])
@endsection
