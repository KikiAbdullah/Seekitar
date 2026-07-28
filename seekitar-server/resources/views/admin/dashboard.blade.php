@extends('admin.layout')
@section('title', 'Dasbor')

@section('content')
    <h1 class="h4 mb-4">Dasbor</h1>

    <div class="row g-3">
        @foreach ([
            ['Pengguna', $stats['users'], 'secondary'],
            ['Toko', $stats['stores'], 'secondary'],
            ['Pesanan Hari Ini', $stats['orders_today'], 'primary'],
            ['KTP Menunggu', $stats['pending_ktp'], 'warning'],
            ['Toko Menunggu', $stats['pending_stores'], 'warning'],
            ['Laporan Lewat SLA', $stats['overdue_disputes'], 'danger'],
        ] as [$label, $value, $tone])
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card h-100 border-{{ $tone }}">
                    <div class="card-body text-center">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="fs-3 fw-bold">{{ number_format($value) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
