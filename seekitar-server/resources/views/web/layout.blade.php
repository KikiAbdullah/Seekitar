<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Seekitar') — Yang kamu butuhkan, ada di sekitar</title>
    <meta name="description" content="@yield('description', 'Seekitar menghubungkan warga dengan penjual, penyedia jasa, dan penyewaan di sekitar ' . config('seekitar.regency') . '.')">

    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="theme-color" content="#0F6E3F">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Seekitar">
    <meta property="og:title" content="@yield('title', 'Seekitar')">
    <meta property="og:description" content="@yield('description', 'Yang kamu butuhkan, ada di sekitar.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="id_ID">
    <meta name="twitter:card" content="summary_large_image">
    <meta property="og:image" content="{{ asset('img/web/og.jpg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Seekitar — Yang kamu butuhkan, ada di sekitar">
    <meta name="twitter:image" content="{{ asset('img/web/og.jpg') }}">

    @unless (app()->isProduction())
        <meta name="robots" content="noindex, nofollow">
    @endunless

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
          onerror="this.onerror=null;this.href='{{ asset('vendor/bootstrap/bootstrap.min.css') }}'">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/modernize/css/icons/tabler-icons/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/web.css') }}">
    @stack('head')
</head>
<body>

<a href="#konten" class="skip-link">Lewati ke konten utama</a>

<nav class="navbar navbar-expand-lg navbar-seekitar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('web.home') }}">
            <img src="{{ asset('img/brand/logo-lockup.png') }}" alt="Seekitar" width="618" height="144" class="brand-lockup">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navmenu" aria-controls="navmenu"
                aria-expanded="false" aria-label="Buka menu navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navmenu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('web.about') ? 'active fw-semibold' : '' }}"
                       href="{{ route('web.about') }}">Tentang</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('web.help') ? 'active fw-semibold' : '' }}"
                       href="{{ route('web.help') }}">Bantuan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('web.for-sellers') ? 'active fw-semibold' : '' }}"
                       href="{{ route('web.for-sellers') }}">Penjual</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('web.blog') ? 'active fw-semibold' : '' }}"
                       href="{{ route('web.blog') }}">Blog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('web.contact') ? 'active fw-semibold' : '' }}"
                       href="{{ route('web.contact') }}">Kontak</a>
                </li>
                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                    <a class="btn btn-seekitar btn-sm px-3" href="{{ route('web.home') }}#unduh">Mulai</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main id="konten">
    @yield('content')
</main>

<button id="backToTop" aria-label="Kembali ke atas" title="Kembali ke atas">
    <i class="ti ti-chevron-up"></i>
</button>

<footer class="pt-5 pb-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img src="{{ asset('img/brand/logo-lockup-putih.png') }}" alt="Seekitar" width="618" height="144" class="brand-lockup">
                </div>
                <p class="mb-2">Yang kamu butuhkan, ada di sekitar.</p>
                <p class="mb-0" style="font-size: 13px; opacity: .7;">
                    {{ config('seekitar.company.name') }}<br>
                    {{ config('seekitar.company.address') }}
                </p>
            </div>

            <div class="col-4 col-lg-2">
                <h2 class="h6 text-white">Jelajahi</h2>
                <ul class="list-unstyled" style="font-size: 14px;">
                    <li class="mb-1"><a href="{{ route('web.about') }}">Tentang</a></li>
                    <li class="mb-1"><a href="{{ route('web.for-sellers') }}">Untuk Penjual</a></li>
                    <li class="mb-1"><a href="{{ route('web.help') }}">Pusat Bantuan</a></li>
                    <li class="mb-1"><a href="{{ route('web.blog') }}">Blog</a></li>
                    <li class="mb-1"><a href="{{ route('web.contact') }}">Kontak</a></li>
                    <li class="mb-1"><a href="{{ route('web.careers') }}">Karier</a></li>
                </ul>
            </div>

            <div class="col-4 col-lg-2">
                <h2 class="h6 text-white">Informasi</h2>
                <ul class="list-unstyled" style="font-size: 14px;">
                    <li class="mb-1"><a href="{{ route('web.pricing') }}">Biaya &amp; Harga</a></li>
                    <li class="mb-1"><a href="{{ route('web.security') }}">Pusat Keamanan</a></li>
                    <li class="mb-1"><a href="{{ route('web.status') }}">Status Layanan</a></li>
                    <li class="mb-1"><a href="{{ route('web.verification') }}">Verifikasi Toko</a></li>
                </ul>
            </div>

            <div class="col-4 col-lg-2">
                <h2 class="h6 text-white">Legal</h2>
                <ul class="list-unstyled" style="font-size: 14px;">
                    <li class="mb-1"><a href="{{ route('web.privacy') }}">Kebijakan Privasi</a></li>
                    <li class="mb-1"><a href="{{ route('web.terms') }}">Syarat Ketentuan</a></li>
                    <li class="mb-1"><a href="{{ route('web.cookie') }}">Kebijakan Cookie</a></li>
                    <li class="mb-1"><a href="{{ route('web.guidelines') }}">Pedoman Komunitas</a></li>
                    <li class="mb-1"><a href="{{ route('web.refund') }}">Pengembalian Dana</a></li>
                </ul>
            </div>

            <div class="col-lg-4">
                <h2 class="h6 text-white">Kanal Pengaduan</h2>
                <ul class="list-unstyled mb-0" style="font-size: 13px;">
                    <li class="mb-1">Pengaduan:
                        <a href="mailto:{{ config('seekitar.contacts.complaint') }}">{{ config('seekitar.contacts.complaint') }}</a>
                        <span style="opacity: .6;">(2×24 jam)</span></li>
                    <li class="mb-1">Konten ilegal:
                        <a href="mailto:{{ config('seekitar.contacts.abuse') }}">{{ config('seekitar.contacts.abuse') }}</a>
                        <span style="opacity: .6;">(1×24 jam)</span></li>
                    <li class="mb-1">Data pribadi:
                        <a href="mailto:{{ config('seekitar.contacts.privacy') }}">{{ config('seekitar.contacts.privacy') }}</a>
                        <span style="opacity: .6;">(3×24 jam)</span></li>
                    <li class="mb-1">Celah keamanan:
                        <a href="mailto:{{ config('seekitar.contacts.security') }}">{{ config('seekitar.contacts.security') }}</a>
                        <span style="opacity: .6;">(1×24 jam)</span></li>
                </ul>
            </div>
        </div>

        <hr class="my-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <p class="mb-0" style="font-size: 13px; opacity: .7;">
                &copy; {{ date('Y') }} {{ config('seekitar.company.name') }}. Beroperasi di {{ config('seekitar.regency') }}.
                @if (config('seekitar.pse.registration_number'))
                    <br>PSE Kominfo: {{ config('seekitar.pse.registration_number') }}
                @endif
            </p>
            <p class="mb-0" style="font-size: 12px; opacity: .5;">
                Dibuat di {{ config('seekitar.regency') }} untuk Indonesia
            </p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        onerror="this.remove();document.body.appendChild(function(){var s=document.createElement('script');s.src='{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}';return s}())"></script>

<script>
    (function () {
        // Scroll reveal
        var reveal = document.querySelectorAll('.sr-reveal');
        if (reveal.length && 'IntersectionObserver' in window) {
            var obs = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) { e.target.classList.add('sr-tampil'); obs.unobserve(e.target); }
                });
            }, { threshold: .12, rootMargin: '0px 0px -40px 0px' });
            reveal.forEach(function (el) { obs.observe(el); });
        } else {
            reveal.forEach(function (el) { el.classList.add('sr-tampil'); });
        }

        // Back to top
        var btn = document.getElementById('backToTop');
        if (btn) {
            window.addEventListener('scroll', function () {
                btn.classList.toggle('show', window.scrollY > 500);
            }, { passive: true });
            btn.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    })();
</script>

@include('web.partials._cookie-consent')
@stack('scripts')
</body>
</html>