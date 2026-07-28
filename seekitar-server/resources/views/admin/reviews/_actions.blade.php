<form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" class="d-inline">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-outline-danger"
            onclick="return confirm('Hapus ulasan ini? Rating toko akan dihitung ulang.')">Hapus</button>
</form>
