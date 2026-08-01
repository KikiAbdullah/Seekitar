<div class="d-flex align-items-center gap-1">
  <span>{{ $listing->store?->name ?? '—' }}</span>
  @if ($listing->store && $listing->store->status->value === 'verified')
    <span class="text-success" title="Toko Terverifikasi">
      <i class="ti ti-circle-check fs-2" role="img" aria-label="Terverifikasi"></i>
    </span>
  @endif
</div>
