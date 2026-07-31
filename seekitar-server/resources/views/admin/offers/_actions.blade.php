<div class="d-flex align-items-center">
  @can('manage-offers')
    <a href="{{ route('admin.offers.show', $offer) }}" class="btn btn-sm btn-light-info text-info" title="Detail">
      <i class="ti ti-eye fs-4"></i> Detail
    </a>
  @endcan
</div>
