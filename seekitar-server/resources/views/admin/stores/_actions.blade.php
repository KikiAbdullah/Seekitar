@can('manage-stores')
    <a href="{{ route('admin.stores.show', $store) }}" class="btn btn-sm btn-outline-primary">
        <i class="ti ti-eye me-1" aria-hidden="true"></i> Detail
    </a>
@endcan

@can('verify-stores')
    @if ($store->verification_status !== \App\Enums\VerificationStatus::Verified)
        <form action="{{ route('admin.stores.approve', $store) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-success">
                <i class="ti ti-circle-check me-1" aria-hidden="true"></i> Setujui
            </button>
        </form>
    @endif

    @if ($store->verification_status !== \App\Enums\VerificationStatus::Rejected)
        <button type="button" class="btn btn-sm btn-outline-danger js-tolak-toko"
                data-action="{{ route('admin.stores.reject', $store) }}"
                data-nama="{{ $store->name }}">
            <i class="ti ti-ban me-1" aria-hidden="true"></i> Tolak
        </button>
    @endif
@endcan
