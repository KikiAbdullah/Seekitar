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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- FontAwesome 7.3.1 — HANYA di panel admin. Aplikasi mobile & web publik
         memakai Heroicons; mencampurnya dalam satu layar langsung terasa tidak
         rapi karena ketebalan garisnya berbeda (BRANDING §3.7.1). --}}
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@7.3.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

{{-- Lewati navigasi: tautan pertama bagi pengguna keyboard & pembaca layar,
     supaya tidak perlu menelusuri 12 butir menu di setiap halaman. --}}
<a href="#konten-utama" class="visually-hidden-focusable admin-skip">Lewati ke konten</a>

<div class="admin-shell">

    @include('admin.partials.sidebar')

    {{-- Latar gelap saat sidebar terbuka di layar kecil. Tanpa ini, sidebar
         menutupi konten dan tidak ada cara jelas untuk menutupnya kembali. --}}
    <div class="admin-backdrop" id="sidebarBackdrop" hidden></div>

    <div class="admin-main">

        <header class="admin-topbar">
            <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button"
                    id="sidebarToggle" aria-controls="sidebar" aria-expanded="false"
                    aria-label="Buka menu navigasi">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>

            <div class="admin-topbar-title">
                <h1 class="h6 mb-0">@yield('title', 'Panel Admin')</h1>
                @hasSection('breadcrumb')
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">@yield('breadcrumb')</ol>
                    </nav>
                @endif
            </div>

            <div class="dropdown ms-auto">
                <button class="btn btn-sm btn-light border d-flex align-items-center gap-2"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                    <span class="d-none d-sm-inline">{{ auth()->user()?->name }}</span>
                    <i class="fa-solid fa-chevron-down fa-xs opacity-50" aria-hidden="true"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="px-3 py-2">
                        <div class="fw-semibold">{{ auth()->user()?->name }}</div>
                        <div class="small text-muted">{{ auth()->user()?->email }}</div>
                        <div class="mt-1">
                            {{-- Peran ditampilkan apa adanya dari Spatie, bukan
                                 ditebak dari daftar permission: dua admin bisa
                                 punya izin sama tetapi peran berbeda. --}}
                            @foreach (auth()->user()?->getRoleNames() ?? [] as $role)
                                <span class="badge text-bg-secondary">{{ $role }}</span>
                            @endforeach
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.profile.edit') }}">
                            <i class="fa-solid fa-user fa-fw me-1" aria-hidden="true"></i> Profil Saya
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.password.edit') }}">
                            <i class="fa-solid fa-key fa-fw me-1" aria-hidden="true"></i> Ubah Kata Sandi
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        {{-- POST, bukan GET: logout mengubah state, dan tautan
                             GET bisa dipicu prefetch browser atau <img> di
                             halaman lain. --}}
                        <form method="POST" action="{{ route('admin.logout') }}" class="px-3 py-1">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                <i class="fa-solid fa-right-from-bracket fa-fw me-1" aria-hidden="true"></i> Keluar
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <main class="admin-content" id="konten-utama">

            {{-- role="alert" agar pembaca layar mengumumkan hasil aksi; tanpa
                 itu, pengguna non-visual tidak tahu simpannya berhasil. --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
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

        <footer class="admin-footer">
            <span>&copy; {{ now()->year }} {{ config('seekitar.company.name') }}</span>
            <span class="text-muted">Panel Admin Seekitar</span>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script>
    // Token CSRF dipasang sekali untuk seluruh request AJAX.
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // Sidebar geser di layar kecil. aria-expanded ikut diperbarui — tanpa itu
    // pembaca layar selalu melaporkan menu dalam keadaan tertutup.
    (function () {
        const shell    = document.querySelector('.admin-shell');
        const toggle   = document.getElementById('sidebarToggle');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (!shell || !toggle || !backdrop) return;

        const setOpen = (open) => {
            shell.classList.toggle('sidebar-open', open);
            toggle.setAttribute('aria-expanded', String(open));
            backdrop.hidden = !open;
        };

        toggle.addEventListener('click', () => setOpen(!shell.classList.contains('sidebar-open')));
        backdrop.addEventListener('click', () => setOpen(false));
        // Escape menutup menu: pola yang sudah diharapkan pengguna keyboard.
        document.addEventListener('keydown', e => { if (e.key === 'Escape') setOpen(false); });
    })();
</script>
@stack('scripts')
</body>
</html>
