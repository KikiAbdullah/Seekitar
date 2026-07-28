{{--
    Sidebar panel admin (Server_Implementation_Guide.md §8).

    SETIAP butir menu dibungkus @can dengan permission yang SAMA PERSIS dengan
    middleware `permission:` pada route-nya di routes/admin.php. Kalau keduanya
    berbeda, salah satu dari dua hal buruk terjadi:

      - menu tampil tapi diklik menghasilkan 403 (menu berbohong), atau
      - menu tersembunyi padahal admin sebenarnya berhak (fitur hilang diam-diam).

    Kecocokan itu ditegakkan otomatis oleh tools/dev/check-admin-menu.mjs, yang
    membandingkan @can di berkas ini dengan middleware hasil `route:list`.

    ⚠️ @can di sini HANYA menyembunyikan tautan — ia bukan pengaman. Otorisasi
    sesungguhnya tetap di middleware route (§6.3); pengguna bisa mengetik URL
    langsung.
--}}
@php
    /*
     * Dihitung sekali di sini, dipakai dua kali di bawah (kelas `show` pada
     * submenu dan `aria-expanded` pada tombolnya). Kalau keduanya dihitung
     * terpisah, submenu bisa terbuka secara visual sementara pembaca layar
     * diberi tahu ia tertutup.
     */
    $verifikasiTerbuka = request()->routeIs('admin.verifications.*');
@endphp

<nav id="sidebar" class="admin-sidebar" aria-label="Menu utama">
    <a href="{{ route('admin.dashboard') }}" class="admin-brand">
        <span class="admin-brand-dot" aria-hidden="true">S</span>
        <span class="admin-brand-text">Seekitar<span class="fw-normal opacity-75"> Admin</span></span>
    </a>

    <ul class="nav flex-column admin-nav">

        {{-- Dasbor tidak diberi @can: setiap admin yang lolos `role:admin|super-admin`
             berhak melihatnya, dan isinya sendiri sudah disaring per permission. --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
               href="{{ route('admin.dashboard') }}"
               @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                <i class="fa-solid fa-house fa-fw" aria-hidden="true"></i>
                <span>Dasbor</span>
            </a>
        </li>

        {{-- ── Verifikasi ──────────────────────────────────────────────────
             @canany, bukan @can: induk dropdown harus tetap muncul bila admin
             punya SALAH SATU dari dua izin di dalamnya. --}}
        @canany(['verify-users', 'verify-stores'])
            <li class="nav-item">
                <a class="nav-link d-flex justify-content-between align-items-center {{ $verifikasiTerbuka ? 'active' : '' }}"
                   data-bs-toggle="collapse" href="#menuVerifikasi" role="button"
                   aria-expanded="{{ $verifikasiTerbuka ? 'true' : 'false' }}"
                   aria-controls="menuVerifikasi">
                    <span>
                        <i class="fa-solid fa-circle-check fa-fw" aria-hidden="true"></i>
                        <span>Verifikasi</span>
                    </span>
                    @if ($pendingVerifikasi ?? 0)
                        <span class="badge rounded-pill text-bg-warning">{{ $pendingVerifikasi }}</span>
                    @else
                        <i class="fa-solid fa-chevron-down fa-xs opacity-50" aria-hidden="true"></i>
                    @endif
                </a>

                <ul class="nav flex-column admin-subnav collapse {{ $verifikasiTerbuka ? 'show' : '' }}"
                    id="menuVerifikasi">
                    @can('verify-users')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.verifications.users') ? 'active' : '' }}"
                               href="{{ route('admin.verifications.users') }}">
                                <i class="fa-solid fa-id-card fa-fw" aria-hidden="true"></i>
                                <span>Pengguna</span>
                            </a>
                        </li>
                    @endcan

                    @can('verify-stores')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.verifications.stores') ? 'active' : '' }}"
                               href="{{ route('admin.verifications.stores') }}">
                                <i class="fa-solid fa-shop fa-fw" aria-hidden="true"></i>
                                <span>Toko</span>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcanany

        {{-- ── Manajemen Data ──────────────────────────────────────────────
             Judul kelompok ikut disembunyikan bila admin tidak punya satu pun
             izin di bawahnya; kalau tidak, akan tampil judul tanpa isi. --}}
        @canany([
            'manage-users', 'manage-categories', 'manage-stores', 'manage-listings',
            'manage-requests', 'manage-offers', 'manage-orders', 'manage-disputes',
            'manage-reviews',
        ])
            <li class="admin-nav-heading" aria-hidden="true">Manajemen Data</li>
        @endcanany

        @can('manage-users')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                   href="{{ route('admin.users.index') }}">
                    <i class="fa-solid fa-users fa-fw" aria-hidden="true"></i>
                    <span>Pengguna</span>
                </a>
            </li>
        @endcan

        @can('manage-categories')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
                   href="{{ route('admin.categories.index') }}">
                    <i class="fa-solid fa-tags fa-fw" aria-hidden="true"></i>
                    <span>Kategori</span>
                </a>
            </li>
        @endcan

        @can('manage-stores')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.stores.*') ? 'active' : '' }}"
                   href="{{ route('admin.stores.index') }}">
                    <i class="fa-solid fa-store fa-fw" aria-hidden="true"></i>
                    <span>Toko</span>
                </a>
            </li>
        @endcan

        @can('manage-listings')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.listings.*') ? 'active' : '' }}"
                   href="{{ route('admin.listings.index') }}">
                    <i class="fa-solid fa-boxes-stacked fa-fw" aria-hidden="true"></i>
                    <span>Listing</span>
                </a>
            </li>
        @endcan

        @can('manage-requests')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.requests.*') ? 'active' : '' }}"
                   href="{{ route('admin.requests.index') }}">
                    <i class="fa-solid fa-circle-question fa-fw" aria-hidden="true"></i>
                    <span>Permintaan</span>
                </a>
            </li>
        @endcan

        @can('manage-offers')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.offers.*') ? 'active' : '' }}"
                   href="{{ route('admin.offers.index') }}">
                    <i class="fa-solid fa-handshake fa-fw" aria-hidden="true"></i>
                    <span>Penawaran</span>
                </a>
            </li>
        @endcan

        @can('manage-orders')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"
                   href="{{ route('admin.orders.index') }}">
                    <i class="fa-solid fa-cart-shopping fa-fw" aria-hidden="true"></i>
                    <span>Pesanan</span>
                </a>
            </li>
        @endcan

        @can('manage-disputes')
            <li class="nav-item">
                <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('admin.disputes.*') ? 'active' : '' }}"
                   href="{{ route('admin.disputes.index') }}">
                    <span>
                        <i class="fa-solid fa-triangle-exclamation fa-fw" aria-hidden="true"></i>
                        <span>Laporan</span>
                    </span>
                    {{-- Angka merah hanya untuk yang SUDAH lewat SLA — kalau
                         semua laporan terbuka ikut dihitung, badge ini selalu
                         menyala dan berhenti berarti apa-apa. --}}
                    @if ($laporanLewatSla ?? 0)
                        <span class="badge rounded-pill text-bg-danger">{{ $laporanLewatSla }}</span>
                    @endif
                </a>
            </li>
        @endcan

        @can('manage-reviews')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}"
                   href="{{ route('admin.reviews.index') }}">
                    <i class="fa-solid fa-star fa-fw" aria-hidden="true"></i>
                    <span>Ulasan</span>
                </a>
            </li>
        @endcan

        {{-- ── Sistem ──────────────────────────────────────────────────── --}}
        @can('manage-settings')
            <li class="admin-nav-heading" aria-hidden="true">Sistem</li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}"
                   href="{{ route('admin.settings') }}">
                    <i class="fa-solid fa-gear fa-fw" aria-hidden="true"></i>
                    <span>Pengaturan</span>
                </a>
            </li>
        @endcan
    </ul>

    <div class="admin-sidebar-foot">
        <div class="small opacity-75">{{ config('seekitar.regency') }}</div>
        <div class="small opacity-50">Kode BPS {{ config('seekitar.regency_code') }}</div>
    </div>
</nav>
