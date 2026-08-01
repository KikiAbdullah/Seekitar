<div class="d-flex align-items-center gap-2">
  @can('manage-listings')
    <a href="{{ route('admin.listings.show', $listing) }}" class="btn btn-sm btn-outline-info" title="Detail">
      <i class="ti ti-search fs-4" aria-hidden="true"></i> Detail
    </a>
    <form action="{{ route('admin.listings.destroy', $listing) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin men-takedown/menghapus listing ini?');" style="display:inline-block;">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-sm btn-outline-danger" title="Takedown">
        <i class="ti ti-trash fs-4" aria-hidden="true"></i> Takedown
      </button>
    </form>
  @endcan
</div>
