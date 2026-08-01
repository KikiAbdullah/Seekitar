@extends('admin.layouts.admin')

@section('title', 'Detail Penawaran Toko — Seekitar')

@section('content')
  <div class="row">
    <!-- Left: Offer details & Store details -->
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <div class="d-md-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
            <div>
              <span class="badge bg-light-primary text-primary fw-semibold fs-2 text-capitalize mb-2">
                ID Penawaran: <code>{{ $offer->id }}</code>
              </span>
              <h3 class="fw-bold mb-0 text-dark">Tawaran Harga untuk Permintaan</h3>
              <p class="text-muted mb-0 fs-3">Diajukan pada {{ $offer->created_at?->format('d M Y H:i') }}</p>
            </div>
            <div class="mt-3 mt-md-0">
              @php
                $o_colors = [
                  'pending' => 'warning',
                  'accepted' => 'success',
                  'rejected' => 'danger'
                ];
                $color = $o_colors[$offer->status->value ?? $offer->status] ?? 'secondary';
                $label = $offer->status->label() ?? $offer->status;
              @endphp
              <span class="badge bg-light-{{ $color }} text-{{ $color }} fw-bold px-3 py-2 fs-3">
                {{ $label }}
              </span>
            </div>
          </div>

          <h5 class="fw-bold mb-3 text-dark">Rincian Penawaran Harga</h5>
          <div class="table-responsive mb-4">
            <table class="table align-middle mb-0 fs-3">
              <tbody>
                <tr>
                  <td class="text-muted" style="width: 250px;">Harga Pekerjaan/Barang</td>
                  <td class="text-dark fw-semibold">Rp {{ number_format($offer->price, 0, ',', '.') }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Biaya Tambahan / Ongkos Kirim</td>
                  <td class="text-dark fw-semibold">Rp {{ number_format($offer->additional_cost, 0, ',', '.') }}</td>
                </tr>
                @if ($offer->additional_cost_note)
                  <tr>
                    <td class="text-muted">Keterangan Biaya Tambahan</td>
                    <td class="text-dark">{{ $offer->additional_cost_note }}</td>
                  </tr>
                @endif
                <tr class="bg-light">
                  <td class="text-muted fw-bold">Total Harga yang Dibayar Pembeli</td>
                  <td class="text-primary fw-bold fs-4">Rp {{ number_format($offer->price + $offer->additional_cost, 0, ',', '.') }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Estimasi Waktu Kerja / Pengiriman</td>
                  <td class="text-dark">{{ $offer->estimation_time ?? '—' }}</td>
                </tr>
                @if ($offer->estimated_hours)
                  <tr>
                    <td class="text-muted">Estimasi Durasi (Jam)</td>
                    <td class="text-dark">{{ $offer->estimated_hours }} jam</td>
                  </tr>
                @endif
                <tr>
                  <td class="text-muted">Batas Masa Berlaku Penawaran</td>
                  <td class="text-dark">
                    {{ $offer->expires_at?->format('d M Y H:i') }}
                    @if ($offer->status->value === 'pending' && $offer->expires_at)
                      <span class="badge bg-{{ $offer->expires_at->isPast() ? 'danger' : 'success' }} text-white fw-semibold fs-2 ms-2">
                        {{ $offer->expires_at->isPast() ? 'Kedaluwarsa' : 'Sisa: ' . $offer->expires_at->diffForHumans() }}
                      </span>
                    @endif
                  </td>
                </tr>
                <tr>
                  <td class="text-muted">ID Pembeli Peminta</td>
                  <td class="text-dark"><code>{{ $offer->request?->user_id ?? '—' }}</code></td>
                </tr>
              </tbody>
            </table>
          </div>

          @if ($offer->notes)
            <div class="border rounded p-3 bg-light mb-4">
              <h6 class="fw-bold text-dark mb-2">Catatan Tambahan Toko:</h6>
              <p class="mb-0 text-dark fs-3" style="white-space: pre-wrap; line-height: 1.6;">{{ $offer->notes }}</p>
            </div>
          @endif
        </div>
      </div>
      
      <!-- Store / Seller Info -->
      @if ($offer->store)
        <div class="card shadow-sm mt-4">
          <div class="card-body p-4">
            <h5 class="fw-bold mb-3 text-dark">Toko Penjual (Penawar)</h5>
            <div class="d-flex align-items-center gap-3 mb-3">
              <img src="{{ $offer->store->photo ? asset('storage/' . $offer->store->photo) : 'https://placehold.co/100x100?text=Toko' }}" class="rounded-circle border" width="60" height="60" style="object-fit: cover;">
              <div>
                <div class="d-flex align-items-center gap-2">
                  <h6 class="fw-bold mb-0 text-dark">{{ $offer->store->name }}</h6>
                  @if ($offer->store->status->value === 'verified')
                    <span class="text-success" title="Toko Terverifikasi">
                      <i class="ti ti-circle-check fs-4" role="img" aria-label="Terverifikasi"></i>
                    </span>
                  @endif
                </div>
                <p class="text-muted mb-0 fs-3">Alamat: {{ $offer->store->address }}</p>
                <p class="fs-2 text-muted mb-0 mt-1">
                  @if ($offer->store->total_reviews > 0)
                    <i class="ti ti-star text-warning me-1" aria-hidden="true"></i> {{ \App\Support\Angka::desimal($offer->store->rating_avg, 1) }} ({{ $offer->store->total_reviews }} ulasan)
                  @else
                    Toko baru — belum ada ulasan
                  @endif
                </p>
              </div>
            </div>
            <a href="{{ route('admin.stores.show', $offer->store_id) }}" class="btn btn-sm btn-outline-primary w-100"><i class="ti ti-building-store me-1" aria-hidden="true"></i> Detail Toko Penjual</a>
          </div>
        </div>
      @endif
    </div>

    <!-- Right: Origin Request & Buyer Info -->
    <div class="col-lg-4">
      @if ($offer->request)
        <!-- Request Info Card -->
        <div class="card shadow-sm mb-4">
          <div class="card-body p-4">
            <h5 class="fw-bold mb-3 text-dark">Permintaan Asal</h5>
            <h6 class="fw-bold text-dark mb-2">{{ $offer->request->title }}</h6>
            <p class="text-muted fs-3 mb-3" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">{{ $offer->request->description }}</p>
            <a href="{{ route('admin.requests.show', $offer->request_id) }}" class="btn btn-sm btn-outline-primary w-100"><i class="ti ti-rectangle me-1" aria-hidden="true"></i> Detail Permintaan</a>
          </div>
        </div>

        <!-- Buyer Info Card -->
        @if ($offer->request->user)
          <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
              <h5 class="fw-bold mb-3 text-dark">Informasi Pembeli</h5>
              <div class="d-flex align-items-center gap-3 mb-3">
                <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 48px; height: 48px;">
                  {{ $offer->request->user->initials }}
                </span>
                <div>
                  <div class="d-flex align-items-center gap-2">
                    <h6 class="fw-bold mb-0 text-dark">{{ $offer->request->user->name }}</h6>
                    @if ($offer->request->user->verified_at)
                      <span class="text-success" title="KTP Terverifikasi">
                        <i class="ti ti-circle-check fs-4" role="img" aria-label="Terverifikasi"></i>
                      </span>
                    @endif
                  </div>
                  <span class="fs-2 text-muted">{{ $offer->request->user->phone }}</span>
                </div>
              </div>
              <a href="{{ route('admin.users.show', $offer->request->user_id) }}" class="btn btn-sm btn-outline-primary w-100"><i class="ti ti-user me-1" aria-hidden="true"></i> Detail Akun Pembeli</a>
            </div>
          </div>
        @endif
      @endif

      <!-- Policy Info -->
      <div class="card bg-light shadow-sm">
        <div class="card-body p-4 text-center">
          <i class="ti ti-info-circle text-muted fs-8 mb-2" aria-hidden="true"></i>
          <p class="mb-0 fs-3 text-muted">Penawaran bersifat mengikat secara hukum. Perubahan atau penghapusan hanya boleh dilakukan oleh pihak penjual/pembeli.</p>
        </div>
      </div>
    </div>
  </div>
@endsection
