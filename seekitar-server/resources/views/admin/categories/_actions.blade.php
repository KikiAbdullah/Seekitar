@can('manage-categories')
    <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-pencil me-1" aria-hidden="true"></i> Sunting
    </a>

    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Hapus kategori ini?')">
            <i class="ti ti-trash me-1" aria-hidden="true"></i> Hapus
        </button>
    </form>
@endcan
