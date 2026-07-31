{{--
    Halaman detail satu listing.

    Kontrak data dari ListingController::show — semua relasi sudah dimuat,
    view ini TIDAK menembak basis data lagi:

    $listing    Listing — eager store (nama/photo/centang/rating/domisili)
                + favorites_count dari loadCount.
    $statistik  array ['total', 'selesai', 'omzet'] — tiga agregat kecil.
    $pesanan    koleksi Order terbaru (maks 8) eager buyer.
    $penggemar  koleksi Favorite terbaru (maks 5) eager user.
--}}
@extends('admin.layout')
@section('title', 'Detail Listing')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h4 class="fw-semibold mb-0">{{ $listing->title }}</h4>
                        @include('admin.listings._tipe', ['listing' => $listing])
                        @include('admin.listings._status', ['listing' => $listing])
                    </div>
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

        <div class="col-lg-4">

            {{-- Galeri + ringkasan. --}}
            <div class="card">
                @php $foto = $listing->images; @endphp
                <a id="tautanFotoUtama" href="{{ $foto[0] }}" data-lightbox="listing"
                   data-title="Foto {{ $listing->title }}">
                    <img id="fotoUtamaListing" src="{{ $foto[0] }}" alt="Foto listing {{ $listing->title }}"
                         class="card-img-top" style="height: 240px; object-fit: cover; cursor: pointer;">
                </a>
                @if (count($foto) > 1)
                    {{-- Thumbnail: klik mengganti foto utama & bisa buka lightbox. --}}
                    <div class="d-flex flex-wrap gap-1 px-3 pt-3" role="group" aria-label="Galeri foto">
                        @foreach ($foto as $i => $url)
                            <a href="{{ $url }}" data-lightbox="listing" data-title="Foto {{ $listing->title }} ({{ $i + 1 }})"
                               class="btn p-0 border rounded overflow-hidden {{ $loop->first ? 'border-primary' : '' }}"
                               style="width: 52px; height: 52px; cursor: pointer;"
                               aria-label="Tampilkan foto {{ $loop->iteration }}">
                                <img loading="lazy" decoding="async" src="{{ $url }}" alt=""
                                     style="width: 100%; height: 100%; object-fit: cover;">
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="card-body text-center">
                    <div class="fs-6 fw-bold mb-1">
                        {{ $listing->price === null ? '—' : 'Rp '.number_format((float) $listing->price, 0, ',', '.') }}
                    </div>
                    <div class="text-muted fs-2 mb-3">
                        @if ($listing->listing_type === \App\Enums\ListingType::Service)
                            slot {{ $listing->slot }} pemesan per hari
                        @else
                            stok tersedia {{ $listing->stock_qty }} unit
                        @endif
                    </div>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        @include('admin.listings._actions', ['listing' => $listing])
                    </div>
                </div>

                <ul class="list-group list-group-flush fs-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-3">
                        <span class="text-muted">Difavoritkan</span>
                        <span class="text-end d-inline-flex align-items-center">
                            <i class="fa-regular fa-heart text-danger me-1" aria-hidden="true"></i>
                            {{ number_format((int) $listing->favorites_count, 0, ',', '.') }} pengguna
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

            {{-- Kartu relasi utama: toko pemilik listing. --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Toko Penjual</span>
                    @include('admin.stores._status', ['store' => $listing->store])
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        @if ($listing->store->photo)
                            <a href="{{ $listing->store->photo }}" data-lightbox="listing-store-{{ $listing->id }}"
                               data-title="Foto {{ $listing->store->name }}">
                                <img loading="lazy" decoding="async" src="{{ $listing->store->photo }}" alt="Foto {{ $listing->store->name }}"
                                     class="rounded border flex-shrink-0" style="width: 56px; height: 56px; object-fit: cover; cursor: pointer;">
                            </a>
                        @else
                            <span class="rounded bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0 fw-bold fs-6"
                                  style="width: 56px; height: 56px;" aria-hidden="true">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($listing->store->name, 0, 1)) }}
                            </span>
                        @endif
                        <div class="min-w-0">
                            <div class="d-flex align-items-center fw-semibold">
                                @can('manage-stores')
                                    <a href="{{ route('admin.stores.show', $listing->store) }}" class="text-decoration-none">
                                        {{ $listing->store->name }}
                                    </a>
                                @else
                                    {{ $listing->store->name }}
                                @endcan
                                @if ($listing->store->status === \App\Enums\StoreStatus::Verified)
                                    <i class="fa-regular fa-circle-check text-success ms-1 flex-shrink-0"
                                       title="Toko terverifikasi" role="img" aria-label="Toko terverifikasi"></i>
                                @endif
                            </div>
                            <div class="text-muted fs-2 mb-1">{{ $listing->store->regency }}</div>
                            @if ((int) $listing->store->total_reviews > 0)
                                @include('admin.partials._stars', [
                                    'rating' => (float) $listing->store->rating_avg,
                                    'total'  => (int) $listing->store->total_reviews,
                                ])
                            @else
                                <span class="text-muted fs-2">Belum ada ulasan</span>
                            @endif
                        </div>
                        @can('manage-stores')
                            <a href="{{ route('admin.stores.show', $listing->store) }}"
                               class="btn btn-sm btn-outline-primary ms-auto flex-shrink-0">
                                <i class="fa-regular fa-building me-1" aria-hidden="true"></i> Buka Toko
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            {{-- Statistik performa. --}}
            <div class="row">
                <div class="col-6 col-lg-3">
                    <div class="card"><div class="card-body py-3 text-center">
                        <div class="fs-7 fw-bold">{{ number_format((int) $statistik['total'], 0, ',', '.') }}</div>
                        <div class="text-muted fs-2">Pesanan</div>
                    </div></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card"><div class="card-body py-3 text-center">
                        <div class="fs-7 fw-bold text-success">{{ number_format((int) $statistik['selesai'], 0, ',', '.') }}</div>
                        <div class="text-muted fs-2">Selesai</div>
                    </div></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card"><div class="card-body py-3 px-2 text-center">
                        <div class="admin-stat-nominal fw-bold text-nowrap">Rp {{ number_format((int) $statistik['omzet'], 0, ',', '.') }}</div>
                        <div class="text-muted fs-2">Omzet Selesai</div>
                    </div></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card"><div class="card-body py-3 text-center">
                        <div class="fs-7 fw-bold text-danger">{{ number_format((int) $listing->favorites_count, 0, ',', '.') }}</div>
                        <div class="text-muted fs-2">Difavoritkan</div>
                    </div></div>
                </div>
            </div>

            {{-- Deskripsi: pre-line menjaga paragraf seperti yang ditulis
                 pemilik, tanpa keluar dari auto-escape Blade. --}}
            <div class="card">
                <div class="card-header fw-semibold">Deskripsi</div>
                <div class="card-body">
                    <div class="fs-3" style="white-space: pre-line;">{{ $listing->description }}</div>
                </div>
            </div>

            {{-- Wajah di balik angka favorit. --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Difavoritkan Oleh</span>
                    <span class="badge bg-danger-subtle text-danger">{{ number_format((int) $listing->favorites_count, 0, ',', '.') }}</span>
                </div>
                <div class="card-body">
                    @if ($penggemar->isEmpty())
                        <div class="text-muted fs-3">Belum ada yang memfavoritkan listing ini.</div>
                    @else
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @foreach ($penggemar as $fav)
                                @can('manage-users')
                                    <a href="{{ route('admin.users.show', $fav->user) }}" class="text-decoration-none">
                                        <span class="badge bg-light-secondary text-secondary border">{{ $fav->user?->name ?? '—' }}</span>
                                    </a>
                                @else
                                    <span class="badge bg-light-secondary text-secondary border">{{ $fav->user?->name ?? '—' }}</span>
                                @endcan
                            @endforeach
                            @if ((int) $listing->favorites_count > $penggemar->count())
                                <span class="text-muted fs-2">
                                    +{{ number_format((int) $listing->favorites_count - $penggemar->count(), 0, ',', '.') }} lainnya
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Riwayat pesanan listing ini. --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Pesanan Listing Ini</span>
                    <span class="badge bg-primary-subtle text-primary">{{ number_format((int) $statistik['total'], 0, ',', '.') }}</span>
                </div>
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
                                            @can('manage-users')
                                                <a href="{{ route('admin.users.show', $order->buyer) }}" class="text-decoration-none">
                                                    {{ $order->buyer?->name ?? '—' }}
                                                </a>
                                            @else
                                                {{ $order->buyer?->name ?? '—' }}
                                            @endcan
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
                @if ((int) $statistik['total'] > $pesanan->count())
                    <div class="card-footer text-muted fs-2">
                        Menampilkan {{ $pesanan->count() }} terbaru dari {{ number_format((int) $statistik['total'], 0, ',', '.') }} pesanan.
                    </div>
                @endif
            </div>
        </div>
    </div>
@include('admin.partials._lightbox')
@endsection

@push('scripts')
<script>
    // Galeri: klik thumbnail mengganti foto utama beserta tautan ukuran
    // penuhnya, tanpa muat ulang halaman.
    document.addEventListener('click', function (e) {
        const tautan = e.target.closest('[data-lightbox="listing"]');
        if (!tautan) return;

        const utama  = document.getElementById('fotoUtamaListing');
        const tautanUtama = document.getElementById('tautanFotoUtama');
        if (utama)  utama.src    = tautan.href;
        if (tautanUtama) tautanUtama.href = tautan.href;
    });
</script>
@endpush
