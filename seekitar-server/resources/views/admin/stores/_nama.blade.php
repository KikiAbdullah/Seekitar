<div class="d-flex align-items-center gap-2">
  <span class="fw-semibold text-dark">{{ $store->name }}</span>
  @if ($store->status->value === 'verified')
    <span class="text-success" title="Toko Terverifikasi">
      <i class="ti ti-circle-check fs-3" role="img" aria-label="Terverifikasi"></i>
    </span>
  @endif
</div>
