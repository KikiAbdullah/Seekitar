<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — Seekitar Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --seekitar-green: #168A4A; }
        body { background: #F8FAFC; }
        .login-card { max-width: 420px; }
        .brand-dot {
            width: 44px; height: 44px; border-radius: 50%;
            background: var(--seekitar-green); color: #fff;
            display: grid; place-items: center; font-weight: 700;
        }
        .btn-seekitar { background: var(--seekitar-green); color: #fff; }
        .btn-seekitar:hover { background: #11703C; color: #fff; }
        /* 11px batas terkecil; target pengguna 40+ (BRANDING §4). */
        .form-text { font-size: 12px; }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100">

<main class="container">
    <div class="login-card mx-auto">
        <div class="d-flex align-items-center gap-2 mb-4 justify-content-center">
            <div class="brand-dot">S</div>
            <span class="h5 mb-0 fw-bold">Seekitar Admin</span>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h6 mb-3">Masuk ke panel</h1>

                {{-- Pesan kegagalan sengaja seragam: membedakan "email tidak
                     terdaftar" dari "kata sandi salah" akan membocorkan email
                     mana yang ada di sistem. --}}
                @if ($errors->any())
                    <div class="alert alert-danger py-2" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if (session('status'))
                    <div class="alert alert-success py-2">{{ session('status') }}</div>
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

                    <div class="mb-3">
                        <label for="password" class="form-label">Kata Sandi</label>
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               required autocomplete="current-password">
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" id="remember" name="remember" value="1" class="form-check-input">
                        <label for="remember" class="form-check-label">Ingat saya</label>
                    </div>

                    <button type="submit" class="btn btn-seekitar w-100">Masuk</button>
                </form>

                <p class="form-text text-muted mt-3 mb-0">
                    Panel ini hanya untuk admin. Pengguna aplikasi masuk lewat
                    OTP WhatsApp, bukan halaman ini.
                </p>
            </div>
        </div>
    </div>
</main>

</body>
</html>
