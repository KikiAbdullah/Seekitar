<?php
require __DIR__.'/../../seekitar-server/vendor/autoload.php';
$app = require __DIR__.'/../../seekitar-server/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$conn = DB::connection('mysql');
// pretend() tidak pernah memakai PDO, tapi Laravel tetap membuka koneksi saat
// introspeksi. Menyetel PDO palsu membuat seluruh DDL bisa diambil offline.
$conn->setPdo(null);

$files = glob(__DIR__.'/../../seekitar-server/database/migrations/2026_07_27_*.php');
sort($files);

$queries = $conn->pretend(function () use ($files, $conn) {
    foreach ($files as $f) {
        $m = require $f;
        $m->up();
    }
});

foreach ($queries as $q) echo $q['query'], ";\n";
