<header class="app-header">
    <nav class="navbar navbar-expand-lg navbar-light">

        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link sidebartoggler nav-icon-hover ms-n3" id="headerCollapse"
                   href="javascript:void(0)" aria-label="Buka menu navigasi">
                    <i class="ti ti-menu-2" aria-hidden="true"></i>
                </a>
            </li>

            <li class="nav-item d-none d-lg-block">
                <span class="nav-link fw-semibold fs-4 text-dark">@yield('title', 'Panel Admin')</span>
            </li>
        </ul>

        <div class="d-block d-lg-none">
            <span class="admin-brand-dot" aria-hidden="true">S</span>
        </div>

        <button class="navbar-toggler p-0 border-0" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                aria-label="Buka menu pengguna">
            <span class="p-2">
                <i class="ti ti-dots fs-7" aria-hidden="true"></i>
            </span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <div class="d-flex align-items-center justify-content-between">

                <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-center">

                    @can('manage-disputes')
                        <li class="nav-item dropdown">
                            <a class="nav-link nav-icon-hover" href="javascript:void(0)" id="dropNotifikasi"
                               data-bs-toggle="dropdown" aria-expanded="false"
                               aria-label="Notifikasi">
                                <i class="ti ti-bell-ringing" aria-hidden="true"></i>
                                @if (($laporanLewatSla ?? 0) > 0)
                                    <div class="notification bg-danger rounded-circle"></div>
                                @endif
                            </a>

                            <div class="dropdown-menu content-dd dropdown-menu-end dropdown-menu-animate-up"
                                 aria-labelledby="dropNotifikasi">
                                <div class="d-flex align-items-center justify-content-between py-3 px-7">
                                    <h5 class="mb-0 fs-5 fw-semibold">Notifikasi</h5>
                                    @if (($laporanLewatSla ?? 0) > 0)
                                        <span class="badge text-bg-danger rounded-pill px-3 py-1">
                                            {{ $laporanLewatSla }} baru
                                        </span>
                                    @endif
                                </div>

                                <div class="message-body">
                                    @if (($laporanLewatSla ?? 0) > 0)
                                        <a href="{{ route('admin.disputes.index') }}"
                                           class="py-6 px-7 d-flex align-items-center dropdown-item">
                                            <span class="me-3">
                                                <span class="d-flex align-items-center justify-content-center bg-light-danger rounded-circle p-6">
                                                    <i class="ti ti-alert-triangle text-danger fs-6" aria-hidden="true"></i>
                                                </span>
                                            </span>
                                            <div class="w-75 d-inline-block v-middle">
                                                <h6 class="mb-1 fw-semibold">{{ $laporanLewatSla }} laporan lewat SLA</h6>
                                                <span class="d-block fs-2 text-muted">Perlu ditinjau segera</span>
                                            </div>
                                        </a>
                                    @endif

                                    @if (($pendingVerifikasi ?? 0) > 0)
                                        @canany(['verify-users', 'verify-stores'])
                                            <a href="{{ route('admin.verifications.users') }}"
                                               class="py-6 px-7 d-flex align-items-center dropdown-item">
                                                <span class="me-3">
                                                    <span class="d-flex align-items-center justify-content-center bg-light-warning rounded-circle p-6">
                                                        <i class="ti ti-id text-warning fs-6" aria-hidden="true"></i>
                                                    </span>
                                                </span>
                                                <div class="w-75 d-inline-block v-middle">
                                                    <h6 class="mb-1 fw-semibold">{{ $pendingVerifikasi }} menunggu verifikasi</h6>
                                                    <span class="d-block fs-2 text-muted">Antrian KTP &amp; toko</span>
                                                </div>
                                            </a>
                                        @endcanany
                                    @endif

                                    @if (($laporanLewatSla ?? 0) === 0 && ($pendingVerifikasi ?? 0) === 0)
                                        <div class="py-6 px-7 text-center text-muted fs-3">
                                            Tidak ada notifikasi.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endcan

                    <li class="nav-item dropdown">
                        <a class="nav-link pe-0" href="javascript:void(0)" id="dropProfil"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="d-flex align-items-center">
                                <div class="user-profile-img">
                                    <span class="admin-avatar" aria-hidden="true">
                                        {{ Str::upper(Str::substr(auth()->user()?->name ?? 'A', 0, 1)) }}
                                    </span>
                                </div>
                            </div>
                        </a>

                        <div class="dropdown-menu content-dd dropdown-menu-end dropdown-menu-animate-up"
                             aria-labelledby="dropProfil">
                            <div class="profile-dropdown position-relative">

                                <div class="py-3 px-7 pb-0">
                                    <h5 class="mb-0 fs-5 fw-semibold">Profil Pengguna</h5>
                                </div>

                                <div class="d-flex align-items-center py-9 mx-7 border-bottom">
                                    <span class="admin-avatar admin-avatar-lg" aria-hidden="true">
                                        {{ Str::upper(Str::substr(auth()->user()?->name ?? 'A', 0, 1)) }}
                                    </span>
                                    <div class="ms-3">
                                        <h5 class="mb-1 fs-3">{{ auth()->user()?->name }}</h5>
                                        <span class="mb-1 d-block text-dark fs-2">
                                            {{ auth()->user()?->getRoleNames()->implode(', ') }}
                                        </span>
                                        <p class="mb-0 d-flex text-dark align-items-center gap-2 fs-2">
                                            <i class="ti ti-mail fs-4" aria-hidden="true"></i>
                                            {{ auth()->user()?->email }}
                                        </p>
                                    </div>
                                </div>

                                <div class="message-body">
                                    <a href="{{ route('admin.profile.edit') }}"
                                       class="py-8 px-7 mt-8 d-flex align-items-center">
                                        <span class="d-flex align-items-center justify-content-center bg-light rounded-1 p-6">
                                            <i class="ti ti-user-circle fs-6" aria-hidden="true"></i>
                                        </span>
                                        <div class="w-75 d-inline-block v-middle ps-3">
                                            <h6 class="mb-1 bg-hover-primary fw-semibold">Profil Saya</h6>
                                            <span class="d-block text-dark fs-2">Nama &amp; email akun</span>
                                        </div>
                                    </a>

                                    <a href="{{ route('admin.password.edit') }}"
                                       class="py-8 px-7 d-flex align-items-center">
                                        <span class="d-flex align-items-center justify-content-center bg-light rounded-1 p-6">
                                            <i class="ti ti-key fs-6" aria-hidden="true"></i>
                                        </span>
                                        <div class="w-75 d-inline-block v-middle ps-3">
                                            <h6 class="mb-1 bg-hover-primary fw-semibold">Ubah Kata Sandi</h6>
                                            <span class="d-block text-dark fs-2">Keamanan akun</span>
                                        </div>
                                    </a>

                                    @can('manage-settings')
                                        <a href="{{ route('admin.settings') }}"
                                           class="py-8 px-7 d-flex align-items-center">
                                            <span class="d-flex align-items-center justify-content-center bg-light rounded-1 p-6">
                                                <i class="ti ti-settings fs-6" aria-hidden="true"></i>
                                            </span>
                                            <div class="w-75 d-inline-block v-middle ps-3">
                                                <h6 class="mb-1 bg-hover-primary fw-semibold">Pengaturan Sistem</h6>
                                                <span class="d-block text-dark fs-2">Radius, SLA, kedaluwarsa</span>
                                            </div>
                                        </a>
                                    @endcan
                                </div>

                                <div class="d-grid py-4 px-7 pt-8">
                                    <form method="POST" action="{{ route('admin.logout') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary w-100">
                                            Keluar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>
