<aside class="left-sidebar">
    <div>
        <div class="brand-logo d-flex align-items-center justify-content-between">
            <a href="{{ route('admin.dashboard') }}" class="text-nowrap logo-img d-flex align-items-center gap-2">
                <img src="{{ asset('img/brand/logo-lockup.png') }}" alt="Seekitar" width="618" height="144" class="admin-brand-lockup">
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
                @endcanany

                @can('verify-users')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('admin.verifications.users') ? 'active' : '' }}"
                           href="{{ route('admin.verifications.users') }}" aria-expanded="false"
                           @if (request()->routeIs('admin.verifications.users')) aria-current="page" @endif>
                            <span class="d-flex"><i class="ti ti-id" aria-hidden="true"></i></span>
                            <span class="hide-menu">Pengguna</span>
                            @if ($pendingVerifikasiPengguna ?? 0)
                                <span class="hide-menu badge rounded-pill bg-warning-subtle text-warning fs-2 py-1 px-2 ms-auto">
                                    {{ $pendingVerifikasiPengguna }}
                                </span>
                            @endif
                        </a>
                    </li>
                @endcan

                @can('verify-stores')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('admin.verifications.stores') ? 'active' : '' }}"
                           href="{{ route('admin.verifications.stores') }}" aria-expanded="false"
                           @if (request()->routeIs('admin.verifications.stores')) aria-current="page" @endif>
                            <span class="d-flex"><i class="ti ti-building-store" aria-hidden="true"></i></span>
                            <span class="hide-menu">Toko</span>
                            @if ($pendingVerifikasiToko ?? 0)
                                <span class="hide-menu badge rounded-pill bg-warning-subtle text-warning fs-2 py-1 px-2 ms-auto">
                                    {{ $pendingVerifikasiToko }}
                                </span>
                            @endif
                        </a>
                    </li>
                @endcan

                @canany([
                    'manage-users', 'manage-categories', 'manage-stores', 'manage-listings',
                    'manage-requests', 'manage-offers', 'manage-orders', 'manage-disputes',
                    'manage-reviews', 'manage-blog',
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
                        <a class="sidebar-link {{ request()->routeIs('admin.disputes.*') ? 'active' : '' }}"
                           href="{{ route('admin.disputes.index') }}" aria-expanded="false">
                            <span class="d-flex"><i class="ti ti-alert-triangle" aria-hidden="true"></i></span>
                            <span class="hide-menu">Laporan</span>
                            @if ($laporanLewatSla ?? 0)
                                <span class="hide-menu badge rounded-pill bg-danger-subtle text-danger fs-2 py-1 px-2 ms-auto">
                                    {{ $laporanLewatSla }}
                                </span>
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

                @can('manage-blog')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('admin.blog.*') ? 'active' : '' }}"
                           href="{{ route('admin.blog.index') }}" aria-expanded="false">
                            <span class="d-flex"><i class="ti ti-news" aria-hidden="true"></i></span>
                            <span class="hide-menu">Blog</span>
                        </a>
                    </li>
                @endcan

                @canany(['manage-subscriptions', 'manage-advertisements', 'manage-fees'])
                    <li class="nav-small-cap">
                        <i class="ti ti-dots nav-small-cap-icon fs-4" aria-hidden="true"></i>
                        <span class="hide-menu">Monetisasi</span>
                    </li>
                @endcanany

                @can('manage-subscriptions')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}"
                           href="{{ route('admin.subscriptions.index') }}" aria-expanded="false">
                            <span class="d-flex"><i class="ti ti-crown" aria-hidden="true"></i></span>
                            <span class="hide-menu">Langganan</span>
                        </a>
                    </li>
                @endcan

                @can('manage-advertisements')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('admin.advertisements.*') ? 'active' : '' }}"
                           href="{{ route('admin.advertisements.index') }}" aria-expanded="false">
                            <span class="d-flex"><i class="ti ti-ad" aria-hidden="true"></i></span>
                            <span class="hide-menu">Iklan</span>
                        </a>
                    </li>
                @endcan

                @can('manage-fees')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('admin.fees.*') ? 'active' : '' }}"
                           href="{{ route('admin.fees.index') }}" aria-expanded="false">
                            <span class="d-flex"><i class="ti ti-currency-dollar" aria-hidden="true"></i></span>
                            <span class="hide-menu">Biaya</span>
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

        {{-- Baris wilayah sesingkat satu napas — identitas layanan tetap
             terlihat, tanpa mengorbankan ruang menu di layar pendek. --}}
        <div class="sidebar-ad hide-menu mx-3 mb-3 px-3 py-2 bg-light-primary rounded">
            <span class="d-flex align-items-center gap-2 fs-2">
                <i class="ti ti-map-pin text-primary" aria-hidden="true"></i>
                <span class="fw-semibold text-primary">{{ config('seekitar.regency') }}</span>
                <span class="text-muted">· BPS {{ config('seekitar.regency_code') }}</span>
            </span>
        </div>
    </div>
</aside>
