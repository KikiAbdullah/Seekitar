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
| l10n                 | `flutter_localizations`                 | SDK      | Multi bahasa (opsional)                |

**Dev dependencies:** `flutter_test`, `mocktail`, `riverpod_lint`, `custom_lint`.

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
  static const splash        = '/splash';
  static const login         = '/login';
  static const otp           = '/otp';
  static const explore       = '/';
  static const requests      = '/requests';
  static const createRequest = '/create-request';
  static const orders        = '/orders';
  static const profile       = '/profile';
  static const myStore       = '/store';

  // Rute berparameter: sediakan pola sekaligus pembangunnya.
  static const listingDetail = '/listing/:id';
  static String listingDetailOf(String id) => '/listing/$id';

  static const requestDetail = '/requests/:id';
  static String requestDetailOf(String id) => '/requests/$id';
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

### 4.1 Providers Global (di `app.dart` atau `main.dart`)

```dart
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();

  final dio = Dio(BaseOptions(baseUrl: ApiConstants.baseUrl));
  final secureStorage = FlutterSecureStorage();

  runApp(
    ProviderScope(
      overrides: [
        dioProvider.overrideWithValue(dio),
        secureStorageProvider.overrideWithValue(secureStorage),
      ],
      child: SeekitarApp(),
    ),
  );
}
```

### 4.2 Provider Typikal (Auth)

```dart
@riverpod
class AuthNotifier extends _$AuthNotifier {
  @override
  FutureOr<User?> build() async {
    // cek token di secure storage, jika ada ambil profile
    final token = await ref.read(secureStorageProvider).read('token');
    if (token == null) return null;
    return ref.read(authRepositoryProvider).getProfile();
  }

  Future<void> login(String phone, String otp) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() async {
      final user = await ref.read(authRepositoryProvider).verifyOtp(phone, otp);
      ref.read(secureStorageProvider).write('token', user.token);
      return user;
    });
  }

  Future<void> logout() async {
    await ref.read(secureStorageProvider).delete('token');
    state = const AsyncData(null);
  }
}
```

### 4.3 Provider untuk List (Nearby Stores)

```dart
@riverpod
Future<List<Store>> nearbyStores(Ref ref, {
  required double lat, required double lng, double radius = 10,
}) async {
  final repository = ref.read(storeRepositoryProvider);
  return repository.getNearbyStores(lat, lng, radius);
}
```

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

```dart
@riverpod
Dio dio(Ref ref) {
  final dio = Dio(BaseOptions(
    baseUrl: ApiConstants.baseUrl,
    connectTimeout: const Duration(seconds: 10),
    receiveTimeout: const Duration(seconds: 10),
  ));
  dio.interceptors.add(AuthInterceptor(ref));
  dio.interceptors.add(LogInterceptor(requestBody: true, responseBody: true));
  return dio;
}

class AuthInterceptor extends Interceptor {
  final Ref ref;
  AuthInterceptor(this.ref);

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final token = await ref.read(secureStorageProvider).read('token');
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }
}
```

### 5.2 Model (User)

Gunakan `json_serializable` + `freezed`:

```dart
@freezed
class UserModel with _$UserModel {
  const factory UserModel({
    required String id,
    required String phone,
    required String name,
    String? avatarUrl,
    @JsonKey(fromJson: _pointFromJson, toJson: _pointToJson) GeoPoint? location,
    required int verificationLevel,
  }) = _UserModel;

  factory UserModel.fromJson(Map<String, dynamic> json) => _$UserModelFromJson(json);
}
```

### 5.3 Repository Implementation

```dart
class AuthRepositoryImpl implements AuthRepository {
  final Dio _dio;
  AuthRepositoryImpl(this._dio);

  @override
  Future<User> verifyOtp(String phone, String otp) async {
    final response = await _dio.post('/auth/verify-otp', data: {'phone': phone, 'otp': otp});
    return UserModel.fromJson(response.data['data']['user']);
  }
}
```

---

## 6. LAYER DOMAIN: USECASES & ENTITIES

### 6.1 Entities (pure Dart)

```dart
@freezed
class User with _$User {
  const factory User({
    required String id,
    required String phone,
    required String name,
    String? avatarUrl,
    GeoPoint? location,
    required int verificationLevel,
  }) = _User;
}
```

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

```dart
class LoginPage extends ConsumerWidget {
  final phoneCtrl = TextEditingController();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authNotifierProvider);
    return Scaffold(
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text('Masuk ke Seekitar', style: Theme.of(context).textTheme.headlineMedium),
            SizedBox(height: 24),
            TextField(controller: phoneCtrl, keyboardType: TextInputType.phone),
            SizedBox(height: 16),
            CustomButton(
              label: 'Minta OTP',
              isLoading: authState.isLoading,
              onPressed: () {
                ref.read(requestOtpProvider(phoneCtrl.text));
                // navigasi ke halaman OTP
              },
            ),
          ],
        ),
      ),
    );
  }
}
```

### 7.2 Halaman Detail Listing

Menampilkan slider foto (dengan `PageView` atau `cached_network_image`), deskripsi, profil toko mini, tombol “Pesan Sekarang” & “Tanya Penjual”.

---

## 8. NAVIGASI & ROUTING (GOROUTER)

Path diambil dari `Routes` (lihat §3), bukan ditulis manual:

```dart
import 'package:seekitar_mobile/core/constants/route_constants.dart';

final goRouter = GoRouter(
  initialLocation: Routes.explore,
  redirect: (context, state) {
    final user = ref.read(authNotifierProvider).value;
    final loggedIn = user != null;
    if (!loggedIn && state.matchedLocation != Routes.login) return Routes.login;
    if (loggedIn && state.matchedLocation == Routes.login) return Routes.explore;
    return null;
  },
  routes: [
    GoRoute(path: Routes.login, builder: (_, __) => LoginPage()),
    GoRoute(path: Routes.otp, builder: (_, state) => OtpPage(phone: state.extra as String)),
    ShellRoute(
      builder: (_, __, child) => MainShell(child: child),
      routes: [
        GoRoute(path: Routes.explore, builder: (_, __) => ExplorePage()),
        GoRoute(path: Routes.requests, builder: (_, __) => RequestsPage()),
        GoRoute(path: Routes.orders, builder: (_, __) => OrdersPage()),
        GoRoute(path: Routes.profile, builder: (_, __) => ProfilePage()),
        GoRoute(path: Routes.createRequest, builder: (_, __) => CreateRequestPage()),
        GoRoute(
          path: Routes.listingDetail,
          builder: (_, state) => ListingDetailPage(id: state.pathParameters['id']!),
        ),
      ],
    ),
  ],
);
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

**MainShell:** Scaffold dengan `BottomNavigationBar` (4 item: Jelajahi, Kebutuhan, Transaksi, Profil). Body adalah `child` dari ShellRoute.  
Gunakan `go_router` state untuk highlight tab aktif.

---

## 11. FITUR MARKETPLACE (JELAJAHI)

**ExplorePage:**

- `AppBar` dengan search field.
- Filter chips horizontal (kategori).
- `RefreshIndicator` + list `ListingCard`.
- FAB: “Pasang Kebutuhan”.
- Saat scroll ke bawah, panggil `nearbyStoresProvider` dengan pagination (Riverpod `family` + `keepAlive` + `infinite_scroll` pakai paging).

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

**RequestsPage (Tab Kebutuhan):**

- List permintaan (open) di sekitar (mirip listing).
- Dua tab: “Kebutuhan Terbaru” (untuk penyedia) dan “Permintaan Saya” (untuk pembeli).
- Tiap item onTap → navigasi `RequestDetailPage`.

**RequestDetailPage (pembeli):**

- Detail permintaan + daftar offers (dengan `CompareOffersPage`).

**CompareOffersPage:**

- List offer, sortable, tombol “Terima” di tiap kartu.

---

## 13. FITUR PENAWARAN & TRANSAKSI

- **OfferCard:** Menampilkan nama toko, harga, jarak, estimasi, rating.
- **Accept Offer:** Panggil `PATCH /offers/{id}/accept`, lalu muncul notifikasi sukses.
- **OrdersPage:** Riwayat pesanan, tab “Sebagai Pembeli” / “Sebagai Penjual”.
- **OrderDetailPage:** Timeline status (Stepper widget), aksi (konfirmasi, upload bukti), kontak penjual.

---

## 14. NOTIFIKASI PUSH (FCM)

**Inisialisasi:**

- Request permission (iOS).
- Dapatkan token, kirim ke `POST /auth/fcm-token`.
- Listen: `FirebaseMessaging.onMessage`, `onMessageOpenedApp`.

**Handling payload:**

- `data` berisi `screen`, `entity_id`. Navigasi menggunakan `GoRouter`.

---

## 15. INTEGRASI WHATSAPP

Gunakan `url_launcher`:

```dart
void openWhatsApp(String phone, String message) {
  final url = Uri.parse('https://wa.me/$phone?text=${Uri.encodeComponent(message)}');
  launchUrl(url, mode: LaunchMode.externalApplication);
}
```

Event `whatsapp_click` dikirim ke backend (fire analytics atau custom endpoint).

---

## 16. UI/UX GUIDELINES IMPLEMENTASI

- **Warna:** Sesuai Brand Guideline (Hijau Lokal `#168A4A`, Teks Utama `#1F2933`, dll).
- **Font:** Plus Jakarta Sans (via `google_fonts` package).
- **Spacing:** Grid 8dp, padding standar 16dp.
- **Card:** Radius 12dp, shadow ringan.
- **Loading:** Skeleton shimmer (pakai `shimmer` package).
- **Error:** Widget error dengan tombol retry.
- **Empty state:** Ilustrasi kosong dengan teks “Belum ada data”.

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
  int _page = 1;
  bool _hasMore = true;

  @override
  FutureOr<List<Store>> build(double lat, double lng, {double radius = 10}) async {
    _page = 1;
    _hasMore = true;
    final stores = await ref.read(storeRepositoryProvider).getNearbyStores(lat, lng, radius, page: _page);
    if (stores.length < 15) _hasMore = false;
    return stores;
  }

  Future<void> loadMore() async {
    if (!_hasMore) return;
    _page++;
    final stores = await ref.read(storeRepositoryProvider).getNearbyStores(lat, lng, radius, page: _page);
    state = AsyncData([...state.value ?? [], ...stores]);
    if (stores.length < 15) _hasMore = false;
  }
}
```

### 19.3 Secure Storage Provider

```dart
@riverpod
FlutterSecureStorage secureStorage(Ref ref) => FlutterSecureStorage();
```
