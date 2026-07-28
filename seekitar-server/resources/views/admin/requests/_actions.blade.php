{{-- Aksi baris permintaan — tampil di bilah aksi sebelah judul. --}}
@can('manage-requests')
    <a href="{{ route('admin.requests.show', $request) }}" class="btn btn-sm btn-outline-primary">
        <i class="ti ti-eye me-1" aria-hidden="true"></i> Lihat
    </a>

    {{-- Perpanjangan hanya masuk akal untuk permintaan yang BELUM ditutup;
         yang sudah ditutup berarti pembeli sudah memilih penyedia (§9.7). --}}
    @if ($request->status !== \App\Enums\RequestStatus::Closed)
        <form action="{{ route('admin.requests.extend', $request) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary"
                    onclick="return confirm('Perpanjang permintaan ini 24 jam?')">
                <i class="ti ti-clock-hour-4-rotate-left me-1" aria-hidden="true"></i> Perpanjang
            </button>
        </form>
    @endif
@endcan
