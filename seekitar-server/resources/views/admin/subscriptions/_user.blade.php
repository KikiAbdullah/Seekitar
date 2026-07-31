<span class="d-inline-flex align-items-center gap-2">
    @can('manage-users')
        <a href="{{ route('admin.users.show', $s->user) }}" class="text-decoration-none fw-semibold">
            {{ $s->user?->name ?? '—' }}
        </a>
    @else
        {{ $s->user?->name ?? '—' }}
    @endcan
    @if ($s->store)
        <span class="text-muted" style="font-size: 11px;">· {{ $s->store->name }}</span>
    @endif
</span>
