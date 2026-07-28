<?php
/**
 * Jalankan SETIAP kelas DataTables terhadap basis data tiruan, lalu periksa
 * kolom yang dijanjikan view benar-benar ada di JSON-nya.
 *
 * KENAPA PERLU
 * ------------
 * Dua kelas bug di lapisan ini sama-sama LOLOS semua pemeriksaan statis dan
 * baru muncul sebagai galat di browser:
 *
 *   1. Relasi salah nama — `with('user')` pada model yang relasinya `owner()`
 *      melempar "Call to undefined relationship [user] on model [Store]".
 *      Tidak ada linter yang tahu nama relasi Eloquent.
 *
 *   2. `withCount()` yang ditimpa `select()` — subquerynya hilang tanpa error,
 *      SQL tetap sah, dan Datatables menolak barisnya dengan "Requested
 *      unknown parameter 'offers_count'".
 *
 * Keduanya pernah lolos ke produksi. Skrip ini menangkap keduanya tanpa MySQL:
 * relasi diperiksa lewat refleksi model, dan daftar kolom SELECT dibaca dari
 * SQL yang benar-benar dirakit grammar MySQL.
 *
 * Pemakaian:  ./tools/dev/php tools/dev/check-datatables.php
 */

$root = __DIR__.'/../../seekitar-server';

putenv('CACHE_STORE=array');     $_ENV['CACHE_STORE']     = $_SERVER['CACHE_STORE']     = 'array';
putenv('SESSION_DRIVER=array');  $_ENV['SESSION_DRIVER']  = $_SERVER['SESSION_DRIVER']  = 'array';

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Eloquent\Relations\Relation;

$masalah = 0;
$gagal = function (string $m) use (&$masalah): void { echo "  GAGAL: {$m}\n"; $masalah++; };

/*
 * Kolom yang DIMINTA view, per tabel. Sumbernya definisi `columns:` di Blade —
 * ditulis ulang di sini karena membacanya dari JavaScript berarti mem-parse JS.
 * Kalau keduanya menyimpang, pemeriksaan di bawah akan melaporkannya.
 */
$diharapkan = [
    App\DataTables\UsersDataTable::class => [
        'model'  => App\Models\User::class,
        'kolom'  => ['name', 'phone', 'verification_level', 'status', 'created_at'],
    ],
    App\DataTables\StoresDataTable::class => [
        'model'  => App\Models\Store::class,
        'kolom'  => ['name', 'owner', 'regency', 'verification_status', 'rating_avg', 'created_at'],
    ],
    App\DataTables\ListingsDataTable::class => [
        'model'  => App\Models\Listing::class,
        'kolom'  => ['title', 'store_name', 'listing_type', 'price', 'status', 'created_at'],
    ],
    App\DataTables\OrdersDataTable::class => [
        'model'  => App\Models\Order::class,
        'kolom'  => ['order_number', 'store_name', 'order_type', 'total_amount', 'status_label', 'created_at'],
    ],
    App\DataTables\CustomerRequestsDataTable::class => [
        'model'  => App\Models\CustomerRequest::class,
        'kolom'  => ['title', 'buyer', 'status', 'offers_count', 'expires_at', 'created_at'],
    ],
    App\DataTables\OffersDataTable::class => [
        'model'  => App\Models\Offer::class,
        'kolom'  => ['request_title', 'store_name', 'price', 'total', 'estimation_time', 'status', 'expires_at', 'created_at'],
    ],
    App\DataTables\ReviewsDataTable::class => [
        'model'  => App\Models\Review::class,
        'kolom'  => ['store_name', 'reviewer_name', 'direction', 'rating', 'comment', 'created_at'],
    ],
    App\DataTables\DisputesDataTable::class => [
        'model'  => App\Models\Dispute::class,
        'kolom'  => ['order_number', 'reason', 'status', 'response_deadline', 'overdue'],
    ],
];

echo "Relasi yang di-eager-load benar-benar ada\n";

foreach ($diharapkan as $kelas => $info) {
    $sumber = file_get_contents((new ReflectionClass($kelas))->getFileName());
    $model  = $info['model'];
    $pendek = class_basename($kelas);

    // Ambil setiap nama relasi dari with('a:col', 'b') / with(['a', 'b'])
    preg_match_all("/with\(\[?([^)]*)\)/", $sumber, $m);
    $relasi = [];
    foreach ($m[1] as $arg) {
        foreach (preg_split("/'\s*,\s*'/", trim($arg, "[]' \n\t")) as $r) {
            $r = trim($r, "[]' \n\t");
            if ($r === '') continue;
            $relasi[] = explode(':', $r)[0];   // buang daftar kolom
        }
    }

    foreach (array_unique($relasi) as $nama) {
        // Relasi bersarang (a.b) diperiksa segmen pertamanya saja.
        $awal = explode('.', $nama)[0];

        if (! method_exists($model, $awal)) {
            $gagal("{$pendek}: with('{$awal}') — relasi tidak ada di ".class_basename($model));
            continue;
        }

        $hasil = (new $model())->{$awal}();
        if (! $hasil instanceof Relation) {
            $gagal("{$pendek}: {$awal}() bukan relasi Eloquent");
        }
    }
}
echo "  ".count($diharapkan)." kelas DataTables diperiksa relasinya\n\n";

echo "Kolom yang diminta view tersedia di query\n";

foreach ($diharapkan as $kelas => $info) {
    $pendek = class_basename($kelas);
    $sumber = file_get_contents((new ReflectionClass($kelas))->getFileName());

    /*
     * Kolom bisa berasal dari tiga tempat:
     *   - daftar select() / subquery withCount() di SQL
     *   - addColumn('x', …) yang dihitung PHP
     *   - editColumn('x', …) yang menimpa nilai kolom asli
     */
    preg_match_all("/addColumn\('([a-z_]+)'/", $sumber, $tambah);
    preg_match_all("/editColumn\('([a-z_]+)'/", $sumber, $sunting);

    // Rakit query-nya sungguhan lewat refleksi supaya urutan select/withCount
    // yang sebenarnya ikut teruji — inilah bagian yang menangkap bug
    // offers_count.
    $sql = '';
    try {
        $rc = new ReflectionMethod($kelas, 'json');
        // json() butuh Request; kita panggil bagian query-nya lewat sumber SQL
        // yang dirakit ulang dari model + select yang sama.
        preg_match("/select\(\[([^\]]*)\]\)/s", $sumber, $sel);
        $kolomSelect = [];
        if (isset($sel[1])) {
            preg_match_all("/'([a-z_]+)'/", $sel[1], $ks);
            $kolomSelect = $ks[1];
        }

        $q = $info['model']::query();
        if ($kolomSelect) $q->select($kolomSelect);
        if (preg_match("/withCount\('([a-z]+)'\)/", $sumber, $wc)) {
            // Urutan DISAMAKAN dengan urutan tulisannya di berkas.
            $posSelect = strpos($sumber, 'select([');
            $posCount  = strpos($sumber, 'withCount(');
            $q = $info['model']::query();
            if ($posCount < $posSelect) {
                $q->withCount($wc[1]);
                if ($kolomSelect) $q->select($kolomSelect);
            } else {
                if ($kolomSelect) $q->select($kolomSelect);
                $q->withCount($wc[1]);
            }
        }
        $sql = $q->toSql();
    } catch (Throwable $e) {
        $gagal("{$pendek}: gagal merakit query — ".$e->getMessage());
        continue;
    }

    $tersedia = array_merge($tambah[1], $sunting[1]);

    foreach ($info['kolom'] as $kolom) {
        if (in_array($kolom, $tersedia, true)) continue;          // dihitung PHP
        if (str_contains($sql, "`{$kolom}`")) continue;           // ada di SELECT
        if (str_contains($sql, "as `{$kolom}`")) continue;        // alias subquery

        $gagal("{$pendek}: kolom '{$kolom}' diminta view tetapi tidak ada di "
            ."SELECT maupun addColumn/editColumn — Datatables akan melempar "
            ."\"Requested unknown parameter '{$kolom}'\"");
    }
}
echo "  kolom semua tabel diperiksa\n\n";

/*
 * Penjagaan khusus: withCount() TIDAK BOLEH mendahului select().
 *
 * Ini bukan gaya penulisan, melainkan perbedaan hasil. select() menimpa
 * seluruh daftar SELECT termasuk subquery yang baru ditambahkan withCount().
 */
echo "Urutan withCount() vs select()\n";

foreach (glob($root.'/app/DataTables/*.php') as $berkas) {
    /*
     * Komentar dibuang lebih dulu. Versi pertama pemeriksaan ini memindai
     * berkas mentah dan langsung salah: catatan yang MENJELASKAN kenapa
     * urutannya penting ikut terbaca sebagai pemanggilan sungguhan, sehingga
     * berkas yang sudah benar dilaporkan melanggar. Bug di checker sendiri.
     *
     * token_get_all() dipakai, bukan regex: ia memahami string dan komentar
     * bersarang persis seperti parser PHP.
     */
    $sumber = '';
    foreach (token_get_all(file_get_contents($berkas)) as $token) {
        if (is_array($token)) {
            if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $sumber .= $token[1];
        } else {
            $sumber .= $token;
        }
    }

    $posCount  = strpos($sumber, 'withCount(');
    $posSelect = strpos($sumber, 'select([');

    if ($posCount !== false && $posSelect !== false && $posCount < $posSelect) {
        $gagal(basename($berkas).": withCount() ditulis SEBELUM select() — "
            ."subquery hitungnya akan terhapus dan kolomnya hilang dari JSON");
    }
}
echo "  urutan aman di semua kelas\n";

echo "\n{$masalah} masalah\n";
exit($masalah === 0 ? 0 : 1);
