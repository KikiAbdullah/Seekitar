<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Pengganti Illuminate\Foundation\Testing\RefreshDatabase.
 *
 * KENAPA: RefreshDatabase memakai transaksi yang membungkus seluruh test dan
 * memanggil `migrate:fresh` lewat mekanisme yang membuat runtime PHP-WASM
 * di sandbox ini crash ("RuntimeError: unreachable"). Di mesin dengan PHP
 * native, RefreshDatabase bawaan tetap bisa dipakai.
 *
 * Trait ini menjalankan migrasi sekali per proses, lalu membungkus tiap test
 * dalam transaksi yang di-rollback — efeknya setara untuk isolasi data.
 */
trait RefreshesDatabase
{
    protected function setUpRefreshesDatabase(): void
    {
        // Basis data :memory: hidup selama satu koneksi saja, dan Laravel
        // membuat aplikasi baru untuk SETIAP test — sehingga skemanya harus
        // dibangun ulang tiap kali. Meng-cache flag "sudah migrasi" secara
        // statis justru membuat test kedua dan seterusnya kehilangan tabel.
        Artisan::call('migrate', ['--force' => true]);

        DB::beginTransaction();
    }

    protected function tearDownRefreshesDatabase(): void
    {
        // Rollback mengembalikan basis data ke keadaan semula, sehingga
        // test berikutnya tidak melihat sisa data test sebelumnya.
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }
}
