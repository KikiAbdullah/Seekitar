{{--
    Sel nama untuk DataTables Toko (render per baris di server).

    Ikon centang di belakang nama berarti toko TERVERIFIKASI — dibaca dari
    kedudukan, bukan stempel: toko yang diblokir tidak lagi pantas
    membawa tanda itu meski verified_at-nya masih ada.

    $store — model Store baris (status sudah ikut di-select querynya).
--}}
<span class="d-inline-flex align-items-center">
    {{ $store->name }}
    @if ($store->status === \App\Enums\StoreStatus::Verified)
        <i class="fa-regular fa-circle-check text-success ms-1 flex-shrink-0"
           title="Toko terverifikasi"
           role="img" aria-label="Toko terverifikasi"></i>
    @endif
</span>
