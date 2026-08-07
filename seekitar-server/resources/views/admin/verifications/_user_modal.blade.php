<div class="modal fade" id="verifyModal-{{ $user->id }}" tabindex="-1" aria-labelledby="verifyModalLabel-{{ $user->id }}" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold text-dark" id="verifyModalLabel-{{ $user->id }}">Tinjau Identitas: {{ $user->name }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 bg-light-subtle">
        <div class="row g-4">
          <div class="col-lg-7">
            <div class="card border mb-3">
              <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                  <i class="ti ti-file-text fs-5 text-primary" aria-hidden="true"></i>
                  <h6 class="fw-bold mb-0 text-dark">Data Pengguna</h6>
                </div>
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
                  </tbody>
                </table>
              </div>
            </div>

            {{-- Peta lokasi domisili: pin di titik koordinat (Google Maps embed, tanpa API key). --}}
            <div class="card border mb-3">
              <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                  <i class="ti ti-map-pin fs-5 text-primary" aria-hidden="true"></i>
                  <h6 class="fw-bold mb-0 text-dark">Lokasi Domisili</h6>
                </div>
                @if ($user->latitude && $user->longitude)
                  <div class="rounded overflow-hidden border">
                    <iframe
                      src="https://maps.google.com/maps?q={{ $user->latitude }},{{ $user->longitude }}&z=16&output=embed"
                      style="width: 100%; height: 260px; border: 0;"
                      loading="lazy"
                      allowfullscreen
                      referrerpolicy="no-referrer-when-downgrade"
                      title="Peta lokasi {{ $user->name }}"></iframe>
                  </div>
                  <div class="text-muted mt-2 fs-2">
                    Koordinat: <code>{{ number_format($user->latitude, 6) }}, {{ number_format($user->longitude, 6) }}</code>
                  </div>
                @else
                  <div class="text-danger p-4 text-center bg-light border-dashed rounded">
                    <i class="ti ti-map-pin-off fs-3 d-block mb-1" aria-hidden="true"></i>
                    Koordinat belum disetel
                  </div>
                @endif
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <div class="border rounded p-3 text-center bg-white shadow-sm h-100">
                  <h6 class="fw-semibold mb-2 text-dark">Foto KTP</h6>
                  @if ($user->ktp_image)
                    <a href="{{ route('admin.verifications.users.media', [$user, 'kind' => 'ktp']) }}" class="wa-lightbox d-inline-block" title="Perbesar Foto KTP">
                      <img src="{{ route('admin.verifications.users.media', [$user, 'kind' => 'ktp']) }}" class="img-fluid rounded border mb-2 wa-lightbox-img" style="max-height: 250px; object-fit: contain;" alt="Foto KTP">
                    </a>
                    <div class="text-muted fs-2">Klik foto untuk memperbesar</div>
                  @else
                    <div class="p-5 text-muted bg-light border-dashed rounded">tidak ada foto KTP</div>
                  @endif
                </div>
              </div>
              <div class="col-md-6">
                <div class="border rounded p-3 text-center bg-white shadow-sm h-100">
                  <h6 class="fw-semibold mb-2 text-dark">Foto Wajah (Selfie)</h6>
                  @if ($user->selfie_image)
                    <a href="{{ route('admin.verifications.users.media', [$user, 'kind' => 'selfie']) }}" class="wa-lightbox d-inline-block" title="Perbesar Foto Wajah">
                      <img src="{{ route('admin.verifications.users.media', [$user, 'kind' => 'selfie']) }}" class="img-fluid rounded border mb-2 wa-lightbox-img" style="max-height: 250px; object-fit: contain;" alt="Foto Wajah">
                    </a>
                    <div class="text-muted fs-2">Klik foto untuk memperbesar</div>
                  @else
                    <div class="p-5 text-muted bg-light border-dashed rounded">tidak ada foto wajah</div>
                  @endif
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-5">
            <div class="card border">
              <div class="card-body p-4">
                <h6 class="fw-bold mb-3 text-dark"><i class="ti ti-list-check text-primary" aria-hidden="true"></i> Checklist SOP Verifikasi</h6>
                <p class="text-muted fs-2">Centang semua poin berikut setelah memeriksa kesesuaian berkas untuk mengaktifkan tombol persetujuan.</p>

                <div class="d-flex flex-column gap-3 mb-4" data-checklist>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check-wajah-{{ $user->id }}">
                    <label class="form-check-label text-dark fs-3" for="check-wajah-{{ $user->id }}">
                      Foto wajah (selfie) cocok dengan foto di KTP
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check-ktp-{{ $user->id }}">
                    <label class="form-check-label text-dark fs-3" for="check-ktp-{{ $user->id }}">
                      Foto KTP asli (bukan fotokopi/scan) dan terbaca jelas
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check-nik-{{ $user->id }}">
                    <label class="form-check-label text-dark fs-3" for="check-nik-{{ $user->id }}">
                      NIK KTP valid (16 digit) dan sesuai NIK di formulir
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check-alamat-{{ $user->id }}">
                    <label class="form-check-label text-dark fs-3" for="check-alamat-{{ $user->id }}">
                      Alamat domisili berada di {{ config('seekitar.regency') }}
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check-koordinat-{{ $user->id }}">
                    <label class="form-check-label text-dark fs-3" for="check-koordinat-{{ $user->id }}">
                      Titik koordinat GPS sesuai dengan alamat domisili
                    </label>
                  </div>
                </div>

                <form action="{{ route('admin.verifications.users.verify', $user) }}" method="POST" class="mb-3">
                  @csrf
                  <button type="submit" class="btn btn-success w-100 btn-hover-shadow py-2 fw-semibold" data-tombol-verifikasi disabled>
                    <i class="ti ti-circle-check fs-5 me-1" aria-hidden="true"></i> Setujui Verifikasi
                  </button>
                </form>

                <hr class="my-4 text-muted opacity-25">

                <form action="{{ route('admin.verifications.users.reject', $user) }}" method="POST">
                  @csrf
                  <div class="mb-3">
                    <label for="reason-{{ $user->id }}" class="form-label fw-semibold text-dark fs-3">Alasan Penolakan</label>
                    <textarea class="form-control" id="reason-{{ $user->id }}" name="reason" rows="2" placeholder="Sebutkan berkas yang salah..." required></textarea>
                    <div class="form-text fs-2 text-danger">Alasan wajib diisi agar pendaftar tahu apa yang harus diperbaiki.</div>
                  </div>
                  <button type="submit" class="btn btn-outline-danger w-100 py-2 fw-semibold">
                    <i class="ti ti-circle-x fs-5 me-1" aria-hidden="true"></i> Tolak Pengajuan
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
