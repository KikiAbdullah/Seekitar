<div class="d-flex align-items-center">
  @can('manage-reviews')
    <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus ulasan ini? Penghapusan ulasan akan memicu kalkulasi ulang reputasi/rating toko dan pengulas.');">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1">
        <i class="ti ti-trash fs-4" aria-hidden="true"></i> Hapus
      </button>
    </form>
  @endcan
</div>
