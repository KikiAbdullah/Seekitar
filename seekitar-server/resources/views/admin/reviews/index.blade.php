@extends('admin.layout')
@section('title', 'Ulasan')

@section('content')
    <div class="card bg-light-warning shadow-none border-0 mb-4">
        <div class="card-body py-3 px-4">
            <div class="d-flex align-items-start gap-3">
                <i class="ti ti-alert-triangle fs-6 text-warning mt-1" aria-hidden="true"></i>
                <p class="mb-0 fs-3">Menghapus ulasan otomatis menghitung ulang rating toko terkait.</p>
            </div>
        </div>
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
