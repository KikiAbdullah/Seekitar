<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Seekitar') — Yang kamu butuhkan, ada di sekitar</title>
    <meta name="description" content="@yield('description', 'Seekitar menghubungkan warga dengan penjual, penyedia jasa, dan penyewaan di sekitar ' . config('seekitar.regency') . '.')">

    {{-- Kanonik mencegah konten sama terindeks di beberapa URL (mis. dengan
         dan tanpa parameter pelacakan). --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph: tautan yang dibagikan di WhatsApp — kanal utama di
         Indonesia — menampilkan pratinjau, bukan URL telanjang. --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Seekitar">
    <meta property="og:title" content="@yield('title', 'Seekitar')">
    <meta property="og:description" content="@yield('description', 'Yang kamu butuhkan, ada di sekitar.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="id_ID">
    <meta name="twitter:card" content="summary_large_image">
    {{-- Pratinjau tautan (WhatsApp dsb.): 1200×630 persis spesifikasi agar
         tidak terpotong aneh. JPG, bukan WebP — sebagian perayap pratinjau
         belum mendukung WebP. --}}
    <meta property="og:image" content="{{ asset('img/web/og.jpg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Seekitar — Yang kamu butuhkan, ada di sekitar">
    <meta name="twitter:image" content="{{ asset('img/web/og.jpg') }}">

    @unless (app()->isProduction())
        {{-- Lingkungan non-produksi tidak boleh bersaing dengan domain asli
             di hasil pencarian. --}}
        <meta name="robots" content="noindex, nofollow">
    @endunless

    {{-- Ikon peramban: favicon.ico multi-ukuran (16/32/48) otomatis dipilih
         sesuai kerapatan layar; apple-touch-icon versi latar putih polos —
         iOS mengabaikan kanal alfa sehingga sudut transparan menjadi hitam. --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    {{-- Ikon Tabler — berkas yang sama dengan panel admin, sudah lokal. --}}
    <link rel="stylesheet" href="{{ asset('vendor/modernize/css/icons/tabler-icons/tabler-icons.min.css') }}">

    {{-- Seluruh gaya situs publik. Berkas terpisah (bukan <style> inline)
         supaya ter-cache peramban dan tidak dikirim ulang tiap halaman. --}}
    <link rel="stylesheet" href="{{ asset('css/web.css') }}">
    @stack('head')
</head>
<body>

<a href="#konten" class="skip-link">Lewati ke konten utama</a>

<nav class="navbar navbar-expand-lg navbar-seekitar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('web.home') }}">
            <img src="{{ asset('img/brand/logo-mark.png') }}" alt="Logo Seekitar" class="brand-logo" width="36" height="36"> Seekitar
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navmenu" aria-controls="navmenu"
                aria-expanded="false" aria-label="Buka menu navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navmenu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('web.about') ? 'active fw-semibold' : '' }}"
                       href="{{ route('web.about') }}">Tentang</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('web.help') ? 'active fw-semibold' : '' }}"
                       href="{{ route('web.help') }}">Bantuan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('web.contact') ? 'active fw-semibold' : '' }}"
                       href="{{ route('web.contact') }}">Kontak</a>
                </li>
                {{-- CTA selalu terjangkau dari halaman mana pun (pola template:
                     tombol aksi menetap di kanan bilah navigasi). --}}
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

<footer class="mt-5 pt-5 pb-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img src="{{ asset('img/brand/logo-mark.png') }}" alt="" class="brand-logo" width="36" height="36">
                    <span class="h6 mb-0 text-white fw-bold">Seekitar</span>
                </div>
                <p class="mb-2">Yang kamu butuhkan, ada di sekitar.</p>
                <p class="mb-0">
                    {{ config('seekitar.company.name') }}<br>
                    {{ config('seekitar.company.address') }}
                </p>
            </div>

            <div class="col-6 col-lg-2">
                <h2 class="h6 text-white">Jelajahi</h2>
                <ul class="list-unstyled">
                    <li><a href="{{ route('web.about') }}">Tentang</a></li>
                    <li><a href="{{ route('web.help') }}">Pusat Bantuan</a></li>
                    <li><a href="{{ route('web.contact') }}">Kontak</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-2">
                <h2 class="h6 text-white">Legal</h2>
                <ul class="list-unstyled">
                    <li><a href="{{ route('web.privacy') }}">Kebijakan Privasi</a></li>
                    <li><a href="{{ route('web.terms') }}">Syarat &amp; Ketentuan</a></li>
                </ul>
            </div>

            {{-- Kanal pengaduan WAJIB tercantum di footer situs — kewajiban
                 hukum PSE, bukan praktik baik (BRANDING §8.3). --}}
            <div class="col-lg-4">
                <h2 class="h6 text-white">Kanal Pengaduan</h2>
                <ul class="list-unstyled mb-0">
                    <li>Pengaduan:
                        <a href="mailto:{{ config('seekitar.contacts.complaint') }}">{{ config('seekitar.contacts.complaint') }}</a>
                        <span class="text-secondary">(2×24 jam)</span></li>
                    <li>Konten ilegal:
                        <a href="mailto:{{ config('seekitar.contacts.abuse') }}">{{ config('seekitar.contacts.abuse') }}</a>
                        <span class="text-secondary">(1×24 jam)</span></li>
                    <li>Data pribadi:
                        <a href="mailto:{{ config('seekitar.contacts.privacy') }}">{{ config('seekitar.contacts.privacy') }}</a>
                        <span class="text-secondary">(3×24 jam)</span></li>
                </ul>
            </div>
        </div>

        <hr class="border-secondary my-4">
        <p class="mb-0 text-secondary">
            &copy; {{ date('Y') }} Seekitar. Beroperasi di {{ config('seekitar.regency') }}.
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
