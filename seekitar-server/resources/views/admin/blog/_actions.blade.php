<div class="d-flex gap-1">
    <a href="{{ route('admin.blog.edit', $post) }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-pencil" aria-hidden="true"></i>
    </a>
    <form action="{{ route('admin.blog.destroy', $post) }}" method="POST" class="d-inline">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger"
                data-seekitar-confirm="Artikel &laquo;{{ $post->title }}&raquo; akan dihapus.">
            <i class="ti ti-trash" aria-hidden="true"></i>
        </button>
    </form>
</div>
