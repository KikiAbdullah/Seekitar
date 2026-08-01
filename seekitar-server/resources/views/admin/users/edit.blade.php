@extends('admin.layouts.admin')

@section('title', 'Edit Profil Pengguna — Seekitar')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-md-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
            <div>
              <h4 class="card-title">Edit Profil Pengguna</h4>
              <p class="card-subtitle">Sunting informasi dasar, domisili, NIK, dan koordinat pengguna.</p>
            </div>
            <div>
              <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali ke Detail
              </a>
            </div>
          </div>
          
          <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="row">
              <!-- Left Column: Basic Info & Domisili -->
              <div class="col-lg-8">
                <h5 class="fw-semibold text-dark mb-3">Informasi Dasar</h5>
                
                <div class="mb-3">
                  <label for="name" class="form-label">Nama Lengkap</label>
                  <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                  @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="mb-3">
                  <label for="email" class="form-label">Alamat Email (Opsional)</label>
                  <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}">
                  @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="mb-3">
                  <label for="phone" class="form-label">Nomor WhatsApp (Tidak dapat diubah)</label>
                  <input type="text" class="form-control" id="phone" value="{{ $user->phone }}" readonly>
                  <div class="form-text">Nomor HP/WhatsApp adalah kredensial login (OTP) pembeli/penjual dan hanya bisa diubah via aplikasi.</div>
                </div>
                
                <hr class="my-4 text-muted opacity-25">
                
                <h5 class="fw-semibold text-dark mb-3">Domisili & Lokasi Peta</h5>
                
                <div class="mb-3">
                  <label for="address" class="form-label">Alamat Domisili (Opsional)</label>
                  <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $user->address) }}">
                  @error('address')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="latitude" class="form-label">Lintang (Latitude)</label>
                    <input type="text" class="form-control @error('latitude') is-invalid @enderror" id="latitude" name="latitude" value="{{ old('latitude', $user->latitude) }}" placeholder="Contoh: -7.5912">
                    @error('latitude')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                  <div class="col-md-6">
                    <label for="longitude" class="form-label">Bujur (Longitude)</label>
                    <input type="text" class="form-control @error('longitude') is-invalid @enderror" id="longitude" name="longitude" value="{{ old('longitude', $user->longitude) }}" placeholder="Contoh: 112.7843">
                    @error('longitude')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                  <div class="col-12">
                    <div class="form-text">Koordinat lintang dan bujur harus diisi lengkap berpasangan, atau dikosongkan keduanya. Koordinat ini digunakan untuk verifikasi domisili hyperlocal.</div>
                  </div>
                </div>
              </div>
              
              <!-- Right Column: Avatar & Verification Files -->
              <div class="col-lg-4">
                <div class="card bg-light border-0 shadow-none mb-3">
                  <div class="card-body p-4">
                    <h5 class="fw-semibold mb-3">Foto Profil (Avatar)</h5>
                    <div class="mb-3 text-center">
                      <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold fs-7 mb-3" style="width: 80px; height: 80px;">
                        {{ $user->initials }}
                      </span>
                    </div>
                    <div class="mb-3">
                      <label for="avatar" class="form-label">Unggah Foto Baru</label>
                      <input type="file" class="form-control @error('avatar') is-invalid @enderror" id="avatar" name="avatar" accept="image/jpeg,image/png">
                      <div class="form-text">Maksimal 2 MB (JPEG, PNG).</div>
                      @error('avatar')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                </div>
                
                @can('verify-users')
                  <div class="card bg-light border-0 shadow-none">
                    <div class="card-body p-4">
                      <h5 class="fw-semibold mb-3">Dokumen Identitas</h5>
                      <div class="mb-3">
                        <label for="nik" class="form-label">Nomor NIK KTP</label>
                        <input type="text" class="form-control @error('nik') is-invalid @enderror" id="nik" name="nik" value="{{ old('nik', $user->nik) }}" maxlength="16">
                        <div class="form-text">Tepat 16 digit angka. NIK disimpan terenkripsi di database.</div>
                        @error('nik')
                          <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                      </div>
                      
                      <div class="mb-3">
                        <label for="ktp_image" class="form-label">Ganti Foto KTP</label>
                        <input type="file" class="form-control @error('ktp_image') is-invalid @enderror" id="ktp_image" name="ktp_image" accept="image/jpeg,image/png">
                        <div class="form-text">Maksimal 5 MB (JPEG, PNG).</div>
                        @error('ktp_image')
                          <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                      </div>
                      
                      <div class="mb-3">
                        <label for="selfie_image" class="form-label">Ganti Foto Wajah (Selfie)</label>
                        <input type="file" class="form-control @error('selfie_image') is-invalid @enderror" id="selfie_image" name="selfie_image" accept="image/jpeg,image/png">
                        <div class="form-text">Maksimal 5 MB (JPEG, PNG).</div>
                        @error('selfie_image')
                          <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                      </div>
                    </div>
                  </div>
                @endcan
              </div>
            </div>
            
            <div class="mt-4 border-top pt-3">
              <button type="submit" class="btn btn-primary px-5 btn-hover-shadow">Simpan Perubahan</button>
              <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-secondary px-4 ms-2">Batal</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
