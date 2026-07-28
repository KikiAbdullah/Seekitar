@extends('admin.layout')
@section('title', 'Pengguna')

@section('content')
    @include('admin.partials.table-page', [
        'judul'    => 'Pengguna',
        'tableId'  => 'users-table',
        'ajax'     => route('admin.users.data'),
        'order'    => [[5, 'desc']],
        'filterView' => 'admin.users._filter',
        'columns'  => [
            ['data' => 'name',               'label' => 'Nama'],
            ['data' => 'phone',              'label' => 'Telepon'],
            ['data' => 'verification_level', 'label' => 'Verifikasi'],
            ['data' => 'rating',             'label' => 'Rating', 'orderable' => false, 'searchable' => false],
            ['data' => 'status',             'label' => 'Status', 'orderable' => false, 'searchable' => false],
            ['data' => 'created_at',         'label' => 'Terdaftar'],
        ],
    ])
@endsection
