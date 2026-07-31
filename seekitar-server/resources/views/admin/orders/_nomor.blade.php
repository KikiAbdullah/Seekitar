<div>
  <a href="{{ route('admin.orders.show', $order) }}" class="fw-semibold text-primary text-decoration-none">
    #{{ $order->order_number }}
  </a>
  <div class="fs-2 text-muted text-capitalize">{{ $order->order_type?->label() }}</div>
</div>
