@can('manage-users')
    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-primary">
        <i class="ti ti-eye me-1" aria-hidden="true"></i> Detail
    </a>

    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-pencil me-1" aria-hidden="true"></i> Sunting
    </a>

    <form action="{{ route('admin.users.block', $user) }}" method="POST" class="d-inline">
        @csrf
        <input type="hidden" name="action" value="{{ $user->is_blocked ? 'unblock' : 'block' }}">
        @unless ($user->is_blocked)
            <input type="hidden" name="reason" value="Ditandai admin dari daftar pengguna">
        @endunless
        <button type="submit"
                class="btn btn-sm {{ $user->is_blocked ? 'btn-outline-success' : 'btn-outline-danger' }}"
                data-seekitar-confirm="Status blokir {{ $user->name }} akan diubah.">
            <i class="ti {{ $user->is_blocked ? 'ti-lock-open' : 'ti-ban' }} me-1" aria-hidden="true"></i>
            {{ $user->is_blocked ? 'Buka Blokir' : 'Blokir' }}
        </button>
    </form>
@endcan
