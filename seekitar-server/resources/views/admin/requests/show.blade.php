@extends('admin.layout')
@section('title', 'Detail Permintaan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

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

    <div class="row">

        {{-- Ringkasan permintaan & aksi. --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="fw-semibold mb-2">{{ $request->title }}</h5>
                    <div class="mb-3">
                        @if ($request->status?->value === 'open')
                            <span class="badge bg-success-subtle text-success">{{ $request->status->label() }}</span>
                        @elseif ($request->status?->value === 'closed')
                            <span class="badge bg-primary-subtle text-primary">{{ $request->status->label() }}</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">{{ $request->status?->label() }}</span>
                        @endif
                    </div>

                    {{-- Perpanjangan admin (§9.7) — hanya untuk permintaan yang
                         belum ditutup pembeli. --}}
                    @can('manage-requests')
                        @if ($request->status?->value !== 'closed')
                            <form action="{{ route('admin.requests.extend', $request) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-sm"
                                        onclick="return confirm('Perpanjang permintaan ini 24 jam?')">
                                    <i class="ti ti-history me-1" aria-hidden="true"></i> Perpanjang 24 Jam
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>

                @if ($request->acceptedOffer)
                    <div class="alert alert-success mb-0 rounded-0 fs-3">
                        <div class="fw-semibold mb-1">
                            <i class="ti ti-circle-check me-1" aria-hidden="true"></i> Penawaran diterima pembeli
                        </div>
                        {{ $request->acceptedOffer->store?->name ?? '—' }}
                        · Rp {{ number_format((int) $request->acceptedOffer->total_amount, 0, ',', '.') }}
                    </div>
                @endif

                <ul class="list-group list-group-flush fs-3">
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Pembeli</span>
                        <span class="text-end">
                            @can('manage-users')
                                <a href="{{ route('admin.users.show', $request->user) }}" class="text-decoration-none">
                                    {{ $request->user?->name }}
                                </a>
                            @else
                                {{ $request->user?->name }}
                            @endcan
                            <div class="text-muted font-monospace" style="font-size: 11px;">{{ $request->user?->phone }}</div>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Kategori</span>
                        <span class="text-end">
                            <span class="badge bg-primary-subtle text-primary">{{ $request->category?->name ?? '—' }}</span>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Anggaran</span>
                        <span class="text-end">
                            @if ($request->budget_min === null && $request->budget_max === null)
                                Tidak ditentukan
                            @else
                                Rp {{ number_format((int) $request->budget_min, 0, ',', '.') }}
                                – Rp {{ number_format((int) $request->budget_max, 0, ',', '.') }}
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Radius siar</span>
                        <span class="text-end">{{ (float) $request->radius_km }} km</span>
                    </li>
                    @if ($request->required_date)
                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="text-muted">Dibutuhkan</span>
                            <span class="text-end">{{ $request->required_date->format('d M Y H:i') }}</span>
                        </li>
                    @endif
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Kedaluwarsa</span>
                        <span class="text-end">
                            {{ $request->expires_at?->format('d M Y H:i') }}
                            @if ((int) $request->extension_count > 0)
                                <span class="text-muted">(diperpanjang {{ $request->extension_count }}×)</span>
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Dibuat</span>
                        <span class="text-end">{{ $request->created_at?->format('d M Y H:i') }}</span>
                    </li>
                </ul>

                {{-- Lampiran foto dari pembeli (maks 3, migrasi requests). --}}
                @if ($request->images)
                    <div class="card-body border-top">
                        <div class="text-muted fw-semibold mb-2" style="font-size: 11px;">LAMPIRAN FOTO</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($request->images as $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener"
                                   title="Buka ukuran penuh di tab baru">
                                    <img src="{{ $url }}" alt="Lampiran {{ $request->title }}"
                                         class="rounded border" style="width: 84px; height: 84px; object-fit: cover;">
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-8">

            {{-- Statistik ringkas penawaran. --}}
            @if ($request->offers->isNotEmpty())
                <div class="row">
                    <div class="col-sm-4">
                        <div class="card"><div class="card-body py-3 text-center">
                            <div class="fs-7 fw-bold">{{ $request->offers->count() }}</div>
                            <div class="text-muted fs-2">Penawaran Masuk</div>
                        </div></div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card"><div class="card-body py-3 text-center">
                            <div class="fs-6 fw-bold">Rp {{ number_format((int) $request->offers->min('total_amount'), 0, ',', '.') }}</div>
                            <div class="text-muted fs-2">Total Termurah</div>
                        </div></div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card"><div class="card-body py-3 text-center">
                            <div class="fs-6 fw-bold">Rp {{ number_format((int) $request->offers->max('total_amount'), 0, ',', '.') }}</div>
                            <div class="text-muted fs-2">Total Termahal</div>
                        </div></div>
                    </div>
                </div>
            @endif

            {{-- Deskripsi: paragraf dipertahankan seperti tulisan pembeli. --}}
            <div class="card">
                <div class="card-header fw-semibold">Deskripsi Kebutuhan</div>
                <div class="card-body">
                    <div class="fs-3" style="white-space: pre-line;">{{ $request->description }}</div>
                </div>
            </div>

            {{-- Titik siar + lingkaran radius jangkauan. --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Lokasi Siar</span>
                    <a class="btn btn-sm btn-outline-primary"
                       href="https://www.google.com/maps/search/?api=1&query={{ $request->latitude }},{{ $request->longitude }}"
                       target="_blank" rel="noopener">
                        <i class="ti ti-map-pin" aria-hidden="true"></i> Google Maps
                    </a>
                </div>
                <div class="card-body">
                    <div id="petaLokasiSiar" class="rounded border" style="height: 260px; width: 100%;"
                         role="img" aria-label="Peta lokasi siar {{ $request->title }}"></div>
                    <div class="text-muted fs-2 mt-2">
                        Lingkaran = radius siar {{ (float) $request->radius_km }} km — hanya toko di dalamnya
                        yang menerima permintaan ini.
                    </div>
                </div>
            </div>

            {{-- Penawaran masuk, diurutkan dari TOTAL termurah: total yang
                 mengikat pembeli, bukan harga dasarnya (DATABASE.md §4.6). --}}
            <div class="card">
                <div class="card-header fw-semibold">Penawaran Masuk ({{ $request->offers->count() }})</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 fs-3">
                            <thead>
                                <tr>
                                    <th>Toko</th>
                                    <th>Total</th>
                                    <th>Estimasi</th>
                                    <th>Catatan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($request->offers->sortBy('total_amount') as $offer)
                                    <tr class="{{ $offer->status?->value === 'accepted' ? 'table-success' : '' }}">
                                        <td>
                                            @if ($offer->store?->photo)
                                                <img src="{{ $offer->store->photo }}" alt="" class="rounded me-1"
                                                     style="width: 28px; height: 28px; object-fit: cover;">
                                            @endif
                                            @can('manage-stores')
                                                <a href="{{ route('admin.stores.show', $offer->store) }}"
                                                   class="text-decoration-none">{{ $offer->store?->name ?? '—' }}</a>
                                            @else
                                                {{ $offer->store?->name ?? '—' }}
                                            @endcan
                                        </td>
                                        <td>
                                            <span class="fw-semibold">
                                                Rp {{ number_format((int) $offer->total_amount, 0, ',', '.') }}
                                            </span>
                                            <div class="text-muted" style="font-size: 11px;">
                                                Harga Rp {{ number_format((int) $offer->price, 0, ',', '.') }}
                                                @if ((float) $offer->additional_cost > 0)
                                                    + ongkos Rp {{ number_format((int) $offer->additional_cost, 0, ',', '.') }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            {{ $offer->estimation_time ?: '—' }}
                                            @if ($offer->estimated_hours)
                                                <div class="text-muted" style="font-size: 11px;">± {{ $offer->estimated_hours }} jam</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($offer->additional_cost_note)
                                                <div><span class="text-muted">Ongkos:</span> {{ $offer->additional_cost_note }}</div>
                                            @endif
                                            @if ($offer->notes)
                                                <div>{{ $offer->notes }}</div>
                                            @endif
                                            @if (! $offer->additional_cost_note && ! $offer->notes)
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if ($offer->status?->value === 'accepted')
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="ti ti-check" aria-hidden="true"></i> {{ $offer->status->label() }}
                                                </span>
                                            @elseif ($offer->status?->value === 'pending')
                                                <span class="badge bg-warning-subtle text-warning">{{ $offer->status->label() }}</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $offer->status?->label() }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada penawaran.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const wadah = document.getElementById('petaLokasiSiar');
        if (!wadah || typeof L === 'undefined') return;

        const lat    = @js((float) $request->latitude);
        const lng    = @js((float) $request->longitude);
        const radius = @js((float) $request->radius_km);

        const peta = L.map(wadah, { center: [lat, lng], zoom: 13, scrollWheelZoom: false });

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(peta);

        L.marker([lat, lng]).addTo(peta);

        // Lingkaran radius siar: seberapa jauh permintaan ini tersebar.
        if (radius > 0) {
            L.circle([lat, lng], {
                radius: radius * 1000,
                color: '#5d87ff',
                weight: 1,
                fillOpacity: 0.06,
            }).addTo(peta);
        }
    });
</script>
@endpush
