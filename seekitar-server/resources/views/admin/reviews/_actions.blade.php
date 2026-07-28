{{-- Aksi baris ulasan — tampil di bilah aksi sebelah judul. --}}
@can('manage-reviews')
    <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Hapus ulasan ini? Rating toko akan dihitung ulang.')">
            <i class="fa-solid fa-trash me-1" aria-hidden="true"></i> Hapus
        </button>
    </form>
@endcan
