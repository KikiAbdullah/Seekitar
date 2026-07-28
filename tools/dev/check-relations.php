<?php
/**
 * Verifikasi setiap relasi yang di-eager-load benar-benar ADA di modelnya.
 *
 * KENAPA PERLU
 * ------------
 * `with('user')` pada model yang relasinya bernama `owner()` tidak ditolak
 * PHP, tidak ditolak linter, dan tidak muncul di test statis mana pun. Ia
 * meledak saat halaman dibuka:
 *
 *   Call to undefined relationship [user] on model [App\Models\Store].
 *
 * Sudah terjadi di StoresDataTable dan VerificationController.
 *
 * CARA KERJA — dan kenapa BUKAN sekadar grep
 * ------------------------------------------
 * Versi pertama pemeriksaan ini memakai regex atas seluruh berkas, dan hasilnya
 * tidak terpakai: `back()->with('success', …)` dan `$view->with([...])` ikut
 * tertangkap, begitu pula `with()` milik model lain di berkas yang sama.
 *
 * Di sini rantainya dilacak sungguhan: skrip mencari `NamaModel::` lalu
 * membaca maju sampai akhir statement, dan hanya `->with(...)` DI DALAM
 * rantai itu yang diperiksa terhadap relasi milik model tersebut.
 *
 * Pemakaian:  ./tools/dev/php tools/dev/check-relations.php
 */

$root = __DIR__.'/../../seekitar-server';

putenv('CACHE_STORE=array');    $_ENV['CACHE_STORE']    = $_SERVER['CACHE_STORE']    = 'array';
putenv('SESSION_DRIVER=array'); $_ENV['SESSION_DRIVER'] = $_SERVER['SESSION_DRIVER'] = 'array';

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Eloquent\Relations\Relation;

/** Nama relasi yang benar-benar terdefinisi pada sebuah model. */
function relasiModel(string $kelas): array
{
    $hasil = [];

    foreach ((new ReflectionClass($kelas))->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
        if ($m->getNumberOfParameters() > 0) {
            continue;
        }

        $tipe = $m->getReturnType();

        // Relasi dikenali dari tipe kembaliannya, bukan dari namanya —
        // itulah satu-satunya penanda yang tidak bisa salah.
        if ($tipe instanceof ReflectionNamedType && is_subclass_of($tipe->getName(), Relation::class)) {
            $hasil[] = $m->getName();
        }
    }

    return $hasil;
}

$modelDir = $root.'/app/Models';
$peta = [];

foreach (glob($modelDir.'/*.php') as $berkas) {
    $kelas = 'App\\Models\\'.basename($berkas, '.php');
    if (class_exists($kelas)) {
        $peta[$kelas] = relasiModel($kelas);
    }
}

echo count($peta), " model dipindai\n\n";

$masalah = 0;

/** Buang komentar supaya catatan penjelas tidak terbaca sebagai kode. */
function tanpaKomentar(string $kode): string
{
    $keluar = '';
    foreach (token_get_all($kode) as $t) {
        if (is_array($t)) {
            if (in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                // Baris komentar diganti spasi sepanjang aslinya supaya offset
                // karakter tidak bergeser.
                $keluar .= preg_replace('/\S/', ' ', $t[1]);
            } else {
                $keluar .= $t[1];
            }
        } else {
            $keluar .= $t;
        }
    }

    return $keluar;
}

$berkasPhp = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app')) as $f) {
    if (str_ends_with($f->getFilename(), '.php')) {
        $berkasPhp[] = $f->getPathname();
    }
}
sort($berkasPhp);

$diperiksa = 0;

foreach ($berkasPhp as $berkas) {
    $kode = tanpaKomentar(file_get_contents($berkas));
    $relatif = str_replace($root.'/', '', $berkas);

    foreach ($peta as $kelas => $relasiSah) {
        $pendek = class_basename($kelas);

        // Titik awal rantai: `Store::` (query, where, with, find, …).
        $offset = 0;
        while (($pos = strpos($kode, $pendek.'::', $offset)) !== false) {
            $offset = $pos + 1;

            /*
             * Batas rantai.
             *
             * Memakai `;` saja TIDAK cukup — dan itu bug nyata pada versi
             * pertama skrip ini. Pada potongan:
             *
             *     if (Store::whereRaw(...)->exists()) {
             *         return back()->with('error', '...');
             *
             * `;` terdekat berada di baris BERIKUTNYA, sehingga
             * `back()->with('error')` ikut terbaca sebagai bagian rantai
             * Store dan dilaporkan sebagai relasi yang hilang.
             *
             * Karena itu rantai juga diputus pada `)` yang menutup kurung
             * pembuka sebelum model, pada `{`, dan pada baris baru yang
             * diikuti `return`/`if` — penanda bahwa statement-nya sudah usai.
             */
            $akhir = strlen($kode);
            foreach ([';', "\n        }", '{'] as $penanda) {
                $p = strpos($kode, $penanda, $pos);
                if ($p !== false && $p < $akhir) {
                    $akhir = $p;
                }
            }

            $rantai = substr($kode, $pos, $akhir - $pos);

            // Buang apa pun sesudah `return`/`back(` — itu statement lain.
            foreach (['return ', 'back(', 'redirect('] as $pemutus) {
                $p = strpos($rantai, $pemutus);
                if ($p !== false) {
                    $rantai = substr($rantai, 0, $p);
                }
            }

            // Hanya with()/withCount()/load() DI DALAM rantai ini.
            if (! preg_match_all(
                "/->(?:with|withCount|load|loadCount)\(\s*\[?\s*((?:'[^']*'\s*,?\s*)+)/",
                $rantai,
                $cocok
            )) {
                continue;
            }

            foreach ($cocok[1] as $daftar) {
                preg_match_all("/'([^']+)'/", $daftar, $item);

                foreach ($item[1] as $arg) {
                    // 'store:id,name' → store ; 'offers.store' → offers
                    $nama = explode('.', explode(':', $arg)[0])[0];

                    if ($nama === '' || in_array($nama, $relasiSah, true)) {
                        continue;
                    }

                    $diperiksa++;
                    echo "  GAGAL: {$relatif}\n";
                    echo "         {$pendek} tidak punya relasi '{$nama}'. ";
                    echo 'Yang ada: '.implode(', ', $relasiSah)."\n";
                    $masalah++;
                }
            }
        }
    }
}

echo $masalah === 0
    ? "  semua relasi yang di-eager-load terdefinisi\n"
    : "";

echo "\n{$masalah} masalah\n";
exit($masalah === 0 ? 0 : 1);
