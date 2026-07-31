<?php

use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerRequestController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DisputeController;
use App\Http\Controllers\Admin\ListingController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\OfferController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\AdvertisementController;
use App\Http\Controllers\Admin\FeeController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\StoreMapController;
use App\Http\Controllers\Admin\SubscriptionController;
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
| ATURAN PENTING: setiap route di bawah harus punya permission yang SAMA
| PERSIS dengan @can pada butir menunya di
| resources/views/admin/partials/sidebar.blade.php. Kalau berbeda, menu akan
| tampil lalu menolak saat diklik, atau tersembunyi padahal admin berhak.
| Kecocokan itu ditegakkan tools/dev/check-admin-menu.mjs.
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
    | --- Akun sendiri ------------------------------------------------------
    | TANPA permission: admin yang tidak punya `manage-users` sekalipun harus
    | tetap bisa memperbaiki namanya dan mengganti kata sandinya sendiri.
    | Yang membatasi cakupannya adalah controller-nya, yang hanya pernah
    | menyentuh $request->user().
    */
    Route::get('profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('kata-sandi', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('kata-sandi', [PasswordController::class, 'update'])->name('password.update');

    /*
    | Endpoint AJAX Datatables WAJIB didaftarkan SEBELUM route ber-parameter.
    | Kalau tidak, `users/data` tertangkap sebagai `users/{user}` dan Laravel
    | mencari pengguna ber-id "data".
    |
    | Masing-masing memakai permission yang sama dengan halaman induknya —
    | tanpa itu, admin tanpa izin bisa memanggil endpoint JSON-nya langsung
    | dan menarik seluruh tabel meski menunya tersembunyi.
    */
    Route::get('users/data',    [UserController::class, 'data'])
        ->middleware('permission:manage-users')->name('users.data');
    Route::get('stores/data',   [StoreController::class, 'data'])
        ->middleware('permission:manage-stores')->name('stores.data');
    Route::get('disputes/data', [DisputeController::class, 'data'])
        ->middleware('permission:manage-disputes')->name('disputes.data');
    Route::get('listings/data', [ListingController::class, 'data'])
        ->middleware('permission:manage-listings')->name('listings.data');
    Route::get('orders/data',   [OrderController::class, 'data'])
        ->middleware('permission:manage-orders')->name('orders.data');
    Route::get('requests/data', [CustomerRequestController::class, 'data'])
        ->middleware('permission:manage-requests')->name('requests.data');
    Route::get('offers/data',   [OfferController::class, 'data'])
        ->middleware('permission:manage-offers')->name('offers.data');
    Route::get('reviews/data',  [ReviewController::class, 'data'])
        ->middleware('permission:manage-reviews')->name('reviews.data');

    // --- Pengguna -------------------------------------------------------
    Route::middleware('permission:manage-users')->group(function (): void {
        Route::get('users/export', [UserController::class, 'exportCsv'])->name('users.export');
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/block', [UserController::class, 'block'])->name('users.block');
    });

    // --- Toko -------------------------------------------------------------
    Route::get('stores/export', [StoreController::class, 'exportCsv'])
        ->middleware('permission:manage-stores')
        ->name('stores.export');
    Route::get('stores', [StoreController::class, 'index'])
        ->middleware('permission:manage-stores')
        ->name('stores.index');
    Route::get('stores/{store}', [StoreController::class, 'show'])
        ->middleware('permission:manage-stores')
        ->name('stores.show');
    Route::get('stores/{store}/edit', [StoreController::class, 'edit'])
        ->middleware('permission:manage-stores')
        ->name('stores.edit');
    Route::put('stores/{store}', [StoreController::class, 'update'])
        ->middleware('permission:manage-stores')
        ->name('stores.update');

    /*
    | --- Peta sebaran toko ------------------------------------------------
    | Memakai permission yang sama dengan daftar toko: isinya data toko yang
    | sama, hanya digambar sebagai titik. Memberinya izin sendiri berarti
    | admin bisa melihat sebaran toko tanpa boleh melihat tokonya.
    |
    | Endpoint GeoJSON-nya ikut dijaga — tanpa itu siapa pun yang tahu URL-nya
    | bisa menarik koordinat seluruh toko meski menunya tersembunyi.
    */
    Route::middleware('permission:manage-stores')->group(function (): void {
        Route::get('maps/stores', [StoreMapController::class, 'index'])->name('maps.stores');
        Route::get('maps/stores/data', [StoreMapController::class, 'data'])->name('maps.stores.data');
    });

    Route::middleware('permission:verify-stores')->group(function (): void {
        Route::post('stores/{store}/approve', [StoreController::class, 'approve'])->name('stores.approve');
        Route::post('stores/{store}/reject', [StoreController::class, 'reject'])->name('stores.reject');
    });

    /*
    | --- Verifikasi ------------------------------------------------------
    | DUA halaman terpisah, karena `verify-users` dan `verify-stores` adalah
    | dua permission berbeda (§6.2). Menggabungkannya memaksa admin yang hanya
    | punya salah satunya melihat data yang bukan haknya.
    */
    Route::middleware('permission:verify-users')->group(function (): void {
        Route::get('verifications/users', [VerificationController::class, 'users'])
            ->name('verifications.users');
        Route::post('verifications/users/{user}/verify', [VerificationController::class, 'verifyUser'])
            ->name('verifications.users.verify');
        Route::post('verifications/users/{user}/reject', [VerificationController::class, 'rejectUser'])
            ->name('verifications.users.reject');

        /*
         * Berkas privat (KTP, selfie) tidak punya URL publik —
         * satu-satunya jalan melihatnya lewat route berizin ini (UU PDP).
         * `kind` dibatasi whereIn supaya tidak ada kolom lain yang bisa
         * diintip lewat parameter bebas.
         */
        Route::get('verifications/users/{user}/media/{kind}', [VerificationController::class, 'media'])
            ->name('verifications.users.media')
            ->whereIn('kind', ['ktp', 'selfie']);
    });

    Route::middleware('permission:verify-stores')->group(function (): void {
        Route::get('verifications/stores', [VerificationController::class, 'stores'])
            ->name('verifications.stores');
        Route::post('verifications/stores/{store}/approve', [VerificationController::class, 'approveStore'])
            ->name('verifications.stores.approve');
        Route::post('verifications/stores/{store}/reject', [VerificationController::class, 'rejectStore'])
            ->name('verifications.stores.reject');
    });

    // --- Kategori ---------------------------------------------------------
    Route::middleware('permission:manage-categories')->group(function (): void {
        Route::resource('categories', CategoryController::class)->except(['show']);
    });

    // --- Listing ----------------------------------------------------------
    Route::middleware('permission:manage-listings')->group(function (): void {
        Route::resource('listings', ListingController::class)->only(['index', 'show', 'destroy']);
    });

    // --- Permintaan -------------------------------------------------------
    Route::middleware('permission:manage-requests')->group(function (): void {
        Route::resource('requests', CustomerRequestController::class)->only(['index', 'show'])
            ->parameters(['requests' => 'customerRequest']);

        // Perpanjangan admin untuk permintaan yang kedaluwarsa akibat gangguan
        // sistem, bukan karena pembeli membiarkannya (§9.7).
        Route::post('requests/{customerRequest}/extend', [CustomerRequestController::class, 'extend'])
            ->name('requests.extend');
    });

    // --- Penawaran (hanya baca, §9.8) --------------------------------------
    Route::middleware('permission:manage-offers')->group(function (): void {
        Route::get('offers/export', [OfferController::class, 'exportCsv'])->name('offers.export');
        Route::get('offers', [OfferController::class, 'index'])->name('offers.index');
        Route::get('offers/{offer}', [OfferController::class, 'show'])->name('offers.show');
    });

    // --- Pesanan (hanya baca) ---------------------------------------------
    Route::middleware('permission:manage-orders')->group(function (): void {
        Route::get('orders/export', [OrderController::class, 'exportCsv'])->name('orders.export');
        Route::resource('orders', OrderController::class)->only(['index', 'show']);
        Route::get('orders/{order}/payment-proof', [OrderController::class, 'paymentProofMedia'])
            ->name('orders.payment-proof');
    });

    // --- Ulasan -----------------------------------------------------------
    Route::middleware('permission:manage-reviews')->group(function (): void {
        Route::resource('reviews', ReviewController::class)->only(['index', 'destroy']);
    });

    // --- Blog -------------------------------------------------------------
    Route::middleware('permission:manage-blog')->group(function (): void {
        Route::get('blog/data', [BlogController::class, 'data'])->name('blog.data');
        Route::resource('blog', BlogController::class)
            ->parameters(['blog' => 'post'])
            ->except(['show']);
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

    // --- Langganan & Boost Listing -----------------------------------------
    Route::middleware('permission:manage-subscriptions')->group(function (): void {
        Route::get('subscriptions/data', [SubscriptionController::class, 'data'])->name('subscriptions.data');
        Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
        Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    });

    // --- Iklan Banner ------------------------------------------------------
    Route::middleware('permission:manage-advertisements')->group(function (): void {
        Route::get('advertisements/data', [AdvertisementController::class, 'data'])->name('advertisements.data');
        Route::resource('advertisements', AdvertisementController::class)
            ->parameters(['advertisements' => 'advertisement']);
    });

    // --- Biaya Layanan -----------------------------------------------------
    Route::middleware('permission:manage-fees')->group(function (): void {
        Route::get('fees/data', [FeeController::class, 'data'])->name('fees.data');
        Route::get('fees', [FeeController::class, 'index'])->name('fees.index');
        Route::post('fees/update-settings', [FeeController::class, 'updateSettings'])->name('fees.update-settings');
    });
});
