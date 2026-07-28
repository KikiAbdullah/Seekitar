{{-- Aksi baris listing — tampil di bilah aksi sebelah judul. --}}
@can('manage-listings')
    <a href="{{ route('admin.listings.show', $listing) }}" class="btn btn-sm btn-outline-primary">
        <i class="fa-solid fa-eye me-1" aria-hidden="true"></i> Lihat
    </a>

    <form action="{{ route('admin.listings.destroy', $listing) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Hapus listing ini? Tindakan tidak bisa dibatalkan.')">
            <i class="fa-solid fa-trash me-1" aria-hidden="true"></i> Hapus
        </button>
    </form>
@endcan
