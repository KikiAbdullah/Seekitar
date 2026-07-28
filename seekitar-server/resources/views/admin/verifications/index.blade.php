@extends('admin.layout')
@section('title', 'Verifikasi KTP')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item active" aria-current="page">Verifikasi</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Antrian Verifikasi KTP</h1>

    {{-- Urutan tertua dulu: itulah yang paling dekat melewati SLA 1x24 jam. --}}
    <div class="alert alert-warning py-2">
        SLA peninjauan <strong>1×24 jam</strong>. Diurutkan dari pengajuan terlama.
    </div>

    <div class="card"><div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Nama</th><th>Telepon</th><th>Diajukan</th><th>Menunggu</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($pending as $user)
                @php $overdue = $user->ktp_submitted_at?->diffInHours(now()) >= 24; @endphp
                <tr class="{{ $overdue ? 'table-danger' : '' }}">
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->phone }}</td>
                    <td>{{ $user->ktp_submitted_at?->format('d M Y H:i') }}</td>
                    <td>
                        {{ $user->ktp_submitted_at?->diffForHumans(null, true) }}
                        @if ($overdue)
                            <span class="badge text-bg-danger">Lewat SLA</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('admin.verifications.users.approve', $user) }}"
                              method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success">Setujui</button>
                        </form>

                        <button type="button" class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal" data-bs-target="#reject-{{ $user->id }}">Tolak</button>

                        <div class="modal fade" id="reject-{{ $user->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form class="modal-content" method="POST"
                                      action="{{ route('admin.verifications.users.reject', $user) }}">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Tolak verifikasi {{ $user->name }}</h5>
                                        <button type="button" class="btn-close"
                                                data-bs-dismiss="modal" aria-label="Tutup"></button>
                                    </div>
                                    <div class="modal-body">
                                        <label for="vreason-{{ $user->id }}" class="form-label">
                                            Alasan penolakan
                                        </label>
                                        {{-- Wajib: tanpa alasan, pengguna akan
                                             mengirim ulang berkas yang sama. --}}
                                        <textarea id="vreason-{{ $user->id }}" name="reason"
                                                  class="form-control" rows="3"
                                                  required maxlength="500"></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-danger">Tolak</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">
                    Tidak ada pengajuan menunggu.
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>

    <div class="mt-3">{{ $pending->links() }}</div>
@endsection
