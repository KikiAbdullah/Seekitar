@if ($store->verification_status !== \App\Enums\VerificationStatus::Verified)
    <form action="{{ route('admin.stores.approve', $store) }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-success">Setujui</button>
    </form>
@endif

@if ($store->verification_status !== \App\Enums\VerificationStatus::Rejected)
    <button type="button" class="btn btn-sm btn-outline-danger"
            data-bs-toggle="modal" data-bs-target="#reject-{{ $store->id }}">Tolak</button>

    <div class="modal fade" id="reject-{{ $store->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('admin.stores.reject', $store) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tolak {{ $store->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <label for="reason-{{ $store->id }}" class="form-label">Alasan penolakan</label>
                    {{-- Wajib: tanpa alasan, pemilik toko tidak tahu apa yang
                         harus diperbaiki dan akan mengajukan ulang hal sama. --}}
                    <textarea id="reason-{{ $store->id }}" name="reason" class="form-control"
                              rows="3" required maxlength="500"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Tolak</button>
                </div>
            </form>
        </div>
    </div>
@endif
