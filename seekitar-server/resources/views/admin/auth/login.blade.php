<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin — Seekitar</title>
  
  <link rel="shortcut icon" type="image/png" href="{{ asset('img/brand/logo-mark.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('img/brand/logo-mark.png') }}">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200;300;400;500;600;700;800&display=swap">
  
  <link rel="stylesheet" href="{{ asset('vendor/mordenize/css/style.min.css') }}">
</head>
<body>
  <!-- Preloader -->
  <div class="preloader">
    <img src="{{ asset('img/brand/logo-mark.png') }}" alt="loader" class="lds-ripple img-fluid" style="width: 48px; height: 48px;" />
  </div>
  
  <!--  Body Wrapper -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">
    <div class="position-relative overflow-hidden radial-gradient min-vh-100">
      <div class="position-relative z-index-5">
        <div class="row">
          <div class="col-xl-7 col-xxl-8">
            <a href="{{ route('web.home') }}" class="text-nowrap logo-img d-block px-4 py-9 w-100">
              <img src="{{ asset('img/brand/logo-lockup.png') }}" width="180" alt="Seekitar">
            </a>
            <div class="d-none d-xl-flex align-items-center justify-content-center" style="height: calc(100vh - 80px);">
              <img src="{{ asset('vendor/mordenize/images/backgrounds/login-security.svg') }}" alt="Login Security" class="img-fluid" width="500">
            </div>
          </div>
          <div class="col-xl-5 col-xxl-4">
            <div class="authentication-login min-vh-100 bg-body row justify-content-center align-items-center p-4">
              <div class="col-sm-8 col-md-6 col-xl-9">
                <h2 class="mb-3 fs-7 fw-bolder">Panel Admin</h2>
                <p class="mb-9 text-muted">Silakan masuk dengan email dan kata sandi Anda.</p>
                
                @if ($errors->any())
                  <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                      @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                      @endforeach
                    </ul>
                  </div>
                @endif
                
                <form action="{{ route('admin.login.store') }}" method="POST">
                  @csrf
                  <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                    @error('email')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                  <div class="mb-4">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="current-password">
                    @error('password')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                  <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check">
                      <input class="form-check-input primary" type="checkbox" name="remember" id="remember" value="1" checked>
                      <label class="form-check-label text-dark" for="remember">
                        Ingat Perangkat Ini
                      </label>
                    </div>
                  </div>
                  <button type="submit" class="btn btn-primary w-100 py-8 mb-4 rounded-2">Masuk</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <script src="{{ asset('vendor/mordenize/libs/jquery/dist/jquery.min.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
  
  <script src="{{ asset('vendor/mordenize/js/app.min.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/js/app.init.js') }}"></script>
  <script src="{{ asset('vendor/mordenize/js/custom.js') }}"></script>
</body>
</html>
