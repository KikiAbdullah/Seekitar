<div class="d-flex align-items-center">
  @can('manage-requests')
    <a href="{{ route('admin.requests.show', $request) }}" class="btn btn-sm btn-light-info text-info" title="Detail">
      <i class="ti ti-eye fs-4"></i> Detail
    </a>
  @endcan
</div>
