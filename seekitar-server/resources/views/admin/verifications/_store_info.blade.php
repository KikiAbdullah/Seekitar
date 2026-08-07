<div class="d-flex align-items-center gap-3">
  @php
    // accessor `photo` sudah mengembalikan URL penuh — memakainya di dalam
    // asset('storage/') membuat URL dobel. Konteks verifikasi wajib memakai
    // nilai MENTAH (Store.php) + PlaceholderImg::src() yang host-relatif.
    $infoPhotoUrl = \App\Support\PlaceholderImg::src($store->getRawOriginal('photo'));
  @endphp
  @if ($infoPhotoUrl)
    <img src="{{ $infoPhotoUrl }}" class="rounded border shadow-sm" width="45" height="45" style="object-fit: cover;" alt="Foto {{ $store->name }}">
  @else
    <img src="https://placehold.co/100x100?text=Toko" class="rounded border shadow-sm" width="45" height="45" style="object-fit: cover;" alt="Foto {{ $store->name }}">
  @endif
  <div>
    <h6 class="fw-bold mb-0 text-dark">{{ $store->name }}</h6>
    <span class="fs-2 text-muted">ID: {{ $store->id }}</span>
  </div>
</div>
