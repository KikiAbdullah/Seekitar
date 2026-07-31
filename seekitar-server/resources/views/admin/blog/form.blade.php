@extends('admin.layout')
@section('title', $post->exists ? 'Sunting Artikel' : 'Artikel Baru')

@push('styles')
<style>
    .blog-preview-img { max-height: 200px; object-fit: cover; border-radius: 6px; }
</style>
@endpush

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">{{ $post->exists ? 'Sunting Artikel' : 'Artikel Baru' }}</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.blog.index') }}">Blog</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                {{ $post->exists ? 'Sunting' : 'Baru' }}
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST"
                  action="{{ $post->exists ? route('admin.blog.update', $post) : route('admin.blog.store') }}">
                @csrf
                @if ($post->exists)
                    @method('PUT')
                @endif

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">Judul</label>
                    <div class="col-sm-10">
                        <input type="text" name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $post->title) }}" required maxlength="200"
                               id="judul-artikel">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">Slug</label>
                    <div class="col-sm-10">
                        <div class="input-group">
                            <input type="text" name="slug"
                                   class="form-control @error('slug') is-invalid @enderror"
                                   value="{{ old('slug', $post->slug) }}" maxlength="220"
                                   placeholder="Otomatis dari judul" id="slug-artikel">
                            <button type="button" class="btn btn-outline-secondary" id="isi-slug"
                                    title="Isi slug dari judul">
                                <i class="fa-regular fa-arrow-alt-circle-right" aria-hidden="true"></i>
                            </button>
                        </div>
                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">Kategori</label>
                    <div class="col-sm-4">
                        <select name="category" class="form-select js-select2 @error('category') is-invalid @enderror"
                                data-min-search="20">
                            <option value="Edukasi" @selected(old('category', $post->category) === 'Edukasi')>Edukasi</option>
                            <option value="Panduan" @selected(old('category', $post->category) === 'Panduan')>Panduan</option>
                            <option value="Keamanan" @selected(old('category', $post->category) === 'Keamanan')>Keamanan</option>
                            <option value="Berita" @selected(old('category', $post->category) === 'Berita')>Berita</option>
                            <option value="Cerita" @selected(old('category', $post->category) === 'Cerita')>Cerita</option>
                        </select>
                        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <label class="col-sm-2 col-form-label">Penulis</label>
                    <div class="col-sm-4">
                        <input type="text" name="author"
                               class="form-control @error('author') is-invalid @enderror"
                               value="{{ old('author', $post->author ?? auth()->user()?->name ?? 'Tim Seekitar') }}"
                               maxlength="100">
                        @error('author') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">Gambar</label>
                    <div class="col-sm-6">
                        <input type="url" name="image"
                               class="form-control @error('image') is-invalid @enderror"
                               value="{{ old('image', $post->image) }}" maxlength="500"
                               placeholder="https://... (opsional)" id="url-gambar">
                        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-sm-4">
                        <input type="text" name="image_alt"
                               class="form-control @error('image_alt') is-invalid @enderror"
                               value="{{ old('image_alt', $post->image_alt) }}" maxlength="200"
                               placeholder="Teks alternatif gambar">
                        @error('image_alt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                @php $gambar = old('image', $post->image); @endphp
                @if ($gambar)
                    <div class="row mb-3">
                        <div class="col-sm-10 offset-sm-2">
                            <img src="{{ $gambar }}" alt="Pratinjau gambar" class="blog-preview-img"
                                 onerror="this.style.display='none'">
                        </div>
                    </div>
                @endif

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">Ringkasan</label>
                    <div class="col-sm-10">
                        <textarea name="excerpt" class="form-control @error('excerpt') is-invalid @enderror"
                                  rows="2" maxlength="500"
                                  placeholder="Opsional — jika kosong, diambil dari awal isi artikel.">{{ old('excerpt', $post->excerpt) }}</textarea>
                        @error('excerpt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">Isi Artikel</label>
                    <div class="col-sm-10">
                        <textarea name="body" class="form-control @error('body') is-invalid @enderror"
                                  rows="16" required>{{ old('body', $post->body) }}</textarea>
                        <div class="form-text">
                            HTML diperbolehkan. Gunakan tag &lt;p&gt;, &lt;h2&gt;–&lt;h3&gt;,
                            &lt;ol&gt;/&lt;ul&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;a&gt;.
                        </div>
                        @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">Terbit</label>
                    <div class="col-sm-4">
                        <input type="datetime-local" name="published_at"
                               class="form-control @error('published_at') is-invalid @enderror"
                               value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                        <div class="form-text">
                            Kosongkan untuk draf. Isi tanggal &amp; jam di masa depan untuk menjadwalkan.
                        </div>
                        @error('published_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-10 offset-sm-2">
                        <button type="submit" class="btn btn-seekitar">
                            {{ $post->exists ? 'Simpan Perubahan' : 'Buat Artikel' }}
                        </button>
                        <a href="{{ route('admin.blog.index') }}" class="btn btn-outline-secondary ms-2">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const judul = document.getElementById('judul-artikel');
    const slug  = document.getElementById('slug-artikel');
    const tombol = document.getElementById('isi-slug');

    if (judul && slug && tombol) {
        tombol.addEventListener('click', function () {
            if (!slug.value) {
                isiSlug();
                return;
            }
            Swal.fire({
                title: 'Timpa slug?',
                text: 'Slug sudah diisi. Timpa dengan slug dari judul?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, timpa',
                cancelButtonText: 'Batal',
            }).then(function (result) {
                if (result.isConfirmed) isiSlug();
            });
        });

        function isiSlug() {
            slug.value = judul.value
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
        }
    }
})();
</script>
@endpush
