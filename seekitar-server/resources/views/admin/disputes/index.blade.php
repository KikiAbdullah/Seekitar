@extends('admin.layout')
@section('title', 'Laporan Masalah')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item active" aria-current="page">Laporan</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Laporan Masalah</h1>

    <div class="card">
        <div class="card-body">
            <table id="disputes-table" class="table table-striped w-100">
                <thead>
                <tr>
                    <th>Pesanan</th>
                    <th>Alasan</th>
                    <th>Status</th>
                    <th>Batas SLA</th>
                    <th>Lewat SLA</th>
                    <th>Aksi</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('#disputes-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin.disputes.data') }}',
        // Default: yang paling dekat melewati SLA lebih dulu.
        order: [[3, 'asc']],
        columns: [
            { data: 'order_number' },
            { data: 'reason' },
            { data: 'status' },
            { data: 'response_deadline' },
            { data: 'overdue' },
            { data: 'action', orderable: false, searchable: false },
        ],
    });
</script>
@endpush
