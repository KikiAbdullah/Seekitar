<div class="d-flex align-items-center gap-2">
  <span class="fw-semibold text-dark">{{ $store->name }}</span>
  @if ($store->status->value === 'verified')
    <span class="text-success" title="Toko Terverifikasi">
      <i class="fa-solid fa-circle-check fs-3"></i>
    </span>
  @endif
</div>
