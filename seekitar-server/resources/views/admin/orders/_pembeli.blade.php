<div class="d-flex align-items-center gap-2">
  <span class="text-dark fw-semibold">{{ $order->buyer?->name }}</span>
  @if ($order->buyer?->verified_at)
    <span class="text-success" title="KTP Terverifikasi">
      <i class="ti ti-circle-check fs-3" role="img" aria-label="Terverifikasi"></i>
    </span>
  @endif
</div>
