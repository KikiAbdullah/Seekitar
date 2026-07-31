<div class="d-flex align-items-center gap-2">
  <a href="{{ route('admin.advertisements.edit', $a) }}" class="btn btn-sm btn-light-primary text-primary" title="Edit">
    <i class="ti ti-edit fs-5"></i> Edit
  </a>
  <form action="{{ route('admin.advertisements.destroy', $a) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus iklan ini?');" style="display:inline-block;">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-light-danger text-danger" title="Hapus">
      <i class="ti ti-trash fs-5"></i> Hapus
    </button>
  </form>
</div>
