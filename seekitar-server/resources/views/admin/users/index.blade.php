@extends('admin.layout')
@section('title', 'Pengguna')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item active" aria-current="page">Pengguna</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Pengguna</h1>

    <div class="card">
        <div class="card-body">
            <table id="users-table" class="table table-striped w-100">
                <thead>
                <tr>
                    <th>Nama</th>
                    <th>Telepon</th>
                    <th>Verifikasi</th>
                    <th>Status</th>
                    <th>Terdaftar</th>
                    <th>Aksi</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('#users-table').DataTable({
        processing: true,
        serverSide: true,          // 100 ribu baris tidak dikirim ke browser
        ajax: '{{ route('admin.users.data') }}',
        columns: [
            { data: 'name' },
            { data: 'phone' },
            { data: 'verification_level' },
            { data: 'status' },
            { data: 'created_at' },
            // Kolom aksi bukan data; mengurutkannya tidak bermakna.
            { data: 'action', orderable: false, searchable: false },
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/id.json' },
    });
</script>
@endpush
