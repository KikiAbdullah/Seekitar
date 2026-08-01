<div class="d-flex align-items-center gap-2">
  @can('manage-subscriptions')
  <a href="{{ route('admin.subscriptions.show', $s) }}" class="btn btn-sm btn-outline-info" title="Detail">
    <i class="ti ti-search fs-4" aria-hidden="true"></i> Detail
  </a>
  @if ($s->status === 'active')
    <form action="{{ route('admin.subscriptions.cancel', $s) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan langganan ini?');" style="display:inline-block;">
      @csrf
      <button type="submit" class="btn btn-sm btn-outline-danger" title="Batalkan">
        <i class="ti ti-circle-x fs-4" aria-hidden="true"></i> Batalkan
      </button>
    </form>
  @endif
  @endcan
</div>
