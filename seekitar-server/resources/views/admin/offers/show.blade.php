@extends('admin.layout')
@section('title', 'Detail Penawaran')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">Detail Penawaran</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.offers.index') }}">Penawaran</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Detail</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        {{-- Ringkasan penawaran & aksi. --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="fw-semibold mb-2">
                        Rp {{ number_format((int) $offer->total_amount, 0, ',', '.') }}
                    </h5>
                    <div class="mb-3">
                        @if ($offer->status?->value === 'accepted')
                            <span class="badge bg-success-subtle text-success">{{ $offer->status->label() }}</span>
                        @elseif ($offer->status?->value === 'pending')
                            <span class="badge bg-warning-subtle text-warning">{{ $offer->status->label() }}</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">{{ $offer->status?->label() }}</span>
                        @endif
                    </div>

                    @include('admin.offers._actions', ['offer' => $offer])
                </div>

                <ul class="list-group list-group-flush fs-3">
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Permintaan</span>
                        <span class="text-end">
                            @can('manage-requests')
                                <a href="{{ route('admin.requests.show', $offer->request) }}" class="text-decoration-none">
                                    {{ $offer->request?->title ?? '—' }}
                                </a>
                            @else
                                {{ $offer->request?->title ?? '—' }}
                            @endcan
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Toko</span>
                        <span class="text-end">
                            @can('manage-stores')
                                <a href="{{ route('admin.stores.show', $offer->store) }}" class="text-decoration-none">
                                    {{ $offer->store?->name ?? '—' }}
                                </a>
                            @else
                                {{ $offer->store?->name ?? '—' }}
                            @endcan
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Pemilik Toko</span>
                        <span class="text-end">
                            @can('manage-users')
                                <a href="{{ route('admin.users.show', $offer->store?->owner) }}" class="text-decoration-none">
                                    {{ $offer->store?->owner?->name ?? '—' }}
                                </a>
                            @else
                                {{ $offer->store?->owner?->name ?? '—' }}
                            @endcan
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Pembeli</span>
                        <span class="text-end">
                            @can('manage-users')
                                <a href="{{ route('admin.users.show', $offer->request?->user) }}" class="text-decoration-none">
                                    {{ $offer->request?->user?->name ?? '—' }}
                                </a>
                            @else
                                {{ $offer->request?->user?->name ?? '—' }}
                            @endcan
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Dibuat</span>
                        <span class="text-end">{{ $offer->created_at?->format('d M Y H:i') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Kedaluwarsa</span>
                        <span class="text-end">{{ $offer->expires_at?->format('d M Y H:i') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-lg-8">

            {{-- Rincian harga. --}}
            <div class="card">
                <div class="card-header fw-semibold">Rincian Harga</div>
                <div class="card-body">
                    <dl class="row mb-0 fs-3">
                        <dt class="col-sm-4">Harga</dt>
                        <dd class="col-sm-8">Rp {{ number_format((int) $offer->price, 0, ',', '.') }}</dd>

                        <dt class="col-sm-4">Biaya Tambahan</dt>
                        <dd class="col-sm-8">
                            @if ((float) $offer->additional_cost > 0)
                                Rp {{ number_format((int) $offer->additional_cost, 0, ',', '.') }}
                                @if ($offer->additional_cost_note)
                                    <div class="text-muted" style="font-size: 11px;">{{ $offer->additional_cost_note }}</div>
                                @endif
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-4">Total</dt>
                        <dd class="col-sm-8 fw-semibold">Rp {{ number_format((int) $offer->total_amount, 0, ',', '.') }}</dd>
                    </dl>
                </div>
            </div>

            {{-- Estimasi waktu. --}}
            <div class="card">
                <div class="card-header fw-semibold">Estimasi</div>
                <div class="card-body">
                    <dl class="row mb-0 fs-3">
                        <dt class="col-sm-4">Waktu</dt>
                        <dd class="col-sm-8">{{ $offer->estimation_time ?: '—' }}</dd>

                        @if ($offer->estimated_hours)
                            <dt class="col-sm-4">Perkiraan Jam</dt>
                            <dd class="col-sm-8">{{ $offer->estimated_hours }} jam</dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Catatan. --}}
            @if ($offer->notes)
                <div class="card">
                    <div class="card-header fw-semibold">Catatan</div>
                    <div class="card-body">
                        <div class="fs-3" style="white-space: pre-line;">{{ $offer->notes }}</div>
                    </div>
                </div>
            @endif

            {{-- Informasi permintaan terkait. --}}
            <div class="card">
                <div class="card-header fw-semibold">Permintaan Terkait</div>
                <div class="card-body">
                    <dl class="row mb-0 fs-3">
                        <dt class="col-sm-4">Judul</dt>
                        <dd class="col-sm-8">{{ $offer->request?->title ?? '—' }}</dd>

                        <dt class="col-sm-4">Kategori</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-primary-subtle text-primary">{{ $offer->request?->category?->name ?? '—' }}</span>
                        </dd>

                        <dt class="col-sm-4">Deskripsi</dt>
                        <dd class="col-sm-8" style="white-space: pre-line;">{{ $offer->request?->description ?? '—' }}</dd>

                        <dt class="col-sm-4">Status Permintaan</dt>
                        <dd class="col-sm-8">
                            @if ($offer->request?->status?->value === 'open')
                                <span class="badge bg-success-subtle text-success">{{ $offer->request->status->label() }}</span>
                            @elseif ($offer->request?->status?->value === 'closed')
                                <span class="badge bg-primary-subtle text-primary">{{ $offer->request->status->label() }}</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">{{ $offer->request?->status?->label() }}</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection