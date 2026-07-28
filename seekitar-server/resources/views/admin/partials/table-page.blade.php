{{--
    Kerangka halaman tabel admin: judul + bilah aksi + tabel terpilih-satu.

    Parameter:
      $judul     : teks judul halaman
      $tableId   : id elemen <table>
      $ajax      : URL endpoint Datatables
      $columns   : [['data' => 'name', 'label' => 'Nama', 'orderable' => false], ...]
      $order     : (opsional) [[index, 'asc'|'desc']]
      $petunjuk  : (opsional) kalimat di bawah judul
      $filter    : (opsional) slot HTML filter, dirender di atas tabel

    KENAPA TOMBOL AKSI TIDAK LAGI JADI KOLOM
    ----------------------------------------
    Kolom aksi memaksa setiap baris membawa tombolnya sendiri: pada 1.000 baris
    itu 1.000 tombol di DOM, dan kolomnya ikut melebar mengorbankan kolom data
    yang justru dibaca. Sebagai gantinya baris dipilih, lalu aksinya muncul
    sekali di kanan judul.

    HTML tombolnya TETAP dikirim server di field `action`. Field yang tidak
    dideklarasikan sebagai kolom tidak dirender Datatables, tetapi tetap ikut
    di `row().data()` — diverifikasi langsung dengan Datatables 2.3.8. Dengan
    begitu tombol tetap dirakit Blade lengkap dengan @csrf, @method, dan
    pemeriksaan izin, bukan dirakit ulang di JavaScript.

    PEMILIHAN TUNGGAL, TANPA EKSTENSI
    ---------------------------------
    Ekstensi resmi Select tidak dipakai: ia menambah satu berkas CSS + JS demi
    perilaku yang di sini cukup belasan baris, dan defaultnya justru multi-baris.
--}}
@php
    // Disusun di blok @php, BUKAN di dalam @json(): Blade memotong argumen
    // direktif pada kurung penutup pertama, sehingga arrow function
    // multi-baris menghasilkan PHP yang tidak bisa di-parse.
    $dtColumns = array_map(static fn (array $c): array => [
        'data'       => $c['data'],
        'orderable'  => $c['orderable'] ?? true,
        'searchable' => $c['searchable'] ?? true,
    ], $columns);
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h2 class="h5 mb-0">{{ $judul }}</h2>
        @isset($petunjuk)
            <p class="text-muted small mb-0 mt-1">{{ $petunjuk }}</p>
        @endisset
    </div>

    {{--
        Bilah aksi — sejajar judul, rata kanan.

        aria-live="polite" supaya pembaca layar mengumumkan tombol yang baru
        muncul; tanpa itu pengguna non-visual tidak tahu ada aksi tersedia
        setelah memilih baris.
    --}}
    <div id="{{ $tableId }}-actions" class="admin-rowactions d-flex align-items-center gap-2"
         aria-live="polite">
        <span class="text-muted small admin-rowactions-hint">
            <i class="fa-solid fa-hand-pointer me-1" aria-hidden="true"></i>
            Pilih satu baris untuk melihat aksi
        </span>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @isset($filter)
            <div class="mb-3">{{ $filter }}</div>
        @endisset

        <table id="{{ $tableId }}" class="table table-striped table-hover w-100 admin-selectable">
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

    // Mengubah filter memuat ulang tabel. Tanpa ini, elemen filter terkirim
    // di request berikutnya saja — pengguna harus menyortir atau berpindah
    // halaman dulu sebelum pilihannya berlaku.
    document.querySelectorAll('[data-dt-filter="' + tableId + '"]').forEach(function (el) {
        el.addEventListener('change', function () { tabel.ajax.reload(); });
    });

    const bilah = document.getElementById(tableId + '-actions');
    const petunjukAwal = bilah.innerHTML;

    function kosongkan() {
        // $() milik Datatables, bukan jQuery global: ia mencakup baris di
        // SEMUA halaman tabel, bukan hanya yang sedang tampak.
        tabel.$('tr.table-active').removeClass('table-active');
        bilah.innerHTML = petunjukAwal;
    }

    function pilih(tr, data) {
        // Bersihkan dulu, baru tandai: inilah yang membuat pemilihan selalu
        // TUNGGAL tanpa perlu ekstensi Select.
        tabel.$('tr.table-active').removeClass('table-active');
        tr.classList.add('table-active');

        // Server sudah mengirim HTML tombol lengkap dengan @csrf dan
        // pemeriksaan izin. Merakitnya ulang di sini berarti menduplikasi
        // logika otorisasi ke tempat yang tidak bisa diuji.
        bilah.innerHTML = data.action || petunjukAwal;
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
