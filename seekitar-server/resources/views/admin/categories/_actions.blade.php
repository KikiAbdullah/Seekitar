<a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary">Sunting</a>

<form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline">
    @csrf
    @method('DELETE')
    {{-- Penghapusan bisa ditolak controller bila kategori masih dipakai
         toko/permintaan — FK RESTRICT + pemeriksaan JSON_CONTAINS. --}}
    <button type="submit" class="btn btn-sm btn-outline-danger"
            onclick="return confirm('Hapus kategori ini?')">Hapus</button>
</form>
