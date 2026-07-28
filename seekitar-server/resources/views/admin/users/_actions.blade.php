{{-- Blade meng-escape otomatis dengan {{ }}; JANGAN pakai {!! !!} di sini,
     karena itu justru MEMATIKAN escaping dan membuka XSS. --}}
<a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Sunting</a>

<form action="{{ route('admin.users.block', $user) }}" method="POST" class="d-inline">
    @csrf
    <input type="hidden" name="action" value="{{ $user->is_blocked ? 'unblock' : 'block' }}">
    @unless ($user->is_blocked)
        <input type="hidden" name="reason" value="Ditandai admin dari daftar pengguna">
    @endunless
    <button type="submit"
            class="btn btn-sm {{ $user->is_blocked ? 'btn-outline-success' : 'btn-outline-danger' }}"
            onclick="return confirm('Yakin ubah status blokir pengguna ini?')">
        {{ $user->is_blocked ? 'Buka Blokir' : 'Blokir' }}
    </button>
</form>
