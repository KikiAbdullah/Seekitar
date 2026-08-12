<?php

use App\Http\Controllers\Web\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Publik (SEO)
|--------------------------------------------------------------------------
|
| Landing page, halaman legal, dan berkas SEO. Semuanya PUBLIK — tanpa
| autentikasi dan tanpa middleware panel admin (Server_Implementation_Guide
| §4 & §5.1).
|
| Route admin TIDAK didaftarkan di sini; ia punya berkas sendiri
| (`routes/admin.php`) yang dipasang lewat `then:` di bootstrap/app.php,
| supaya web publik tidak ikut terbebani middleware admin.
|
| Beberapa halaman di bawah ini WAJIB ADA secara hukum, bukan pelengkap:
| Permendag PPMSE mensyaratkan syarat & ketentuan, kebijakan privasi, dan
| mekanisme pengaduan tersedia di semua platform; alamat pengaduan yang
| tidak aktif dapat menyebabkan penolakan pendaftaran PSE
| (BRANDING-GUIDELINE.md §8.3).
|
*/

Route::name('web.')->group(function (): void {

    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/tentang', [PageController::class, 'about'])->name('about');
    Route::get('/bantuan', [PageController::class, 'help'])->name('help');
    Route::get('/kontak', [PageController::class, 'contact'])->name('contact');
    Route::post('/kontak', [PageController::class, 'contactStore'])->name('contact.store')
        ->middleware('throttle:contact');

    // --- Katalog publik -----------------------------------------------------
    Route::get('/cari', [PageController::class, 'listings'])->name('listings');
    Route::get('/listing/{listing}', [PageController::class, 'show'])->name('listing.show');

    // --- Halaman legal (wajib) -------------------------------------------
    Route::get('/kebijakan-privasi', [PageController::class, 'privacy'])->name('privacy');
    Route::get('/syarat-ketentuan', [PageController::class, 'terms'])->name('terms');
    Route::get('/kebijakan-cookie', [PageController::class, 'cookie'])->name('cookie');
    Route::get('/pedoman-komunitas', [PageController::class, 'guidelines'])->name('guidelines');
    Route::get('/kebijakan-pengembalian', [PageController::class, 'refund'])->name('refund');
    Route::get('/kebijakan-verifikasi', [PageController::class, 'verification'])->name('verification');

    // --- Bisnis & kepercayaan --------------------------------------------
    Route::get('/untuk-penjual', [PageController::class, 'forSellers'])->name('for-sellers');
    Route::get('/biaya', [PageController::class, 'pricing'])->name('pricing');
    Route::get('/keamanan', [PageController::class, 'security'])->name('security');
    Route::get('/status', [PageController::class, 'status'])->name('status');

    // --- Konten & engagement ---------------------------------------------
    Route::get('/blog', [PageController::class, 'blog'])->name('blog');
    Route::get('/blog/{slug}', [PageController::class, 'blogPost'])->name('blog.post');
    Route::get('/karier', [PageController::class, 'careers'])->name('careers');

    // --- Berkas SEO -------------------------------------------------------
    Route::get('/robots.txt', [PageController::class, 'robots'])->name('robots');
    Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');
});
