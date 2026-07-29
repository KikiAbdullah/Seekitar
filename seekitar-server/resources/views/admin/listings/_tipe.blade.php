{{--
    Lencana tipe listing dengan ikon — Barang / Jasa / Sewa.

    Ikon membuat kolom sempit tetap terbaca: warna saja gagal bagi
    penglihatan buta warna, teks saja memaksa membaca ulang tiap baris.

    $listing — model Listing (listing_type sudah ikut di-select querynya).
--}}
<span class="badge bg-{{ $listing->listing_type->color() }}-subtle text-{{ $listing->listing_type->color() }}">
    <i class="ti {{ $listing->listing_type->icon() }} me-1" aria-hidden="true"></i>{{ $listing->listing_type->label() }}
</span>
