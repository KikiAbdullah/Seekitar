@can('manage-listings')
    <a href="{{ route('admin.listings.show', $listing) }}" class="btn btn-sm btn-outline-primary">
        <i class="fa-regular fa-eye me-1" aria-hidden="true"></i> Lihat
    </a>

    <form action="{{ route('admin.listings.destroy', $listing) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger"
                data-seekitar-confirm="Listing ini akan dihapus dan tidak bisa dikembalikan.">
            <i class="fa-regular fa-trash-can me-1" aria-hidden="true"></i> Hapus
        </button>
    </form>
@endcan
