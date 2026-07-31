@extends('admin.layout')
@section('title', 'Biaya Layanan')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">Biaya Layanan</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Biaya Layanan</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card bg-light-success shadow-none">
                <div class="card-body py-3">
                    <div class="text-muted fs-2">Total Biaya Layanan</div>
                    <div class="fs-6 fw-bold">Rp {{ number_format((int) $total, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card bg-light-info shadow-none">
                <div class="card-body py-3">
                    <div class="text-muted fs-2">Bulan Ini</div>
                    <div class="fs-6 fw-bold">Rp {{ number_format((int) $bulan, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card bg-light-warning shadow-none">
                <div class="card-body py-3">
                    <div class="text-muted fs-2">Pesanan Terkena Fee</div>
                    <div class="fs-6 fw-bold">{{ number_format($count) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pengaturan --}}
    <div class="card mb-4">
        <div class="card-header fw-semibold">Pengaturan Biaya</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.fees.update-settings') }}">
                @csrf

                @php
                    $feeEnabled = \App\Models\Setting::where('key', 'service_fee_enabled')->first()?->value ?? '0';
                    $feeAmount  = \App\Models\Setting::where('key', 'service_fee_amount')->first()?->value ?? '1500';
                    $proPrice   = \App\Models\Setting::where('key', 'pro_monthly_price')->first()?->value ?? '30000';
                    $boostPrice = \App\Models\Setting::where('key', 'boost_listing_price')->first()?->value ?? '7500';
                    $bannerPrice= \App\Models\Setting::where('key', 'banner_price_per_day')->first()?->value ?? '50000';
                @endphp

                <div class="row mb-3">
                    <label class="col-sm-4 col-form-label">Biaya Flat per Transaksi</label>
                    <div class="col-sm-8">
                        <select name="service_fee_enabled" class="form-select js-select2" data-min-search="20">
                            <option value="1" @selected($feeEnabled === '1')>Aktif</option>
                            <option value="0" @selected($feeEnabled !== '1')>Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-4 col-form-label">Besaran Biaya (Rp)</label>
                    <div class="col-sm-8">
                        <input type="number" name="service_fee_amount" class="form-control"
                               value="{{ $feeAmount }}" min="0" max="100000" step="100">
                    </div>
                </div>

                <hr>
                <div class="row mb-3">
                    <label class="col-sm-4 col-form-label">Pro Bulanan (Rp)</label>
                    <div class="col-sm-8">
                        <input type="number" name="pro_monthly_price" class="form-control"
                               value="{{ $proPrice }}" min="0" step="1000">
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-4 col-form-label">Boost Listing (Rp)</label>
                    <div class="col-sm-8">
                        <input type="number" name="boost_listing_price" class="form-control"
                               value="{{ $boostPrice }}" min="0" step="500">
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-4 col-form-label">Iklan Banner per Hari (Rp)</label>
                    <div class="col-sm-8">
                        <input type="number" name="banner_price_per_day" class="form-control"
                               value="{{ $bannerPrice }}" min="0" step="1000">
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-8 offset-sm-4">
                        <button type="submit" class="btn btn-seekitar">Simpan Pengaturan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Detail biaya per pesanan --}}
    <div class="card">
        <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
            <span>Riwayat Biaya Layanan</span>
        </div>
        <div class="card-body">
            <table id="fees-table" class="table align-middle text-nowrap w-100">
                <thead>
                    <tr>
                        <th scope="col">Pesanan</th>
                        <th scope="col">Pembeli</th>
                        <th scope="col">Toko</th>
                        <th scope="col">Total</th>
                        <th scope="col">Biaya</th>
                        <th scope="col">Tanggal</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const tableId = 'fees-table';
    const columns = [
        { data: 'order_url',       name: 'order_number', orderable: true  },
        { data: 'buyer_name',      name: 'buyer_name', orderable: false },
        { data: 'store_name',      name: 'store_name', orderable: false },
        { data: 'total_amount',    name: 'total_amount', orderable: true  },
        { data: 'service_fee',     name: 'service_fee', orderable: true  },
        { data: 'created_at',      name: 'created_at', orderable: true  },
    ];

    $('#' + tableId).DataTable({
        processing: true,
        serverSide: true,
        ajax: @js(route('admin.fees.data')),
        order: [[5, 'desc']],
        columns: columns.map(function (c) {
            if (c.data === 'order_url') {
                return { data: 'order_number', orderable: true, render: function (data, type, row) {
                    return '<a href="' + row.order_url + '" class="font-monospace text-decoration-none">' + data + '</a>';
                }};
            }
            return c;
        }),
        language: { url: @js(asset('vendor/datatables/id.json')) },
    });
})();
</script>
@endpush
