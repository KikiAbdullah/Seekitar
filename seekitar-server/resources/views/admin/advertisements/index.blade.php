@extends('admin.layout')
@section('title', 'Iklan')

@section('content')
    @include('admin.partials.table-page', [
        'judul'    => 'Iklan Banner',
        'tableId'  => 'ads-table',
        'ajax'     => route('admin.advertisements.data'),
        'order'    => [[6, 'desc']],
        'filterView' => 'admin.advertisements._filter',
        'columns'  => [
            ['data' => 'title',         'label' => 'Judul'],
            ['data' => 'position_label','label' => 'Posisi', 'orderable' => false],
            ['data' => 'buyer_name',     'label' => 'Pembeli', 'orderable' => false],
            ['data' => 'price_per_day',  'label' => 'Harga/Hari'],
            ['data' => 'starts_at',      'label' => 'Mulai'],
            ['data' => 'ends_at',        'label' => 'Berakhir'],
            ['data' => 'created_at',     'label' => 'Dibuat'],
        ],
    ])

    <div class="mt-3">
        <a href="{{ route('admin.advertisements.create') }}" class="btn btn-seekitar">
            <i class="ti ti-plus me-1" aria-hidden="true"></i> Iklan Baru
        </a>
    </div>
@endsection
