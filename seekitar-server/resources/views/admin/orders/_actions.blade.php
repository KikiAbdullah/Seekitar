<div class="d-flex align-items-center">
  @can('manage-orders')
    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-light-info text-info" title="Detail">
      <i class="ti ti-eye fs-4"></i> Detail
    </a>
  @endcan
</div>
