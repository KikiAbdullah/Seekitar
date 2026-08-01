<div class="d-flex align-items-center">
  @can('manage-orders')
    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-info" title="Detail">
      <i class="ti ti-search fs-4" aria-hidden="true"></i> Detail
    </a>
  @endcan
</div>
