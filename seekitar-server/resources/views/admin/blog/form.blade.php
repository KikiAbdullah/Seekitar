@extends('admin.layouts.admin')

@section('title', $post->exists ? 'Edit Artikel — Seekitar' : 'Tambah Artikel — Seekitar')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title mb-1">{{ $post->exists ? 'Edit Artikel' : 'Tambah Artikel Baru' }}</h4>
          <p class="card-subtitle mb-4">Buat atau perbarui artikel untuk blog publik Seekitar.</p>
          
          <form action="{{ $post->exists ? route('admin.blog.update', $post) : route('admin.blog.store') }}" method="POST">
            @csrf
            @if ($post->exists)
              @method('PUT')
            @endif
            
            <div class="row">
              <!-- Left Column (General Info) -->
              <div class="col-lg-8">
                <div class="mb-3">
                  <label for="title" class="form-label">Judul Artikel</label>
                  <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $post->title) }}" required>
                  @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="mb-3">
                  <label for="slug" class="form-label">Slug URL</label>
                  <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $post->slug) }}" placeholder="Ketik judul untuk generate slug otomatis">
                  <div class="form-text">Biarkan kosong untuk generate otomatis dari judul. Format: <code>judul-artikel-anda</code>.</div>
                  @error('slug')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="mb-3">
                  <label for="excerpt" class="form-label">Ringkasan Singkat (Excerpt)</label>
                  <textarea class="form-control @error('excerpt') is-invalid @enderror" id="excerpt" name="excerpt" rows="3">{{ old('excerpt', $post->excerpt) }}</textarea>
                  <div class="form-text">Maksimal 500 karakter. Teks ini tampil di daftar blog halaman depan.</div>
                  @error('excerpt')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="mb-3">
                  <label for="body" class="form-label">Konten Utama</label>
                  <textarea class="form-control @error('body') is-invalid @enderror" id="body" name="body" rows="15" required>{{ old('body', $post->body) }}</textarea>
                  @error('body')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
              
              <!-- Right Column (Sidebar Settings) -->
              <div class="col-lg-4">
                <div class="card bg-light border-0 shadow-none mb-3">
                  <div class="card-body p-4">
                    <h5 class="fw-semibold mb-3">Metadata & Publikasi</h5>
                    
                    <div class="mb-3">
                      <label for="category" class="form-label">Kategori</label>
                      <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category', $post->category ?? 'Edukasi') }}" required>
                      @error('category')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                    
                    <div class="mb-3">
                      <label for="author" class="form-label">Penulis</label>
                      <input type="text" class="form-control @error('author') is-invalid @enderror" id="author" name="author" value="{{ old('author', $post->author ?? 'Tim Seekitar') }}">
                      @error('author')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                    
                    <div class="mb-3">
                      <label for="published_at" class="form-label">Tanggal Terbit</label>
                      <input type="datetime-local" class="form-control @error('published_at') is-invalid @enderror" id="published_at" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                      <div class="form-text">Kosongkan jika ingin menyimpannya sebagai Draf. Isi tanggal mendatang untuk menjadwalkan.</div>
                      @error('published_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                </div>
                
                <div class="card bg-light border-0 shadow-none">
                  <div class="card-body p-4">
                    <h5 class="fw-semibold mb-3">Gambar Cover</h5>
                    
                    <div class="mb-3">
                      <label for="image" class="form-label">URL Gambar</label>
                      <input type="url" class="form-control @error('image') is-invalid @enderror" id="image" name="image" value="{{ old('image', $post->image) }}" placeholder="https://domain.com/path/to/image.jpg">
                      @error('image')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                    
                    <div class="mb-3">
                      <label for="image_alt" class="form-label">Deskripsi Gambar (Alt Text)</label>
                      <input type="text" class="form-control @error('image_alt') is-invalid @enderror" id="image_alt" name="image_alt" value="{{ old('image_alt', $post->image_alt) }}" placeholder="Deskripsi untuk keterbacaan screen reader">
                      @error('image_alt')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="mt-4 border-top pt-3">
              <button type="submit" class="btn btn-primary px-5 btn-hover-shadow">{{ $post->exists ? 'Perbarui Artikel' : 'Terbitkan Artikel' }}</button>
              <a href="{{ route('admin.blog.index') }}" class="btn btn-outline-secondary px-4 ms-2">Batal</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
