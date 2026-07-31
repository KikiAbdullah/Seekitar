@extends('admin.layouts.admin')

@section('title', 'Antrian Verifikasi KTP — Seekitar')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card w-100 shadow-sm border-0">
        <div class="card-body p-4">
          <div class="d-md-flex align-items-center justify-content-between mb-4">
            <div>
              <h4 class="card-title">Antrian Verifikasi KTP (Identitas)</h4>
              <p class="card-subtitle">Daftar pengguna yang mengajukan verifikasi identitas (KTP + Wajah). Urut berdasarkan pengajuan terlama sesuai SLA 24 jam.</p>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle text-nowrap" style="width: 100%;">
              <thead>
                <tr class="text-muted fw-semibold">
                  <th scope="col">Nama Pengguna</th>
                  <th scope="col">Nomor HP</th>
                  <th scope="col">Tanggal Pengajuan</th>
                  <th scope="col">Konteks</th>
                  <th scope="col" class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($pending as $user)
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-3">
                        <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                          {{ $user->initials }}
                        </span>
                        <div>
                          <h6 class="fw-semibold mb-0 fs-3">{{ $user->name }}</h6>
                          <span class="fs-2 text-muted">ID: {{ $user->id }}</span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="fw-semibold text-dark">{{ $user->phone }}</span>
                    </td>
                    <td>
                      <span class="text-dark">{{ $user->ktp_submitted_at?->format('d M Y H:i') ?? $user->updated_at->format('d M Y H:i') }}</span>
                      <div class="fs-2 text-danger fw-semibold">Sisa Waktu SLA: {{ $user->ktp_submitted_at ? $user->ktp_submitted_at->addHours(24)->diffForHumans() : '—' }}</div>
                    </td>
                    <td>
                      @if ($user->rejected_at)
                        <span class="badge bg-light-warning text-warning fw-semibold fs-2" title="Pernah ditolak sebelumnya oleh {{ $user->rejectedBy?->name }}">Pengajuan Ulang</span>
                      @else
                        <span class="badge bg-light-primary text-primary fw-semibold fs-2">Pengajuan Baru</span>
                      @endif
                    </td>
                    <td class="text-end">
                      <button type="button" class="btn btn-sm btn-primary btn-hover-shadow" data-bs-toggle="modal" data-bs-target="#verifyModal-{{ $user->id }}">
                        <i class="ti ti-shield-check fs-4"></i> Tinjau & Verifikasi
                      </button>
                    </td>
                  </tr>

                  <!-- Verification Modal -->
                  <div class="modal fade" id="verifyModal-{{ $user->id }}" tabindex="-1" aria-labelledby="verifyModalLabel-{{ $user->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                      <div class="modal-content">
                        <div class="modal-header border-bottom">
                          <h5 class="modal-title fw-bold text-dark" id="verifyModalLabel-{{ $user->id }}">Tinjau Identitas: {{ $user->name }}</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 bg-light-subtle">
                          <div class="row g-4">
                            <!-- Left: Files & Data Details -->
                            <div class="col-lg-7">
                              <div class="card border mb-3">
                                <div class="card-body p-3">
                                  <h6 class="fw-semibold mb-3 text-dark">Data Pengguna</h6>
                                  <table class="table table-sm table-borderless mb-0 fs-3">
                                    <tbody>
                                      <tr>
                                        <td class="text-muted ps-0" style="width: 150px;">NIK KTP</td>
                                        <td class="fw-bold text-dark">{{ $user->nik ?? '—' }}</td>
                                      </tr>
                                      <tr>
                                        <td class="text-muted ps-0">Alamat Domisili</td>
                                        <td class="text-dark">{{ $user->address ?? '—' }}</td>
                                      </tr>
                                      <tr>
                                        <td class="text-muted ps-0">Koordinat</td>
                                        <td class="text-dark">
                                          @if ($user->latitude && $user->longitude)
                                            <code>{{ $user->latitude }}, {{ $user->longitude }}</code>
                                          @else
                                            <span class="text-danger">belum disetel</span>
                                          @endif
                                        </td>
                                      </tr>
                                    </tbody>
                                  </table>
                                </div>
                              </div>

                              <div class="row g-3">
                                <div class="col-md-6">
                                  <div class="border rounded p-3 text-center bg-white shadow-sm h-100">
                                    <h6 class="fw-semibold mb-2 text-dark">Foto KTP</h6>
                                    @if ($user->ktp_image)
                                      <a href="{{ route('admin.verifications.users.media', [$user, 'kind' => 'ktp']) }}" target="_blank">
                                        <img src="{{ route('admin.verifications.users.media', [$user, 'kind' => 'ktp']) }}" class="img-fluid rounded border mb-2" style="max-height: 250px; object-fit: contain;" alt="Foto KTP">
                                      </a>
                                    @else
                                      <div class="p-5 text-muted bg-light border-dashed rounded">tidak ada foto KTP</div>
                                    @endif
                                  </div>
                                </div>
                                <div class="col-md-6">
                                  <div class="border rounded p-3 text-center bg-white shadow-sm h-100">
                                    <h6 class="fw-semibold mb-2 text-dark">Foto Wajah (Selfie)</h6>
                                    @if ($user->selfie_image)
                                      <a href="{{ route('admin.verifications.users.media', [$user, 'kind' => 'selfie']) }}" target="_blank">
                                        <img src="{{ route('admin.verifications.users.media', [$user, 'kind' => 'selfie']) }}" class="img-fluid rounded border mb-2" style="max-height: 250px; object-fit: contain;" alt="Foto Wajah">
                                      </a>
                                    @else
                                      <div class="p-5 text-muted bg-light border-dashed rounded">tidak ada foto wajah</div>
                                    @endif
                                  </div>
                                </div>
                              </div>
                            </div>

                            <!-- Right: SOP Checklist & Actions -->
                            <div class="col-lg-5">
                              <div class="card border">
                                <div class="card-body p-4">
                                  <h6 class="fw-bold mb-3 text-dark"><i class="ti ti-list-check text-primary"></i> Checklist SOP Verifikasi</h6>
                                  <p class="text-muted fs-2">Centang semua poin berikut setelah memeriksa kesesuaian berkas untuk mengaktifkan tombol persetujuan.</p>
                                  
                                  <div class="d-flex flex-column gap-3 mb-4">
                                    <div class="form-check">
                                      <input class="form-check-input verify-checklist-{{ $user->id }}" type="checkbox" id="check-wajah-{{ $user->id }}">
                                      <label class="form-check-label text-dark fs-3" for="check-wajah-{{ $user->id }}">
                                        Foto wajah (selfie) cocok dengan foto di KTP
                                      </label>
                                    </div>
                                    <div class="form-check">
                                      <input class="form-check-input verify-checklist-{{ $user->id }}" type="checkbox" id="check-ktp-{{ $user->id }}">
                                      <label class="form-check-label text-dark fs-3" for="check-ktp-{{ $user->id }}">
                                        Foto KTP asli (bukan fotokopi/scan) dan terbaca jelas
                                      </label>
                                    </div>
                                    <div class="form-check">
                                      <input class="form-check-input verify-checklist-{{ $user->id }}" type="checkbox" id="check-nik-{{ $user->id }}">
                                      <label class="form-check-label text-dark fs-3" for="check-nik-{{ $user->id }}">
                                        NIK KTP valid (16 digit) dan sesuai NIK di formulir
                                      </label>
                                    </div>
                                    <div class="form-check">
                                      <input class="form-check-input verify-checklist-{{ $user->id }}" type="checkbox" id="check-alamat-{{ $user->id }}">
                                      <label class="form-check-label text-dark fs-3" for="check-alamat-{{ $user->id }}">
                                        Alamat domisili berada di {{ config('seekitar.regency') }}
                                      </label>
                                    </div>
                                    <div class="form-check">
                                      <input class="form-check-input verify-checklist-{{ $user->id }}" type="checkbox" id="check-koordinat-{{ $user->id }}">
                                      <label class="form-check-label text-dark fs-3" for="check-koordinat-{{ $user->id }}">
                                        Titik koordinat GPS sesuai dengan alamat domisili
                                      </label>
                                    </div>
                                  </div>

                                  <!-- Approve Form -->
                                  <form action="{{ route('admin.verifications.users.verify', $user) }}" method="POST" class="mb-3">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100 btn-hover-shadow py-2 fw-semibold" id="btn-approve-{{ $user->id }}" disabled>
                                      <i class="ti ti-circle-check fs-5 me-1"></i> Setujui Verifikasi
                                    </button>
                                  </form>

                                  <hr class="my-4 text-muted opacity-25">

                                  <!-- Reject Form -->
                                  <form action="{{ route('admin.verifications.users.reject', $user) }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                      <label for="reason-{{ $user->id }}" class="form-label fw-semibold text-dark fs-3">Alasan Penolakan</label>
                                      <textarea class="form-control" id="reason-{{ $user->id }}" name="reason" rows="2" placeholder="Sebutkan berkas yang salah..." required></textarea>
                                      <div class="form-text fs-2 text-danger">Alasan wajib diisi agar pendaftar tahu apa yang harus diperbaiki.</div>
                                    </div>
                                    <button type="submit" class="btn btn-outline-danger w-100 py-2 fw-semibold">
                                      <i class="ti ti-circle-x fs-5 me-1"></i> Tolak Pengajuan
                                    </button>
                                  </form>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <script>
                    $(function () {
                      $('.verify-checklist-{{ $user->id }}').change(function() {
                        var allChecked = true;
                        $('.verify-checklist-{{ $user->id }}').each(function() {
                          if (!$(this).is(':checked')) {
                            allChecked = false;
                          }
                        });
                        $('#btn-approve-{{ $user->id }}').prop('disabled', !allChecked);
                      });
                    });
                  </script>
                @empty
                  <tr>
                    <td colspan="5" class="p-5 text-center text-muted">
                      <div class="mb-3">
                        <span class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center" style="width:72px;height:72px;">
                          <i class="ti ti-check fs-4"></i>
                        </span>
                      </div>
                      <h5 class="fw-semibold text-dark mb-1">Antrian Kosong</h5>
                      <p class="mb-0 text-muted">Tidak ada identitas pengguna yang menunggu verifikasi.</p>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-center mt-4">
            {{ $pending->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
