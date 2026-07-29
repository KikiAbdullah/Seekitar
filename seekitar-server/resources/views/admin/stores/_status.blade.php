{{--
    Lencana kedudukan toko (kolom status) untuk DataTables & detail.

    Warna & label TIDAK ditulis di sini: keduanya milik StoreStatus —
    menuliskannya ulang di Blade berarti dua sumber kebenaran untuk
    kosakata yang sama. Lencana nonaktif ditambahkan terpisah: saklar
    operasional bukan bagian dari kedudukan.

    $store — model Store (status & is_active sudah ikut di-select querynya).
--}}
<span class="badge bg-{{ $store->status->color() }}-subtle text-{{ $store->status->color() }}">
    {{ $store->status->label() }}
</span>
@unless ($store->is_active)
    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
@endunless
