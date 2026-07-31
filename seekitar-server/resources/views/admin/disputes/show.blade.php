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

    {{-- SLA respons (Dispute::isOverdue) — ditampilkan mencolok karena
         memengaruhi prioritas penanganan, bukan sekadar tanggal di tabel. --}}
    @if ($dispute->isOverdue())
        <div class="card bg-light-danger shadow-none border-0 mb-4">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-start gap-3">
                    <i class="fa-regular fa-circle-question fs-6 text-danger mt-1" aria-hidden="true"></i>
                    <p class="mb-0 fs-3">
                        <strong>SLA respons sudah terlewat</strong> — batasnya
                        {{ $dispute->response_deadline?->format('d M Y H:i') }}. Tangani segera.
                    </p>
                </div>
            </div>
        </div>
    @elseif ($dispute->status?->value === 'open')
        <div class="card bg-light-warning shadow-none border-0 mb-4">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-start gap-3">
                    <i class="fa-regular fa-clock fs-6 text-warning mt-1" aria-hidden="true"></i>
                    <p class="mb-0 fs-3">
                        Laporan terbuka — batas respons sampai
                        <strong>{{ $dispute->response_deadline?->format('d M Y H:i') }}</strong>.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="row">

        {{-- Rincian laporan. --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header fw-semibold">Rincian Laporan</div>
                <ul class="list-group list-group-flush fs-3">
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Alasan</span>
                        <span class="text-end fw-semibold">{{ $dispute->reason?->label() }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Status</span>
                        <span class="text-end">
                            @if ($dispute->status?->value === 'open')
                                <span class="badge bg-warning-subtle text-warning">{{ $dispute->status->label() }}</span>
                            @else
                                <span class="badge bg-success-subtle text-success">{{ $dispute->status?->label() }}</span>
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Pelapor</span>
                        <span class="text-end">
                            @if ($dispute->reporter)
                                @can('manage-users')
                                    <a href="{{ route('admin.users.show', $dispute->reporter) }}" class="text-decoration-none">
                                        {{ $dispute->reporter->name }}
                                    </a>
                                @else
                                    {{ $dispute->reporter->name }}
                                @endcan
                                <div class="text-muted font-monospace" style="font-size: 11px;">{{ $dispute->reporter->phone }}</div>
                            @else
                                —
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Dibuat</span>
                        <span class="text-end">{{ $dispute->created_at?->format('d M Y H:i') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Batas SLA</span>
                        <span class="text-end {{ $dispute->isOverdue() ? 'text-danger fw-semibold' : '' }}">
                            {{ $dispute->response_deadline?->format('d M Y H:i') }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Pertama direspons</span>
                        <span class="text-end">{{ $dispute->first_responded_at?->format('d M Y H:i') ?? '—' }}</span>
                    </li>
                    @if ($dispute->escalated_at)
                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="text-muted">Dieskalasi</span>
                            <span class="text-end">{{ $dispute->escalated_at->format('d M Y H:i') }}</span>
                        </li>
                    @endif
                </ul>
                <div class="card-body border-top">
                    <div class="text-muted fw-semibold mb-1" style="font-size: 11px;">PENJELASAN PELAPOR</div>
                    <div class="fs-3" style="white-space: pre-line;">{{ $dispute->description ?: '—' }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">

            {{-- Konteks pesanan yang dipersoalkan. --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Pesanan Terkait</span>
                    @can('manage-orders')
                        <a href="{{ route('admin.orders.show', $dispute->order) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="fa-regular fa-eye" aria-hidden="true"></i> Detail Pesanan
                        </a>
                    @endcan
                </div>
                <div class="card-body">
                    <dl class="row mb-0 fs-3">
                        <dt class="col-sm-3">No. Pesanan</dt>
                        <dd class="col-sm-9 font-monospace">{{ $dispute->order?->order_number ?? '—' }}</dd>

                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9">
                            @if ($dispute->order)
                                @include('admin.partials._order_badge', ['order' => $dispute->order])
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-3">Total</dt>
                        <dd class="col-sm-9">
                            Rp {{ number_format((int) $dispute->order?->total_amount, 0, ',', '.') }}
                        </dd>

                        <dt class="col-sm-3">Toko</dt>
                        <dd class="col-sm-9">
                            @if ($dispute->order?->store)
                                @can('manage-stores')
                                    <a href="{{ route('admin.stores.show', $dispute->order->store) }}"
                                       class="text-decoration-none">{{ $dispute->order->store->name }}</a>
                                @else
                                    {{ $dispute->order->store->name }}
                                @endcan
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-3">Pembeli</dt>
                        <dd class="col-sm-9">
                            @if ($dispute->order?->buyer)
                                @can('manage-users')
                                    <a href="{{ route('admin.users.show', $dispute->order->buyer) }}"
                                       class="text-decoration-none">{{ $dispute->order->buyer->name }}</a>
                                    <span class="text-muted font-monospace">{{ $dispute->order->buyer->phone }}</span>
                                @else
                                    {{ $dispute->order->buyer->name }}
                                @endcan
                            @else
                                —
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- Keputusan admin. --}}
            <div class="card">
                <div class="card-header fw-semibold">Keputusan</div>
                <div class="card-body">
                    @if ($dispute->status?->value === 'resolved')
                        {{-- Hasil diturunkan dari status pesanan: transisi
                             final pesanan adalah keputusan dispute-nya. --}}
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="fa-regular fa-circle-check fs-6 text-success" aria-hidden="true"></i>
                            <div>
                                <div class="fw-semibold">Sudah diselesaikan</div>
                                <div class="text-muted fs-2">
                                    {{ $dispute->resolved_at?->format('d M Y H:i') }}
                                    @if ($dispute->assignee)
                                        · oleh {{ $dispute->assignee->name }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($dispute->order?->status?->value === 'dibatalkan')
                            <span class="badge bg-danger-subtle text-danger mb-2">Hasil: pesanan dibatalkan</span>
                        @else
                            <span class="badge bg-success-subtle text-success mb-2">Hasil: diteruskan sebagai selesai</span>
                        @endif

                        <div class="text-muted fw-semibold mb-1" style="font-size: 11px;">CATATAN KEPUTUSAN</div>
                        <div class="fs-3 mb-0" style="white-space: pre-line;">{{ $dispute->resolution_note }}</div>
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
                                          rows="4" required maxlength="2000"
                                          placeholder="Alasan keputusan — tercatat permanen untuk kedua pihak."></textarea>
                            </div>
                            <button type="submit" class="btn btn-seekitar"
                                    data-seekitar-confirm="Keputusan ini akan tercatat permanen untuk kedua pihak.">Selesaikan</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
