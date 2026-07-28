<?php
/**
 * Kompilasi setiap template Blade lalu periksa sintaks PHP hasilnya.
 *
 * KENAPA PERLU: berkas .blade.php tidak pernah diperiksa `php -l` — isinya
 * baru menjadi PHP setelah dikompilasi. Direktif yang salah pasang (@endif
 * hilang, @json dengan closure multi-baris) baru meledak saat halamannya
 * dibuka di browser, yang di sandbox ini tidak pernah terjadi.
 *
 * Dipakai oleh check-http.mjs; bisa juga dijalankan langsung:
 *   ./tools/dev/php tools/dev/compile-blade.php
 *
 * Keluar dengan kode 1 bila ada yang gagal, supaya bisa dipakai di CI.
 */
$root = __DIR__.'/../../seekitar-server';
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Blade;

$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/resources/views'));
foreach ($it as $f) if (str_ends_with($f->getFilename(), '.blade.php')) $files[] = $f->getPathname();
sort($files);

$bad = 0;
foreach ($files as $f) {
    $compiled = Blade::compileString(file_get_contents($f));
    $tmp = tempnam(sys_get_temp_dir(), 'blade').'.php';
    file_put_contents($tmp, $compiled);

    // php -l tidak bisa dipanggil (WASM tanpa subprocess), jadi pakai
    // token_get_all() yang melempar ParseError pada sintaks rusak.
    try {
        token_get_all($compiled, TOKEN_PARSE);
    } catch (ParseError $e) {
        echo 'RUSAK: ', str_replace($root.'/', '', $f), ' -> ', $e->getMessage(), PHP_EOL;
        $bad++;
    }
    @unlink($tmp);
}
echo count($files), " blade dikompilasi, {$bad} gagal parse\n";

exit($bad === 0 ? 0 : 1);
