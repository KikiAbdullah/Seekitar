<?php

namespace App\Http\Controllers\Web;

use App\Enums\ListingStatus;
use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
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

    // -----------------------------------------------------------------------
    //  Halaman legal tambahan
    // -----------------------------------------------------------------------

    public function cookie(): View
    {
        return view('web.cookie');
    }

    public function guidelines(): View
    {
        return view('web.guidelines');
    }

    public function refund(): View
    {
        return view('web.refund');
    }

    public function verification(): View
    {
        return view('web.verification');
    }

    // -----------------------------------------------------------------------
    //  Bisnis & kepercayaan
    // -----------------------------------------------------------------------

    public function forSellers(): View
    {
        $stats = Cache::remember('web.for-sellers.stats', self::STATIC_CACHE_SECONDS, fn () => [
            'toko'    => Store::query()->where('status', StoreStatus::Verified->value)->where('is_active', true)->count(),
            'listing' => Listing::query()->where('status', ListingStatus::Active->value)->count(),
        ]);

        return view('web.for-sellers', ['stats' => $stats]);
    }

    public function pricing(): View
    {
        return view('web.pricing');
    }

    public function security(): View
    {
        return view('web.security');
    }

    public function status(): View
    {
        return view('web.status');
    }

    // -----------------------------------------------------------------------
    //  Konten & engagement
    // -----------------------------------------------------------------------

    public function blog(): View
    {
        $posts = BlogPost::published()->latest()->get()->map(fn ($p) => [
            'slug'     => $p->slug,
            'title'    => $p->title,
            'excerpt'  => $p->excerpt,
            'author'   => $p->author,
            'date'     => $p->published_at?->format('j F Y') ?? $p->created_at->format('j F Y'),
            'category' => $p->category,
            'image'    => $p->image ?? asset('img/web/blog-default.webp'),
            'imageAlt' => $p->image_alt ?? 'Ilustrasi artikel ' . $p->title,
        ]);

        return view('web.blog', ['posts' => $posts]);
    }

    public function blogPost(string $slug): View
    {
        $record = BlogPost::published()->where('slug', $slug)->firstOrFail();

        $post = [
            'slug'     => $record->slug,
            'title'    => $record->title,
            'excerpt'  => $record->excerpt,
            'author'   => $record->author,
            'date'     => $record->published_at?->format('j F Y') ?? $record->created_at->format('j F Y'),
            'category' => $record->category,
            'image'    => $record->image ?? asset('img/web/blog-default.webp'),
            'imageAlt' => $record->image_alt ?? 'Ilustrasi artikel ' . $record->title,
            'body'     => $record->body,
        ];

        return view('web.blog-post', ['post' => $post]);
    }

    public function careers(): View
    {
        return view('web.careers');
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
            ['loc' => route('web.home'),       'priority' => '1.0', 'freq' => 'daily'],
            ['loc' => route('web.about'),      'priority' => '0.6', 'freq' => 'monthly'],
            ['loc' => route('web.help'),       'priority' => '0.7', 'freq' => 'weekly'],
            ['loc' => route('web.privacy'),    'priority' => '0.4', 'freq' => 'yearly'],
            ['loc' => route('web.terms'),      'priority' => '0.4', 'freq' => 'yearly'],
            ['loc' => route('web.contact'),    'priority' => '0.5', 'freq' => 'monthly'],
            ['loc' => route('web.cookie'),     'priority' => '0.3', 'freq' => 'yearly'],
            ['loc' => route('web.guidelines'), 'priority' => '0.4', 'freq' => 'yearly'],
            ['loc' => route('web.refund'),     'priority' => '0.4', 'freq' => 'yearly'],
            ['loc' => route('web.verification'), 'priority' => '0.4', 'freq' => 'yearly'],
            ['loc' => route('web.for-sellers'), 'priority' => '0.7', 'freq' => 'monthly'],
            ['loc' => route('web.pricing'),    'priority' => '0.5', 'freq' => 'monthly'],
            ['loc' => route('web.security'),   'priority' => '0.5', 'freq' => 'monthly'],
            ['loc' => route('web.status'),     'priority' => '0.3', 'freq' => 'weekly'],
            ['loc' => route('web.blog'),       'priority' => '0.6', 'freq' => 'weekly'],
            ['loc' => route('web.careers'),    'priority' => '0.3', 'freq' => 'monthly'],
        ];

        return response()
            ->view('web.sitemap', ['pages' => $pages])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
