{{--
    Kerangka halaman tabel admin.

    $judul        teks judul & remah roti
    $tableId      id elemen <table>
    $ajax         URL endpoint Datatables
    $columns      [['data' => .., 'label' => .., 'orderable' => bool?, 'searchable' => bool?,
                    'visible' => bool?, 'name' => string?, 'render' => string? (sumber fungsi JS)], ...]
    $order            (opsional) [[index, 'asc'|'desc']]
    $exportRoute      (opsional) nama route ekspor CSV
    $exportPermission (opsional) permission untuk menampilkan tombol ekspor
    $filterView       (opsional) nama view filter ber-atribut data-dt-filter="<tableId>"
    $extraActions     (opsional) HTML tambahan di kanan toolbar
--}}
@php
    $dtColumns = array_map(static function (array $c): array {
        return [
            'data'       => $c['data'],
            'name'       => $c['name'] ?? $c['data'],
            'orderable'  => $c['orderable'] ?? true,
            'searchable' => $c['searchable'] ?? true,
            'visible'    => $c['visible'] ?? true,
            'render'     => $c['render'] ?? null,
        ];
    }, $columns);
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
        <div class="table-responsive">
            <table id="{{ $tableId }}" class="table align-middle text-nowrap w-100 admin-selectable">
                <thead>
                    <tr>
                        @foreach ($columns as $col)
                            <th scope="col" @if (($col['visible'] ?? true) === false) style="display:none" @endif>{{ $col['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div id="{{ $tableId }}-toolbar" class="d-none">
    @isset($exportRoute)
        @can($exportPermission)
            <a href="{{ route($exportRoute) }}" id="{{ $tableId }}-export"
               class="btn btn-sm btn-outline-secondary table-action-btn" target="_blank">
                <i class="ti ti-download fs-4" aria-hidden="true"></i> Ekspor CSV
            </a>
        @endcan
    @endisset

    {!! $extraActions ?? '' !!}

    <div id="{{ $tableId }}-actions" class="admin-rowactions d-flex align-items-center gap-2"
         aria-live="polite"></div>

    @isset($filterView)
        @includeIf($filterView, ['tableId' => $tableId])
    @endisset
</div>

@push('scripts')
<script>
(function () {
    const tableId = @js($tableId);
    const columns = @json($dtColumns).map(function (def) {
        const col = {
            data: def.data,
            name: def.name,
            orderable: def.orderable,
            searchable: def.searchable,
            visible: def.visible,
        };
        if (def.render) {
            col.render = eval('(' + def.render + ')');
        }
        return col;
    });

    const tabel = $('#' + tableId).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: @js($ajax),
            data: function (d) {
                document.querySelectorAll('[data-dt-filter="' + tableId + '"]').forEach(function (el) {
                    d[el.name] = el.value;
                });
            },
        },
        order: @json($order ?? [[0, 'desc']]),
        columns: columns,
        dom: '<"dt-toolbar d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3"f>'
            + 'rt'
            + '<"row g-0 mt-3 align-items-center"<"col-6"l><"col-6 d-flex justify-content-end"p>>',
    });

    // Bilah filter + tombol toolbar dipindah ke baris cari DataTables:
    // pencarian di kiri, filter & aksi di kanan (paling kanan), sejajar.
    const $toolbar = tabel.table().container().querySelector('.dt-toolbar');
    const $isian = document.getElementById(tableId + '-toolbar');
    if ($toolbar && $isian) {
        $isian.classList.remove('d-none');
        $isian.classList.add('ms-auto', 'd-flex', 'flex-wrap', 'align-items-center', 'gap-2');
        $toolbar.appendChild($isian);

        // Select2 yang sempat diinisialisasi dalam keadaan tersembunyi
        // punya lebar 0; inisialisasi ulang setelah tampil.
        if (window.seekitarSelect2) {
            $isian.querySelectorAll('.js-select2').forEach(function (el) {
                const $el = $(el);
                if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
            });
            window.seekitarSelect2('#' + tableId + '-toolbar');
        }
    }

    $('[data-dt-filter="' + tableId + '"]').on('change', function () {
        tabel.ajax.reload();
        syncExport();
    });

    function syncExport() {
        const tautan = document.getElementById(tableId + '-export');
        if (!tautan) return;
        const params = new URLSearchParams();
        document.querySelectorAll('[data-dt-filter="' + tableId + '"]').forEach(function (el) {
            if (el.value) params.set(el.name, el.value);
        });
        const qs = params.toString();
        tautan.href = @js(isset($exportRoute) ? route($exportRoute) : '') + (qs ? '?' + qs : '');
    }

    const bilah = document.getElementById(tableId + '-actions');

    // Data baris terpilih disimpan di properti elemen supaya halaman yang
    // menyertakan partial ini bisa membaca status/id baris lewat
    // bilah._dtAction (mis. untuk membuka modal kontekstual).
    function kosongkan() {
        tabel.$('tr.table-active').removeClass('table-active');
        bilah.innerHTML = '';
        delete bilah._dtAction;
    }

    function pilih(tr, data) {
        tabel.$('tr.table-active').removeClass('table-active');
        tr.classList.add('table-active');
        bilah.innerHTML = data.action || '';
        bilah._dtAction = data;
    }

    $('#' + tableId + ' tbody').on('click', 'tr', function () {
        const baris = tabel.row(this);
        const data = baris.data();
        if (!data) return;

        if (this.classList.contains('table-active')) {
            kosongkan();
            return;
        }

        pilih(this, data);
    });

    $('#' + tableId + ' tbody').on('keydown', 'tr', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
    });

    tabel.on('draw', function () {
        kosongkan();
        tabel.$('tr').attr('tabindex', 0);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') kosongkan();
    });
})();
</script>
@endpush
