@extends('admin.layouts.admin')

@section('title', 'Detail Langganan — Seekitar')

@section('content')
  <div class="row">
    <!-- Left: Subscription details -->
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <div class="d-md-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
            <div>
              <span class="badge bg-light-primary text-primary fw-semibold fs-2 text-capitalize mb-2">
                {{ $subscription->planLabel() }}
              </span>
              <h3 class="fw-bold mb-0 text-dark">ID Transaksi Langganan: <code>{{ $subscription->id }}</code></h3>
              <p class="text-muted mb-0 fs-3">Tanggal Transaksi: {{ $subscription->created_at?->format('d F Y H:i') }}</p>
            </div>
            <div class="mt-3 mt-md-0">
              @php
                $s_colors = [
                  'active' => 'success',
                  'cancelled' => 'danger',
                  'expired' => 'secondary',
                  'pending' => 'warning'
                ];
                $color = $s_colors[$subscription->status] ?? 'secondary';
                $labels = [
                  'active' => 'Aktif',
                  'cancelled' => 'Dibatalkan',
                  'expired' => 'Kedaluwarsa',
                  'pending' => 'Menunggu'
                ];
                $label = $labels[$subscription->status] ?? $subscription->status;
              @endphp
              <span class="badge bg-light-{{ $color }} text-{{ $color }} fw-bold px-3 py-2 fs-3">
                {{ $label }}
              </span>
            </div>
          </div>

          <h5 class="fw-bold mb-3 text-dark">Informasi Billing</h5>
          <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0 fs-3">
              <tbody>
                <tr>
                  <td class="text-muted ps-0" style="width: 200px;">Biaya Langganan</td>
                  <td class="fw-bold text-dark fs-4">Rp {{ number_format($subscription->amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                  <td class="text-muted ps-0">Tanggal Mulai Masa Aktif</td>
                  <td class="text-dark">{{ $subscription->starts_at?->format('d F Y') }}</td>
                </tr>
                <tr>
                  <td class="text-muted ps-0">Tanggal Selesai Masa Aktif</td>
                  <td class="text-dark">{{ $subscription->ends_at?->format('d F Y') }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      
      <!-- Store & Owner Info Cards -->
      <div class="row g-4 mt-1">
        <!-- Store info -->
        @if ($subscription->store)
          <div class="col-md-6">
            <div class="card shadow-sm h-100">
              <div class="card-body p-4">
                <h5 class="fw-bold mb-3 text-dark">Toko Penerima Manfaat</h5>
                <div class="d-flex align-items-center gap-3 mb-3">
                  <img src="{{ $subscription->store->photo ? asset('storage/' . $subscription->store->photo) : 'https://placehold.co/100x100?text=Toko' }}" class="rounded border" width="48" height="48" style="object-fit: cover;">
                  <div>
                    <h6 class="fw-bold mb-0 text-dark">{{ $subscription->store->name }}</h6>
                    <span class="fs-2 text-muted">ID: {{ $subscription->store->id }}</span>
                  </div>
                </div>
                <a href="{{ route('admin.stores.show', $subscription->store_id) }}" class="btn btn-sm btn-outline-primary w-100"><i class="ti ti-store me-1"></i> Detail Toko</a>
              </div>
            </div>
          </div>
        @endif

        <!-- User info -->
        @if ($subscription->user)
          <div class="col-md-6">
            <div class="card shadow-sm h-100">
              <div class="card-body p-4">
                <h5 class="fw-bold mb-3 text-dark">Akun Pembayar</h5>
                <div class="d-flex align-items-center gap-3 mb-3">
                  <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 48px; height: 48px;">
                    {{ $subscription->user->initials }}
                  </span>
                  <div>
                    <h6 class="fw-bold mb-0 text-dark">{{ $subscription->user->name }}</h6>
                    <span class="fs-2 text-muted">{{ $subscription->user->phone }}</span>
                  </div>
                </div>
                <a href="{{ route('admin.users.show', $subscription->user_id) }}" class="btn btn-sm btn-outline-primary w-100"><i class="ti ti-user me-1"></i> Detail Pengguna</a>
              </div>
            </div>
          </div>
        @endif
      </div>
    </div>

    <!-- Actions Sidebar -->
    <div class="col-lg-4">
      @if ($subscription->status === 'active')
        @can('manage-subscriptions')
          <div class="card bg-light-danger shadow-sm">
            <div class="card-body p-4">
              <h5 class="fw-bold text-danger mb-2">Batalkan Langganan</h5>
              <p class="fs-3 text-dark-danger mb-4">Membatalkan paket langganan secara paksa. Toko akan kehilangan benefit langganan PRO atau boost listing secara instan.</p>
              
              <form action="{{ route('admin.subscriptions.cancel', $subscription) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan langganan toko ini?');">
                @csrf
                <button type="submit" class="btn btn-danger w-100 btn-hover-shadow py-2 fw-semibold">
                  <i class="ti ti-circle-x fs-5 me-1"></i> Batalkan Langganan
                </button>
              </form>
            </div>
          </div>
        @endcan
      @else
        <div class="card bg-light shadow-sm">
          <div class="card-body p-4 text-center">
            <i class="ti ti-info-circle text-muted fs-8 mb-2"></i>
            <p class="mb-0 fs-3 text-muted">Langganan ini sudah tidak aktif dan tidak dapat dibatalkan kembali.</p>
          </div>
        </div>
      @endif
    </div>
  </div>
@endsection
