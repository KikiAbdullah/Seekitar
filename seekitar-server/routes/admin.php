<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerRequestController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DisputeController;
use App\Http\Controllers\Admin\ListingController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panel Admin (web)
|--------------------------------------------------------------------------
|
| Prefix `admin` dan name `admin.` sudah disetel saat grup ini didaftarkan
| di bootstrap/app.php, jadi TIDAK diulang di sini — kalau diulang, nama
| route menjadi `admin.admin.users.index`.
|
| Autentikasi memakai sesi Laravel (stateful), bukan Bearer token: panel ini
| diakses lewat browser, bukan aplikasi mobile.
|
*/

/*
| Login BERADA DI LUAR grup 'auth' — kalau di dalam, halaman login sendiri
| menuntut login dan tidak ada yang bisa masuk sama sekali.
*/
Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])
        ->middleware('throttle:admin-login')
        ->name('login.store');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:admin|super-admin'])->group(function (): void {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/chart', [DashboardController::class, 'chartData'])->name('dashboard.chart');

    /*
    | Endpoint AJAX Datatables WAJIB didaftarkan SEBELUM route ber-parameter.
    | Kalau tidak, `users/data` tertangkap sebagai `users/{user}` dan Laravel
    | mencari pengguna ber-id "data".
    */
    Route::get('users/data',    [UserController::class, 'data'])->name('users.data');
    Route::get('stores/data',   [StoreController::class, 'data'])->name('stores.data');
    Route::get('disputes/data', [DisputeController::class, 'data'])->name('disputes.data');
    Route::get('listings/data', [ListingController::class, 'data'])->name('listings.data');
    Route::get('orders/data',   [OrderController::class, 'data'])->name('orders.data');
    Route::get('requests/data', [CustomerRequestController::class, 'data'])->name('requests.data');
    Route::get('reviews/data',  [ReviewController::class, 'data'])->name('reviews.data');

    // --- Pengguna -------------------------------------------------------
    Route::middleware('permission:manage-users')->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/block', [UserController::class, 'block'])->name('users.block');
    });

    // --- Toko & verifikasi ----------------------------------------------
    Route::get('stores', [StoreController::class, 'index'])
        ->middleware('permission:manage-stores')
        ->name('stores.index');

    Route::middleware('permission:verify-stores')->group(function (): void {
        Route::post('stores/{store}/approve', [StoreController::class, 'approve'])->name('stores.approve');
        Route::post('stores/{store}/reject', [StoreController::class, 'reject'])->name('stores.reject');
    });

    // --- Verifikasi KTP ---------------------------------------------------
    Route::middleware('permission:verify-users')->group(function (): void {
        Route::get('verifications', [VerificationController::class, 'index'])->name('verifications.index');
        Route::post('verifications/users/{user}/approve', [VerificationController::class, 'approveUser'])->name('verifications.users.approve');
        Route::post('verifications/users/{user}/reject', [VerificationController::class, 'rejectUser'])->name('verifications.users.reject');
    });

    // --- Kategori ---------------------------------------------------------
    Route::middleware('permission:manage-categories')->group(function (): void {
        Route::resource('categories', CategoryController::class)->except(['show']);
    });

    // --- Listing ----------------------------------------------------------
    Route::middleware('permission:manage-listings')->group(function (): void {
        Route::resource('listings', ListingController::class)->only(['index', 'show', 'destroy']);
    });

    // --- Permintaan & pesanan (hanya baca) --------------------------------
    Route::middleware('permission:manage-requests')->group(function (): void {
        Route::resource('requests', CustomerRequestController::class)->only(['index', 'show'])
            ->parameters(['requests' => 'customerRequest']);
    });

    Route::middleware('permission:manage-orders')->group(function (): void {
        Route::resource('orders', OrderController::class)->only(['index', 'show']);
    });

    // --- Ulasan -----------------------------------------------------------
    Route::middleware('permission:manage-reviews')->group(function (): void {
        Route::resource('reviews', ReviewController::class)->only(['index', 'destroy']);
    });

    // --- Pengaturan sistem (hanya super-admin lewat permission) -----------
    Route::middleware('permission:manage-settings')->group(function (): void {
        Route::get('settings', [SettingController::class, 'index'])->name('settings');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });

    // --- Laporan masalah -------------------------------------------------
    Route::middleware('permission:manage-disputes')->group(function (): void {
        Route::get('disputes', [DisputeController::class, 'index'])->name('disputes.index');
        Route::get('disputes/{dispute}', [DisputeController::class, 'show'])->name('disputes.show');
        Route::post('disputes/{dispute}/resolve', [DisputeController::class, 'resolve'])->name('disputes.resolve');
    });
});
