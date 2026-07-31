@extends('admin.layouts.admin')

@section('title', 'Detail Permintaan Belanja — Seekitar')

@section('content')
  <div class="row">
    <!-- Left: Request details & Offers -->
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <div class="d-md-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
            <div>
              <span class="badge bg-light-primary text-primary fw-semibold fs-2 text-capitalize mb-2">
                {{ $request->category?->name ?? 'Kategori Umum' }}
              </span>
              <h3 class="fw-bold mb-0 text-dark">{{ $request->title }}</h3>
              <p class="text-muted mb-0 fs-3">ID: <code>{{ $request->id }}</code> | Disiarkan {{ $request->created_at?->format('d M Y H:i') }}</p>
            </div>
            <div class="mt-3 mt-md-0">
              @php
                $r_colors = [
                  'open' => 'success',
                  'closed' => 'secondary',
                  'expired' => 'danger'
                ];
                $color = $r_colors[$request->status->value ?? $request->status] ?? 'secondary';
                $label = $request->status->label() ?? $request->status;
              @endphp
              <span class="badge bg-light-{{ $color }} text-{{ $color }} fw-bold px-3 py-2 fs-3">
                {{ $label }}
              </span>
            </div>
          </div>

          <h5 class="fw-bold mb-3 text-dark">Kebutuhan Belanja</h5>
          <div class="text-dark fs-3 mb-4" style="white-space: pre-wrap; line-height: 1.6;">{{ $request->description }}</div>
          
          <div class="row g-3 mb-4">
            <div class="col-sm-6">
              <div class="border rounded p-2 bg-light">
                <span class="text-muted fs-2 d-block">Anggaran Belanja</span>
                <span class="fw-bold text-dark fs-4">
                  @if ($request->budget_min && $request->budget_max)
                    Rp {{ number_format($request->budget_min, 0, ',', '.') }} – Rp {{ number_format($request->budget_max, 0, ',', '.') }}
                  @elseif ($request->budget_min)
                    Mulai dari Rp {{ number_format($request->budget_min, 0, ',', '.') }}
                  @elseif ($request->budget_max)
                    Hingga Rp {{ number_format($request->budget_max, 0, ',', '.') }}
                  @else
                    Tawar-menawar (Nego)
                  @endif
                </span>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="border rounded p-2 bg-light">
                <span class="text-muted fs-2 d-block">Batas Waktu (Expired)</span>
                <span class="fw-bold text-dark fs-4">{{ $request->expires_at?->format('d M Y H:i') }}</span>
              </div>
            </div>
          </div>

          <!-- Accepted Offer Prominently -->
          @if ($request->acceptedOffer)
            <div class="alert alert-success border-0 shadow-sm p-4 mb-4" role="alert">
              <h5 class="alert-heading fw-bold mb-3"><i class="ti ti-circle-check"></i> Penawaran Diterima Pembeli</h5>
              <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                  <img src="{{ $request->acceptedOffer->store->photo ? asset('storage/' . $request->acceptedOffer->store->photo) : 'https://placehold.co/100x100?text=Toko' }}" class="rounded-circle border" width="48" height="48" style="object-fit: cover;">
                  <div>
                    <h6 class="fw-bold mb-0 text-dark">{{ $request->acceptedOffer->store->name }}</h6>
                    <span class="fs-2 text-muted">ID Toko: {{ $request->acceptedOffer->store_id }}</span>
                  </div>
                </div>
                <div class="text-end">
                  <h5 class="fw-bold text-success mb-0">Rp {{ number_format($request->acceptedOffer->price + $request->acceptedOffer->additional_cost, 0, ',', '.') }}</h5>
                  <span class="fs-2 text-muted">Estimasi: {{ $request->acceptedOffer->estimation_time ?? '—' }}</span>
                </div>
              </div>
            </div>
          @endif
        </div>
      </div>

      <!-- Offers List -->
      <div class="card shadow-sm mt-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Semua Penawaran Masuk ({{ $request->offers->count() }})</h5>
          @if ($request->offers->isEmpty())
            <p class="text-muted fs-3 mb-0">Belum ada toko yang mengirim penawaran untuk permintaan ini.</p>
          @else
            <div class="table-responsive">
              <table class="table align-middle text-nowrap mb-0">
                <thead>
                  <tr class="text-muted fw-semibold">
                    <th scope="col">Toko Penawar</th>
                    <th scope="col">Harga Penawaran</th>
                    <th scope="col">Biaya Tambahan</th>
                    <th scope="col">Estimasi Waktu</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($request->offers as $off)
                    <tr>
                      <td>
                        <div class="d-flex align-items-center gap-3">
                          <img src="{{ $off->store->photo ? asset('storage/' . $off->store->photo) : 'https://placehold.co/100x100?text=Toko' }}" class="rounded-circle border" width="40" height="40" style="object-fit: cover;">
                          <div>
                            <h6 class="fw-semibold mb-0 fs-3">{{ $off->store->name }}</h6>
                            <span class="fs-2 text-muted">Rating: {{ $off->store->total_reviews > 0 ? '★ ' . number_format($off->store->rating_avg, 1) : 'baru' }}</span>
                          </div>
                        </div>
                      </td>
                      <td>
                        <span class="fw-semibold text-dark fs-3">Rp {{ number_format($off->price, 0, ',', '.') }}</span>
                      </td>
                      <td>
                        <span class="text-dark fs-3">Rp {{ number_format($off->additional_cost, 0, ',', '.') }}</span>
                      </td>
                      <td>
                        <span class="text-muted fs-3">{{ $off->estimation_time ?? '—' }}</span>
                      </td>
                      <td>
                        @php
                          $o_class = $off->status->value === 'accepted' ? 'success' : ($off->status->value === 'rejected' ? 'danger' : 'warning');
                          $o_label = $off->status->value === 'accepted' ? 'Diterima' : ($off->status->value === 'rejected' ? 'Ditolak' : 'Menunggu');
                        @endphp
                        <span class="badge bg-light-{{ $o_class }} text-{{ $o_class }} fw-semibold fs-2">{{ $o_label }}</span>
                      </td>
                      <td class="text-end">
                        <a href="{{ route('admin.offers.show', $off->id) }}" class="btn btn-xs btn-light-info text-info"><i class="ti ti-eye"></i> Detail</a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Right: Buyer Info & Actions -->
    <div class="col-lg-4">
      <!-- Buyer Info Card -->
      <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Informasi Pembeli</h5>
          <div class="d-flex align-items-center gap-3 mb-3">
            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 48px; height: 48px;">
              {{ $request->user?->initials }}
            </span>
            <div>
              <div class="d-flex align-items-center gap-2">
                <h6 class="fw-bold mb-0 text-dark">{{ $request->user?->name }}</h6>
                @if ($request->user?->verified_at)
                  <span class="text-success" title="KTP Terverifikasi">
                    <i class="fa-solid fa-circle-check fs-4"></i>
                  </span>
                @endif
              </div>
              <span class="fs-2 text-muted">{{ $request->user?->phone }}</span>
            </div>
          </div>
          <a href="{{ route('admin.users.show', $request->user_id) }}" class="btn btn-sm btn-outline-primary w-100"><i class="ti ti-user me-1"></i> Detail Akun Pembeli</a>
        </div>
      </div>

      <!-- GPS Map Card -->
      @if ($request->latitude && $request->longitude)
        <div class="card shadow-sm mb-4">
          <div class="card-body p-4">
            <h5 class="fw-bold mb-3 text-dark">Titik Siar GPS</h5>
            <p class="fs-3 text-muted mb-3">Lokasi domisili pembeli saat menyiarkan permintaan ini.</p>
            <code>{{ $request->latitude }}, {{ $request->longitude }}</code>
            <a href="https://www.google.com/maps/search/?api=1&query={{ $request->latitude }},{{ $request->longitude }}" target="_blank" class="btn btn-sm btn-light-primary text-primary w-100 mt-3">
              <i class="ti ti-map-pin"></i> Buka di Google Maps
            </a>
          </div>
        </div>
      @endif

      <!-- Audit and Extend Actions -->
      <div class="card bg-light-subtle shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Manajemen Sistem</h5>
          <ul class="list-unstyled d-flex flex-column gap-3 mb-4 fs-3">
            <li class="d-flex justify-content-between">
              <span class="text-muted">Kali Diperpanjang</span>
              <span class="text-dark fw-bold">{{ $request->extension_count }} kali</span>
            </li>
            @if ($request->extended_at)
              <li class="d-flex justify-content-between">
                <span class="text-muted">Perpanjangan Terakhir</span>
                <span class="text-dark fw-medium">{{ $request->extended_at?->format('d M Y H:i') }}</span>
              </li>
            @endif
          </ul>

          <!-- Extend Button (POST) -->
          @can('manage-requests')
            @if ($request->status->value !== 'closed')
              <form action="{{ route('admin.requests.extend', $request) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-warning w-100 btn-hover-shadow py-2 fw-semibold" onclick="return confirm('Apakah Anda yakin ingin memperpanjang masa aktif permintaan ini selama 24 jam?');">
                  <i class="ti ti-clock me-1"></i> Perpanjang 24 Jam (SLA)
                </button>
              </form>
            @else
              <div class="alert alert-secondary mb-0 text-center py-2" role="alert">
                <span class="fs-3 text-muted">Permintaan sudah ditutup oleh pembeli</span>
              </div>
            @endif
          @endcan
        </div>
      </div>
    </div>
  </div>
@endsection
