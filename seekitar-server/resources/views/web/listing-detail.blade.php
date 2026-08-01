@extends('web.layout')

@php
  $tipe = $listing->listing_type->value;
@endphp

@section('title', $listing->title . ' — ' . $listing->store?->name . ' — Seekitar')
@section('meta_description', Str::limit(strip_tags($listing->description), 160))

@push('head')
  <meta property="og:image" content="{{ $listing->images[0] }}">
  <meta property="og:type" content="product">
  <meta property="product:price:amount" content="{{ $listing->price }}">
  <meta property="product:price:currency" content="IDR">
@endpush

@push('styles')
  <style>
    .detail-gallery {
      border-radius: 1.5rem;
      overflow: hidden;
      aspect-ratio: 4/3;
      background: var(--bs-primary-bg-subtle);
      position: relative;
    }
    .detail-gallery img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .detail-gallery .gallery-badge {
      position: absolute;
      top: 1rem;
      left: 1rem;
      z-index: 2;
    }
    .detail-price {
      font-size: calc(1.8rem + .5vw);
      font-weight: 800;
      color: var(--bs-primary);
      line-height: 1;
    }
    .detail-store-avatar {
      width: 56px;
      height: 56px;
      border-radius: 1rem;
      background: var(--bs-primary-bg-subtle);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 1.25rem;
      color: var(--bs-primary);
      flex-shrink: 0;
    }
    .detail-info-row {
      display: flex;
      align-items: center;
      gap: .65rem;
      padding: .65rem 0;
      border-bottom: 1px solid var(--bs-border-color);
    }
    .detail-info-row:last-child {
      border-bottom: 0;
    }
    .detail-info-icon {
      width: 38px;
      height: 38px;
      border-radius: .75rem;
      background: var(--bs-primary-bg-subtle);
      color: var(--bs-primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      flex-shrink: 0;
    }
    .app-cta-card {
      background: linear-gradient(135deg, var(--bs-primary) 0%, #0d6e3a 100%);
      border-radius: 1.25rem;
      color: #fff;
      position: relative;
      overflow: hidden;
    }
    .app-cta-card::before {
      content: "";
      position: absolute;
      width: 280px;
      height: 280px;
      top: -60px;
      right: -60px;
      border-radius: 50%;
      background: rgba(255,255,255,.06);
      pointer-events: none;
    }
  </style>
@endpush

@section('content')

  {{-- ============================ BREADCRUMB ============================ --}}
  <section class="page-hero">
    <div class="container">
      <div class="pt-8 pt-lg-10 pb-4 pb-lg-6">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a class="text-hover-primary" href="{{ route('web.home') }}">Beranda</a></li>
            <li class="breadcrumb-item"><a class="text-hover-primary" href="{{ route('web.listings') }}">Cari</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($listing->title, 40) }}</li>
          </ol>
        </nav>
      </div>
    </div>
  </section>

  {{-- ============================ DETAIL ============================ --}}
  <section class="py-6 py-lg-8">
    <div class="container">
      <div class="row g-5 g-lg-7">
        {{-- Kolom kiri: gambar --}}
        <div class="col-lg-7" data-aos="fade-up" data-aos-duration="900">
          <div class="detail-gallery">
            <img src="{{ $listing->images[0] }}" alt="{{ $listing->title }}" fetchpriority="high"
              onerror="this.onerror=null; this.src='https://placehold.co/1200x900/E7F6EC/168A4A?text=Seekitar'">
            <span class="badge gallery-badge text-bg-{{ $listing->listing_type->color() }} fs-4 px-3 py-2">
              <i class="ti ti-{{ $tipe === 'product' ? 'shopping-bag' : ($tipe === 'service' ? 'tool' : 'calendar-month') }} me-1"></i>
              {{ $listing->listing_type->label() }}
            </span>
          </div>
        </div>

        {{-- Kolom kanan: info & CTA --}}
        <div class="col-lg-5" data-aos="fade-up" data-aos-delay="150" data-aos-duration="900">
          <h1 class="fs-7 fw-bolder mb-2 lh-sm">{{ $listing->title }}</h1>

          {{-- Harga --}}
          <div class="detail-price mb-2">
            @if ($listing->price !== null)
              {{ \App\Support\Angka::rupiah($listing->price) }}
            @else
              Hubungi Penjual
            @endif
          </div>
          @if ($listing->price !== null && $listing->listing_type->value === 'rental')
            <span class="badge bg-primary-subtle text-primary mb-3">per hari / per periode sewa</span>
          @endif

          {{-- Toko --}}
          <div class="d-flex align-items-center gap-3 mb-4 mt-3">
            <div class="detail-store-avatar">
              {{ Str::substr($listing->store?->name ?? 'T', 0, 2) }}
            </div>
            <div>
              <div class="fw-semibold fs-5 d-flex align-items-center gap-1">
                {{ $listing->store?->name }}
                @if ($listing->store?->verified_at)
                  <i class="ti ti-shield-check-filled text-success fs-5" title="Toko Terverifikasi"></i>
                @endif
              </div>
              <div class="text-muted fs-3">
                <i class="ti ti-map-pin me-1"></i>
                {{ $listing->store?->district ?? $listing->store?->regency ?? config('seekitar.regency') }}
              </div>
            </div>
          </div>

          {{-- CTA utama --}}
          <div class="d-grid gap-2 mb-5">
            <div class="app-cta-card p-4">
              <div class="position-relative z-1">
                <h5 class="text-white fw-bold mb-2">
                  <i class="ti ti-brand-android me-2"></i>Transaksi di Aplikasi
                </h5>
                <p class="mb-3 text-white opacity-90 fs-4">
                  Untuk membeli atau menghubungi penjual, gunakan aplikasi Seekitar di ponselmu.
                  Semua transaksi aman dan tercatat.
                </p>
                <a href="{{ route('web.help') }}#cara-daftar" class="btn btn-light px-4 fw-semibold">
                  <i class="ti ti-download me-1"></i> Cara Unduh & Daftar
                </a>
              </div>
            </div>
          </div>

          {{-- Info ringkas --}}
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
              <h6 class="fw-semibold mb-3">Informasi</h6>
              <div class="detail-info-row">
                <span class="detail-info-icon"><i class="ti ti-tag"></i></span>
                <div>
                  <div class="fw-medium">{{ $listing->listing_type->label() }}</div>
                  <div class="text-muted fs-3">Tipe listing</div>
                </div>
              </div>
              @if ($listing->stock_qty !== null)
                <div class="detail-info-row">
                  <span class="detail-info-icon"><i class="ti ti-packages"></i></span>
                  <div>
                    <div class="fw-medium">{{ number_format($listing->stock_qty) }}</div>
                    <div class="text-muted fs-3">Stok tersedia</div>
                  </div>
                </div>
              @endif
              @if ($listing->slot !== null)
                <div class="detail-info-row">
                  <span class="detail-info-icon"><i class="ti ti-users"></i></span>
                  <div>
                    <div class="fw-medium">{{ number_format($listing->slot) }}</div>
                    <div class="text-muted fs-3">Slot tersedia</div>
                  </div>
                </div>
              @endif
              <div class="detail-info-row">
                <span class="detail-info-icon"><i class="ti ti-calendar"></i></span>
                <div>
                  <div class="fw-medium">{{ $listing->created_at?->format('d M Y') }}</div>
                  <div class="text-muted fs-3">Dipasang sejak</div>
                </div>
              </div>
              @if ($listing->store?->accepts_cod)
                <div class="detail-info-row">
                  <span class="detail-info-icon"><i class="ti ti-cash"></i></span>
                  <div>
                    <div class="fw-medium">COD Tersedia</div>
                    <div class="text-muted fs-3">Bayar di tempat saat bertemu</div>
                  </div>
                </div>
              @endif
              @if ($listing->store?->service_radius_km)
                <div class="detail-info-row">
                  <span class="detail-info-icon"><i class="ti ti-radar"></i></span>
                  <div>
                    <div class="fw-medium">{{ $listing->store->service_radius_km }} km</div>
                    <div class="text-muted fs-3">Radius layanan toko</div>
                  </div>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>

      {{-- ============================ DESKRIPSI ============================ --}}
      <div class="row mt-5 mt-lg-7">
        <div class="col-lg-8" data-aos="fade-up" data-aos-duration="900">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5">
              <h5 class="fw-semibold mb-3">Deskripsi</h5>
              <div class="doc-content">
                @if ($listing->description)
                  {!! nl2br(e($listing->description)) !!}
                @else
                  <p class="text-muted">Penjual belum menambahkan deskripsi untuk listing ini.</p>
                @endif
              </div>
            </div>
          </div>
        </div>

        {{-- Sidebar: Info toko --}}
        <div class="col-lg-4 mt-4 mt-lg-0" data-aos="fade-up" data-aos-delay="150" data-aos-duration="900">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
              <h6 class="fw-semibold mb-3">Tentang Toko</h6>
              <div class="d-flex align-items-center gap-3 mb-3">
                <div class="detail-store-avatar">
                  {{ Str::substr($listing->store?->name ?? 'T', 0, 2) }}
                </div>
                <div>
                  <div class="fw-semibold d-flex align-items-center gap-1">
                    {{ $listing->store?->name }}
                    @if ($listing->store?->verified_at)
                      <i class="ti ti-shield-check-filled text-success" title="Terverifikasi"></i>
                    @endif
                  </div>
                  <div class="text-muted fs-3">{{ $listing->store?->regency ?? config('seekitar.regency') }}</div>
                </div>
              </div>

              {{-- Statistik toko --}}
              <div class="row g-2 mb-3">
                <div class="col-4">
                  <div class="text-center p-2 rounded-3 bg-primary-subtle">
                    <div class="fw-bold text-primary">{{ number_format($statToko['listing']) }}</div>
                    <div class="fs-3 text-muted">Listing</div>
                  </div>
                </div>
                <div class="col-4">
                  <div class="text-center p-2 rounded-3 bg-warning-subtle">
                    <div class="fw-bold text-warning">
                      <i class="ti ti-star-filled me-1"></i>{{ number_format($statToko['rating'], 1) }}
                    </div>
                    <div class="fs-3 text-muted">Rating</div>
                  </div>
                </div>
                <div class="col-4">
                  <div class="text-center p-2 rounded-3 bg-info-subtle">
                    <div class="fw-bold text-info">{{ number_format($statToko['ulasan']) }}</div>
                    <div class="fs-3 text-muted">Ulasan</div>
                  </div>
                </div>
              </div>

              @if ($listing->store?->address)
                <div class="d-flex align-items-start gap-2 mb-2">
                  <i class="ti ti-map-pin text-primary mt-1 flex-shrink-0"></i>
                  <span class="fs-3 text-muted">{{ $listing->store->address }}</span>
                </div>
              @endif

              <div class="alert alert-success d-flex align-items-start gap-2 mb-0 mt-3 fs-3">
                <i class="ti ti-info-circle mt-1 flex-shrink-0"></i>
                <div>
                  Untuk bertransaksi dengan toko ini, gunakan <strong>aplikasi Seekitar</strong> di ponselmu.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ============================ LISTING LAIN ============================ --}}
      @if ($lainnya->isNotEmpty())
        <div class="mt-5 mt-lg-7" data-aos="fade-up" data-aos-duration="900">
          <h5 class="fw-bold mb-4">Lainnya dari {{ $listing->store?->name }}</h5>
          <div class="row g-4">
            @foreach ($lainnya as $item)
              <div class="col-sm-6 col-lg-3">
                <div class="card card-lift border-0 shadow-sm h-100 overflow-hidden">
                  <div class="position-relative" style="height: 160px; overflow: hidden; border-radius: 1rem 1rem 0 0; background: var(--bs-primary-bg-subtle);">
                    <img src="{{ $item->images[0] }}" alt="{{ $item->title }}" loading="lazy" class="w-100 h-100"
                      style="object-fit: cover;"
                      onerror="this.onerror=null; this.src='https://placehold.co/600x400/E7F6EC/168A4A?text=Seekitar'">
                    <span class="badge position-absolute text-bg-{{ $item->listing_type->color() }}" style="top: .5rem; left: .5rem; font-size: .65rem;">
                      {{ $item->listing_type->label() }}
                    </span>
                  </div>
                  <div class="card-body p-3 d-flex flex-column">
                    <h6 class="fw-semibold mb-1 fs-3">{{ Str::limit($item->title, 36) }}</h6>
                    <div class="mt-auto listing-price fs-4">
                      @if ($item->price !== null)
                        {{ \App\Support\Angka::rupiah($item->price) }}
                      @else
                        Hubungi
                      @endif
                    </div>
                    <a href="{{ route('web.listing.show', $item) }}" class="stretched-link" aria-label="Lihat detail {{ $item->title }}"></a>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endif

      {{-- ============================ CTA BAWAH ============================ --}}
      <div class="row justify-content-center mt-5 mt-lg-7">
        <div class="col-lg-8">
          <div class="app-cta-card p-4 p-lg-5 text-center" data-aos="fade-up" data-aos-duration="900">
            <div class="position-relative z-1">
              <h4 class="text-white fw-bold mb-2">Siap bertransaksi?</h4>
              <p class="mb-4 text-white opacity-90">
                Unduh aplikasi Seekitar untuk membeli, menjual, atau menyewa dari warga sekitar.
                Gratis, tanpa komisi.
              </p>
              <div class="d-sm-flex justify-content-center gap-3">
                <a href="{{ route('web.help') }}#cara-daftar" class="btn btn-light px-4 fw-semibold">
                  <i class="ti ti-download me-1"></i> Cara Unduh Aplikasi
                </a>
                <a href="{{ route('web.listings') }}" class="btn btn-outline-light px-4">
                  Jelajahi Listing Lain
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

@endsection
