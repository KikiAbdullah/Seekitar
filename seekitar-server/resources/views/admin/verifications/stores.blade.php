@extends('admin.layout')
@section('title', 'Verifikasi Toko')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">Verifikasi Toko</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                        <li class="breadcrumb-item active" aria-current="page">Verifikasi</li>
                        <li class="breadcrumb-item active" aria-current="page">Toko</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>


    <div class="card bg-light-info shadow-none border-0 mb-4">
        <div class="card-body py-3 px-4">
            <div class="d-flex align-items-start gap-3">
                <i class="ti ti-info-circle fs-6 text-info mt-1" aria-hidden="true"></i>
                <p class="mb-0 fs-3">Toko yang belum disetujui <strong>tidak bisa memasang listing</strong> maupun mengirim penawaran. Pemiliknya wajib sudah terverifikasi KTP (Level 2).</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Antrian Pengajuan Toko</span>
            <span class="badge text-bg-secondary">{{ $pending->total() }} menunggu</span>
        </div>

        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th scope="col">Toko</th>
                        <th scope="col">Pemilik</th>
                        <th scope="col">Jenis</th>
                        <th scope="col">Diajukan</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($pending as $store)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $store->name }}</div>
                            <div class="text-muted" style="font-size: 12px;">
                                {{ Str::limit($store->address, 60) }}
                            </div>
                        </td>
                        <td>
                            {{ $store->owner?->name ?? '—' }}
                            <div class="text-muted" style="font-size: 12px;">{{ $store->owner?->phone }}</div>
                        </td>
                        <td>
                            @foreach ($store->store_type ?? [] as $tipe)
                                <span class="badge text-bg-light border">{{ $tipe }}</span>
                            @endforeach
                        </td>
                        <td class="text-nowrap">
                            {{ $store->created_at?->format('d M Y') }}
                            <div class="text-muted" style="font-size: 12px;">
                                {{ $store->created_at?->diffForHumans() }}
                            </div>
                        </td>
                        <td class="text-end text-nowrap">
                            <form action="{{ route('admin.verifications.stores.approve', $store) }}"
                                  method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="ti ti-circle-check" aria-hidden="true"></i> Setujui
                                </button>
                            </form>

                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal" data-bs-target="#tolakStore{{ $loop->index }}">
                                Tolak
                            </button>

                            @include('admin.verifications._reject_modal', [
                                'modalId' => 'tolakStore'.$loop->index,
                                'judul'   => 'Tolak toko '.$store->name,
                                'action'  => route('admin.verifications.stores.reject', $store),
                                'catatan' => 'Pemilik dapat memperbaiki data lalu mengajukan ulang.',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="ti ti-circle-check fs-7 d-block mb-2 opacity-25" aria-hidden="true"></i>
                            Tidak ada pengajuan toko menunggu.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $pending->links() }}</div>
@endsection
