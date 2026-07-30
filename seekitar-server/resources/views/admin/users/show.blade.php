@extends('admin.layout')
@section('title', 'Detail Pengguna')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">Detail Pengguna</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.users.index') }}">Pengguna</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">{{ $user->name }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        {{-- Identitas & aksi utama dalam satu kartu: semua keputusan admin
             atas akun ini (sunting, blokir) berangkat dari sini. --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center pb-0">
                    @if ($user->avatar_url)
                        <a href="{{ $user->avatar_url }}" data-lightbox data-title="Foto {{ $user->name }}">
                            <img src="{{ $user->avatar_url }}" alt="Foto profil {{ $user->name }}"
                                 width="104" height="104" class="rounded-circle mb-3 border border-2 border-white shadow-sm"
                                 style="object-fit: cover; cursor: pointer;">
                        </a>
                    @else
                        <span class="rounded-circle bg-light-primary text-primary fw-bold d-inline-flex align-items-center justify-content-center fs-8 mb-3"
                              style="width: 104px; height: 104px;" aria-hidden="true">
                            {{ $user->initials }}
                        </span>
                    @endif

                    <h5 class="fw-semibold mb-1 d-inline-flex align-items-center">
                        {{ $user->name }}
                        @include('admin.partials._cek_terverifikasi', ['user' => $user])
                    </h5>
                    <div class="font-monospace text-muted mb-3">{{ $user->phone }}</div>

                    <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                        @include('admin.users._status', ['user' => $user])
                        @if ((int) $user->total_reviews > 0)
                            @include('admin.partials._stars', [
                                'rating' => $user->rating_avg,
                                'total'  => $user->total_reviews,
                            ])
                        @endif
                    </div>
                </div>

                <div class="card-body d-flex flex-wrap justify-content-center gap-2 border-top">
                    @include('admin.users._actions', ['user' => $user])
                </div>

                <ul class="list-group list-group-flush fs-3 border-top">
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Email</span>
                        <span class="text-end">{{ $user->email ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Bergabung</span>
                        <span class="text-end">{{ $user->created_at->format('d M Y') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Alamat</span>
                        <span class="text-end">{{ $user->address ?? '—' }}</span>
                    </li>
                    @if ($user->latitude !== null)
                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="text-muted">Koordinat</span>
                            <span class="font-monospace text-end">
                                {{ \App\Support\Angka::desimal($user->latitude, 6) }}, {{ \App\Support\Angka::desimal($user->longitude, 6) }}
                            </span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="col-lg-8">

            <div class="row">
                <div class="col-4">
                    <div class="card"><div class="card-body py-3 text-center">
                        <div class="fs-7 fw-bold">{{ $user->stores_count }}</div>
                        <div class="text-muted fs-2">Toko</div>
                    </div></div>
                </div>
                <div class="col-4">
                    <div class="card"><div class="card-body py-3 text-center">
                        <div class="fs-7 fw-bold">{{ $user->customer_requests_count }}</div>
                        <div class="text-muted fs-2">Permintaan</div>
                    </div></div>
                </div>
                <div class="col-4">
                    <div class="card"><div class="card-body py-3 text-center">
                        <div class="fs-7 fw-bold">{{ $user->orders_count }}</div>
                        <div class="text-muted fs-2">Pesanan</div>
                    </div></div>
                </div>
            </div>

            {{-- Keadaan saat ini di satu pandang, lalu jejak auditnya — status
                 ditolak/diblokir sengaja paling mencolok. --}}
            @if ($user->isBlocked())
                <div class="alert alert-dark d-flex align-items-start gap-3" role="alert">
                    <i class="ti ti-ban fs-5 mt-1" aria-hidden="true"></i>
                    <div>
                        <strong>Diblokir</strong>
                        oleh {{ $user->blockedBy?->name ?? '—' }} · {{ $user->blocked_at?->format('d M Y H:i') }}<br>
                        {{ $user->blocked_reason }}<br>
                        <span class="text-muted fs-2">Seluruh token dicabut dan tokonya ikut dinonaktifkan selama blokir berlangsung.</span>
                    </div>
                </div>
            @elseif ($user->rejected_at)
                <div class="alert alert-warning d-flex align-items-start gap-3" role="alert">
                    <i class="ti ti-alert-triangle fs-5 mt-1" aria-hidden="true"></i>
                    <div>
                        <strong>Berkas ditolak</strong>
                        oleh {{ $user->rejectedBy?->name ?? '—' }} · {{ $user->rejected_at->format('d M Y H:i') }}<br>
                        {{ $user->rejected_reason }}<br>
                        <span class="text-muted fs-2">Pengguna bisa mengirim ulang berkas kapan saja — antrian terbuka lagi saat itu.</span>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header fw-semibold">Jejak Verifikasi Identitas</div>
                <div class="card-body">
                    {{-- Garis waktu tunggal: SATU verifikasi — nomor HP bukan
                         "tahap"; bukti OTP-nya keberadaan akun itu sendiri. --}}
                    <ul class="list-unstyled mb-0 fs-3">
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="ti ti-device-mobile-check text-success mt-1" aria-hidden="true"></i>
                            <span>
                                <strong>Nomor HP dibuktikan OTP</strong><br>
                                <span class="text-muted">Kode hanya dikirim ke nomornya sendiri — {{ $user->phone }}</span>
                            </span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="ti {{ $user->verified_at ? 'ti-circle-check text-success' : 'ti-clock-hour-4 text-warning' }} mt-1" aria-hidden="true"></i>
                            <span>
                                <strong>Identitas (wajah, KTP, alamat, titik domisili)</strong><br>
                                @if ($user->verified_at)
                                    {{ $user->verifiedBy?->name ?? '—' }} · {{ $user->verified_at->format('d M Y H:i') }}
                                @elseif ($user->ktp_submitted_at)
                                    <span class="text-muted">Menunggu peninjauan — berkas diajukan {{ $user->ktp_submitted_at->format('d M Y H:i') }}</span>
                                @else
                                    <span class="text-muted">Belum mengajukan berkas</span>
                                @endif
                            </span>
                        </li>
                    </ul>
                </div>

                {{-- Berkas identitas = hak `verify-users` (UU PDP); route
                     medianya sendiri juga memagari izin yang sama. --}}
                @can('verify-users')
                    <div class="card-body border-top">
                        <dl class="row mb-3 fs-3">
                            <dt class="col-sm-3">NIK</dt>
                            <dd class="col-sm-9 font-monospace">{{ $user->nik ?? '— (belum diisi)' }}</dd>
                        </dl>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted fw-semibold mb-1" style="font-size: 11px;">FOTO WAJAH</div>
                                @if ($user->selfie_image)
                                    @php
                                        $selfiePath = $user->selfie_image;
                                        $selfieExists = \Illuminate\Support\Facades\Storage::disk('local')->exists($selfiePath);
                                    @endphp
                                    @if ($selfieExists)
                                        <a href="{{ route('admin.verifications.users.media', [$user, 'selfie']) }}"
                                           data-lightbox="foto-wajah"
                                           data-title="Foto Wajah {{ $user->name }}">
                                            <img src="{{ route('admin.verifications.users.media', [$user, 'selfie']) }}"
                                                 alt="Foto wajah {{ $user->name }}" class="img-fluid rounded border"
                                                 style="max-height: 160px; object-fit: cover; background: #f0f0f0; cursor: pointer;"
                                                 onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                                            <div class="border rounded text-muted text-center py-3 small" style="display:none">Gambar tidak dapat dimuat</div>
                                        </a>
                                    @else
                                        <div class="border rounded text-muted text-center py-3 small">Berkas tidak ditemukan di penyimpanan</div>
                                    @endif
                                @else
                                    <div class="border rounded text-muted text-center py-3 small">Belum diunggah</div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted fw-semibold mb-1" style="font-size: 11px;">FOTO KTP</div>
                                @if ($user->ktp_image)
                                    @php
                                        $ktpPath = $user->ktp_image;
                                        $ktpExists = \Illuminate\Support\Facades\Storage::disk('local')->exists($ktpPath);
                                    @endphp
                                    @if ($ktpExists)
                                        <a href="{{ route('admin.verifications.users.media', [$user, 'ktp']) }}"
                                           data-lightbox="foto-ktp"
                                           data-title="KTP {{ $user->name }}">
                                            <img src="{{ route('admin.verifications.users.media', [$user, 'ktp']) }}"
                                                 alt="KTP {{ $user->name }}" class="img-fluid rounded border"
                                                 style="max-height: 160px; object-fit: cover; background: #f0f0f0; cursor: pointer;"
                                                 onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                                            <div class="border rounded text-muted text-center py-3 small" style="display:none">Gambar tidak dapat dimuat</div>
                                        </a>
                                    @else
                                        <div class="border rounded text-muted text-center py-3 small">Berkas tidak ditemukan di penyimpanan</div>
                                    @endif
                                @else
                                    <div class="border rounded text-muted text-center py-3 small">Belum diunggah</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    @if ($user->ktp_image || $user->nik)
                        <div class="card-body border-top fs-2 text-muted">
                            Berkas identitas (NIK, foto KTP/selfie) tersembunyi — memerlukan izin verifikasi pengguna.
                        </div>
                    @endif
                @endcan
            </div>

            @if ($user->latitude !== null)
                <div class="card">
                    <div class="card-header fw-semibold">Titik Domisili</div>
                    <div class="card-body">
                        <div class="rounded border" style="height: 260px; width: 100%;"
                             data-peta-user
                             data-lat="{{ $user->latitude }}"
                             data-lng="{{ $user->longitude }}"
                             role="img" aria-label="Peta titik domisili {{ $user->name }}"></div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                            <span class="font-monospace text-muted fs-2 me-auto">
                                {{ \App\Support\Angka::desimal($user->latitude, 6) }}, {{ \App\Support\Angka::desimal($user->longitude, 6) }}
                            </span>
                            <a class="btn btn-sm btn-outline-primary"
                               href="https://www.google.com/maps/search/?api=1&query={{ $user->latitude }},{{ $user->longitude }}"
                               target="_blank" rel="noopener">
                                <i class="ti ti-map-pin" aria-hidden="true"></i>
                                Buka di Google Maps
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Toko milik pengguna ini. --}}
    <div class="card">
        <div class="card-header fw-semibold">Toko ({{ $user->stores_count }})</div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <tbody>
                @forelse ($user->stores as $store)
                    <tr>
                        <td style="width: 72px;">
                            @if ($store->photo)
                                <a href="{{ $store->photo }}" data-lightbox="toko-{{ $store->id }}"
                                   data-title="Foto {{ $store->name }}">
                                    <img loading="lazy" decoding="async" src="{{ $store->photo }}" alt="" width="56" height="42"
                                         class="rounded border" style="object-fit: cover; cursor: pointer;">
                                </a>
                            @else
                                <span class="rounded bg-light-primary text-primary d-inline-flex align-items-center justify-content-center"
                                      style="width: 56px; height: 42px;" aria-hidden="true">
                                    <i class="ti ti-building-store" aria-hidden="true"></i>
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">
                                @can('manage-stores')
                                    <a href="{{ route('admin.stores.show', $store) }}" class="text-decoration-none">
                                        {{ $store->name }}
                                    </a>
                                @else
                                    {{ $store->name }}
                                @endcan
                            </div>
                            @if ((int) $store->total_reviews > 0)
                                @include('admin.partials._stars', [
                                    'rating' => $store->rating_avg,
                                    'total'  => $store->total_reviews,
                                ])
                            @endif
                            <div class="text-muted" style="font-size: 12px;">sejak {{ $store->created_at->format('d M Y') }}</div>
                        </td>
                        <td class="text-end text-nowrap">
                            <span class="badge bg-{{ $store->status->color() }}-subtle text-{{ $store->status->color() }}">{{ $store->status->label() }}</span>
                            @unless ($store->is_active)
                                <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center text-muted py-4">Belum punya toko.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @include('admin.partials._lightbox')
@endsection

@push('scripts')
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script>
        // Peta titik domisili statis: penanda saja, tanpa interaksi apa pun
        // — konteksnya melihat & memastikan, bukan mengubah (itu di Sunting).
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-peta-user]').forEach(function (wadah) {
                if (typeof L === 'undefined') return;
                const lat = parseFloat(wadah.dataset.lat);
                const lng = parseFloat(wadah.dataset.lng);

                const peta = L.map(wadah, {
                    center: [lat, lng],
                    zoom: 15,
                    scrollWheelZoom: false,
                });
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(peta);
                L.marker([lat, lng]).addTo(peta);
            });
        });
    </script>
@endpush
