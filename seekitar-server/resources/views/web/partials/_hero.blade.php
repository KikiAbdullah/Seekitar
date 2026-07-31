<section @class(['lp-hero', 'lp-hero-mini', 'lp-hero-berfoto' => isset($gambar)])>
    @isset($gambar)
        <img src="{{ asset($gambar) }}" alt="{{ $gambarAlt ?? '' }}"
             class="lp-hero-bg" fetchpriority="high" decoding="async">
        <span class="lp-hero-scrim" aria-hidden="true"></span>
    @else
        <span class="lp-blob lp-blob-hijau" aria-hidden="true"></span>
        <span class="lp-blob lp-blob-kuning" aria-hidden="true"></span>
        <span class="lp-dots lp-dots-atas" aria-hidden="true"></span>
    @endisset

    <div class="container position-relative">
        <nav aria-label="Remah roti" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('web.home') }}" class="text-decoration-none">Beranda</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">{{ $judul }}</li>
            </ol>
        </nav>

        <span class="lp-pill mb-3">{{ $kicker }}</span>
        <h1 class="h2 fw-bold mb-2">{{ $judul }}</h1>
        @isset($subjudul)
            <p class="lead mb-0" style="color: var(--teks-secondary); max-width: 42rem;">{{ $subjudul }}</p>
        @endisset
    </div>
</section>