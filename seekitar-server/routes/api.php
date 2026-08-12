<?php

use App\Http\Controllers\Api\V1\Admin\AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\AdminDisputeController;
use App\Http\Controllers\Api\V1\Admin\AdminSettingController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminVerificationController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BlockController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\CustomerRequestController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\NotificationPreferenceController;
use App\Http\Controllers\Api\V1\OfferController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\ShareController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\StoreDashboardController;
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

    Route::post('auth/request-otp', [AuthController::class, 'requestOtp'])
        ->middleware('throttle:otp');

    Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:otp-verify');

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('config', [SettingController::class, 'publicConfig']);

    /*
    |----------------------------------------------------------------------
    | Perlu token
    |----------------------------------------------------------------------
    */
    Route::middleware(['auth:api', 'user.active'])->group(function (): void {
        // --- Profil & perangkat ---------------------------------------
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::patch('auth/profile', [AuthController::class, 'updateProfile']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::post('auth/phone/request-otp', [AuthController::class, 'requestPhoneChangeOtp'])
            ->middleware('throttle:otp');
        Route::post('auth/phone/verify-otp', [AuthController::class, 'verifyPhoneChangeOtp'])
            ->middleware('throttle:otp-verify');
        Route::get('auth/export-data', [AuthController::class, 'exportData']);
        Route::delete('auth/account', [AuthController::class, 'requestDeletion']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/fcm-token', [DeviceController::class, 'store']);
        Route::delete('auth/fcm-token', [DeviceController::class, 'destroy']);
        Route::post('auth/verification/ktp', [VerificationController::class, 'uploadKtp']);
        Route::get('auth/verification/photo/{kind}', [VerificationController::class, 'myPhoto'])
            ->whereIn('kind', ['ktp', 'selfie']);
        Route::post('uploads/images', [UploadController::class, 'store']);
Route::delete('uploads/images', [UploadController::class, 'destroy']);

        // --- Home / Discovery feed -----------------------------------
        Route::get('home', [HomeController::class, 'index']);

        // --- Pencarian & penjelajahan ---------------------------------
        Route::get('search/suggestions', [SearchController::class, 'suggestions']);
        Route::get('stores/nearby', [StoreController::class, 'nearby']);
        Route::get('stores/mine', [StoreController::class, 'mine']);
        Route::get('stores/{store}', [StoreController::class, 'show']);
        Route::get('stores/{store}/reviews', [StoreController::class, 'reviews']);
        Route::get('stores/{store}/dashboard', [StoreDashboardController::class, 'show']);
        Route::get('listings', [ListingController::class, 'index']);
        Route::get('listings/{listing}', [ListingController::class, 'show']);
        Route::get('listings/{listing}/share', [ShareController::class, 'listing']);

        // --- Wishlist -------------------------------------------------
        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('listings/{listing}/favorite', [FavoriteController::class, 'store']);
        Route::delete('listings/{listing}/favorite', [FavoriteController::class, 'destroy']);

        // --- Notifikasi ------------------------------------------------
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::get('notifications/preferences', [NotificationPreferenceController::class, 'show']);
        Route::patch('notifications/preferences', [NotificationPreferenceController::class, 'update']);

        // --- Chat / percakapan ----------------------------------------
        Route::get('conversations', [ConversationController::class, 'index']);
        Route::post('conversations', [ConversationController::class, 'store']);
        Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
        Route::get('conversations/{conversation}/messages', [ConversationController::class, 'messages']);
        Route::post('conversations/{conversation}/messages', [ConversationController::class, 'send']);

        // --- Blokir pengguna ------------------------------------------
        Route::get('users/blocked', [BlockController::class, 'index']);
        Route::post('users/{user}/block', [BlockController::class, 'block']);
        Route::delete('users/{user}/block', [BlockController::class, 'unblock']);

        // --- Pelaporan konten ------------------------------------------
        Route::post('reports', [ReportController::class, 'store']);

        // --- Transaksional (wajib profil lengkap) ----------------------
        Route::middleware('profile.complete')->group(function (): void {

            Route::post('stores', [StoreController::class, 'store']);
            Route::patch('stores/{store}', [StoreController::class, 'update']);

            Route::post('listings', [ListingController::class, 'store']);
            Route::match(['put', 'patch'], 'listings/{listing}', [ListingController::class, 'update']);
            Route::delete('listings/{listing}', [ListingController::class, 'destroy']);

            Route::get('requests', [CustomerRequestController::class, 'index']);
            Route::get('requests/mine', [CustomerRequestController::class, 'mine']);
            Route::post('requests', [CustomerRequestController::class, 'store']);
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
            Route::get('orders/{order}/payment-proof', [OrderController::class, 'paymentProof']);
            Route::post('orders/{order}/review', [OrderController::class, 'review']);
            Route::post('orders/{order}/disputes', [OrderController::class, 'dispute']);

            Route::get('wallet', [WalletController::class, 'show']);
            Route::get('wallet/transactions', [WalletController::class, 'transactions']);
            Route::post('wallet/topup', [WalletController::class, 'topup']);
            Route::post('wallet/withdraw', [WalletController::class, 'withdraw']);

            Route::get('addresses', [UserAddressController::class, 'index']);
            Route::post('addresses', [UserAddressController::class, 'store']);
            Route::patch('addresses/{address}', [UserAddressController::class, 'update']);
            Route::delete('addresses/{address}', [UserAddressController::class, 'destroy']);
            Route::patch('addresses/{address}/default', [UserAddressController::class, 'setDefault']);

            Route::post('coupons/validate', [CouponController::class, 'validate']);
            Route::post('coupons/apply', [CouponController::class, 'apply']);
        });

        /*
        |------------------------------------------------------------------
        | Admin — permission granular Spatie
        |------------------------------------------------------------------
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
