@extends('web.layout')

@section('title', 'Blog — Seekitar')
@section('meta_description', 'Artikel, tips, dan panduan seputar pasar lokal, transaksi aman, dan UMKM.')

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
  </style>
@endpush

@section('content')

  @include('web.partials._hero', [
    'kicker'   => 'Blog',
    'judul'    => 'Cerita dari pasar sekitar',
    'subjudul' => 'Tips, panduan, dan cerita seputar belanja dan jualan di sekitar.',
  ])

  <section class="pb-8 pb-lg-11">
    <div class="container">
      @if (empty($posts))
        <div class="row justify-content-center">
          <div class="col-lg-7" data-aos="fade-up" data-aos-duration="900">
            <div class="card border-0 shadow-sm">
              <div class="card-body text-center p-5">
                <span class="icon-soft d-inline-flex align-items-center justify-content-center mb-3"
                  style="width: 64px; height: 64px; font-size: 1.75rem;">
                  <i class="ti ti-news"></i>
                </span>
                <h5 class="fw-semibold mb-1">Belum ada artikel</h5>
                <p class="mb-0 text-muted fs-4">Kembali lagi nanti!</p>
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
                  <span class="badge position-absolute bg-primary-subtle text-primary"
                    style="top: 1rem; left: 1rem;">{{ $post['category'] }}</span>
                </div>
                <div class="card-body p-4 d-flex flex-column">
                  <div class="fs-3 text-muted mb-2">
                    <i class="ti ti-calendar me-1"></i>{{ $post['date'] }}
                  </div>
                  <h5 class="fs-5 fw-semibold mb-2">
                    <a href="{{ route('web.blog.post', $post['slug']) }}"
                      class="stretched-link text-decoration-none text-reset">{{ $post['title'] }}</a>
                  </h5>
                  <p class="mb-0 text-muted fs-4">{{ $post['excerpt'] }}</p>
                  <div class="mt-auto pt-3 d-flex align-items-center justify-content-between">
                    <span class="fs-3 text-muted">
                      <i class="ti ti-user me-1"></i>{{ $post['author'] }}
                    </span>
                    <span class="fw-semibold text-primary">
                      Baca <i class="ti ti-arrow-right"></i>
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

@endsection
