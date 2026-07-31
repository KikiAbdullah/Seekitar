<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Admin Panel — Seekitar')</title>
  
  <link rel="shortcut icon" type="image/png" href="{{ asset('img/brand/logo-mark.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('img/brand/logo-mark.png') }}">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200;300;400;500;600;700;800&display=swap">
  
  <link rel="stylesheet" href="{{ asset('vendor/mordenize/libs/owl.carousel/dist/assets/owl.carousel.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/mordenize/css/style.min.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  @stack('styles')
</head>
<body>
  <!-- Preloader -->
  <div class="preloader">
    <img src="{{ asset('img/brand/logo-mark.png') }}" alt="loader" class="lds-ripple img-fluid" style="width: 48px; height: 48px;" />
  </div>
  
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
  <script src="{{ asset('vendor/mordenize/js/app.init.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/js/app-style-switcher.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/js/sidebarmenu.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/js/custom.js') }}"></script>
  @stack('scripts')
</body>
</html>
