<div class="d-flex align-items-center gap-2">
  @can('manage-blog')
  <a href="{{ route('admin.blog.edit', $post) }}" class="btn btn-sm btn-outline-primary" title="Edit">
    <i class="ti ti-edit fs-5" aria-hidden="true"></i> Edit
  </a>
  <form action="{{ route('admin.blog.destroy', $post) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus artikel ini?');" style="display:inline-block;">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
      <i class="ti ti-trash fs-5" aria-hidden="true"></i> Hapus
    </button>
  </form>
  @endcan
</div>
