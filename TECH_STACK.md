# 🧱 TECH STACK & VERSI — SEEKITAR

**Status:** Sumber kebenaran tunggal (_single source of truth_) untuk semua versi.
**Terakhir diverifikasi:** 27 Juli 2026

> Dokumen lain (PRD, DATABASE, API_DOCUMENTATION, Server/Mobile Implementation
> Guide) **tidak boleh** menyebut versi yang berbeda dari tabel di sini. Jika ada
> perbedaan, dokumen ini yang menang. Saat menaikkan versi, ubah di sini dulu,
> baru sesuaikan dokumen turunannya.

---

## 1. KEPUTUSAN VERSI INTI

Stack yang dipakai proyek ini: **Laravel 13 + PHP 8.3**.

Versi di bawah ini bukan asumsi — sudah diverifikasi langsung terhadap
`seekitar-server/composer.lock` dan repositori resmi tiap paket.

| Komponen       | Versi resmi proyek | Bukti verifikasi                                        |
| :------------- | :----------------- | :------------------------------------------------------ |
| **PHP**        | `8.3+` (`^8.3`)    | `composer.lock` → `platform.php: ^8.3`                  |
| **Laravel**    | `13.x` (`^13.8`)   | `composer.lock` → `laravel/framework v13.22.0`          |
| **MySQL**      | `8.0.34+`          | Butuh `POINT` SRID 4326 + `ST_Distance_Sphere`          |
| **Redis**      | `7.x`              | Cache, session, queue broadcast                         |
| **Bootstrap**  | `5.3.x`            | Rilis 5.3 line (terbaru: 5.3.8)                         |
| **Flutter**    | `3.44+`            | `pubspec.lock` → `flutter >=3.18.0`, Dart `>=3.12.2`    |
| **Dart**       | `3.12+`            | `pubspec.lock` → `dart: ">=3.12.2 <4.0.0"`              |

### Catatan penting soal PHP

Tulis **`PHP 8.3+`**, jangan `PHP 8.3.30`. Menyebut versi patch spesifik
membuat dokumen cepat basi dan seolah-olah patch lain tidak didukung. Batasan
riil dari `composer.json` adalah `^8.3`, artinya **8.3.0 ke atas, di bawah 9.0**.

### Catatan penting soal Laravel

Beberapa dokumen lama menyebut "Laravel 11". Itu sudah **tidak berlaku** —
`composer.lock` mengunci `laravel/framework v13.22.0`. Semua penyebutan
Laravel 11 telah diseragamkan menjadi Laravel 13.

---

## 2. MATRIKS KOMPATIBILITAS PAKET BACKEND

> ⚠️ **Ini bagian paling rawan.** Constraint di panduan lama (`^11.0` untuk
> Yajra, `^6.0` untuk Spatie) **tidak akan ter-install di Laravel 13** —
> Composer akan menolak dengan error resolusi dependensi.

| Paket                              | Constraint **BENAR** | Constraint lama (salah) | Alasan                                                                 |
| :--------------------------------- | :------------------- | :---------------------- | :--------------------------------------------------------------------- |
| `yajra/laravel-datatables-oracle`  | `^13.0`              | ~~`^11.0`~~             | v13 butuh `illuminate/support: ^13`; v11 mengunci Laravel 11           |
| `spatie/laravel-permission`        | `^8.0`               | ~~`^6.0`~~              | v8 mendukung `illuminate/auth: ^12.0\|^13.0`; v6 mentok Laravel 11/12  |
| `tymon/jwt-auth`                   | `^2.2`               | —                       | Autentikasi **JWT** API mobile (guard `api`, stateless)                |
| `laravel/sanctum`                  | `^4.3`               | —                       | Sesi stateful admin (cookie) + kompatibilitas token transisi           |

**Aturan praktis:** versi mayor Yajra Datatables mengikuti versi mayor Laravel.
Laravel 13 → Yajra 13. Spatie Permission tidak mengikuti pola itu, jadi selalu
cek `illuminate/auth` di `composer.json` paketnya.

### Nama paket Yajra sering salah tulis

Paket Composer-nya bernama **`yajra/laravel-datatables-oracle`**, bukan
`yajra/laravel-datatables`. Yang terakhir adalah nama repositori GitHub-nya.

```bash
composer require yajra/laravel-datatables-oracle:^13.0
composer require spatie/laravel-permission:^8.0
```

Jika butuh tombol export/print, tambahkan (opsional):

```bash
composer require yajra/laravel-datatables-buttons:^13.0
```

---

## 3. MATRIKS KOMPATIBILITAS PAKET MOBILE

Aplikasi mobile memakai **`provider` (ChangeNotifier)** untuk state
management, **GoRouter** untuk navigasi, dan **Dio** untuk HTTP — lihat
`seekitar_mobile/pubspec.yaml`. Model JSON ditulis manual (`fromJson`), bukan
codegen, jadi **tidak ada** `build_runner`/`json_serializable`/`freezed`.

| Paket                    | Versi proyek | Catatan kompatibilitas                                     |
| :----------------------- | :----------- | :--------------------------------------------------------- |
| `provider`               | `^6.1.0`     | State management — `ChangeNotifierProvider` (bukan Riverpod)|
| `go_router`              | `^14.8.0`    | Navigasi deklaratif + `StatefulShellRoute` (5 tab bawah)    |
| `dio`                    | `^5.7.0`     | HTTP client; interceptor 401 → refresh JWT & retry          |
| `connectivity_plus`      | `^6.1.0`     | Deteksi offline (banner offline)                            |
| `flutter_secure_storage` | `^10.3.1`    | Penyimpanan token JWT (`jwt_token`); kompatibel win32 ^6      |
| `shared_preferences`     | `^2.3.0`     | Preferensi ringan                                           |
| `path_provider`          | `^2.1.0`     | Direktori berkas                                            |
| `flutter_screenutil`     | `^5.9.0`     | Skala UI (designSize 390×844)                               |
| `cached_network_image`   | `^3.4.0`     | Cache gambar listing/avatar                                 |
| `shimmer`                | `^3.0.0`     | Skeleton loading                                            |
| `flutter_rating_bar`     | `^4.0.1`     | Tampilan rating                                             |
| `smooth_page_indicator`  | `^1.2.0`     | Indikator galeri gambar listing                              |
| `flutter_svg`            | `^2.0.0`     | Aset SVG (logo dsb.)                                        |
| `image_picker`           | `^1.1.0`     | Pilih foto (KTP, listing)                                   |
| `flutter_image_compress` | `^2.5.1`     | Kompresi sebelum unggah (built-in Kotlin)                    |
| `geolocator`             | `^13.0.0`    | Posisi pengguna                                             |
| `geocoding`              | `^3.0.0`     | Reverse geocoding alamat                                    |
| `firebase_core`          | `^3.6.0`     | Firebase                                                    |
| `firebase_messaging`     | `^15.1.0`    | FCM push notification                                       |
| `flutter_local_notifications` | `^18.0.0` | Notifikasi lokal saat aplikasi di latar depan          |
| `share_plus`             | `^13.3.0`    | Berbagi tautan listing (API SharePlus.instance.share)       |
| `url_launcher`           | `^6.3.0`     | Buka WhatsApp/nomor telepon                                  |
| `intl`                   | `^0.19.0`    | Format tanggal/angka                                        |
| `permission_handler`     | `^11.3.0`    | Izin lokasi/notifikasi                                      |
| `logger`                 | `^2.5.0`     | Log aplikasi                                                |
| `package_info_plus`      | `^10.2.1`    | Versi aplikasi (built-in Kotlin)                            |

### ⚠️ Klarifikasi: GoRouter ↔ state management tidak saling bergantung

`go_router` hanya bergantung pada `collection`, `logging`, dan `meta` — tidak
ada kaitan dengan `provider` maupun Riverpod. Keduanya independen.

### Pola state management yang dipakai

- Satu `AppState extends ChangeNotifier` global, disuntikkan lewat
  `ChangeNotifierProvider.value` di `main.dart`.
- Layar membaca state dengan `context.watch<T>()` / `context.read<T>()`.
- `ApiClient`/`AuthService` adalah plain class (bukan provider) yang dipakai
  `AppState`; token JWT dibaca dari secure storage oleh `DioClient` dan
  disisipkan sebagai `Authorization: Bearer <token>`.

---

## 4. BASE URL PER ENVIRONMENT

| Environment     | Base URL                              | Keterangan                                  |
| :-------------- | :------------------------------------ | :------------------------------------------- |
| **Production**  | `https://api.seekitar.id/api/v1`      | Rilis publik                                 |
| **Staging**     | `https://staging-api.seekitar.id/api/v1` | UAT & closed beta                         |
| **Development** | `http://localhost:8000/api/v1`        | `php artisan serve` di mesin lokal           |
| **Dev (emulator Android)** | `http://10.0.2.2:8000/api/v1` | `localhost` tidak bisa diakses dari emulator |

Catatan untuk tim mobile: emulator Android memetakan host ke `10.0.2.2`,
sedangkan simulator iOS bisa langsung pakai `localhost`. Simpan nilai ini di
`--dart-define` atau file konfigurasi environment, jangan di-hardcode.

---

## 5. KONFIGURASI AUTENTIKASI (JWT untuk API, Sesi untuk Web Admin)

Seekitar memakai **JWT stateless** (`tymon/jwt-auth`, guard `api`) untuk API
mobile dan **sesi cookie Laravel** untuk panel admin. Sanctum tetap terpasang
untuk sesi stateful berbasis cookie (`SANCTUM_STATEFUL_DOMAINS`) dan
kompatibilitas token lama selama masa transisi — ini sumber kebingungan yang
umum:

| Kanal                     | Mode          | Mekanisme                                    |
| :------------------------ | :------------ | :-------------------------------------------- |
| **Mobile app** (`/api/*`) | **Stateless** | Bearer JWT (`auth:api`), tanpa cookie         |
| **Admin panel** (web)     | **Stateful**  | Session cookie Laravel biasa (guard `web`)    |

### `.env`

```env
# JWT — kunci & umur token (config/jwt.php)
JWT_SECRET=<hasil php artisan jwt:secret>
JWT_TTL=43200            # 30 hari
JWT_REFRESH_TTL=20160    # 14 hari — jendela refresh sejak token pertama
JWT_BLACKLIST_ENABLED=true

# Domain yang boleh memakai autentikasi berbasis cookie (SPA/web admin).
# Mobile app TIDAK perlu masuk daftar ini — ia memakai Bearer JWT.
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:8000,127.0.0.1,127.0.0.1:8000,admin.seekitar.id

SESSION_DOMAIN=.seekitar.id
SESSION_DRIVER=redis
```

### Poin penting

- **Jangan** masukkan `api.seekitar.id` ke `SANCTUM_STATEFUL_DOMAINS`. Kalau
  dimasukkan, request mobile akan diperlakukan stateful dan mulai menuntut
  CSRF token — penyebab umum error 419 yang membingungkan.
- Panel admin memakai guard `web`; endpoint API memakai guard `api` (JWT);
  guard `sanctum` hanya untuk kompatibilitas transisi. Ketiganya terdaftar di
  `config/permission.php` agar Spatie Permission bekerja di kanal yang memakai
  role/permission (web admin dan endpoint admin API).
- Logout API = mem-blacklist token JWT saat ini (`auth('api')->logout()`);
  refresh = token lama di-blacklist, token baru diterbitkan
  (`POST /auth/refresh`, jendela `refresh_ttl`).
- Untuk produksi, `SESSION_DOMAIN=.seekitar.id` memungkinkan cookie dipakai
  lintas subdomain admin.

---

## 6. GLOSARIUM LINTAS LAPISAN

Satu konsep memakai **nama berbeda di tiap lapisan**, dan itu disengaja: UI
berbahasa Indonesia agar akrab bagi pengguna, sedangkan kode berbahasa Inggris
mengikuti konvensi framework. Yang berbahaya adalah ketika satu lapisan
memakai dua nama berbeda untuk hal yang sama.

Tabel ini adalah sumber kebenarannya. Kolom **Jangan pakai** memuat istilah
yang pernah muncul di dokumen dan sudah dihentikan.

| Konsep | UI (Indonesia) | Database | API / Kode | Jangan pakai |
| :-- | :-- | :-- | :-- | :-- |
| Permintaan pembeli | **Permintaan** · *Pasang Kebutuhan* (aksi) | `customer_requests` | `Request`, `/requests` | ~~Kebutuhan~~ sebagai kata benda tunggal |
| Penawaran penyedia | **Penawaran** | `offers` | `Offer`, `/offers` | ~~Bid~~, ~~Tawaran~~ |
| Produk/jasa yang dijual | **Jelajahi** (halaman) · *Produk/Jasa* (item) | `listings` | `Listing`, `/listings` | ~~Katalog~~ sebagai nama entitas |
| Profil usaha | **Toko** (umum) · *Lapak* (materi merek) | `stores` | `Store`, `/stores` | ~~Merchant~~, ~~Seller~~ |
| Akun | **Pengguna** (UI) · *Warga Seekitar* (merek) | `users` | `User`, `/auth` | ~~Customer~~, ~~Member~~ |
| Pesanan | **Pesanan** | `orders` | `Order`, `/orders` | ~~Transaksi~~ sebagai nama entitas |
| Ulasan | **Ulasan** | `reviews` | `Review` | ~~Rating~~ (itu nama kolom, bukan entitas) |
| Laporan masalah | **Laporan** | `disputes` | `Dispute` | ~~Komplain~~, ~~Sengketa~~ |

### Kenapa nama tabel tetap `customer_requests`

Sempat diusulkan mengganti menjadi `user_requests` dengan alasan “semua
pengguna adalah customer”. **Tidak diubah**, karena:

- Seekitar memakai **akun terpadu** (`PRD.md` §3): satu pengguna bisa sekaligus
  pembeli dan penjual. Prefiks `customer_` justru menegaskan **peran** pengguna
  saat membuat permintaan — ia bertindak sebagai pembeli, bukan penjual.
- `user_requests` malah ambigu: bisa terbaca sebagai “permintaan pendaftaran
  pengguna” atau “permintaan bantuan”.
- Nama ini sudah dipakai di 33 tempat pada 4 dokumen. Mengganti nama tabel
  tanpa manfaat nyata hanya menambah risiko.

Yang penting: **konsisten di semua dokumen**, dan saat ini sudah demikian —
nama alternatif itu tidak dipakai di satu tempat pun.

### Sufiks `_level` vs `_status`

Keduanya sudah tepat dan **tidak perlu diseragamkan**:

| Kolom | Tipe | Kenapa sufiksnya begitu |
| :-- | :-- | :-- |
| `users.verification_level` | Turunan 1–3 (bukan kolom) | **Bertingkat** — level 3 lebih tinggi dari level 2. Kolomnya sudah dihapus; nilainya dihitung `User::verificationLevel` dari stempel `verified_at` + status toko (DATABASE.md §4.1) |
| `users.status` | ENUM (`UserStatus`) | **Kategori** — `menunggu`, `terverifikasi`, `ditolak`, `diblokir` adalah keadaan kedudukan, bukan tangga kenaikan |
| `stores.status` | ENUM (`StoreStatus`) | **Kategori** — `pending`, `verified`, `rejected`, `blocked`; `verified` bukan “lebih tinggi” dari `rejected`, sekadar berbeda. Kolom `verification_status` lama diganti; JSON API tetap memakai kunci `verification_status` untuk klien lama (lihat `StoreResource`) |

Aturannya: pakai `_level` bila nilainya berurutan dan bisa dibandingkan,
`_status` bila nilainya sekadar keadaan yang setara.

### Istilah UI di aplikasi penyedia

`Pasang Kebutuhan` dan `Kebutuhan Sekitar` **bukan duplikasi** — keduanya
merujuk data yang sama (`customer_requests`) dari dua sudut pandang:

| Istilah | Untuk siapa | Artinya |
| :-- | :-- | :-- |
| **Pasang Kebutuhan** | Pembeli | Aksi membuat permintaan |
| **Kebutuhan Sekitar** | Penyedia | Menu berisi permintaan terdekat |
| **Permintaan Saya** | Pembeli | Daftar permintaan miliknya sendiri |

Definisi lengkap istilah menghadap-pengguna ada di
[`BRANDING-GUIDELINE.md`](BRANDING-GUIDELINE.md) §2.6.

---

## 6A. VERSI & TANGGAL DOKUMEN

Seluruh dokumen memakai **satu nomor versi bersama**, bukan versi per berkas.
Dokumen-dokumen ini saling merujuk secara ketat, jadi "PRD v3.0 dengan API v1.0"
tidak bermakna — keduanya harus dibaca sebagai satu himpunan.

| Dokumen | Versi |
| :-- | :-- |
| `PRD.md`, `DATABASE.md`, `API_DOCUMENTATION.md`, `Server_Implementation_Guide.md`, `Mobile_Implementation_Guide.md`, `BRANDING-GUIDELINE.md` | **2.3** |

**Aturan penomoran:**

| Perubahan | Naikkan |
| :-- | :-- |
| Perubahan skema / kontrak API yang memutus kompatibilitas | Mayor (2.x → 3.0) |
| Penambahan fitur, kolom, atau endpoint baru | Minor (2.1 → 2.2) |
| Perbaikan penulisan, klarifikasi, contoh kode | Tidak perlu |

> ℹ️ **Tanggal “29 Juli 2026” bukan salah ketik.** Sempat dipertanyakan apakah
> itu tanggal masa depan; dokumen ini memang disusun pada tanggal tersebut.
> Yang perlu dijaga adalah **konsistensinya** — perbarui tanggal hanya saat
> nomor versi berubah, bukan setiap kali menyunting kalimat.

---

## 7. CARA MENAIKKAN VERSI

1. Ubah tabel di dokumen ini lebih dulu.
2. Untuk backend: perbarui `composer.json`, jalankan `composer update`, commit
   `composer.lock` yang baru.
3. Untuk mobile: perbarui `pubspec.yaml`, jalankan `flutter pub get`, commit
   `pubspec.lock`.
4. Cek dokumen turunan masih konsisten:
   ```bash
   for c in versions structure datamodel api backend mobile brand prd terms security deploy dbperf docs; do
     node tools/dev/check-$c.mjs || exit 1
   done
   ```
   Tiga belas pemeriksa ini menggantikan pencarian `grep` manual dan keluar
   dengan status bukan-nol bila ada yang meleset.
