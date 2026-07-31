@extends('web.layout')

@push('styles')
  <style>
    .listing-thumb {
      height: 200px;
      overflow: hidden;
      border-radius: 1.25rem 1.25rem 0 0;
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
    }
    .filter-chip {
      border-radius: 2rem;
      padding: .5rem 1.1rem;
      font-weight: 600;
    }
    .filter-chip.active {
      background: var(--bs-primary);
      border-color: var(--bs-primary);
      color: #fff;
    }
  </style>
@endpush

@section('title', 'Cari Barang, Jasa, dan Sewa — Seekitar')
@section('meta_description', 'Jelajahi listing barang, jasa, dan sewa dari toko terverifikasi di ' . config('seekitar.regency') . '.')

@section('content')

  {{-- ============================ HEADER KATALOG ============================ --}}
  <section class="hero-wrap position-relative overflow-hidden">
    <div class="container position-relative z-2">
      <div class="row justify-content-center text-center pt-13 pb-10 pt-lg-13 pb-lg-11">
        <div class="col-lg-8" data-aos="fade-up" data-aos-duration="1000">
          <span class="eyebrow">Katalog Publik</span>
          <h1 class="fw-bolder mt-4 mb-3 fs-9">Temukan kebutuhanmu di sekitar</h1>
          <p class="fs-5 text-muted mb-0">
            Semua listing di bawah dari toko terverifikasi di {{ config('seekitar.regency') }}.
          </p>
        </div>
      </div>

      {{-- Form pencarian --}}
      <form action="{{ route('web.listings') }}" method="GET" class="pb-11 pb-lg-12"
        data-aos="fade-up" data-aos-delay="150" data-aos-duration="1000">
        <div class="row g-3 justify-content-center align-items-end">
          <div class="col-lg-5">
            <div class="position-relative">
              <i class="ti ti-search position-absolute text-muted"
                style="top: 50%; left: 1.1rem; transform: translateY(-50%);"></i>
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
              @foreach (['product' => 'Barang', 'service' => 'Jasa', 'rental' => 'Sewa'] as $value => $label)
                <option value="{{ $value }}" {{ ($filters['type'] ?? null) === $value ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-lg-2">
            <button type="submit" class="btn btn-primary btn-lg w-100 btn-hover-shadow">Cari</button>
          </div>
        </div>
      </form>
    </div>
  </section>

  {{-- ============================ GRID LISTING ============================ --}}
  <section class="bg-light py-8 py-lg-11" id="hasil">
    <div class="container">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <p class="mb-0 text-muted fs-4">
          {{ $listings->total() }} listing ditemukan
        </p>
        <a href="{{ route('web.listings') }}" class="fs-4 text-primary text-decoration-none fw-semibold">
          Tampilkan semua <i class="ti ti-arrow-up-right"></i>
        </a>
      </div>

      <div class="row g-4">
        @forelse ($listings as $listing)
          <div class="col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="50" data-aos-duration="800">
            <div class="card listing-card card-lift border-0 shadow-sm h-100 overflow-hidden">
              <div class="listing-thumb position-relative">
                <img src="{{ $listing->images[0] }}" alt="{{ $listing->title }}" loading="lazy"
                  onerror="this.onerror=null; this.src='https://placehold.co/800x600/E7F6EC/168A4A?text=Seekitar'">
                <span
                  class="badge position-absolute text-bg-{{ $listing->listing_type->color() }}"
                  style="top: 1rem; left: 1rem;">
                  {{ $listing->listing_type->label() }}
                </span>
              </div>
              <div class="card-body p-4 d-flex flex-column">
                <h5 class="fs-5 fw-semibold mb-1">{{ $listing->title }}</h5>
                <p class="mb-2 fs-3 text-muted d-flex align-items-center gap-1">
                  <i class="ti ti-building-store"></i>
                  {{ $listing->store?->name }}
                </p>
                <p class="mb-3 fs-3 text-muted d-flex align-items-center gap-1">
                  <i class="ti ti-map-pin"></i>
                  {{ $listing->store?->regency ?? config('seekitar.regency') }}
                </p>
                <div class="mt-auto d-flex align-items-center justify-content-between">
                  <span class="listing-price fs-5">
                    @if ($listing->price !== null)
                      {{ \App\Support\Angka::rupiah($listing->price) }}
                    @else
                      Hubungi
                    @endif
                  </span>
                  <span class="fs-3 text-muted">{{ $listing->created_at?->format('d M Y') }}</span>
                </div>
              </div>
            </div>
          </div>
        @empty
          <div class="col-12">
            <div class="card border-0 shadow-sm">
              <div class="card-body text-center p-5">
                <span class="icon-soft d-inline-flex align-items-center justify-content-center mb-3">
                  <i class="ti ti-search-off"></i>
                </span>
                <h5 class="fw-semibold mb-1">Tidak ada hasil</h5>
                <p class="mb-0 text-muted">Coba kata kunci atau filter lain, atau kembali ke beranda.</p>
              </div>
            </div>
          </div>
        @endforelse
      </div>

      <div class="d-flex justify-content-center mt-5">
        {{ $listings->links() }}
      </div>
    </div>
  </section>

@endsection
