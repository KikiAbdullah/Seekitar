{{--
    Lencana kedudukan akun (kolom status) untuk DataTables & detail.

    Warna & label TIDAK ditulis di sini: keduanya milik UserStatus —
    menuliskannya ulang di Blade berarti dua sumber kebenaran untuk
    kosakata yang sama.

    $user — model User (status sudah ikut di-select querynya).
--}}
<span class="badge bg-{{ $user->status->color() }}-subtle text-{{ $user->status->color() }}">
    {{ $user->status->label() }}
</span>
