<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Masuk — Seekitar Admin</title>

    <link rel="stylesheet" href="{{ asset('vendor/modernize/css/icons/tabler-icons/tabler-icons.min.css') }}">
    <link id="themeColors" rel="stylesheet" href="{{ asset('vendor/modernize/css/style.min.css') }}">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body>

<div class="page-wrapper" id="main-wrapper" data-layout="vertical"
     data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">

    <div class="position-relative overflow-hidden text-bg-light min-vh-100 d-flex align-items-center justify-content-center">
        <div class="d-flex align-items-center justify-content-center w-100">
            <div class="row justify-content-center w-100">
                <div class="col-md-8 col-lg-6 col-xxl-4">
                    <div class="card mb-0">
                        <div class="card-body p-4">

                            <div class="text-center py-3">
                                <span class="admin-brand-dot mx-auto mb-2" aria-hidden="true"
                                      style="width:48px;height:48px;font-size:22px;">S</span>
                                <h1 class="h5 fw-bold mb-1">Seekitar Admin</h1>
                                <p class="fs-2 text-muted mb-0">{{ config('seekitar.regency') }}</p>
                            </div>

                            @if ($errors->any())
                                <div class="alert alert-danger py-2 d-flex align-items-center gap-2" role="alert">
                                    <i class="ti ti-alert-triangle" aria-hidden="true"></i>
                                    <span>{{ $errors->first() }}</span>
                                </div>
                            @endif

                            @if (session('status'))
                                <div class="alert alert-success py-2" role="alert">{{ session('status') }}</div>
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
                                               value="1" id="remember" name="remember">
                                        <label class="form-check-label text-dark" for="remember">
                                            Ingat perangkat ini
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-8 fs-4 mb-4 rounded-2">
                                    Masuk
                                </button>

                                <p class="fs-2 text-muted text-center mb-0">
                                    Panel ini hanya untuk admin. Pengguna aplikasi masuk
                                    lewat OTP WhatsApp, bukan halaman ini.
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
