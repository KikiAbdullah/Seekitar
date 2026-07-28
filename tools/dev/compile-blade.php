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

/*
 * Kompilasi saja TIDAK cukup.
 *
 * `@php` yang ditulis di dalam komentar {{-- --}} tetap dibaca sebagai
 * direktif dan membuka blok yang tak pernah tertutup. Hasil kompilasinya
 * masih PHP yang sah, jadi lolos pemeriksaan sintaks — tetapi meledak dengan
 * "Cannot end a section without first starting one" begitu di-render.
 *
 * Karena itu halaman publik ikut benar-benar di-render di sini.
 */
$renderable = [
    'web.home'    => ['categories' => collect()],
    'web.about'   => [],
    'web.help'    => [],
    'web.contact' => [],
    'web.privacy' => [],
    'web.terms'   => [],
    'web.sitemap' => ['pages' => [['loc' => 'https://seekitar.id/', 'freq' => 'daily', 'priority' => '1.0']]],
];

$renderFailed = 0;
foreach ($renderable as $view => $data) {
    try {
        Illuminate\Support\Facades\View::make($view, $data)->render();
    } catch (Throwable $e) {
        echo 'GAGAL RENDER: ', $view, ' -> ', $e->getMessage(), PHP_EOL;
        $renderFailed++;
    }
}
echo count($renderable), " halaman dirender, {$renderFailed} gagal render\n";

$bad += $renderFailed;

exit($bad === 0 ? 0 : 1);
