@extends('admin.layouts.admin')

@section('title', $category->exists ? 'Edit Kategori — Seekitar' : 'Tambah Kategori — Seekitar')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title mb-1">{{ $category->exists ? 'Edit Kategori' : 'Tambah Kategori Baru' }}</h4>
          <p class="card-subtitle mb-4">Buat atau perbarui kategori/subkategori produk, jasa, atau rental.</p>
          
          <form action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" method="POST">
            @csrf
            @if ($category->exists)
              @method('PUT')
            @endif
            
            <div class="row">
              <!-- Left Column (General Info) -->
              <div class="col-lg-8">
                <div class="mb-3">
                  <label for="name" class="form-label">Nama Kategori</label>
                  <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $category->name) }}" required max="50">
                  @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="mb-3">
                  <label for="slug" class="form-label">Slug URL</label>
                  <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="Contoh: kuliner-lokal" required max="50">
                  <div class="form-text">Hanya boleh berisi huruf kecil, angka, dan tanda hubung (-). Format: <code>kategori-barang</code>.</div>
                  @error('slug')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="mb-3">
                  <label for="parent_id" class="form-label">Kategori Induk (Parent Category)</label>
                  <select class="form-select js-select2 @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                    <option value="">Tidak ada (Jadikan Kategori Utama)</option>
                    @foreach ($parents as $parent)
                      <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>
                        {{ $parent->name }}
                      </option>
                    @endforeach
                  </select>
                  <div class="form-text">Pilih kategori induk jika ingin menjadikannya Subkategori.</div>
                  @error('parent_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
              
              <!-- Right Column (Settings) -->
              <div class="col-lg-4">
                <div class="card bg-light border-0 shadow-none">
                  <div class="card-body p-4">
                    <h5 class="fw-semibold mb-3">Tampilan & Urutan</h5>
                    
                    <div class="mb-3">
                      <label for="icon" class="form-label">Nama Ikon (Tabler / Heroicons)</label>
                      <input type="text" class="form-control @error('icon') is-invalid @enderror" id="icon" name="icon" value="{{ old('icon', $category->icon) }}" placeholder="Contoh: shopping-bag" max="50">
                      <div class="form-text">Nama ikon untuk frontend (misal: <code>shopping-bag</code>, <code>home-modern</code>).</div>
                      @error('icon')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                    
                    <div class="mb-3">
                      <label for="sort_order" class="form-label">Urutan Tampilan</label>
                      <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0">
                      <div class="form-text">Angka urutan terkecil akan tampil paling pertama.</div>
                      @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="mt-4 border-top pt-3">
              <button type="submit" class="btn btn-primary px-5 btn-hover-shadow">{{ $category->exists ? 'Perbarui Kategori' : 'Simpan Kategori' }}</button>
              <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary px-4 ms-2">Batal</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
