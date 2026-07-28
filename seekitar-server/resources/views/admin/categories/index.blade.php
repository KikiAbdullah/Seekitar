@extends('admin.layout')
@section('title', 'Kategori')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dasbor</a></li>
    <li class="breadcrumb-item active" aria-current="page">Kategori</li>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Kategori</h1>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-seekitar btn-sm">+ Kategori</a>
    </div>

    {{-- Taksonomi dua level saja (PRD §5.1), jadi cukup daftar bersarang —
         tidak perlu tabel server-side untuk 24 baris. --}}
    <div class="card"><div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Nama</th><th>Slug</th><th>Ikon</th><th>Urutan</th><th>Aksi</th></tr></thead>
            <tbody>
            @foreach ($categories as $parent)
                <tr class="table-light">
                    <td><strong>{{ $parent->name }}</strong></td>
                    <td><code>{{ $parent->slug }}</code></td>
                    <td>{{ $parent->icon ?: '—' }}</td>
                    <td>{{ $parent->sort_order }}</td>
                    <td>@include('admin.categories._actions', ['category' => $parent])</td>
                </tr>
                @foreach ($parent->children->sortBy('sort_order') as $child)
                    <tr>
                        <td class="ps-4 text-muted">↳ {{ $child->name }}</td>
                        <td><code>{{ $child->slug }}</code></td>
                        <td>{{ $child->icon ?: '—' }}</td>
                        <td>{{ $child->sort_order }}</td>
                        <td>@include('admin.categories._actions', ['category' => $child])</td>
                    </tr>
                @endforeach
            @endforeach
            </tbody>
        </table>
    </div></div>
@endsection
