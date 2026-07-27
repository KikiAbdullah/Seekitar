# 📱 Seekitar – Mobile Implementation Guide

**Versi:** 1.0 (Production‑Ready)  
**Tanggal:** 27 Juli 2026  
**Target:** Flutter 3.44+ (Dart 3.12+) · Android & iOS  
**Arsitektur:** Clean Architecture + Riverpod · Dependency Injection dengan Riverpod  
**State Management:** Riverpod 3.x dengan AsyncNotifier  
**HTTP Client:** Dio 5.x  
**Database Lokal:** Isar (opsional, untuk cache)  
**Maps & Geolokasi:** Google Maps Flutter, Geolocator, Geocoding  
**Push Notification:** Firebase Cloud Messaging (FCM)  
**WhatsApp Redirection:** url_launcher  
**Analytics:** Firebase Analytics, Firebase Crashlytics  
**CI/CD:** GitHub Actions (build APK/IPA)

> 📌 Versi lengkap & matriks kompatibilitas paket ada di [`TECH_STACK.md`](TECH_STACK.md) (sumber kebenaran tunggal).

### Backend yang Dikonsumsi Aplikasi Ini

Aplikasi mobile tidak berdiri sendiri. Konteks singkat arsitektur server yang
memengaruhi cara app berperilaku:

| Komponen backend       | Versi         | Relevansi untuk mobile                                                                 |
| :--------------------- | :------------ | :-------------------------------------------------------------------------------------- |
| Laravel + Sanctum      | 13 · Sanctum 4 | Sumber REST API; token Bearer disimpan di `flutter_secure_storage`                     |
| MySQL 8.0.34+ (Spatial) | 8.0.34+       | Pencarian radius toko dihitung di server — app hanya mengirim `lat`, `lng`, `radius`   |
| **Redis 7**            | 7.x           | Cache & **queue**: broadcast penawaran dan notifikasi FCM diproses asinkron            |
| Firebase FCM           | —             | Push notification masuk lewat queue Redis, bukan langsung dari request                 |

**Implikasi praktis dari Redis queue:** notifikasi (mis. penawaran baru masuk)
dikirim lewat antrian, jadi **tidak instan**. Jangan rancang UI yang
mengasumsikan respons real-time setelah aksi — gunakan pull-to-refresh atau
polling ringan sebagai pelengkap push.

---

## DAFTAR ISI

1. [Arsitektur & Prinsip](#1-arsitektur--prinsip)
2. [Library Utama (pub.dev)](#2-library-utama-pubdev)
3. [Struktur Proyek](#3-struktur-proyek)
   - 3.1 Tiga Lokasi Widget — Kapan Pakai yang Mana
   - 3.2 `core/services/` vs `data/repositories/`
   - 3.3 `core/constants/route_constants.dart`
4. [State Management & Dependency Injection (Riverpod)](#4-state-management--dependency-injection-riverpod)
5. [Layer Data: API, Repositories, Models](#5-layer-data-api-repositories-models)
6. [Layer Domain: UseCases & Entities](#6-layer-domain-usecases--entities)
7. [Layer Presentation: Halaman & Widget](#7-layer-presentation-halaman--widget)
8. [Navigasi & Routing (GoRouter)](#8-navigasi--routing-gorouter)
9. [Autentikasi & OTP Flow](#9-autentikasi--otp-flow)
10. [Halaman Utama & Bottom Navigation](#10-halaman-utama--bottom-navigation)
11. [Fitur Marketplace (Jelajahi)](#11-fitur-marketplace-jelajahi)
12. [Fitur Papan Kebutuhan (Pasang Kebutuhan)](#12-fitur-papan-kebutuhan-pasang-kebutuhan)
13. [Fitur Penawaran & Transaksi](#13-fitur-penawaran--transaksi)
14. [Notifikasi Push (FCM)](#14-notifikasi-push-fcm)
15. [Integrasi WhatsApp](#15-integrasi-whatsapp)
16. [UI/UX Guidelines Implementasi](#16-uiux-guidelines-implementasi)
17. [Testing](#17-testing)
18. [Deployment & CI/CD](#18-deployment--cicd)
19. [Lampiran: Contoh Kode Penting](#19-lampiran-contoh-kode-penting)
20. [Konfigurasi Environment (dev/staging/prod)](#20-konfigurasi-environment-dev--staging--prod)
21. [Error Handling Global](#21-error-handling-global)
22. [Logging & Analytics](#22-logging--analytics)
23. [Deep Link](#23-deep-link)

---

## 1. ARSITEKTUR & PRINSIP

Aplikasi Flutter Seekitar mengadopsi **Clean Architecture** dengan 3 lapis:

- **Data:** API client (Dio), model DTO, repository implementasi.
- **Domain:** Entitas, usecase, repository interface.
- **Presentation:** Halaman, widget, state (Riverpod AsyncNotifier).

**Prinsip:**

- **Separation of Concerns** – Setiap lapis hanya berkomunikasi melalui interface.
- **Unidirectional Data Flow** – State mengalir dari provider ke UI, event dari UI ke provider.
- **Testability** – Domain murni Dart tanpa dependency Flutter.
- **Reusability** – Widget kecil, composable.

---

## 2. LIBRARY UTAMA (pub.dev)

Versi di bawah ini selaras dengan **Flutter 3.44 / Dart 3.12**.

| Kategori             | Library                                 | Versi    | Keterangan                             |
| -------------------- | --------------------------------------- | -------- | -------------------------------------- |
| State Management     | `flutter_riverpod`                      | ^3.4.0   | Riverpod untuk state, DI, caching      |
| HTTP Client          | `dio`                                   | ^5.11.0  | REST API calls dengan interceptors     |
| Routing              | `go_router`                             | ^17.3.0  | Navigasi deklaratif, deep link         |
| Maps                 | `google_maps_flutter`                   | ^2.18.0  | Menampilkan peta, pin lokasi           |
| Geolokasi            | `geolocator`                            | ^14.0.0  | Mendapatkan posisi GPS                 |
| Geocoding            | `geocoding`                             | ^5.0.0   | Reverse geocoding (koordinat → alamat) |
| Push Notification    | `firebase_messaging`                    | ^16.4.0  | FCM untuk notifikasi                   |
| Firebase Core        | `firebase_core`                         | ^4.12.0  | Inisialisasi Firebase                  |
| Deep Link            | `app_links`                             | ^6.0.0   | Menangani universal link               |
| WhatsApp             | `url_launcher`                          | ^6.3.0   | Membuka WhatsApp                       |
| Image Picker         | `image_picker`                          | ^1.2.0   | Ambil foto produk, KTP                 |
| Cached Network Image | `cached_network_image`                  | ^3.4.0   | Cache gambar                           |
| Local Storage        | `shared_preferences`                    | ^2.5.0   | Token, preferensi                      |
| Secure Storage       | `flutter_secure_storage`                | ^10.3.0  | Token akses disimpan aman              |
| JSON Serialization   | `json_annotation` + `json_serializable` | ^4.12.0  | Generate kode model                    |
| Build Runner         | `build_runner`                          | ^2.4.0   | Menjalankan generator                  |
| Font                 | `google_fonts`                          | ^8.1.0   | Plus Jakarta Sans (§16)                |
| Skeleton Loading     | `shimmer`                               | ^3.0.0   | Placeholder saat memuat data           |
| Analytics            | `firebase_analytics`                    | ^12.4.0  | Pelacakan event (§20)                  |
| Crash Reporting      | `firebase_crashlytics`                  | ^5.2.0   | Laporan galat otomatis                 |
| l10n                 | `flutter_localizations`                 | SDK      | Multi bahasa (opsional)                |

**Dev dependencies:** `flutter_test`, `mocktail`, `riverpod_lint`, `custom_lint`.

> Konfigurasi environment (dev/staging/prod) memakai `--dart-define`, **bukan**
> `flutter_dotenv` — lihat §21 untuk alasannya. Tidak ada package tambahan.

### Catatan Kompatibilitas

**GoRouter ↔ Riverpod tidak saling bergantung.** Anggapan bahwa "GoRouter
membutuhkan Riverpod versi tertentu" itu keliru: `go_router` hanya bergantung
pada `collection`, `logging`, dan `meta`, dan Riverpod tidak menyebut GoRouter
sama sekali. Yang benar-benar mengikat keduanya adalah **versi Dart SDK** —
`go_router` 17 butuh Dart `^3.10`, `flutter_riverpod` 3 butuh Dart `^3.12`.
Keduanya aman di Dart 3.12.

**Riverpod 3 wajib, bukan opsional.** Riverpod 2.x tidak dites untuk Dart 3.12
dan sudah tidak dirawat. Konsekuensinya ada breaking change pada pola penulisan
provider — lihat §4.4.

**Firebase harus sekeluarga.** `firebase_core` dan `firebase_messaging` dirilis
berpasangan; menaikkan salah satu saja sering memicu konflik di build Android.

**Dihapus dari daftar:**

- `freezed` / `freezed_annotation` — versi stabilnya belum menjangkau Dart 3.12.
  Untuk sementara pakai `json_serializable` + kelas immutable manual
  (`final` field + `copyWith`). Tambahkan kembali setelah Freezed 4 stabil.
- `pull_to_refresh_flutter3` — tidak lagi dirawat. Pakai `RefreshIndicator`
  bawaan Flutter yang sudah memadai.

---

## 3. STRUKTUR PROYEK

```
lib/
├── app.dart                    # MaterialApp + GoRouter
├── main.dart                   # Entry point (initialize)
├── core/
│   ├── constants/
│   │   ├── api_constants.dart      # Base URL, endpoints
│   │   ├── app_colors.dart         # Warna sesuai brand guideline
│   │   └── route_constants.dart    # Nama & path rute GoRouter (anti hardcode)
│   ├── enums/                      # Cerminan Enum backend
│   │   ├── order_status.dart
│   │   ├── request_status.dart
│   │   ├── offer_status.dart
│   │   └── store_type.dart
│   ├── errors/
│   │   ├── exceptions.dart
│   │   └── failure.dart
│   ├── network/
│   │   ├── dio_client.dart         # Dio instance + interceptors
│   │   └── api_response.dart       # Wrapper response
│   ├── services/                   # Pembungkus SDK/platform (bukan REST API)
│   │   ├── location_service.dart      # GPS, izin lokasi, reverse geocoding
│   │   ├── notification_service.dart  # FCM: token, izin, handler pesan
│   │   ├── analytics_service.dart     # Firebase Analytics & Crashlytics
│   │   ├── storage_service.dart       # Secure storage token, prefs
│   │   └── deep_link_service.dart     # app_links / universal link
│   ├── theme/
│   │   └── app_theme.dart          # ThemeData, TextTheme
│   ├── utils/
│   │   ├── validators.dart
│   │   └── formatters.dart
│   └── widgets/                    # Widget global, TIDAK terikat fitur apa pun
│       ├── custom_button.dart
│       ├── loading_indicator.dart
│       └── error_widget.dart
├── data/
│   ├── datasources/
│   │   └── remote/
│   │       ├── auth_remote_datasource.dart
│   │       ├── store_remote_datasource.dart
│   │       ├── listing_remote_datasource.dart
│   │       └── ...
│   ├── models/                 # DTO, JSON serializable
│   │   ├── user_model.dart
│   │   ├── store_model.dart
│   │   ├── listing_model.dart
│   │   └── ...
│   └── repositories/           # Implementasi interface domain
│       ├── auth_repository_impl.dart
│       ├── store_repository_impl.dart
│       └── ...
├── domain/
│   ├── entities/               # Objek domain (sealed/freezed)
│   │   ├── user.dart
│   │   ├── store.dart
│   │   ├── listing.dart
│   │   └── ...
│   ├── repositories/           # Interface
│   │   ├── auth_repository.dart
│   │   ├── store_repository.dart
│   │   └── ...
│   └── usecases/               # Aksi bisnis
│       ├── auth/
│       │   ├── request_otp.dart
│       │   ├── verify_otp.dart
│       │   └── get_profile.dart
│       ├── stores/
│       │   ├── get_nearby_stores.dart
│       │   └── create_store.dart
│       └── ...
├── presentation/
│   ├── providers/              # Riverpod AsyncNotifier (state per fitur)
│   │   ├── auth_provider.dart
│   │   ├── store_provider.dart
│   │   ├── listing_provider.dart
│   │   ├── request_provider.dart
│   │   ├── offer_provider.dart
│   │   ├── order_provider.dart
│   │   └── location_provider.dart
│   ├── pages/
│   │   ├── splash/
│   │   ├── auth/
│   │   │   ├── login_page.dart
│   │   │   ├── otp_page.dart
│   │   │   └── register_page.dart
│   │   ├── home/
│   │   │   └── main_shell.dart  # Scaffold with BottomNav
│   │   ├── explore/            # Tab Jelajahi
│   │   │   ├── explore_page.dart
│   │   │   ├── listing_detail_page.dart
│   │   │   └── widgets/        # Widget khusus halaman explore saja
│   │   │       ├── explore_filter_sheet.dart
│   │   │       └── explore_map_view.dart
│   │   ├── requests/           # Tab Kebutuhan
│   │   │   ├── requests_page.dart
│   │   │   ├── create_request_page.dart
│   │   │   ├── request_detail_page.dart
│   │   │   └── compare_offers_page.dart
│   │   ├── orders/             # Tab Transaksi
│   │   │   ├── orders_page.dart
│   │   │   └── order_detail_page.dart
│   │   ├── store/              # Manajemen Toko
│   │   │   ├── my_store_page.dart
│   │   │   ├── create_store_page.dart
│   │   │   └── edit_listing_page.dart
│   │   └── profile/
│   │       ├── profile_page.dart
│   │       ├── edit_profile_page.dart
│   │       └── verification_page.dart
│   └── widgets/               # Widget lintas fitur yang terikat domain
│       ├── listing_card.dart
│       ├── store_card.dart
│       ├── request_card.dart
│       └── offer_card.dart
└── l10n/                       # Opsional
```

### Tiga Lokasi Widget — Kapan Pakai yang Mana

Ini sumber kebingungan yang paling sering. Aturannya berdasarkan **seberapa
luas widget itu dipakai** dan **apakah ia tahu soal domain Seekitar**:

| Lokasi                             | Tahu domain? | Contoh                                          | Aturan                                                        |
| :--------------------------------- | :----------- | :---------------------------------------------- | :-------------------------------------------------------------- |
| `core/widgets/`                    | ❌ Tidak     | `CustomButton`, `LoadingIndicator`              | Bisa disalin ke proyek Flutter lain tanpa diubah              |
| `presentation/widgets/`            | ✅ Ya        | `ListingCard`, `StoreCard`, `OfferCard`         | Menerima entity domain, dipakai **lebih dari satu** halaman    |
| `presentation/pages/<fitur>/widgets/` | ✅ Ya     | `ExploreFilterSheet`, `OfferComparisonRow`      | Hanya dipakai **satu** halaman                                 |

**Tes cepat saat ragu:**

1. Apakah widget ini menyebut tipe domain (`Store`, `Listing`, `Offer`)?
   Jika **tidak** → `core/widgets/`.
2. Jika ya, apakah dipakai di lebih dari satu halaman?
   Ya → `presentation/widgets/`. Tidak → folder `widgets/` di dalam halaman itu.

**Aturan promosi:** mulai dari yang paling sempit. Begitu sebuah widget dipakai
halaman kedua, pindahkan ke `presentation/widgets/`. Jangan langsung menaruh
semua widget di folder global "untuk berjaga-jaga".

> `core/widgets/` **tidak boleh** meng-import apa pun dari `domain/` atau
> `data/`. Kalau sampai perlu, berarti widget itu salah tempat.

### `core/services/` vs `data/repositories/`

Keduanya sama-sama "layanan", tapi tanggung jawabnya berbeda:

| | `core/services/` | `data/repositories/` |
| :-- | :-- | :-- |
| Bicara dengan | SDK perangkat (GPS, FCM, storage) | REST API backend |
| Contoh | `LocationService.getCurrentPosition()` | `StoreRepository.getNearbyStores()` |
| Bergantung pada | Plugin Flutter | `Dio` + datasource |

Alur khasnya menggabungkan keduanya: `LocationService` mengambil koordinat GPS,
lalu koordinat itu dikirim ke `StoreRepository` untuk mencari toko sekitar.

```dart
// core/services/location_service.dart
class LocationService {
  Future<Position> getCurrentPosition() async {
    final permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      final requested = await Geolocator.requestPermission();
      if (requested == LocationPermission.denied) {
        throw const LocationPermissionDeniedException();
      }
    }
    if (permission == LocationPermission.deniedForever) {
      throw const LocationPermissionDeniedException();
    }
    return Geolocator.getCurrentPosition();
  }
}

// Diekspos sebagai provider agar mudah di-mock saat testing.
@riverpod
LocationService locationService(Ref ref) => LocationService();
```

### `core/constants/route_constants.dart`

Path rute ditulis di dua tempat — saat mendaftarkan `GoRoute` dan saat navigasi.
Kalau di-hardcode, salah ketik baru ketahuan waktu runtime. Pusatkan:

```dart
// core/constants/route_constants.dart
abstract final class Routes {
  static const splash         = '/splash';
  static const login          = '/login';
  static const otp            = '/otp';
  static const completeProfile = '/complete-profile';
  static const explore        = '/';
  static const requests       = '/requests';
  static const createRequest  = '/requests/create';
  static const orders         = '/orders';
  static const profile        = '/profile';
  static const myStore        = '/store';
  static const favorites      = '/favorites';

  // Rute berparameter: sediakan pola sekaligus pembangunnya.
  static const listingDetail = '/listing/:id';
  static String listingDetailOf(String id) => '/listing/$id';

  static const requestDetail = '/requests/:id';
  static String requestDetailOf(String id) => '/requests/$id';

  static const orderDetail = '/orders/:id';
  static String orderDetailOf(String id) => '/orders/$id';
}
```

Pemakaian:

```dart
GoRoute(path: Routes.login, builder: (_, __) => const LoginPage()),

// Navigasi — tidak ada string mentah:
context.go(Routes.listingDetailOf(listing.id));
```

---

## 4. STATE MANAGEMENT & DEPENDENCY INJECTION (RIVERPOD)

### 4.1 Providers Global

Dependensi global **dideklarasikan sebagai provider**, bukan dibuat di `main()`
lalu di-override. Dengan begitu `dio` dan `secureStorage` bisa diakses dari
mana saja lewat `ref.read(...)`, dan tetap mudah diganti saat pengujian.

```dart
// core/network/dio_client.dart
@Riverpod(keepAlive: true)
Dio dio(Ref ref) {
  final client = Dio(BaseOptions(
    baseUrl: Env.apiBaseUrl,
    connectTimeout: const Duration(seconds: 10),
    receiveTimeout: const Duration(seconds: 10),
  ));
  client.interceptors.add(AuthInterceptor(ref));
  return client;
}

// core/services/storage_service.dart
@Riverpod(keepAlive: true)
FlutterSecureStorage secureStorage(Ref ref) => const FlutterSecureStorage(
      aOptions: AndroidOptions(encryptedSharedPreferences: true),
    );
```

`keepAlive: true` penting: tanpa itu `Dio` akan dibuang begitu tidak ada
listener, sehingga koneksi dan interceptor dibangun ulang berkali-kali.

`main()` kini hanya mengurus inisialisasi platform:

```dart
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);

  runApp(
    const ProviderScope(
      // Nonaktifkan auto-retry Riverpod 3 (lihat §4.4).
      retry: _noRetry,
      child: SeekitarApp(),
    ),
  );
}

Duration? _noRetry(int retryCount, Object error) => null;
```

**Override hanya dipakai di test**, bukan di produksi:

```dart
ProviderScope(
  overrides: [
    dioProvider.overrideWithValue(mockDio),
    secureStorageProvider.overrideWithValue(FakeSecureStorage()),
  ],
  child: const SeekitarApp(),
);
```

### 4.2 Provider Typikal (Auth)

```dart
@riverpod
class AuthNotifier extends _$AuthNotifier {
  @override
  FutureOr<User?> build() async {
    final storage = ref.read(storageServiceProvider);
    final token = await storage.readToken();

    if (token == null) return null;

    try {
      return await ref.read(authRepositoryProvider).getProfile();
    } on UnauthorizedException {
      // Token ditolak server (kedaluwarsa/dicabut admin). Bersihkan lalu
      // perlakukan sebagai belum login — JANGAN lempar error, karena itu
      // membuat aplikasi macet di layar error saat dibuka.
      await storage.clearToken();
      return null;
    } on NetworkException {
      // Sedang offline: token belum tentu tidak valid, jadi jangan dihapus.
      // Lempar agar UI bisa menampilkan tombol "Coba lagi".
      rethrow;
    }
  }

  Future<void> login(String phone, String otp) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() async {
      final auth = await ref.read(authRepositoryProvider).verifyOtp(phone, otp);
      // await — tanpa ini token bisa belum tersimpan saat request berikutnya jalan.
      await ref.read(storageServiceProvider).saveToken(auth.token);
      return auth.user;
    });
  }

  Future<void> logout() async {
    // Cabut token di server dulu; kegagalan jaringan tidak boleh
    // menghalangi pengguna keluar dari perangkatnya sendiri.
    try {
      await ref.read(authRepositoryProvider).logout();
    } on Exception catch (e, s) {
      ref.read(analyticsServiceProvider).recordError(e, s, fatal: false);
    }

    await ref.read(storageServiceProvider).clearToken();
    await ref.read(notificationServiceProvider).deleteToken();  // stop notifikasi
    state = const AsyncData(null);
  }
}
```

> ⚠️ Membedakan `UnauthorizedException` dari `NetworkException` itu penting.
> Menghapus token hanya karena perangkat sedang offline akan memaksa pengguna
> login ulang setiap kali sinyal hilang.

### 4.3 Provider untuk List (Nearby Stores)

Untuk daftar pendek tanpa paginasi, `family` sudah cukup:

```dart
@riverpod
Future<List<Store>> nearbyStores(Ref ref, {
  required double lat, required double lng, double radius = 10,
}) async {
  final repository = ref.read(storeRepositoryProvider);
  return repository.getNearbyStores(lat, lng, radius);
}
```

Untuk daftar panjang (katalog, papan kebutuhan) pakai `AsyncNotifier` dengan
`loadMore` — implementasi lengkapnya di §19.2. Perhatikan bahwa provider ini
**tidak** memakai `keepAlive`, sehingga hasil pencarian dibuang saat pengguna
meninggalkan halaman; ini disengaja agar data lokasi tidak basi.

### 4.4 Perubahan Riverpod 3 yang Wajib Diketahui

Proyek ini memakai **Riverpod 3**, yang membawa beberapa breaking change dari
pola Riverpod 2 yang banyak beredar di tutorial lama.

**1. Subclass `Ref` hasil codegen dihapus.** Tidak ada lagi `NearbyStoresRef`,
`DioRef`, dan sejenisnya — pakai `Ref` langsung:

```dart
// ❌ Riverpod 2 (tidak lagi berlaku)
@riverpod
Future<List<Store>> nearbyStores(NearbyStoresRef ref) async { ... }

// ✅ Riverpod 3
@riverpod
Future<List<Store>> nearbyStores(Ref ref) async { ... }
```

**2. `AsyncValue.valueOrNull` dihapus.** Gunakan `.value`, yang kini
mengembalikan `null` saat error (dulu melempar exception):

```dart
final user = ref.watch(authNotifierProvider).value; // bisa null
```

**3. Provider gagal kini auto-retry.** Secara default Riverpod mengulang dengan
backoff (mulai 200 ms, hingga 10 kali). Untuk request yang tidak layak diulang
— misalnya verifikasi OTP yang salah — matikan retry-nya agar user tidak
menunggu percobaan sia-sia:

```dart
ProviderScope(
  retry: (retryCount, error) => null, // nonaktifkan retry global
  child: SeekitarApp(),
)
```

**4. `StateProvider` & `StateNotifierProvider` dipindah** ke
`package:flutter_riverpod/legacy.dart`. Untuk kode baru, pakai `Notifier` /
`AsyncNotifier` saja.

---

## 5. LAYER DATA: API, REPOSITORIES, MODELS

### 5.1 Dio Client dengan Interceptors

Provider `dio` sudah didefinisikan di §4.1. Interceptornya:

```dart
class AuthInterceptor extends Interceptor {
  AuthInterceptor(this.ref);
  final Ref ref;

  @override
  Future<void> onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final token = await ref.read(storageServiceProvider).readToken();
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }

  @override
  Future<void> onError(DioException err, ErrorInterceptorHandler handler) async {
    final status = err.response?.statusCode;

    // 401: token tidak lagi berlaku -> paksa keluar.
    // 423: akun dibekukan admin -> keluar DAN tampilkan alasannya.
    if (status == 401 || status == 423) {
      await ref.read(storageServiceProvider).clearToken();
      ref.invalidate(authNotifierProvider);   // GoRouter otomatis ke /login

      if (status == 423) {
        ref.read(appMessengerProvider).showBlocked(
          err.response?.data['message'] as String? ?? 'Akun Anda dibekukan.',
        );
      }
    }

    handler.next(err);
  }
}
```

> ⚠️ **Tidak ada mekanisme refresh token.** Backend memakai Sanctum dengan
> token berumur panjang (30 hari) tanpa endpoint refresh — lihat
> `API_DOCUMENTATION.md` §2.2. Jadi menambahkan antrian *retry-after-refresh*
> di interceptor justru sia-sia: satu-satunya pemulihan dari `401` adalah
> mengulang alur OTP.
>
> Bedakan `401` dari `423`: yang pertama bisa dipulihkan dengan login ulang,
> yang kedua **tidak** — akun dibekukan, jadi pengguna harus diberi tahu
> alasannya alih-alih dilempar ke layar login berulang kali.

**Logging hanya di mode debug** — `LogInterceptor` mencetak seluruh body,
termasuk OTP dan token:

```dart
if (kDebugMode) {
  client.interceptors.add(LogInterceptor(requestBody: true, responseBody: true));
}
```

### 5.2 Model (User)

Memakai `json_serializable` dengan kelas immutable manual. **Bukan `freezed`** —
versi stabil Freezed belum menjangkau Dart 3.12 (lihat §2).

```dart
@JsonSerializable()
class UserModel {
  const UserModel({
    required this.id,
    required this.phone,
    required this.name,
    required this.verificationLevel,
    this.avatarUrl,
    this.location,
  });

  final String id;
  final String phone;
  final String name;

  @JsonKey(name: 'verification_level')
  final int verificationLevel;

  @JsonKey(name: 'avatar_url')
  final String? avatarUrl;

  @JsonKey(fromJson: geoPointFromJson, toJson: geoPointToJson)
  final GeoPoint? location;

  factory UserModel.fromJson(Map<String, dynamic> json) => _$UserModelFromJson(json);
  Map<String, dynamic> toJson() => _$UserModelToJson(this);

  /// Konversi ke entity domain (lapisan domain tidak tahu soal JSON).
  User toEntity() => User(
        id: id,
        phone: phone,
        name: name,
        avatarUrl: avatarUrl,
        location: location,
        verificationLevel: VerificationLevel.fromValue(verificationLevel),
      );
}
```

Backend memakai `snake_case`, Dart memakai `camelCase`. Daripada menulis
`@JsonKey(name: ...)` di setiap field, setel sekali di `build.yaml`:

```yaml
targets:
  $default:
    builders:
      json_serializable:
        options:
          field_rename: snake
          create_to_json: true
```

**Koordinat** datang sebagai GeoJSON `[longitude, latitude]` — urutannya
terbalik dari kebiasaan menulis "lat, lng" (`API_DOCUMENTATION.md` §12.3):

```dart
GeoPoint? geoPointFromJson(Map<String, dynamic>? json) {
  if (json == null) return null;
  final coords = (json['coordinates'] as List).cast<num>();
  return GeoPoint(longitude: coords[0].toDouble(), latitude: coords[1].toDouble());
}
```

### 5.3 Repository Implementation

Repository **wajib** mengubah `DioException` menjadi exception domain, supaya
lapisan presentation tidak perlu tahu apa pun tentang Dio:

```dart
class AuthRepositoryImpl implements AuthRepository {
  AuthRepositoryImpl(this._dio);
  final Dio _dio;

  @override
  Future<AuthResult> verifyOtp(String phone, String otp) async {
    try {
      final res = await _dio.post('/auth/verify-otp', data: {'phone': phone, 'otp': otp});
      final data = res.data['data'] as Map<String, dynamic>;

      return AuthResult(
        token: data['token'] as String,
        user: UserModel.fromJson(data['user']).toEntity(),
      );
    } on DioException catch (e) {
      throw mapDioException(e);
    }
  }
}
```

Pemetaannya dipusatkan agar konsisten di seluruh repository:

```dart
// core/errors/exceptions.dart
AppException mapDioException(DioException e) {
  // Tidak ada respons sama sekali = masalah jaringan, bukan masalah server.
  if (e.type == DioExceptionType.connectionTimeout ||
      e.type == DioExceptionType.receiveTimeout ||
      e.type == DioExceptionType.connectionError) {
    return const NetworkException('Koneksi bermasalah. Periksa jaringan Anda.');
  }

  final res = e.response;
  final message = res?.data is Map ? res!.data['message'] as String? : null;

  return switch (res?.statusCode) {
    401 => UnauthorizedException(message ?? 'Sesi berakhir, silakan masuk lagi.'),
    403 => ForbiddenException(message ?? 'Anda tidak berhak melakukan ini.'),
    404 => NotFoundException(message ?? 'Data tidak ditemukan.'),
    409 => ConflictException(message ?? 'Aksi ini sudah pernah dilakukan.'),
    // 422 membawa detail per-field untuk ditandai di form.
    422 => ValidationException(
        message ?? 'Data tidak valid.',
        errors: (res?.data['errors'] as Map?)?.map(
          (k, v) => MapEntry(k as String, (v as List).cast<String>()),
        ) ?? {},
      ),
    423 => AccountBlockedException(message ?? 'Akun Anda dibekukan.'),
    429 => RateLimitException(
        message ?? 'Terlalu banyak percobaan.',
        retryAfter: int.tryParse(res?.headers.value('retry-after') ?? ''),
      ),
    _   => ServerException(message ?? 'Terjadi kesalahan pada server.'),
  };
}
```

> `RateLimitException` membawa `retryAfter` dari header respons, sehingga
> layar OTP bisa menampilkan hitung mundur yang akurat alih-alih pesan generik.

---

## 6. LAYER DOMAIN: USECASES & ENTITIES

### 6.1 Entities (pure Dart)

Entity tidak mengenal JSON maupun Dio — murni Dart, sehingga mudah diuji.

```dart
class User {
  const User({
    required this.id,
    required this.phone,
    required this.name,
    required this.verificationLevel,
    this.avatarUrl,
    this.location,
  });

  final String id;
  final String phone;
  final String name;
  final VerificationLevel verificationLevel;
  final String? avatarUrl;
  final GeoPoint? location;

  /// Aturan bisnis ikut di entity, bukan tersebar di widget.
  bool get canOpenStore => verificationLevel.value >= 2;
  bool get isProfileComplete => name.isNotEmpty && location != null;

  User copyWith({String? name, String? avatarUrl, GeoPoint? location}) => User(
        id: id,
        phone: phone,
        name: name ?? this.name,
        verificationLevel: verificationLevel,
        avatarUrl: avatarUrl ?? this.avatarUrl,
        location: location ?? this.location,
      );

  // Riverpod 3 memakai '==' untuk menyaring rebuild, jadi ini wajib —
  // tanpanya setiap refresh dianggap perubahan dan UI rebuild sia-sia.
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is User && other.id == id && other.name == name &&
      other.avatarUrl == avatarUrl && other.location == location &&
      other.verificationLevel == verificationLevel;

  @override
  int get hashCode => Object.hash(id, name, avatarUrl, location, verificationLevel);
}
```

Enum domain mencerminkan nilai backend (`DATABASE.md` §4.1):

```dart
enum VerificationLevel {
  basic(1), verified(2), pro(3);

  const VerificationLevel(this.value);
  final int value;

  static VerificationLevel fromValue(int v) =>
      values.firstWhere((e) => e.value == v, orElse: () => basic);
}
```

> ⚠️ `==` dan `hashCode` ditulis manual karena Freezed belum dipakai. Kalau
> terlupa, Riverpod menganggap setiap objek baru sebagai perubahan state dan
> widget rebuild terus-menerus. Begitu Freezed 4 stabil, semua boilerplate ini
> bisa dihapus.

### 6.2 Usecase (Request OTP)

```dart
class RequestOtp {
  final AuthRepository repository;
  RequestOtp(this.repository);
  Future<void> call(String phone) => repository.requestOtp(phone);
}
```

Usecase dapat di-inject menggunakan provider.

---

## 7. LAYER PRESENTATION: HALAMAN & WIDGET

### 7.1 Halaman Login/OTP

Halaman dengan state lokal (controller, flag loading) memakai
`ConsumerStatefulWidget` — `TextEditingController` pada `ConsumerWidget` akan
bocor karena dibuat ulang setiap rebuild.

```dart
class LoginPage extends ConsumerStatefulWidget {
  const LoginPage({super.key});
  @override
  ConsumerState<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends ConsumerState<LoginPage> {
  final _formKey = GlobalKey<FormState>();
  final _phoneCtrl = TextEditingController();
  bool _isSubmitting = false;

  @override
  void dispose() {
    _phoneCtrl.dispose();   // wajib, kalau tidak controller bocor
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() || _isSubmitting) return;

    setState(() => _isSubmitting = true);
    final phone = _normalizePhone(_phoneCtrl.text);

    try {
      await ref.read(authRepositoryProvider).requestOtp(phone);
      if (!mounted) return;                         // widget bisa sudah dilepas
      context.push(Routes.otp, extra: phone);       // push, agar bisa kembali
    } on RateLimitException catch (e) {
      _showError('Terlalu banyak percobaan. Coba lagi dalam ${e.retryAfter ?? 60} detik.');
    } on AppException catch (e) {
      _showError(e.message);
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  void _showError(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  /// 08xxx / +62xxx / 62xxx -> 62xxx (format yang diterima backend).
  String _normalizePhone(String input) {
    final digits = input.replaceAll(RegExp(r'\D'), '');
    if (digits.startsWith('0'))  return '62${digits.substring(1)}';
    if (digits.startsWith('62')) return digits;
    return '62$digits';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text('Masuk ke Seekitar',
                    style: Theme.of(context).textTheme.headlineMedium),
                const SizedBox(height: 24),
                TextFormField(
                  controller: _phoneCtrl,
                  keyboardType: TextInputType.phone,
                  autofillHints: const [AutofillHints.telephoneNumber],
                  enabled: !_isSubmitting,
                  decoration: const InputDecoration(
                    labelText: 'Nomor WhatsApp',
                    hintText: '08123456789',
                    prefixIcon: Icon(Icons.phone),
                  ),
                  validator: (v) {
                    final digits = (v ?? '').replaceAll(RegExp(r'\D'), '');
                    if (digits.isEmpty) return 'Nomor WhatsApp wajib diisi';
                    if (digits.length < 10 || digits.length > 15) {
                      return 'Nomor tidak valid';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                CustomButton(
                  label: 'Minta OTP',
                  isLoading: _isSubmitting,
                  onPressed: _isSubmitting ? null : _submit,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
```

Tombolnya menampilkan indikator sekaligus mencegah klik ganda — pengiriman OTP
dua kali akan langsung kena rate limit 3×/menit:

```dart
class CustomButton extends StatelessWidget {
  const CustomButton({super.key, required this.label, this.onPressed, this.isLoading = false});

  final String label;
  final VoidCallback? onPressed;
  final bool isLoading;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      height: 48,
      child: FilledButton(
        onPressed: isLoading ? null : onPressed,
        child: isLoading
            ? const SizedBox(
                width: 20, height: 20,
                child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
              )
            : Text(label),
      ),
    );
  }
}
```

> Untuk proses yang memblokir seluruh layar (mis. mengunggah KTP), pakai
> `AbsorbPointer` + overlay gelap, bukan sekadar tombol loading.

### 7.2 Halaman Detail Listing

Menampilkan slider foto (`PageView` + `cached_network_image`), deskripsi,
profil toko mini, tombol “Pesan Sekarang” & “Tanya Penjual”.

**“Pesan Sekarang”** membuka bottom sheet, bukan halaman baru — pengguna tetap
melihat produknya sambil memilih opsi:

```dart
Future<void> _showOrderSheet(BuildContext context, WidgetRef ref, Listing listing) async {
  final user = ref.read(authNotifierProvider).value;

  // Belum login: simpan tujuan agar bisa kembali setelah masuk.
  if (user == null) {
    context.push('${Routes.login}?redirect=${Uri.encodeComponent(Routes.listingDetailOf(listing.id))}');
    return;
  }

  final created = await showModalBottomSheet<Order>(
    context: context,
    isScrollControlled: true,          // agar tidak tertutup keyboard
    useSafeArea: true,
    builder: (_) => OrderFormSheet(listing: listing),
  );

  if (created != null && context.mounted) {
    context.push(Routes.orderDetailOf(created.id));
  }
}
```

Isi `OrderFormSheet` mengikuti kontrak `POST /orders`
(`API_DOCUMENTATION.md` §7.1): jumlah (khusus `product`), metode pembayaran
(`cod`/`transfer`), metode pengiriman (`pickup`/`delivery`), dan alamat yang
**wajib** muncul hanya bila memilih `delivery`.

---

## 8. NAVIGASI & ROUTING (GOROUTER)

> ⚠️ **Router harus jadi provider, bukan variabel global.** Contoh yang beredar
> luas menulis `final goRouter = GoRouter(redirect: (c, s) { ref.read(...) })`
> — itu **tidak akan kompilasi**, karena `ref` tidak ada di lingkup variabel
> top-level. Selain itu router perlu tahu kapan status login berubah agar
> redirect dievaluasi ulang.

Path diambil dari `Routes` (lihat §3), bukan ditulis manual:

```dart
@Riverpod(keepAlive: true)   // router tidak boleh dibuang saat rebuild
GoRouter goRouter(Ref ref) {
  // watch: setiap perubahan status auth memicu evaluasi ulang redirect.
  final auth = ref.watch(authNotifierProvider);

  return GoRouter(
    initialLocation: Routes.splash,
    debugLogDiagnostics: kDebugMode,
    navigatorKey: rootNavigatorKey,      // dipakai deep link & notifikasi (§22)

    redirect: (context, state) {
      // Selama status auth belum diketahui, tahan di splash — tanpa ini
      // pengguna yang sudah login sempat "berkedip" ke halaman login.
      if (auth.isLoading) {
        return state.matchedLocation == Routes.splash ? null : Routes.splash;
      }

      final loggedIn = auth.value != null;
      final atAuthPage = state.matchedLocation == Routes.login ||
                         state.matchedLocation == Routes.otp ||
                         state.matchedLocation == Routes.splash;

      if (!loggedIn) {
        if (atAuthPage) return null;
        // Simpan tujuan asal agar bisa dikembalikan setelah login.
        return '${Routes.login}?redirect=${Uri.encodeComponent(state.matchedLocation)}';
      }

      // Sudah login tapi profil belum lengkap -> paksa lengkapi dulu.
      // Ini mencerminkan middleware EnsureProfileComplete di backend.
      if (!auth.value!.isProfileComplete && state.matchedLocation != Routes.completeProfile) {
        return Routes.completeProfile;
      }

      if (atAuthPage) {
        return state.uri.queryParameters['redirect'] ?? Routes.explore;
      }
      return null;
    },

    routes: [
      GoRoute(path: Routes.splash, builder: (_, __) => const SplashPage()),
      GoRoute(path: Routes.login, builder: (_, __) => const LoginPage()),
      GoRoute(
        path: Routes.otp,
        builder: (_, state) => OtpPage(phone: state.extra! as String),
      ),
      GoRoute(path: Routes.completeProfile, builder: (_, __) => const CompleteProfilePage()),

      // StatefulShellRoute: tiap tab punya tumpukan navigasi sendiri, jadi
      // posisi scroll & halaman detail tidak hilang saat berpindah tab.
      StatefulShellRoute.indexedStack(
        builder: (_, __, shell) => MainShell(shell: shell),
        branches: [
          StatefulShellBranch(routes: [
            GoRoute(
              path: Routes.explore,
              builder: (_, __) => const ExplorePage(),
              routes: [
                GoRoute(
                  path: 'listing/:id',        // path relatif terhadap induk
                  builder: (_, state) => ListingDetailPage(id: state.pathParameters['id']!),
                ),
              ],
            ),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(
              path: Routes.requests,
              builder: (_, __) => const RequestsPage(),
              routes: [
                GoRoute(path: 'create', builder: (_, __) => const CreateRequestPage()),
                GoRoute(
                  path: ':id',
                  builder: (_, state) => RequestDetailPage(id: state.pathParameters['id']!),
                ),
              ],
            ),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(path: Routes.orders, builder: (_, __) => const OrdersPage()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(path: Routes.profile, builder: (_, __) => const ProfilePage()),
          ]),
        ],
      ),
    ],

    errorBuilder: (_, state) => NotFoundPage(uri: state.uri),
  );
}
```

Dipakai di `app.dart`:

```dart
class SeekitarApp extends ConsumerWidget {
  const SeekitarApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return MaterialApp.router(
      title: 'Seekitar',
      theme: AppTheme.light,
      routerConfig: ref.watch(goRouterProvider),
      debugShowCheckedModeBanner: false,
    );
  }
}
```

---

## 9. AUTENTIKASI & OTP FLOW

1. User masukkan nomor HP → panggil `POST /auth/request-otp`.
2. Navigasi ke halaman OTP (bawa phone).
3. User masukkan 6 digit OTP → panggil `POST /auth/verify-otp`.
4. Simpan token di secure storage, update `AuthNotifier`.
5. GoRouter otomatis redirect ke home.

---

## 10. HALAMAN UTAMA & BOTTOM NAVIGATION

`MainShell` menerima `StatefulNavigationShell` dari router (§8), yang sudah
mengurus indeks aktif dan tumpukan navigasi per tab.

```dart
class MainShell extends StatelessWidget {
  const MainShell({super.key, required this.shell});
  final StatefulNavigationShell shell;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: shell,
      bottomNavigationBar: NavigationBar(
        selectedIndex: shell.currentIndex,
        onDestinationSelected: (index) => shell.goBranch(
          index,
          // Menekan tab yang sedang aktif akan kembali ke akar tab itu —
          // perilaku standar yang diharapkan pengguna.
          initialLocation: index == shell.currentIndex,
        ),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.explore_outlined),
              selectedIcon: Icon(Icons.explore), label: 'Jelajahi'),
          NavigationDestination(icon: Icon(Icons.campaign_outlined),
              selectedIcon: Icon(Icons.campaign), label: 'Kebutuhan'),
          NavigationDestination(icon: Icon(Icons.receipt_long_outlined),
              selectedIcon: Icon(Icons.receipt_long), label: 'Transaksi'),
          NavigationDestination(icon: Icon(Icons.person_outline),
              selectedIcon: Icon(Icons.person), label: 'Profil'),
        ],
      ),
    );
  }
}
```

`NavigationBar` (Material 3) dipakai alih-alih `BottomNavigationBar` yang lebih
lama, agar konsisten dengan tema aplikasi.

> Tab "Kebutuhan" perlu badge saat ada penawaran baru masuk. Bungkus ikonnya
> dengan `Badge` dan hubungkan ke `unreadOffersProvider`.

---

## 11. FITUR MARKETPLACE (JELAJAHI)

**ExplorePage:**

- `AppBar` dengan search field (debounce 500 ms).
- Filter chips horizontal (kategori).
- `RefreshIndicator` + list `ListingCard`.
- FAB: “Pasang Kebutuhan”.
- Infinite scroll lewat `ListingSearchNotifier` (§19.2).

### 11.1 Search dengan Debounce

Tanpa debounce, mengetik "beras" mengirim 5 request; empat di antaranya sia-sia
dan berisiko kena rate limit 60/menit.

```dart
class _SearchField extends ConsumerStatefulWidget {
  const _SearchField();
  @override
  ConsumerState<_SearchField> createState() => _SearchFieldState();
}

class _SearchFieldState extends ConsumerState<_SearchField> {
  final _controller = TextEditingController();
  Timer? _debounce;

  @override
  void dispose() {
    _debounce?.cancel();     // wajib: timer aktif setelah dispose = crash
    _controller.dispose();
    super.dispose();
  }

  void _onChanged(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 500), () {
      // Abaikan kueri terlalu pendek; FULLTEXT butuh minimal 3 karakter.
      if (value.isNotEmpty && value.trim().length < 3) return;
      ref.read(listingFilterProvider.notifier).setKeyword(value.trim());
    });
  }

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: _controller,
      onChanged: _onChanged,
      textInputAction: TextInputAction.search,
      // Enter = cari seketika, tanpa menunggu debounce.
      onSubmitted: (v) {
        _debounce?.cancel();
        ref.read(listingFilterProvider.notifier).setKeyword(v.trim());
      },
      decoration: InputDecoration(
        hintText: 'Cari produk atau jasa…',
        prefixIcon: const Icon(Icons.search),
        suffixIcon: _controller.text.isEmpty ? null : IconButton(
          icon: const Icon(Icons.clear),
          onPressed: () {
            _controller.clear();
            _debounce?.cancel();
            ref.read(listingFilterProvider.notifier).setKeyword('');
          },
        ),
      ),
    );
  }
}
```

Filter disimpan di satu notifier agar perubahan apa pun memicu pencarian ulang:

```dart
@riverpod
class ListingFilter extends _$ListingFilter {
  @override
  ListingQuery build() => const ListingQuery();

  void setKeyword(String v)     => state = state.copyWith(keyword: v);
  void setCategory(int? id)     => state = state.copyWith(categoryId: id);
  void setType(ListingType? t)  => state = state.copyWith(type: t);
  void setSort(ListingSort s)   => state = state.copyWith(sort: s);
  void reset()                  => state = const ListingQuery();
}
```

### 11.2 Filter Chips Kategori

```dart
class CategoryChips extends ConsumerWidget {
  const CategoryChips({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final categories = ref.watch(categoriesProvider);
    final selected   = ref.watch(listingFilterProvider.select((f) => f.categoryId));

    return SizedBox(
      height: 48,
      child: categories.when(
        loading: () => const _ChipsSkeleton(),
        error: (_, __) => const SizedBox.shrink(),   // filter opsional, jangan blokir layar
        data: (list) => ListView.separated(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          itemCount: list.length + 1,
          separatorBuilder: (_, __) => const SizedBox(width: 8),
          itemBuilder: (_, i) {
            if (i == 0) {
              return FilterChip(
                label: const Text('Semua'),
                selected: selected == null,
                onSelected: (_) => ref.read(listingFilterProvider.notifier).setCategory(null),
              );
            }
            final c = list[i - 1];
            return FilterChip(
              label: Text(c.name),
              selected: selected == c.id,
              // Menekan chip yang sudah aktif akan membatalkan filternya.
              onSelected: (on) => ref
                  .read(listingFilterProvider.notifier)
                  .setCategory(on ? c.id : null),
            );
          },
        ),
      ),
    );
  }
}
```

Daftar kategori jarang berubah, jadi di-cache seumur aplikasi:

```dart
@Riverpod(keepAlive: true)
Future<List<Category>> categories(Ref ref) =>
    ref.read(categoryRepositoryProvider).getAll();
```

`ref.watch(...select(...))` membuat chip hanya rebuild saat `categoryId`
berubah — bukan setiap kali pengguna mengetik di kolom pencarian.

**ListingCard:**

- `Card` dengan gambar, judul, harga, jarak, rating, toko.
- onTap → navigate ke detail listing.

---

## 12. FITUR PAPAN KEBUTUHAN (PASANG KEBUTUHAN)

**CreateRequestPage:**

- Form input: judul, deskripsi, kategori (dropdown), budget, lokasi (pilih dari map), radius, tanggal.
- Pin lokasi bisa geser di `GoogleMap`.
- Tombol “Pasang”.
- Panggil `POST /requests`, lalu kembali ke halaman Kebutuhan.

### 12.1 Pemilih Lokasi dengan Google Maps

Pin lokasi wajib dan bisa digeser (PRD §5.2.1). Pola yang dipakai: peta
statis dengan **pin tetap di tengah layar** — pengguna menggeser petanya, bukan
menyeret marker. Ini jauh lebih presisi di layar kecil karena jari tidak
menutupi titik yang dipilih.

```dart
class LocationPickerPage extends ConsumerStatefulWidget {
  const LocationPickerPage({super.key, this.initial});
  final LatLng? initial;
  @override
  ConsumerState<LocationPickerPage> createState() => _LocationPickerPageState();
}

class _LocationPickerPageState extends ConsumerState<LocationPickerPage> {
  GoogleMapController? _map;
  LatLng _center = const LatLng(-7.2575, 112.7521);   // Surabaya
  String? _address;
  Timer? _geocodeDebounce;

  @override
  void initState() {
    super.initState();
    _center = widget.initial ?? _center;
    if (widget.initial == null) _useCurrentLocation();
  }

  @override
  void dispose() {
    _geocodeDebounce?.cancel();
    _map?.dispose();
    super.dispose();
  }

  Future<void> _useCurrentLocation() async {
    try {
      final pos = await ref.read(locationServiceProvider).getCurrentPosition();
      final target = LatLng(pos.latitude, pos.longitude);
      setState(() => _center = target);
      await _map?.animateCamera(CameraUpdate.newLatLngZoom(target, 16));
      _reverseGeocode(target);
    } on LocationPermissionDeniedException {
      if (mounted) _showPermissionDialog();
    }
  }

  /// Reverse geocode di-debounce: tanpa ini, satu gesekan memicu puluhan
  /// panggilan dan kuota API cepat habis.
  void _onCameraIdle() {
    _geocodeDebounce?.cancel();
    _geocodeDebounce = Timer(const Duration(milliseconds: 600),
        () => _reverseGeocode(_center));
  }

  Future<void> _reverseGeocode(LatLng target) async {
    final addr = await ref.read(locationServiceProvider).addressOf(target);
    if (mounted) setState(() => _address = addr);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Pilih Lokasi')),
      body: Stack(
        alignment: Alignment.center,
        children: [
          GoogleMap(
            initialCameraPosition: CameraPosition(target: _center, zoom: 16),
            onMapCreated: (c) => _map = c,
            onCameraMove: (pos) => _center = pos.target,   // tanpa setState
            onCameraIdle: _onCameraIdle,
            myLocationEnabled: true,
            myLocationButtonEnabled: false,
            zoomControlsEnabled: false,
          ),

          // Pin tetap di tengah; diangkat setengah tingginya agar ujung
          // pin benar-benar menunjuk ke titik tengah peta.
          const Padding(
            padding: EdgeInsets.only(bottom: 40),
            child: Icon(Icons.location_pin, size: 40, color: Color(0xFF168A4A)),
          ),

          Positioned(
            left: 16, right: 16, bottom: 16,
            child: Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(_address ?? 'Mencari alamat…',
                        style: Theme.of(context).textTheme.bodyMedium),
                    const SizedBox(height: 12),
                    CustomButton(
                      label: 'Pilih Lokasi Ini',
                      onPressed: () => context.pop(
                        PickedLocation(latLng: _center, address: _address),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.small(
        onPressed: _useCurrentLocation,
        child: const Icon(Icons.my_location),
      ),
    );
  }
}
```

> ⚠️ `onCameraMove` **tidak** memanggil `setState` — dipanggil puluhan kali per
> detik saat menggeser, dan rebuild di situ membuat peta tersendat. Simpan ke
> variabel biasa, lalu perbarui UI di `onCameraIdle`.
>
> API key Google Maps dikonfigurasi per platform di `AndroidManifest.xml` dan
> `AppDelegate.swift`; jangan ditaruh di kode Dart.

### 12.2 RequestsPage — Dua Tab

- Tab “Kebutuhan Terbaru” (untuk penyedia) dan “Permintaan Saya” (untuk pembeli).
- Tiap item onTap → navigasi `RequestDetailPage`.

```dart
class RequestsPage extends ConsumerStatefulWidget {
  const RequestsPage({super.key});
  @override
  ConsumerState<RequestsPage> createState() => _RequestsPageState();
}

class _RequestsPageState extends ConsumerState<RequestsPage>
    with SingleTickerProviderStateMixin {
  late final TabController _tabs = TabController(length: 2, vsync: this);

  @override
  void dispose() {
    _tabs.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authNotifierProvider).value;
    final isProvider = user?.canOpenStore ?? false;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Kebutuhan'),
        bottom: TabBar(
          controller: _tabs,
          tabs: const [
            Tab(text: 'Kebutuhan Terbaru'),
            Tab(text: 'Permintaan Saya'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabs,
        children: [
          // Tab penyedia hanya relevan bagi pemilik toko terverifikasi.
          isProvider
              ? const NearbyRequestsTab()
              : const EmptyState(
                  icon: Icons.storefront_outlined,
                  title: 'Khusus Penyedia',
                  message: 'Buka toko untuk melihat kebutuhan di sekitar Anda.',
                ),
          const MyRequestsTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('${Routes.requests}/create'),
        icon: const Icon(Icons.add),
        label: const Text('Pasang Kebutuhan'),
      ),
    );
  }
}
```

`SingleTickerProviderStateMixin` + `dispose()` wajib — `TabController` yang
tidak dilepas akan terus memicu animasi setelah halaman ditutup.

### 12.3 CompareOffersPage — Sorting

Pembeli membandingkan penawaran berdasarkan tiga sumbu: harga, kecepatan,
reputasi.

```dart
enum OfferSort {
  cheapest('Termurah'),
  fastest('Tercepat'),
  bestRating('Rating Tertinggi'),
  nearest('Terdekat');

  const OfferSort(this.label);
  final String label;
}

@riverpod
List<Offer> sortedOffers(Ref ref, String requestId) {
  final offers = ref.watch(offersProvider(requestId)).value ?? const <Offer>[];
  final sort   = ref.watch(offerSortProvider);

  // Salin dulu: sort() mengubah list aslinya, dan memutasi state provider
  // secara langsung membuat Riverpod tidak mendeteksi perubahan.
  final list = [...offers];

  switch (sort) {
    case OfferSort.cheapest:
      list.sort((a, b) => a.price.compareTo(b.price));
    case OfferSort.fastest:
      // Penawaran tanpa estimasi numerik ditaruh di akhir, bukan dianggap 0.
      list.sort((a, b) => (a.estimatedHours ?? 1 << 30)
          .compareTo(b.estimatedHours ?? 1 << 30));
    case OfferSort.bestRating:
      list.sort((a, b) => b.store.ratingAvg.compareTo(a.store.ratingAvg));
    case OfferSort.nearest:
      list.sort((a, b) => a.store.distanceKm.compareTo(b.store.distanceKm));
  }
  return list;
}
```

```dart
DropdownButton<OfferSort>(
  value: ref.watch(offerSortProvider),
  items: [
    for (final s in OfferSort.values)
      DropdownMenuItem(value: s, child: Text(s.label)),
  ],
  onChanged: (v) => v == null ? null : ref.read(offerSortProvider.notifier).set(v),
)
```

**RequestDetailPage (pembeli):** detail permintaan + daftar offers, dengan
tombol “Terima” di tiap kartu. Menerima penawaran bersifat **final** — tampilkan
dialog konfirmasi, karena aksi itu menutup permintaan dan menolak semua
penawaran lain (`API_DOCUMENTATION.md` §6.3).

---

## 13. FITUR PENAWARAN & TRANSAKSI

- **OfferCard:** Menampilkan nama toko, harga, jarak, estimasi, rating.
- **Accept Offer:** Panggil `PATCH /offers/{id}/accept`, lalu muncul notifikasi sukses.
- **OrdersPage:** Riwayat pesanan, tab “Sebagai Pembeli” / “Sebagai Penjual”.
- **OrderDetailPage:** Timeline status (Stepper widget), aksi (konfirmasi, upload bukti), kontak penjual.

---

## 14. NOTIFIKASI PUSH (FCM)

```dart
class NotificationService {
  NotificationService(this._ref);
  final Ref _ref;

  final _fcm = FirebaseMessaging.instance;
  final _local = FlutterLocalNotificationsPlugin();

  Future<void> initialize() async {
    // iOS & Android 13+ WAJIB meminta izin secara eksplisit.
    final settings = await _fcm.requestPermission(
      alert: true, badge: true, sound: true,
      provisional: false,   // true = izin diam-diam, tapi notifikasi masuk senyap
    );

    if (settings.authorizationStatus == AuthorizationStatus.denied) {
      // Jangan memaksa. Catat saja, dan tawarkan lagi saat pengguna
      // memasang kebutuhan pertamanya (saat manfaatnya jelas).
      _ref.read(analyticsServiceProvider).logEvent('notification_permission_denied');
      return;
    }

    await _initLocalNotifications();
    await _registerToken();

    // Token bisa berubah sewaktu-waktu (reinstall, clear data, migrasi).
    _fcm.onTokenRefresh.listen((token) => _sendToken(token));

    _listenForeground();
    _listenNotificationTap();
  }

  Future<void> _registerToken() async {
    // Di iOS, token FCM baru tersedia setelah APNS token diterima.
    if (Platform.isIOS) {
      final apns = await _fcm.getAPNSToken();
      if (apns == null) return;      // coba lagi di sesi berikutnya
    }
    final token = await _fcm.getToken();
    if (token != null) await _sendToken(token);
  }

  Future<void> _sendToken(String token) =>
      _ref.read(deviceRepositoryProvider).registerToken(
            token: token,
            deviceId: await _deviceId(),
            platform: Platform.isIOS ? 'ios' : 'android',
          );

  /// Dipanggil saat logout — kalau tidak, pengguna berikutnya di perangkat
  /// yang sama akan menerima notifikasi milik akun sebelumnya.
  Future<void> deleteToken() async {
    await _ref.read(deviceRepositoryProvider).unregisterToken(await _deviceId());
    await _fcm.deleteToken();
  }
}
```

**Foreground.** Android tidak menampilkan notifikasi sistem saat aplikasi
terbuka; harus ditampilkan sendiri lewat `flutter_local_notifications`:

```dart
void _listenForeground() {
  FirebaseMessaging.onMessage.listen((message) {
    final notification = message.notification;
    if (notification == null) return;

    _local.show(
      message.hashCode,
      notification.title,
      notification.body,
      const NotificationDetails(
        android: AndroidNotificationDetails(
          'seekitar_default', 'Notifikasi Seekitar',
          importance: Importance.high, priority: Priority.high,
        ),
        iOS: DarwinNotificationDetails(),
      ),
      payload: jsonEncode(message.data),
    );

    // Segarkan data terkait agar layar yang terbuka ikut ter-update.
    _handleDataRefresh(message.data);
  });
}
```

**Deep link dari notifikasi.** Ada tiga jalur masuk yang harus ditangani
semuanya — melewatkan `getInitialMessage()` membuat notifikasi tidak berfungsi
saat aplikasi dalam keadaan mati:

| Kondisi aplikasi | Sumber event |
| :-- | :-- |
| Terbuka (foreground) | `onMessage` → notifikasi lokal → tap payload |
| Latar belakang | `onMessageOpenedApp` |
| Mati total | `getInitialMessage()` |

```dart
Future<void> _listenNotificationTap() async {
  // Aplikasi dibuka DARI notifikasi saat proses sudah mati.
  final initial = await _fcm.getInitialMessage();
  if (initial != null) _navigate(initial.data);

  FirebaseMessaging.onMessageOpenedApp.listen((m) => _navigate(m.data));
}

void _navigate(Map<String, dynamic> data) {
  final screen = data['screen'] as String?;
  final id     = data['entity_id'] as String?;
  if (screen == null || id == null) return;

  final path = switch (screen) {
    'request_detail' => Routes.requestDetailOf(id),
    'listing_detail' => Routes.listingDetailOf(id),
    'order_detail'   => Routes.orderDetailOf(id),
    _                => null,
  };
  if (path == null) return;

  // Router diakses lewat provider, bukan BuildContext — saat notifikasi
  // tiba, belum tentu ada context yang valid.
  _ref.read(goRouterProvider).push(path);
}
```

Payload `screen` dan `entity_id` dikirim backend di
`RequestBroadcastNotification` (`Server_Implementation_Guide.md` §15.1).

**Handler background** harus fungsi top-level, bukan method:

```dart
@pragma('vm:entry-point')
Future<void> firebaseBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  // Isolate terpisah: jangan sentuh state aplikasi di sini.
}

// di main(): FirebaseMessaging.onBackgroundMessage(firebaseBackgroundHandler);
```

---

## 15. INTEGRASI WHATSAPP

WhatsApp adalah **satu-satunya** kanal komunikasi di MVP (PRD §5.6), jadi
kegagalan membukanya tidak boleh berakhir senyap.

```dart
class WhatsAppService {
  const WhatsAppService(this._analytics);
  final AnalyticsService _analytics;

  Future<void> open(
    BuildContext context, {
    required String phone,
    required String message,
  }) async {
    final uri = Uri.parse('https://wa.me/$phone?text=${Uri.encodeComponent(message)}');

    // canLaunchUrl butuh <queries> di AndroidManifest (lihat catatan bawah),
    // dan tetap bisa false-negative — karena itu hasil launchUrl juga dicek.
    var opened = false;
    try {
      opened = await launchUrl(uri, mode: LaunchMode.externalApplication);
    } on PlatformException {
      opened = false;
    }

    _analytics.logEvent('whatsapp_click', {'success': opened});

    if (!opened && context.mounted) {
      await _showFallback(context, phone, message);
    }
  }

  /// WhatsApp tidak terpasang: tawarkan salin nomor atau buka WhatsApp Web.
  Future<void> _showFallback(BuildContext context, String phone, String message) {
    return showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('WhatsApp tidak tersedia'),
        content: Text('Nomor penjual: +$phone\n\n'
            'Aplikasi WhatsApp tidak ditemukan di perangkat ini.'),
        actions: [
          TextButton(
            onPressed: () async {
              await Clipboard.setData(ClipboardData(text: '+$phone'));
              if (ctx.mounted) Navigator.pop(ctx);
            },
            child: const Text('Salin Nomor'),
          ),
          TextButton(
            onPressed: () {
              launchUrl(Uri.parse('https://web.whatsapp.com/send?phone=$phone'),
                  mode: LaunchMode.externalApplication);
              Navigator.pop(ctx);
            },
            child: const Text('Buka WhatsApp Web'),
          ),
        ],
      ),
    );
  }
}
```

> ⚠️ Sejak Android 11, `canLaunchUrl` mengembalikan `false` untuk skema yang
> tidak dideklarasikan, meski aplikasinya terpasang. Tambahkan di
> `AndroidManifest.xml`:
>
> ```xml
> <queries>
>   <intent>
>     <action android:name="android.intent.action.VIEW" />
>     <data android:scheme="https" />
>   </intent>
> </queries>
> ```

Pesan dibuat otomatis berisi konteks transaksi, sesuai PRD §5.6:

```dart
String buildOrderMessage(Order order) =>
    'Halo, saya ingin menanyakan pesanan ${order.orderNumber} '
    '(${order.listingTitle}) di Seekitar.';
```

---

## 16. UI/UX GUIDELINES IMPLEMENTASI

- **Warna:** Sesuai Brand Guideline (Hijau Lokal `#168A4A`, Teks Utama `#1F2933`, dll).
- **Spacing:** Grid 8dp, padding standar 16dp.
- **Card:** Radius 12dp, shadow ringan.
- **Error:** Widget error dengan tombol retry.
- **Empty state:** Ilustrasi kosong dengan teks “Belum ada data”.

### 16.1 Font Plus Jakarta Sans

Terapkan sekali di `ThemeData`, jangan per-widget:

```dart
class AppTheme {
  static ThemeData get light {
    final base = ThemeData.light(useMaterial3: true);

    return base.copyWith(
      colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF168A4A)),
      textTheme: GoogleFonts.plusJakartaSansTextTheme(base.textTheme).apply(
        bodyColor: const Color(0xFF1F2933),
        displayColor: const Color(0xFF1F2933),
      ),
      cardTheme: CardThemeData(
        elevation: 1,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    );
  }
}
```

> ⚠️ Secara bawaan `google_fonts` **mengunduh font saat runtime** — teks akan
> berkedip pada pembukaan pertama, dan gagal total jika pengguna offline. Untuk
> produksi, bundel font-nya sebagai aset:
>
> ```yaml
> flutter:
>   assets:
>     - assets/google_fonts/     # PlusJakartaSans-{Regular,Medium,Bold}.ttf
> ```
>
> Lalu matikan pengambilan runtime di `main()`:
> ```dart
> GoogleFonts.config.allowRuntimeFetching = false;
> ```
> Ini penting untuk pengguna di area sinyal lemah — target utama Seekitar.

### 16.2 Skeleton Shimmer

Skeleton dipakai untuk pemuatan **pertama**; `RefreshIndicator` untuk muat ulang.

```dart
class ListingCardSkeleton extends StatelessWidget {
  const ListingCardSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: Colors.grey.shade300,
      highlightColor: Colors.grey.shade100,
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              Container(width: 80, height: 80, color: Colors.white),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(height: 16, width: double.infinity, color: Colors.white),
                    const SizedBox(height: 8),
                    Container(height: 14, width: 120, color: Colors.white),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

Bentuk skeleton harus **menyerupai konten aslinya**; placeholder generik justru
membuat pergeseran tata letak terasa mengganggu saat data tiba.

```dart
listings.when(
  loading: () => ListView.builder(
    itemCount: 6,
    itemBuilder: (_, __) => const ListingCardSkeleton(),
  ),
  error: (e, _) => AppErrorWidget(
    message: e is AppException ? e.message : 'Terjadi kesalahan',
    onRetry: () => ref.invalidate(listingSearchProvider),
  ),
  data: (items) => items.isEmpty
      ? const EmptyState(title: 'Belum ada hasil')
      : ListView.builder(/* … */),
)
```

---

## 17. TESTING

- **Unit Test:** Usecase, repository (mock `Dio`), model.
- **Widget Test:** Halaman dengan provider overrides.
- **Integration Test:** Alur login → jelajahi → pasang kebutuhan.

---

## 18. DEPLOYMENT & CI/CD

- **Build Android:** `flutter build apk --release` atau app bundle.
- **Build iOS:** `flutter build ipa --release` (via Xcode).
- **GitHub Actions:**
  - `flutter analyze` → `flutter test` → `flutter build apk` (artifact).
  - Fastlane untuk distribusi ke Play Store/App Store.

---

## 19. LAMPIRAN: CONTOH KODE PENTING

### 19.1 Dio Client lengkap

(lihat kode di atas)

### 19.2 Nearby Stores Provider dengan Infinite Scroll

```dart
@riverpod
class NearbyStoresPaginated extends _$NearbyStoresPaginated {
  static const _perPage = 15;

  int _page = 1;
  bool _hasMore = true;
  bool _isLoadingMore = false;

  @override
  FutureOr<List<Store>> build(double lat, double lng, {double radius = 10}) async {
    _page = 1;
    _hasMore = true;
    _isLoadingMore = false;

    final stores = await ref
        .read(storeRepositoryProvider)
        .getNearbyStores(lat, lng, radius, page: _page);

    _hasMore = stores.length >= _perPage;
    return stores;
  }

  Future<void> loadMore() async {
    // TIGA guard, semuanya perlu:
    // 1. sudah habis  2. sedang memuat  3. muatan awal belum selesai
    if (!_hasMore || _isLoadingMore || !state.hasValue) return;

    _isLoadingMore = true;
    final nextPage = _page + 1;

    try {
      final stores = await ref
          .read(storeRepositoryProvider)
          .getNearbyStores(lat, lng, radius, page: nextPage);

      // Provider bisa saja sudah dibuang saat request berjalan.
      if (!ref.mounted) return;

      _page = nextPage;                       // hanya naik jika BERHASIL
      _hasMore = stores.length >= _perPage;
      state = AsyncData([...?state.value, ...stores]);
    } on AppException catch (e) {
      // Jangan ganti state jadi error — daftar yang sudah tampil akan hilang.
      // Cukup beri tahu lewat snackbar, biar pengguna bisa mencoba lagi.
      ref.read(appMessengerProvider).showError(e.message);
    } finally {
      _isLoadingMore = false;
    }
  }
}
```

> ⚠️ Tiga hal yang mudah salah di sini:
>
> 1. **`_page++` sebelum request.** Kalau request gagal, halaman terlanjur naik
>    dan satu halaman data hilang selamanya saat pengguna mencoba lagi.
> 2. **Tanpa guard `_isLoadingMore`.** Scroll cepat memicu `loadMore()`
>    berkali-kali, dan item yang sama masuk berulang ke daftar.
> 3. **Melempar error ke `state`.** Kegagalan memuat halaman kedua tidak boleh
>    menghapus halaman pertama yang sudah tampil.

Pemicunya di UI — jangan pakai `ScrollController.addListener` biasa, karena
akan menembak berkali-kali per frame:

```dart
NotificationListener<ScrollEndNotification>(
  onNotification: (n) {
    final m = n.metrics;
    if (m.pixels >= m.maxScrollExtent - 300) {
      ref.read(nearbyStoresPaginatedProvider(lat, lng).notifier).loadMore();
    }
    return false;
  },
  child: ListView.builder(/* … */),
)
```

### 19.3 Secure Storage Provider

`flutter_secure_storage` bisa **gagal** pada perangkat nyata: keystore Android
rusak setelah pembaruan OS, atau Keychain iOS terkunci saat perangkat baru
di-boot dan belum dibuka. Tanpa penanganan, aplikasi crash saat dibuka.

```dart
@Riverpod(keepAlive: true)
FlutterSecureStorage secureStorage(Ref ref) => const FlutterSecureStorage(
      aOptions: AndroidOptions(encryptedSharedPreferences: true),
      iOptions: IOSOptions(accessibility: KeychainAccessibility.first_unlock),
    );

@Riverpod(keepAlive: true)
StorageService storageService(Ref ref) =>
    StorageService(ref.watch(secureStorageProvider), ref.read(analyticsServiceProvider));

class StorageService {
  const StorageService(this._storage, this._analytics);
  final FlutterSecureStorage _storage;
  final AnalyticsService _analytics;

  static const _tokenKey = 'auth_token';

  Future<String?> readToken() async {
    try {
      return await _storage.read(key: _tokenKey);
    } on PlatformException catch (e, s) {
      // Keystore rusak: perlakukan sebagai "belum login" dan bersihkan,
      // daripada membiarkan aplikasi gagal dibuka selamanya.
      _analytics.recordError(e, s, fatal: false);
      await _safeDeleteAll();
      return null;
    }
  }

  Future<void> saveToken(String token) async {
    try {
      await _storage.write(key: _tokenKey, value: token);
    } on PlatformException catch (e, s) {
      _analytics.recordError(e, s, fatal: false);
      // Gagal menyimpan token = pengguna harus login lagi nanti.
      // Ini harus terlihat, jangan ditelan diam-diam.
      throw const StorageException('Gagal menyimpan sesi. Coba lagi.');
    }
  }

  Future<void> clearToken() => _safeDeleteAll();

  Future<void> _safeDeleteAll() async {
    try {
      await _storage.deleteAll();
    } on PlatformException {
      // Sudah tidak terbaca; tidak ada lagi yang bisa dilakukan.
    }
  }
}
```

> `KeychainAccessibility.first_unlock` dipilih agar token tetap terbaca saat
> aplikasi dibangunkan notifikasi di latar belakang. Nilai bawaan
> (`unlocked`) membuat pembacaan gagal jika layar sedang terkunci.

---

## 20. KONFIGURASI ENVIRONMENT (DEV / STAGING / PROD)

Memakai **`--dart-define-from-file`**, bukan `flutter_dotenv`. Alasannya:
berkas `.env` ikut terbundel sebagai aset dan **bisa dibaca siapa pun** yang
membongkar APK, sementara `--dart-define` di-inline saat kompilasi. Nilainya
juga tersedia sebagai `const`, sehingga bisa dipakai di konteks konstan.

```json
// config/dev.json  (JANGAN di-commit jika berisi kunci asli)
{
  "API_BASE_URL": "http://10.0.2.2:8000/api/v1",
  "ENVIRONMENT": "dev",
  "ENABLE_LOGGING": true
}
```

```json
// config/prod.json
{
  "API_BASE_URL": "https://api.seekitar.id/api/v1",
  "ENVIRONMENT": "prod",
  "ENABLE_LOGGING": false
}
```

```dart
// core/config/env.dart
abstract final class Env {
  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/v1',
  );

  static const environment = String.fromEnvironment('ENVIRONMENT', defaultValue: 'dev');
  static const enableLogging = bool.fromEnvironment('ENABLE_LOGGING', defaultValue: true);

  static bool get isProduction => environment == 'prod';
}
```

```bash
flutter run  --dart-define-from-file=config/dev.json
flutter build apk --release --dart-define-from-file=config/prod.json
```

| Environment | Base URL | Catatan |
| :-- | :-- | :-- |
| Dev (emulator Android) | `http://10.0.2.2:8000/api/v1` | `localhost` tidak terjangkau dari emulator |
| Dev (perangkat fisik) | `http://<IP-LAN>:8000/api/v1` | Server jalan dengan `--host=0.0.0.0` |
| Staging | `https://staging-api.seekitar.id/api/v1` | UAT & closed beta |
| Produksi | `https://api.seekitar.id/api/v1` | |

Selaras dengan tabel di `API_DOCUMENTATION.md` §Base URL.

> ⚠️ `String.fromEnvironment` **harus** `const`. Menulisnya sebagai variabel
> biasa membuat nilainya selalu kosong tanpa peringatan apa pun saat kompilasi.

---

## 21. ERROR HANDLING GLOBAL

Tiga jalur galat yang harus ditangkap; melewatkan salah satunya berarti crash
yang tidak pernah terlihat di laporan.

```dart
Future<void> main() async {
  // runZonedGuarded menangkap galat asinkron yang lolos dari Flutter.
  await runZonedGuarded(() async {
    WidgetsFlutterBinding.ensureInitialized();
    await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);

    // 1. Galat di dalam framework Flutter (build, layout, paint).
    FlutterError.onError = (details) {
      FlutterError.presentError(details);          // tetap cetak di konsol
      FirebaseCrashlytics.instance.recordFlutterFatalError(details);
    };

    // 2. Galat pada isolate/platform di luar Flutter.
    PlatformDispatcher.instance.onError = (error, stack) {
      FirebaseCrashlytics.instance.recordError(error, stack, fatal: true);
      return true;
    };

    // 3. Layar merah diganti tampilan yang layak dilihat pengguna.
    ErrorWidget.builder = (details) => Material(
          child: AppErrorWidget(
            message: Env.isProduction
                ? 'Terjadi kesalahan. Silakan coba lagi.'
                : details.exceptionAsString(),      // detail hanya saat debug
          ),
        );

    runApp(const ProviderScope(child: SeekitarApp()));
  }, (error, stack) {
    FirebaseCrashlytics.instance.recordError(error, stack, fatal: true);
  });
}
```

**Galat di dalam provider** ditangkap lewat observer, sehingga tidak perlu
`try-catch` di setiap notifier:

```dart
class ErrorLoggingObserver extends ProviderObserver {
  @override
  void providerDidFail(ProviderObserverContext context, Object error, StackTrace st) {
    // Galat yang sudah dipetakan (mis. 401, 422) adalah alur normal,
    // bukan bug — jangan cemari laporan crash dengannya.
    if (error is AppException && error is! ServerException) return;

    FirebaseCrashlytics.instance.recordError(
      error, st,
      reason: 'Provider ${context.provider.name ?? context.provider.runtimeType} gagal',
    );
  }
}

// ProviderScope(observers: [ErrorLoggingObserver()], …)
```

> `providerDidFail` adalah API Riverpod 3; di Riverpod 2 namanya
> `didAddProvider`/`providerDidFail` dengan tanda tangan berbeda.

---

## 22. LOGGING & ANALYTICS

```dart
@Riverpod(keepAlive: true)
AnalyticsService analyticsService(Ref ref) => AnalyticsService(
      FirebaseAnalytics.instance,
      FirebaseCrashlytics.instance,
    );

class AnalyticsService {
  const AnalyticsService(this._analytics, this._crashlytics);
  final FirebaseAnalytics _analytics;
  final FirebaseCrashlytics _crashlytics;

  Future<void> logEvent(String name, [Map<String, Object>? params]) {
    if (!Env.isProduction) debugPrint('[analytics] $name $params');
    return _analytics.logEvent(name: name, parameters: params);
  }

  /// Jangan pernah mengirim nomor telepon / nama sebagai user property —
  /// itu data pribadi (UU PDP). Cukup ID dan atribut non-identifikasi.
  Future<void> setUser(User? user) async {
    await _analytics.setUserId(id: user?.id);
    await _crashlytics.setUserIdentifier(user?.id ?? '');
    if (user != null) {
      await _analytics.setUserProperty(
        name: 'verification_level',
        value: user.verificationLevel.value.toString(),
      );
    }
  }

  Future<void> recordError(Object e, StackTrace s, {bool fatal = false}) =>
      _crashlytics.recordError(e, s, fatal: fatal);
}
```

**Event yang dilacak** — dipilih untuk mengukur KPI di PRD §13, bukan sekadar
mengumpulkan data:

| Event | Parameter | KPI yang diukur |
| :-- | :-- | :-- |
| `otp_requested` / `otp_verified` | – | Corong registrasi |
| `store_created` | `store_type` | Conversion rate penjual (≥25%) |
| `listing_viewed` | `listing_id`, `distance_km` | Keterlibatan katalog |
| `request_created` | `category_id`, `radius_km` | Pemakaian papan kebutuhan |
| `offer_submitted` | `request_id` | Responsivitas penyedia |
| `offer_accepted` | `price`, `offer_count` | Tingkat konversi lelang |
| `whatsapp_click` | `success` | Kanal komunikasi (§15) |
| `order_completed` | `order_type`, `payment_method` | Transaksi selesai |

Pelacakan layar otomatis lewat observer GoRouter:

```dart
GoRouter(
  observers: [FirebaseAnalyticsObserver(analytics: FirebaseAnalytics.instance)],
  // …
)
```

---

## 23. DEEP LINK

Dua sumber deep link, keduanya bermuara ke `GoRouter` yang sama:

1. **Notifikasi FCM** — sudah ditangani `NotificationService._navigate()` (§14).
2. **Tautan web** (`https://seekitar.id/listing/abc`) — dibagikan lewat
   WhatsApp, dan harus membuka aplikasi bila terpasang.

```dart
@Riverpod(keepAlive: true)
DeepLinkService deepLinkService(Ref ref) => DeepLinkService(ref)..initialize();

class DeepLinkService {
  DeepLinkService(this._ref);
  final Ref _ref;
  final _appLinks = AppLinks();
  StreamSubscription<Uri>? _sub;

  Future<void> initialize() async {
    // Tautan yang membuka aplikasi dari keadaan mati.
    final initial = await _appLinks.getInitialLink();
    if (initial != null) _handle(initial);

    _sub = _appLinks.uriLinkStream.listen(_handle);
    _ref.onDispose(() => _sub?.cancel());
  }

  void _handle(Uri uri) {
    // Path web sengaja dibuat sama dengan path in-app, sehingga tidak perlu
    // tabel pemetaan terpisah yang gampang basi.
    final path = uri.path;
    if (path.isEmpty || path == '/') return;

    _ref.read(analyticsServiceProvider).logEvent('deep_link_opened', {'path': path});
    _ref.read(goRouterProvider).push(path);
  }
}
```

Konfigurasi platform:

```xml
<!-- android/app/src/main/AndroidManifest.xml -->
<intent-filter android:autoVerify="true">
  <action android:name="android.intent.action.VIEW" />
  <category android:name="android.intent.category.DEFAULT" />
  <category android:name="android.intent.category.BROWSABLE" />
  <data android:scheme="https" android:host="seekitar.id" />
</intent-filter>
```

> `autoVerify="true"` mengharuskan berkas `.well-known/assetlinks.json`
> tersedia di `https://seekitar.id`. Tanpa itu Android tetap menampilkan dialog
> pemilih aplikasi, bukan langsung membuka Seekitar. Padanan di iOS adalah
> `apple-app-site-association` untuk Universal Links.

Karena rute web dan aplikasi memakai path yang sama, halaman SEO Laravel
(`PRD.md` §7.2) otomatis menjadi tautan yang bisa membuka aplikasi.
