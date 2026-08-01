<div class="d-flex align-items-center gap-3">
  <img src="{{ $store->photo ? asset('storage/' . $store->photo) : 'https://placehold.co/100x100?text=Toko' }}" class="rounded border shadow-sm" width="45" height="45" style="object-fit: cover;">
  <div>
    <h6 class="fw-bold mb-0 text-dark">{{ $store->name }}</h6>
    <span class="fs-2 text-muted">ID: {{ $store->id }}</span>
  </div>
</div>
