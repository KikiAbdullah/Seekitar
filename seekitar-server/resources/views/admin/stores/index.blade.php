@extends('admin.layout')
@section('title', 'Toko')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item active" aria-current="page">Toko</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Toko</h1>

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label for="filter-status" class="form-label">Status verifikasi</label>
                <select id="filter-status" class="form-select w-auto d-inline-block">
                    <option value="">Semua</option>
                    @foreach (\App\Enums\VerificationStatus::cases() as $status)
                        <option value="{{ $status->value }}">{{ $status->value }}</option>
                    @endforeach
                </select>
            </div>

            <table id="stores-table" class="table table-striped w-100">
                <thead>
                <tr>
                    <th>Nama</th>
                    <th>Pemilik</th>
                    <th>Kabupaten</th>
                    <th>Verifikasi</th>
                    <th>Rating</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const table = $('#stores-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.stores.data') }}',
            data: d => { d.verification_status = $('#filter-status').val(); },
        },
        columns: [
            { data: 'name' },
            { data: 'owner' },
            { data: 'regency' },
            { data: 'verification_status' },
            { data: 'rating_avg' },
            { data: 'created_at' },
            { data: 'action', orderable: false, searchable: false },
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/id.json' },
    });

    $('#filter-status').on('change', () => table.ajax.reload());
</script>
@endpush
