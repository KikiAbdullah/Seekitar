<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel Admin') — Seekitar</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        /* Palet dari BRANDING-GUIDELINE.md §3.5 */
        :root { --seekitar-green: #168A4A; --seekitar-yellow: #F5B83D; }
        .navbar-seekitar { background-color: var(--seekitar-green); }
        .navbar-seekitar .navbar-brand,
        .navbar-seekitar .nav-link { color: #fff; }
        .navbar-seekitar .nav-link.active { font-weight: 600; text-decoration: underline; }
        .btn-seekitar { background-color: var(--seekitar-green); color: #fff; }
        .btn-seekitar:hover { background-color: #11703c; color: #fff; }
        /* 11px adalah batas terkecil yang diizinkan — target pengguna 40+
           tahun yang umumnya sudah presbiopia (BRANDING §4). */
        .table, .form-control, .btn { font-size: 14px; }
    </style>
    @stack('styles')
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-seekitar mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('admin.dashboard') }}">Seekitar Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
                aria-controls="nav" aria-expanded="false" aria-label="Buka menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                {{-- Menu ditampilkan per-permission: admin tanpa izin tidak
                     melihat menu yang toh akan ditolak saat diklik. --}}
                @can('manage-users')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                           href="{{ route('admin.users.index') }}">Pengguna</a>
                    </li>
                @endcan
                @can('manage-stores')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.stores.*') ? 'active' : '' }}"
                           href="{{ route('admin.stores.index') }}">Toko</a>
                    </li>
                @endcan
                @can('manage-disputes')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.disputes.*') ? 'active' : '' }}"
                           href="{{ route('admin.disputes.index') }}">Laporan</a>
                    </li>
                @endcan
            </ul>
            <span class="navbar-text text-white">{{ auth()->user()?->name }}</span>
        </div>
    </div>
</nav>

<main class="container pb-5">
    @hasSection('breadcrumb')
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">@yield('breadcrumb')</ol>
        </nav>
    @endif

    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script>
    // Token CSRF dipasang sekali untuk seluruh request AJAX.
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@stack('scripts')
</body>
</html>
