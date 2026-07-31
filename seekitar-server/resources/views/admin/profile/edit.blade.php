@extends('admin.layouts.admin')

@section('title', 'Profil Saya — Seekitar')

@section('content')
  <div class="row">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title mb-1">Profil Saya</h4>
          <p class="card-subtitle mb-4">Perbarui informasi nama dan email login Anda. Mengganti email akan mencatat log aktivitas keamanan.</p>
          
          <form action="{{ route('admin.profile.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-3">
              <label for="name" class="form-label">Nama Lengkap</label>
              <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="mb-3">
              <label for="email" class="form-label">Alamat Email</label>
              <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
              @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="mb-4">
              <label for="phone" class="form-label">Nomor WhatsApp (Aplikasi)</label>
              <input type="text" class="form-control-plaintext" id="phone" value="+62 {{ ltrim($user->phone, '62') }}" readonly>
              <div class="form-text text-muted">Nomor WhatsApp dikelola melalui akun pengguna dan hanya dapat diubah dari aplikasi mobile.</div>
            </div>
            
            <button type="submit" class="btn btn-primary px-4 btn-hover-shadow">Simpan Profil</button>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
