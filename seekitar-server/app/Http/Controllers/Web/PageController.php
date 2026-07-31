<?php

namespace App\Http\Controllers\Web;

use App\Enums\ListingStatus;
use App\Enums\ReviewDirection;
use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Review;
use App\Models\Store;
use App\Support\PlaceholderImg;
use Illuminate\Contracts\View\View;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
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
                ->withCount('children')
                ->get(),
            'statistik'   => $statistik,
            'testimoni'   => $this->testimoni(),
        ]);
    }

    /**
     * Testimoni asli dari ulasan pembeli (PRD §14: halaman publik memakai
     * data sungguhan, bukan narasi rekaan). Cukup enam ulasan terbaik yang
     * punya komentar — rating bintang di kartu harus bisa dibaca, jadi yang
     * tanpa teks dibuang.
     */
    private function testimoni(): array
    {
        return Review::query()
            ->where('direction', ReviewDirection::BuyerToStore)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->with(['reviewer:id,name,email,phone,avatar_url', 'store:id,name'])
            ->orderByDesc('rating')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Review $r) => [
                'nama'    => $r->reviewer?->displayName() ?: 'Pengguna Seekitar',
                'inisial' => $r->reviewer?->initials ?: 'P',
                'toko'    => $r->store?->name ?: 'Toko Seekitar',
                'rating'  => $r->rating,
                'kata'    => $r->comment,
            ])
            ->all();
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
     * Katalog publik `/cari` (PRD §14 "halaman listing").
     *
     * Beda dari API ListingController::index: halaman ini TIDAK menuntut
     * koordinat — pengunjung web belum tentu di kabupaten target, dan untuk
     * SEO lokasi pengunjung bahkan tidak berarti. Filter berhenti di
     * kategori + tipe + kata kunci; toko yang belum tayang ikut
     * disingkirkan persis seperti aturan tayang di aplikasi.
     */
    public function listings(Request $request): View
    {
        $data = $request->validate([
            'category' => ['sometimes', 'integer', 'exists:categories,id'],
            'type'     => ['sometimes', 'in:product,service,rental'],
            'keyword'  => ['sometimes', 'string', 'max:100'],
        ]);

        // Kategori utama di beranda diklik pengunjung web — toko memilih
        // SUBkategori, jadi menyaring hanya kategori utama akan selalu
        // kosong. Di sini kategori utama diperluas ke semua subkategorinya;
        // pilihan subkategori dipakai apa adanya.
        $categoryIds = null;
        if (isset($data['category'])) {
            $category = Category::find($data['category']);
            $categoryIds = $category
                ? $category->children()->pluck('id')->push($category->id)
                : collect([(int) $data['category']]);
        }

        $listings = Listing::query()
            ->with('store')
            ->where('status', ListingStatus::Active->value)
            ->whereHas('store', fn ($q) => $q
                ->where('is_active', true)
                ->where('status', StoreStatus::Verified->value))
            ->when($categoryIds, fn ($q) => $q->whereHas(
                'store',
                fn ($q) => $q->where(function ($q) use ($categoryIds) {
                    foreach ($categoryIds as $id) {
                        $q->orWhereJsonContains('category_ids', (int) $id);
                    }
                })
            ))
            ->when(isset($data['type']), fn ($q) => $q->where('listing_type', $data['type']))
            ->when(isset($data['keyword']) && $data['keyword'] !== '', fn ($q) => $q->whereFullText(
                ['title', 'description'],
                $data['keyword']
            ))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('web.listings', [
            'listings'   => $listings,
            'categories' => Category::query()
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get(),
            'filters' => $data,
        ]);
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

    public function contactStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'category' => ['required', Rule::in(['umum', 'abuse', 'privacy', 'security'])],
            'message' => ['required', 'string', 'max:5000'],
        ]);
        //         // dd($request->all(), $request->hasSession(), $request->session()->token(), $request->input('_token'));

        ContactMessage::create($data);

        return back()->with('success', 'Laporan Anda telah berhasil terkirim. Kami akan segera meninjaunya.');
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
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'notifications' => $this->checkNotifications(),
        ];
        $healthy = !collect($checks)->contains('ok', false); // null is not false, so not unhealthy
        return view('web.status', [
            'checks' => $checks,
            'healthy' => $healthy,
            'checked_at' => now(),
        ]);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            return ['ok' => true, 'message' => 'Koneksi dan query berhasil'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Database tidak terjangkau atau bermasalah: ' . $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = 'web.status.cache.'.Str::random(6);
            Cache::put($key, true, 10);
            $read = Cache::get($key);
            Cache::forget($key);
            return $read === true
                ? ['ok' => true, 'message' => 'Tulis & baca cache berhasil']
                : ['ok' => false, 'message' => 'Cache tidak konsisten'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Cache tidak terjangkau atau bermasalah: ' . $e->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        $dir = storage_path();
        try {
            $probe = $dir.DIRECTORY_SEPARATOR.'health-'.Str::random(6).'.tmp';
            if (@file_put_contents($probe, 'ok') === false) {
                return ['ok' => false, 'message' => 'Direktori penyimpanan tidak dapat ditulis'];
            }
            @unlink($probe);
            return ['ok' => true, 'message' => 'Penyimpanan dapat ditulis'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Penyimpanan bermasalah: ' . $e->getMessage()];
        }
    }

    private function checkNotifications(): array
    {
        $token = config('services.kirimwa.token');
        if (empty($token)) {
            return ['ok' => null, 'message' => 'Gateway WhatsApp belum dikonfigurasi (tidak diperiksa)'];
        }
        return ['ok' => true, 'message' => 'Gateway WhatsApp terkonfigurasi'];
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
            'image'    => $p->image ?? PlaceholderImg::url('blog-'.$p->slug, 800, 600, $p->title),
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
            'image'    => $record->image ?? PlaceholderImg::url('blog-'.$record->slug, 800, 600, $record->title),
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
            ['loc' => route('web.listings'),   'priority' => '0.8', 'freq' => 'daily'],
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
