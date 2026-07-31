@extends('admin.layout')
@section('title', 'Detail Langganan')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">Detail Langganan</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.subscriptions.index') }}">Langganan</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Detail</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="fw-semibold mb-2">{{ $subscription->planLabel() }}</h5>
                    <div class="fs-6 fw-bold mb-2">Rp {{ number_format((int) $subscription->amount, 0, ',', '.') }}</div>
                    @php
                        $warna = match ($subscription->status) {
                            'active'    => 'success',
                            'pending'   => 'warning',
                            'expired'   => 'secondary',
                            'cancelled' => 'danger',
                            default     => 'secondary',
                        };
                    @endphp
                    <span class="badge bg-{{ $warna }}-subtle text-{{ $warna }} fs-3">{{ match ($subscription->status) { 'active' => 'Aktif', 'pending' => 'Tertunda', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', default => ucfirst($subscription->status) } }}</span>
                </div>

                <ul class="list-group list-group-flush fs-3">
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Pemilik</span>
                        <span class="text-end">
                            @can('manage-users')
                                <a href="{{ route('admin.users.show', $subscription->user) }}" class="text-decoration-none">
                                    {{ $subscription->user?->name ?? '—' }}
                                </a>
                            @else
                                {{ $subscription->user?->name ?? '—' }}
                            @endcan
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Toko</span>
                        <span class="text-end">
                            @if ($subscription->store)
                                @can('manage-stores')
                                    <a href="{{ route('admin.stores.show', $subscription->store) }}" class="text-decoration-none">
                                        {{ $subscription->store->name }}
                                    </a>
                                @else
                                    {{ $subscription->store->name }}
                                @endcan
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Referensi Bayar</span>
                        <span class="text-end font-monospace">{{ $subscription->payment_ref ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Mulai</span>
                        <span class="text-end">{{ $subscription->starts_at?->format('d M Y H:i') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Berakhir</span>
                        <span class="text-end">{{ $subscription->ends_at?->format('d M Y H:i') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Dibuat</span>
                        <span class="text-end">{{ $subscription->created_at?->format('d M Y H:i') }}</span>
                    </li>
                </ul>

                @if ($subscription->status === 'active')
                    <div class="card-footer text-center">
                        <form action="{{ route('admin.subscriptions.cancel', $subscription) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger"
                                    data-seekitar-confirm="Langganan ini akan dibatalkan.">
                                <i class="ti ti-ban me-1" aria-hidden="true"></i> Batalkan Langganan
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-7">
            @if ($subscription->notes)
                <div class="card">
                    <div class="card-header fw-semibold">Catatan</div>
                    <div class="card-body">
                        <div class="fs-3" style="white-space: pre-line;">{{ $subscription->notes }}</div>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header fw-semibold">Informasi Paket</div>
                <div class="card-body fs-3">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Paket</dt>
                        <dd class="col-sm-8">{{ $subscription->planLabel() }}</dd>

                        <dt class="col-sm-4">Jumlah</dt>
                        <dd class="col-sm-8">Rp {{ number_format((int) $subscription->amount, 0, ',', '.') }}</dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-{{ $warna }}-subtle text-{{ $warna }}">{{ match ($subscription->status) { 'active' => 'Aktif', 'pending' => 'Tertunda', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', default => ucfirst($subscription->status) } }}</span>
                        </dd>

                        <dt class="col-sm-4">Periode</dt>
                        <dd class="col-sm-8">
                            {{ $subscription->starts_at?->format('d M Y') }} —
                            {{ $subscription->ends_at?->format('d M Y') }}
                            ({{ now()->diffInDays($subscription->ends_at, false) > 0
                                ? now()->diffInDays($subscription->ends_at).' hari lagi'
                                : 'kedaluwarsa' }})
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
