<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Masuk — Seekitar Admin</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('vendor/modernize/css/icons/tabler-icons/tabler-icons.min.css') }}">
    <link id="themeColors" rel="stylesheet" href="{{ asset('vendor/modernize/css/style.min.css') }}">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body>

<div class="page-wrapper" id="main-wrapper" data-layout="vertical"
     data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">

    <div class="position-relative overflow-hidden radial-gradient min-vh-100">
        <div class="position-relative z-index-5">
            <div class="row">

                <div class="col-xl-7 col-xxl-8">
                    <a href="{{ url('/') }}" class="text-nowrap logo-img d-flex align-items-center gap-2 px-4 py-9">
                        <img src="{{ asset('img/brand/logo-lockup.png') }}" alt="Seekitar" width="618" height="144" class="admin-brand-lockup">
                    </a>

                    <div class="d-none d-xl-flex align-items-center justify-content-center admin-login-art">
                        <img src="{{ asset('vendor/modernize/images/backgrounds/login-security.svg') }}"
                             alt="" class="img-fluid" width="500" height="500">
                    </div>
                </div>

                <div class="col-xl-5 col-xxl-4">
                    <div class="authentication-login min-vh-100 bg-body row justify-content-center align-items-center p-4">
                        <div class="col-sm-8 col-md-6 col-xl-9">

                            <h1 class="mb-3 fs-7 fw-bolder">Selamat datang</h1>
                            <p class="mb-9">Panel admin {{ config('seekitar.regency') }}</p>

                            @if ($errors->any())
                                <div class="alert alert-danger bg-light-danger text-danger border-0 d-flex align-items-start gap-2 mb-4"
                                     role="alert">
                                    <i class="ti ti-alert-triangle fs-4 mt-1" aria-hidden="true"></i>
                                    <span class="fs-3">{{ $errors->first() }}</span>
                                </div>
                            @endif

                            @if (session('status'))
                                <div class="alert alert-success bg-light-success text-success border-0 d-flex align-items-start gap-2 mb-4"
                                     role="alert">
                                    <i class="ti ti-circle-check fs-4 mt-1" aria-hidden="true"></i>
                                    <span class="fs-3">{{ session('status') }}</span>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('admin.login.store') }}" novalidate>
                                @csrf

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" id="email" name="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email') }}" required autofocus
                                           autocomplete="username" inputmode="email">
                                </div>

                                <div class="mb-4">
                                    <label for="password" class="form-label">Kata Sandi</label>
                                    <input type="password" id="password" name="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           required autocomplete="current-password">
                                </div>

                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input primary" type="checkbox"
                                               value="1" id="remember" name="remember"
                                               @checked(old('remember'))>
                                        <label class="form-check-label text-dark" for="remember">
                                            Ingat perangkat ini
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-8 mb-4 rounded-2">
                                    Masuk
                                </button>

                                <div class="d-flex align-items-start gap-2 text-muted">
                                    <i class="ti ti-info-circle fs-4 mt-1 flex-shrink-0" aria-hidden="true"></i>
                                    <p class="fs-2 mb-0">
                                        Halaman ini hanya untuk admin. Pengguna aplikasi Seekitar
                                        masuk lewat OTP WhatsApp, bukan dari sini.
                                    </p>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

</body>
</html>
