@extends('admin.layout')
@section('title', 'Ubah Kata Sandi')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">Ubah Kata Sandi</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="{{ route('admin.profile.edit') }}">Profil Saya</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Ubah Kata Sandi</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header fw-semibold">Ubah Kata Sandi</div>
                <div class="card-body">

                    <div class="alert alert-warning py-2 small" role="alert">
                        <i class="fa-regular fa-circle-xmark me-1" aria-hidden="true"></i>
                        Setelah berhasil, <strong>semua sesi di perangkat lain dikeluarkan</strong>
                        dan token aplikasi dicabut. Sesi di perangkat ini tetap berjalan.
                    </div>

                    <form method="POST" action="{{ route('admin.password.update') }}">
                        @csrf
                        @method('PUT')

                        <input type="hidden" name="email" autocomplete="username"
                               value="{{ auth()->user()?->email }}">

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Kata Sandi Saat Ini</label>
                            <input type="password" id="current_password" name="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   required autocomplete="current-password">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Wajib diisi meski Anda sudah masuk — mencegah pengambilalihan
                                akun dari perangkat yang tertinggal dalam keadaan terbuka.
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label for="password" class="form-label">Kata Sandi Baru</label>
                            <input type="password" id="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   required autocomplete="new-password" minlength="12">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Ulangi Kata Sandi Baru</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   class="form-control" required autocomplete="new-password" minlength="12">
                        </div>

                        <button type="submit" class="btn btn-seekitar">Simpan Kata Sandi</button>
                        <a href="{{ route('admin.profile.edit') }}" class="btn btn-link">Batal</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header fw-semibold">Syarat Kata Sandi</div>
                <div class="card-body">
                    <ul class="mb-0 ps-3 small">
                        <li>Minimal <strong>12 karakter</strong> — lebih ketat dari
                            bawaan Laravel (8), karena akun ini memegang data
                            pribadi seluruh pengguna (UU PDP).</li>
                        <li>Memuat huruf besar dan huruf kecil.</li>
                        <li>Memuat minimal satu angka.</li>
                        <li>Berbeda dari kata sandi saat ini.</li>
                        <li>Bukan kata umum seperti <code>password</code>,
                            <code>admin</code>, atau <code>seekitar2026</code>.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
