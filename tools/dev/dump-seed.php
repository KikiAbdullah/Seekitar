<?php

/**
 * Jalankan seeder TANPA server MySQL dan cetak SQL tulis yang dihasilkannya.
 *
 * KENAPA TIDAK PAKAI pretend() SEPERTI dump-ddl.php
 * -------------------------------------------------
 * Seeder idempoten memakai `firstOrCreate`/`updateOrCreate`, yang menjalankan
 * SELECT lebih dulu. Di dalam pretend(), SELECT tidak pernah dieksekusi dan
 * Laravel tetap mencoba membuka PDO — skrip mati dengan "RuntimeError:
 * unreachable".
 *
 * Sebagai gantinya koneksi diganti subclass yang:
 *   - membalas semua SELECT dengan array kosong (seolah tabel masih kosong,
 *     yaitu jalur INSERT yang memang ingin diperiksa), dan
 *   - mencatat setiap INSERT/UPDATE/DELETE alih-alih mengirimkannya.
 *
 * Yang dibuktikan: seeder berjalan sampai selesai, memakai kolom yang benar,
 * dan SQL-nya dirakit grammar MySQL. Yang TIDAK dibuktikan: perilaku
 * constraint dan keunikan — itu tetap butuh MySQL sungguhan.
 *
 * Pemakaian:
 *   ./tools/dev/php tools/dev/dump-seed.php 'Database\Seeders\CategorySeeder'
 */

$root = __DIR__.'/../../seekitar-server';

putenv('CACHE_STORE=array');     $_ENV['CACHE_STORE'] = $_SERVER['CACHE_STORE'] = 'array';
putenv('SESSION_DRIVER=array');  $_ENV['SESSION_DRIVER'] = $_SERVER['SESSION_DRIVER'] = 'array';
putenv('QUEUE_CONNECTION=sync'); $_ENV['QUEUE_CONNECTION'] = $_SERVER['QUEUE_CONNECTION'] = 'sync';
putenv('APP_ENV=local');         $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'local';

require $root.'/vendor/autoload.php';

use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;

/** Koneksi yang mencatat operasi tulis dan tidak pernah menyentuh jaringan. */
final class RecordingConnection extends MySqlConnection
{
    /** @var array<int, array{sql:string, bindings:array}> */
    public array $writes = [];

    /**
     * Baris hasil INSERT, dikelompokkan per tabel.
     *
     * Dipakai agar SELECT berikutnya bisa "menemukan" baris yang baru saja
     * ditulis seeder. Tanpa ini, seeder berantai (DummyDataSeeder mencari
     * kategori buatan CategorySeeder) berhenti di firstOrFail().
     *
     * @var array<string, array<int, object>>
     */
    public array $rows = [];

    private int $autoId = 0;

    public function select($query, $bindings = [], $useReadPdo = true, array $fetchUsing = [])
    {
        $row = $this->lookup($query);

        return $row ? [$row] : [];
    }

    public function selectOne($query, $bindings = [], $useReadPdo = true)
    {
        return $this->lookup($query);
    }

    /**
     * Balas SELECT dengan satu baris dari tabel yang diminta, bila ada.
     *
     * Sengaja TIDAK mengevaluasi klausa WHERE — tujuannya hanya membuat
     * seeder berjalan sampai tuntas, bukan meniru MySQL. Karena itu hasil
     * skrip ini tidak bisa dipakai menilai kebenaran query.
     */
    private function lookup(string $query): ?object
    {
        if (! preg_match('/from `([a-z_]+)`/i', $query, $m)) {
            return null;
        }

        return $this->rows[$m[1]][0] ?? null;
    }

    public function insert($query, $bindings = [], $sequence = null)
    {
        $this->writes[] = ['sql' => $query, 'bindings' => $bindings];
        $this->remember($query, $bindings);

        return true;
    }

    /** Simpan baris yang di-INSERT supaya SELECT setelahnya menemukannya. */
    private function remember(string $query, array $bindings): void
    {
        if (! preg_match('/insert into `([a-z_]+)` \(([^)]*)\)/i', $query, $m)) {
            return;
        }

        $table   = $m[1];
        $columns = array_map(
            static fn (string $c): string => trim($c, " `"),
            explode(',', $m[2])
        );

        $row = [];
        foreach ($columns as $i => $col) {
            $row[$col] = $bindings[$i] ?? null;
        }
        // Tabel ber-auto-increment tidak mengirim `id` di INSERT.
        $row['id'] ??= ++$this->autoId;

        $this->rows[$table][] = (object) $row;
    }

    public function getLastInsertId($sequence = null)
    {
        return $this->autoId;
    }

    public function update($query, $bindings = [])
    {
        $this->writes[] = ['sql' => $query, 'bindings' => $bindings];

        return 1;
    }

    public function delete($query, $bindings = [])
    {
        $this->writes[] = ['sql' => $query, 'bindings' => $bindings];

        return 1;
    }

    public function statement($query, $bindings = [])
    {
        $this->writes[] = ['sql' => $query, 'bindings' => $bindings];

        return true;
    }

    public function affectingStatement($query, $bindings = [])
    {
        $this->writes[] = ['sql' => $query, 'bindings' => $bindings];

        return 1;
    }

    /** Seeder membungkus kerjanya dalam transaksi; jangan sentuh PDO. */
    public function transaction(\Closure $callback, $attempts = 1)
    {
        return $callback($this);
    }

    public function beginTransaction() {}

    public function commit() {}

    public function rollBack($toLevel = null) {}

    public function getPdo()
    {
        return null;
    }
}

$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Daftarkan resolver sebelum koneksi pertama dibuat.
Illuminate\Database\Connection::resolverFor('mysql', function ($pdo, $database, $prefix, $config) {
    return new RecordingConnection($pdo, $database, $prefix, $config);
});
DB::purge('mysql');

/** @var RecordingConnection $conn */
$conn = DB::connection('mysql');

$class = $argv[1] ?? 'Database\Seeders\DatabaseSeeder';
$seeder = $app->make($class);
$seeder->setContainer($app);
$seeder->__invoke();

foreach ($conn->writes as $w) {
    $sql = $w['sql'];
    foreach ($w['bindings'] as $b) {
        if (is_null($b))              $val = 'NULL';
        elseif (is_bool($b))          $val = $b ? '1' : '0';
        elseif (is_numeric($b))       $val = (string) $b;
        elseif ($b instanceof DateTimeInterface) $val = "'".$b->format('Y-m-d H:i:s')."'";
        else                          $val = "'".str_replace("'", "''", (string) $b)."'";
        $sql = preg_replace('/\?/', str_replace('$', '\$', $val), $sql, 1);
    }
    echo $sql, ";\n";
}

fwrite(STDERR, sprintf("\n-- %d operasi tulis dari %s\n", count($conn->writes), $class));
