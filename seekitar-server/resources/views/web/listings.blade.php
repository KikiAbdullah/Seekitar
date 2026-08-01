@extends('web.layout')

@push('styles')
  <style>
    .listing-thumb {
      height: 200px;
      overflow: hidden;
      border-radius: 1.25rem 1.25rem 0 0;
      background: var(--bs-primary-bg-subtle);
      position: relative;
    }
    .listing-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform .5s ease;
    }
    .listing-card:hover .listing-thumb img {
      transform: scale(1.05);
    }
    .listing-price {
      font-weight: 800;
      color: var(--bs-primary);
      font-size: 1.1rem;
    }
    .listing-badge {
      position: absolute;
      top: 1rem;
      left: 1rem;
      z-index: 2;
    }
    .listing-type-icon {
      position: absolute;
      top: 1rem;
      right: 1rem;
      width: 36px;
      height: 36px;
      border-radius: .65rem;
      background: rgba(255,255,255,.92);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      color: var(--bs-primary);
      z-index: 2;
      backdrop-filter: blur(4px);
    }
    .search-box {
      position: relative;
    }
    .search-box .search-icon {
      position: absolute;
      top: 50%;
      left: 1.25rem;
      transform: translateY(-50%);
      color: var(--bs-secondary-color);
      font-size: 1.25rem;
      z-index: 3;
    }
    .search-box input {
      padding-left: 3rem;
      border-radius: 1rem;
    }
    .filter-bar {
      background: #fff;
      border-radius: 1rem;
      padding: .75rem 1.25rem;
      box-shadow: var(--bs-box-shadow-sm);
    }
    .result-count {
      font-weight: 600;
    }
    .empty-state-icon {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: var(--bs-primary-bg-subtle);
      color: var(--bs-primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      margin: 0 auto;
    }
    .store-badge-verified {
      display: inline-flex;
      align-items: center;
      gap: .25rem;
      font-size: .75rem;
      color: var(--bs-success);
      font-weight: 600;
    }
  </style>
@endpush

@section('title', 'Cari Barang, Jasa, dan Sewa — Seekitar')
@section('meta_description', 'Jelajahi listing barang, jasa, dan sewa dari toko terverifikasi di ' . config('seekitar.regency') . '. Gratis tanpa biaya.')

@section('content')

  {{-- ============================ HEADER KATALOG ============================ --}}
  <section class="hero-wrap position-relative overflow-hidden">
    <div class="container position-relative z-2">
      <div class="row justify-content-center text-center pt-13 pb-10 pt-lg-13 pb-lg-11">
        <div class="col-lg-8" data-aos="fade-up" data-aos-duration="1000">
          <span class="eyebrow">Katalog Publik</span>
          <h1 class="fw-bolder mt-4 mb-3 fs-9">Temukan kebutuhanmu di sekitar</h1>
          <p class="fs-5 text-muted mb-0">
            Semua listing di bawah berasal dari toko terverifikasi di {{ config('seekitar.regency') }}.
            Pembayaran langsung ke penjual — tanpa biaya tambahan.
          </p>
        </div>
      </div>

      {{-- Form pencarian utama --}}
      <form action="{{ route('web.listings') }}" method="GET" class="pb-11 pb-lg-12"
        data-aos="fade-up" data-aos-delay="150" data-aos-duration="1000">
        <div class="row g-3 justify-content-center align-items-end">
          <div class="col-lg-5">
            <div class="search-box">
              <i class="ti ti-search search-icon"></i>
              <input type="text" name="keyword" value="{{ $filters['keyword'] ?? '' }}"
                class="form-control form-control-lg ps-5" placeholder="Cari barang, jasa, atau sewa...">
            </div>
          </div>
          <div class="col-lg-3">
            <select name="category" class="form-select form-select-lg">
              <option value="">Semua Kategori</option>
              @foreach ($categories as $kategori)
                <option value="{{ $kategori->id }}"
                  {{ ($filters['category'] ?? null) == $kategori->id ? 'selected' : '' }}>
                  {{ $kategori->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-lg-2">
            <select name="type" class="form-select form-select-lg">
              <option value="">Semua Tipe</option>
              @foreach (['product' => '🛍️ Barang', 'service' => '🔧 Jasa', 'rental' => '📅 Sewa'] as $value => $label)
                <option value="{{ $value }}" {{ ($filters['type'] ?? null) === $value ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-lg-2">
            <button type="submit" class="btn btn-primary btn-lg w-100 btn-hover-shadow">
              <i class="ti ti-search me-1"></i> Cari
            </button>
          </div>
        </div>
      </form>
    </div>
  </section>

  {{-- ============================ GRID LISTING ============================ --}}
  <section class="bg-light py-8 py-lg-11" id="hasil">
    <div class="container">
      {{-- Info hasil & quick filter --}}
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-5">
        <p class="mb-0 text-muted fs-4">
          <span class="result-count text-dark">{{ $listings->total() }}</span> listing ditemukan
          @if ($filters['keyword'] ?? false)
            untuk "<strong>{{ $filters['keyword'] }}</strong>"
          @endif
          @if ($filters['type'] ?? false)
            · Tipe: <strong>{{ ['product' => 'Barang', 'service' => 'Jasa', 'rental' => 'Sewa'][$filters['type']] }}</strong>
          @endif
        </p>
        <div class="d-flex gap-2 flex-wrap">
          <a href="{{ route('web.listings', ['type' => 'product']) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 {{ ($filters['type'] ?? null) === 'product' ? 'active' : '' }}">
            🛍️ Barang
          </a>
          <a href="{{ route('web.listings', ['type' => 'service']) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 {{ ($filters['type'] ?? null) === 'service' ? 'active' : '' }}">
            🔧 Jasa
          </a>
          <a href="{{ route('web.listings', ['type' => 'rental']) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 {{ ($filters['type'] ?? null) === 'rental' ? 'active' : '' }}">
            📅 Sewa
          </a>
          @if ($filters['keyword'] || $filters['category'] || $filters['type'])
            <a href="{{ route('web.listings') }}" class="btn btn-sm btn-outline-danger rounded-pill px-3">
              <i class="ti ti-x me-1"></i> Reset
            </a>
          @endif
        </div>
      </div>

      <div class="row g-4">
        @forelse ($listings as $listing)
          <div class="col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="50" data-aos-duration="800">
            <div class="card listing-card card-lift border-0 shadow-sm h-100 overflow-hidden">
              <div class="listing-thumb position-relative">
                <img src="{{ $listing->images[0] }}" alt="{{ $listing->title }}" loading="lazy"
                  onerror="this.onerror=null; this.src='https://placehold.co/800x600/E7F6EC/168A4A?text=Seekitar'">
                <span class="badge listing-badge text-bg-{{ $listing->listing_type->color() }}">
                  {{ $listing->listing_type->label() }}
                </span>
                <span class="listing-type-icon">
                  <i class="ti ti-{{ $listing->listing_type->value === 'product' ? 'shopping-bag' : ($listing->listing_type->value === 'service' ? 'tool' : 'calendar-month') }}"></i>
                </span>
              </div>
              <div class="card-body p-4 d-flex flex-column">
                <h5 class="fs-5 fw-semibold mb-1">{{ Str::limit($listing->title, 42) }}</h5>
                <p class="mb-2 fs-3 text-muted d-flex align-items-center gap-1">
                  <i class="ti ti-building-store"></i>
                  {{ $listing->store?->name }}
                  @if ($listing->store?->is_verified)
                    <span class="store-badge-verified ms-1">
                      <i class="ti ti-shield-check-filled"></i>
                    </span>
                  @endif
                </p>
                <p class="mb-3 fs-3 text-muted d-flex align-items-center gap-1">
                  <i class="ti ti-map-pin"></i>
                  {{ $listing->store?->district ?? $listing->store?->regency ?? config('seekitar.regency') }}
                </p>
                <div class="mt-auto d-flex align-items-center justify-content-between">
                  <span class="listing-price">
                    @if ($listing->price !== null)
                      {{ \App\Support\Angka::rupiah($listing->price) }}
                    @else
                      Hubungi Penjual
                    @endif
                  </span>
                  <span class="fs-3 text-muted d-flex align-items-center gap-1">
                    <i class="ti ti-calendar"></i>
                    {{ $listing->created_at?->format('d M Y') }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        @empty
          <div class="col-12">
            <div class="card border-0 shadow-sm">
              <div class="card-body text-center p-5">
                <div class="empty-state-icon mb-4">
                  <i class="ti ti-search-off"></i>
                </div>
                <h5 class="fw-semibold mb-2">Tidak ada listing ditemukan</h5>
                <p class="mb-4 text-muted fs-4">
                  @if ($filters['keyword'] || $filters['category'] || $filters['type'])
                    Coba kata kunci atau filter yang berbeda.
                  @else
                    Belum ada listing yang terpasang. Jadilah yang pertama!
                  @endif
                </p>
                <div class="d-flex justify-content-center gap-2">
                  @if ($filters['keyword'] || $filters['category'] || $filters['type'])
                    <a href="{{ route('web.listings') }}" class="btn btn-outline-primary">Lihat Semua Listing</a>
                  @endif
                  <a href="{{ route('web.for-sellers') }}" class="btn btn-primary btn-hover-shadow">Buka Toko Gratis</a>
                </div>
              </div>
            </div>
          </div>
        @endforelse
      </div>

      {{-- Pagination --}}
      @if ($listings->hasPages())
        <div class="d-flex justify-content-center mt-5">
          {{ $listings->links() }}
        </div>
      @endif
    </div>
  </section>

  {{-- ============================ CTA BAWAH ============================ --}}
  <section class="pb-8 pb-lg-11">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card c2a-box border-0 shadow-sm" data-aos="fade-up" data-aos-duration="900">
            <div class="card-body text-center p-4 p-lg-8 py-8">
              <h3 class="fs-7 fw-semibold">Punya barang atau jasa untuk dijual?</h3>
              <p class="mb-8 text-muted">
                Buka toko gratis di Seekitar dan jangkau pembeli di {{ config('seekitar.regency') }}.
              </p>
              <a href="{{ route('web.for-sellers') }}" class="btn btn-primary px-5 btn-hover-shadow">Buka Toko Gratis</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
