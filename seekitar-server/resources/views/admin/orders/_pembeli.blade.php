<div class="d-flex align-items-center gap-2">
  <span class="text-dark fw-semibold">{{ $order->buyer?->name }}</span>
  @if ($order->buyer?->verified_at)
    <span class="text-success" title="KTP Terverifikasi">
      <i class="fa-solid fa-circle-check fs-3"></i>
    </span>
  @endif
</div>
