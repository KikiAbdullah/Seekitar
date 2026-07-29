@extends('admin.layout')
@section('title', 'Detail Pesanan')

@if ($order->latitude !== null)
    @push('styles')
        <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
    @endpush
@endif

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h4 class="fw-semibold mb-0 font-monospace">{{ $order->order_number }}</h4>
                        @include('admin.partials._order_badge', ['order' => $order])
                        <span class="badge bg-light-primary text-primary border">
                            {{ $order->order_type?->label() }} · × {{ $order->quantity }}
                        </span>
                    </div>
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

    {{-- Rasional kenapa tidak ada tombol aksi: transisi status milik pembeli
         & penjual; intervensi admin hanya sah lewat penyelesaian laporan. --}}
    <div class="card bg-light-info shadow-none border-0 mb-4">
        <div class="card-body py-3 px-4">
            <div class="d-flex align-items-start gap-3">
                <i class="ti ti-info-circle fs-6 text-info mt-1" aria-hidden="true"></i>
                <p class="mb-0 fs-3">Admin tidak mengubah status pesanan dari halaman ini. Gunakan penyelesaian laporan bila perlu intervensi.</p>
            </div>
        </div>
    </div>

    <div class="row">

        {{-- Status, jumlah & para pihak. --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-2">@include('admin.partials._order_badge', ['order' => $order])</div>
                    <div class="fs-6 fw-bold mb-1">Rp {{ number_format((int) $order->total_amount, 0, ',', '.') }}</div>
                    <div class="text-muted fs-2">
                        {{ $order->order_type?->label() }} · × {{ $order->quantity }}
                    </div>
                </div>

                <ul class="list-group list-group-flush fs-3">
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Pembeli</span>
                        <span class="text-end">
                            <span class="d-inline-flex align-items-center">
                                @can('manage-users')
                                    <a href="{{ route('admin.users.show', $order->buyer) }}" class="text-decoration-none">
                                        {{ $order->buyer?->name }}
                                    </a>
                                @else
                                    {{ $order->buyer?->name }}
                                @endcan
                                @include('admin.partials._cek_terverifikasi', ['user' => $order->buyer])
                            </span>
                            <div class="text-muted font-monospace" style="font-size: 11px;">{{ $order->buyer?->phone }}</div>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Toko</span>
                        <span class="text-end">
                            <span class="d-inline-flex align-items-center">
                                @if ($order->store?->photo)
                                    <img loading="lazy" decoding="async" src="{{ $order->store->photo }}" alt="" class="rounded me-1"
                                         style="width: 28px; height: 28px; object-fit: cover;">
                                @endif
                                @can('manage-stores')
                                    <a href="{{ route('admin.stores.show', $order->store) }}" class="text-decoration-none">
                                        {{ $order->store?->name }}
                                    </a>
                                @else
                                    {{ $order->store?->name }}
                                @endcan
                                @if ($order->store?->status === \App\Enums\StoreStatus::Verified)
                                    <i class="ti ti-circle-check-filled text-success ms-1 flex-shrink-0"
                                       title="Toko terverifikasi" role="img" aria-label="Toko terverifikasi"></i>
                                @endif
                            </span>
                        </span>
                    </li>
                    {{-- Sumber pesanan: listing XOR penawaran (DATABASE.md §4.7). --}}
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Sumber</span>
                        <span class="text-end">
                            @if ($order->listing)
                                <span class="d-inline-flex align-items-center gap-2">
                                    <img loading="lazy" decoding="async" src="{{ $order->listing->images[0] }}" alt=""
                                         class="rounded border" style="width: 40px; height: 40px; object-fit: cover;">
                                    @can('manage-listings')
                                        <a href="{{ route('admin.listings.show', $order->listing) }}" class="text-decoration-none">
                                            {{ $order->listing->title }}
                                        </a>
                                    @else
                                        {{ $order->listing->title }}
                                    @endcan
                                </span>
                            @elseif ($order->offer?->request)
                                Penawaran untuk
                                @can('manage-requests')
                                    <a href="{{ route('admin.requests.show', $order->offer->request) }}" class="text-decoration-none">
                                        {{ $order->offer->request->title }}
                                    </a>
                                @else
                                    {{ $order->offer->request->title }}
                                @endcan
                            @else
                                —
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Pembayaran</span>
                        <span class="text-end">{{ $order->payment_method?->label() }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Pengiriman</span>
                        <span class="text-end">{{ $order->delivery_method?->label() }}</span>
                    </li>
                    @if ($order->shipping_address)
                        <li class="list-group-item">
                            <div class="text-muted mb-1">Alamat tujuan</div>
                            {{ $order->shipping_address }}
                        </li>
                    @endif
                    @if ($order->notes)
                        <li class="list-group-item">
                            <div class="text-muted mb-1">Catatan pembeli</div>
                            <div style="white-space: pre-line;">{{ $order->notes }}</div>
                        </li>
                    @endif
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Dibuat</span>
                        <span class="text-end">{{ $order->created_at?->format('d M Y H:i') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-lg-8">

            <div class="row">
                {{-- Alur pesanan. --}}
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header fw-semibold">Alur Pesanan</div>
                        <div class="card-body">
                            {{-- Garis vertikal di belakang ikon: pseudo-flow yang bisa dibaca
                                 sekilas dari atas ke bawah tanpa legenda. --}}
                            <ul class="list-unstyled mb-0 fs-3 position-relative"
                                style="padding-inline-start: .25rem; border-inline-start: 2px solid var(--bs-border-color); margin-inline-start: .55rem;">
                                <li class="d-flex gap-2 mb-3">
                                    <i class="ti ti-circle-check text-success mt-1 bg-body flex-shrink-0" style="margin-inline-start: -1.1rem;" aria-hidden="true"></i>
                                    <div>
                                        <div class="fw-semibold">Dibuat</div>
                                        <div class="text-muted" style="font-size: 11px;">
                                            {{ $order->created_at?->format('d M Y H:i') }}
                                        </div>
                                    </div>
                                </li>
                                <li class="d-flex gap-2 mb-3">
                                    @if ($order->payment_confirmed_at)
                                        <i class="ti ti-circle-check text-success mt-1 bg-body flex-shrink-0" style="margin-inline-start: -1.1rem;" aria-hidden="true"></i>
                                        <div>
                                            <div class="fw-semibold">Pembayaran dikonfirmasi</div>
                                            <div class="text-muted" style="font-size: 11px;">
                                                {{ $order->payment_confirmed_at->format('d M Y H:i') }}
                                            </div>
                                        </div>
                                    @else
                                        <i class="ti ti-clock text-muted mt-1 bg-body flex-shrink-0" style="margin-inline-start: -1.1rem;" aria-hidden="true"></i>
                                        <div>
                                            <div class="text-muted">Menunggu konfirmasi pembayaran</div>
                                        </div>
                                    @endif
                                </li>
                                @if ($order->disputes->where('status.value', 'open')->isNotEmpty())
                                    <li class="d-flex gap-2 mb-3">
                                        <i class="ti ti-alert-triangle text-warning mt-1 bg-body flex-shrink-0" style="margin-inline-start: -1.1rem;" aria-hidden="true"></i>
                                        <div>
                                            <div class="fw-semibold text-warning">Ada laporan terbuka</div>
                                            <div class="text-muted" style="font-size: 11px;">
                                                Pesanan sedang ditinjau admin
                                            </div>
                                        </div>
                                    </li>
                                @endif
                                <li class="d-flex gap-2">
                                    @if ($order->status?->value === 'selesai')
                                        <i class="ti ti-circle-check text-success mt-1 bg-body flex-shrink-0" style="margin-inline-start: -1.1rem;" aria-hidden="true"></i>
                                        <div>
                                            <div class="fw-semibold">Selesai</div>
                                            <div class="text-muted" style="font-size: 11px;">
                                                {{ $order->completed_at?->format('d M Y H:i') }}
                                            </div>
                                        </div>
                                    @elseif ($order->status?->value === 'dibatalkan')
                                        <i class="ti ti-circle-x text-danger mt-1 bg-body flex-shrink-0" style="margin-inline-start: -1.1rem;" aria-hidden="true"></i>
                                        <div>
                                            <div class="fw-semibold text-danger">Dibatalkan</div>
                                            <div class="text-muted" style="font-size: 11px;">
                                                {{ $order->cancelled_at?->format('d M Y H:i') }}
                                                @if ($order->cancelledBy)
                                                    · oleh {{ $order->cancelledBy->name }}
                                                @elseif ($order->cancelled_at)
                                                    · oleh sistem
                                                @endif
                                            </div>
                                            @if ($order->cancel_reason)
                                                <div class="text-muted" style="font-size: 11px;">
                                                    Alasan: {{ $order->cancel_reason }}
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <i class="ti ti-clock text-muted mt-1 bg-body flex-shrink-0" style="margin-inline-start: -1.1rem;" aria-hidden="true"></i>
                                        <div>
                                            <div class="text-muted">Dalam proses</div>
                                        </div>
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Bukti pembayaran. --}}
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header fw-semibold">Bukti Pembayaran</div>
                        <div class="card-body">
                            {{-- payment_proof_url accessor selalu mengembalikan
                                 URL (placeholder bila belum ada unggahan). --}}
                            <a href="{{ $order->payment_proof_url }}" target="_blank" rel="noopener"
                               title="Buka ukuran penuh di tab baru">
                                <img loading="lazy" decoding="async" src="{{ $order->payment_proof_url }}" alt="Bukti pembayaran {{ $order->order_number }}"
                                     class="rounded border w-100" style="max-height: 180px; object-fit: cover;">
                            </a>
                            <div class="d-flex align-items-center justify-content-between mt-2 fs-3">
                                <span class="text-muted">{{ $order->payment_method?->label() }}</span>
                                @if ($order->payment_confirmed_at)
                                    <span class="badge bg-success-subtle text-success">
                                        dikonfirmasi {{ $order->payment_confirmed_at->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">menunggu konfirmasi</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Peta tujuan antar — hanya ada untuk pesanan delivery. --}}
            @if ($order->latitude !== null)
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Titik Tujuan Antar</span>
                        <a class="btn btn-sm btn-outline-primary"
                           href="https://www.google.com/maps/search/?api=1&query={{ $order->latitude }},{{ $order->longitude }}"
                           target="_blank" rel="noopener">
                            <i class="ti ti-map-pin" aria-hidden="true"></i> Google Maps
                        </a>
                    </div>
                    <div class="card-body">
                        <div id="petaTujuanAntar" class="rounded border admin-peta-detail"
                             role="img" aria-label="Peta tujuan antar {{ $order->order_number }}"></div>
                        <div class="text-muted fs-2 mt-2 font-monospace">
                            {{ \App\Support\Angka::desimal($order->latitude, 6) }}, {{ \App\Support\Angka::desimal($order->longitude, 6) }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- Laporan atas pesanan ini. --}}
            <div class="card">
                <div class="card-header fw-semibold">Laporan ({{ $order->disputes->count() }})</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 fs-3">
                            <thead>
                                <tr>
                                    <th>Alasan</th>
                                    <th>Pelapor</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($order->disputes as $dispute)
                                    <tr>
                                        <td>{{ $dispute->reason?->label() }}</td>
                                        <td>{{ $dispute->reporter?->name ?? '—' }}</td>
                                        <td>
                                            @if ($dispute->status?->value === 'open')
                                                <span class="badge bg-warning-subtle text-warning">{{ $dispute->status->label() }}</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success">{{ $dispute->status?->label() }}</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">{{ $dispute->created_at?->format('d M Y H:i') }}</td>
                                        <td class="text-end">
                                            @can('manage-disputes')
                                                <a href="{{ route('admin.disputes.show', $dispute) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="ti ti-gavel" aria-hidden="true"></i> Tinjau
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            Tidak ada laporan untuk pesanan ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Ulasan dua arah atas pesanan ini. --}}
            <div class="card">
                <div class="card-header fw-semibold">Ulasan ({{ $order->reviews->count() }})</div>
                <div class="card-body">
                    @forelse ($order->reviews as $review)
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between flex-wrap gap-2 mb-1">
                                <div>
                                    <span class="fw-semibold">{{ $review->reviewer?->name ?? '—' }}</span>
                                    <span class="text-muted fs-2">· {{ $review->direction?->label() }}</span>
                                </div>
                                @include('admin.partials._stars', ['rating' => $review->rating, 'total' => null])
                            </div>
                            @if ($review->comment)
                                <p class="fs-3 mb-0" style="white-space: pre-line;">{{ $review->comment }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted fs-3 mb-0">Belum ada ulasan untuk pesanan ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@if ($order->latitude !== null)
    @push('scripts')
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const wadah = document.getElementById('petaTujuanAntar');
            if (!wadah || typeof L === 'undefined') return;

            const lat = @js((float) $order->latitude);
            const lng = @js((float) $order->longitude);

            const peta = L.map(wadah, { center: [lat, lng], zoom: 15, scrollWheelZoom: false });

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(peta);

            L.marker([lat, lng]).addTo(peta);
        });
    </script>
    @endpush
@endif
