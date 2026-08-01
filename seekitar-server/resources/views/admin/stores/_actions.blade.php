<div class="d-flex align-items-center gap-2">
  @can('manage-stores')
    <a href="{{ route('admin.stores.show', $store) }}" class="btn btn-sm btn-outline-info" title="Detail">
      <i class="ti ti-search fs-4" aria-hidden="true"></i> Detail
    </a>
    <a href="{{ route('admin.stores.edit', $store) }}" class="btn btn-sm btn-outline-primary" title="Edit">
      <i class="ti ti-edit fs-4" aria-hidden="true"></i> Edit
    </a>
  @endcan
</div>
