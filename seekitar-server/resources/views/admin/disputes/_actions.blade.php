<div class="d-flex align-items-center">
  @can('manage-disputes')
    <a href="{{ route('admin.disputes.show', $dispute) }}" class="btn btn-sm btn-light-info text-info" title="Detail">
      <i class="ti ti-eye fs-4"></i> Detail
    </a>
  @endcan
</div>
