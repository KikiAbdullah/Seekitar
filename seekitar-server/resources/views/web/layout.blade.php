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

    @unless (app()->isProduction())
        {{-- Lingkungan non-produksi tidak boleh bersaing dengan domain asli
             di hasil pencarian. --}}
        <meta name="robots" content="noindex, nofollow">
    @endunless

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        /* Palet & tipografi resmi (BRANDING-GUIDELINE.md §3.5, §4) */
        :root {
            --hijau-lokal: #168A4A;
            --hijau-muda:  #D1FAE5;
            --kuning:      #F5B83D;
            --teks:        #1F2933;
        }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: var(--teks);
        }
        /* Tidak ada teks di bawah 11px — target pengguna 40+ tahun yang
           umumnya sudah presbiopia (BRANDING §4). */
        .small, footer { font-size: 14px; }

        .navbar-seekitar { background: #fff; border-bottom: 1px solid #E5E7EB; }
        .brand-dot {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--hijau-lokal); color: #fff;
            display: grid; place-items: center; font-weight: 700;
        }
        .btn-seekitar { background: var(--hijau-lokal); color: #fff; }
        .btn-seekitar:hover { background: #11703C; color: #fff; }
        .hero { background: var(--hijau-muda); }
        .fitur-ikon {
            width: 56px; height: 56px; border-radius: 16px;
            background: var(--hijau-muda); color: var(--hijau-lokal);
            display: grid; place-items: center; font-size: 24px;
        }
        footer { background: var(--teks); color: #D1D5DB; }
        footer a { color: #fff; text-decoration: none; }
        footer a:hover { text-decoration: underline; }
        /* Lewati navigasi — pengguna pembaca layar tidak perlu menelusuri
           seluruh menu di tiap halaman. */
        .skip-link {
            position: absolute; left: -999px;
            background: var(--hijau-lokal); color: #fff; padding: .5rem 1rem;
        }
        .skip-link:focus { left: 1rem; top: 1rem; z-index: 1080; }
    </style>
    @stack('head')
</head>
<body>

<a href="#konten" class="skip-link">Lewati ke konten utama</a>

<nav class="navbar navbar-expand-lg navbar-seekitar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('web.home') }}">
            <span class="brand-dot">S</span> Seekitar
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
                    <span class="brand-dot">S</span>
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
