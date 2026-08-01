@extends('web.layout')

@section('title', 'Blog — Seekitar')
@section('meta_description', 'Artikel, tips, dan panduan seputar pasar lokal, transaksi aman, dan UMKM di ' . config('seekitar.regency') . '.')

@push('styles')
  <style>
    .post-thumb {
      height: 220px;
      overflow: hidden;
      border-radius: 1.25rem 1.25rem 0 0;
      background: var(--bs-primary-bg-subtle);
    }
    .post-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform .5s ease;
    }
    .post-card:hover .post-thumb img {
      transform: scale(1.05);
    }
    .post-meta {
      font-size: .85rem;
      color: var(--bs-secondary-color);
    }
    .blog-empty-icon {
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
  </style>
@endpush

@section('content')

  @include('web.partials._hero', [
    'kicker'   => 'Blog',
    'judul'    => 'Cerita dari pasar sekitar',
    'subjudul' => 'Tips, panduan, dan cerita seputar belanja dan jualan di sekitar ' . config('seekitar.regency') . '.',
    'gambar'   => asset('img/web/blog-baru.jpg'),
    'gambarAlt' => 'Ilustrasi blog dan artikel Seekitar',
  ])

  <section class="pb-8 pb-lg-11">
    <div class="container">
      @if (empty($posts))
        <div class="row justify-content-center">
          <div class="col-lg-7" data-aos="fade-up" data-aos-duration="900">
            <div class="card border-0 shadow-sm">
              <div class="card-body text-center p-5">
                <div class="blog-empty-icon mb-4">
                  <i class="ti ti-news"></i>
                </div>
                <h5 class="fw-semibold mb-2">Belum ada artikel</h5>
                <p class="mb-0 text-muted fs-4">
                  Tim kami sedang menyiapkan konten bermanfaat. Kembali lagi nanti!
                </p>
              </div>
            </div>
          </div>
        </div>
      @else
        <div class="row g-4">
          @foreach ($posts as $i => $post)
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="{{ $i * 100 }}" data-aos-duration="900">
              <div class="card post-card card-lift border-0 shadow-sm h-100 overflow-hidden">
                <div class="post-thumb position-relative">
                  <img src="{{ $post['image'] }}" alt="{{ $post['imageAlt'] }}" loading="lazy"
                    onerror="this.onerror=null; this.src='https://placehold.co/800x600/E7F6EC/168A4A?text=Seekitar'">
                  <span class="badge position-absolute bg-primary text-white"
                    style="top: 1rem; left: 1rem;">{{ $post['category'] }}</span>
                </div>
                <div class="card-body p-4 d-flex flex-column">
                  <div class="post-meta mb-2 d-flex align-items-center gap-3">
                    <span><i class="ti ti-calendar me-1"></i>{{ $post['date'] }}</span>
                    <span><i class="ti ti-user me-1"></i>{{ $post['author'] }}</span>
                  </div>
                  <h5 class="fs-5 fw-semibold mb-2">
                    <a href="{{ route('web.blog.post', $post['slug']) }}"
                      class="stretched-link text-decoration-none text-reset">{{ $post['title'] }}</a>
                  </h5>
                  <p class="mb-0 text-muted fs-4 flex-fill">{{ $post['excerpt'] }}</p>
                  <div class="mt-3 pt-3 border-top">
                    <span class="fw-semibold text-primary d-flex align-items-center gap-1">
                      Baca selengkapnya <i class="ti ti-arrow-right"></i>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </section>

  {{-- ============================ CTA ============================ --}}
  <section class="pb-8 pb-lg-11">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card c2a-box border-0 shadow-sm" data-aos="fade-up" data-aos-duration="900">
            <div class="card-body text-center p-4 p-lg-8 py-8">
              <h3 class="fs-7 fw-semibold">Mulai jualan atau belanja sekarang</h3>
              <p class="mb-8 text-muted">
                Terinspirasi dari cerita di atas? Saatnya kamu jadi bagian dari pasar lokal.
              </p>
              <div class="d-sm-flex align-items-center justify-content-center gap-3">
                <a href="{{ route('web.listings') }}" class="btn btn-primary px-5 d-block mb-3 mb-sm-0 btn-hover-shadow">Jelajahi Pasar</a>
                <a href="{{ route('web.for-sellers') }}" class="btn btn-outline-primary px-5 d-block">Buka Toko Gratis</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
