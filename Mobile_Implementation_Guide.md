# 📱 Seekitar – Mobile Implementation Guide

**Versi:** 2.3 (Production‑Ready)  
**Tanggal:** 29 Juli 2026  
**Target:** Flutter 3.44+ (Dart 3.12+) · Android & iOS  
**Arsitektur:** pragmatis per peran — `screens/`, `services/`, `models/`, `providers/`, `routing/`, `widgets/`  
**State Management:** provider (`ChangeNotifier` + `ChangeNotifierProvider`)  
**HTTP Client:** Dio 5.x (interceptor JWT + refresh + retry)  
**Database Lokal:** tidak ada (tanpa cache lokal; `shared_preferences` untuk preferensi ringan)  
**Maps & Geolokasi:** Geolocator + Geocoding (tanpa `google_maps_flutter`)  
**Push Notification:** Firebase Cloud Messaging (FCM) + `flutter_local_notifications`  
**WhatsApp Redirection:** url_launcher  
**CI/CD:** GitHub Actions (build APK/IPA)

> 📌 Versi lengkap & matriks kompatibilitas paket ada di [`TECH_STACK.md`](TECH_STACK.md) (sumber kebenaran tunggal).

### Backend yang Dikonsumsi Aplikasi Ini

Aplikasi mobile tidak berdiri sendiri. Konteks singkat arsitektur server yang
memengaruhi cara app berperilaku:

| Komponen backend       | Versi         | Relevansi untuk mobile                                                                 |
| :--------------------- | :------------ | :-------------------------------------------------------------------------------------- |
| Laravel + JWT          | 13 · JWT (tymon 2) | Sumber REST API; Bearer JWT disimpan di `flutter_secure_storage` (kunci `jwt_token`); refresh via `POST /auth/refresh` |
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
4. [State Management & Dependency Injection (provider)](#4-state-management--dependency-injection-provider)
5. [Layer Data: Dio, API Client, Models](#5-layer-data-dio-api-client-models)
6. [Layer Layanan & Sesi: Auth Service](#6-layer-layanan--sesi-auth-service)
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
    - 18.1 Penandatanganan Android (Keystore)
    - 18.2 Penandatanganan iOS
    - 18.3 GitHub Actions
19. [Lampiran: Contoh Kode Penting](#19-lampiran-contoh-kode-penting)
20. [Konfigurasi Environment (dev/staging/prod)](#20-konfigurasi-environment-dev--staging--prod)
21. [Error Handling Global](#21-error-handling-global)
22. [Logging & Analytics](#22-logging--analytics)
23. [Deep Link](#23-deep-link)

---

## 1. ARSITEKTUR & PRINSIP

Aplikasi Flutter Seekitar memakai struktur **pragmatis per peran** (bukan
Clean Architecture berlapis — lihat §3), dengan pemisahan yang jelas antara
tanggung jawab:

- **`screens/`** — halaman (UI), termasuk state lokal halaman (StatefulWidget).
- **`services/`** — akses ke dunia luar: `DioClient` (HTTP + interceptor JWT),
  `ApiClient` (metode API), `AuthService` (OTP & sesi), `FcmService` (push).
- **`models/`** — objek data polos dengan `fromJson` manual (tanpa codegen).
- **`providers/`** — state global (`AppState extends ChangeNotifier`).
- **`routing/`** — GoRouter (35 route + `StatefulShellRoute` 5 tab).
- **`widgets/`** — komponen bersama (banner offline, base screen).
- **`core/`** — konstanta, tema, logger.

**Prinsip:**

- **Satu sumber state global** – `AppState` (ChangeNotifier) memegang sesi
  pengguna & token; layar membaca lewat `context.watch`/`context.read`.
- **Layar stateless bila mungkin** – Data diambil `ApiClient`, state lokal
  cukup dengan `setState`; provider global hanya untuk hal yang dipakai
  lintas layar (sesi, jumlah notifikasi).
- **Model manual** – `fromJson` ditulis tangan: tanpa `build_runner`, build
  lebih cepat dan tidak ada berkas `.g.dart` yang bisa basi.
- **Reusability** – Widget kecil, composable (`widgets/`).

---

## 2. LIBRARY UTAMA (pub.dev)

Daftar di bawah ini adalah isi **`seekitar_mobile/pubspec.yaml`** yang
sebenarnya — sumber kebenaran. Tidak ada `flutter_riverpod`, `freezed`,
`json_serializable`, `build_runner`, maupun `google_maps_flutter`; model JSON
ditulis manual.

| Kategori             | Library                                 | Versi    | Keterangan                             |
| -------------------- | --------------------------------------- | -------- | -------------------------------------- |
| State Management     | `provider`                              | ^6.1.0   | `ChangeNotifier` + `ChangeNotifierProvider` |
| HTTP Client          | `dio`                                   | ^5.7.0   | REST API calls; interceptor JWT/refresh/retry |
| Routing              | `go_router`                             | ^14.8.0  | Navigasi deklaratif + `StatefulShellRoute` |
| Geolokasi            | `geolocator`                            | ^13.0.0  | Mendapatkan posisi GPS                 |
| Geocoding            | `geocoding`                             | ^3.0.0   | Reverse geocoding (koordinat → alamat) |
| Push Notification    | `firebase_messaging`                    | ^15.1.0  | FCM untuk notifikasi                   |
| Firebase Core        | `firebase_core`                         | ^3.6.0   | Inisialisasi Firebase                  |
| Notifikasi Lokal     | `flutter_local_notifications`           | ^18.0.0  | Notif saat aplikasi di latar depan     |
| WhatsApp             | `url_launcher`                          | ^6.3.0   | Membuka WhatsApp / nomor telepon       |
| Image Picker         | `image_picker`                          | ^1.1.0   | Ambil foto produk, KTP                 |
| Kompresi Gambar      | `flutter_image_compress`                | ^2.5.1   | Kompresi sebelum unggah                |
| File Picker          | `file_picker`                           | ^8.1.0   | Pilih berkas umum                      |
| Cached Network Image | `cached_network_image`                  | ^3.4.0   | Cache gambar                           |
| Local Storage        | `shared_preferences`                    | ^2.3.0   | Preferensi ringan                      |
| Secure Storage       | `flutter_secure_storage`                | ^10.3.1  | Token JWT disimpan aman (`jwt_token`)  |
| Konektivitas         | `connectivity_plus`                     | ^6.1.0   | Banner offline                         |
| Path                 | `path_provider`                         | ^2.1.0   | Direktori berkas                       |
| Skala UI             | `flutter_screenutil`                    | ^5.9.0   | Design size 390×844                    |
| Rating               | `flutter_rating_bar`                    | ^4.0.1   | Tampilan bintang rating                |
| Indikator Galeri     | `smooth_page_indicator`                 | ^1.2.0   | Dots galeri gambar listing             |
| SVG                  | `flutter_svg`                           | ^2.0.0   | Logo & aset vektor                     |
| Skeleton Loading     | `shimmer`                               | ^3.0.0   | Placeholder saat memuat data           |
| Berbagi              | `share_plus`                            | ^13.3.0  | Bagikan tautan listing (SharePlus.instance.share) |
| Format               | `intl`                                  | ^0.19.0  | Format tanggal/angka Rupiah            |
| Izin Platform        | `permission_handler`                    | ^11.3.0  | Izin lokasi, notifikasi                |
| Log                  | `logger`                                | ^2.5.0   | Log aplikasi                           |
| Info Paket           | `package_info_plus`                     | ^10.2.1  | Versi aplikasi (halaman Tentang)       |

**Dev dependencies:** `flutter_test`, `flutter_lints`, `flutter_native_splash`.

> Konfigurasi environment memakai `--dart-define=API_BASE_URL=...` (dibaca
> `String.fromEnvironment` di `lib/core/constants.dart`) — tidak ada
> `flutter_dotenv` dan tidak ada `config/dev.json`.

### Catatan Kompatibilitas

**GoRouter ↔ state management tidak saling bergantung.** `go_router` hanya
bergantung pada `collection`, `logging`, dan `meta` — tidak ada kaitan dengan
`provider` maupun Riverpod. Keduanya independen; yang mengikat adalah versi
Dart SDK (proyek ini Dart 3.12+).

**Firebase harus sekeluarga.** `firebase_core` dan `firebase_messaging` dirilis
berpasangan; menaikkan salah satu saja sering memicu konflik di build Android.

**Sengaja tidak dipakai:**

- `freezed` / `json_serializable` / `build_runner` — model memakai `fromJson`
  manual; tanpa codegen, build lebih cepat dan tidak ada berkas `.g.dart`.
- `google_maps_flutter` — pemilih lokasi cukup dengan koordinat GPS +
  reverse geocoding; peta interaktif ada di web admin (Leaflet), bukan di
  aplikasi mobile.
- `pull_to_refresh_flutter3` — tidak lagi dirawat. Pakai `RefreshIndicator`
  bawaan Flutter yang sudah memadai.

---

## 3. STRUKTUR PROYEK

```
lib/
├── main.dart                   # Entry point: init AppState + FCM, runApp
├── core/
│   ├── constants.dart              # AppConstants (baseUrl via --dart-define, warna, format) + UiStrings
│   ├── theme.dart                  # AppTheme.light / AppTheme.dark (hijau #168A4A)
│   └── logger.dart                 # appLogger (paket logger)
├── models/                     # Objek data polos — fromJson MANUAL (tanpa codegen)
│   ├── user.dart                   # User (status, verified_at, latitude/longitude)
│   ├── store.dart
│   ├── listing.dart
│   ├── customer_request.dart
│   ├── order.dart
│   ├── review.dart
│   ├── notification.dart
│   ├── conversation.dart
│   ├── address.dart
│   ├── category.dart
│   ├── dashboard.dart              # ringkasan dasbor toko
│   └── wallet.dart
├── providers/
│   └── app_state.dart          # AppState extends ChangeNotifier (sesi, jumlah notif)
├── routing/
│   └── app_router.dart         # GoRouter: 35 route + StatefulShellRoute (5 tab) + HomeShell
├── services/                   # Akses dunia luar (HTTP, auth, FCM)
│   ├── dio_client.dart             # Dio singleton + interceptor Bearer JWT, refresh 401, retry
│   ├── api_client.dart             # Semua metode API (/auth, /stores, /listings, /orders, …)
│   ├── api_compat.dart             # ApiProvider — pembungkus kompatibilitas pemanggilan
│   ├── auth_service.dart           # OTP flow, simpan/baca token JWT, profil
│   └── fcm_service.dart            # Firebase Messaging: token, izin, handler pesan
├── screens/                    # 35 halaman (satu berkas per halaman)
│   ├── splash_screen.dart          # fade+scale 1200ms → /home atau /onboarding
│   ├── onboarding_screen.dart      # 3 slide PageView
│   ├── login_screen.dart           # OTP: input nomor WA + 6 digit kode, countdown 60s
│   ├── home_screen.dart            # Beranda: hero, tren, terdekat, kebutuhan
│   ├── search_screen.dart          # Pencarian + filter chips kategori
│   ├── requests_screen.dart        # Tab Kebutuhan (Daftar/Menawarkan)
│   ├── orders_screen.dart          # Tab Pesanan
│   ├── profile_screen.dart         # Tab Profil
│   ├── listing_detail_screen.dart
│   ├── create_listing_screen.dart
│   ├── request_detail_screen.dart
│   ├── order_detail_screen.dart
│   ├── checkout_screen.dart        # Ringkasan + kupon + metode bayar/antar
│   ├── coupon_screen.dart
│   ├── wallet_screen.dart
│   ├── address_screen.dart
│   ├── conversations_screen.dart   # Daftar chat
│   ├── store_screen.dart           # Toko saya + dasbor + buka toko
│   ├── stores_nearby_screen.dart
│   ├── category_screen.dart        # Jelajah kategori
│   ├── favorites_screen.dart
│   ├── notifications_screen.dart
│   ├── notif_prefs_screen.dart
│   ├── blocked_screen.dart
│   ├── verification_screen.dart    # Verifikasi KTP
│   ├── reviews_screen.dart
│   ├── settings_full_screen.dart
│   ├── legal_screen.dart           # Bantuan & legal
│   ├── about_screen.dart
│   ├── pricing_screen.dart
│   └── status_screen.dart
└── widgets/
    ├── base_screen.dart        # Scaffold + AppBar konsisten
    └── offline_banner.dart     # Banner "offline" dari connectivity_plus
```

**Jumlah asli (per `find lib -type f`):** 1 `main.dart` + 3 `core/` + 12
`models/` + 1 `providers/` + 1 `routing/` + 5 `services/` + 31 `screens/` +
2 `widgets/` = **56 berkas Dart**.

### Di mana widget ditaruh

| Lokasi                  | Tahu domain? | Contoh                              | Aturan                                         |
| :---------------------- | :----------- | :---------------------------------- | :--------------------------------------------- |
| `widgets/`              | ❌ Tidak     | `BaseScreen`, `OfflineBanner`       | Dipakai banyak layar, tanpa tahu model bisnis  |
| `screens/<nama>_screen.dart` | ✅ Ya   | `ListingDetailScreen`, `HomeScreen` | Satu layar = satu berkas; bagian UI-nya ditulis privat di berkas yang sama |

Aturan sederhana: halaman = satu berkas di `screens/`; komponen yang dipakai
lebih dari satu halaman dan tidak tahu domain → `widgets/`; komponen yang
tahu domain cukup didefinisikan di dalam berkas layar yang memakainya.

### `core/constants.dart` — pusat konstanta

Base URL, warna, label, dan formatter dipusatkan di `AppConstants`:

```dart
class AppConstants {
  // Base URL disuntikkan saat build; default untuk pengembangan lokal.
  static const String baseUrl =
      String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.201.162:8000/api/v1');
  static const Duration connectTimeout = Duration(seconds: 15);
  static const Duration receiveTimeout = Duration(seconds: 15);
  static const int maxRetries = 2;
  static const Color primaryColor = Color(0xFF168A4A);
  // ...
}
```

Tidak ada kelas `Routes` terpisah — path GoRouter ditulis langsung di
`routing/app_router.dart` dan dipakai layar lewat `context.go(...)` /
`context.push(...)` dengan string literal yang konsisten di satu tempat.

---

## 4. STATE MANAGEMENT & DEPENDENCY INJECTION (PROVIDER)

State management memakai paket **`provider`** dengan satu `AppState extends
ChangeNotifier` sebagai state global. Tidak ada codegen, tidak ada
`ProviderScope`/`ref` — layar membaca state langsung dari widget tree lewat
`context.watch<T>()` / `context.read<T>()`.

### 4.1 Satu `AppState`, disuntikkan di `main()`

`main.dart` membuat `AppState`, memanggil `init()` (memuat token & profil),
lalu menyuntikkannya ke seluruh pohon widget:

```dart
void main() {
  WidgetsFlutterBinding.ensureInitialized();
  _setupErrorHandling();                 // FlutterError.onError + runZonedGuarded
  final app = AppState();
  app.init();                            // AuthService.init() → GET /auth/me
  FcmService().init().catchError((_) {});// token FCM, tanpa memblokir UI
  runApp(SeekitarApp(app: app));
}

class SeekitarApp extends StatelessWidget {
  final AppState app;
  const SeekitarApp({super.key, required this.app});

  @override
  Widget build(BuildContext context) {
    return ScreenUtilInit(
      designSize: const Size(390, 844),
      minTextAdapt: true,
      builder: (_, __) => ChangeNotifierProvider.value(
        value: app,
        child: MaterialApp.router(
          title: AppConstants.appName,
          theme: AppTheme.light,
          themeMode: ThemeMode.light,   // tanpa dark mode — palet terang saja
          routerConfig: appRouter,
        ),
      ),
    );
  }
}
```

`AppState` adalah satu-satunya tempat state lintas layar (sesi pengguna,
jumlah notifikasi belum dibaca). Data per-halaman diambil langsung oleh layar
melalui `ApiClient` dan disimpan di state lokal `StatefulWidget` — tidak
perlu provider tambahan.

```dart
// lib/providers/app_state.dart
class AppState extends ChangeNotifier {
  final AuthService _auth = AuthService();
  final ApiClient _api = ApiClient();

  User? get user => _auth.user;
  bool get isLoggedIn => _auth.isLoggedIn;
  bool get isLoading => _auth.isLoading;

  int _unreadNotif = 0;
  int get unreadNotif => _unreadNotif;

  Future<void> init() => _auth.init();
  Future<Map<String, dynamic>> requestOtp(String phone) => _auth.requestOtp(phone);
  Future<Map<String, dynamic>> verifyOtp(String phone, String otp) => _auth.verifyOtp(phone, otp);
  Future<void> logout() => _auth.logout();

  Future<void> fetchUnreadCount() async { /* GET /notifications/unread-count */ }
}
```

### 4.2 Membaca & memicu dari layar

```dart
// Membaca state (ikut rebuild saat berubah):
final app = context.watch<AppState>();
final user = app.user;

// Memicu aksi tanpa rebuild:
context.read<AppState>().logout();
```

Karena `AppState` mengimplementasikan `ChangeNotifier`, `context.watch`
men-subscribe otomatis dan `notifyListeners()` di dalam `AppState` memicu
rebuild hanya pada layar yang menontonnya.

### 4.3 State lokal halaman

Untuk data yang hanya dipakai satu layar, cukup state lokal — tidak perlu
global provider:

```dart
class LoginScreen extends StatefulWidget { ... }

class _LoginScreenState extends State<LoginScreen> {
  bool _isSubmitting = false;          // loading tombol
  int _resendCountdown = 60;           // countdown resend OTP

  Future<void> _verify() async {
    setState(() => _isSubmitting = true);
    try {
      final res = await context.read<AppState>().verifyOtp(_phone, _otp);
      if (!mounted) return;
      context.go('/home');
    } catch (e) {
      if (!mounted) return;
      _showError(e);
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }
}
```

> ⚠️ `if (!mounted) return;` setelah setiap `await` — penyebab crash paling
> umum di Flutter. `context` lintas async harus dicek `mounted` dulu.

### 4.4 Kenapa bukan Riverpod?

Versi dokumen sebelumnya mendeskripsikan Riverpod 3 (`@riverpod`, `Ref`,
`AsyncValue`, `ProviderScope`). Implementasi yang ada memakai `provider`
karena: (1) lingkup state globalnya kecil (satu `AppState`), (2) tanpa codegen
build lebih cepat, dan (3) layar membaca state lewat `context.watch` yang
bawaan Flutter. Jika suatu saat kompleksitas naik (cache per-request,
pembatalan otomatis), migrasi bertahap ke Riverpod tetap mungkin — tetapi
pola di dokumen ini adalah sumber kebenaran saat ini.

---

## 5. LAYER DATA: DIO, API CLIENT, MODELS

### 5.1 DioClient — instance tunggal + interceptor

`DioClient` adalah **singleton** yang menyiapkan `Dio` sekali, menyimpan
token JWT di `flutter_secure_storage` (kunci `jwt_token`), dan memasang tiga
interceptor: auth (sisipkan `Authorization: Bearer <token>`), refresh+retry
(401 → `POST /auth/refresh` → ulangi request), dan log (debug).

```dart
class DioClient {
  static final DioClient _instance = DioClient._();
  factory DioClient() => _instance;
  DioClient._() {
    _dio = Dio(BaseOptions(
      baseUrl: AppConstants.baseUrl,
      connectTimeout: AppConstants.connectTimeout,
      receiveTimeout: AppConstants.receiveTimeout,
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
    ));
    _dio.interceptors.addAll([_authInterceptor(), _retryInterceptor(), _logInterceptor()]);
  }

  late final Dio _dio;
  final _storage = const FlutterSecureStorage();
  String? _token;

  Dio get dio => _dio;

  Future<void> setToken(String t) async { _token = t; await _storage.write(key: 'jwt_token', value: t); }
  Future<void> loadToken() async { _token = await _storage.read(key: 'jwt_token'); }
  Future<void> clearToken() async { _token = null; await _storage.delete(key: 'jwt_token'); }
}
```

**Interceptor 401 → refresh → retry** (berbeda dari versi Sanctum lama yang
tidak punya refresh):

```dart
InterceptorsWrapper _authInterceptor() => InterceptorsWrapper(
  onRequest: (options, handler) {
    if (_token != null) options.headers['Authorization'] = 'Bearer $_token';
    handler.next(options);
  },
  onError: (error, handler) async {
    if (error.response?.statusCode == 401 && _token != null) {
      try {
        final refreshDio = Dio(BaseOptions(baseUrl: AppConstants.baseUrl));
        refreshDio.options.headers['Authorization'] = 'Bearer $_token';
        final res = await refreshDio.post('/auth/refresh');
        final newToken = (res.data is Map ? res.data['data'] : null)?['token']?.toString();
        if (newToken == null) throw Exception('Refresh gagal');
        await setToken(newToken);
        error.requestOptions.headers['Authorization'] = 'Bearer $newToken';
        final retry = await _dio.fetch(error.requestOptions);   // ulangi request asli
        handler.resolve(retry);
        return;
      } catch (_) {
        await clearToken();   // refresh gagal → paksa login ulang
      }
    }
    handler.next(error);
  },
);
```

> ⚠️ Kode `423` berarti akun **dibekukan admin** — bedakan dari `401`
> (token kedaluwarsa). Layar login menampilkan pesan pembekuan, bukan sekadar
> "silakan masuk lagi". Bedakan juga `401` dari `NetworkException` (offline):
> jangan hapus token hanya karena perangkat sedang offline.

**Logging hanya di mode debug** — jangan pernah mencetak body berisi OTP/token
di produksi:

```dart
if (kDebugMode) {
  client.interceptors.add(LogInterceptor(requestBody: true, responseBody: true));
}
```

### 5.2 ApiClient — semua endpoint dalam satu kelas

`ApiClient` membungkus `DioClient` dan menyediakan metode per endpoint.
Setiap metode mengurai amplop respons `{ success, data, message }` dan
mengembalikan isi `data` — pemanggil tidak perlu tahu bentuk amplop:

```dart
class ApiClient {
  final DioClient _client = DioClient();
  Dio get _dio => _client.dio;

  dynamic _payload(dynamic body) {
    if (body is Map && body['success'] == true && body.containsKey('data')) {
      return body['data'];
    }
    return body;
  }

  Future<T> _get<T>(String path, {Map<String, dynamic>? query, T Function(dynamic json)? parser}) async {
    final res = await _dio.get(path, queryParameters: query);
    final body = _payload(res.data);
    return parser != null ? parser(body) : body as T;
  }

  Future<Map<String, dynamic>> verifyOtp(String phone, String otp) =>
      _post('/auth/verify-otp', data: {'phone': phone, 'otp': otp});
  // ...77 metode: /home, /listings, /stores, /requests, /offers, /orders,
  // /wallet, /coupons, /conversations, /notifications, /uploads, /reports, dsb.
}
```

`api_compat.dart` menyediakan `ApiProvider` — pembungkus kompatibilitas yang
mengembalikan model terurai (`List<Listing>`, `User`, …) untuk layar lama;
layar baru cukup memakai `ApiClient` langsung.

### 5.3 Model — `fromJson` manual, bukan codegen

Tidak ada `json_serializable`/`build_runner`. Setiap model menulis
`fromJson` tangan dan memetakan `snake_case` backend ke `camelCase` Dart:

```dart
// lib/models/user.dart
class User {
  final String id;
  final String? name;
  final String phone;
  final String? avatarUrl;
  final String status;          // 'menunggu' | 'terverifikasi' | 'ditolak' | 'diblokir'
  final double? latitude;
  final double? longitude;
  final DateTime? verifiedAt;

  factory User.fromJson(Map<String, dynamic> json) => User(
    id: json['id']?.toString() ?? '',
    name: json['name']?.toString(),
    phone: json['phone']?.toString() ?? '',
    avatarUrl: json['avatar_url']?.toString(),
    status: json['status']?.toString() ?? 'menunggu',
    latitude: (json['latitude'] as num?)?.toDouble(),
    longitude: (json['longitude'] as num?)?.toDouble(),
    verifiedAt: json['verified_at'] != null
        ? DateTime.tryParse(json['verified_at'].toString()) : null,
  );

  bool get isVerified => verifiedAt != null;
  bool get isBlocked => status == 'diblokir';
  bool get isProfileComplete => name != null && name!.isNotEmpty && latitude != null;
}
```

> **Koordinat.** API mengirim `latitude`/`longitude` sebagai angka biasa pada
> `user` dan `store` (backend menyimpannya sebagai kolom DECIMAL). GeoJSON
> `[longitude, latitude]` hanya muncul pada endpoint peta — jangan tertukar.

## 6. LAYER LAYANAN & SESI: AUTH SERVICE

Tidak ada lapisan `domain/` terpisah (usecase/entity/repository interface) —
peran itu diisi `services/` + `models/` secara langsung. Ini menjaga kode
tetap kecil tanpa kehilangan keterpisahan tanggung jawab.

### 6.1 AuthService (ChangeNotifier)

`AuthService` mengelola sesi: memuat token & profil saat start, alur OTP,
refresh, dan logout. Ia adalah `ChangeNotifier` dan dibungkus `AppState`.

```dart
class AuthService extends ChangeNotifier {
  static final AuthService _instance = AuthService._();
  factory AuthService() => _instance;
  AuthService._();

  final DioClient _dio = DioClient();
  final ApiClient _api = ApiClient();

  User? _user;
  bool _loading = true;

  User? get user => _user;
  bool get isLoggedIn => _user != null && _dio.token != null;

  Future<void> init() async {
    _loading = true;
    notifyListeners();
    await _dio.loadToken();
    if (_dio.token != null) {
      try {
        final res = await _api.me();                       // GET /auth/me
        _user = User.fromJson(res['user'] as Map<String, dynamic>);
      } catch (e) {
        await _dio.clearToken();   // token ditolak → bersihkan, anggap belum login
      }
    }
    _loading = false;
    notifyListeners();
  }

  Future<Map<String, dynamic>> verifyOtp(String phone, String otp) async {
    final res = await _api.verifyOtp(phone, otp);
    await _dio.setToken(res['token'] as String);           // simpan JWT
    _user = User.fromJson(res['user'] as Map<String, dynamic>);
    notifyListeners();
    return res;
  }

  Future<void> logout() async {
    try { await _api.logout(); } catch (_) {}              // blacklist token di server
    await _dio.clearToken();
    _user = null;
    notifyListeners();
  }
}
```

### 6.2 Alur sesi ringkas

1. **Start:** `DioClient.loadToken()` → jika ada token, `GET /auth/me` untuk
   profil; `401` → token dihapus.
2. **Login:** `POST /auth/request-otp` → `POST /auth/verify-otp` → simpan JWT
   (30 hari) → `context.go('/home')`.
3. **Saat berjalan:** interceptor menempelkan Bearer; `401` memicu
   `POST /auth/refresh` lalu mengulang request; refresh gagal → logout.
4. **Logout:** `POST /auth/logout` (blacklist token) → hapus dari secure
   storage → kembali ke `/onboarding` atau `/login`.

---

## 7. LAYER PRESENTATION: HALAMAN & WIDGET

### 7.1 Halaman Login/OTP (satu layar)

Login **dan** pendaftaran digabung dalam satu layar
(`screens/login_screen.dart`) dengan dua mode ("Masuk" / "Daftar"). OTP
dikirim dan diverifikasi di layar yang sama — tidak ada halaman OTP terpisah.

```dart
class _LoginScreenState extends State<LoginScreen> with SingleTickerProviderStateMixin {
  final _phoneCtrl = TextEditingController(), _otpCtrl = TextEditingController(), _nameCtrl = TextEditingController();
  bool _otpSent = false, _loading = false;
  bool _isRegister = false;
  String? _error;
  String? _debugOtp;
  int _countdown = 0;   // countdown kirim ulang (60s)

  Future<void> _sendOtp() async {
    final p = _phoneCtrl.text.trim();
    if (p.length < 10) { setState(() => _error = 'Masukkan nomor WhatsApp yang valid'); return; }
    setState(() { _loading = true; _error = null; });
    try {
      final res = await context.read<AppState>().requestOtp(p);
      if (mounted) {
        setState(() {
          _otpSent = true;
          _debugOtp = res['debug_otp']?.toString();   // mode dummy: OTP diisi otomatis
        });
        _startCountdown();
      }
    } catch (e) { if (mounted) setState(() => _error = e.toString().replaceAll('Exception: ', '')); }
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _verifyOtp() async {
    setState(() { _loading = true; _error = null; });
    try {
      await context.read<AppState>().verifyOtp(_phoneCtrl.text.trim(), _otpCtrl.text.trim());
      if (_isRegister && _nameCtrl.text.trim().isNotEmpty) {
        await context.read<AppState>().updateProfile(name: _nameCtrl.text.trim());
      }
      if (mounted) context.go('/home');
    } catch (e) { if (mounted) setState(() => _error = e.toString().replaceAll('Exception: ', '')); }
    if (mounted) setState(() => _loading = false);
  }
}
```

Hal-hal penting:

- `context.read<AppState>()` memicu `AuthService`; token JWT disimpan ke
  `flutter_secure_storage` di dalam `DioClient.setToken()`.
- Di environment `local`, server mengembalikan `debug_otp` — layar
  menampilkannya dan mengisinya otomatis agar pengujian cepat.
- Tombol submit menampilkan `CircularProgressIndicator` dan dinonaktifkan
  saat `_loading` (mencegah klik ganda → rate limit 3×/menit).
- `if (!mounted) return;` setelah setiap `await`.

### 7.2 Halaman Detail Listing

`ListingDetailScreen` menampilkan galeri foto (`PageView` +
`smooth_page_indicator` + `cached_network_image`), deskripsi, profil toko
mini, tombol "Pesan Sekarang", "Tanya Penjual" (membuat percakapan lalu
`context.push('/chat/:id')`), favorit, dan berbagi (`share_plus`).

Data diambil `ApiProvider().getListing(id)`; jika listing dikirim lewat
`extra` dari layar sebelumnya, langsung dirender tanpa request ulang
(`_load()` memeriksa `widget.listing`).

**"Pesan Sekarang"** membuka halaman checkout (`/checkout`) — bukan bottom
sheet — yang menerima `Listing` lewat `extra`:

```dart
ctx.push('/checkout', extra: listing);
```

`CheckoutScreen` menyusun body `POST /orders` sesuai `API_DOCUMENTATION.md`
§7.1: `quantity`, `payment_method` (`cod`/`transfer`), `delivery_method`
(`pickup`/`delivery`), dan `shipping_address` yang hanya dikirim bila
`delivery`. Kupon dapat dipakai lewat `/coupon` (extra: `{total, orderId}`).

---

## 8. NAVIGASI & ROUTING (GOROUTER)

Router adalah **objek statis** `appRouter` di `routing/app_router.dart` —
bukan provider. Layar berpindah lewat `context.go(...)` (tab & level atas)
atau `context.push(...)` (halaman detail).

```dart
final appRouter = GoRouter(
  navigatorKey: _rootKey,
  initialLocation: '/splash',
  routes: [
    GoRoute(path: '/splash', builder: (_, __) => const SplashScreen()),
    GoRoute(path: '/onboarding', builder: (_, __) => const OnboardingScreen()),
    GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
    StatefulShellRoute.indexedStack(
      builder: (_, __, shell) => HomeShell(navigationShell: shell),
      branches: [
        StatefulShellBranch(routes: [GoRoute(path: '/home', builder: (_, __) => const HomeScreen())]),
        StatefulShellBranch(routes: [GoRoute(path: '/search', builder: (_, __) => const SearchScreen())]),
        StatefulShellBranch(routes: [GoRoute(path: '/requests', builder: (_, __) => const RequestsScreen())]),
        StatefulShellBranch(routes: [GoRoute(path: '/orders', builder: (_, __) => const OrdersScreen())]),
        StatefulShellBranch(routes: [GoRoute(path: '/profile', builder: (_, __) => const ProfileScreen())]),
      ],
    ),
    GoRoute(path: '/listing/:id', builder: (_, s) => ListingDetailScreen(listingId: s.pathParameters['id']!)),
    GoRoute(path: '/checkout', builder: (_, s) => CheckoutScreen(listing: s.extra as dynamic)),
    GoRoute(path: '/request/:id', builder: (_, s) => RequestDetailScreen(requestId: s.pathParameters['id']!)),
    GoRoute(path: '/chat/:id', builder: (_, s) => ChatScreen(conversation: s.extra as Conversation)),
    // … total 35 GoRoute (lihat app_router.dart)
  ],
);
```

`HomeShell` adalah scaffold 5 tab (Beranda, Cari, Kebutuhan, Pesanan,
Profil) memakai `StatefulNavigationShell`:

```dart
class HomeShell extends StatelessWidget {
  final StatefulNavigationShell navigationShell;
  const HomeShell({super.key, required this.navigationShell});

  @override
  Widget build(BuildContext ctx) => Scaffold(
    body: navigationShell,
    bottomNavigationBar: NavigationBar(
      selectedIndex: navigationShell.currentIndex,
      onDestinationSelected: (i) => navigationShell.goBranch(i),
      destinations: const [
        NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'Beranda'),
        NavigationDestination(icon: Icon(Icons.search_outlined), selectedIcon: Icon(Icons.search), label: 'Cari'),
        NavigationDestination(icon: Icon(Icons.broadcast_on_personal_outlined), selectedIcon: Icon(Icons.broadcast_on_personal), label: 'Kebutuhan'),
        NavigationDestination(icon: Icon(Icons.receipt_long_outlined), selectedIcon: Icon(Icons.receipt_long), label: 'Pesanan'),
        NavigationDestination(icon: Icon(Icons.person_outlined), selectedIcon: Icon(Icons.person), label: 'Profil'),
      ],
    ),
  );
}
```

`StatefulShellRoute.indexedStack` mempertahankan state tiap tab (posisi
scroll, hasil pencarian) saat berpindah tab — itulah alasan memakai
`indexedStack`, bukan `StatefulShellRoute` biasa.

## 9. AUTENTIKASI & OTP FLOW

Alur lengkap (lihat juga §6.2 dan `FLOWS.md`):

1. **`POST /auth/request-otp`** (throttle 3×/menit per nomor) — server
   mengirim OTP lewat WhatsApp (atau menampilkan `debug_otp` di mode dummy).
2. **`POST /auth/verify-otp`** — bila cocok, server membalas
   `{ token, token_type, expires_in, user }`. Token adalah **JWT** (30 hari).
3. Klien menyimpan token ke `flutter_secure_storage` (`jwt_token`) dan
   `AppState.user` terisi → `context.go('/home')`.
4. Setiap request berikutnya: interceptor menempelkan
   `Authorization: Bearer <token>`; saat `401`, interceptor memanggil
   `POST /auth/refresh`, menyimpan token baru, lalu **mengulang request
   asli**. Bila refresh gagal → token dihapus → kembali ke `/login`.
5. **Logout** — `POST /auth/logout` (server mem-blacklist token), lalu token
   lokal dihapus dan `context.go('/onboarding')`.

> Jangan menyimpan token di `shared_preferences` — gunakan
> `flutter_secure_storage`. Jangan menebak isi payload JWT di klien:
> interpretasi kedaluwarsa dilakukan server, klien cukup mengikuti `401`.

## 10. HALAMAN UTAMA & BOTTOM NAVIGATION

`HomeScreen` (tab Beranda):

1. Minta posisi `Geolocator.getCurrentPosition()` (izin via
   `permission_handler`).
2. `GET /home?lat=&lng=` → daftar `Listing` (tren & terdekat) +
   `CustomerRequest` (kebutuhan).
3. Skeleton `Shimmer.fromColors` saat memuat; `RefreshIndicator` untuk
   tarik-untuk-muat-ulang.
4. Kartu listing → `context.push('/listing/:id', extra: listing)`; kartu
   kebutuhan → `context.push('/request/:id', extra: request)`.
5. AppBar: lonceng notifikasi → `/notifications`, favorit → `/favorites`.

`SplashScreen` memutuskan arah awal: `AppState.init()` memuat token & profil;
bila `isLoggedIn` → `context.go('/home')`, bila belum pernah onboarding →
`/onboarding`, selain itu `/login`.

---

## 11. FITUR MARKETPLACE (JELAJAHI)

**SearchScreen** (`screens/search_screen.dart`) — tab Cari:

- `AppBar` opsional (dipakai dari halaman kategori via `/category-search`).
- Kolom pencarian dengan **debounce 350 ms** (`Timer`).
- Filter chips tipe: Semua / Barang (`product`) / Jasa (`service`) / Sewa
  (`rental`).
- Posisi GPS diambil `Geolocator.getCurrentPosition()` (gagal → fallback
  Pasuruan `-7.5, 112.0`).
- Saat mengetik ≥ 2 karakter: `GET /search/suggestions` → daftar sugesti
  (tappable, langsung ke detail listing).
- Enter/submit: `GET /listings?lat&lng&keyword&type&category` → daftar hasil.
- Tap hasil → `context.push('/listing/:id', extra: listing)`.

### 11.1 Search dengan Debounce

Tanpa debounce, mengetik "beras" mengirim banyak request; empat di antaranya
sia-sia dan berisiko kena rate limit 60/menit.

```dart
Timer? _debounce;

Future<void> _onChanged(String q) async {
  _debounce?.cancel();
  _debounce = Timer(const Duration(milliseconds: 350), _search);
  if (q.length >= 2) {
    try {
      final s = await _api.searchSuggestions(q, type: _filter);
      if (mounted) setState(() => _suggestions = s);
    } catch (_) {}
  } else {
    if (mounted) setState(() => _suggestions = []);
  }
}

@override
void dispose() { _debounce?.cancel(); _searchCtrl.dispose(); super.dispose(); }
```

`Timer` **wajib dibatalkan di `dispose()`** — timer aktif setelah widget
dilepas adalah sumber crash klasik.

### 11.2 Filter Chips Tipe

Filter disimpan sebagai state lokal (`String? _filter`), bukan provider —
satu layar, satu pemilik state:

```dart
_chip(null, 'Semua'), const SizedBox(width: 8),
_chip('product', 'Barang'), const SizedBox(width: 8),
_chip('service', 'Jasa'), const SizedBox(width: 8),
_chip('rental', 'Sewa'),

Widget _chip(String? type, String label) {
  final selected = _filter == type;
  return ChoiceChip(
    label: Text(label),
    selected: selected,
    onSelected: (_) { setState(() => _filter = type); _search(); },
  );
}
```

Menekan chip yang sudah aktif akan mengosongkan filter (`type = null`),
karena `onSelected` selalu menerima `true` — bandingkan nilai lama.

## 12. FITUR PAPAN KEBUTUHAN (PASANG KEBUTUHAN)

**RequestsScreen** (tab Kebutuhan) memakai `TabController` dua tab:
"Terdekat" (semua permintaan dalam radius) dan "Saya" (permintaan sendiri).

**CreateRequestScreen** (dalam `requests_screen.dart`):

- Form: judul, deskripsi, kategori (dropdown dari `GET /categories`),
  radius (tetap 15 km — default server), tanggal.
- Lokasi **tidak** memakai peta interaktif: posisi diambil langsung dari GPS
  `Geolocator.getCurrentPosition()` saat form dikirim, lalu dikirim sebagai
  `latitude`/`longitude`:

```dart
final pos = await Geolocator.getCurrentPosition();
await _api.createRequest({
  'title': _titleCtrl.text,
  'description': _descCtrl.text,
  'category_id': _catId,
  'latitude': pos.latitude,
  'longitude': pos.longitude,
  'radius_km': 15,
});
```

> Mengapa tanpa peta: pemilih lokasi interaktif (Google Maps) menambah
> dependensi berat dan sertifikat API key, sementara kebutuhan MVP cukup
> dilayani koordinat GPS + alamat reverse-geocoding. Peta interaktif (Leaflet)
> ada di panel admin (Peta Toko), bukan di aplikasi mobile.

`RequestDetailScreen` menampilkan detail permintaan + daftar penawaran
(`GET /requests/{id}/offers`); pemilik bisa memperpanjang masa berlaku
(`POST /requests/{id}/extend`) atau menutup permintaan.

## 13. FITUR PENAWARAN & TRANSAKSI

- Penyedia membuka permintaan → `StoreOfferRequest` (`POST
  /requests/{id}/offers`) dengan `price`, `additional_cost`,
  `estimation_time`, `notes`. Batas satu penawaran per toko per permintaan
  (UNIQUE di DB).
- Pemilik permintaan membandingkan penawaran (urutkan berdasarkan harga /
  estimasi) dan **menerima** satu (`POST /offers/{id}/accept`) — server
  membuat `Order`, menutup permintaan, dan menolak penawaran lain.
- `OrdersScreen` (tab Pesanan) menampilkan daftar order; `OrderDetailScreen`
  menyajikan detail + aksi sesuai status (unggah bukti transfer, konfirmasi
  selesai, batalkan, buka dispute, beri ulasan).
- Status order: `menunggu_konfirmasi → diproses → dikirim → selesai`, plus
  `dibatalkan` dan `dispute` — diagram lengkap di `API_DOCUMENTATION.md`
  §7.2 dan `OrderStateMachine` di server.
- `CheckoutScreen` (pesan langsung dari listing): pilih metode bayar
  (`cod`/`transfer`) dan antar (`pickup`/`delivery`); saat `delivery`,
  alamat tujuan wajib diisi. Kupon opsional via `/coupon`.

---

## 14. NOTIFIKASI PUSH (FCM)

Implementasi nyata ada di `services/fcm_service.dart` — singleton yang
dipanggil sekali dari `main()`:

```dart
class FcmService {
  static final FcmService _instance = FcmService._();
  factory FcmService() => _instance;
  FcmService._();

  final _api = ApiClient();
  final _local = FlutterLocalNotificationsPlugin();

  Future<void> init() async {
    // Android channel
    const androidChannel = AndroidNotificationChannel(
      'seekitar', 'Seekitar',
      description: 'Notifikasi Seekitar', importance: Importance.high,
    );
    await _local.resolvePlatformSpecificImplementation<
        AndroidFlutterLocalNotificationsPlugin>()?.createNotificationChannel(androidChannel);

    const initSettings = InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
      iOS: DarwinInitializationSettings(),
    );
    await _local.initialize(initSettings);

    // Daftarkan token FCM ke server (POST /auth/fcm-token)
    final token = await FirebaseMessaging.instance.getToken();
    if (token != null) await _register(token);
    FirebaseMessaging.instance.onTokenRefresh.listen(_register);

    // Foreground: tampilkan notifikasi lokal sendiri (Android tidak
    // menampilkan notifikasi sistem saat aplikasi terbuka).
    FirebaseMessaging.onMessage.listen(_showLocalNotification);

    // Tap notifikasi dari latar belakang / kondisi mati.
    FirebaseMessaging.onMessageOpenedApp.listen(_onTap);
    final initial = await FirebaseMessaging.instance.getInitialMessage();
    if (initial != null) _onTap(initial);
  }

  Future<void> _register(String token) async {
    try {
      await _api.registerFcmToken(token, 'android_${token.hashCode}');
    } catch (_) {}
  }

  Future<void> _showLocalNotification(RemoteMessage msg) async {
    await _local.show(
      msg.hashCode,
      msg.notification?.title ?? 'Seekitar',
      msg.notification?.body ?? '',
      const NotificationDetails(
        android: AndroidNotificationDetails(
          'seekitar', 'Seekitar',
          channelDescription: 'Notifikasi Seekitar',
          importance: Importance.high, priority: Priority.high,
        ),
        iOS: DarwinNotificationDetails(),
      ),
      payload: msg.data.isNotEmpty ? msg.data.toString() : null,
    );
  }
}
```

**Izin notifikasi:** Android 13+ meminta lewat dialog sistem (dideklarasikan
di manifest); iOS lewat `requestPermission` — pastikan dipanggil sebelum
`getToken()`. `FcmService.init()` di `main()` dibungkus
`.catchError((_) {})` agar kegagalan Firebase tidak memblokir startup.

**Navigasi dari notifikasi:** `_onTap` saat ini hanya stub — payload notifikasi
belum memetakan ke rute tertentu. Saat fitur ini ditambahkan, tangani **tiga
jalur** sekaligus: `onMessage` (foreground → notifikasi lokal → payload saat
tap), `onMessageOpenedApp` (latar belakang), dan `getInitialMessage()` (mati
total).

**Handler background** harus fungsi top-level, bukan method:

```dart
@pragma('vm:entry-point')
Future<void> firebaseBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  // Isolate terpisah: jangan sentuh state aplikasi di sini.
}
// di main(): FirebaseMessaging.onBackgroundMessage(firebaseBackgroundHandler);
```

## 15. INTEGRASI WHATSAPP

Aplikasi membuka chat WhatsApp lewat deep link `https://wa.me/<nomor>` dengan
`url_launcher`. Tidak ada `WhatsAppService` terpisah — pemanggilan langsung di
layar (halaman Tentang, detail listing):

```dart
// screens/about_screen.dart
final whatsapp = _val('whatsapp');              // dari GET /config
_row(Icons.phone, 'WhatsApp', '+62 $whatsapp', 'https://wa.me/$whatsapp');
// ...
onTap: () => launchUrl(Uri.parse(link), mode: LaunchMode.externalApplication),
```

Pada detail listing, teks yang dikirim bisa memakai `whatsapp_text` dari
response API (`GET /listings/{id}`):

```dart
final text = res['whatsapp_text']?.toString() ?? _detail?.title ?? '';
launchUrl(Uri.parse('https://wa.me/$phone?text=${Uri.encodeComponent(text)}'),
    mode: LaunchMode.externalApplication);
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

## 16. UI/UX GUIDELINES IMPLEMENTASI

- **Warna:** Hijau Lokal `#168A4A` (primary), `#E7F6EC` (subtle),
  latar hangat `#F8FAF9`, teks `#1B1F1C` — sesuai `core/theme.dart` &
  `BRANDING-GUIDELINE.md`.
- **Dark mode:** sengaja **tidak ada** — `ThemeMode.light` selalu (komentar
  di `AppTheme`: palet terang menjaga kontras teks & keterbacaan form).
- **Card:** radius 24dp, elevation 0 dengan shadow ringan.
- **Empty state:** ikon + teks ("Belum ada listing", "Tidak ada hasil", …)
  dari `UiStrings` di `core/constants.dart`.
- **Skeleton:** layar beranda memakai `_skeleton()` (kotak abu-abu
  `Colors.grey.shade200/100` ber-radius) saat `_loading`; paket `shimmer`
  tersedia di pubspec bila ingin efek animasi.

### 16.1 Font Plus Jakarta Sans

Tema mendeklarasikan `fontFamily: 'Plus Jakarta Sans'` sekali di
`AppTheme._buildLight()` — jangan set font per-widget:

```dart
static ThemeData _buildLight() {
  final cs = ColorScheme.fromSeed(seedColor: primary, brightness: Brightness.light);
  return ThemeData(
    useMaterial3: true,
    colorScheme: cs,
    fontFamily: 'Plus Jakarta Sans',   // wajib dibundel — lihat catatan
    textTheme: const TextTheme(
      bodyMedium: TextStyle(color: Color(0xFF1B1F1C), height: 1.4),
      bodySmall: TextStyle(color: Color(0xFF5C665F), height: 1.35),
    ),
  );
}
```

> ⚠️ Aplikasi **tidak** memakai paket `google_fonts` — nama font hanya
> deklarasi. Bundel berkas TTF-nya di `pubspec.yaml` sebelum rilis, kalau
> tidak Flutter memakai font sistem:
>
> ```yaml
> flutter:
>   fonts:
>     - family: Plus Jakarta Sans
>       fonts:
>         - asset: assets/fonts/PlusJakartaSans-Regular.ttf
>         - asset: assets/fonts/PlusJakartaSans-Bold.ttf
>           weight: 700
> ```

### 16.2 Skeleton & Empty State

Skeleton dipakai untuk pemuatan **pertama**; `RefreshIndicator` untuk muat
ulang. Contoh pola di `HomeScreen`:

```dart
body: _loading
    ? _skeleton()                       // placeholder abu-abu
    : _error != null
        ? _err(t)                       // pesan + tombol coba lagi
        : RefreshIndicator(
            onRefresh: _load,
            child: CustomScrollView(slivers: [ /* hero, tren, terdekat, kebutuhan */ ]),
          ),
```

Bentuk skeleton harus **menyerupai konten aslinya**; placeholder generik justru
membuat pergeseran tata letak terasa mengganggu saat data tiba.

---

## 17. TESTING

```bash
flutter analyze
flutter test
```

### 17.1 Kondisi saat ini

Direktori `test/` berisi satu berkas `widget_test.dart` — **masih template
bawaan** `flutter create` (widget test "counter" yang merujuk `MyApp`, padahal
aplikasi memakai `SeekitarApp`). Artinya `flutter test` belum memverifikasi
layar Seekitar; widget test per layar perlu ditulis.

Pola widget test dengan `provider` — bungkus layar dengan
`ChangeNotifierProvider.value` memakai `AppState` tiruan (atau `AuthService`
yang di-*fake*):

```dart
// test/login_screen_test.dart (contoh pola)
class _FakeAppState extends AppState {
  @override
  Future<Map<String, dynamic>> requestOtp(String phone) async {
    // jangan panggil jaringan — kembalikan debug_otp tiruan
    return {'debug_otp': '123456'};
  }
}

Widget buildSubject() => ChangeNotifierProvider.value(
      value: _FakeAppState(),
      child: const MaterialApp(home: LoginScreen()),
    );

testWidgets('nomor pendek menampilkan pesan galat', (tester) async {
  await tester.pumpWidget(buildSubject());
  await tester.enterText(find.byType(TextField).first, '0812');
  await tester.tap(find.text('Kirim Kode OTP'));
  await tester.pump();
  expect(find.text('Masukkan nomor WhatsApp yang valid'), findsOneWidget);
});
```

> ⚠️ **`pump()` vs `pumpAndSettle()`.** `pumpAndSettle()` menunggu semua
> animasi selesai — dan akan **menggantung selamanya** bila ada animasi
> berulang seperti `CircularProgressIndicator` atau countdown. Untuk menguji
> keadaan memuat, pakai `pump()`.

Daftar widget test yang direkomendasikan:

| Berkas | Yang diuji |
| :-- | :-- |
| `login_screen_test.dart` | Validasi nomor, tampilan OTP debug, keadaan memuat |
| `home_screen_test.dart` | Skeleton → data → keadaan kosong |
| `listing_detail_test.dart` | Galeri, tombol pesan/chat tanpa login |
| `checkout_test.dart` | Alamat wajib saat `delivery`, body `POST /orders` |

### 17.2 Integration Test

Belum ada berkas `integration_test/` dan paket `integration_test` tidak ada di
`pubspec.yaml`. Bila ingin menambahkannya, gunakan paket SDK
`integration_test` dan jalankan `flutter test integration_test` dengan
emulator — server dev harus aktif (`./tools/dev/serve`).

```dart
// integration_test/app_test.dart (contoh pola)
import 'package:integration_test/integration_test.dart';
import 'package:seekitar_mobile/main.dart' as app;

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets('alur login OTP → beranda', (tester) async {
    app.main();
    await tester.pumpAndSettle();
    // masukkan nomor, kirim OTP, verifikasi, harapkan /home …
  });
}
```

## 18. DEPLOYMENT & CI/CD

- **Build Android:** `flutter build appbundle --release` (Play Store) atau
  `--release --apk` (distribusi langsung).
- **Build iOS:** `flutter build ipa --release`.
- **CI:** repositori **belum** memiliki `.github/workflows` — template berikut
  siap dipakai saat CI ditambahkan: `flutter analyze` → `flutter test` →
  build APK → unggah artefak.

### 18.1 Penandatanganan Android (Keystore)

Tanpa penandatanganan yang benar, aplikasi tidak bisa diunggah ke Play Store —
dan yang lebih berbahaya, **keystore yang hilang membuat pembaruan aplikasi
mustahil selamanya**.

```bash
keytool -genkey -v -keystore ~/seekitar-release.jks \
  -keyalg RSA -keysize 2048 -validity 10000 -alias seekitar
```

`android/key.properties` — **jangan pernah di-commit**:

```properties
storePassword=<sandi>
keyPassword=<sandi>
keyAlias=seekitar
storeFile=/absolute/path/seekitar-release.jks
```

Pastikan `android/.gitignore` memuat `key.properties`, `*.jks`, `*.keystore`,
dan `build/`. Konfigurasi `signingConfigs` di `android/app/build.gradle.kts`
membaca berkas itu saat ada (lihat template Flutter standar).

### 18.2 Penandatanganan iOS

Lakukan di Xcode (Signing & Capabilities) dengan Apple Developer account;
simpan `.p8`/`.p12` di tempat aman. Untuk rilis TestFlight: `flutter build
ipa --release` lalu unggah lewat Xcode/Transporter.

### 18.3 GitHub Actions (template)

```yaml
name: mobile-ci
on: [push, pull_request]
jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: subosito/flutter-action@v2
        with:
          channel: stable
          flutter-version: 3.44.x
      - run: flutter pub get
      - run: flutter analyze
      - run: flutter test
      - run: flutter build apk --release --dart-define=API_BASE_URL=https://api.seekitar.id/api/v1
      - uses: actions/upload-artifact@v4
        with:
          name: apk-release
          path: build/app/outputs/flutter-apk/app-release.apk
```

## 19. LAMPIRAN: CONTOH KODE PENTING

### 19.1 DioClient lengkap

```dart
class DioClient {
  static final DioClient _instance = DioClient._();
  factory DioClient() => _instance;
  DioClient._() {
    _dio = Dio(BaseOptions(
      baseUrl: AppConstants.baseUrl,
      connectTimeout: AppConstants.connectTimeout,
      receiveTimeout: AppConstants.receiveTimeout,
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
    ));
    _dio.interceptors.addAll([_authInterceptor(), _retryInterceptor(), _logInterceptor()]);
  }

  late final Dio _dio;
  final _storage = const FlutterSecureStorage();
  String? _token;

  Dio get dio => _dio;
  String? get token => _token;

  Future<void> setToken(String t) async {
    _token = t;
    await _storage.write(key: 'jwt_token', value: t);
  }

  Future<void> loadToken() async { _token = await _storage.read(key: 'jwt_token'); }
  Future<void> clearToken() async { _token = null; await _storage.delete(key: 'jwt_token'); }

  InterceptorsWrapper _authInterceptor() => InterceptorsWrapper(
        onRequest: (options, handler) {
          if (_token != null) options.headers['Authorization'] = 'Bearer $_token';
          handler.next(options);
        },
        // onError: 401 → POST /auth/refresh → ulangi request (lihat §5.1)
      );
}
```

### 19.2 Paginasi dengan guard

`ApiClient` menerima `page` (listings, notifications, messages). Pola guard
yang benar saat memuat halaman berikutnya — tiga hal yang mudah salah:

1. **`_page++` sebelum request.** Kalau request gagal, halaman terlanjur naik
   dan satu halaman data hilang selamanya saat pengguna mencoba lagi.
2. **Tanpa guard `_isLoadingMore`.** Scroll cepat memicu `loadMore()`
   berkali-kali, dan item yang sama masuk berulang ke daftar.
3. **Melempar error ke state.** Kegagalan memuat halaman kedua tidak boleh
   menghapus halaman pertama yang sudah tampil.

```dart
class _ListState extends State<SomeListScreen> {
  final _api = ApiProvider();
  final _items = <Listing>[];
  int _page = 1;
  bool _hasMore = true;
  bool _isLoadingMore = false;

  Future<void> _loadMore() async {
    if (!_hasMore || _isLoadingMore) return;     // TIGA guard: habis/sedang/awal
    _isLoadingMore = true;
    final next = _page + 1;
    try {
      final items = await _api.getListings(lat: _lat, lng: _lng, page: next);
      if (!mounted) return;
      _page = next;                               // hanya naik jika BERHASIL
      _hasMore = items.length >= 15;
      setState(() => _items.addAll(items));
    } catch (_) {
      // daftar yang sudah tampil tetap utuh
    } finally {
      _isLoadingMore = false;
    }
  }
}
```

Pemicu scroll sebaiknya `NotificationListener<ScrollEndNotification>` (atau
`ScrollController` dengan margin 300 px), bukan `addListener` mentah yang
menembak berkali-kali per frame.

### 19.3 Token & sesi

Semua penyimpanan token dipusatkan di `DioClient` (secure storage, kunci
`jwt_token`). `AuthService` memakainya lewat `setToken`/`loadToken`/
`clearToken`; jangan menulis `FlutterSecureStorage` langsung di layar.

## 20. KONFIGURASI ENVIRONMENT (DEV / STAGING / PROD)

Memakai **`--dart-define`**, bukan `flutter_dotenv` maupun
`--dart-define-from-file`. Alasannya: nilai `String.fromEnvironment` di-inline
saat kompilasi sebagai `const` — tidak ada berkas yang ikut terbundel ke APK.

```dart
// core/constants.dart
class AppConstants {
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://192.168.201.162:8000/api/v1',   // dev lokal
  );
}
```

```bash
flutter run  --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
flutter build apk --release --dart-define=API_BASE_URL=https://api.seekitar.id/api/v1
```

| Environment | Base URL | Catatan |
| :-- | :-- | :-- |
| Dev (emulator Android) | `http://10.0.2.2:8000/api/v1` | `localhost` tidak terjangkau dari emulator |
| Dev (perangkat fisik) | `http://<IP-LAN>:8000/api/v1` | Server jalan dengan `--host=0.0.0.0` |
| Staging | `https://staging-api.seekitar.id/api/v1` | UAT & closed beta |
| Produksi | `https://api.seekitar.id/api/v1` | |

> ⚠️ `String.fromEnvironment` **harus** `const`. Menulisnya sebagai variabel
> biasa membuat nilainya selalu kosong tanpa peringatan apa pun saat kompilasi.
>
> ⚠️ **URL dev memakai `http://`, jadi Android butuh izin tambahan** (sudah
> dipasang di `android/app/src/main/AndroidManifest.xml`): permission
> `INTERNET` dan `android:usesCleartextTraffic="true"` (Android 9+ memblokir
> traffic plaintext secara default). Sebelum rilis produksi, ganti ke HTTPS
> dan hapus `usesCleartextTraffic` agar hanya koneksi terenkripsi yang diizinkan.

## 21. ERROR HANDLING GLOBAL

`main()` memasang dua pengaman global (lihat `_setupErrorHandling`):

```dart
void _setupErrorHandling() {
  FlutterError.onError = (details) {
    FlutterError.presentError(details);      // tetap cetak di konsol
    logError('Flutter Error', details.exception, details.stack);
  };
  runZonedGuarded(() {}, (error, stack) {
    logError('Uncaught Error', error, stack);
  });
}
```

- `FlutterError.onError` — galat build/layout/paint di dalam framework.
- `runZonedGuarded` — galat asinkron yang lolos dari zona Flutter.
- Layar memakai pola `try/catch` + `setState(_error)` dan menampilkan pesan
  dengan tombol "Coba Lagi"; `if (!mounted) return;` setelah setiap `await`.

> Belum ada `firebase_crashlytics` — galat hanya dicatat lewat `logger`
> (`core/logger.dart`). Tambahkan Crashlytics bila laporan galat terpusat
> dibutuhkan.

## 22. LOGGING

Menggunakan paket `logger` — satu instance global `appLogger` di
`core/logger.dart` dengan pembungkus `logInfo`/`logWarn`/`logError`:

```dart
final appLogger = Logger(
  printer: PrettyPrinter(methodCount: 1, errorMethodCount: 5, lineLength: 80, colors: true, printEmojis: false),
  level: Level.debug,
);

void logInfo(String msg) => appLogger.i(msg);
void logError(String msg, [dynamic error, StackTrace? stack]) =>
    appLogger.e(msg, error: error, stackTrace: stack);
```

Aturan:

- **Jangan pernah log OTP, token JWT, atau NIK** — data pribadi (UU PDP).
- Log di `debugPrint`/`logger` level `debug` untuk alur normal; `error` untuk
  kegagalan tak terduga.
- Tidak ada `FirebaseAnalytics` di aplikasi ini — event marketing belum
  dipasang.

## 23. DEEP LINK

Tidak ada paket `app_links` dan tidak ada skema URI kustom yang didaftarkan —
satu-satunya "jalur masuk dari luar" adalah **tap notifikasi FCM**:

- Aplikasi terbuka (foreground): `FirebaseMessaging.onMessage` → notifikasi
  lokal → payload saat tap.
- Latar belakang: `onMessageOpenedApp`.
- Mati total: `getInitialMessage()`.

`FcmService._onTap` saat ini masih stub; saat navigasi dari notifikasi
diimplementasikan, petakan payload `{screen, entity_id}` ke rute GoRouter
(`/request/:id`, `/listing/:id`, `/order-detail/:id`) — dan tangani ketiga
jalur di atas sekaligus, jangan hanya satu.

---

