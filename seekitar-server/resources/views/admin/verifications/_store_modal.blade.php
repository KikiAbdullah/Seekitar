<div class="modal fade" id="verifyStoreModal-{{ $store->id }}" tabindex="-1" aria-labelledby="verifyStoreModalLabel-{{ $store->id }}" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold text-dark" id="verifyStoreModalLabel-{{ $store->id }}">Tinjau Toko: {{ $store->name }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 bg-light-subtle">
        <div class="row g-4">
          <div class="col-lg-7">
            <div class="border rounded p-3 text-center bg-white shadow-sm mb-3">
              <h6 class="fw-semibold mb-2 text-dark">Foto Tampak Depan Toko</h6>
              @if ($store->photo)
                <a href="{{ asset('storage/' . $store->photo) }}" target="_blank">
                  <img src="{{ asset('storage/' . $store->photo) }}" class="img-fluid rounded border shadow-sm mb-2" style="max-height: 350px; width: 100%; object-fit: cover;" alt="Foto Depan Toko">
                </a>
              @else
                <div class="p-5 text-muted bg-light border-dashed rounded">tidak ada foto depan toko</div>
              @endif
            </div>

            <div class="card border">
              <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                  <i class="ti ti-building-bank fs-5 text-primary" aria-hidden="true"></i>
                  <h6 class="fw-bold mb-0 text-dark">Data Legal & Keuangan</h6>
                </div>
                <table class="table table-sm table-borderless mb-0 fs-3">
                  <tbody>
                    <tr>
                      <td class="text-muted ps-0" style="width: 150px;">NPWP Toko</td>
                      <td class="fw-bold text-dark">{{ $store->npwp ?? '—' }}</td>
                    </tr>
                    <tr>
                      <td class="text-muted ps-0">Nomor Rekening</td>
                      <td class="text-dark">{{ $store->bank_account ?? '—' }}</td>
                    </tr>
                    <tr>
                      <td class="text-muted ps-0">Atas Nama Rekening</td>
                      <td class="text-dark">{{ $store->bank_account_name ?? '—' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="col-lg-5">
            <div class="card border mb-3">
              <div class="card-body p-3">
                <h6 class="fw-semibold mb-2 text-dark">Informasi Pemilik</h6>
                <div class="d-flex align-items-center gap-2 mb-2">
                  <h6 class="fw-bold mb-0 text-dark">{{ $store->owner?->name }}</h6>
                  @if ($store->owner?->verified_at)
                    <span class="badge bg-light-success text-success fw-bold fs-2"><i class="ti ti-circle-check" aria-hidden="true"></i> KTP Terverifikasi</span>
                  @else
                    <span class="badge bg-light-danger text-danger fw-bold fs-2"><i class="ti ti-circle-x" aria-hidden="true"></i> KTP Belum Terverifikasi</span>
                  @endif
                </div>
                <p class="mb-0 fs-3 text-muted">WhatsApp: {{ $store->owner?->phone }}</p>
                <p class="mb-0 fs-3 text-muted">Alamat Pemilik: {{ $store->owner?->address }}</p>
              </div>
            </div>

            <div class="card border mb-3">
              <div class="card-body p-3">
                <h6 class="fw-semibold mb-2 text-dark">Alamat & Operasional Toko</h6>
                <p class="mb-1 fs-3 text-dark"><strong>Alamat:</strong> {{ $store->address ?? '—' }}</p>
                @if ($store->latitude && $store->longitude)
                  <p class="mb-1 fs-3 text-muted"><strong>Koordinat:</strong> <code>{{ $store->latitude }}, {{ $store->longitude }}</code></p>
                @endif
                <p class="mb-0 fs-3 text-muted"><strong>Radius Pelayanan:</strong> {{ $store->service_radius_km }} km</p>
              </div>
            </div>

            <div class="card border">
              <div class="card-body p-4">
                <h6 class="fw-bold mb-3 text-dark">Persetujuan Verifikasi Toko</h6>

                <form action="{{ route('admin.verifications.stores.approve', $store) }}" method="POST" class="mb-3">
                  @csrf
                  <button type="submit" class="btn btn-success w-100 btn-hover-shadow py-2 fw-semibold" {{ !$store->owner?->verified_at ? 'disabled' : '' }}>
                    <i class="ti ti-circle-check fs-5 me-1" aria-hidden="true"></i> Setujui Toko
                  </button>
                  @if (!$store->owner?->verified_at)
                    <div class="form-text text-danger fs-2 mt-1"><i class="ti ti-info-circle" aria-hidden="true"></i> Pemilik harus diverifikasi identitasnya terlebih dahulu sebelum toko dapat disetujui.</div>
                  @endif
                </form>

                <hr class="my-4 text-muted opacity-25">

                <form action="{{ route('admin.verifications.stores.reject', $store) }}" method="POST">
                  @csrf
                  <div class="mb-3">
                    <label for="reason-{{ $store->id }}" class="form-label fw-semibold text-dark fs-3">Alasan Penolakan</label>
                    <textarea class="form-control" id="reason-{{ $store->id }}" name="reason" rows="3" placeholder="Sebutkan alasan penolakan, minimal 10 karakter..." required></textarea>
                    <div class="form-text fs-2 text-danger">Wajib diisi agar pemilik tahu alasan peninjauan ditolak.</div>
                  </div>
                  <button type="submit" class="btn btn-outline-danger w-100 py-2 fw-semibold">
                    <i class="ti ti-circle-x fs-5 me-1" aria-hidden="true"></i> Tolak Pendaftaran
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
