@extends('admin.layout')
@section('title', 'Iklan Baru')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">Iklan Baru</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.advertisements.index') }}">Iklan</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Baru</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.advertisements.store') }}">
                @csrf

                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Judul</label>
                    <div class="col-sm-9">
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" required maxlength="200">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Deskripsi</label>
                    <div class="col-sm-9">
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                  rows="3" maxlength="2000">{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">URL Gambar</label>
                    <div class="col-sm-9">
                        <input type="url" name="image_url" class="form-control @error('image_url') is-invalid @enderror"
                               value="{{ old('image_url') }}" maxlength="500" placeholder="https://...">
                        @error('image_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">URL Tautan</label>
                    <div class="col-sm-9">
                        <input type="url" name="link_url" class="form-control @error('link_url') is-invalid @enderror"
                               value="{{ old('link_url') }}" maxlength="500" placeholder="https://...">
                        @error('link_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Posisi</label>
                    <div class="col-sm-9">
                        <select name="position" class="form-select js-select2 @error('position') is-invalid @enderror"
                                data-min-search="20">
                            <option value="feed" @selected(old('position') === 'feed')>Feed Beranda</option>
                            <option value="sidebar" @selected(old('position') === 'sidebar')>Sidebar</option>
                            <option value="search" @selected(old('position') === 'search')>Hasil Pencarian</option>
                            <option value="category" @selected(old('position') === 'category')>Halaman Kategori</option>
                        </select>
                        @error('position') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Harga per Hari (Rp)</label>
                    <div class="col-sm-9">
                        <input type="number" name="price_per_day" class="form-control @error('price_per_day') is-invalid @enderror"
                               value="{{ old('price_per_day', 50000) }}" min="0" step="1000">
                        @error('price_per_day') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Status</label>
                    <div class="col-sm-9">
                        <select name="status" class="form-select js-select2 @error('status') is-invalid @enderror"
                                data-min-search="20">
                            <option value="available" @selected(old('status') === 'available')>Tersedia</option>
                            <option value="active" @selected(old('status') === 'active')>Aktif</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Nonaktif</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-9 offset-sm-3">
                        <button type="submit" class="btn btn-seekitar">Simpan</button>
                        <a href="{{ route('admin.advertisements.index') }}" class="btn btn-outline-secondary ms-2">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
