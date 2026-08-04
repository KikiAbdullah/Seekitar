<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Admin Panel — Seekitar')</title>
  
  <link rel="shortcut icon" type="image/png" href="{{ asset('img/brand/logo-mark.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('img/brand/logo-mark.png') }}">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200;300;400;500;600;700;800&display=swap">
  
  <link rel="stylesheet" href="{{ asset('vendor/mordenize/libs/owl.carousel/dist/assets/owl.carousel.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/mordenize/css/style.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/mordenize/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/mordenize/libs/select2@4.1.0/dist/css/select2.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/mordenize/libs/select2-bootstrap-5-theme/select2-bootstrap-5-theme.min.css') }}">
  @stack('styles')
  <style>
    .dataTables_wrapper .dataTables_info { padding-top: 0; }
    div.dataTables_wrapper div.dataTables_filter { position: relative; margin: 0; }
    div.dataTables_wrapper div.dataTables_filter label { margin: 0; font-size: 0; white-space: nowrap; }
    div.dataTables_wrapper div.dataTables_filter input {
      margin-left: 0;
      width: 200px;
      padding: 0.5rem 1rem 0.5rem 2.5rem;
      border: 1px solid #dfe5ef;
      border-radius: 6px;
      font-size: 0.875rem;
      color: #5a6a85;
    }
    div.dataTables_wrapper .dt-filters select.form-select,
    div.dataTables_wrapper .dt-filters input.form-control {
      width: 200px;
    }
    div.dataTables_wrapper div.dataTables_filter input:focus {
      border-color: #5d87ff;
      box-shadow: 0 0 0 0.15rem rgba(93,135,255,.15);
    }
    div.dataTables_wrapper div.dataTables_filter::before {
      font-family: "tabler-icons";
      content: "\eb1c";
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1rem;
      color: #5a6a85;
      pointer-events: none;
    }
  </style>
</head>
<body>
  <!-- Preloader -->
  <div class="preloader">
    <img src="{{ asset('img/brand/logo-mark.png') }}" alt="loader" class="lds-ripple img-fluid" style="width: 48px; height: 48px;" />
  </div>

  <div class="dark-transparent sidebartoggler"></div>

  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">
    <!-- Sidebar Start -->
    @include('admin.partials.sidebar')
    <!-- Sidebar End -->
    
    <!-- Main wrapper -->
    <div class="body-wrapper">
      <!-- Header Start -->
      @include('admin.partials.header')
      <!-- Header End -->
      
      <div class="container-fluid">
        @if (session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif
        
        @if ($errors->any())
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        @yield('content')
      </div>
    </div>
  </div>
  
  <script src="{{ asset('vendor/mordenize/libs/jquery/dist/jquery.min.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/libs/simplebar/dist/simplebar.min.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
  
  <script src="{{ asset('vendor/mordenize/js/app.min.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/js/seekitar.init.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/js/app-style-switcher.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/js/sidebarmenu.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/js/custom.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/libs/select2@4.1.0/dist/js/select2.full.min.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/libs/select2@4.1.0/dist/js/i18n/id.js') }}"></script>
  <script>
    // Bahasa Datatables disetel sekali di sini, tidak diulang tiap tabel.
    $.fn.dataTable.defaults.language = { url: "{{ asset('vendor/datatables/id.json') }}" };

    // Select2 untuk SEMUA dropdown panel. Dipasang ke `.js-select2`, bukan
    // ke seluruh `select`: pemilih "Tampilkan N entri" milik Datatables
    // dirender ulang setiap tabel digambar.
    window.seekitarSelect2 = function (scope) {
      const $target = $(scope || document).find('.js-select2');
      if (!$target.length || typeof $.fn.select2 !== 'function') return;
      $target.each(function () {
        const $el = $(this);
        if ($el.hasClass('select2-hidden-accessible')) return;
        $el.select2({
          theme: 'bootstrap-5',
          language: 'id',
          width: $el.data('width') || 'style',
        });
      });
    };
    seekitarSelect2();
  </script>
  @stack('scripts')
</body>
</html>
