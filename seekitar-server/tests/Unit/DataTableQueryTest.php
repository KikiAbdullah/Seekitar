<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Dua bug DataTables yang lolos SEMUA pemeriksaan statis dan hanya muncul
 * sebagai galat di browser. Keduanya sudah pernah terjadi.
 *
 * 1. Relasi salah nama — `with('user')` pada Store yang relasinya `owner()`:
 *
 *        Call to undefined relationship [user] on model [App\Models\Store].
 *
 *    Tidak tertangkap `toSql()` sekalipun: eager load bersifat malas, jadi
 *    exception-nya baru dilempar ketika baris benar-benar diambil. Itulah
 *    sebabnya ia lolos sampai ke pengguna.
 *
 * 2. `withCount()` ditimpa `select()` — subquerynya hilang tanpa error dan
 *    Datatables menolak barisnya:
 *
 *        Requested unknown parameter 'offers_count' for row 0, column 3.
 *
 * Test ini memeriksa sumbernya secara tekstual supaya tetap jalan tanpa MySQL.
 * Pemeriksaan yang benar-benar merakit query ada di
 * tools/dev/check-datatables.php dan tools/dev/check-relations.php.
 */
class DataTableQueryTest extends TestCase
{
    /** @return list<string> */
    private function berkasDataTable(): array
    {
        return glob(__DIR__.'/../../app/DataTables/*.php') ?: [];
    }

    /** Sumber tanpa komentar — catatan penjelas bukan kode. */
    private function tanpaKomentar(string $berkas): string
    {
        $keluar = '';

        foreach (token_get_all(file_get_contents($berkas)) as $t) {
            if (is_array($t)) {
                if (in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $keluar .= $t[1];
            } else {
                $keluar .= $t;
            }
        }

        return $keluar;
    }

    public function test_withcount_tidak_pernah_mendahului_select(): void
    {
        $this->assertNotEmpty($this->berkasDataTable());

        foreach ($this->berkasDataTable() as $berkas) {
            $src = $this->tanpaKomentar($berkas);

            $posCount  = strpos($src, 'withCount(');
            $posSelect = strpos($src, 'select([');

            if ($posCount === false || $posSelect === false) {
                continue;
            }

            $this->assertGreaterThan(
                $posSelect,
                $posCount,
                basename($berkas).': withCount() ditulis sebelum select(). '
                .'select() menimpa seluruh daftar SELECT termasuk subquery hitungnya, '
                .'sehingga kolomnya hilang dari JSON dan Datatables melempar '
                .'"Requested unknown parameter".',
            );
        }
    }

    public function test_store_dieagerload_lewat_relasi_owner(): void
    {
        // Store::owner(), BUKAN Store::user(). Kolomnya memang `user_id`,
        // dan itulah yang membuat kekeliruan ini mudah terjadi.
        foreach ([
            __DIR__.'/../../app/DataTables/StoresDataTable.php',
            __DIR__.'/../../app/Http/Controllers/Admin/VerificationController.php',
        ] as $berkas) {
            $src = $this->tanpaKomentar($berkas);

            $this->assertDoesNotMatchRegularExpression(
                "/->with\(\s*'user(:|')/",
                $src,
                basename($berkas).": memakai with('user') untuk Store — "
                .'relasinya bernama owner(). Ini melempar '
                .'"Call to undefined relationship [user]" saat baris diambil.',
            );
        }
    }

    public function test_relasi_store_bernama_owner(): void
    {
        $model = new ReflectionClass(\App\Models\Store::class);

        $this->assertTrue(
            $model->hasMethod('owner'),
            'Store::owner() hilang — semua eager load pemilik toko akan gagal.',
        );

        $this->assertFalse(
            $model->hasMethod('user'),
            'Store::user() ditambahkan. Kalau memang disengaja sebagai alias, '
            .'perbarui test ini; kalau tidak, dua nama untuk satu relasi hanya '
            .'membuat separuh kode memakai yang salah.',
        );
    }

    public function test_action_bukan_lagi_kolom_tabel(): void
    {
        // Tombol aksi kini muncul di bilah sebelah judul saat baris dipilih.
        // HTML-nya TETAP dikirim server di field `action`, tetapi field itu
        // tidak boleh didaftarkan sebagai kolom — kalau didaftarkan, kolom
        // tombol kembali dan polanya jadi setengah jadi.
        foreach (glob(__DIR__.'/../../resources/views/admin/*/index.blade.php') as $view) {
            $src = preg_replace('/\{\{--[\s\S]*?--\}\}/', '', file_get_contents($view));

            $this->assertStringNotContainsString(
                "'data' => 'action'",
                $src,
                basename(dirname($view)).'/index.blade.php masih mendaftarkan kolom action.',
            );
        }
    }

    public function test_partial_aksi_selalu_dibungkus_can(): void
    {
        $partials = glob(__DIR__.'/../../resources/views/admin/*/_actions.blade.php');

        $this->assertNotEmpty($partials);

        foreach ($partials as $partial) {
            $src = preg_replace('/\{\{--[\s\S]*?--\}\}/', '', file_get_contents($partial));

            // Bilah aksi hanyalah tempat menampilkan, bukan pengganti
            // otorisasi. Tanpa @can, admin tanpa izin ikut melihat tombolnya.
            $this->assertMatchesRegularExpression(
                '/@can(any)?\(/',
                $src,
                basename(dirname($partial)).'/_actions.blade.php tidak dibungkus @can.',
            );
        }
    }

    public function test_html_tidak_dioper_antar_view_sebagai_variabel(): void
    {
        /*
         * `'filter' => view('x')` lalu ditampilkan dengan kurung-kurawal-ganda
         * membuat SELURUH filter tampil sebagai teks mentah (&lt;select&gt;).
         *
         * Sebabnya @include me-render sub-view menjadi string lebih dulu;
         * objek View yang Htmlable tidak pernah sampai ke tahap escaping,
         * yang sampai adalah string biasa — dan string biasa memang di-escape.
         *
         * Halamannya tetap "berhasil dirender", jadi render harness pun tidak
         * mengeluhkannya. Perbaikannya bukan {!! !!} (mematikan escaping)
         * melainkan mengoper NAMA view lalu @includeIf.
         */
        foreach (glob(__DIR__.'/../../resources/views/admin/*/index.blade.php') as $view) {
            $src = preg_replace('/\{\{--[\s\S]*?--\}\}/', '', file_get_contents($view));

            $this->assertDoesNotMatchRegularExpression(
                "/'\w+'\s*=>\s*view\(/",
                $src,
                basename(dirname($view)).'/index.blade.php mengoper objek view sebagai variabel.',
            );
        }
    }

    public function test_bilah_aksi_tanpa_teks_petunjuk_dan_nama(): void
    {
        $partial = file_get_contents(__DIR__.'/../../resources/views/admin/partials/table-page.blade.php');

        // Bilah aksi kosong sampai ada baris dipilih; tingginya dijaga CSS.
        $this->assertStringNotContainsString('Pilih satu baris', $partial);

        // Nama entitas tidak ditempel di samping tombol.
        foreach (glob(__DIR__.'/../../resources/views/admin/*/_actions.blade.php') as $aksi) {
            $this->assertStringNotContainsString(
                'text-muted small ms-1',
                file_get_contents($aksi),
                basename(dirname($aksi)).'/_actions.blade.php masih menempelkan nama entitas.',
            );
        }
    }

    public function test_semua_select_memakai_select2(): void
    {
        $berkas = array_merge(
            glob(__DIR__.'/../../resources/views/admin/*/*.blade.php') ?: [],
            glob(__DIR__.'/../../resources/views/admin/*.blade.php') ?: [],
        );

        // Tanpa ini test lolos secara palsu bila glob-nya salah dan tidak
        // ada berkas yang terperiksa sama sekali.
        $this->assertGreaterThan(30, count($berkas), 'Glob view admin tidak menemukan berkas.');

        $diperiksa = 0;

        foreach ($berkas as $view) {
            $src = preg_replace(
                ['/\{\{--[\s\S]*?--\}\}/', '/\{\{[\s\S]*?\}\}/', '/@\w+\([^)]*\)/'],
                ['', 'X', 'X'],
                file_get_contents($view),
            );

            preg_match_all('/<select\b[^>]*?>/', $src, $cocok);

            foreach ($cocok[0] as $tag) {
                $this->assertStringContainsString(
                    'js-select2',
                    $tag,
                    basename(dirname($view)).'/'.basename($view).' punya <select> tanpa kelas js-select2.',
                );
            }
        }
    }

    public function test_filter_tabel_memakai_listener_jquery(): void
    {
        $partial = $this->tanpaKomentar(
            __DIR__.'/../../resources/views/admin/partials/table-page.blade.php'
        );

        /*
         * Select2 mengganti nilai lewat `$el.trigger('change')` milik jQuery,
         * dan event sintetis itu TIDAK menyentuh listener native.
         * Diverifikasi di jsdom: addEventListener terpanggil 0 kali, jQuery
         * .on 1 kali. Memakai yang salah membuat seluruh filter tabel berhenti
         * bekerja tanpa satu pun pesan error.
         */
        $this->assertStringNotContainsString(
            "addEventListener('change'",
            $partial,
            'Filter memakai listener native — Select2 tidak akan memicunya.',
        );

        $this->assertStringContainsString("data-dt-filter", $partial);
    }

    public function test_blade_admin_bersih_dari_komentar_naratif(): void
    {
        $berdokumen = ['_datatable.blade.php', 'table-page.blade.php', '_reject_modal.blade.php'];

        $berkas = array_merge(
            glob(__DIR__.'/../../resources/views/admin/*/*.blade.php') ?: [],
            glob(__DIR__.'/../../resources/views/admin/*.blade.php') ?: [],
        );

        // Tanpa ini test lolos secara palsu bila glob-nya salah dan tidak
        // ada berkas yang terperiksa sama sekali.
        $this->assertGreaterThan(30, count($berkas), 'Glob view admin tidak menemukan berkas.');

        $diperiksa = 0;

        foreach ($berkas as $view) {
            if (in_array(basename($view), $berdokumen, true)) {
                continue;
            }

            $diperiksa++;

            preg_match_all('/\{\{--([\s\S]*?)--\}\}/', file_get_contents($view), $cocok);

            foreach ($cocok[1] as $isi) {
                $baris = count(explode("\n", trim($isi)));

                $this->assertLessThanOrEqual(
                    2,
                    $baris,
                    basename(dirname($view)).'/'.basename($view)
                    .' memuat komentar naratif. View adalah lapisan presentasi — '
                    .'alasan teknis tempatnya di controller atau Server_Implementation_Guide.md.',
                );

                $this->assertDoesNotMatchRegularExpression(
                    '/KENAPA|Sebabnya|Diverifikasi|Versi sebelumnya|TODO_BUG|jebakan/u',
                    $isi,
                    basename(dirname($view)).'/'.basename($view).' memuat catatan investigasi.',
                );
            }
        }

        $this->assertGreaterThan(30, $diperiksa, 'Terlalu sedikit view yang diperiksa.');
    }

    public function test_kolom_offers_count_dijamin_ada_nilainya(): void
    {
        $src = $this->tanpaKomentar(__DIR__.'/../../app/DataTables/CustomerRequestsDataTable.php');

        // Jaring pengaman: null pada kolom yang diminta Datatables tetap
        // memicu "Requested unknown parameter".
        $this->assertStringContainsString(
            "editColumn('offers_count'",
            $src,
            'offers_count tidak diberi nilai default — baris tanpa penawaran bisa mengirim null.',
        );
    }
}
