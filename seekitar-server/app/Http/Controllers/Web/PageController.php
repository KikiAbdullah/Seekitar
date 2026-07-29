<?php

namespace App\Http\Controllers\Web;

use App\Enums\ListingStatus;
use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Halaman publik & SEO (`Server_Implementation_Guide.md` §4, PRD §14).
 *
 * Terpisah dari `Admin\` dan `Api\`: halaman ini diakses tanpa autentikasi
 * dan tidak boleh ikut terbebani middleware panel admin.
 */
class PageController extends Controller
{
    /** Cache halaman statis: isinya nyaris tidak berubah, tapi sering dibuka. */
    private const STATIC_CACHE_SECONDS = 3600;

    /**
     * Landing page (BRANDING-GUIDELINE.md §6.3).
     *
     * Kategori diambil dari basis data, bukan ditulis di template: daftar
     * yang berbeda dari isi aplikasi justru merusak kepercayaan.
     *
     * Angka pita kepercayaan pun demikian — tetapi di-cache: landing page
     * adalah halaman paling ramai dibuka, dan angka yang basi 1 jam tidak
     * mengubah keputusan pengunjung apa pun.
     */
    public function home(): View
    {
        $statistik = Cache::remember('web.home.stats', self::STATIC_CACHE_SECONDS, fn () => [
            // Hanya angka yang JUJUR diverifikasi sistem, bukan klaim pemasaran.
            'toko'    => Store::query()
                ->where('status', StoreStatus::Verified->value)
                ->where('is_active', true)
                ->count(),
            'listing' => Listing::query()
                ->where('status', ListingStatus::Active->value)
                ->count(),
        ]);

        return view('web.home', [
            'categories' => Category::query()
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get(),
            'statistik'  => $statistik,
        ]);
    }

    public function about(): View
    {
        return view('web.about');
    }

    /** Pusat Bantuan — dijanjikan PRD §11.4 (transparansi). */
    public function help(): View
    {
        return view('web.help');
    }

    /**
     * Kebijakan Privasi.
     *
     * WAJIB ADA, bukan pelengkap: Permendag PPMSE mensyaratkan informasi
     * jelas soal syarat, privasi, dan mekanisme pengaduan di semua platform
     * (BRANDING-GUIDELINE.md §8.3). Seekitar juga menyimpan foto KTP &
     * selfie, sehingga hak subjek data UU PDP harus dijelaskan.
     */
    public function privacy(): View
    {
        return view('web.privacy');
    }

    public function terms(): View
    {
        return view('web.terms');
    }

    /** Kanal pengaduan — alamatnya wajib aktif sebelum pendaftaran PSE. */
    public function contact(): View
    {
        return view('web.contact');
    }

    /**
     * robots.txt dinamis.
     *
     * Dibuat dari route, bukan berkas statis di `public/`, supaya lingkungan
     * non-produksi TIDAK PERNAH terindeks. Staging yang bocor ke mesin
     * pencari akan bersaing dengan domain aslinya.
     */
    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? [
                'User-agent: *',
                'Allow: /',
                // Panel admin & API tidak punya nilai SEO dan tidak boleh
                // muncul di hasil pencarian.
                'Disallow: /admin',
                'Disallow: /api',
                'Sitemap: '.route('web.sitemap'),
            ]
            : [
                'User-agent: *',
                'Disallow: /',
            ];

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /** Sitemap XML — hanya halaman statis; katalog menyusul di Fase 2. */
    public function sitemap(): Response
    {
        $pages = [
            ['loc' => route('web.home'),    'priority' => '1.0', 'freq' => 'daily'],
            ['loc' => route('web.about'),   'priority' => '0.6', 'freq' => 'monthly'],
            ['loc' => route('web.help'),    'priority' => '0.7', 'freq' => 'weekly'],
            ['loc' => route('web.privacy'), 'priority' => '0.4', 'freq' => 'yearly'],
            ['loc' => route('web.terms'),   'priority' => '0.4', 'freq' => 'yearly'],
            ['loc' => route('web.contact'), 'priority' => '0.5', 'freq' => 'monthly'],
        ];

        return response()
            ->view('web.sitemap', ['pages' => $pages])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
