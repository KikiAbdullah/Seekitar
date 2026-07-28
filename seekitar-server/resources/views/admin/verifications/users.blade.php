@extends('admin.layout')
@section('title', 'Verifikasi Pengguna')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">Verifikasi Pengguna</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Verifikasi</li>
                            <li class="breadcrumb-item active" aria-current="page">Pengguna</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>


    <div class="card bg-light-warning shadow-none border-0 mb-4">
        <div class="card-body py-3 px-4">
            <div class="d-flex align-items-start gap-3">
                <i class="ti ti-clock-hour-4 fs-6 text-warning mt-1" aria-hidden="true"></i>
                <p class="mb-0 fs-3">SLA peninjauan <strong>1&times;24 jam</strong> — diurutkan dari pengajuan terlama. <strong>Klik baris</strong> untuk membuka berkas (foto orang &amp; KTP) dan tombol Verifikasi.</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Antrian Verifikasi Pengguna</span>
            <span class="badge text-bg-secondary">{{ $pending->total() }} menunggu</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th scope="col" style="min-width: 220px;">Pengguna</th>
                        <th scope="col" class="text-nowrap">Nomor HP</th>
                        <th scope="col" class="text-nowrap">
                            <span class="badge rounded-pill bg-primary-subtle text-primary me-1">1</span>Nomor
                        </th>
                        <th scope="col" class="text-nowrap">
                            <span class="badge rounded-pill bg-primary-subtle text-primary me-1">2</span>KTP &amp; NIK
                        </th>
                        <th scope="col" class="text-nowrap">Menunggu</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($pending as $user)
                    @php
                        /*
                         * Dihitung sekali; dipakai untuk warna baris DAN badge.
                         * Setiap baris antrian pasti punya ktp_submitted_at
                         * (demikian definisi scope pendingVerification).
                         */
                        $menungguSejak = $user->ktp_submitted_at;
                        $lewatSla = $menungguSejak->diffInHours(now()) >= 24;
                        $tahap = $user->nextVerificationStep();
                    @endphp

                    {{-- Tidak ada kolom aksi: SELURUH baris adalah pemicu modal. --}}
                    <tr @class(['table-danger' => $lewatSla, 'cursor-pointer'])
                        data-bs-toggle="modal" data-bs-target="#verifikasiUser{{ $loop->index }}"
                        role="button" tabindex="0"
                        aria-label="Buka berkas verifikasi {{ $user->name }}">

                        {{-- Pengguna: foto user + nama + NIK + tahap berikutnya. --}}
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if ($user->avatar_url)
                                    <img loading="lazy" decoding="async" src="{{ $user->avatar_url }}" alt="" width="44" height="44"
                                         class="rounded-circle flex-shrink-0" style="object-fit: cover;">
                                @else
                                    <span class="rounded-circle bg-light-primary text-primary fw-semibold d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                          style="width: 44px; height: 44px;" aria-hidden="true">
                                        {{ Str::upper(Str::substr($user->name, 0, 1)) }}
                                    </span>
                                @endif

                                <div class="lh-sm">
                                    <div class="fw-semibold mb-1">{{ $user->name }}</div>
                                    <div class="font-monospace text-muted" style="font-size: 12px;">
                                        NIK {{ $user->nik ?? '—' }}
                                    </div>
                                    @if ($tahap !== null)
                                        <span class="badge bg-primary-subtle text-primary mt-1">
                                            Tahap {{ $tahap }} · {{ \App\Models\User::verificationStepLabel($tahap) }}
                                        </span>
                                    @endif
                                    @if ($user->ktp_rejected_reason)
                                        <div class="text-danger mt-1" style="font-size: 12px;">
                                            Pernah ditolak: {{ Str::limit($user->ktp_rejected_reason, 50) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Nomor HP: salah satu data kunci validasi KTP. --}}
                        <td class="text-nowrap font-monospace">{{ $user->phone }}</td>

                        {{-- Tahap 1: nomor HP (verified1_by / verified1_at). --}}
                        <td class="text-nowrap">
                            @if ($user->verified1_at)
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ti ti-circle-check text-success fs-5" aria-hidden="true"></i>
                                    <div class="lh-sm">
                                        <div class="fw-semibold">{{ $user->verified1By?->name ?? '—' }}</div>
                                        <div class="text-muted" style="font-size: 12px;">{{ $user->verified1_at->format('d M Y H:i') }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="badge bg-warning-subtle text-warning">Menunggu</span>
                            @endif
                        </td>

                        {{-- Tahap 2: KTP & NIK (verified2_by / verified2_at). --}}
                        <td class="text-nowrap">
                            @if ($user->verified2_at)
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ti ti-circle-check text-success fs-5" aria-hidden="true"></i>
                                    <div class="lh-sm">
                                        <div class="fw-semibold">{{ $user->verified2By?->name ?? '—' }}</div>
                                        <div class="text-muted" style="font-size: 12px;">{{ $user->verified2_at->format('d M Y H:i') }}</div>
                                    </div>
                                </div>
                            @elseif ($user->verified1_at)
                                <span class="badge bg-warning-subtle text-warning">Menunggu</span>
                            @else
                                <span class="text-muted" title="Selesaikan tahap 1 dulu">—</span>
                            @endif
                        </td>

                        {{-- Menunggu: dihitung dari pengajuan, >24 jam = lewat SLA. --}}
                        <td class="text-nowrap">
                            <div class="lh-sm">
                                <div class="fw-semibold">{{ $menungguSejak->diffForHumans(null, true) }}</div>
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
                            Tidak ada pengajuan menunggu.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $pending->links() }}</div>

    {{--
        Modal dirender DI LUAR tabel: elemen <tr>/<td> punya konteks
        stacking sendiri, dan modal yang menumpuk di dalam sel bisa
        terpotong atau tertutup baris lain.
    --}}
    @foreach ($pending as $user)
        @include('admin.verifications._user_modal', ['user' => $user, 'index' => $loop->index])
    @endforeach

    <script>
        /*
         * data-bs-toggle Bootstrap hanya merespons klik mouse. Baris yang
         * mendapat fokus keyboard (tabindex="0") harus bisa dibuka dengan
         * Enter/Spasi seperti tombol sungguhan.
         */
        document.addEventListener('DOMContentLoaded', function () {
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
@endsection
