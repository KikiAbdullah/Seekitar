@php
    /*
     * Dihitung sekali; dipakai untuk kelas `show` pada submenu DAN untuk
     * aria-expanded pada pemicunya. Kalau dihitung terpisah, submenu bisa
     * terbuka secara visual sementara pembaca layar diberi tahu ia tertutup.
     */
    $verifikasiTerbuka = request()->routeIs('admin.verifications.*');
@endphp

<aside class="left-sidebar">
    <div>
        <div class="brand-logo d-flex align-items-center justify-content-between">
            <a href="{{ route('admin.dashboard') }}" class="text-nowrap logo-img d-flex align-items-center gap-2">
                <span class="admin-brand-dot" aria-hidden="true">S</span>
                <span class="fw-bold fs-5 text-dark">Seekitar</span>
            </a>
            <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse"
                 role="button" tabindex="0" aria-label="Tutup menu">
                <i class="ti ti-x fs-8 text-muted" aria-hidden="true"></i>
            </div>
        </div>

        <nav class="sidebar-nav scroll-sidebar" data-simplebar="" aria-label="Menu utama">
            <ul id="sidebarnav">

                <li class="nav-small-cap">
                    <i class="ti ti-dots nav-small-cap-icon fs-4" aria-hidden="true"></i>
                    <span class="hide-menu">Beranda</span>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                       href="{{ route('admin.dashboard') }}" aria-expanded="false"
                       @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                        <span class="d-flex"><i class="ti ti-layout-dashboard" aria-hidden="true"></i></span>
                        <span class="hide-menu">Dasbor</span>
                    </a>
                </li>

                @canany(['verify-users', 'verify-stores'])
                    <li class="nav-small-cap">
                        <i class="ti ti-dots nav-small-cap-icon fs-4" aria-hidden="true"></i>
                        <span class="hide-menu">Verifikasi</span>
                    </li>

                    <li class="sidebar-item">
                        <a class="sidebar-link has-arrow justify-content-between {{ $verifikasiTerbuka ? 'active' : '' }}"
                           href="javascript:void(0)" aria-expanded="{{ $verifikasiTerbuka ? 'true' : 'false' }}">
                            <div class="d-flex align-items-center gap-3">
                                <span class="d-flex"><i class="ti ti-checkup-list" aria-hidden="true"></i></span>
                                <span class="hide-menu">Antrian</span>
                            </div>
                            @if ($pendingVerifikasi ?? 0)
                                <div class="hide-menu">
                                    <span class="badge rounded-pill bg-warning-subtle text-warning fs-2 py-1 px-2">
                                        {{ $pendingVerifikasi }}
                                    </span>
                                </div>
                            @endif
                        </a>

                        <ul aria-expanded="false" class="collapse first-level {{ $verifikasiTerbuka ? 'in' : '' }}">
                            @can('verify-users')
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.verifications.users') ? 'active' : '' }}"
                                       href="{{ route('admin.verifications.users') }}">
                                        <div class="round-16 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-id" aria-hidden="true"></i>
                                        </div>
                                        <span class="hide-menu">Pengguna</span>
                                    </a>
                                </li>
                            @endcan

                            @can('verify-stores')
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.verifications.stores') ? 'active' : '' }}"
                                       href="{{ route('admin.verifications.stores') }}">
                                        <div class="round-16 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-building-store" aria-hidden="true"></i>
                                        </div>
                                        <span class="hide-menu">Toko</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany([
                    'manage-users', 'manage-categories', 'manage-stores', 'manage-listings',
                    'manage-requests', 'manage-offers', 'manage-orders', 'manage-disputes',
                    'manage-reviews',
                ])
                    <li class="nav-small-cap">
                        <i class="ti ti-dots nav-small-cap-icon fs-4" aria-hidden="true"></i>
                        <span class="hide-menu">Manajemen Data</span>
                    </li>
                @endcanany

                @can('manage-users')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                           href="{{ route('admin.users.index') }}" aria-expanded="false">
                            <span class="d-flex"><i class="ti ti-users" aria-hidden="true"></i></span>
                            <span class="hide-menu">Pengguna</span>
                        </a>
                    </li>
                @endcan

                @can('manage-categories')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
                           href="{{ route('admin.categories.index') }}" aria-expanded="false">
                            <span class="d-flex"><i class="ti ti-tag" aria-hidden="true"></i></span>
                            <span class="hide-menu">Kategori</span>
                        </a>
                    </li>
                @endcan

                @can('manage-stores')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('admin.stores.*') ? 'active' : '' }}"
                           href="{{ route('admin.stores.index') }}" aria-expanded="false">
                            <span class="d-flex"><i class="ti ti-building-store" aria-hidden="true"></i></span>
                            <span class="hide-menu">Toko</span>
                        </a>
                    </li>

                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('admin.maps.*') ? 'active' : '' }}"
                           href="{{ route('admin.maps.stores') }}" aria-expanded="false">
                            <span class="d-flex"><i class="ti ti-map-2" aria-hidden="true"></i></span>
                            <span class="hide-menu">Peta Toko</span>
                        </a>
                    </li>
                @endcan

                @can('manage-listings')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('admin.listings.*') ? 'active' : '' }}"
                           href="{{ route('admin.listings.index') }}" aria-expanded="false">
                            <span class="d-flex"><i class="ti ti-package" aria-hidden="true"></i></span>
                            <span class="hide-menu">Listing</span>
                        </a>
                    </li>
                @endcan

                @can('manage-requests')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('admin.requests.*') ? 'active' : '' }}"
                           href="{{ route('admin.requests.index') }}" aria-expanded="false">
                            <span class="d-flex"><i class="ti ti-clipboard-list" aria-hidden="true"></i></span>
                            <span class="hide-menu">Permintaan</span>
                        </a>
                    </li>
                @endcan

                @can('manage-offers')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('admin.offers.*') ? 'active' : '' }}"
                           href="{{ route('admin.offers.index') }}" aria-expanded="false">
                            <span class="d-flex"><i class="ti ti-discount-2" aria-hidden="true"></i></span>
                            <span class="hide-menu">Penawaran</span>
                        </a>
                    </li>
                @endcan

                @can('manage-orders')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"
                           href="{{ route('admin.orders.index') }}" aria-expanded="false">
                            <span class="d-flex"><i class="ti ti-shopping-cart" aria-hidden="true"></i></span>
                            <span class="hide-menu">Pesanan</span>
                        </a>
                    </li>
                @endcan

                @can('manage-disputes')
                    <li class="sidebar-item">
                        <a class="sidebar-link justify-content-between {{ request()->routeIs('admin.disputes.*') ? 'active' : '' }}"
                           href="{{ route('admin.disputes.index') }}" aria-expanded="false">
                            <div class="d-flex align-items-center gap-3">
                                <span class="d-flex"><i class="ti ti-alert-triangle" aria-hidden="true"></i></span>
                                <span class="hide-menu">Laporan</span>
                            </div>
                            @if ($laporanLewatSla ?? 0)
                                <div class="hide-menu">
                                    <span class="badge rounded-pill bg-danger-subtle text-danger fs-2 py-1 px-2">
                                        {{ $laporanLewatSla }}
                                    </span>
                                </div>
                            @endif
                        </a>
                    </li>
                @endcan

                @can('manage-reviews')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}"
                           href="{{ route('admin.reviews.index') }}" aria-expanded="false">
                            <span class="d-flex"><i class="ti ti-star" aria-hidden="true"></i></span>
                            <span class="hide-menu">Ulasan</span>
                        </a>
                    </li>
                @endcan

                @can('manage-settings')
                    <li class="nav-small-cap">
                        <i class="ti ti-dots nav-small-cap-icon fs-4" aria-hidden="true"></i>
                        <span class="hide-menu">Sistem</span>
                    </li>

                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}"
                           href="{{ route('admin.settings') }}" aria-expanded="false">
                            <span class="d-flex"><i class="ti ti-settings" aria-hidden="true"></i></span>
                            <span class="hide-menu">Pengaturan</span>
                        </a>
                    </li>
                @endcan
            </ul>
        </nav>

        <div class="fixed-profile p-3 bg-light-primary rounded sidebar-ad mt-3 hide-menu">
            <div class="hstack gap-3">
                <span class="admin-brand-dot" aria-hidden="true">
                    <i class="ti ti-map-pin fs-4" aria-hidden="true"></i>
                </span>
                <div class="lh-sm">
                    <h6 class="mb-0 fs-3 fw-semibold">{{ config('seekitar.regency') }}</h6>
                    <span class="fs-2 text-muted">Kode BPS {{ config('seekitar.regency_code') }}</span>
                </div>
            </div>
        </div>
    </div>
</aside>
