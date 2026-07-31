@extends('admin.layouts.admin')

@section('title', 'Edit Iklan — Seekitar')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title mb-1">Edit Iklan</h4>
          <p class="card-subtitle mb-4">Perbarui informasi iklan banner.</p>
          
          <form action="{{ route('admin.advertisements.update', $ad) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
              <!-- Left Column (General Info) -->
              <div class="col-lg-8">
                <div class="mb-3">
                  <label for="title" class="form-label">Judul Iklan</label>
                  <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $ad->title) }}" required>
                  @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="mb-3">
                  <label for="description" class="form-label">Deskripsi Iklan (Opsional)</label>
                  <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $ad->description) }}</textarea>
                  @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="mb-3">
                  <label for="image_url" class="form-label">URL Gambar Banner (Opsional)</label>
                  <input type="url" class="form-control @error('image_url') is-invalid @enderror" id="image_url" name="image_url" value="{{ old('image_url', $ad->image_url) }}" placeholder="https://domain.com/images/banner.jpg">
                  @error('image_url')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="mb-3">
                  <label for="link_url" class="form-label">Link Tujuan / URL Redirect (Opsional)</label>
                  <input type="url" class="form-control @error('link_url') is-invalid @enderror" id="link_url" name="link_url" value="{{ old('link_url', $ad->link_url) }}" placeholder="https://domain.com/toko/maju-jaya">
                  @error('link_url')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
              
              <!-- Right Column (Settings) -->
              <div class="col-lg-4">
                <div class="card bg-light border-0 shadow-none mb-3">
                  <div class="card-body p-4">
                    <h5 class="fw-semibold mb-3">Posisi & Harga</h5>
                    
                    <div class="mb-3">
                      <label for="position" class="form-label">Posisi Penempatan</label>
                      <select class="form-select @error('position') is-invalid @enderror" id="position" name="position" required>
                        <option value="feed" {{ old('position', $ad->position) == 'feed' ? 'selected' : '' }}>Feed Utama</option>
                        <option value="sidebar" {{ old('position', $ad->position) == 'sidebar' ? 'selected' : '' }}>Sidebar</option>
                        <option value="search" {{ old('position', $ad->position) == 'search' ? 'selected' : '' }}>Halaman Pencarian</option>
                        <option value="category" {{ old('position', $ad->position) == 'category' ? 'selected' : '' }}>Halaman Kategori</option>
                      </select>
                      @error('position')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                    
                    <div class="mb-3">
                      <label for="price_per_day" class="form-label">Harga Per Hari (Rp)</label>
                      <input type="number" class="form-control @error('price_per_day') is-invalid @enderror" id="price_per_day" name="price_per_day" value="{{ old('price_per_day', (int)$ad->price_per_day) }}" min="0" required>
                      @error('price_per_day')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                    
                    <div class="mb-3">
                      <label for="status" class="form-label">Status</label>
                      <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                        <option value="available" {{ old('status', $ad->status) == 'available' ? 'selected' : '' }}>Tersedia (Available)</option>
                        <option value="active" {{ old('status', $ad->status) == 'active' ? 'selected' : '' }}>Aktif (Active)</option>
                        <option value="inactive" {{ old('status', $ad->status) == 'inactive' ? 'selected' : '' }}>Nonaktif (Inactive)</option>
                      </select>
                      @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="mt-4 border-top pt-3">
              <button type="submit" class="btn btn-primary px-5 btn-hover-shadow">Simpan Perubahan</button>
              <a href="{{ route('admin.advertisements.index') }}" class="btn btn-outline-secondary px-4 ms-2">Batal</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
