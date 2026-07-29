@can('manage-reviews')
    <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger"
                data-seekitar-confirm="Ulasan dihapus dan rating toko dihitung ulang.">
            <i class="ti ti-trash me-1" aria-hidden="true"></i> Hapus
        </button>
    </form>
@endcan
