<?php

namespace Tests\Unit;

use App\Http\Controllers\Web\PageController;
use PHPUnit\Framework\TestCase;

/**
 * Kontrak halaman publik yang bisa diperiksa tanpa basis data.
 *
 * Halaman legal & kanal pengaduan bukan pelengkap: Permendag PPMSE
 * mensyaratkannya tersedia di semua platform, dan alamat pengaduan yang
 * tidak aktif dapat menyebabkan penolakan pendaftaran PSE
 * (BRANDING-GUIDELINE.md §8.3).
 */
class WebRoutesContractTest extends TestCase
{
    private function routesSource(): string
    {
        return file_get_contents(__DIR__.'/../../routes/web.php');
    }

    public function test_halaman_wajib_terdaftar(): void
    {
        $src = $this->routesSource();

        foreach (['home', 'about', 'help', 'contact', 'privacy', 'terms'] as $name) {
            $this->assertStringContainsString(
                "->name('{$name}')",
                $src,
                "Halaman '{$name}' belum punya route.",
            );
        }
    }

    public function test_halaman_legal_memakai_url_bahasa_indonesia(): void
    {
        $src = $this->routesSource();

        // URL berbahasa Indonesia: pengguna & regulator membacanya langsung,
        // dan konsisten dengan bahasa antarmuka.
        $this->assertStringContainsString("'/kebijakan-privasi'", $src);
        $this->assertStringContainsString("'/syarat-ketentuan'", $src);
    }

    public function test_berkas_seo_dilayani_route_bukan_berkas_statis(): void
    {
        $src = $this->routesSource();

        // Kalau statis di public/, staging ikut mengizinkan pengindeksan
        // dan bersaing dengan domain aslinya di hasil pencarian.
        $this->assertStringContainsString("'/robots.txt'", $src);
        $this->assertStringContainsString("'/sitemap.xml'", $src);

        $this->assertFileDoesNotExist(
            __DIR__.'/../../public/robots.txt',
            'robots.txt statis akan menimpa route dinamis.',
        );
    }

    public function test_route_admin_tidak_didaftarkan_di_web_php(): void
    {
        // Admin punya berkas sendiri agar web publik tidak ikut terbebani
        // middleware panel (Server_Implementation_Guide §5.1).
        $this->assertStringNotContainsString('Admin\\', $this->routesSource());
    }

    public function test_robots_memblokir_semua_di_luar_produksi(): void
    {
        $method = new \ReflectionMethod(PageController::class, 'robots');
        $source = file_get_contents($method->getFileName());

        $this->assertStringContainsString('isProduction()', $source);
        $this->assertStringContainsString('Disallow: /admin', $source);
        $this->assertStringContainsString('Disallow: /api', $source);
    }

    public function test_kanal_pengaduan_terpusat_di_konfigurasi(): void
    {
        $config = require __DIR__.'/../../config/seekitar.php';

        // Ditulis ulang di tiap template = satu alamat berubah, sisanya
        // tertinggal. Regulator memeriksa alamat yang tercantum.
        foreach (['complaint', 'abuse', 'privacy'] as $channel) {
            $this->assertArrayHasKey($channel, $config['contacts']);
            $this->assertNotEmpty($config['contacts'][$channel]);
        }
    }

    public function test_footer_menampilkan_seluruh_kanal_pengaduan(): void
    {
        $layout = file_get_contents(__DIR__.'/../../resources/views/web/layout.blade.php');

        foreach (['contacts.complaint', 'contacts.abuse', 'contacts.privacy'] as $key) {
            $this->assertStringContainsString(
                "config('seekitar.{$key}')",
                $layout,
                "Footer tidak menampilkan kanal {$key} — kewajiban PSE.",
            );
        }

        // Tautan legal wajib ada di footer setiap halaman.
        $this->assertStringContainsString("route('web.privacy')", $layout);
        $this->assertStringContainsString("route('web.terms')", $layout);
    }

    public function test_view_publik_tidak_memakai_output_tanpa_escaping(): void
    {
        foreach (glob(__DIR__.'/../../resources/views/web/*.blade.php') as $file) {
            // Komentar Blade dibuang: catatan yang MELARANG sintaks itu
            // justru harus menyebutkannya.
            $code = preg_replace('/\{\{--[\s\S]*?--\}\}/', '', file_get_contents($file));

            $this->assertStringNotContainsString(
                '{!!',
                $code,
                basename($file).' memakai {!! !!} — mematikan escaping Blade.',
            );
        }
    }
}
