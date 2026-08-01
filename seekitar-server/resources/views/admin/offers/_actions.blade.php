<div class="d-flex align-items-center">
  @can('manage-offers')
    <a href="{{ route('admin.offers.show', $offer) }}" class="btn btn-sm btn-outline-info" title="Detail">
      <i class="ti ti-search fs-4" aria-hidden="true"></i> Detail
    </a>
  @endcan
</div>
