<?php

use App\Http\Controllers\Api\V1\Admin\AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\AdminDisputeController;
use App\Http\Controllers\Api\V1\Admin\AdminSettingController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminVerificationController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerRequestController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OfferController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Controllers\Api\V1\UserAddressController;
use App\Http\Controllers\Api\V1\VerificationController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| Base URL: /api/v1  (lihat TECH_STACK.md §4)
|
| Versi ada di URL, bukan header: klien mobile lama tetap bisa berjalan saat
| v2 dirilis, dan pengujian lewat curl/Postman jadi jauh lebih sederhana.
|
*/

Route::prefix('v1')->group(function (): void {

    /*
    |----------------------------------------------------------------------
    | Publik — tanpa token
    |----------------------------------------------------------------------
    */

    // Throttle PER NOMOR, bukan per IP: kalau per IP, satu orang bisa
    // memanen OTP dengan berganti jaringan, dan pengguna satu WiFi kantor
    // justru saling memblokir (Server_Implementation_Guide §5.3).
    Route::post('auth/request-otp', [AuthController::class, 'requestOtp'])
        ->middleware('throttle:otp');

    Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:otp-verify');

    /*
    |----------------------------------------------------------------------
    | Perlu token
    |----------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function (): void {

        // --- Profil & perangkat ---------------------------------------
        // TIDAK memakai 'profile.complete': pengguna baru justru datang ke
        // sini untuk melengkapi profilnya.
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::patch('auth/profile', [AuthController::class, 'updateProfile']);
        // Ganti nomor HP: dua langkah OTP (request ke nomor baru → verify).
        // Throttle sama dengan OTP masuk — kode 6 digit tidak boleh bisa
        // ditebak lewat kanal mana pun.
        Route::post('auth/phone/request-otp', [AuthController::class, 'requestPhoneChangeOtp'])
            ->middleware('throttle:otp');
        Route::post('auth/phone/verify-otp', [AuthController::class, 'verifyPhoneChangeOtp'])
            ->middleware('throttle:otp-verify');
        // --- Privasi dan kepatuhan (UU PDP) --------------------------------
        // Ekspor data portabilitas (Pasal 8) — unduh JSON data pribadi.
        Route::get('auth/export-data', [AuthController::class, 'exportData']);
        // Hak dilupakan (right to erasure) — anonimisasi akun.
        Route::delete('auth/account', [AuthController::class, 'requestDeletion']);

        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/fcm-token', [DeviceController::class, 'store']);
        Route::delete('auth/fcm-token', [DeviceController::class, 'destroy']);
        Route::post('auth/verification/ktp', [VerificationController::class, 'uploadKtp']);

        // Unggah gambar dipakai sebelum listing/permintaan dibuat, jadi TIDAK
        // berada di balik 'profile.complete'.
        Route::post('uploads/images', [UploadController::class, 'store']);

        // --- Penjelajahan (boleh profil belum lengkap) -----------------
        Route::get('stores/nearby', [StoreController::class, 'nearby']);
        Route::get('stores/mine', [StoreController::class, 'mine']);
        Route::get('stores/{store}', [StoreController::class, 'show']);
        Route::get('stores/{store}/reviews', [StoreController::class, 'reviews']);
        Route::get('listings', [ListingController::class, 'index']);
        Route::get('listings/{listing}', [ListingController::class, 'show']);

        // Wishlist — boleh dipakai sebelum profil lengkap; menyimpan barang
        // untuk nanti tidak butuh lokasi.
        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('listings/{listing}/favorite', [FavoriteController::class, 'store']);
        Route::delete('listings/{listing}/favorite', [FavoriteController::class, 'destroy']);

        // --- Notifikasi (boleh profil belum lengkap) -------------------
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);

        // --- Chat / percakapan (boleh profil belum lengkap) ------------
        Route::get('conversations', [ConversationController::class, 'index']);
        Route::post('conversations', [ConversationController::class, 'store']);
        Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
        Route::get('conversations/{conversation}/messages', [ConversationController::class, 'messages']);
        Route::post('conversations/{conversation}/messages', [ConversationController::class, 'send']);

        // --- Transaksional (wajib profil lengkap) ----------------------
        // Lokasi & nama dibutuhkan untuk pencocokan dan pengiriman, jadi
        // aksi di bawah ini tidak masuk akal tanpa keduanya.
        Route::middleware('profile.complete')->group(function (): void {

            Route::post('stores', [StoreController::class, 'store']);
            Route::patch('stores/{store}', [StoreController::class, 'update']);

            Route::post('listings', [ListingController::class, 'store']);
            // Kontrak API §4.4 menyebut PUT; PATCH diterima sebagai sinonim
            // karena klien mobile umum mengirimnya untuk pembaruan parsial.
            Route::match(['put', 'patch'], 'listings/{listing}', [ListingController::class, 'update']);
            Route::delete('listings/{listing}', [ListingController::class, 'destroy']);

            Route::get('requests', [CustomerRequestController::class, 'index']);
            Route::get('requests/mine', [CustomerRequestController::class, 'mine']);
            Route::post('requests', [CustomerRequestController::class, 'store']);
            // Rute statis didaftarkan SEBELUM {customerRequest}, kalau tidak
            // 'mine' akan tertangkap sebagai id permintaan.
            Route::get('requests/{customerRequest}', [CustomerRequestController::class, 'show']);
            Route::patch('requests/{customerRequest}', [CustomerRequestController::class, 'update']);
            Route::delete('requests/{customerRequest}', [CustomerRequestController::class, 'destroy']);
            Route::post('requests/{customerRequest}/extend', [CustomerRequestController::class, 'extend']);
            Route::get('requests/{customerRequest}/offers', [CustomerRequestController::class, 'offers']);

            Route::post('requests/{customerRequest}/offers', [OfferController::class, 'store'])
                ->middleware('throttle:offers');
            Route::get('offers/{offer}', [OfferController::class, 'show']);
            Route::patch('offers/{offer}/accept', [OfferController::class, 'accept']);

            Route::get('orders', [OrderController::class, 'index']);
            Route::post('orders', [OrderController::class, 'store']);
            Route::get('orders/{order}', [OrderController::class, 'show']);
            Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
            Route::post('orders/{order}/payment-proof', [OrderController::class, 'uploadPaymentProof']);
            Route::post('orders/{order}/review', [OrderController::class, 'review']);
            Route::post('orders/{order}/disputes', [OrderController::class, 'dispute']);

            // --- Dompet & transaksi keuangan --------------------------------
            Route::get('wallet', [WalletController::class, 'show']);
            Route::get('wallet/transactions', [WalletController::class, 'transactions']);
            Route::post('wallet/topup', [WalletController::class, 'topup']);
            Route::post('wallet/withdraw', [WalletController::class, 'withdraw']);

            // --- Alamat tersimpan --------------------------------------------
            Route::get('addresses', [UserAddressController::class, 'index']);
            Route::post('addresses', [UserAddressController::class, 'store']);
            Route::patch('addresses/{address}', [UserAddressController::class, 'update']);
            Route::delete('addresses/{address}', [UserAddressController::class, 'destroy']);
            Route::patch('addresses/{address}/default', [UserAddressController::class, 'setDefault']);

            // --- Kupon / voucher -----------------------------------------------
            Route::post('coupons/validate', [CouponController::class, 'validate']);
            Route::post('coupons/apply', [CouponController::class, 'apply']);
        });

        /*
        |------------------------------------------------------------------
        | Admin — permission granular Spatie
        |------------------------------------------------------------------
        |
        | Tiap route memakai permission-nya sendiri, bukan sekadar
        | 'role:admin': dengan begitu peran baru bisa diberi sebagian akses
        | tanpa mengubah satu pun route.
        */
        Route::prefix('admin')->group(function (): void {

            Route::get('verifications/pending', [AdminVerificationController::class, 'pending'])
                ->middleware('permission:verify-users');
            Route::post('verifications/users/{user}/approve', [AdminVerificationController::class, 'approveUser'])
                ->middleware('permission:verify-users');
            Route::post('verifications/users/{user}/reject', [AdminVerificationController::class, 'rejectUser'])
                ->middleware('permission:verify-users');
            Route::post('verifications/stores/{store}/approve', [AdminVerificationController::class, 'approveStore'])
                ->middleware('permission:verify-stores');
            Route::post('verifications/stores/{store}/reject', [AdminVerificationController::class, 'rejectStore'])
                ->middleware('permission:verify-stores');

            Route::get('users', [AdminUserController::class, 'index'])
                ->middleware('permission:manage-users');
            Route::patch('users/{user}/block', [AdminUserController::class, 'block'])
                ->middleware('permission:manage-users');

            Route::get('categories', [AdminCategoryController::class, 'index'])
                ->middleware('permission:manage-categories');
            Route::post('categories', [AdminCategoryController::class, 'store'])
                ->middleware('permission:manage-categories');
            Route::put('categories/{category}', [AdminCategoryController::class, 'update'])
                ->middleware('permission:manage-categories');
            Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])
                ->middleware('permission:manage-categories');

            Route::get('disputes', [AdminDisputeController::class, 'index'])
                ->middleware('permission:manage-disputes');
            Route::patch('disputes/{dispute}/resolve', [AdminDisputeController::class, 'resolve'])
                ->middleware('permission:manage-disputes');

            Route::get('settings', [AdminSettingController::class, 'index'])
                ->middleware('permission:manage-settings');
            Route::post('settings', [AdminSettingController::class, 'update'])
                ->middleware('permission:manage-settings');
        });
    });
});
