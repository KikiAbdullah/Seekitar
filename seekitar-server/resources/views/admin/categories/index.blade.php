@extends('admin.layout')
@section('title', 'Kategori')

@section('content')

    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <h4 class="fw-semibold mb-2">Kategori</h4>
                    <nav aria-label="Remah roti">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                            </li>
                        <li class="breadcrumb-item active" aria-current="page">Kategori</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <h2 class="h5 mb-0">Kategori</h2>

        <div class="d-flex align-items-center gap-2">
            <div id="categories-table-actions" class="admin-rowactions d-flex align-items-center gap-2"
                 aria-live="polite"></div>

            <a href="{{ route('admin.categories.create') }}" class="btn btn-seekitar btn-sm">
                <i class="ti ti-plus me-1" aria-hidden="true"></i> Kategori
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
            <table id="categories-table" class="table table-hover mb-0 align-middle admin-selectable">
                <thead>
                    <tr>
                        <th scope="col">Nama</th>
                        <th scope="col">Slug</th>
                        <th scope="col">Ikon</th>
                        <th scope="col">Urutan</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($categories as $parent)
                    <tr class="table-light" data-actions="{{ view('admin.categories._actions', ['category' => $parent])->render() }}">
                        <td><strong>{{ $parent->name }}</strong></td>
                        <td><code>{{ $parent->slug }}</code></td>
                        <td>{{ $parent->icon ?: '—' }}</td>
                        <td>{{ $parent->sort_order }}</td>
                    </tr>

                    @foreach ($parent->children->sortBy('sort_order') as $child)
                        <tr data-actions="{{ view('admin.categories._actions', ['category' => $child])->render() }}">
                            <td class="ps-4 text-muted">↳ {{ $child->name }}</td>
                            <td><code>{{ $child->slug }}</code></td>
                            <td>{{ $child->icon ?: '—' }}</td>
                            <td>{{ $child->sort_order }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-5">
                            Belum ada kategori. Jalankan <code>db:seed</code> atau tambahkan manual.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const tabel = document.getElementById('categories-table');
    const bilah = document.getElementById('categories-table-actions');
    if (!tabel || !bilah) return;

    function kosongkan() {
        tabel.querySelectorAll('tr.table-active').forEach(tr => tr.classList.remove('table-active'));
        bilah.innerHTML = '';
    }

    tabel.querySelectorAll('tbody tr[data-actions]').forEach(function (tr) {
        tr.setAttribute('tabindex', 0);

        tr.addEventListener('click', function () {
            if (tr.classList.contains('table-active')) {
                kosongkan();
                return;
            }

            // Bersihkan dulu, baru tandai — inilah yang menjaga pemilihan
            // tetap TUNGGAL.
            tabel.querySelectorAll('tr.table-active').forEach(x => x.classList.remove('table-active'));
            tr.classList.add('table-active');

            /*
             * dataset.actions berisi HTML yang dirakit server dari template
             * tepercaya (partial _actions), bukan dari masukan pengguna —
             * nama kategori di dalamnya sudah lolos escaping Blade saat
             * partial itu dirender. Karena itu menyetelnya sebagai innerHTML
             * aman di sini.
             *
             * Catatan: sintaks kurung-kurawal-ganda Blade sengaja TIDAK
             * ditulis di komentar ini. Blade tetap mengompilasinya walau
             * berada di dalam komentar JavaScript, dan hasilnya memanggil
             * e() tanpa argumen — halaman mati saat render.
             */
            bilah.innerHTML = tr.dataset.actions;
        });

        tr.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                tr.click();
            }
        });
    });

    document.addEventListener('keydown', e => { if (e.key === 'Escape') kosongkan(); });
})();
</script>
@endpush
