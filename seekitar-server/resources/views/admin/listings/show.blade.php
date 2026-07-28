@extends('admin.layout')
@section('title', 'Detail Listing')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.listings.index') }}">Listing</a></li>
    <li class="breadcrumb-item active" aria-current="page">Detail</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">{{ $listing->title }}</h1>

    <div class="card"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Toko</dt>
            <dd class="col-sm-9">{{ $listing->store?->name ?? '—' }}</dd>

            <dt class="col-sm-3">Tipe</dt>
            <dd class="col-sm-9">{{ $listing->listing_type?->value }}</dd>

            <dt class="col-sm-3">Harga</dt>
            <dd class="col-sm-9">
                {{ $listing->price === null ? '—' : 'Rp '.number_format((int) $listing->price, 0, ',', '.') }}
            </dd>

            <dt class="col-sm-3">Stok / Slot</dt>
            <dd class="col-sm-9">{{ $listing->stock_qty ?? $listing->slot ?? '—' }}</dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">{{ $listing->status?->value }}</dd>

            <dt class="col-sm-3">Deskripsi</dt>
            <dd class="col-sm-9">{{ $listing->description }}</dd>

            <dt class="col-sm-3">Foto</dt>
            <dd class="col-sm-9">
                @forelse ($listing->images ?? [] as $url)
                    {{-- Atribut tetap memakai {{ }}: Blade meng-escape kutip
                         sehingga URL berbahaya tidak bisa keluar dari atribut. --}}
                    <img src="{{ $url }}" alt="Foto {{ $listing->title }}"
                         class="rounded border me-2 mb-2" style="height:96px">
                @empty
                    —
                @endforelse
            </dd>
        </dl>
    </div></div>
@endsection
