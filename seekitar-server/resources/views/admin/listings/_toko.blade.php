{{--
    Sel toko untuk DataTables Etalase (render per baris di server).

    Centang hijau di belakang nama berarti toko TERVERIFIKASI — bahasa
    visual yang sama dengan tabel Toko, supaya admin tidak belajar dua
    kali. Tautan ke detail toko hanya bagi yang berhak membukanya.

    $listing — model Listing baris (relasi store sudah di-eager-load).
--}}
@if ($listing->store)
    <span class="d-inline-flex align-items-center">
        @can('manage-stores')
            <a href="{{ route('admin.stores.show', $listing->store) }}" class="text-decoration-none">
                {{ $listing->store->name }}
            </a>
        @else
            {{ $listing->store->name }}
        @endcan
        @if ($listing->store->status === \App\Enums\StoreStatus::Verified)
            <i class="ti ti-circle-check-filled text-success ms-1 flex-shrink-0"
               title="Toko terverifikasi"
               role="img" aria-label="Toko terverifikasi"></i>
        @endif
    </span>
@else
    <span class="text-muted">—</span>
@endif
