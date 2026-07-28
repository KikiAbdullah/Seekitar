@extends('admin.layout')
@section('title', 'Profil Saya')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">Profil Saya</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                        <li class="breadcrumb-item active" aria-current="page">Profil Saya</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">

        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header fw-semibold">Data Akun</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Nama</label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}" required maxlength="100">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}" required maxlength="255"
                                   autocomplete="username">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Email ini juga menjadi nama pengguna saat masuk panel.
                                Perubahannya dicatat di log keamanan.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Nomor Telepon</label>
                            <input type="text" id="phone" class="form-control"
                                   value="{{ $user->phone }}" readonly>
                            <div class="form-text">
                                Nomor adalah identitas akun dan hanya bisa diubah lewat verifikasi OTP.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-seekitar">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card mb-3">
                <div class="card-header fw-semibold">Peran &amp; Izin</div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Peran</div>
                        @forelse ($user->getRoleNames() as $role)
                            <span class="badge text-bg-success">{{ $role }}</span>
                        @empty
                            <span class="text-muted">Tidak ada peran.</span>
                        @endforelse
                    </div>

                    <div>
                        <div class="text-muted small mb-1">
                            Izin aktif ({{ $user->getAllPermissions()->count() }})
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            @forelse ($user->getAllPermissions()->pluck('name')->sort() as $izin)
                                <span class="badge text-bg-light border">{{ $izin }}</span>
                            @empty
                                <span class="text-muted">Belum ada izin.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header fw-semibold">Keamanan</div>
                <div class="card-body">
                    <p class="small text-muted">
                        Kata sandi diubah di halaman terpisah agar tidak ikut tersimpan
                        saat Anda hanya memperbaiki nama.
                    </p>
                    <a href="{{ route('admin.password.edit') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ti ti-key me-1" aria-hidden="true"></i> Ubah Kata Sandi
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
