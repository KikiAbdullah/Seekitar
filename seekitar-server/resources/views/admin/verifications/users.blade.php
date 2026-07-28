@extends('admin.layout')
@section('title', 'Verifikasi Pengguna')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item">Verifikasi</li>
    <li class="breadcrumb-item active" aria-current="page">Pengguna</li>
@endsection

@section('content')

    <div class="alert alert-warning py-2 d-flex align-items-center gap-2" role="alert">
        <i class="ti ti-clock-hour-4" aria-hidden="true"></i>
        <span>
            SLA peninjauan <strong>1×24 jam</strong> (PRD §5.3.2).
            Diurutkan dari pengajuan <strong>terlama</strong> — itulah yang paling dekat melewati batas.
        </span>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Antrian KTP</span>
            <span class="badge text-bg-secondary">{{ $pending->total() }} menunggu</span>
        </div>

        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th scope="col">Nama</th>
                        <th scope="col">Telepon</th>
                        <th scope="col">Diajukan</th>
                        <th scope="col">Menunggu</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($pending as $user)
                    @php
                        // Dihitung sekali; dipakai untuk warna baris DAN badge.
                        // Kalau dihitung dua kali, keduanya bisa berbeda di
                        // detik pergantian jam.
                        $lewatSla = $user->ktp_submitted_at !== null
                            && $user->ktp_submitted_at->diffInHours(now()) >= 24;
                    @endphp

                    <tr @class(['table-danger' => $lewatSla])>
                        <td>
                            <div class="fw-semibold">{{ $user->name }}</div>
                            @if ($user->ktp_rejected_reason)
                                <div class="text-muted" style="font-size: 12px;">
                                    Pernah ditolak: {{ Str::limit($user->ktp_rejected_reason, 60) }}
                                </div>
                            @endif
                        </td>
                        <td class="text-nowrap">{{ $user->phone }}</td>
                        <td class="text-nowrap">{{ $user->ktp_submitted_at?->format('d M Y H:i') }}</td>
                        <td class="text-nowrap">
                            {{ $user->ktp_submitted_at?->diffForHumans(null, true) }}
                            @if ($lewatSla)
                                <span class="badge text-bg-danger">Lewat SLA</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <form action="{{ route('admin.verifications.users.approve', $user) }}"
                                  method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="ti ti-circle-check" aria-hidden="true"></i> Setujui
                                </button>
                            </form>

                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal" data-bs-target="#tolakUser{{ $loop->index }}">
                                Tolak
                            </button>

                            @include('admin.verifications._reject_modal', [
                                'modalId' => 'tolakUser'.$loop->index,
                                'judul'   => 'Tolak verifikasi '.$user->name,
                                'action'  => route('admin.verifications.users.reject', $user),
                                'catatan' => 'Pengguna tetap di Level 1 dan bisa mengirim ulang berkas.',
                            ])
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
@endsection
