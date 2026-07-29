@extends('admin.layout')
@section('title', 'Detail Toko')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">{{ $store->name }}</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.stores.index') }}">Toko</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Detail</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        {{-- Foto, status & aksi. --}}
        <div class="col-lg-4">
            <div class="card">
                @if ($store->photo)
                    <a href="{{ $store->photo }}" target="_blank" rel="noopener"
                       title="Buka ukuran penuh di tab baru">
                        <img src="{{ $store->photo }}" alt="Foto toko {{ $store->name }}"
                             class="card-img-top" style="max-height: 240px; object-fit: cover;">
                    </a>
                @else
                    <div class="bg-light-primary text-primary d-flex align-items-center justify-content-center"
                         style="height: 160px;" aria-hidden="true">
                        <i class="ti ti-building-store fs-10"></i>
                    </div>
                @endif

                <div class="card-body text-center">
                    <h5 class="fw-semibold mb-2">{{ $store->name }}</h5>

                    <div class="d-flex flex-wrap justify-content-center gap-2 mb-2">
                        @if ($store->verification_status->value === 'verified')
                            <span class="badge bg-success-subtle text-success">{{ $store->verification_status->label() }}</span>
                        @elseif ($store->verification_status->value === 'pending')
                            <span class="badge bg-warning-subtle text-warning">{{ $store->verification_status->label() }}</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger">{{ $store->verification_status->label() }}</span>
                        @endif
                        @if ($store->is_active)
                            <span class="badge bg-primary-subtle text-primary">Aktif</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                        @endif
                    </div>

                    <div class="mb-3">
                        @if ((int) $store->total_reviews > 0)
                            @include('admin.partials._stars', [
                                'rating' => $store->rating_avg,
                                'total'  => $store->total_reviews,
                            ])
                        @else
                            <span class="text-muted fs-2">Belum ada ulasan</span>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        @include('admin.stores._actions', ['store' => $store])
                    </div>
                </div>

                <ul class="list-group list-group-flush fs-3">
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Pemilik</span>
                        <span class="text-end">
                            @if ($store->owner)
                                @can('manage-users')
                                    <a href="{{ route('admin.users.show', $store->owner) }}" class="text-decoration-none">
                                        {{ $store->owner->name }}
                                    </a>
                                @else
                                    {{ $store->owner->name }}
                                @endcan
                                <span class="font-monospace text-muted">{{ $store->owner->phone }}</span>
                                @if ($store->owner->canOpenStore())
                                    <span class="badge bg-success-subtle text-success">KTP terverifikasi</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">KTP belum terverifikasi</span>
                                @endif
                            @else
                                —
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Alamat</span>
                        <span class="text-end">{{ $store->address ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Kabupaten</span>
                        <span class="text-end">
                            {{ $store->regency }}
                            @if ($store->regency_code)
                                <span class="text-muted">· BPS {{ $store->regency_code }}</span>
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Koordinat</span>
                        <span class="font-monospace text-end">
                            {{ number_format((float) $store->latitude, 6) }}, {{ number_format((float) $store->longitude, 6) }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Radius layanan</span>
                        <span class="text-end">{{ rtrim(rtrim(number_format((float) $store->service_radius_km, 2), '0'), '.') }} km</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Dibuat</span>
                        <span class="text-end">{{ $store->created_at->format('d M Y H:i') }}</span>
                    </li>
                    @if ($store->verified_at)
                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="text-muted">Disetujui</span>
                            <span class="text-end">
                                {{ $store->verifiedBy?->name ?? '—' }} · {{ $store->verified_at->format('d M Y H:i') }}
                            </span>
                        </li>
                    @endif
                    @if ($store->rejected_reason)
                        <li class="list-group-item">
                            <div class="text-muted mb-1">Alasan penolakan</div>
                            <span class="text-danger">{{ $store->rejected_reason }}</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="col-lg-8">

            {{-- Statistik aktivitas toko. --}}
            <div class="row">
                <div class="col-6 col-lg-3">
                    <div class="card"><div class="card-body py-3 text-center">
                        <div class="fs-7 fw-bold">{{ $store->listings_count }}</div>
                        <div class="text-muted fs-2">Listing</div>
                    </div></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card"><div class="card-body py-3 text-center">
                        <div class="fs-7 fw-bold">{{ $store->offers_count }}</div>
                        <div class="text-muted fs-2">Penawaran</div>
                    </div></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card"><div class="card-body py-3 text-center">
                        <div class="fs-7 fw-bold">{{ $store->orders_count }}</div>
                        <div class="text-muted fs-2">Pesanan</div>
                    </div></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card"><div class="card-body py-3 text-center">
                        <div class="fs-7 fw-bold">{{ $store->reviews_count }}</div>
                        <div class="text-muted fs-2">Ulasan</div>
                    </div></div>
                </div>
            </div>

            {{-- Profil usaha. --}}
            <div class="card">
                <div class="card-header fw-semibold">Profil Usaha</div>
                <div class="card-body">
                    <dl class="row mb-3 fs-3">
                        <dt class="col-sm-3">Jenis</dt>
                        <dd class="col-sm-9">
                            @foreach ($store->store_type ?? [] as $tipe)
                                <span class="badge text-bg-light border">{{ $tipe->label() }}</span>
                            @endforeach
                        </dd>
                        <dt class="col-sm-3">Kategori</dt>
                        <dd class="col-sm-9">
                            @forelse ($kategori as $nama)
                                <span class="badge bg-primary-subtle text-primary">{{ $nama }}</span>
                            @empty
                                <span class="text-muted">—</span>
                            @endforelse
                        </dd>
                        @if ($store->npwp)
                            <dt class="col-sm-3">NPWP</dt>
                            <dd class="col-sm-9 font-monospace">{{ $store->npwp }}</dd>
                        @endif
                        @if ($store->bank_account)
                            <dt class="col-sm-3">Rekening</dt>
                            <dd class="col-sm-9 font-monospace">{{ $store->bank_account }}</dd>
                        @endif
                    </dl>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @if ($store->accepts_cod)
                            <span class="badge bg-success-subtle text-success">Bisa COD</span>
                        @endif
                        @if ($store->offers_delivery)
                            <span class="badge bg-info-subtle text-info">Bisa Diantar</span>
                        @endif
                        @if ($store->allows_pickup)
                            <span class="badge bg-primary-subtle text-primary">Ambil di Tempat</span>
                        @endif
                    </div>

                    {{-- Jam operasional, bila diisi pemilik (JSON per hari). --}}
                    @php
                        $hari = ['senin' => 'Senin', 'selasa' => 'Selasa', 'rabu' => 'Rabu',
                                 'kamis' => 'Kamis', 'jumat' => 'Jumat', 'sabtu' => 'Sabtu',
                                 'minggu' => 'Minggu'];
                        $jam = $store->operating_hours ?? [];
                    @endphp
                    @if ($jam)
                        <div class="text-muted fw-semibold mb-1" style="font-size: 11px;">JAM OPERASIONAL</div>
                        <ul class="list-unstyled fs-3 mb-0">
                            @foreach ($hari as $kunci => $nama)
                                <li class="d-flex justify-content-between" style="max-width: 280px;">
                                    <span>{{ $nama }}</span>
                                    <span class="font-monospace">
                                        @if (isset($jam[$kunci]['open'], $jam[$kunci]['close']))
                                            {{ $jam[$kunci]['open'] }}–{{ $jam[$kunci]['close'] }}
                                        @else
                                            <span class="text-muted">Tutup</span>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            {{-- Lokasi + lingkaran radius layanan. --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Lokasi</span>
                    <a class="btn btn-sm btn-outline-primary"
                       href="https://www.google.com/maps/search/?api=1&query={{ $store->latitude }},{{ $store->longitude }}"
                       target="_blank" rel="noopener">
                        <i class="ti ti-map-pin" aria-hidden="true"></i> Google Maps
                    </a>
                </div>
                <div class="card-body">
                    <div id="petaDetailToko" class="rounded border admin-peta-detail"
                         role="img" aria-label="Peta lokasi {{ $store->name }}"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const wadah = document.getElementById('petaDetailToko');
        if (!wadah || typeof L === 'undefined') return;

        const lat    = @js((float) $store->latitude);
        const lng    = @js((float) $store->longitude);
        const radius = @js((float) $store->service_radius_km);

        const peta = L.map(wadah, { center: [lat, lng], zoom: 14, scrollWheelZoom: false });

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(peta);

        L.marker([lat, lng]).addTo(peta);

        if (radius > 0) {
            // Lingkaran klaim jangkauan — langsung terlihat masuk akal
            // untuk titik tokonya atau tidak.
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
