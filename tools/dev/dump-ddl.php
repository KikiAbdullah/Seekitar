<?php

/**
 * Cetak DDL MySQL dari seluruh migrasi TANPA server MySQL.
 *
 * Laravel `pretend()` menjalankan migrasi lewat grammar MySQL dan menangkap
 * SQL-nya alih-alih mengeksekusi. Ini satu-satunya cara memverifikasi skema
 * di lingkungan yang tidak bisa memasang MySQL.
 *
 * Dijalankan lewat ./tools/dev/ddl
 */

$root = __DIR__.'/../../seekitar-server';

// CACHE_STORE=database membuat cache menembak MySQL. Spatie Permission
// membersihkan cache-nya saat boot, sehingga tanpa penggantian ini seluruh
// skrip mati dengan "RuntimeError: unreachable" dari PDO yang gagal connect.
putenv('CACHE_STORE=array');
$_ENV['CACHE_STORE'] = $_SERVER['CACHE_STORE'] = 'array';
putenv('SESSION_DRIVER=array');
$_ENV['SESSION_DRIVER'] = $_SERVER['SESSION_DRIVER'] = 'array';
putenv('QUEUE_CONNECTION=sync');
$_ENV['QUEUE_CONNECTION'] = $_SERVER['QUEUE_CONNECTION'] = 'sync';

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$conn = DB::connection('mysql');
// pretend() tidak butuh PDO, tetapi Laravel tetap mencoba menyambung saat
// introspeksi. PDO dikosongkan agar seluruh DDL bisa diambil offline.
$conn->setPdo(null);

// Migrasi bawaan Laravel (cache, jobs) ikut agar gambaran skemanya utuh.
$files = glob($root.'/database/migrations/*.php');
sort($files);

$queries = $conn->pretend(function () use ($files) {
    foreach ($files as $f) {
        $migration = require $f;
        $migration->up();
    }
});

foreach ($queries as $q) {
    echo $q['query'], ";\n";
}
