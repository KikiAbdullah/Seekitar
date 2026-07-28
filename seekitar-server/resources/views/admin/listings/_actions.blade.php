<a href="{{ route('admin.listings.show', $listing) }}" class="btn btn-sm btn-outline-primary">Lihat</a>

<form action="{{ route('admin.listings.destroy', $listing) }}" method="POST" class="d-inline">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-outline-danger"
            onclick="return confirm('Hapus listing ini? Tindakan tidak bisa dibatalkan.')">Hapus</button>
</form>
