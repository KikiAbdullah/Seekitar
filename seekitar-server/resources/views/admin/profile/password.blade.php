@extends('admin.layouts.admin')

@section('title', 'Ubah Kata Sandi — Seekitar')

@section('content')
  <div class="row">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title mb-1">Ubah Kata Sandi</h4>
          <p class="card-subtitle mb-4">Demi keamanan akun, pastikan kata sandi baru minimal 12 karakter, mengandung huruf besar, huruf kecil, dan angka.</p>
          
          <form action="{{ route('admin.password.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-3">
              <label for="current_password" class="form-label">Kata Sandi Saat Ini</label>
              <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required>
              @error('current_password')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="mb-3">
              <label for="password" class="form-label">Kata Sandi Baru</label>
              <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
              <div class="form-text text-muted">Minimal 12 karakter dengan kombinasi huruf besar, kecil, dan angka.</div>
              @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="mb-4">
              <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi Baru</label>
              <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
            </div>
            
            <button type="submit" class="btn btn-primary px-4 btn-hover-shadow">Simpan Perubahan</button>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
