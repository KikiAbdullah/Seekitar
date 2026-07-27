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
| `laravel/sanctum`                  | `^4.0`               | —                       | Autentikasi token API                                                  |

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

Flutter 3.44 membawa Dart 3.12. Ini memaksa **Riverpod 3.x**, karena Riverpod
2.x tidak dites untuk SDK setinggi itu dan sudah tidak dirawat.

| Paket                    | Versi proyek | Catatan kompatibilitas                                     |
| :----------------------- | :----------- | :--------------------------------------------------------- |
| `flutter_riverpod`       | `^3.4.0`     | Butuh Dart `^3.12.0`                                        |
| `go_router`              | `^17.3.0`    | Butuh Dart `^3.10.0`, Flutter `>=3.38.0`                    |
| `dio`                    | `^5.11.0`    | —                                                           |
| `google_maps_flutter`    | `^2.18.0`    | —                                                           |
| `geolocator`             | `^14.0.0`    | —                                                           |
| `geocoding`              | `^5.0.0`     | —                                                           |
| `firebase_core`          | `^4.12.0`    | Samakan versi dengan produk Firebase lain                   |
| `firebase_messaging`     | `^16.4.0`    | Wajib sejalan dengan `firebase_core`                        |
| `url_launcher`           | `^6.3.0`     | Redirect WhatsApp                                           |
| `image_picker`           | `^1.2.0`     | —                                                           |
| `shared_preferences`     | `^2.5.0`     | —                                                           |
| `flutter_secure_storage` | `^10.3.0`    | Penyimpanan token                                           |
| `cached_network_image`   | `^3.4.0`     | —                                                           |
| `json_annotation`        | `^4.12.0`    | Pasangan `json_serializable`                                |

### ⚠️ Klarifikasi: GoRouter ↔ Riverpod tidak saling bergantung

Catatan asal menyebut "GoRouter 14 membutuhkan Riverpod 2.x". **Itu keliru.**
`go_router` hanya bergantung pada `collection`, `logging`, dan `meta` — tidak
ada Riverpod sama sekali, begitu pula sebaliknya. Keduanya independen.

Yang benar-benar mengikat keduanya adalah **versi Dart SDK**, dan keduanya
sudah cocok di Dart 3.12.

### ⚠️ Riverpod 3 punya breaking change yang memengaruhi contoh kode

Riverpod 3 **menghapus semua subclass `Ref`** hasil codegen. Pola lama seperti
`NearbyStoresRef`, `DioRef`, `SecureStorageRef` sudah tidak ada — ganti dengan
`Ref` biasa:

```dart
// Riverpod 2.x (lama)
@riverpod
Future<List<Store>> nearbyStores(NearbyStoresRef ref) async { ... }

// Riverpod 3.x (benar)
@riverpod
Future<List<Store>> nearbyStores(Ref ref) async { ... }
```

Perubahan lain yang perlu diwaspadai saat menulis kode:

- `AsyncValue.valueOrNull` dihapus — pakai `.value` (kini bisa `null` saat error).
- `StateProvider` / `StateNotifierProvider` pindah ke `package:flutter_riverpod/legacy.dart`.
- Provider yang gagal kini **auto-retry** secara default.

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

## 5. KONFIGURASI SANCTUM (API stateless vs Web stateful)

Seekitar memakai Sanctum dalam **dua mode sekaligus**, dan ini sumber
kebingungan yang umum:

| Kanal                     | Mode          | Mekanisme                                    |
| :------------------------ | :------------ | :-------------------------------------------- |
| **Mobile app** (`/api/*`) | **Stateless** | Bearer personal access token, tanpa cookie    |
| **Admin panel** (web)     | **Stateful**  | Session cookie Laravel biasa                  |

### `.env`

```env
# Domain yang boleh memakai autentikasi berbasis cookie (SPA/web admin).
# Mobile app TIDAK perlu masuk daftar ini — ia memakai Bearer token.
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:8000,127.0.0.1,127.0.0.1:8000,admin.seekitar.id

SESSION_DOMAIN=.seekitar.id
SESSION_DRIVER=redis
```

### Poin penting

- **Jangan** masukkan `api.seekitar.id` ke `SANCTUM_STATEFUL_DOMAINS`. Kalau
  dimasukkan, request mobile akan diperlakukan stateful dan mulai menuntut
  CSRF token — penyebab umum error 419 yang membingungkan.
- Panel admin memakai guard `web`; endpoint API memakai guard `sanctum`.
  Keduanya harus terdaftar di `config/permission.php` agar Spatie Permission
  bekerja di dua kanal tersebut.
- Untuk produksi, `SESSION_DOMAIN=.seekitar.id` memungkinkan cookie dipakai
  lintas subdomain admin.
- Token mobile sebaiknya diberi _ability_ terbatas (mis. `user`, `store-owner`)
  agar cakupan aksesnya jelas.

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
| `users.verification_level` | TINYINT 1–3 | **Bertingkat** — level 3 lebih tinggi dari level 2 |
| `stores.verification_status` | ENUM | **Kategori** — `verified` bukan “lebih tinggi” dari `rejected`, sekadar berbeda |

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

## 7. CARA MENAIKKAN VERSI

1. Ubah tabel di dokumen ini lebih dulu.
2. Untuk backend: perbarui `composer.json`, jalankan `composer update`, commit
   `composer.lock` yang baru.
3. Untuk mobile: perbarui `pubspec.yaml`, jalankan `flutter pub get`, commit
   `pubspec.lock`.
4. Cek dokumen turunan masih konsisten:
   ```bash
   for c in versions structure datamodel api backend mobile brand prd terms security deploy; do
     node tools/dev/check-$c.mjs || exit 1
   done
   ```
   Sebelas pemeriksa ini menggantikan pencarian `grep` manual dan keluar
   dengan status bukan-nol bila ada yang meleset.
