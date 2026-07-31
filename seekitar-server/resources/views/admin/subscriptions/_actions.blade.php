<div class="d-flex gap-1">
    <a href="{{ route('admin.subscriptions.show', $s) }}" class="btn btn-sm btn-outline-primary">
        <i class="ti ti-eye" aria-hidden="true"></i>
    </a>
    @if ($s->status === 'active')
        <form action="{{ route('admin.subscriptions.cancel', $s) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-danger"
                    data-seekitar-confirm="Langganan ini akan dibatalkan.">
                <i class="ti ti-ban" aria-hidden="true"></i>
            </button>
        </form>
    @endif
</div>
