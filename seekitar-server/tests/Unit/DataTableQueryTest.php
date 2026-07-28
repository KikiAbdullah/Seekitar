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
