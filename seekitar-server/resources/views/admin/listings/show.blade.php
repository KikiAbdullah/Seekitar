@extends('admin.layout')
@section('title', 'Detail Listing')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">{{ $listing->title }}</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.listings.index') }}">Listing</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Detail</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        {{-- Galeri foto, status & aksi. --}}
        <div class="col-lg-4">
            <div class="card">
                @php $foto = $listing->images ?? []; @endphp
                @if ($foto)
                    <a id="tautanFotoUtama" href="{{ $foto[0] }}" target="_blank" rel="noopener"
                       title="Buka ukuran penuh di tab baru">
                        <img id="fotoUtamaListing" src="{{ $foto[0] }}" alt="Foto listing {{ $listing->title }}"
                             class="card-img-top" style="height: 240px; object-fit: cover;">
                    </a>
                    @if (count($foto) > 1)
                        {{-- Thumbnail mengganti foto utama tanpa memuat ulang halaman. --}}
                        <div class="d-flex flex-wrap gap-1 px-3 pt-3" role="group" aria-label="Galeri foto">
                            @foreach ($foto as $url)
                                <button type="button" data-foto-listing="{{ $url }}"
                                        class="btn p-0 border rounded overflow-hidden {{ $loop->first ? 'border-primary' : '' }}"
                                        style="width: 52px; height: 52px;"
                                        aria-label="Tampilkan foto {{ $loop->iteration }}">
                                    <img loading="lazy" decoding="async" src="{{ $url }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                </button>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="bg-light-primary text-primary d-flex align-items-center justify-content-center"
                         style="height: 160px;" aria-hidden="true">
                        <i class="ti ti-photo fs-10"></i>
                    </div>
                @endif

                <div class="card-body text-center">
                    <h5 class="fw-semibold mb-2">{{ $listing->title }}</h5>

                    <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                        <span class="badge bg-primary-subtle text-primary">{{ $listing->listing_type?->label() }}</span>
                        @if ($listing->status?->value === 'active')
                            <span class="badge bg-success-subtle text-success">{{ $listing->status->label() }}</span>
                        @elseif ($listing->status?->value === 'sold')
                            <span class="badge bg-info-subtle text-info">{{ $listing->status->label() }}</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">{{ $listing->status?->label() }}</span>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        @include('admin.listings._actions', ['listing' => $listing])
                    </div>
                </div>

                <ul class="list-group list-group-flush fs-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-3">
                        <span class="text-muted">Toko</span>
                        <span class="text-end">
                            @if ($listing->store)
                                @if ($listing->store->photo)
                                    <img loading="lazy" decoding="async" src="{{ $listing->store->photo }}" alt="" class="rounded me-1"
                                         style="width: 28px; height: 28px; object-fit: cover;">
                                @endif
                                @can('manage-stores')
                                    <a href="{{ route('admin.stores.show', $listing->store) }}" class="text-decoration-none">
                                        {{ $listing->store->name }}
                                    </a>
                                @else
                                    {{ $listing->store->name }}
                                @endcan
                            @else
                                —
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Harga</span>
                        <span class="text-end fw-semibold">
                            {{ $listing->price === null ? '—' : 'Rp '.number_format((int) $listing->price, 0, ',', '.') }}
                        </span>
                    </li>
                    {{--
                        Stok vs slot saling eksklusif menurut tipe (CHECK
                        listings_qty_slot_chk): barang/sewa pakai stok,
                        jasa pakai slot kapasitas per hari.
                    --}}
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">{{ $listing->listing_type?->value === 'service' ? 'Slot' : 'Stok' }}</span>
                        <span class="text-end">
                            @if ($listing->listing_type?->value === 'service')
                                {{ $listing->slot }} / hari
                            @else
                                {{ $listing->stock_qty }} unit
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Dibuat</span>
                        <span class="text-end">{{ $listing->created_at->format('d M Y H:i') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-3">
                        <span class="text-muted">Diperbarui</span>
                        <span class="text-end">{{ $listing->updated_at->format('d M Y H:i') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-lg-8">

            {{-- Statistik performa listing. --}}
            <div class="row">
                <div class="col-6 col-lg-3">
                    <div class="card"><div class="card-body py-3 text-center">
                        <div class="fs-7 fw-bold">{{ (int) $statistik->total }}</div>
                        <div class="text-muted fs-2">Pesanan</div>
                    </div></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card"><div class="card-body py-3 text-center">
                        <div class="fs-7 fw-bold">{{ (int) $statistik->selesai }}</div>
                        <div class="text-muted fs-2">Selesai</div>
                    </div></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card"><div class="card-body py-3 px-2 text-center">
                        <div class="admin-stat-nominal fw-bold text-nowrap">Rp {{ number_format((int) $statistik->omzet, 0, ',', '.') }}</div>
                        <div class="text-muted fs-2">Omzet Selesai</div>
                    </div></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card"><div class="card-body py-3 text-center">
                        <div class="fs-7 fw-bold">{{ $favorit }}</div>
                        <div class="text-muted fs-2">Difavoritkan</div>
                    </div></div>
                </div>
            </div>

            {{-- Deskripsi: white-space pre-line menjaga paragraf seperti
                 yang ditulis pemilik, tanpa keluar dari auto-escape Blade. --}}
            <div class="card">
                <div class="card-header fw-semibold">Deskripsi</div>
                <div class="card-body">
                    <div class="fs-3" style="white-space: pre-line;">{{ $listing->description }}</div>
                </div>
            </div>

            {{-- Riwayat pesanan listing ini. --}}
            <div class="card">
                <div class="card-header fw-semibold">Pesanan Listing Ini</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 fs-3">
                            <thead>
                                <tr>
                                    <th>No. Pesanan</th>
                                    <th>Pembeli</th>
                                    <th class="text-center">Jml</th>
                                    <th class="text-nowrap">Total</th>
                                    <th>Status</th>
                                    <th class="text-nowrap">Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pesanan as $order)
                                    <tr>
                                        <td>
                                            @can('manage-orders')
                                                <a href="{{ route('admin.orders.show', $order) }}"
                                                   class="text-decoration-none font-monospace">
                                                    {{ $order->order_number }}
                                                </a>
                                            @else
                                                <span class="font-monospace">{{ $order->order_number }}</span>
                                            @endcan
                                        </td>
                                        <td>
                                            {{ $order->buyer?->name ?? '—' }}
                                            @if ($order->buyer?->phone)
                                                <div class="text-muted font-monospace" style="font-size: 11px;">
                                                    {{ $order->buyer->phone }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center">× {{ $order->quantity }}</td>
                                        <td class="text-nowrap">Rp {{ number_format((int) $order->total_amount, 0, ',', '.') }}</td>
                                        <td>@include('admin.partials._order_badge', ['order' => $order])</td>
                                        <td class="text-nowrap">{{ $order->created_at->format('d M Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Belum ada pesanan untuk listing ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ((int) $statistik->total > $pesanan->count())
                    <div class="card-footer text-muted fs-2">
                        Menampilkan {{ $pesanan->count() }} terbaru dari {{ (int) $statistik->total }} pesanan.
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Galeri: klik thumbnail mengganti foto utama beserta tautan ukuran
    // penuhnya, tanpa muat ulang halaman. Delegated listener agar tombol
    // tetap berfungsi walau markup galeri berubah.
    document.addEventListener('click', function (e) {
        const tombol = e.target.closest('[data-foto-listing]');
        if (!tombol) return;

        const utama  = document.getElementById('fotoUtamaListing');
        const tautan = document.getElementById('tautanFotoUtama');
        if (utama)  utama.src    = tombol.dataset.fotoListing;
        if (tautan) tautan.href  = tombol.dataset.fotoListing;

        document.querySelectorAll('[data-foto-listing]').forEach(function (t) {
            t.classList.toggle('border-primary', t === tombol);
        });
    });
</script>
@endpush
