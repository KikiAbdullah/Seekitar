@extends('admin.layout')
@section('title', 'Ulasan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item active" aria-current="page">Ulasan</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Ulasan</h1>

    <div class="alert alert-warning py-2">
        Menghapus ulasan otomatis menghitung ulang rating toko terkait.
    </div>

    <div class="card"><div class="card-body">
        @include('admin._datatable', [
            'tableId' => 'reviews-table',
            'ajax'    => route('admin.reviews.data'),
            'order'   => [[5, 'desc']],
            'columns' => [
                ['data' => 'store_name',    'label' => 'Toko', 'orderable' => false],
                ['data' => 'reviewer_name', 'label' => 'Pengulas', 'orderable' => false],
                ['data' => 'direction',     'label' => 'Arah'],
                ['data' => 'rating',        'label' => 'Rating'],
                ['data' => 'comment',       'label' => 'Komentar'],
                ['data' => 'created_at',    'label' => 'Tanggal'],
                ['data' => 'action',        'label' => 'Aksi', 'orderable' => false, 'searchable' => false],
            ],
        ])
    </div></div>
@endsection
