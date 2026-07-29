{{--
    Sel judul untuk DataTables Etalase (render per baris di server).

    Foto mini menemani judul supaya admin mengenali barangnya sekilas;
    baris bawahnya membawa kapasitas (stok / slot) agar kolom tersendiri
    tidak perlu makan lebar tabel. images TIDAK pernah kosong —
    accessor menjamin placeholder — jadi <img> tanpa cabang.

    $listing — model Listing baris (images, stock_qty/slot, listing_type).
--}}
<span class="d-flex align-items-center gap-2">
    <img loading="lazy" decoding="async" src="{{ $listing->images[0] }}" alt=""
         class="rounded flex-shrink-0 border"
         style="width: 46px; height: 46px; object-fit: cover;">
    <span class="d-flex flex-column">
        @can('manage-listings')
            <a href="{{ route('admin.listings.show', $listing) }}"
               class="fw-semibold text-decoration-none">{{ $listing->title }}</a>
        @else
            <span class="fw-semibold">{{ $listing->title }}</span>
        @endcan
        <small class="text-muted">
            @if ($listing->listing_type === \App\Enums\ListingType::Service)
                slot {{ $listing->slot }} / hari
            @else
                stok {{ $listing->stock_qty }} unit
            @endif
        </small>
    </span>
</span>
