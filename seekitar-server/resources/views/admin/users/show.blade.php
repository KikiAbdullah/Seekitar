@extends('admin.layouts.admin')

@section('title', 'Detail Pengguna — Seekitar')

@section('content')
  <div class="row">
    <!-- Main Info & Statistics -->
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <div class="d-flex align-items-center gap-3 mb-4">
            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-9" style="width: 72px; height: 72px;">
              {{ $user->initials }}
            </span>
            <div>
              <div class="d-flex align-items-center gap-2">
                <h3 class="fw-bold mb-0 text-dark">{{ $user->name }}</h3>
                @if ($user->verified_at)
                  <span class="text-success" title="Identitas Terverifikasi">
                    <i class="fa-solid fa-circle-check fs-6"></i>
                  </span>
                @endif
              </div>
              <p class="text-muted mb-0 fs-3">ID: <code>{{ $user->id }}</code> | Terdaftar sejak {{ $user->created_at?->format('d F Y H:i') }}</p>
            </div>
          </div>

          <div class="row g-4 mb-4 border-top border-bottom py-3">
            <div class="col-4 text-center border-end">
              <h4 class="fw-bold mb-0 text-dark">{{ $user->stores_count }}</h4>
              <span class="text-muted fs-3">Toko Dimiliki</span>
            </div>
            <div class="col-4 text-center border-end">
              <h4 class="fw-bold mb-0 text-dark">{{ $user->customer_requests_count }}</h4>
              <span class="text-muted fs-3">Permintaan Siar</span>
            </div>
            <div class="col-4 text-center">
              <h4 class="fw-bold mb-0 text-dark">{{ $user->orders_count }}</h4>
              <span class="text-muted fs-3">Total Transaksi</span>
            </div>
          </div>

          <h5 class="fw-bold mb-3 text-dark">Informasi Akun</h5>
          <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0">
              <tbody>
                <tr>
                  <td class="text-muted ps-0" style="width: 200px;">Nomor HP (WhatsApp)</td>
                  <td class="fw-semibold text-dark">{{ $user->phone }}</td>
                </tr>
                <tr>
                  <td class="text-muted ps-0">Alamat Email</td>
                  <td class="text-dark">{{ $user->email ?? '—' }}</td>
                </tr>
                <tr>
                  <td class="text-muted ps-0">Alamat Domisili</td>
                  <td class="text-dark" style="white-space: normal;">{{ $user->address ?? '—' }}</td>
                </tr>
                <tr>
                  <td class="text-muted ps-0">Koordinat Lokasi</td>
                  <td class="text-dark">
                    @if ($user->latitude && $user->longitude)
                      <code>{{ $user->latitude }}, {{ $user->longitude }}</code>
                      <a href="https://www.google.com/maps/search/?api=1&query={{ $user->latitude }},{{ $user->longitude }}" target="_blank" class="ms-2 btn btn-xs btn-outline-primary py-0">
                        <i class="ti ti-map-pin"></i> Lihat Peta
                      </a>
                    @else
                      <span class="text-muted">belum disetel</span>
                    @endif
                  </td>
                </tr>
                <tr>
                  <td class="text-muted ps-0">Rating & Reputasi</td>
                  <td class="text-dark">
                    @if ($user->total_reviews > 0)
                      <span class="text-warning"><i class="fa-solid fa-star"></i></span>
                      <span class="fw-semibold">{{ number_format($user->rating_avg, 1) }}</span>
                      <span class="text-muted fs-3">({{ $user->total_reviews }} ulasan)</span>
                    @else
                      <span class="text-muted">belum ada ulasan</span>
                    @endif
                  </td>
                </tr>
                @can('verify-users')
                  <tr>
                    <td class="text-muted ps-0">NIK KTP</td>
                    <td class="fw-semibold text-dark">{{ $user->nik ?? '—' }}</td>
                  </tr>
                @endcan
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Private Identity Files (KTP / Selfie) -->
      @can('verify-users')
        @if ($user->ktp_image || $user->selfie_image)
          <div class="card shadow-sm mt-4">
            <div class="card-body p-4">
              <h5 class="fw-bold mb-3 text-dark">Berkas Identitas (Privat)</h5>
              <p class="card-subtitle mb-4">Sesuai ketentuan UU PDP, berkas ini hanya dapat diakses oleh admin dengan izin <code>verify-users</code>.</p>
              
              <div class="row g-4">
                @if ($user->ktp_image)
                  <div class="col-md-6">
                    <div class="border rounded p-3 text-center bg-light">
                      <h6 class="fw-semibold mb-2 text-dark">Foto KTP</h6>
                      <img src="{{ route('admin.verifications.users.media', [$user, 'kind' => 'ktp']) }}" class="img-fluid rounded border shadow-sm mb-2" style="max-height: 200px; object-fit: contain;" alt="Foto KTP">
                    </div>
                  </div>
                @endif
                
                @if ($user->selfie_image)
                  <div class="col-md-6">
                    <div class="border rounded p-3 text-center bg-light">
                      <h6 class="fw-semibold mb-2 text-dark">Foto Wajah (Selfie)</h6>
                      <img src="{{ route('admin.verifications.users.media', [$user, 'kind' => 'selfie']) }}" class="img-fluid rounded border shadow-sm mb-2" style="max-height: 200px; object-fit: contain;" alt="Foto Wajah">
                    </div>
                  </div>
                @endif
              </div>
            </div>
          </div>
        @endif
      @endcan

      <!-- User's Stores -->
      @if ($user->stores->isNotEmpty())
        <div class="card shadow-sm mt-4">
          <div class="card-body p-4">
            <h5 class="fw-bold mb-3 text-dark">Daftar Toko Milik Pengguna</h5>
            <div class="table-responsive">
              <table class="table align-middle text-nowrap mb-0">
                <thead>
                  <tr class="text-muted fw-semibold">
                    <th scope="col">Nama Toko</th>
                    <th scope="col">Status</th>
                    <th scope="col">Keaktifan</th>
                    <th scope="col">Rating</th>
                    <th scope="col">Terdaftar</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($user->stores as $toko)
                    <tr>
                      <td>
                        <div class="d-flex align-items-center gap-3">
                          <img src="{{ $toko->photo ?? 'https://placehold.co/100x100?text=Toko' }}" class="rounded-circle border" width="40" height="40" style="object-fit: cover;">
                          <div>
                            <h6 class="fw-semibold mb-0 fs-3">{{ $toko->name }}</h6>
                            <span class="fs-2 text-muted">ID: {{ $toko->id }}</span>
                          </div>
                        </div>
                      </td>
                      <td>
                        @php
                          $s_class = $toko->status->value === 'verified' ? 'success' : ($toko->status->value === 'rejected' ? 'danger' : 'warning');
                        @endphp
                        <span class="badge bg-light-{{ $s_class }} text-{{ $s_class }} fw-semibold fs-2">{{ $toko->status->label() }}</span>
                      </td>
                      <td>
                        @if ($toko->is_active)
                          <span class="badge bg-success text-white fw-semibold fs-2">Aktif</span>
                        @else
                          <span class="badge bg-secondary text-white fw-semibold fs-2">Nonaktif</span>
                        @endif
                      </td>
                      <td>
                        @if ($toko->total_reviews > 0)
                          <span class="text-warning"><i class="fa-solid fa-star"></i></span>
                          <span class="fw-semibold">{{ number_format($toko->rating_avg, 1) }}</span>
                        @else
                          <span class="text-muted fs-2">—</span>
                        @endif
                      </td>
                      <td>
                        <span class="fs-2 text-muted">{{ $toko->created_at?->format('d M Y') }}</span>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endif
    </div>

    <!-- Side Actions (Status, Block/Unblock, Audit Trail) -->
    <div class="col-lg-4">
      <div class="card bg-light-subtle shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Status Akun</h5>
          <div class="d-flex align-items-center justify-content-between mb-4">
            <span class="badge bg-light-{{ $user->status->color() }} text-{{ $user->status->color() }} fw-bold px-3 py-2 fs-3">
              {{ $user->status->label() }}
            </span>
          </div>

          <!-- Account Block/Unblock Forms -->
          @can('manage-users')
            @if ($user->isBlocked())
              <div class="alert alert-warning mb-3" role="alert">
                <h6 class="alert-heading fw-bold mb-1"><i class="ti ti-alert-triangle"></i> Akun Diblokir</h6>
                <p class="mb-0 fs-3"><strong>Alasan:</strong> {{ $user->blocked_reason }}</p>
                <hr class="my-2">
                <span class="fs-2">Diblokir oleh: {{ $user->blockedBy?->name ?? 'Sistem' }} pada {{ $user->blocked_at?->format('d M Y H:i') }}</span>
              </div>
              
              <form action="{{ route('admin.users.block', $user) }}" method="POST">
                @csrf
                <input type="hidden" name="action" value="unblock">
                <button type="submit" class="btn btn-success w-100 btn-hover-shadow" onclick="return confirm('Apakah Anda yakin ingin membuka blokir akun ini?');">
                  <i class="ti ti-lock-open me-1"></i> Buka Blokir Akun
                </button>
              </form>
            @else
              <form action="{{ route('admin.users.block', $user) }}" method="POST">
                @csrf
                <input type="hidden" name="action" value="block">
                
                <div class="mb-3">
                  <label for="reason" class="form-label fw-semibold text-dark fs-3">Alasan Pemblokiran</label>
                  <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Masukkan alasan pemblokiran..." required></textarea>
                  <div class="form-text">Alasan akan dicatat pada log audit keamanan dan diinfokan kepada pengguna.</div>
                </div>
                
                <button type="submit" class="btn btn-danger w-100 btn-hover-shadow" onclick="return confirm('Apakah Anda yakin ingin memblokir akun ini? Toko milik pengguna ini juga akan ikut diblokir.');">
                  <i class="ti ti-lock me-1"></i> Blokir Akun Pengguna
                </button>
              </form>
            @endif
          @endcan
        </div>
      </div>

      <!-- Verification Audit Trail -->
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Jejak Audit</h5>
          <ul class="list-unstyled d-flex flex-column gap-3 mb-0 fs-3">
            <li class="d-flex justify-content-between">
              <span class="text-muted">Pendaftar</span>
              <span class="text-dark fw-medium">{{ $user->created_at?->format('d M Y H:i') }}</span>
            </li>
            @if ($user->verified_at)
              <li class="d-flex justify-content-between">
                <span class="text-muted">Terverifikasi Oleh</span>
                <span class="text-dark fw-medium">{{ $user->verifiedBy?->name ?? '—' }}</span>
              </li>
              <li class="d-flex justify-content-between">
                <span class="text-muted">Tanggal Verifikasi</span>
                <span class="text-dark fw-medium">{{ $user->verified_at?->format('d M Y H:i') }}</span>
              </li>
            @endif
            @if ($user->rejected_at)
              <li class="d-flex justify-content-between text-danger">
                <span class="text-muted">Ditolak Oleh</span>
                <span class="fw-semibold">{{ $user->rejectedBy?->name ?? '—' }}</span>
              </li>
              <li class="d-flex justify-content-between text-danger">
                <span class="text-muted">Tanggal Penolakan</span>
                <span class="fw-semibold">{{ $user->rejected_at?->format('d M Y H:i') }}</span>
              </li>
              <li class="border-top pt-2">
                <span class="text-muted d-block mb-1">Alasan Penolakan:</span>
                <p class="mb-0 text-dark bg-light rounded p-2">{{ $user->rejected_reason }}</p>
              </li>
            @endif
          </ul>
        </div>
      </div>
    </div>
  </div>
@endsection
