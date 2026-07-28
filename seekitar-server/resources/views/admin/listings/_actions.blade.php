@can('manage-listings')
    <a href="{{ route('admin.listings.show', $listing) }}" class="btn btn-sm btn-outline-primary">
        <i class="ti ti-eye me-1" aria-hidden="true"></i> Lihat
    </a>

    <form action="{{ route('admin.listings.destroy', $listing) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Hapus listing ini? Tindakan tidak bisa dibatalkan.')">
            <i class="ti ti-trash me-1" aria-hidden="true"></i> Hapus
        </button>
    </form>
@endcan
