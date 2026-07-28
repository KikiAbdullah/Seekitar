<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Panel admin tidak boleh terindeks mesin pencari, apa pun isi
         robots.txt. Halaman di balik login memang tak terjangkau crawler,
         tetapi URL-nya bisa bocor lewat referer atau riwayat browser. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Panel Admin') — Seekitar Admin</title>

    {{--
        Modernize — template admin Bootstrap 5, disalin dari
        github.com/KikiAbdullah/mordenize-template-bs (package/dist).

        ⚠️ style.min.css SUDAH MEMUAT Bootstrap 5.3.0 di dalamnya. Karena itu
        Bootstrap TIDAK dimuat terpisah lagi — memuatnya dua kali menggandakan
        ±200 KB CSS dan membuat aturan yang belakangan menang secara acak
        tergantung urutan berkas.

        Warnanya sudah diubah dari biru bawaan (#5D87FF) menjadi hijau
        Seekitar (#168A4A) langsung di dalam berkas, lewat
        tools/dev/recolor-modernize.mjs — 164 penggantian. Skrip itu idempoten
        dan harus dijalankan ulang setiap kali berkas vendor diperbarui.
    --}}
    <link rel="stylesheet" href="{{ asset('vendor/modernize/css/icons/tabler-icons/tabler-icons.min.css') }}">
    <link id="themeColors" rel="stylesheet" href="{{ asset('vendor/modernize/css/style.min.css') }}">

    <link href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

    {{-- Terakhir, supaya penyesuaian Seekitar menang atas gaya template. --}}
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

{{-- Lewati navigasi: tautan pertama bagi pengguna keyboard & pembaca layar,
     supaya tidak perlu menelusuri belasan butir menu di setiap halaman. --}}
<a href="#konten-utama" class="visually-hidden-focusable admin-skip">Lewati ke konten</a>

{{--
    Atribut data-* di bawah BUKAN hiasan: app.min.js membacanya untuk
    menentukan mode sidebar, dan CSS template menargetkannya langsung
    (mis. [data-sidebartype="mini-sidebar"]). Menghapusnya membuat tombol
    ciutkan sidebar tidak berfungsi.
--}}
<div class="page-wrapper" id="main-wrapper" data-layout="vertical"
     data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">

    @include('admin.partials.sidebar')

    <div class="body-wrapper">

        @include('admin.partials.header')

        <div class="container-fluid">

            @hasSection('breadcrumb')
                <nav aria-label="Remah roti" class="mb-3">
                    <ol class="breadcrumb mb-0">@yield('breadcrumb')</ol>
                </nav>
            @endif

            <main id="konten-utama">

                {{-- role="alert" agar pembaca layar mengumumkan hasil aksi;
                     tanpa itu pengguna non-visual tidak tahu simpannya
                     berhasil. --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                        <i class="ti ti-circle-check fs-5" aria-hidden="true"></i>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                        <i class="ti ti-alert-triangle fs-5" aria-hidden="true"></i>
                        <span>{{ session('error') }}</span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

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
</div>

{{-- jQuery WAJIB paling awal: sidebarmenu.js, app.min.js, Datatables, dan
     Select2 semuanya plugin jQuery. --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
{{-- bootstrap.bundle: HANYA JS-nya. CSS-nya sudah ada di styles.min.css. --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simplebar@6.3.3/dist/simplebar.min.js"></script>

{{-- Urutan WAJIB: app.min.js mendefinisikan $.fn.AdminSettings yang dipanggil
     seekitar.init.js. Membaliknya membuat sidebar responsif mati diam-diam. --}}
<script src="{{ asset('vendor/modernize/js/app.min.js') }}"></script>
<script src="{{ asset('vendor/modernize/js/seekitar.init.js') }}"></script>
<script src="{{ asset('vendor/modernize/js/sidebarmenu.js') }}"></script>
<script src="{{ asset('vendor/modernize/js/custom.js') }}"></script>

<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
{{-- select2.full: varian ini sudah memuat modul terjemahan, sehingga berkas
     i18n/id.js di bawah bisa mendaftarkan dirinya. Varian select2.min biasa
     TIDAK memuatnya dan bahasa Indonesia diam-diam diabaikan. --}}
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
