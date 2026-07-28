@extends('admin.layout')
@section('title', 'Detail Permintaan')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">{{ $request->title }}</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="{{ route('admin.requests.index') }}">Permintaan</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Detail</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Pembeli</dt>
            <dd class="col-sm-9">{{ $request->user?->name ?? '—' }}</dd>

            <dt class="col-sm-3">Kategori</dt>
            <dd class="col-sm-9">{{ $request->category?->name ?? '—' }}</dd>

            <dt class="col-sm-3">Anggaran</dt>
            <dd class="col-sm-9">
                @if ($request->budget_min === null && $request->budget_max === null)
                    Tidak ditentukan
                @else
                    Rp {{ number_format((int) $request->budget_min, 0, ',', '.') }}
                    – Rp {{ number_format((int) $request->budget_max, 0, ',', '.') }}
                @endif
            </dd>

            <dt class="col-sm-3">Radius siar</dt>
            <dd class="col-sm-9">{{ (float) $request->radius_km }} km</dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">{{ $request->status?->value }}</dd>

            <dt class="col-sm-3">Kedaluwarsa</dt>
            <dd class="col-sm-9">
                {{ $request->expires_at?->format('d M Y H:i') }}
                @if ($request->extension_count > 0)
                    <span class="text-muted">(diperpanjang {{ $request->extension_count }}×)</span>
                @endif
            </dd>

            <dt class="col-sm-3">Deskripsi</dt>
            <dd class="col-sm-9">{{ $request->description }}</dd>
        </dl>
    </div></div>

    <div class="card">
        <div class="card-header">Penawaran Masuk ({{ $request->offers->count() }})</div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                <tr>
                    <th>Toko</th><th>Harga</th><th>Ongkos</th><th>Total</th>
                    <th>Estimasi</th><th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($request->offers as $offer)
                    <tr>
                        <td>{{ $offer->store?->name ?? '—' }}</td>
                        <td>Rp {{ number_format((int) $offer->price, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format((int) $offer->additional_cost, 0, ',', '.') }}</td>
                        <td><strong>Rp {{ number_format((int) $offer->price + (int) $offer->additional_cost, 0, ',', '.') }}</strong></td>
                        <td>{{ $offer->estimation_time }}</td>
                        <td>{{ $offer->status?->value }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada penawaran.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
