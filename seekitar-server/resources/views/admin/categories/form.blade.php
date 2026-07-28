@extends('admin.layout')
@section('title', $category->exists ? 'Sunting Kategori' : 'Kategori Baru')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Kategori</a></li>
    <li class="breadcrumb-item active" aria-current="page">
        {{ $category->exists ? 'Sunting' : 'Baru' }}
    </li>
@endsection

@section('content')
    <h1 class="h4 mb-3">{{ $category->exists ? 'Sunting Kategori' : 'Kategori Baru' }}</h1>

    <div class="card"><div class="card-body">
        <form method="POST"
              action="{{ $category->exists
                  ? route('admin.categories.update', $category)
                  : route('admin.categories.store') }}">
            @csrf
            @if ($category->exists)
                @method('PUT')
            @endif

            <div class="mb-3">
                <label for="name" class="form-label">Nama</label>
                <input type="text" id="name" name="name" maxlength="50" required
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $category->name) }}">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="slug" class="form-label">Slug</label>
                <input type="text" id="slug" name="slug" maxlength="50" required
                       pattern="[a-z0-9\-]+"
                       class="form-control @error('slug') is-invalid @enderror"
                       value="{{ old('slug', $category->slug) }}">
                <div class="form-text">Huruf kecil, angka, dan tanda hubung saja.</div>
                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="parent_id" class="form-label">Induk</label>
                <select id="parent_id" name="parent_id" class="form-select js-select2">
                    <option value="">— Kategori induk —</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}"
                            @selected(old('parent_id', $category->parent_id) == $parent->id)>
                            {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Kosongkan untuk membuat kategori induk (maksimal dua level).</div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="icon" class="form-label">Ikon</label>
                    <input type="text" id="icon" name="icon" maxlength="50" class="form-control"
                           value="{{ old('icon', $category->icon) }}" placeholder="shopping-bag">
                    <div class="form-text">Nama ikon Heroicons v2 outline.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="sort_order" class="form-label">Urutan</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" class="form-control"
                           value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                </div>
            </div>

            <button type="submit" class="btn btn-seekitar">Simpan</button>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-link">Batal</a>
        </form>
    </div></div>
@endsection
