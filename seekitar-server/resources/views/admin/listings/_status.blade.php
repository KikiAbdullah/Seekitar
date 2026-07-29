{{--
    Lencana status listing (kolom status) untuk DataTables & detail.

    Warna & label TIDAK ditulis di sini: keduanya milik ListingStatus —
    menuliskannya ulang di Blade berarti dua sumber kebenaran untuk
    kosakata yang sama.

    $listing — model Listing (status sudah ikut di-select querynya).
--}}
<span class="badge bg-{{ $listing->status->color() }}-subtle text-{{ $listing->status->color() }}">
    {{ $listing->status->label() }}
</span>
