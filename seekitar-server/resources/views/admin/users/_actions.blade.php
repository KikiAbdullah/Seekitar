@can('manage-users')
    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-primary">
        <i class="ti ti-eye me-1" aria-hidden="true"></i> Detail
    </a>

    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-pencil me-1" aria-hidden="true"></i> Sunting
    </a>

    @if ($user->isBlocked())
        <form action="{{ route('admin.users.block', $user) }}" method="POST" class="d-inline">
            @csrf
            <input type="hidden" name="action" value="unblock">
            <button type="submit"
                    class="btn btn-sm btn-outline-success"
                    data-seekitar-confirm="Blokir {{ $user->name }} akan dibuka.">
                <i class="ti ti-lock-open me-1" aria-hidden="true"></i> Buka Blokir
            </button>
        </form>
    @else
        <button type="button"
                class="btn btn-sm btn-outline-danger js-blokir-user"
                data-action="{{ route('admin.users.block', $user) }}"
                data-nama="{{ $user->name }}">
            <i class="ti ti-ban me-1" aria-hidden="true"></i> Blokir
        </button>
    @endif
@endcan
