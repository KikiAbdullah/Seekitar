{{--
    Ikon centang setelah nama pengguna yang identitasnya SUDAH disetujui
    admin — penanda sekilas "orang ini nyata" tanpa membuka detailnya.
    Dibaca dari stempel verified_at (satu-satunya sumber kebenaran), bukan
    dari status atau level turunan: akun yang diblokir tetap pernah lolos
    identitas, meski kedudukannya kini diblokir.

    Sengaja TIDAK dirender untuk akun antrian/ditolak: kedudukan mereka
    sudah diwakili lencana statusnya sendiri.

    $user — model User (yang dibaca hanya verified_at).
--}}
@if (($user->verified_at ?? null) !== null)
    <i class="ti ti-circle-check-filled text-success ms-1 flex-shrink-0"
       title="Identitas terverifikasi admin"
       role="img" aria-label="Identitas terverifikasi"></i>
@endif
