<aside class="left-sidebar">
  <div>
    <div class="brand-logo d-flex align-items-center justify-content-between">
      <a href="{{ route('admin.dashboard') }}" class="text-nowrap logo-img">
        <img src="{{ asset('img/brand/logo-lockup.png') }}" class="dark-logo" width="140" alt="Seekitar" />
      </a>
      <div class="close-btn d-lg-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
        <i class="ti ti-x fs-8 text-muted" aria-hidden="true"></i>
      </div>
    </div>
    
    <nav class="sidebar-nav scroll-sidebar" data-simplebar>
      <ul id="sidebarnav">
        <li class="nav-small-cap">
          <i class="ti ti-dots nav-small-cap-icon fs-4" aria-hidden="true"></i>
          <span class="hide-menu">Utama</span>
        </li>
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}" aria-expanded="false">
            <span>
              <i class="ti ti-chart-line" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Dashboard</span>
          </a>
        </li>
        
        @can('manage-users')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}" aria-expanded="false">
            <span>
              <i class="ti ti-address-book" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Pengguna</span>
          </a>
        </li>
        @endcan
        
        @can('manage-stores')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.stores.*') || request()->routeIs('admin.maps.stores') ? 'active' : '' }}" href="{{ route('admin.stores.index') }}" aria-expanded="false">
            <span>
              <i class="ti ti-building" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Toko</span>
          </a>
        </li>
        @endcan

        @canany(['verify-users', 'verify-stores'])
        <li class="nav-small-cap">
          <i class="ti ti-dots nav-small-cap-icon fs-4" aria-hidden="true"></i>
          <span class="hide-menu">Verifikasi</span>
        </li>
        @can('verify-users')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.verifications.users') ? 'active' : '' }}" href="{{ route('admin.verifications.users') }}" aria-expanded="false">
            <span>
              <i class="ti ti-id" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Verifikasi KTP</span>
          </a>
        </li>
        @endcan
        @can('verify-stores')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.verifications.stores') ? 'active' : '' }}" href="{{ route('admin.verifications.stores') }}" aria-expanded="false">
            <span>
              <i class="ti ti-building-store" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Verifikasi Toko</span>
          </a>
        </li>
        @endcan
        @endcanany

        @canany(['manage-categories', 'manage-listings', 'manage-requests', 'manage-offers', 'manage-orders', 'manage-reviews'])
        <li class="nav-small-cap">
          <i class="ti ti-dots nav-small-cap-icon fs-4" aria-hidden="true"></i>
          <span class="hide-menu">Katalog & Transaksi</span>
        </li>
        @can('manage-categories')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}" aria-expanded="false">
            <span>
              <i class="ti ti-list" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Kategori</span>
          </a>
        </li>
        @endcan
        @can('manage-listings')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.listings.*') ? 'active' : '' }}" href="{{ route('admin.listings.index') }}" aria-expanded="false">
            <span>
              <i class="ti ti-clipboard" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Listing</span>
          </a>
        </li>
        @endcan
        @can('manage-requests')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.requests.*') ? 'active' : '' }}" href="{{ route('admin.requests.index') }}" aria-expanded="false">
            <span>
              <i class="ti ti-list-check" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Permintaan</span>
          </a>
        </li>
        @endcan
        @can('manage-offers')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.offers.*') ? 'active' : '' }}" href="{{ route('admin.offers.index') }}" aria-expanded="false">
            <span>
              <i class="ti ti-arrows-exchange" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Penawaran</span>
          </a>
        </li>
        @endcan
        @can('manage-orders')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}" aria-expanded="false">
            <span>
              <i class="ti ti-credit-card" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Pesanan</span>
          </a>
        </li>
        @endcan
        @can('manage-reviews')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}" href="{{ route('admin.reviews.index') }}" aria-expanded="false">
            <span>
              <i class="ti ti-star" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Ulasan</span>
          </a>
        </li>
        @endcan
        @endcanany

        @canany(['manage-blog', 'manage-advertisements', 'manage-subscriptions'])
        <li class="nav-small-cap">
          <i class="ti ti-dots nav-small-cap-icon fs-4" aria-hidden="true"></i>
          <span class="hide-menu">Marketing & Konten</span>
        </li>
        @can('manage-blog')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.blog.*') ? 'active' : '' }}" href="{{ route('admin.blog.index') }}" aria-expanded="false">
            <span>
              <i class="ti ti-news" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Blog</span>
          </a>
        </li>
        @endcan
        @can('manage-advertisements')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.advertisements.*') ? 'active' : '' }}" href="{{ route('admin.advertisements.index') }}" aria-expanded="false">
            <span>
              <i class="ti ti-ad" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Iklan Banner</span>
          </a>
        </li>
        @endcan
        @can('manage-subscriptions')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}" href="{{ route('admin.subscriptions.index') }}" aria-expanded="false">
            <span>
              <i class="ti ti-award" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Langganan</span>
          </a>
        </li>
        @endcan
        @endcanany

        @canany(['manage-disputes', 'manage-fees', 'manage-settings', 'manage-whatsapp'])
        <li class="nav-small-cap">
          <i class="ti ti-dots nav-small-cap-icon fs-4" aria-hidden="true"></i>
          <span class="hide-menu">Sistem & SLA</span>
        </li>
        @can('manage-disputes')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.disputes.*') ? 'active' : '' }}" href="{{ route('admin.disputes.index') }}" aria-expanded="false">
            <span>
              <i class="ti ti-circle-x" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Laporan Masalah</span>
          </a>
        </li>
        @endcan
        @can('manage-fees')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.fees.*') ? 'active' : '' }}" href="{{ route('admin.fees.index') }}" aria-expanded="false">
            <span>
              <i class="ti ti-coins" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Biaya Layanan</span>
          </a>
        </li>
        @endcan
        @can('manage-settings')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}" href="{{ route('admin.settings') }}" aria-expanded="false">
            <span>
              <i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">Pengaturan</span>
          </a>
        </li>
        @endcan
        @can('manage-whatsapp')
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('admin.whatsapp.*') ? 'active' : '' }}" href="{{ route('admin.whatsapp.index') }}" aria-expanded="false">
            <span>
              <i class="ti ti-brand-whatsapp" aria-hidden="true"></i>
            </span>
            <span class="hide-menu">WhatsApp Gateway</span>
          </a>
        </li>
        @endcan
        @endcanany
      </ul>
    </nav>

    <div class="sidebar-ad hide-menu mx-3 mb-3 px-3 py-2 bg-light-primary rounded">
      <span class="d-flex flex-wrap align-items-center gap-2 fs-2">
        <i class="ti ti-map text-primary" aria-hidden="true"></i>
        <span class="fw-semibold text-primary">{{ config('seekitar.regency') }}</span>
        <span class="text-muted">· BPS {{ config('seekitar.regency_code') }}</span>
      </span>
    </div>
  </div>
</aside>
