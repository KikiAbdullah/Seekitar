<div class="d-flex align-items-center gap-2">
  @can('manage-stores')
    <a href="{{ route('admin.stores.show', $store) }}" class="btn btn-sm btn-light-info text-info" title="Detail">
      <i class="ti ti-eye fs-4"></i> Detail
    </a>
    <a href="{{ route('admin.stores.edit', $store) }}" class="btn btn-sm btn-light-primary text-primary" title="Edit">
      <i class="ti ti-edit fs-4"></i> Edit
    </a>
  @endcan
</div>
