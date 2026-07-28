@extends('admin.layout')
@section('title', 'Pengguna')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item">Manajemen Data</li>
    <li class="breadcrumb-item active" aria-current="page">Pengguna</li>
@endsection

@section('content')
    @include('admin.partials.table-page', [
        'judul'    => 'Pengguna',
        'tableId'  => 'users-table',
        'ajax'     => route('admin.users.data'),
        'order'    => [[4, 'desc']],
        'petunjuk' => 'Pilih baris untuk menyunting atau memblokir pengguna.',
        'filter'   => view('admin.users._filter'),
        'columns'  => [
            ['data' => 'name',               'label' => 'Nama'],
            ['data' => 'phone',              'label' => 'Telepon'],
            ['data' => 'verification_level', 'label' => 'Verifikasi'],
            ['data' => 'status',             'label' => 'Status', 'orderable' => false, 'searchable' => false],
            ['data' => 'created_at',         'label' => 'Terdaftar'],
        ],
    ])
@endsection
