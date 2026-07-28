<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DisputeController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\UserController;
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

    // --- Laporan masalah -------------------------------------------------
    Route::middleware('permission:manage-disputes')->group(function (): void {
        Route::get('disputes', [DisputeController::class, 'index'])->name('disputes.index');
        Route::get('disputes/{dispute}', [DisputeController::class, 'show'])->name('disputes.show');
        Route::post('disputes/{dispute}/resolve', [DisputeController::class, 'resolve'])->name('disputes.resolve');
    });
});
