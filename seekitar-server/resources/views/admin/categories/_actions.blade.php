@can('manage-categories')
    <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-regular fa-pen-to-square me-1" aria-hidden="true"></i> Sunting
    </a>

    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger"
                data-seekitar-confirm="Kategori ini dan seluruh isinya akan dihapus.">
            <i class="fa-regular fa-trash-can me-1" aria-hidden="true"></i> Hapus
        </button>
    </form>
@endcan
