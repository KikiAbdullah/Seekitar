{{--
    Kerangka halaman tabel admin.

    $judul      teks judul
    $tableId    id elemen <table>
    $ajax       URL endpoint Datatables
    $columns    [['data' => 'name', 'label' => 'Nama', 'orderable' => false], ...]
    $order      (opsional) [[index, 'asc'|'desc']]
    $filterView (opsional) nama view filter, mis. 'admin.stores._filter'
--}}
@php
    $dtColumns = array_map(static fn (array $c): array => [
        'data'       => $c['data'],
        'orderable'  => $c['orderable'] ?? true,
        'searchable' => $c['searchable'] ?? true,
    ], $columns);
@endphp

<div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <h4 class="fw-semibold mb-2">{{ $judul }}</h4>
                <nav aria-label="Remah roti">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dasbor</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $judul }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="card w-100">
    <div class="card-body">
        <div class="d-sm-flex d-block align-items-center justify-content-between mb-4">
            <div class="mb-3 mb-sm-0">
                @isset($filterView)
                    @includeIf($filterView)
                @endisset
            </div>

            <div class="d-flex align-items-center gap-2">
                @isset($exportRoute)
                    <a href="{{ route($exportRoute) }}" class="btn btn-sm btn-outline-success" target="_blank">
                        <i class="fa-regular fa-arrow-alt-circle-down me-1" aria-hidden="true"></i> Export CSV
                    </a>
                @endisset

                <div id="{{ $tableId }}-actions" class="admin-rowactions d-flex align-items-center gap-2"
                     aria-live="polite"></div>
            </div>
        </div>

        <table id="{{ $tableId }}" class="table align-middle text-nowrap w-100 admin-selectable">
            <thead>
                <tr>
                    @foreach ($columns as $col)
                        <th scope="col">{{ $col['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
        </table>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const tableId = @js($tableId);
    const columns = @json($dtColumns);

    const tabel = $('#' + tableId).DataTable({
        processing: true,
        serverSide: true,   // tabel besar tidak dikirim utuh ke browser
        ajax: {
            url: @js($ajax),
            // Filter tambahan dikirim lewat elemen ber-atribut data-dt-filter,
            // sehingga halaman tidak perlu menulis ulang blok ajax ini.
            data: function (d) {
                document.querySelectorAll('[data-dt-filter="' + tableId + '"]').forEach(function (el) {
                    d[el.name] = el.value;
                });
            },
        },
        order: @json($order ?? [[0, 'desc']]),
        columns: columns,
    });

    /*
     * Mengubah filter memuat ulang tabel.
     *
     * WAJIB memakai jQuery `.on('change')`, BUKAN addEventListener.
     *
     * Select2 mengganti nilai lewat `$el.trigger('change')` milik jQuery, dan
     * event sintetis itu TIDAK menyentuh listener native — diverifikasi
     * langsung di jsdom: listener addEventListener terpanggil 0 kali,
     * listener jQuery 1 kali. Dengan addEventListener, seluruh filter
     * berhenti bekerja tanpa satu pun pesan error.
     */
    $('[data-dt-filter="' + tableId + '"]').on('change', function () {
        tabel.ajax.reload();
    });

    const bilah = document.getElementById(tableId + '-actions');

    function kosongkan() {
        // $() milik Datatables, bukan jQuery global: ia mencakup baris di
        // SEMUA halaman tabel, bukan hanya yang sedang tampak.
        tabel.$('tr.table-active').removeClass('table-active');
        bilah.innerHTML = '';
    }

    function pilih(tr, data) {
        // Bersihkan dulu, baru tandai: inilah yang membuat pemilihan selalu
        // TUNGGAL tanpa perlu ekstensi Select.
        tabel.$('tr.table-active').removeClass('table-active');
        tr.classList.add('table-active');

        // Server sudah mengirim HTML tombol lengkap dengan @csrf dan
        // pemeriksaan izin. Merakitnya ulang di sini berarti menduplikasi
        // logika otorisasi ke tempat yang tidak bisa diuji.
        //
        // Bila admin tidak punya izin apa pun atas baris ini, partial _actions
        // menghasilkan string kosong — bilahnya memang harus tetap kosong.
        bilah.innerHTML = data.action || '';
    }

    $('#' + tableId + ' tbody').on('click', 'tr', function () {
        const baris = tabel.row(this);
        const data = baris.data();

        // Baris "tidak ada data" tidak punya data — mengabaikannya mencegah
        // TypeError saat tabel kosong.
        if (!data) return;

        if (this.classList.contains('table-active')) {
            kosongkan();     // klik ulang = batalkan pilihan
            return;
        }

        pilih(this, data);
    });

    // Keyboard: baris harus bisa dipilih tanpa tetikus (WCAG 2.1.1).
    $('#' + tableId + ' tbody').on('keydown', 'tr', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
    });

    // Pilihan dibatalkan saat isi tabel berganti. Tanpa ini, bilah aksi tetap
    // memuat tombol milik baris yang sudah tidak ada di layar — dan menekannya
    // mengubah data yang tidak sedang dilihat siapa pun.
    tabel.on('draw', function () {
        kosongkan();
        // tabindex agar baris bisa dijangkau Tab.
        tabel.$('tr').attr('tabindex', 0);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') kosongkan();
    });
})();
</script>
@endpush
