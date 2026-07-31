@extends('admin.layouts.admin')

@section('title', 'Manajemen Kategori — Seekitar')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card w-100 shadow-sm">
        <div class="card-body">
          <div class="d-md-flex align-items-center justify-content-between mb-4">
            <div>
              <h4 class="card-title">Kategori & Subkategori</h4>
              <p class="card-subtitle">Kelola taksonomi kategori barang, jasa, dan sewa di platform Seekitar.</p>
            </div>
            <div>
              @can('manage-categories')
                <a href="{{ route('admin.categories.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
                  <i class="ti ti-plus fs-4"></i> Tambah Kategori
                </a>
              @endcan
            </div>
          </div>

          @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              {{ session('error') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          <div class="list-group list-group-flush border rounded-3 overflow-hidden mt-3">
            @forelse ($categories as $parent)
              <div class="list-group-item bg-light-subtle p-3 border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-3">
                    <span class="rounded bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                      <i class="{{ method_exists($parent, 'tablerIcon') ? $parent->tablerIcon() : 'ti ti-tag' }} fs-5"></i>
                    </span>
                    <div>
                      <h6 class="mb-0 fw-bold fs-4 text-dark">{{ $parent->name }}</h6>
                      <span class="fs-2 text-muted">Slug: <code>{{ $parent->slug }}</code> | Urutan: {{ $parent->sort_order ?? 0 }}</span>
                    </div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.categories.edit', $parent) }}" class="btn btn-sm btn-light-primary text-primary" title="Edit">
                      <i class="ti ti-edit fs-4"></i> Edit
                    </a>
                    <form action="{{ route('admin.categories.destroy', $parent) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');" style="display:inline-block;">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-light-danger text-danger" title="Hapus">
                        <i class="ti ti-trash fs-4"></i> Hapus
                      </button>
                    </form>
                  </div>
                </div>
                
                <!-- Subcategories List -->
                @if ($parent->children->isNotEmpty())
                  <div class="ps-5 mt-3 border-start ms-4">
                    <div class="list-group list-group-flush rounded-3 border">
                      @foreach ($parent->children as $child)
                        <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                          <div class="d-flex align-items-center gap-3">
                            <span class="rounded bg-secondary-subtle text-secondary p-1 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                              <i class="ti ti-tag fs-4"></i>
                            </span>
                            <div>
                              <h6 class="mb-0 fw-semibold fs-3">{{ $child->name }}</h6>
                              <span class="fs-2 text-muted">Slug: <code>{{ $child->slug }}</code> | Urutan: {{ $child->sort_order ?? 0 }}</span>
                            </div>
                          </div>
                          <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('admin.categories.edit', $child) }}" class="btn btn-sm btn-light-primary text-primary" title="Edit">
                              <i class="ti ti-edit fs-3"></i> Edit
                            </a>
                            <form action="{{ route('admin.categories.destroy', $child) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus subkategori ini?');" style="display:inline-block;">
                              @csrf
                              @method('DELETE')
                              <button type="submit" class="btn btn-sm btn-light-danger text-danger" title="Hapus">
                                <i class="ti ti-trash fs-3"></i> Hapus
                              </button>
                            </form>
                          </div>
                        </div>
                      @endforeach
                    </div>
                  </div>
                @endif
              </div>
            @empty
              <div class="p-5 text-center">
                <i class="ti ti-tag text-muted fs-9 mb-3 d-block"></i>
                <p class="mb-0 text-muted fs-4">Belum ada kategori yang dibuat.</p>
              </div>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
