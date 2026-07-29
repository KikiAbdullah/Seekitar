@extends('admin.layout')
@section('title', 'Pengguna')

@section('content')
    @include('admin.partials.table-page', [
        'judul'    => 'Pengguna',
        'tableId'  => 'users-table',
        'ajax'     => route('admin.users.data'),
        'order'    => [[4, 'desc']],
        'filterView' => 'admin.users._filter',
        'columns'  => [
            ['data' => 'name',       'label' => 'Nama'],
            ['data' => 'phone',      'label' => 'Telepon'],
            ['data' => 'rating',     'label' => 'Rating', 'orderable' => false, 'searchable' => false],
            ['data' => 'status',     'label' => 'Status', 'orderable' => false, 'searchable' => false],
            // created_at TETAP kolom ke-5: urutan default ('order' di atas)
            // menunjuk indeks kolom, bukan nama.
            ['data' => 'created_at', 'label' => 'Terdaftar'],
            ['data' => 'email',      'label' => 'Email'],
            ['data' => 'address',    'label' => 'Alamat'],
        ],
    ])
@endsection
