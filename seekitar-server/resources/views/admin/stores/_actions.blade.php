{{--
    Aksi baris toko — dirender server, ditampilkan di bilah aksi sebelah judul.

    Modal penolakan TIDAK lagi ikut di sini. Sebelumnya tiap baris membawa
    modalnya sendiri ber-id `reject-{uuid}`; dengan 100 baris itu 100 modal di
    DOM. Sekarang dipakai SATU modal bersama di halaman index, dan tombol di
    bawah hanya mengirim data toko lewat atribut data-*.

    Aksi tetap dibungkus @can: bilah aksi hanyalah tempat menampilkan, bukan
    pengganti otorisasi. Admin tanpa `verify-stores` memilih baris dan tidak
    melihat tombol apa pun.
--}}
@can('verify-stores')
    @if ($store->verification_status !== \App\Enums\VerificationStatus::Verified)
        <form action="{{ route('admin.stores.approve', $store) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-success">
                <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i> Setujui
            </button>
        </form>
    @endif

    @if ($store->verification_status !== \App\Enums\VerificationStatus::Rejected)
        {{-- data-* dibaca skrip modal bersama; nilainya sudah di-escape Blade. --}}
        <button type="button" class="btn btn-sm btn-outline-danger js-tolak-toko"
                data-action="{{ route('admin.stores.reject', $store) }}"
                data-nama="{{ $store->name }}">
            <i class="fa-solid fa-ban me-1" aria-hidden="true"></i> Tolak
        </button>
    @endif
@endcan
