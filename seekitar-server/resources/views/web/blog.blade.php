@extends('web.layout')

@section('title', 'Blog')
@section('description', 'Artikel, tips, dan panduan seputar pasar lokal, transaksi aman, dan UMKM.')

@section('content')

    @include('web.partials._hero', [
        'kicker'    => 'Blog',
        'judul'     => 'Blog',
        'subjudul'  => 'Tips, panduan, dan cerita seputar belanja dan jualan di sekitar.',
        'gambar'    => 'img/web/blog.webp',
        'gambarAlt' => 'Ilustrasi meja kerja dengan laptop, kopi, dan buku catatan — menggambarkan blog yang informatif',
    ])

    <section class="py-5">
        <div class="container" style="max-width: 860px;">
            @if (empty($posts))
                <div class="text-center py-5">
                    <i class="ti ti-news" style="font-size: 48px; color: var(--teks-secondary); opacity: .4;" aria-hidden="true"></i>
                    <p class="mt-3" style="color: var(--teks-secondary);">Belum ada artikel. Kembali lagi nanti!</p>
                </div>
            @else
                <div class="row g-4">
                    @foreach ($posts as $i => $post)
                        <div class="col-md-6 sr-reveal sr-reveal-delay-{{ min($i + 1, 4) }}">
                            <div class="lp-kartu-layanan">
                                <img src="{{ $post['image'] }}"
                                     alt="{{ $post['imageAlt'] }}"
                                     width="900" height="600" loading="lazy" decoding="async"
                                     onerror="this.style.display='none'">
                                <div class="p-4">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge" style="background: var(--hijau-muda); color: var(--hijau-gelap); font-weight: 500;">
                                            {{ $post['category'] }}
                                        </span>
                                        <span style="color: var(--teks-secondary); font-size: 12px;">{{ $post['date'] }}</span>
                                    </div>
                                    <h3 class="h5 fw-bold mb-2">
                                        <a href="{{ route('web.blog.post', $post['slug']) }}"
                                           class="text-decoration-none" style="color: inherit;">
                                            {{ $post['title'] }}
                                        </a>
                                    </h3>
                                    <p class="mb-3" style="color: var(--teks-secondary); font-size: 14px;">{{ $post['excerpt'] }}</p>
                                    <a href="{{ route('web.blog.post', $post['slug']) }}"
                                       class="fw-semibold text-decoration-none" style="color: var(--hijau-lokal); font-size: 14px;">
                                        Baca selengkapnya <i class="ti ti-arrow-right" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

@endsection