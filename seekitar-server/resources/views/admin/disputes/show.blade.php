@extends('admin.layout')
@section('title', 'Tinjau Laporan')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">Laporan #{{ $dispute->order?->order_number }}</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="{{ route('admin.disputes.index') }}">Laporan</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Tinjau</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">Rincian Laporan</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Alasan</dt>
                        <dd class="col-7">{{ $dispute->reason?->value }}</dd>

                        <dt class="col-5">Status</dt>
                        <dd class="col-7">{{ $dispute->status?->value }}</dd>

                        <dt class="col-5">Batas SLA</dt>
                        <dd class="col-7">{{ $dispute->response_deadline?->format('d M Y H:i') }}</dd>

                        <dt class="col-5">Penjelasan</dt>
                        <dd class="col-7">{{ $dispute->description ?: '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">Keputusan</div>
                <div class="card-body">
                    @if ($dispute->status === \App\Enums\DisputeStatus::Resolved)
                        <p class="mb-1"><strong>Sudah diselesaikan</strong>
                            {{ $dispute->resolved_at?->format('d M Y H:i') }}</p>
                        <p class="text-muted mb-0">{{ $dispute->resolution_note }}</p>
                    @else
                        <form method="POST" action="{{ route('admin.disputes.resolve', $dispute) }}">
                            @csrf
                            <div class="mb-3">
                                <label for="resolution" class="form-label">Hasil</label>
                                <select id="resolution" data-min-search="20" name="resolution" class="form-select js-select2" required>
                                    <option value="selesai">Teruskan sebagai selesai</option>
                                    <option value="dibatalkan">Batalkan pesanan</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="resolution_note" class="form-label">Catatan keputusan</label>
                                <textarea id="resolution_note" name="resolution_note" class="form-control"
                                          rows="4" required maxlength="2000"></textarea>
                            </div>
                            <button type="submit" class="btn btn-seekitar">Selesaikan</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
