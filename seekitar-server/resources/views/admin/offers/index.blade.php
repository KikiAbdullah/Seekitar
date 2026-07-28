@extends('admin.layout')
@section('title', 'Penawaran')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item">Manajemen Data</li>
    <li class="breadcrumb-item active" aria-current="page">Penawaran</li>
@endsection

@section('content')

    <div class="alert alert-info py-2 d-flex align-items-center gap-2" role="alert">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        <span>
            Halaman ini <strong>hanya baca</strong> (§9.8). Penawaran adalah kesepakatan
            harga antara penyedia dan pembeli; admin tidak mengubahnya. Untuk penawaran
            bermasalah, tangani lewat toko terkait atau penyelesaian laporan agar
            alasannya tercatat.
        </span>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label for="filter-status" class="form-label">Status</label>
                <select id="filter-status" class="form-select w-auto d-inline-block">
                    <option value="">Semua</option>
                    @foreach (\App\Enums\OfferStatus::cases() as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>

            <table id="offers-table" class="table table-striped w-100">
                <thead>
                    <tr>
                        <th scope="col">Permintaan</th>
                        <th scope="col">Toko</th>
                        <th scope="col">Harga</th>
                        <th scope="col">Total</th>
                        <th scope="col">Estimasi</th>
                        <th scope="col">Status</th>
                        <th scope="col">Kedaluwarsa</th>
                        <th scope="col">Dibuat</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const tabelPenawaran = $('#offers-table').DataTable({
        processing: true,
        serverSide: true,   // tabel penawaran tumbuh cepat; jangan kirim utuh
        ajax: {
            url: @js(route('admin.offers.data')),
            data: d => { d.status = $('#filter-status').val(); },
        },
        order: [[7, 'desc']],
        columns: [
            { data: 'request_title' },
            { data: 'store_name', orderable: false },
            { data: 'price' },
            // Kolom hasil hitung tidak punya padanan di SQL, jadi tidak bisa
            // diurutkan maupun dicari lewat query.
            { data: 'total', orderable: false, searchable: false },
            { data: 'estimation_time' },
            { data: 'status' },
            { data: 'expires_at' },
            { data: 'created_at' },
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/id.json' },
    });

    $('#filter-status').on('change', () => tabelPenawaran.ajax.reload());
</script>
@endpush
