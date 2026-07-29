{{--
    Ikon centang setelah nama pengguna yang KTP-nya SUDAH terverifikasi —
    penanda sekilas "orang ini identitasnya nyata" tanpa harus membuka
    detailnya. Dibaca dari stempel verified2_at (satu-satunya sumber
    kebenaran), bukan dari level turunan.

    Sengaja TIDAK dirender untuk akun antrian/pending: status mereka sudah
    diwakili lencana "Menunggu".

    $user — model User (yang dibaca hanya verified2_at).
--}}
@if (($user->verified2_at ?? null) !== null)
    <i class="ti ti-circle-check-filled text-success ms-1 flex-shrink-0"
       title="Identitas terverifikasi (KTP disetujui)"
       role="img" aria-label="Identitas terverifikasi"></i>
@endif
