@extends('admin.layout')
@section('title', 'Detail Pesanan')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">{{ $order->order_number }}</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="{{ route('admin.orders.index') }}">Pesanan</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Detail</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-light-info shadow-none border-0 mb-4">
        <div class="card-body py-3 px-4">
            <div class="d-flex align-items-start gap-3">
                <i class="ti ti-info-circle fs-6 text-info mt-1" aria-hidden="true"></i>
                <p class="mb-0 fs-3">Admin tidak mengubah status pesanan dari halaman ini. Gunakan penyelesaian laporan bila perlu intervensi.</p>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">Transaksi</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Status</dt>
                        <dd class="col-7">
                            {{ $order->status?->contextualLabel($order->order_type, $order->delivery_method) }}
                            <span class="text-muted">({{ $order->status?->value }})</span>
                        </dd>

                        <dt class="col-5">Tipe</dt>
                        <dd class="col-7">{{ $order->order_type?->value }}</dd>

                        <dt class="col-5">Jumlah</dt>
                        <dd class="col-7">{{ $order->quantity }}</dd>

                        <dt class="col-5">Total</dt>
                        <dd class="col-7">Rp {{ number_format((int) $order->total_amount, 0, ',', '.') }}</dd>

                        <dt class="col-5">Pembayaran</dt>
                        <dd class="col-7">{{ $order->payment_method?->value }}</dd>

                        <dt class="col-5">Pengiriman</dt>
                        <dd class="col-7">{{ $order->delivery_method?->value }}</dd>

                        <dt class="col-5">Alamat</dt>
                        <dd class="col-7">{{ $order->shipping_address ?: '—' }}</dd>

                        <dt class="col-5">Catatan</dt>
                        <dd class="col-7">{{ $order->notes ?: '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">Riwayat</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Dibuat</dt>
                        <dd class="col-7">{{ $order->created_at?->format('d M Y H:i') }}</dd>

                        <dt class="col-5">Selesai</dt>
                        <dd class="col-7">{{ $order->completed_at?->format('d M Y H:i') ?: '—' }}</dd>

                        <dt class="col-5">Dibatalkan</dt>
                        <dd class="col-7">{{ $order->cancelled_at?->format('d M Y H:i') ?: '—' }}</dd>

                        <dt class="col-5">Alasan batal</dt>
                        <dd class="col-7">{{ $order->cancel_reason ?: '—' }}</dd>

                        <dt class="col-5">Laporan</dt>
                        <dd class="col-7">
                            @forelse ($order->disputes as $dispute)
                                <a href="{{ route('admin.disputes.show', $dispute) }}">
                                    {{ $dispute->reason?->value }}
                                </a>
                            @empty
                                —
                            @endforelse
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
