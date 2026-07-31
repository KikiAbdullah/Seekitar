<div class="d-flex align-items-center gap-2">
  <span class="text-dark fw-semibold">{{ $order->store?->name }}</span>
  @if ($order->store && $order->store->status->value === 'verified')
    <span class="text-success" title="Toko Terverifikasi">
      <i class="fa-solid fa-circle-check fs-3"></i>
    </span>
  @endif
</div>
