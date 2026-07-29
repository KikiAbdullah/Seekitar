@can('manage-requests')
    <a href="{{ route('admin.requests.show', $request) }}" class="btn btn-sm btn-outline-primary">
        <i class="ti ti-eye me-1" aria-hidden="true"></i> Lihat
    </a>

    @if ($request->status !== \App\Enums\RequestStatus::Closed)
        <form action="{{ route('admin.requests.extend', $request) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary"
                    data-seekitar-confirm="Tenggat permintaan diperpanjang 24 jam.">
                <i class="ti ti-history me-1" aria-hidden="true"></i> Perpanjang
            </button>
        </form>
    @endif
@endcan
