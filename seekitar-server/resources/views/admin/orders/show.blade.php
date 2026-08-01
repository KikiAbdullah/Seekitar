@extends('admin.layouts.admin')

@section('title', 'Detail Pesanan — Seekitar')

@section('content')
<div class="row">
  {{-- Kolom Kiri: Info Pesanan, Listing, Pembayaran --}}
  <div class="col-lg-8">

    {{-- Header Pesanan --}}
    <div class="card shadow-sm mb-4">
      <div class="card-body p-4">
        <div class="d-md-flex align-items-start justify-content-between border-bottom pb-3 mb-4">
          <div>
            <h4 class="fw-bold mb-1 text-dark">Pesanan #{{ $order->order_number }}</h4>
            <p class="text-muted fs-3 mb-0">
              Dibuat {{ $order->created_at?->format('d M Y H:i') }}
              &bull; Tipe: <strong>{{ $order->order_type?->label() }}</strong>
              &bull; Metode: <strong>{{ $order->delivery_method?->label() }}</strong>
            </p>
          </div>
          <div class="mt-3 mt-md-0">
            @include('admin.partials._order_badge', ['order' => $order])
          </div>
        </div>

        {{-- Ringkasan Harga --}}
        <div class="row g-3 mb-4">
          <div class="col-sm-4">
            <div class="border rounded p-3 bg-light text-center">
              <span class="text-muted fs-2 d-block mb-1">Kuantitas</span>
              <h5 class="fw-bold mb-0 text-dark">{{ $order->quantity }} unit</h5>
            </div>
          </div>
          <div class="col-sm-4">
            <div class="border rounded p-3 bg-light text-center">
              <span class="text-muted fs-2 d-block mb-1">Biaya Layanan</span>
              <h5 class="fw-bold mb-0 text-dark">Rp {{ number_format($order->service_fee ?? 0, 0, ',', '.') }}</h5>
            </div>
          </div>
          <div class="col-sm-4">
            <div class="border rounded p-3 bg-light-primary text-center">
              <span class="text-muted fs-2 d-block mb-1">Total Dibayar</span>
              <h5 class="fw-bold mb-0 text-primary">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</h5>
            </div>
          </div>
        </div>

        {{-- Bukti Bayar --}}
        @if ($order->payment_proof_url)
          <div class="border rounded p-3 bg-light d-flex align-items-center justify-content-between mb-0">
            <div class="d-flex align-items-center gap-2">
              <i class="ti ti-photo text-primary fs-5" aria-hidden="true"></i>
              <div>
                <span class="fw-semibold text-dark fs-3 d-block">Bukti Pembayaran Tersedia</span>
                <span class="fs-2 text-muted">Dokumen privat — diakses lewat route aman</span>
              </div>
            </div>
            <a href="{{ route('admin.orders.payment-proof', $order) }}" target="_blank" class="btn btn-sm btn-primary">
              <i class="ti ti-download me-1" aria-hidden="true"></i> Lihat Bukti Bayar
            </a>
          </div>
        @else
          <div class="alert alert-warning py-2 px-3 mb-0 fs-3">Bukti pembayaran belum diunggah pembeli.</div>
        @endif
      </div>
    </div>

    {{-- Listing / Produk --}}
    @if ($order->listing)
      <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Produk / Jasa Dipesan</h5>
          <div class="d-flex align-items-center gap-3">
            @php
              $imgs = is_array($order->listing->images) ? $order->listing->images : json_decode($order->listing->images, true);
              $cover = !empty($imgs) ? asset('storage/' . $imgs[0]) : 'https://placehold.co/80x80?text=Listing';
            @endphp
            <img src="{{ $cover }}" class="rounded border" width="64" height="64" style="object-fit:cover;" onerror="this.src='https://placehold.co/80x80?text=Listing'">
            <div class="flex-grow-1">
              <h6 class="fw-bold mb-1 text-dark">{{ $order->listing->title }}</h6>
              <span class="badge bg-light-primary text-primary fs-2 text-capitalize me-1">{{ $order->listing->listing_type?->label() }}</span>
              <span class="badge bg-light-{{ $order->listing->status->color() }} text-{{ $order->listing->status->color() }} fs-2">{{ $order->listing->status->label() }}</span>
            </div>
            <div class="text-end">
              <span class="fw-bold text-primary fs-4">Rp {{ number_format($order->listing->price ?? 0, 0, ',', '.') }}</span>
              <a href="{{ route('admin.listings.show', $order->listing_id) }}" class="d-block btn btn-xs btn-light-info text-info mt-1"><i class="ti ti-search" aria-hidden="true"></i> Detail Listing</a>
            </div>
          </div>
        </div>
      </div>
    @endif

    {{-- Penawaran / Offer terkait --}}
    @if ($order->offer)
      <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Penawaran Terkait</h5>
          <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0 fs-3">
              <tbody>
                <tr>
                  <td class="text-muted ps-0" style="width:200px">Permintaan Asal</td>
                  <td class="fw-semibold text-dark">{{ $order->offer->request?->title ?? '—' }}</td>
                </tr>
                <tr>
                  <td class="text-muted ps-0">Harga Penawaran</td>
                  <td class="text-dark">Rp {{ number_format($order->offer->price, 0, ',', '.') }}</td>
                </tr>
                <tr>
                  <td class="text-muted ps-0">Biaya Tambahan</td>
                  <td class="text-dark">Rp {{ number_format($order->offer->additional_cost, 0, ',', '.') }}</td>
                </tr>
                <tr>
                  <td class="text-muted ps-0">Estimasi Waktu</td>
                  <td class="text-dark">{{ $order->offer->estimation_time ?? '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <a href="{{ route('admin.offers.show', $order->offer_id) }}" class="btn btn-sm btn-outline-primary mt-3"><i class="ti ti-search me-1" aria-hidden="true"></i> Detail Penawaran</a>
        </div>
      </div>
    @endif

    {{-- Dispute terkait --}}
    @if ($order->disputes->isNotEmpty())
      <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-danger"><i class="ti ti-alert-triangle me-1" aria-hidden="true"></i> Laporan Masalah (Dispute)</h5>
          @foreach ($order->disputes as $d)
            <div class="border rounded p-3 mb-3 {{ $d->status->value === 'open' ? 'border-danger' : 'border-secondary' }}">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div>
                  @php
                    $reasons = [
                      'item_not_received'=>'Barang Tidak Diterima',
                      'item_damaged'=>'Barang Rusak/Cacat',
                      'item_not_matching'=>'Barang Tidak Sesuai',
                      'others'=>'Lainnya'
                    ];
                  @endphp
                  <span class="fw-semibold text-dark fs-3">{{ $reasons[$d->reason->value ?? $d->reason] ?? $d->reason }}</span>
                  <span class="fs-2 text-muted d-block">Dilaporkan {{ $d->created_at?->format('d M Y H:i') }} oleh {{ $d->reporter?->name }}</span>
                </div>
                <span class="badge bg-light-{{ $d->status->value === 'open' ? 'danger' : 'success' }} text-{{ $d->status->value === 'open' ? 'danger' : 'success' }} fw-bold">
                  {{ $d->status->value === 'open' ? 'Terbuka' : 'Selesai' }}
                </span>
              </div>
              @if ($d->resolution_note)
                <div class="bg-light rounded p-2 fs-3 text-muted">Keputusan: {{ $d->resolution_note }}</div>
              @endif
              <a href="{{ route('admin.disputes.show', $d->id) }}" class="btn btn-xs btn-light-info text-info mt-2"><i class="ti ti-search me-1" aria-hidden="true"></i> Detail Laporan</a>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    {{-- Ulasan --}}
    @if ($order->reviews->isNotEmpty())
      <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Ulasan pada Pesanan Ini</h5>
          @foreach ($order->reviews as $rev)
            <div class="border-bottom pb-3 mb-3">
              <div class="d-flex align-items-center gap-2 mb-1">
                <span class="fw-semibold text-dark fs-3">{{ $rev->reviewer?->name }}</span>
                <div class="text-warning ms-1">
                  @for ($i = 1; $i <= 5; $i++)
                    <i class="ti ti-star {{ $i <= $rev->rating ? '' : 'text-muted' }}" aria-hidden="true"></i>
                  @endfor
                  <span class="fs-2 text-muted ms-1">({{ $rev->rating }})</span>
                </div>
              </div>
              <p class="mb-0 fs-3 text-muted">{{ $rev->comment ?? 'Tanpa komentar teks.' }}</p>
            </div>
          @endforeach
        </div>
      </div>
    @endif

  </div>

  {{-- Kolom Kanan: Para Pihak, Pengiriman, Pembatalan --}}
  <div class="col-lg-4">

    {{-- Pembeli --}}
    <div class="card shadow-sm mb-4">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-3 text-dark">Pembeli</h5>
        <div class="d-flex align-items-center gap-3 mb-3">
          <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width:48px;height:48px;">
            {{ $order->buyer?->initials }}
          </span>
          <div>
            <div class="d-flex align-items-center gap-2">
              <h6 class="fw-bold mb-0 text-dark">{{ $order->buyer?->name }}</h6>
              @if ($order->buyer?->verified_at)
                <span class="text-success" title="KTP Terverifikasi"><i class="ti ti-circle-check fs-4" role="img" aria-label="Terverifikasi"></i></span>
              @endif
            </div>
            <span class="fs-2 text-muted">{{ $order->buyer?->phone }}</span>
          </div>
        </div>
        <a href="{{ route('admin.users.show', $order->buyer_id) }}" class="btn btn-sm btn-outline-primary w-100"><i class="ti ti-user me-1" aria-hidden="true"></i> Detail Akun</a>
      </div>
    </div>

    {{-- Toko --}}
    <div class="card shadow-sm mb-4">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-3 text-dark">Toko Penjual</h5>
        <div class="d-flex align-items-center gap-3 mb-3">
          <img src="{{ $order->store?->photo ? asset('storage/'.$order->store->photo) : 'https://placehold.co/80x80?text=Toko' }}" class="rounded-circle border" width="48" height="48" style="object-fit:cover;">
          <div>
            <div class="d-flex align-items-center gap-2">
              <h6 class="fw-bold mb-0 text-dark">{{ $order->store?->name }}</h6>
              @if ($order->store?->status->value === 'verified')
                <span class="text-success" title="Toko Terverifikasi"><i class="ti ti-circle-check fs-4" role="img" aria-label="Terverifikasi"></i></span>
              @endif
            </div>
            <span class="fs-2 text-muted">{{ $order->store?->regency }}</span>
          </div>
        </div>
        <a href="{{ route('admin.stores.show', $order->store_id) }}" class="btn btn-sm btn-outline-primary w-100"><i class="ti ti-building-store me-1" aria-hidden="true"></i> Detail Toko</a>
      </div>
    </div>

    {{-- Alamat Pengiriman --}}
    @if ($order->delivery_method?->value === 'delivery')
      <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark">Lokasi Antar</h5>
          <p class="fs-3 text-dark mb-2" style="white-space:normal">{{ $order->shipping_address ?? '—' }}</p>
          @if ($order->latitude && $order->longitude)
            <code class="fs-2 d-block mb-3">{{ $order->latitude }}, {{ $order->longitude }}</code>
            <a href="https://www.google.com/maps/search/?api=1&query={{ $order->latitude }},{{ $order->longitude }}" target="_blank" class="btn btn-sm btn-light-primary text-primary w-100">
              <i class="ti ti-map-pin me-1" aria-hidden="true"></i> Buka di Google Maps
            </a>
          @endif
        </div>
      </div>
    @endif

    {{-- Pembatalan --}}
    @if ($order->status->value === 'dibatalkan' && $order->cancellation_reason)
      <div class="card bg-light-danger shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-bold text-danger mb-2"><i class="ti ti-ban me-1" aria-hidden="true"></i> Dibatalkan</h5>
          @if ($order->cancelledBy)
            <p class="fs-3 text-dark mb-1">Oleh: <strong>{{ $order->cancelledBy->name }}</strong></p>
            <p class="fs-3 text-dark mb-2">Pada: {{ $order->cancelled_at?->format('d M Y H:i') }}</p>
          @endif
          <div class="bg-white rounded p-2 fs-3 text-muted">{{ $order->cancellation_reason }}</div>
        </div>
      </div>
    @endif

  </div>
</div>
@endsection
