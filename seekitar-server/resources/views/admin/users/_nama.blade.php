{{--
    Sel nama untuk DataTables Pengguna (render per baris di server).
    Terpisah dari _actions supaya escaping nama tetap ditangani Blade.

    $user — model User baris (verified_at sudah ikut di-select querynya).
--}}
<span class="d-inline-flex align-items-center">
    {{ $user->name ?? '(belum mengisi nama)' }}
    @include('admin.partials._cek_terverifikasi', ['user' => $user])
</span>
