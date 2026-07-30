@extends('admin.layout')
@section('title', 'Verifikasi Toko')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">Verifikasi Toko</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Verifikasi</li>
                            <li class="breadcrumb-item active" aria-current="page">Toko</li>
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
                <p class="mb-0 fs-3">Antrian ini <strong>hanya memuat toko yang pemiliknya sudah terverifikasi identitas</strong> — yang belum, dinilai dulu di Verifikasi Pengguna. <strong>Klik baris</strong> untuk membuka berkas (foto toko &amp; peta lokasi) dan tombol Verifikasi.</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Antrian Pengajuan Toko</span>
            <span class="badge text-bg-secondary">{{ $pending->total() }} menunggu</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th scope="col" style="min-width: 220px;">Toko</th>
                        <th scope="col" class="text-nowrap">Pemilik</th>
                        <th scope="col" class="text-nowrap">Jenis</th>
                        <th scope="col" class="text-nowrap">Lokasi</th>
                        <th scope="col" class="text-nowrap">Menunggu</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($pending as $store)
                    @php
                        // Dihitung sekali; dipakai untuk warna baris DAN badge.
                        $lewatSla = $store->created_at->diffInHours(now()) >= 24;
                    @endphp

                    {{-- Tidak ada kolom aksi: SELURUH baris adalah pemicu modal. --}}
                    <tr @class(['table-danger' => $lewatSla, 'cursor-pointer'])
                        data-bs-toggle="modal" data-bs-target="#verifikasiToko{{ $loop->index }}"
                        role="button" tabindex="0"
                        aria-label="Buka berkas verifikasi {{ $store->name }}">

                        {{-- Toko: foto + nama + alamat singkat. --}}
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if ($store->photo)
                                    <a href="{{ $store->photo }}" data-lightbox="verif-store-list-{{ $store->id }}"
                                       data-title="Foto {{ $store->name }}">
                                        <img loading="lazy" decoding="async" src="{{ $store->photo }}" alt="Foto {{ $store->name }}"
                                             width="52" height="40" class="rounded border flex-shrink-0"
                                             style="object-fit: cover; cursor: pointer;">
                                    </a>
                                @else
                                    <span class="rounded bg-light-primary text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                          style="width: 52px; height: 40px;" aria-hidden="true">
                                        <i class="ti ti-building-store" aria-hidden="true"></i>
                                    </span>
                                @endif

                                <div class="lh-sm">
                                    <div class="fw-semibold mb-1">{{ $store->name }}</div>
                                    <div class="text-muted" style="font-size: 12px;">
                                        {{ Str::limit($store->address ?? $store->regency, 50) }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Lencana merah = TIDAK BISA disetujui (pemilik belum
                             terverifikasi) — terlihat sebelum modal dibuka. --}}
                        <td>
                            <div class="lh-sm">
                                <div class="fw-semibold mb-1 d-inline-flex align-items-center">
                                    {{ $store->owner?->name ?? '—' }}
                                    @if ($store->owner)
                                        @include('admin.partials._cek_terverifikasi', ['user' => $store->owner])
                                    @endif
                                </div>
                                <div class="text-muted font-monospace" style="font-size: 12px;">{{ $store->owner?->phone }}</div>
                                {{-- Antrian sudah menyaring pemilik terverifikasi di SQL (poin 1),
                                     jadi lencananya selalu hijau — bukan kebetulan desain. --}}
                                <span class="badge bg-success-subtle text-success mt-1" style="font-size: 11px;">
                                    <i class="ti ti-circle-check" aria-hidden="true"></i>
                                    Pemilik terverifikasi
                                </span>
                            </div>
                        </td>

                        {{-- Jenis usaha (SET): bisa lebih dari satu. --}}
                        <td class="text-nowrap">
                            @foreach ($store->store_type ?? [] as $tipe)
                                <span class="badge text-bg-light border">{{ $tipe->label() }}</span>
                            @endforeach
                        </td>

                        {{-- Lokasi: koordinat + radius + jarak dari pusat kabupaten —
                             pin yang ke luar wilayah langsung menyolok di sini. --}}
                        <td class="text-nowrap">
                            @if ($store->latitude !== null)
                                @php
                                    $jarakPusat = \App\Support\Jarak::haversineKm(
                                        \Database\Factories\Support\Wilayah::PUSAT_LAT,
                                        \Database\Factories\Support\Wilayah::PUSAT_LNG,
                                        (float) $store->latitude, (float) $store->longitude);
                                @endphp
                                <div class="lh-sm">
                                    <div class="font-monospace" style="font-size: 12px;">
                                        {{ \App\Support\Angka::desimal($store->latitude, 5) }}, {{ \App\Support\Angka::desimal($store->longitude, 5) }}
                                    </div>
                                    <div class="text-muted" style="font-size: 12px;">
                                        Radius {{ rtrim(rtrim(\App\Support\Angka::desimal($store->service_radius_km, 2), '0'), ',') }} km · {{ \App\Support\Angka::desimal($jarakPusat, 1) }} km dari pusat
                                    </div>
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- Menunggu: dihitung dari pengajuan, >24 jam = lewat SLA. --}}
                        <td class="text-nowrap">
                            <div class="lh-sm">
                                <div class="fw-semibold">{{ $store->created_at->diffForHumans(null, true) }}</div>
                                @if ($lewatSla)
                                    <span class="badge text-bg-danger mt-1">Lewat SLA</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="ti ti-circle-check fs-7 d-block mb-2 opacity-25" aria-hidden="true"></i>
                            Tidak ada pengajuan toko menunggu.<br>
                            <span style="font-size: 12px;">
                                Pengajuan yang pemiliknya belum terverifikasi tidak tampil di sini —
                                selesaikan dulu di <a href="{{ route('admin.verifications.users') }}">Verifikasi Pengguna</a>.
                            </span>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $pending->links() }}</div>

    {{-- Modal DI LUAR tabel: modal di dalam <td> bisa terpotong / tertutup
         baris lain karena konteks stacking milik sel. --}}
    @foreach ($pending as $store)
        @include('admin.verifications._store_modal', ['store' => $store, 'index' => $loop->index])
    @endforeach

    @include('admin.partials._lightbox')
@endsection

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
    /*
     * Peta kecil tiap modal dibuat SEKALI saat modal pertama kali dibuka
     * (bukan saat halaman dimuat): 20 peta tersembunyi memaksa unduhan
     * ratusan tile untuk sesuatu yang mungkin tidak pernah diklik.
     * invalidateSize() wajib dipanggil setelah modal tampil — Leaflet
     * membaca ukuran kontainer yang sebelumnya display:none sebagai nol.
     */
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-peta-toko]').forEach(function (wadah) {
            const modal = wadah.closest('.modal');
            if (!modal) return;

            let peta = null;

            modal.addEventListener('shown.bs.modal', function () {
                if (typeof L === 'undefined') return;

                if (!peta) {
                    const lat    = parseFloat(wadah.dataset.lat);
                    const lng    = parseFloat(wadah.dataset.lng);
                    const radius = parseFloat(wadah.dataset.radius || '0');

                    peta = L.map(wadah, {
                        center: [lat, lng],
                        zoom: 14,
                        scrollWheelZoom: false,
                    });

                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 18,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(peta);

                    L.marker([lat, lng]).addTo(peta);

                    // Lingkaran radius layanan: klaim "melayani 5 km" bisa
                    // dibandingkan langsung dengan letak tokonya.
                    if (radius > 0) {
                        L.circle([lat, lng], {
                            radius: radius * 1000,
                            color: '#5d87ff',
                            weight: 1,
                            fillOpacity: 0.06,
                        }).addTo(peta);
                    }
                }

                peta.invalidateSize();
            });
        });

        // Baris yang mendapat fokus keyboard harus bisa dibuka dengan
        // Enter/Spasi — data-bs-toggle hanya merespons klik mouse.
        document.querySelectorAll('tr[data-bs-toggle="modal"]').forEach(function (row) {
            row.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    row.click();
                }
            });
        });
    });
</script>
{{-- Gerbang checklist SOP dipakai bersama antrian pengguna (lihat
     public/js/checklist-gate.js — jangan duplikasi logikanya di sini). --}}
<script src="{{ asset('js/checklist-gate.js') }}"></script>
@endpush
