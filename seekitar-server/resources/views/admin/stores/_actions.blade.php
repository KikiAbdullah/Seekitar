{{--
    Aksi per baris DataTables Toko.

    Setujui/Tolak cepat HANYA tampil saat antre (pending): persetujuannya
    tetap melewati gerbang penuh di server (VerifikasiTokoService), jadi
    tombol cepat ini jalan pintas tampilan, bukan jalan pintas aturan.

    $store — model Store baris (status sudah ikut di-select querynya).
--}}
@can('manage-stores')
    <a href="{{ route('admin.stores.show', $store) }}" class="btn btn-sm btn-outline-primary">
        <i class="ti ti-eye me-1" aria-hidden="true"></i> Detail
    </a>
    <a href="{{ route('admin.stores.edit', $store) }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-pencil me-1" aria-hidden="true"></i> Sunting
    </a>
@endcan

@can('verify-stores')
    @if ($store->status === \App\Enums\StoreStatus::Pending)
        <form action="{{ route('admin.stores.approve', $store) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-success"
                    data-seekitar-confirm="Setujui toko {{ $store->name }}?">
                <i class="ti ti-circle-check me-1" aria-hidden="true"></i> Setujui
            </button>
        </form>

        <button type="button" class="btn btn-sm btn-outline-danger js-tolak-toko"
                data-action="{{ route('admin.stores.reject', $store) }}"
                data-nama="{{ $store->name }}">
            <i class="ti ti-ban me-1" aria-hidden="true"></i> Tolak
        </button>
    @endif
@endcan
