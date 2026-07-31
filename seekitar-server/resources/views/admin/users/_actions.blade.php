<div class="d-flex align-items-center gap-2">
  @can('manage-users')
    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-light-info text-info" title="Detail">
      <i class="ti ti-eye fs-4"></i> Detail
    </a>
    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-light-primary text-primary" title="Edit">
      <i class="ti ti-edit fs-4"></i> Edit
    </a>
  @endcan
</div>
