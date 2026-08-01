@extends('admin.layouts.admin')

@section('title', 'Detail Pengguna — Seekitar')

@section('content')
  <div class="row">
    <!-- Main Info & Activity -->
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <div class="d-md-flex align-items-start gap-4 mb-4">
            <img src="{{ $user->avatar_url }}" class="rounded-circle border shadow-sm mb-3 mb-md-0" width="96" height="96" style="object-fit: cover;">
            <div class="flex-grow-1">
              <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                <h3 class="fw-bold mb-0 text-dark">{{ $user->name }}</h3>
                @if ($user->verified_at)
                  <span class="text-success" title="Identitas Terverifikasi">
                    <i class="ti ti-circle-check fs-6" role="img" aria-label="Terverifikasi"></i>
                  </span>
                @endif
                @if ($user->email_verified_at)
                  <span class="text-primary" title="Email Terverifikasi">
                    <i class="ti ti-mail-check fs-6" role="img" aria-label="Email Terverifikasi"></i>
                  </span>
                @endif
                <span class="badge bg-light-primary text-primary fw-semibold fs-2 ms-1">{{ $user->verificationLevel->label() }}</span>
              </div>
              <p class="text-muted mb-2 fs-3">ID: <code>{{ $user->id }}</code> | Terdaftar sejak {{ $user->created_at?->format('d F Y H:i') }}</p>
              <span class="badge bg-light-{{ $user->status->color() }} text-{{ $user->status->color() }} fw-bold px-3 py-2 fs-3">
                {{ $user->status->label() }}
              </span>
            </div>
          </div>

          <div class="row g-4 mb-4 border-top border-bottom py-3">
            <div class="col-6 col-md-3 text-center border-end">
              <h4 class="fw-bold mb-0 text-dark">{{ $user->stores_count }}</h4>
              <span class="text-muted fs-3">Toko Dimiliki</span>
            </div>
            <div class="col-6 col-md-3 text-center border-end">
              <h4 class="fw-bold mb-0 text-dark">{{ $user->customer_requests_count }}</h4>
              <span class="text-muted fs-3">Permintaan Siar</span>
            </div>
            <div class="col-6 col-md-3 text-center border-end">
              <h4 class="fw-bold mb-0 text-dark">{{ $user->orders_count }}</h4>
              <span class="text-muted fs-3">Total Transaksi</span>
            </div>
            <div class="col-6 col-md-3 text-center">
              <h4 class="fw-bold mb-0 text-dark">{{ $user->reviews_received_count }}</h4>
              <span class="text-muted fs-3">Ulasan Diterima</span>
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
                  <td class="text-dark">
                    {{ $user->email ?? '—' }}
                    @if ($user->email)
                      @if ($user->email_verified_at)
                        <span class="badge bg-success text-white fs-2 ms-1">Terverifikasi</span>
                      @else
                        <span class="badge bg-secondary text-white fs-2 ms-1">Belum Diverifikasi</span>
                      @endif
                    @endif
                  </td>
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
                        <i class="ti ti-map-pin" aria-hidden="true"></i> Lihat Peta
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
                      <span class="text-warning"><i class="ti ti-star" aria-hidden="true"></i></span>
                      <span class="fw-semibold">{{ \App\Support\Angka::desimal($user->rating_avg, 1) }}</span>
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

      <!-- Recent Customer Requests -->
      @if ($user->customerRequests->isNotEmpty())
        <div class="card shadow-sm mt-4">
          <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h5 class="fw-bold mb-0 text-dark">Permintaan Siar Terbaru ({{ $user->customer_requests_count }})</h5>
              @can('manage-requests')
                <a href="{{ route('admin.requests.index') }}" class="btn btn-sm btn-light-primary text-primary"><i class="ti ti-arrow-right me-1" aria-hidden="true"></i> Lihat Semua</a>
              @endcan
            </div>
            <div class="table-responsive">
              <table class="table align-middle text-nowrap mb-0">
                <thead>
                  <tr class="text-muted fw-semibold">
                    <th scope="col">Permintaan</th>
                    <th scope="col">Kategori</th>
                    <th scope="col">Anggaran</th>
                    <th scope="col">Status</th>
                    <th scope="col">Disiarkan</th>
                    <th scope="col" class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($user->customerRequests as $req)
                    <tr>
                      <td>
                        <span class="fw-semibold text-dark fs-3">{{ $req->title }}</span>
                        <span class="fs-2 text-muted d-block">ID: {{ $req->id }}</span>
                      </td>
                      <td>
                        <span class="fs-3 text-dark">{{ $req->category?->name ?? 'Kategori Umum' }}</span>
                      </td>
                      <td>
                        @php
                          $anggaran = $req->budget_min && $req->budget_max
                            ? 'Rp ' . number_format($req->budget_min, 0, ',', '.') . ' – Rp ' . number_format($req->budget_max, 0, ',', '.')
                            : ($req->budget_min ? 'Mulai Rp ' . number_format($req->budget_min, 0, ',', '.') : ($req->budget_max ? 'Hingga Rp ' . number_format($req->budget_max, 0, ',', '.') : 'Nego'));
                        @endphp
                        <span class="fs-3 text-dark">{{ $anggaran }}</span>
                      </td>
                      <td>
                        @php
                          $r_colors = ['open' => 'success', 'closed' => 'secondary', 'expired' => 'danger'];
                          $r_color = $r_colors[$req->status->value] ?? 'secondary';
                        @endphp
                        <span class="badge bg-light-{{ $r_color }} text-{{ $r_color }} fw-semibold fs-2">{{ $req->status->label() }}</span>
                      </td>
                      <td>
                        <span class="fs-2 text-muted">{{ $req->created_at?->format('d M Y H:i') }}</span>
                      </td>
                      <td class="text-end">
                        <a href="{{ route('admin.requests.show', $req) }}" class="btn btn-xs btn-light-info text-info"><i class="ti ti-search" aria-hidden="true"></i> Detail</a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endif

      <!-- Recent Orders -->
      @if ($user->orders->isNotEmpty())
        <div class="card shadow-sm mt-4">
          <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h5 class="fw-bold mb-0 text-dark">Transaksi Terbaru ({{ $user->orders_count }})</h5>
              @can('manage-orders')
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-light-primary text-primary"><i class="ti ti-arrow-right me-1" aria-hidden="true"></i> Lihat Semua</a>
              @endcan
            </div>
            <div class="table-responsive">
              <table class="table align-middle text-nowrap mb-0">
                <thead>
                  <tr class="text-muted fw-semibold">
                    <th scope="col">Pesanan</th>
                    <th scope="col">Toko</th>
                    <th scope="col">Total</th>
                    <th scope="col">Status</th>
                    <th scope="col">Dibuat</th>
                    <th scope="col" class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($user->orders as $order)
                    <tr>
                      <td>
                        <span class="fw-semibold text-dark fs-3">#{{ $order->order_number }}</span>
                        <span class="fs-2 text-muted d-block">{{ $order->order_type?->label() }}</span>
                      </td>
                      <td>
                        <span class="fs-3 text-dark">{{ $order->store?->name ?? '—' }}</span>
                      </td>
                      <td>
                        <span class="fw-semibold text-dark fs-3">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                      </td>
                      <td>
                        @include('admin.partials._order_badge', ['order' => $order])
                      </td>
                      <td>
                        <span class="fs-2 text-muted">{{ $order->created_at?->format('d M Y H:i') }}</span>
                      </td>
                      <td class="text-end">
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-xs btn-light-info text-info"><i class="ti ti-search" aria-hidden="true"></i> Detail</a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endif

      <!-- User's Stores -->
      @if ($user->stores->isNotEmpty())
        <div class="card shadow-sm mt-4">
          <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h5 class="fw-bold mb-0 text-dark">Daftar Toko Milik Pengguna</h5>
              @can('manage-stores')
                <a href="{{ route('admin.stores.index') }}" class="btn btn-sm btn-light-primary text-primary"><i class="ti ti-arrow-right me-1" aria-hidden="true"></i> Lihat Semua</a>
              @endcan
            </div>
            <div class="table-responsive">
              <table class="table align-middle text-nowrap mb-0">
                <thead>
                  <tr class="text-muted fw-semibold">
                    <th scope="col">Nama Toko</th>
                    <th scope="col">Status</th>
                    <th scope="col">Keaktifan</th>
                    <th scope="col">Rating</th>
                    <th scope="col">Terdaftar</th>
                    <th scope="col" class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($user->stores as $toko)
                    <tr>
                      <td>
                        <div class="d-flex align-items-center gap-3">
                          <img src="{{ $toko->photo }}" class="rounded-circle border" width="40" height="40" style="object-fit: cover;">
                          <div>
                            <h6 class="fw-semibold mb-0 fs-3">{{ $toko->name }}</h6>
                            <span class="fs-2 text-muted">ID: {{ $toko->id }}</span>
                          </div>
                        </div>
                      </td>
                      <td>
                        <span class="badge bg-light-{{ $toko->status->color() }} text-{{ $toko->status->color() }} fw-semibold fs-2">{{ $toko->status->label() }}</span>
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
                          <span class="text-warning"><i class="ti ti-star" aria-hidden="true"></i></span>
                          <span class="fw-semibold">{{ \App\Support\Angka::desimal($toko->rating_avg, 1) }}</span>
                        @else
                          <span class="text-muted fs-2">—</span>
                        @endif
                      </td>
                      <td>
                        <span class="fs-2 text-muted">{{ $toko->created_at?->format('d M Y') }}</span>
                      </td>
                      <td class="text-end">
                        <a href="{{ route('admin.stores.show', $toko) }}" class="btn btn-xs btn-light-info text-info"><i class="ti ti-search" aria-hidden="true"></i> Detail</a>
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

    <!-- Side Actions (Status, Devices, Verification Level, Audit Trail) -->
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
                <h6 class="alert-heading fw-bold mb-1"><i class="ti ti-alert-triangle" aria-hidden="true"></i> Akun Diblokir</h6>
                <p class="mb-0 fs-3"><strong>Alasan:</strong> {{ $user->blocked_reason }}</p>
                <hr class="my-2">
                <span class="fs-2">Diblokir oleh: {{ $user->blockedBy?->name ?? 'Sistem' }} pada {{ $user->blocked_at?->format('d M Y H:i') }}</span>
              </div>
              
              <form action="{{ route('admin.users.block', $user) }}" method="POST">
                @csrf
                <input type="hidden" name="action" value="unblock">
                <button type="submit" class="btn btn-success w-100 btn-hover-shadow" onclick="return confirm('Apakah Anda yakin ingin membuka blokir akun ini?');">
                  <i class="ti ti-lock-open me-1" aria-hidden="true"></i> Buka Blokir Akun
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
                  <i class="ti ti-lock me-1" aria-hidden="true"></i> Blokir Akun Pengguna
                </button>
              </form>
            @endif
          @endcan
        </div>
      </div>

      <!-- Verification Level -->
      <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Level Verifikasi</h5>
          @php
            $tahap = [
              [1, 'Nomor Terverifikasi', 'Nomor HP dibuktikan lewat OTP.', true],
              [2, 'Identitas Terverifikasi', 'Berkas KTP disetujui admin.', $user->verified_at !== null],
              [3, 'Usaha Terverifikasi', 'Memiliki toko berstatus verified.', $user->verificationLevel->value >= 3],
            ];
          @endphp
          <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
            @foreach ($tahap as [$level, $label, $desc, $selesai])
              <li class="d-flex gap-3">
                <div class="flex-shrink-0">
                  @if ($selesai)
                    <span class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="ti ti-check" aria-hidden="true"></i></span>
                  @elseif ($user->verificationLevel->value === $level)
                    <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">{{ $level }}</span>
                  @else
                    <span class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">{{ $level }}</span>
                  @endif
                </div>
                <div>
                  <span class="fw-semibold text-dark fs-3 d-block {{ $selesai ? '' : 'text-muted' }}">{{ $label }}</span>
                  <span class="fs-2 text-muted">{{ $desc }}</span>
                </div>
              </li>
            @endforeach
          </ul>
        </div>
      </div>

      <!-- Registered Devices -->
      <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Perangkat Terdaftar ({{ $user->devices->count() }})</h5>
          @if ($user->devices->isEmpty())
            <p class="text-muted fs-3 mb-0">Belum ada perangkat yang terdaftar.</p>
          @else
            <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
              @foreach ($user->devices as $device)
                <li class="border rounded p-3">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    @if ($device->platform === 'android')
                      <i class="ti ti-brand-android text-success fs-4" aria-hidden="true"></i>
                    @elseif ($device->platform === 'ios')
                      <i class="ti ti-brand-apple text-dark fs-4" aria-hidden="true"></i>
                    @else
                      <i class="ti ti-device-mobile text-muted fs-4" aria-hidden="true"></i>
                    @endif
                    <span class="fw-semibold text-dark fs-3 text-capitalize">{{ $device->platform }}</span>
                    <span class="fs-2 text-muted ms-auto">Terakhir aktif: {{ $device->last_used_at?->format('d M Y H:i') ?? '—' }}</span>
                  </div>
                  <code class="fs-2 d-block mb-1">Device ID: {{ $device->device_id }}</code>
                  <span class="fs-2 text-muted">FCM Token: <code>{{ Str::limit($device->fcm_token, 40) }}</code></span>
                </li>
              @endforeach
            </ul>
          @endif
        </div>
      </div>

      <!-- Reviews Received -->
      @if ($user->reviewsReceived->isNotEmpty())
        <div class="card shadow-sm mb-4">
          <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h5 class="fw-bold mb-0 text-dark">Ulasan Diterima</h5>
              @can('manage-reviews')
                <a href="{{ route('admin.reviews.index') }}" class="btn btn-sm btn-light-primary text-primary"><i class="ti ti-arrow-right me-1" aria-hidden="true"></i> Semua</a>
              @endcan
            </div>
            @foreach ($user->reviewsReceived as $rev)
              <div class="border-bottom pb-3 mb-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <span class="fw-semibold text-dark fs-3">{{ $rev->reviewer?->name ?? 'Pengguna' }}</span>
                  <span class="badge bg-light-info text-info fs-2">{{ $rev->direction->label() }}</span>
                  <div class="text-warning ms-1">
                    @for ($i = 1; $i <= 5; $i++)
                      <i class="ti ti-star {{ $i <= $rev->rating ? '' : 'text-muted' }}" aria-hidden="true"></i>
                    @endfor
                    <span class="fs-2 text-muted ms-1">({{ $rev->rating }})</span>
                  </div>
                </div>
                <p class="mb-1 fs-3 text-muted">{{ $rev->comment ?? 'Tanpa komentar teks.' }}</p>
                <span class="fs-2 text-muted">
                  {{ $rev->created_at?->format('d M Y H:i') }}
                  @if ($rev->store)
                    • untuk toko {{ $rev->store->name }}
                  @endif
                </span>
              </div>
            @endforeach
          </div>
        </div>
      @endif

      <!-- Verification Audit Trail -->
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Jejak Audit</h5>
          <ul class="list-unstyled d-flex flex-column gap-3 mb-0 fs-3">
            <li class="d-flex justify-content-between">
              <span class="text-muted">Pendaftar</span>
              <span class="text-dark fw-medium">{{ $user->created_at?->format('d M Y H:i') }}</span>
            </li>
            @if ($user->ktp_submitted_at)
              <li class="d-flex justify-content-between">
                <span class="text-muted">Berkas Identitas Diajukan</span>
                <span class="text-dark fw-medium">{{ $user->ktp_submitted_at?->format('d M Y H:i') }}</span>
              </li>
            @endif
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
            @if ($user->blocked_at)
              <li class="d-flex justify-content-between text-danger">
                <span class="text-muted">Diblokir Oleh</span>
                <span class="fw-semibold">{{ $user->blockedBy?->name ?? '—' }}</span>
              </li>
              <li class="d-flex justify-content-between text-danger">
                <span class="text-muted">Tanggal Diblokir</span>
                <span class="fw-semibold">{{ $user->blocked_at?->format('d M Y H:i') }}</span>
              </li>
              <li class="border-top pt-2">
                <span class="text-muted d-block mb-1">Alasan Pemblokiran:</span>
                <p class="mb-0 text-dark bg-light rounded p-2">{{ $user->blocked_reason }}</p>
              </li>
            @endif
          </ul>
        </div>
      </div>
    </div>
  </div>
@endsection
