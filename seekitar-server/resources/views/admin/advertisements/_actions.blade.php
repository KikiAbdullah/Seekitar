<div class="d-flex gap-1">
    <a href="{{ route('admin.advertisements.edit', $a) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
    </a>
    <form action="{{ route('admin.advertisements.destroy', $a) }}" method="POST" class="d-inline">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger"
                data-seekitar-confirm="Iklan ini akan dihapus.">
            <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
        </button>
    </form>
</div>
