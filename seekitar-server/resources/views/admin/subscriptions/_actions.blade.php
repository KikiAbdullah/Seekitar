<div class="d-flex align-items-center gap-2">
  <a href="{{ route('admin.subscriptions.show', $s) }}" class="btn btn-sm btn-light-info text-info" title="Detail">
    <i class="ti ti-eye fs-4"></i> Detail
  </a>
  @if ($s->status === 'active')
    <form action="{{ route('admin.subscriptions.cancel', $s) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan langganan ini?');" style="display:inline-block;">
      @csrf
      <button type="submit" class="btn btn-sm btn-light-danger text-danger" title="Batalkan">
        <i class="ti ti-circle-x fs-4"></i> Batalkan
      </button>
    </form>
  @endif
</div>
