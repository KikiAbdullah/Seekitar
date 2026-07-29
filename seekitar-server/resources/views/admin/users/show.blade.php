@extends('admin.layout')
@section('title', 'Detail Pengguna')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">{{ $user->name }}</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.users.index') }}">Pengguna</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Detail</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        {{-- Profil & aksi. --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    @if ($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="Foto profil {{ $user->name }}"
                             width="96" height="96" class="rounded-circle mb-3" style="object-fit: cover;">
                    @else
                        <span class="rounded-circle bg-light-primary text-primary fw-bold d-inline-flex align-items-center justify-content-center fs-8 mb-3"
                              style="width: 96px; height: 96px;" aria-hidden="true">
                            {{ $user->initials }}
                        </span>
                    @endif

                    <h5 class="fw-semibold mb-1">{{ $user->name }}</h5>
                    <div class="font-monospace text-muted mb-2">{{ $user->phone }}</div>

                    <div class="d-flex flex-wrap justify-content-center gap-2 mb-2">
                        <span class="badge bg-primary-subtle text-primary">
                            Level {{ $user->verification_level->value }} · {{ $user->verification_level->label() }}
                        </span>
                        @if ($user->is_blocked)
                            <span class="badge bg-danger-subtle text-danger">Diblokir</span>
                        @else
                            <span class="badge bg-success-subtle text-success">Aktif</span>
                        @endif
                    </div>

                    {{-- Reputasi pembeli dari ulasan toko → pembeli. --}}
                    <div class="mb-3">
                        @if ((int) $user->total_reviews > 0)
                            @include('admin.partials._stars', [
                                'rating' => $user->rating_avg,
                                'total'  => $user->total_reviews,
                            ])
                        @else
                            <span class="text-muted fs-2">Belum ada ulasan sebagai pembeli</span>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        @include('admin.users._actions', ['user' => $user])
                    </div>
                </div>

                <ul class="list-group list-group-flush fs-3">
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Alamat</span>
                        <span class="text-end">{{ $user->address ?? '—' }}</span>
                    </li>
                    @if ($user->latitude !== null)
                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="text-muted">Koordinat</span>
                            <span class="font-monospace text-end">
                                {{ number_format((float) $user->latitude, 6) }}, {{ number_format((float) $user->longitude, 6) }}
                            </span>
                        </li>
                    @endif
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Bergabung</span>
                        <span class="text-end">{{ $user->created_at->format('d M Y') }}</span>
                    </li>
                    @if ($user->is_blocked)
                        <li class="list-group-item">
                            <div class="text-muted mb-1">Alasan blokir · {{ $user->blocked_at?->format('d M Y H:i') }}</div>
                            <span class="text-danger">{{ $user->blocked_reason }}</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="col-lg-8">

            {{-- Statistik ringkas aktivitasnya. --}}
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

            {{-- Jejak verifikasi dua tahap. --}}
            <div class="card">
                <div class="card-header fw-semibold">Verifikasi Identitas</div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 fs-3">
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="ti {{ $user->verified1_at ? 'ti-circle-check text-success' : 'ti-clock-hour-4 text-warning' }} mt-1" aria-hidden="true"></i>
                            <span>
                                <strong>Tahap 1 · Nomor HP</strong><br>
                                @if ($user->verified1_at)
                                    {{ $user->verified1By?->name ?? '—' }} · {{ $user->verified1_at->format('d M Y H:i') }}
                                @else
                                    <span class="text-muted">Belum diverifikasi</span>
                                @endif
                            </span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="ti {{ $user->verified2_at ? 'ti-circle-check text-success' : 'ti-clock-hour-4 text-warning' }} mt-1" aria-hidden="true"></i>
                            <span>
                                <strong>Tahap 2 · KTP &amp; NIK</strong><br>
                                @if ($user->verified2_at)
                                    {{ $user->verified2By?->name ?? '—' }} · {{ $user->verified2_at->format('d M Y H:i') }}
                                @elseif ($user->ktp_submitted_at)
                                    <span class="text-muted">Menunggu — diajukan {{ $user->ktp_submitted_at->format('d M Y H:i') }}</span>
                                @else
                                    <span class="text-muted">Belum mengajukan</span>
                                @endif
                            </span>
                        </li>
                    </ul>

                    @if ($user->ktp_rejected_reason)
                        <div class="alert alert-warning py-2 fs-3 mt-3 mb-0">
                            Pernah ditolak: {{ $user->ktp_rejected_reason }}
                        </div>
                    @endif
                </div>

                {{-- Berkas identitas adalah hak `verify-users`, bukan
                     `manage-users`. Ditampilkan hanya bila izinnya ada;
                     route medianya sendiri juga memagarainya (UU PDP). --}}
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
                                    <a href="{{ route('admin.verifications.users.media', [$user, 'selfie']) }}" target="_blank" rel="noopener">
                                        <img loading="lazy" decoding="async" src="{{ route('admin.verifications.users.media', [$user, 'selfie']) }}"
                                             alt="Foto wajah {{ $user->name }}" class="img-fluid rounded border"
                                             style="max-height: 160px; object-fit: cover;">
                                    </a>
                                @else
                                    <div class="border rounded text-muted text-center py-3 small">Belum diunggah</div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted fw-semibold mb-1" style="font-size: 11px;">FOTO KTP</div>
                                @if ($user->ktp_image)
                                    <a href="{{ route('admin.verifications.users.media', [$user, 'ktp']) }}" target="_blank" rel="noopener">
                                        <img loading="lazy" decoding="async" src="{{ route('admin.verifications.users.media', [$user, 'ktp']) }}"
                                             alt="KTP {{ $user->name }}" class="img-fluid rounded border"
                                             style="max-height: 160px; object-fit: cover;">
                                    </a>
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
                                <img loading="lazy" decoding="async" src="{{ $store->photo }}" alt="" width="56" height="42"
                                     class="rounded border" style="object-fit: cover;">
                            @else
                                <span class="rounded bg-light-primary text-primary d-inline-flex align-items-center justify-content-center"
                                      style="width: 56px; height: 42px;" aria-hidden="true">
                                    <i class="ti ti-building-store"></i>
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
                            @if ($store->verification_status->value === 'verified')
                                <span class="badge bg-success-subtle text-success">{{ $store->verification_status->label() }}</span>
                            @elseif ($store->verification_status->value === 'pending')
                                <span class="badge bg-warning-subtle text-warning">{{ $store->verification_status->label() }}</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">{{ $store->verification_status->label() }}</span>
                            @endif
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
@endsection
