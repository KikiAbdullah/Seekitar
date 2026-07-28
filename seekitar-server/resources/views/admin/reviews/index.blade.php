@extends('admin.layout')
@section('title', 'Ulasan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item">Manajemen Data</li>
    <li class="breadcrumb-item active" aria-current="page">Ulasan</li>
@endsection

@section('content')
    <div class="alert alert-warning py-2 d-flex align-items-center gap-2" role="alert">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span>Menghapus ulasan otomatis menghitung ulang rating toko terkait.</span>
    </div>

    @include('admin.partials.table-page', [
        'judul'    => 'Ulasan',
        'tableId'  => 'reviews-table',
        'ajax'     => route('admin.reviews.data'),
        'order'    => [[5, 'desc']],
        'filterView' => 'admin.reviews._filter',
        'columns'  => [
            ['data' => 'store_name',    'label' => 'Toko', 'orderable' => false],
            ['data' => 'reviewer_name', 'label' => 'Pengulas', 'orderable' => false],
            ['data' => 'direction',     'label' => 'Arah'],
            ['data' => 'rating',        'label' => 'Rating'],
            ['data' => 'comment',       'label' => 'Komentar'],
            ['data' => 'created_at',    'label' => 'Tanggal'],
        ],
    ])
@endsection
