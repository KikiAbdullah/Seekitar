# Seekitar Mobile — Complete Flow Documentation

## FLOW 1: APP STARTUP (Cold Boot)

1. `main.dart` → void main()
   - WidgetsFlutterBinding.ensureInitialized()
   - `_setupErrorHandling()` → FlutterError.onError + runZonedGuarded
   - `AppState()` → `providers/app_state.dart`
   - `app.init()` → `services/auth_service.dart` → AuthService.init()
     - `DioClient().loadToken()` → `services/dio_client.dart`
     - `flutter_secure_storage` → baca 'jwt_token'
     - `ApiClient().me()` → `services/api_client.dart` → **GET /auth/me**
     - `User.fromJson()` → `models/user.dart`
   - `FcmService().init()` → `services/fcm_service.dart`
     - FirebaseMessaging.getToken()
     - ApiClient.registerFcmToken() → **POST /auth/fcm-token**
    - runApp(SeekitarApp) → MaterialApp.router
      - Theme: AppTheme.light → `core/theme.dart` (mode light saja; tanpa dark theme)
      - Router: appRouter → `routing/app_router.dart` → 35 GoRoute

## FLOW 2: SPLASH → ONBOARDING → LOGIN

2. `screens/splash_screen.dart` — SplashScreen
   - AnimationController (1200ms fade + scale)
   - AppState.init() cek token
   - IF app.isLoggedIn → `context.go('/home')`
   - ELSE → `context.go('/onboarding')`

3. `screens/onboarding_screen.dart` — OnboardingScreen
   - PageView (3 slides)
   - "Lewati" → `context.go('/login')`
   - "Mulai" → `context.go('/login')`

4. `screens/login_screen.dart` — LoginScreen
   - Input nomor WA (+62 prefix)
   - AppState.requestOtp() → AuthService → ApiClient → **POST /auth/request-otp**
   - Countdown 60s untuk resend
   - Input 6-digit OTP (letterSpacing: 8)
   - AppState.verifyOtp() → AuthService → ApiClient → **POST /auth/verify-otp**
     - Terima JWT token
     - DioClient.setToken() → flutter_secure_storage
     - User.fromJson(res['user'])
   - `context.go('/home')`

## FLOW 3: HOME SCREEN

5. `screens/home_screen.dart` — HomeScreen
   - Geolocator.getCurrentPosition()
   - ApiProvider().home(lat,lng) → ApiClient → **GET /home**
     - Parse: Listing.fromJson(), CustomerRequest.fromJson()
    - _hero(t) → gambar hero (asset lokal `assets/images/hero.jpg`) + overlay gelap; gradien #168A4A hanya fallback
   - Trending section → Horizontal cards (260px wide)
     - onTap → `ctx.push('/listing/:id', extra: Listing)`
   - Terdekat section → SliverList + Row cards
     - onTap → `ctx.push('/listing/:id', extra: Listing)`
   - Kebutuhan section → Orange request cards
     - onTap → `ctx.push('/request/:id', extra: CustomerRequest)`
   - AppBar actions:
     - Notif → `ctx.push('/notifications')`
     - Favorit → `ctx.push('/favorites')`
   - Skeleton loading (while loading)

## FLOW 4: SEARCH

6. `screens/search_screen.dart` — SearchScreen
   - Geolocator.getCurrentPosition()
   - Filter chips: Semua / Barang / Jasa / Sewa
   - TextField.onChanged(q >= 2)
     - ApiProvider.searchSuggestions(q, type) → **GET /search/suggestions**
   - TextField.onSubmitted()
     - ApiProvider.getListings(lat,lng,keyword,type) → **GET /listings**
   - onTap → `ctx.push('/listing/:id')`

## FLOW 5: LISTING DETAIL

7. `screens/listing_detail_screen.dart` — ListingDetailScreen
   - IF listing passed as extra → langsung render
   - ELSE → ApiProvider.getListing(id) → **GET /listings/:id**
   - PageView + SmoothPageIndicator (image galeri)
   - Store card (nama, verified, district)
   - Deskripsi
   - AppBar actions:
     - Share → ApiProvider.shareListing(id) → **GET /listings/:id/share**
       - Share.share(text) → `share_plus`
     - Report → BottomSheet (5 reasons)
       - ApiProvider.report('listing', id, reason) → **POST /reports**
   - CTA: "Transaksi di Aplikasi"

## FLOW 6: KEBUTUHAN (Requests)

8. `screens/requests_screen.dart` — RequestsScreen
   - TabController (2 tabs: Terdekat / Saya)
   - Tab "Terdekat": ApiProvider.getRequests(lat,lng) → **GET /requests**
   - Tab "Saya": ApiProvider.myRequests() → **GET /requests/mine**
   - FAB (+) → `ctx.push('/create-request')`

9. `screens/requests_screen.dart` — CreateRequestScreen (same file)
   - Input: Judul Kebutuhan, Deskripsi
   - Geolocator.getCurrentPosition()
    - ApiProvider.createRequest({title, desc, category_id, lat, lng, radius_km}) → **POST /requests**

10. `screens/request_detail_screen.dart` — RequestDetailScreen
    - IF request passed as extra → langsung render
    - ELSE → ApiProvider.getRequest(id) → **GET /requests/:id**
    - ApiProvider.getRequestOffers(id) → **GET /requests/:id/offers**
    - "Kirim Penawaran" → ApiProvider.createOffer(reqId, {price, notes})
      → **POST /requests/:id/offers**
    - Accept offer → ApiProvider.acceptOffer(id) → **PATCH /offers/:id/accept**

## FLOW 7: PESANAN (Orders)

11. `screens/orders_screen.dart` — OrdersScreen
    - TabController (2 tabs: Pembelian / Penjualan)
    - ApiProvider.getOrders(role:'buyer') → **GET /orders?role=buyer**
    - ApiProvider.getOrders(role:'seller') → **GET /orders?role=seller**
    - onTap → `ctx.push('/order-detail/${o.id}', extra: Order)`

12. `screens/order_detail_screen.dart` — OrderDetailScreen
    - Status chip (AppConstants.orderStatusColor)
    - Action buttons per status:
      - "menunggu_konfirmasi" → "Proses" → **PATCH /orders/:id/status**
      - "diproses" → "Selesai" → **PATCH /orders/:id/status**
      - "selesai" → "Beri Ulasan"
        - ApiProvider.submitReview(id, rating, comment)
          → **POST /orders/:id/review**

## FLOW 8: PROFIL & TOKO

13. `screens/profile_screen.dart` — ProfileScreen
    - AppState.user (Provider context.watch)
    - Edit Profil → ModalBottomSheet
      - AppState.updateProfile(name, address, lat, lng)
        → AuthService → ApiClient → **PATCH /auth/profile**
    - Toko Saya: ApiProvider.mine() → **GET /stores/mine**
      - onTap → `ctx.push('/store/:id/dashboard')`
    - "Buka Toko" → `ctx.push('/create-store')`
    - Menu tiles: Wishlist, Notif, Dompet, Alamat, Chat, Verifikasi, Blokir, Pengaturan
    - Keluar → AppState.logout() → DioClient.clearToken() → `ctx.go('/login')`

14. `screens/store_screen.dart` — CreateStoreScreen
    - Input: Nama Toko, Alamat Toko
    - ApiProvider.createStore({name, address, lat, lng})
      → **POST /stores**

15. `screens/store_screen.dart` — StoreDashboardScreen (same file)
    - ApiProvider.getStoreDashboard(id) → **GET /stores/:id/dashboard**
    - Stat cards: Listing, Pesanan, Menunggu, Omzet, Bulan Ini
    - Reviews link → `ctx.push('/store/:id/reviews')`
    - FAB "Pasang Listing" → `ctx.push('/create-listing', extra: Store)`

16. `screens/reviews_screen.dart` — StoreReviewsScreen
    - ApiProvider.getStoreReviews(id) → **GET /stores/:id/reviews**

## FLOW 9: LISTING MANAGEMENT

17. `screens/create_listing_screen.dart` — CreateListingScreen
    - Tipe selector: Barang / Jasa / Sewa
    - Image picker → FlutterImageCompress
    - Upload images → **POST /uploads/images**
    - ApiProvider.createListing() → **POST /listings**

## FLOW 10: VERIFIKASI KTP

18. `screens/verification_screen.dart` — VerificationScreen
    - Camera picker: Foto KTP + Selfie (ImagePicker + compress)
    - NIK input (wajib, tepat 16 digit)
    - ApiProvider.uploadKtp(ktp, selfie, nik) → **POST /auth/verification/ktp**

## FLOW 11: DOMPET, ALAMAT, CHAT, PENGATURAN

19. `screens/wallet_screen.dart` — WalletScreen
    - ApiProvider.getWallet() → **GET /wallet**
    - ApiProvider.getWalletTransactions() → **GET /wallet/transactions**
    - Top Up → **POST /wallet/topup**
    - Tarik Saldo → BottomSheet (amount, bank, rekening)

20. `screens/address_screen.dart` — AddressScreen
    - ApiProvider.getAddresses() → **GET /addresses**
    - Add → ApiProvider.createAddress() → **POST /addresses**
    - Set Default → **PATCH /addresses/:id/default**
    - Delete → **DELETE /addresses/:id**

21. `screens/conversations_screen.dart` — ConversationsScreen
    - ApiProvider.getConversations() → **GET /conversations**
    - onTap → `ctx.push('/chat/:id')`

22. `screens/conversations_screen.dart` — ChatScreen (same file)
    - ApiProvider.getMessages(convId) → **GET /conversations/:id/messages**
    - Send → ApiProvider.sendMessage() → **POST /conversations/:id/messages**

23. `screens/notifications_screen.dart` — NotificationsScreen
    - ApiProvider.getNotifications() → **GET /notifications**
    - Mark read → **PATCH /notifications/:id/read**
    - Mark all → **PATCH /notifications/read-all**
    - Unread count → **GET /notifications/unread-count**

24. `screens/notif_prefs_screen.dart` — NotifPrefsScreen
    - Get prefs → **GET /notifications/preferences**
    - Update → **PATCH /notifications/preferences**

25. `screens/favorites_screen.dart` — FavoritesScreen
    - ApiProvider.getFavorites() → **GET /favorites**
    - Remove → **DELETE /listings/:id/favorite**

26. `screens/blocked_screen.dart` — BlockedUsersScreen
    - ApiProvider.getBlockedUsers() → **GET /users/blocked**
    - Unblock → **DELETE /users/:id/block**

27. `screens/coupon_screen.dart` — CouponScreen
    - Validate → **POST /coupons/validate**
    - Apply → **POST /coupons/apply**

28. `screens/settings_full_screen.dart` — FullSettingsScreen
    - Ekspor Data → **GET /auth/export-data**
    - Hapus Akun → **DELETE /auth/account**
      - AppState.logout() → ctx.go('/login')

29. `screens/about_screen.dart` — AboutScreen
    - Static: logo, deskripsi, kontak Seekitar


## FILE CALL TREE SUMMARY

```
main.dart
├── core/constants.dart          AppConstants + UiStrings
├── core/theme.dart              AppTheme.light
├── core/logger.dart             appLogger
├── core/text_utils.dart         helper teks (normalisasi dll.)
├── providers/app_state.dart     ChangeNotifier (user state)
├── routing/app_router.dart      35 GoRoute + StatefulNavigationShell
├── services/dio_client.dart     Dio + JWT interceptor + retry
├── services/api_client.dart     82 API methods
├── services/api_compat.dart     ApiProvider wrapper
├── services/auth_service.dart   OTP flow, JWT storage
├── services/fcm_service.dart    Push notification
├── services/location_service.dart  Geolocator + koordinat default
├── widgets/offline_banner.dart  Connectivity detection
├── widgets/base_screen.dart     Scaffold wrapper
├── widgets/error_view.dart      Tampilan error/retry
├── models/                      (12 model files, fromJson manual, tanpa codegen)
└── screens/                     (32 screen files)
```

## TOTALS
- 60 Dart files
- 32 screens
- 35 GoRouter routes
- 82 ApiClient methods
- 12 models
- 6 services
- 3 widgets
- 2 Navigator.push remaining (`store_screen.dart:77`, `verification_screen.dart:154,319`)
