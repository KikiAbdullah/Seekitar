<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#168A4A">
    <title>@yield('title', 'Panel Admin') — Seekitar Admin</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    {{-- Handshake TLS ke CDN/Tiles dimulai lebih awal; header & ubin peta
         merupakan satu-satunya sumber daya eksternal panel ini. --}}
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdn.datatables.net" crossorigin>
    <link rel="preconnect" href="https://tile.openstreetmap.org" crossorigin>

    {{-- Font dimuat SEBELUM style.min.css agar siap saat tema membaca
         --bs-font-sans-serif yang dialihkan ke font ini di admin.css. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link id="themeColors" rel="stylesheet" href="{{ asset('vendor/mordenize/css/style-green.min.css') }}">

    <link href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" rel="stylesheet"
          media="print" onload="this.media='all';this.onload=null"
          onerror="this.onerror=null;this.href='{{ asset('vendor/datatables/dataTables.bootstrap5.min.css') }}'">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet"
          media="print" onload="this.media='all';this.onload=null">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet"
          media="print" onload="this.media='all';this.onload=null">

    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <noscript>
        <link href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    </noscript>
    @stack('styles')
</head>
<body>

<div class="preloader">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Memuat…</span>
    </div>
</div>

<a href="#konten-utama" class="visually-hidden-focusable admin-skip">Lewati ke konten</a>

<div class="page-wrapper" id="main-wrapper" data-layout="vertical"
     data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">

    @include('admin.partials.sidebar')

    <div class="body-wrapper">

        @include('admin.partials.header')

        <div class="container-fluid">

            <main id="konten-utama">

                @yield('content')
            </main>

            <footer class="admin-footer text-center py-4 mt-4">
                <p class="mb-0 fs-2">
                    &copy; {{ now()->year }} {{ config('seekitar.company.name') }} ·
                    <span class="text-muted">Panel Admin Seekitar</span>
                </p>
            </footer>
        </div>
    </div>

    <div class="dark-transparent sidebartoggler" aria-hidden="true"></div>
</div>

{{-- Flash diubah jadi DATA, bukan markup: SweetAlert2 (vendor) dan
     seekitar-flash.js yang merender toast/dialognya. --}}
<script>
    window.seekitarFlash = {
        sukses:   @json(session('success')),
        gagal:    @json(session('error')),
        validasi: @json($errors->all()),
    };
</script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('js/seekitar-flash.js') }}"></script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"
        onerror="this.remove();document.body.appendChild(function(){var s=document.createElement('script');s.src='{{ asset('vendor/jquery.min.js') }}';return s}())"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        onerror="this.remove();document.body.appendChild(function(){var s=document.createElement('script');s.src='{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}';return s}())"></script>
<script src="{{ asset('vendor/mordenize/js/app.min.js') }}"></script>
<script src="{{ asset('vendor/mordenize/js/seekitar.init.js') }}"></script>
<script src="{{ asset('vendor/mordenize/js/sidebarmenu.js') }}"></script>
<script src="{{ asset('vendor/mordenize/js/custom.js') }}"></script>

<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"
        onerror="this.remove();document.body.appendChild(function(){var s=document.createElement('script');s.src='{{ asset('vendor/datatables/dataTables.min.js') }}';return s}())"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"
        onerror="this.remove();document.body.appendChild(function(){var s=document.createElement('script');s.src='{{ asset('vendor/datatables/dataTables.bootstrap5.min.js') }}';return s}())"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/i18n/id.js"></script>

<script>
    // Token CSRF dipasang sekali untuk seluruh request AJAX.
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    /*
     * Bahasa Datatables — disetel SEKALI untuk seluruh panel.
     *
     * Berkasnya di-host sendiri, bukan dari CDN: jalur
     * `cdn.datatables.net/plug-ins/<versi>/i18n/id.json` memakai penomoran
     * repo Plugins yang berbeda dari versi core, sehingga URL-nya mudah 404
     * dan seluruh tabel memunculkan "i18n file loading error".
     */
    $.extend(true, $.fn.dataTable.defaults, {
        language: { url: @js(asset('vendor/datatables/id.json')) },
    });

    /*
     * Select2 untuk SEMUA dropdown panel — disetel sekali di sini.
     *
     * Dipasang ke `.js-select2`, bukan ke seluruh `select`: pemilih
     * "Tampilkan N entri" milik Datatables dirender ulang setiap tabel
     * digambar, dan membungkusnya membuat kontrol itu hilang setelah sortir.
     */
    window.seekitarSelect2 = function (scope) {
        const $target = $(scope || document).find('.js-select2');
        if (!$target.length || typeof $.fn.select2 !== 'function') return;

        $target.each(function () {
            const $el = $(this);

            // Idempoten: memanggil select2() dua kali pada elemen yang sama
            // menumpuk kontainer dan menyisakan kotak kosong di bawahnya.
            if ($el.hasClass('select2-hidden-accessible')) return;

            $el.select2({
                theme: 'bootstrap-5',
                language: 'id',
                width: $el.data('width') || 'style',
                placeholder: $el.data('placeholder') || $el.find('option[value=""]').text() || 'Pilih…',
                allowClear: $el.find('option[value=""]').length > 0 && !$el.prop('required'),
                minimumResultsForSearch: Number($el.data('min-search') ?? 8),
            });
        });
    };

    seekitarSelect2();
</script>
@stack('scripts')
</body>
</html>
