@extends('admin.layouts.admin')

@section('title', 'Detail Toko — Seekitar')

@section('content')
  <div class="row">
    <!-- Main Info & Photo -->
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <div class="d-md-flex align-items-start gap-4 mb-4">
            <img src="{{ $store->photo ? asset('storage/' . $store->photo) : 'https://placehold.co/150x150?text=Toko' }}" class="rounded border shadow-sm mb-3 mb-md-0" width="120" height="120" style="object-fit: cover;">
            <div class="flex-grow-1">
              <div class="d-flex align-items-center gap-2 mb-1">
                <h3 class="fw-bold mb-0 text-dark">{{ $store->name }}</h3>
                @if ($store->status->value === 'verified')
                  <span class="text-success" title="Toko Terverifikasi">
                    <i class="ti ti-circle-check fs-6" role="img" aria-label="Terverifikasi"></i>
                  </span>
                @endif
              </div>
              <p class="text-muted mb-2 fs-3">ID: <code>{{ $store->id }}</code> | Terdaftar sejak {{ $store->created_at?->format('d F Y H:i') }}</p>
              
              <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach ($store->store_type ?? [] as $type)
                  <span class="badge bg-light-primary text-primary fw-semibold fs-2 text-capitalize">{{ $type->value ?? $type }}</span>
                @endforeach
              </div>
            </div>
          </div>

          <div class="row g-4 mb-4 border-top border-bottom py-3">
            <div class="col-3 text-center border-end">
              <h4 class="fw-bold mb-0 text-dark">{{ $store->listings_count }}</h4>
              <span class="text-muted fs-3">Total Listing</span>
            </div>
            <div class="col-3 text-center border-end">
              <h4 class="fw-bold mb-0 text-dark">{{ $store->offers_count }}</h4>
              <span class="text-muted fs-3">Penawaran</span>
            </div>
            <div class="col-3 text-center border-end">
              <h4 class="fw-bold mb-0 text-dark">{{ $store->orders_count }}</h4>
              <span class="text-muted fs-3">Pesanan Masuk</span>
            </div>
            <div class="col-3 text-center">
              <h4 class="fw-bold mb-0 text-dark">{{ $store->reviews_count }}</h4>
              <span class="text-muted fs-3">Total Ulasan</span>
            </div>
          </div>

          <h5 class="fw-bold mb-3 text-dark">Informasi Toko</h5>
          <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0">
              <tbody>
                <tr>
                  <td class="text-muted ps-0" style="width: 200px;">Alamat Toko</td>
                  <td class="text-dark" style="white-space: normal;">{{ $store->address ?? '—' }}</td>
                </tr>
                <tr>
                  <td class="text-muted ps-0">Kabupaten/Wilayah</td>
                  <td class="text-dark">{{ $store->regency ?? '—' }} (Kode: {{ $store->regency_code ?? '—' }})</td>
                </tr>
                <tr>
                  <td class="text-muted ps-0">Koordinat Lokasi</td>
                  <td class="text-dark">
                    @if ($store->latitude && $store->longitude)
                      <code>{{ $store->latitude }}, {{ $store->longitude }}</code>
                      <a href="https://www.google.com/maps/search/?api=1&query={{ $store->latitude }},{{ $store->longitude }}" target="_blank" class="ms-2 btn btn-xs btn-outline-primary py-0">
                        <i class="ti ti-map-pin" aria-hidden="true"></i> Lihat Peta
                      </a>
                    @else
                      <span class="text-muted">belum disetel</span>
                    @endif
                  </td>
                </tr>
                <tr>
                  <td class="text-muted ps-0">Radius Layanan</td>
                  <td class="text-dark fw-semibold">{{ $store->service_radius_km }} km</td>
                </tr>
                <tr>
                  <td class="text-muted ps-0">Metode Pengiriman</td>
                  <td class="text-dark">
                    <div class="d-flex gap-3">
                      <span class="text-{{ $store->allows_pickup ? 'success' : 'muted' }}"><i class="ti ti-{{ $store->allows_pickup ? 'circle-check' : 'circle-x' }} me-1" aria-hidden="true"></i> Ambil di Tempat (Pickup)</span>
                      <span class="text-{{ $store->offers_delivery ? 'success' : 'muted' }}"><i class="ti ti-{{ $store->offers_delivery ? 'circle-check' : 'circle-x' }} me-1" aria-hidden="true"></i> Pengiriman Toko</span>
                      <span class="text-{{ $store->accepts_cod ? 'success' : 'muted' }}"><i class="ti ti-{{ $store->accepts_cod ? 'circle-check' : 'circle-x' }} me-1" aria-hidden="true"></i> Bayar di Tempat (COD)</span>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted ps-0">Kategori Terpilih</td>
                  <td class="text-dark">
                    @forelse ($kategori as $kat)
                      <span class="badge bg-light-secondary text-secondary fw-semibold fs-2 mb-1">{{ $kat }}</span>
                    @empty
                      <span class="text-muted">—</span>
                    @endforelse
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <hr class="my-4 text-muted opacity-25">

          <h5 class="fw-bold mb-3 text-dark">Data Legal & Rekening</h5>
          <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0">
              <tbody>
                <tr>
                  <td class="text-muted ps-0" style="width: 200px;">NPWP Toko</td>
                  <td class="fw-semibold text-dark">{{ $store->npwp ?? '—' }}</td>
                </tr>
                <tr>
                  <td class="text-muted ps-0">Nomor Rekening Bank</td>
                  <td class="fw-semibold text-dark">{{ $store->bank_account ?? '—' }}</td>
                </tr>
                <tr>
                  <td class="text-muted ps-0">Atas Nama Rekening</td>
                  <td class="text-dark">{{ $store->bank_account_name ?? '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          @if ($store->operating_hours)
            <hr class="my-4 text-muted opacity-25">
            <h5 class="fw-bold mb-3 text-dark">Jam Operasional</h5>
            <div class="row row-cols-2 row-cols-md-4 g-3">
              @foreach (['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'] as $hari)
                @php
                  $jam = $store->operating_hours[$hari] ?? null;
                @endphp
                <div class="col">
                  <div class="border rounded p-2 text-center bg-light">
                    <span class="text-capitalize fw-bold text-dark fs-3 d-block mb-1">{{ $hari }}</span>
                    @if ($jam)
                      <span class="badge bg-success text-white fw-semibold fs-2">{{ $jam['open'] }} - {{ $jam['close'] }}</span>
                    @else
                      <span class="badge bg-secondary text-white fw-semibold fs-2">Tutup</span>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Side Actions & Owner Info -->
    <div class="col-lg-4">
      <!-- Owner Info Card -->
      <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Informasi Pemilik</h5>
          <div class="d-flex align-items-center gap-3 mb-3">
            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 48px; height: 48px;">
              {{ $store->owner?->initials }}
            </span>
            <div>
              <div class="d-flex align-items-center gap-2">
                <h6 class="fw-bold mb-0 text-dark">{{ $store->owner?->name }}</h6>
                @if ($store->owner?->verified_at)
                  <span class="text-success" title="KTP Terverifikasi">
                    <i class="ti ti-circle-check fs-4" role="img" aria-label="Terverifikasi"></i>
                  </span>
                @endif
              </div>
              <span class="fs-2 text-muted">{{ $store->owner?->phone }}</span>
            </div>
          </div>
          <a href="{{ route('admin.users.show', $store->user_id) }}" class="btn btn-sm btn-outline-primary w-100"><i class="ti ti-user me-1" aria-hidden="true"></i> Lihat Akun Pemilik</a>
        </div>
      </div>

      <!-- Verification Status & Actions -->
      <div class="card bg-light-subtle shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Status Toko</h5>
          <div class="d-flex align-items-center justify-content-between mb-4">
            <span class="badge bg-light-{{ $store->status->color() }} text-{{ $store->status->color() }} fw-bold px-3 py-2 fs-3">
              {{ $store->status->label() }}
            </span>
            @if ($store->is_active)
              <span class="badge bg-success text-white fw-bold px-3 py-2 fs-3">Aktif</span>
            @else
              <span class="badge bg-secondary text-white fw-bold px-3 py-2 fs-3">Nonaktif</span>
            @endif
          </div>

          <!-- Approve/Reject forms if pending -->
          @can('verify-stores')
            @if ($store->status->value === 'pending')
              <div class="border rounded p-3 bg-white mb-3">
                <h6 class="fw-bold text-dark mb-3">Aksi Verifikasi Toko</h6>
                
                <!-- Approve Form -->
                <form action="{{ route('admin.stores.approve', $store) }}" method="POST" class="mb-3">
                  @csrf
                  <button type="submit" class="btn btn-success w-100 btn-hover-shadow py-2 fw-semibold" {{ !$store->owner?->verified_at ? 'disabled' : '' }}>
                    <i class="ti ti-circle-check fs-5 me-1" aria-hidden="true"></i> Setujui Toko
                  </button>
                  @if (!$store->owner?->verified_at)
                    <div class="form-text text-danger fs-2 mt-1"><i class="ti ti-info-circle" aria-hidden="true"></i> Pemilik harus diverifikasi identitasnya terlebih dahulu.</div>
                  @endif
                </form>
                
                <hr class="my-3 text-muted opacity-25">
                
                <!-- Reject Form -->
                <form action="{{ route('admin.stores.reject', $store) }}" method="POST">
                  @csrf
                  <div class="mb-3">
                    <label for="reason" class="form-label fw-semibold text-dark fs-3">Alasan Penolakan</label>
                    <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Sebutkan alasan penolakan, minimal 10 karakter..." required></textarea>
                  </div>
                  <button type="submit" class="btn btn-outline-danger w-100 py-2 fw-semibold">
                    <i class="ti ti-circle-x fs-5 me-1" aria-hidden="true"></i> Tolak Toko
                  </button>
                </form>
              </div>
            @endif
          @endcan
        </div>
      </div>

      <!-- Audit Trail -->
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Jejak Audit</h5>
          <ul class="list-unstyled d-flex flex-column gap-3 mb-0 fs-3">
            <li class="d-flex justify-content-between">
              <span class="text-muted">Terdaftar Pada</span>
              <span class="text-dark fw-medium">{{ $store->created_at?->format('d M Y H:i') }}</span>
            </li>
            @if ($store->verified_at)
              <li class="d-flex justify-content-between">
                <span class="text-muted">Disetujui Oleh</span>
                <span class="text-dark fw-medium">{{ $store->verifiedBy?->name ?? '—' }}</span>
              </li>
              <li class="d-flex justify-content-between">
                <span class="text-muted">Tanggal Disetujui</span>
                <span class="text-dark fw-medium">{{ $store->verified_at?->format('d M Y H:i') }}</span>
              </li>
            @endif
            @if ($store->rejected_at)
              <li class="d-flex justify-content-between text-danger">
                <span class="text-muted">Ditolak Oleh</span>
                <span class="fw-semibold">{{ $store->rejectedBy?->name ?? '—' }}</span>
              </li>
              <li class="d-flex justify-content-between text-danger">
                <span class="text-muted">Tanggal Penolakan</span>
                <span class="fw-semibold">{{ $store->rejected_at?->format('d M Y H:i') }}</span>
              </li>
              <li class="border-top pt-2">
                <span class="text-muted d-block mb-1">Alasan Penolakan:</span>
                <p class="mb-0 text-dark bg-light rounded p-2">{{ $store->rejected_reason }}</p>
              </li>
            @endif
            @if ($store->blocked_at)
              <li class="d-flex justify-content-between text-danger">
                <span class="text-muted">Diblokir Oleh</span>
                <span class="fw-semibold">{{ $store->blockedBy?->name ?? '—' }}</span>
              </li>
              <li class="d-flex justify-content-between text-danger">
                <span class="text-muted">Tanggal Diblokir</span>
                <span class="fw-semibold">{{ $store->blocked_at?->format('d M Y H:i') }}</span>
              </li>
              <li class="border-top pt-2">
                <span class="text-muted d-block mb-1">Alasan Pemblokiran:</span>
                <p class="mb-0 text-dark bg-light rounded p-2">{{ $store->blocked_reason }}</p>
              </li>
            @endif
          </ul>
        </div>
      </div>
    </div>
  </div>
@endsection
