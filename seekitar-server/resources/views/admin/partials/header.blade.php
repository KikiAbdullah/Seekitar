<header class="app-header">
    <nav class="navbar navbar-expand-lg navbar-light">

        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link sidebartoggler nav-icon-hover ms-n3" id="headerCollapse" href="javascript:void(0)"
                   aria-label="Buka menu navigasi">
                    <i class="ti ti-menu-2" aria-hidden="true"></i>
                </a>
            </li>

            <li class="nav-item d-none d-md-flex align-items-center ps-2">
                <span class="fw-semibold fs-4 text-dark">@yield('title', 'Panel Admin')</span>
            </li>
        </ul>

        <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
            <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-end gap-2">

                @can('manage-disputes')
                    @if (($laporanLewatSla ?? 0) > 0)
                        <li class="nav-item">
                            <a class="nav-link nav-icon-hover position-relative" href="{{ route('admin.disputes.index') }}"
                               title="{{ $laporanLewatSla }} laporan melewati SLA">
                                <i class="ti ti-alert-triangle fs-6 text-danger" aria-hidden="true"></i>
                                <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle">
                                    {{ $laporanLewatSla }}
                                    <span class="visually-hidden">laporan melewati SLA</span>
                                </span>
                            </a>
                        </li>
                    @endif
                @endcan

                <li class="nav-item dropdown">
                    <a class="nav-link nav-icon-hover d-flex align-items-center gap-2" href="javascript:void(0)"
                       id="dropdownProfil" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="admin-avatar" aria-hidden="true">
                            {{ Str::upper(Str::substr(auth()->user()?->name ?? 'A', 0, 1)) }}
                        </span>
                        <span class="d-none d-sm-inline fs-3 text-dark">{{ auth()->user()?->name }}</span>
                        <i class="ti ti-chevron-down fs-4" aria-hidden="true"></i>
                    </a>

                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up"
                         aria-labelledby="dropdownProfil">
                        <div class="message-body">

                            <div class="px-3 py-2">
                                <div class="fw-semibold">{{ auth()->user()?->name }}</div>
                                <div class="fs-2 text-muted">{{ auth()->user()?->email }}</div>
                                <div class="mt-1">
                                    @foreach (auth()->user()?->getRoleNames() ?? [] as $role)
                                        <span class="badge bg-primary-subtle text-primary fs-1">{{ $role }}</span>
                                    @endforeach
                                </div>
                            </div>

                            <hr class="dropdown-divider">

                            <a href="{{ route('admin.profile.edit') }}"
                               class="d-flex align-items-center gap-2 dropdown-item">
                                <i class="ti ti-user-circle fs-6" aria-hidden="true"></i>
                                <p class="mb-0 fs-3">Profil Saya</p>
                            </a>

                            <a href="{{ route('admin.password.edit') }}"
                               class="d-flex align-items-center gap-2 dropdown-item">
                                <i class="ti ti-key fs-6" aria-hidden="true"></i>
                                <p class="mb-0 fs-3">Ubah Kata Sandi</p>
                            </a>

                            <form method="POST" action="{{ route('admin.logout') }}" class="px-3 mt-2">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger w-100 d-block">
                                    <i class="ti ti-logout me-1" aria-hidden="true"></i> Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
</header>
