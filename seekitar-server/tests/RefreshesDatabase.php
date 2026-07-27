<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pengganti Illuminate\Foundation\Testing\RefreshDatabase.
 *
 * KENAPA: RefreshDatabase bawaan memanggil `migrate:fresh` lewat mekanisme
 * yang membuat runtime PHP-WASM di sandbox ini crash ("RuntimeError:
 * unreachable"). Di mesin dengan PHP native, RefreshDatabase bawaan tetap
 * bisa dipakai.
 *
 * Skema dibangun SEKALI per proses (migrate:fresh), lalu tiap test dibungkus
 * transaksi yang di-rollback. Menjalankan migrasi ulang tiap test akan sangat
 * lambat di MySQL — berbeda dari SQLite :memory: yang dulu memang harus
 * dibangun ulang tiap kali karena basis datanya ikut hilang tiap koneksi.
 */
trait RefreshesDatabase
{
    private static bool $schemaReady = false;

    protected function setUpRefreshesDatabase(): void
    {
        $this->guardTestDatabase();

        if (! self::$schemaReady) {
            // --drop-views & --drop-types tidak dipakai: skema Seekitar tidak
            // punya view, dan --drop-types khusus PostgreSQL.
            Artisan::call('migrate:fresh', ['--force' => true]);
            self::$schemaReady = true;
        }

        DB::beginTransaction();
    }

    protected function tearDownRefreshesDatabase(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }

    /**
     * Cegah test menghapus isi basis data pengembangan.
     *
     * `migrate:fresh` men-DROP semua tabel. Kalau phpunit.xml salah konfigurasi
     * dan menunjuk ke basis data yang dipakai sehari-hari, seluruh data hilang
     * tanpa peringatan — jadi namanya wajib mengandung 'test'.
     */
    private function guardTestDatabase(): void
    {
        $connection = config('database.default');

        if (config("database.connections.$connection.driver") !== 'mysql') {
            throw new RuntimeException(
                'Seekitar hanya mendukung MySQL. Jalankan test terhadap MySQL 8.0.34+ '
                .'(lihat CONTRIBUTING.md "Menyiapkan MySQL untuk test").'
            );
        }

        $database = config("database.connections.$connection.database");

        if (! str_contains(strtolower((string) $database), 'test')) {
            throw new RuntimeException(
                "Menolak menjalankan migrate:fresh pada basis data '{$database}' — "
                ."namanya harus mengandung 'test'. Ini mencegah test menghapus data pengembangan."
            );
        }
    }
}
