<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Seekitar — Pasar Lokal')</title>
  <meta name="description" content="@yield('meta_description', 'Seekitar — pasar lokal satu kabupaten. Beli dan jual di sekitar Anda.')">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="{{ url()->current() }}">

  {{-- Open Graph / Twitter --}}
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="{{ config('seekitar.company.name') }}">
  <meta property="og:title" content="@yield('title', 'Seekitar — Pasar Lokal')">
  <meta property="og:description" content="@yield('meta_description', 'Seekitar — pasar lokal satu kabupaten. Beli dan jual di sekitar Anda.')">
  <meta property="og:url" content="{{ url()->current() }}">
  <meta property="og:image" content="@yield('og_image', asset('img/web/og.jpg'))">
  <meta name="twitter:card" content="summary_large_image">

  <link rel="shortcut icon" type="image/png" href="{{ asset('img/brand/logo-mark.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('img/brand/logo-mark.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200;300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="{{ asset('vendor/mordenize-lp/libs/aos/dist/aos.css') }}">
  <link id="themeColors" rel="stylesheet" href="{{ asset('vendor/mordenize-lp/css/style.min.css') }}">
  @include('web.partials._styles')
  @stack('head')
  @stack('styles')
</head>

<body>
  <div class="page-wrapper p-0 overflow-hidden">
    <header class="header">
      <nav class="navbar navbar-expand-lg py-0">
        <div class="container">
          <a class="navbar-brand me-0 py-0" href="{{ route('web.home') }}">
            <img src="{{ asset('img/brand/logo-lockup.png') }}" alt="Seekitar" width="150" height="36">
          </a>
          <button class="navbar-toggler border-0 p-0 shadow-none" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
            <i class="ti ti-menu-2 fs-9"></i>
          </button>
          <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav align-items-center mb-2 mb-lg-0 ms-auto">
              <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('web.home') ? 'active' : '' }}" aria-current="page"
                  href="{{ route('web.home') }}">Beranda</a>
              </li>
              <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('web.listings') ? 'active' : '' }}"
                  href="{{ route('web.listings') }}">Cari</a>
              </li>
              <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('web.about') ? 'active' : '' }}"
                  href="{{ route('web.about') }}">Tentang</a>
              </li>
              <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('web.for-sellers') ? 'active' : '' }}"
                  href="{{ route('web.for-sellers') }}">Untuk Penjual</a>
              </li>
              <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('web.help') ? 'active' : '' }}"
                  href="{{ route('web.help') }}">Bantuan</a>
              </li>
              <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('web.blog') ? 'active' : '' }}"
                  href="{{ route('web.blog') }}">Blog</a>
              </li>
              <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('web.contact') ? 'active' : '' }}"
                  href="{{ route('web.contact') }}">Kontak</a>
              </li>
              <li class="nav-item ms-2">
                <a class="btn btn-primary fs-3 rounded btn-hover-shadow px-3 py-2"
                  href="{{ route('admin.login') }}">Masuk</a>
              </li>
            </ul>
          </div>
        </div>
      </nav>
    </header>

    <div class="body-wrapper overflow-hidden">
      @yield('content')
    </div>

    {{-- ============================ FOOTER ============================ --}}
    <footer class="footer-part pt-8 pb-5">
      <div class="container">
        <div class="row g-5">
          <div class="col-lg-4">
            <div class="text-center text-lg-start">
              <a href="{{ route('web.home') }}">
                <img src="{{ asset('img/brand/logo-mark.png') }}" alt="Seekitar"
                  class="img-fluid pb-3" width="48" height="48">
              </a>
              <p class="mb-1 text-dark fw-semibold">
                {{ config('seekitar.company.name') }}
              </p>
              <p class="mb-2 text-muted fs-3">
                Pasar lokal {{ config('seekitar.regency') }} — beli dan jual di sekitar Anda.
              </p>
              <p class="mb-0 text-muted fs-3">
                <i class="ti ti-map-pin me-1"></i> {{ config('seekitar.company.address') }}
              </p>
            </div>
          </div>
          <div class="col-6 col-lg-2">
            <h6 class="fw-bold mb-3">Perusahaan</h6>
            <ul class="list-unstyled d-grid gap-2 mb-0 fs-3">
              <li><a class="text-muted text-hover-primary text-decoration-none" href="{{ route('web.about') }}">Tentang</a></li>
              <li><a class="text-muted text-hover-primary text-decoration-none" href="{{ route('web.careers') }}">Karier</a></li>
              <li><a class="text-muted text-hover-primary text-decoration-none" href="{{ route('web.blog') }}">Blog</a></li>
              <li><a class="text-muted text-hover-primary text-decoration-none" href="{{ route('web.status') }}">Status Layanan</a></li>
            </ul>
          </div>
          <div class="col-6 col-lg-3">
            <h6 class="fw-bold mb-3">Bisnis</h6>
            <ul class="list-unstyled d-grid gap-2 mb-0 fs-3">
              <li><a class="text-muted text-hover-primary text-decoration-none" href="{{ route('web.for-sellers') }}">Untuk Penjual</a></li>
              <li><a class="text-muted text-hover-primary text-decoration-none" href="{{ route('web.pricing') }}">Biaya & Harga</a></li>
              <li><a class="text-muted text-hover-primary text-decoration-none" href="{{ route('web.verification') }}">Kebijakan Verifikasi</a></li>
              <li><a class="text-muted text-hover-primary text-decoration-none" href="{{ route('web.security') }}">Pusat Keamanan</a></li>
              <li><a class="text-muted text-hover-primary text-decoration-none" href="{{ route('web.guidelines') }}">Pedoman Komunitas</a></li>
            </ul>
          </div>
          <div class="col-lg-3">
            <h6 class="fw-bold mb-3">Hukum & Bantuan</h6>
            <ul class="list-unstyled d-grid gap-2 mb-0 fs-3">
              <li><a class="text-muted text-hover-primary text-decoration-none" href="{{ route('web.help') }}">Pusat Bantuan</a></li>
              <li><a class="text-muted text-hover-primary text-decoration-none" href="{{ route('web.contact') }}">Kontak & Pengaduan</a></li>
              <li><a class="text-muted text-hover-primary text-decoration-none" href="{{ route('web.refund') }}">Pengembalian & Sengketa</a></li>
              <li><a class="text-muted text-hover-primary text-decoration-none" href="{{ route('web.privacy') }}">Kebijakan Privasi</a></li>
              <li><a class="text-muted text-hover-primary text-decoration-none" href="{{ route('web.terms') }}">Syarat & Ketentuan</a></li>
              <li><a class="text-muted text-hover-primary text-decoration-none" href="{{ route('web.cookie') }}">Kebijakan Cookie</a></li>
            </ul>
          </div>
        </div>
        <div class="border-top mt-8 pt-4">
          <div class="row align-items-center justify-content-between g-3">
            <div class="col-md-7">
              <p class="mb-0 text-muted fs-3">
                &copy; {{ date('Y') }} {{ config('seekitar.company.name') }}. Semua hak dilindungi.
                @if (config('seekitar.pse.registration_number'))
                  Terdaftar sebagai PSE di Kominfo — {{ config('seekitar.pse.registration_number') }}.
                @endif
              </p>
            </div>
            <div class="col-md-5">
              <ul class="list-unstyled d-flex justify-content-md-end align-items-center gap-3 mb-0">
                <li>
                  <a class="icon-soft" style="width: 38px; height: 38px;" href="mailto:{{ config('seekitar.contacts.complaint') }}"
                    aria-label="Email" title="Email">
                    <i class="ti ti-mail"></i>
                  </a>
                </li>
                <li>
                  <a class="icon-soft" style="width: 38px; height: 38px;" href="https://wa.me/{{ config('seekitar.contacts.whatsapp') }}"
                    aria-label="WhatsApp" title="WhatsApp" target="_blank" rel="noopener">
                    <i class="ti ti-brand-whatsapp"></i>
                  </a>
                </li>
                <li>
                  <a class="icon-soft" style="width: 38px; height: 38px;" href="{{ route('web.contact') }}"
                    aria-label="Kontak & Pengaduan" title="Kontak & Pengaduan">
                    <i class="ti ti-headset"></i>
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </footer>

    {{-- ============================ OFFCANVAS MOBILE ============================ --}}
    <div class="offcanvas offcanvas-start modernize-lp-offcanvas" tabindex="-1" id="offcanvasNavbar"
      aria-labelledby="offcanvasNavbarLabel">
      <div class="offcanvas-header p-4">
        <img src="{{ asset('img/brand/logo-lockup.png') }}" alt="Seekitar" class="img-fluid"
          width="150" height="36">
      </div>
      <div class="offcanvas-body p-4">
        <ul class="navbar-nav justify-content-end flex-grow-1">
          <li class="nav-item mt-3">
            <a class="nav-link fs-3 text-dark {{ request()->routeIs('web.home') ? 'active' : '' }}"
              href="{{ route('web.home') }}"><i class="ti ti-home me-2"></i> Beranda</a>
          </li>
          <li class="nav-item mt-3">
            <a class="nav-link fs-3 text-dark {{ request()->routeIs('web.listings') ? 'active' : '' }}"
              href="{{ route('web.listings') }}"><i class="ti ti-search me-2"></i> Cari</a>
          </li>
          <li class="nav-item mt-3">
            <a class="nav-link fs-3 text-dark {{ request()->routeIs('web.about') ? 'active' : '' }}"
              href="{{ route('web.about') }}"><i class="ti ti-info-circle me-2"></i> Tentang</a>
          </li>
          <li class="nav-item mt-3">
            <a class="nav-link fs-3 text-dark {{ request()->routeIs('web.for-sellers') ? 'active' : '' }}"
              href="{{ route('web.for-sellers') }}"><i class="ti ti-building-store me-2"></i> Untuk Penjual</a>
          </li>
          <li class="nav-item mt-3">
            <a class="nav-link fs-3 text-dark {{ request()->routeIs('web.help') ? 'active' : '' }}"
              href="{{ route('web.help') }}"><i class="ti ti-help-circle me-2"></i> Bantuan</a>
          </li>
          <li class="nav-item mt-3">
            <a class="nav-link fs-3 text-dark {{ request()->routeIs('web.blog') ? 'active' : '' }}"
              href="{{ route('web.blog') }}"><i class="ti ti-news me-2"></i> Blog</a>
          </li>
          <li class="nav-item mt-3">
            <a class="nav-link fs-3 text-dark {{ request()->routeIs('web.contact') ? 'active' : '' }}"
              href="{{ route('web.contact') }}"><i class="ti ti-headset me-2"></i> Kontak</a>
          </li>
        </ul>
        <form class="d-flex mt-3" role="search">
          <a href="{{ route('admin.login') }}" class="btn btn-primary w-100 py-2">Masuk</a>
        </form>
      </div>
    </div>
  </div>

  <script src="{{ asset('vendor/mordenize-lp/libs/aos/dist/aos.js') }}" defer></script>
  <script src="{{ asset('vendor/mordenize-lp/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}" defer></script>
  <script src="{{ asset('vendor/mordenize-lp/js/custom.js') }}" defer></script>
  @include('web.partials._cookie-consent')
  @stack('scripts')
</body>

</html>
