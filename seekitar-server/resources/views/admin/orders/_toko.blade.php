{{--
    Sel toko untuk DataTables Pesanan (render per baris di server).

    Centang hijau = toko TERVERIFIKASI (dibaca dari kedudukan, bukan
    stempel): toko yang diblokir tidak lagi pantas membawa tanda itu.

    $order — model Order baris (relasi store sudah di-eager-load).
--}}
@if ($order->store)
    <span class="d-inline-flex align-items-center">
        @can('manage-stores')
            <a href="{{ route('admin.stores.show', $order->store) }}" class="text-decoration-none">
                {{ $order->store->name }}
            </a>
        @else
            {{ $order->store->name }}
        @endcan
        @if ($order->store->status === \App\Enums\StoreStatus::Verified)
            <i class="ti ti-circle-check-filled text-success ms-1 flex-shrink-0"
               title="Toko terverifikasi" role="img" aria-label="Toko terverifikasi"></i>
        @endif
    </span>
@else
    <span class="text-muted">—</span>
@endif
