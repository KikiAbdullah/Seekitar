<header class="app-header">
  <nav class="navbar navbar-expand-lg navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link sidebartoggler nav-icon-hover ms-n3" id="headerCollapse" href="javascript:void(0)">
          <i class="ti ti-menu-2" aria-hidden="true"></i>
        </a>
      </li>
    </ul>
    
    <div class="d-block d-lg-none">
      <img src="{{ asset('img/brand/logo-lockup.png') }}" class="dark-logo" width="140" alt="Seekitar" />
    </div>
    
    <button class="navbar-toggler p-0 border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="p-2">
        <i class="ti ti-dots fs-7" aria-hidden="true"></i>
      </span>
    </button>
    
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <div class="d-flex align-items-center justify-content-between">
        <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-end">
          <li class="nav-item dropdown">
            <a class="nav-link nav-icon-hover" href="javascript:void(0)" id="drop2" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; font-weight: 700;">
                {{ Auth::user() ? Auth::user()->initials : 'A' }}
              </span>
            </a>
            <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop2">
              <div class="message-body">
                <a href="{{ route('admin.profile.edit') }}" class="d-flex align-items-center gap-2 dropdown-item">
                  <i class="ti ti-user fs-6" aria-hidden="true"></i>
                  <p class="mb-0 fs-3">Profil Saya</p>
                </a>
                <a href="{{ route('admin.password.edit') }}" class="d-flex align-items-center gap-2 dropdown-item">
                  <i class="ti ti-key fs-6" aria-hidden="true"></i>
                  <p class="mb-0 fs-3">Ubah Kata Sandi</p>
                </a>
                <form action="{{ route('admin.logout') }}" method="POST" class="px-4 mt-3 d-block">
                  @csrf
                  <button type="submit" class="btn btn-outline-primary w-100">Log Out</button>
                </form>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</header>
