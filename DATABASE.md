# 🗄️ DATABASE DESIGN — SEEKITAR

**Dokumen Lengkap, Optimal, & Production‑Ready**  
**Versi:** 2.3 (Production‑Hardened)  
**Tanggal:** 29 Juli 2026  
**Target Deployment:** MySQL 8.0.34+ InnoDB  
**Charset:** utf8mb4 – Collation: utf8mb4_unicode_ci  
**ORM:** Laravel 13 (Eloquent) · PHP 8.3+

> 📌 Versi mengacu pada [`TECH_STACK.md`](TECH_STACK.md) sebagai sumber kebenaran tunggal.

> **Perubahan 2.3** — verifikasi pengguna dirampingkan menjadi SATU langkah
> (lihat §4.1): OTP sudah merupakan bukti pemilikan nomor HP, sehingga tahap
> "verifikasi nomor" beserta stempelnya (`verified1_*`) dihapus. Kolom
> `users.status` (ENUM `menunggu|terverifikasi|ditolak|diblokir`) menggantikan
> boolean berantai `is_blocked`, dan jejak auditnya kini tunggal:
> `verified_*` (disetujui), `rejected_*` (ditolak), `blocked_*` (diblokir).
> Formulir sunting pengguna di panel kini juga menerima unggahan avatar,
> alamat, dan titik domisili di peta (§4.1 bagian aturan 5).

> **Perubahan 2.2** — dokumentasi diselaraskan dengan implementasi
> `seekitar-server` terkini: level pengguna murni turunan (kolom levelnya
> dihapus dari `users`; lihat §4.1), satu persetujuan admin
> menyelesaikan antrian yang menunggu, aturan perubahan-data-vs-stempel
> (admin tak pernah mengubah stempel; pengguna wajib verifikasi ulang data
> yang digantinya), syarat persetujuan toko diperiksa ulang server (pemilik
> terverifikasi + foto asli terunggah), serta konvensi placeholder foto
> berseed `PlaceholderImg` (§4). (Ditulis ulang 2.3: kemasannya berubah —
> stempel dua tahapnya sudah dilebur jadi satu.)

---

## DAFTAR ISI

1. [Visi & Filosofi Database](#1-visi--filosofi-database)
2. [Standar & Konvensi Penamaan](#2-standar--konvensi-penamaan)
3. [Ringkasan Tabel & Relasi](#3-ringkasan-tabel--relasi)
4. [Skema Lengkap Tabel](#4-skema-lengkap-tabel)
   - 4.1 `users`
   - 4.2 `stores`
   - 4.3 `categories`
   - 4.4 `listings`
   - 4.5 `customer_requests`
   - 4.6 `offers`
   - 4.7 `orders`
   - 4.8 `reviews`
   - 4.9 `disputes`
   - 4.9a `user_devices`
   - 4.9b `settings`
   - 4.9c `favorites`
   - 4.9d `personal_access_tokens` & `sessions` (tabel kerangka Sanctum/session)
   - 4.9e `activity_logs`
   - 4.9f `notifications`
   - 4.9g `conversations`
   - 4.9h `conversation_participants`
   - 4.9i `messages`
   - 4.9j `user_addresses`
   - 4.9k `wallets`
   - 4.9l `wallet_transactions`
   - 4.9m `coupons`
   - 4.9n `coupon_usages`
   - 4.9o `consent_logs`
   - 4.9p `reports`
   - 4.9q `blog_posts`
   - 4.9r `contact_messages`
   - 4.9s `advertisements`
   - 4.10 `service_slots` (Fase 2 — belum dimigrasikan)
   - 4.11 `subscriptions`
5. [Strategi Foreign Key & Cascading](#5-strategi-foreign-key--cascading)
6. [Soft Delete: Implementasi & Cleanup](#6-soft-delete-implementasi--cleanup)
7. [Indeks Komprehensif & Query Patterns](#7-indeks-komprehensif--query-patterns)
8. [Data Integrity Guard (Anti Human‑Error)](#8-data-integrity-guard-anti-human-error)
9. [Keamanan & Pencegahan SQL Injection](#9-keamanan--pencegahan-sql-injection)
10. [Migrasi, Seeder & Deployment di Laravel 13](#10-migrasi-seeder--deployment-di-laravel-13)
11. [Lampiran: Raw Query & Performance Tips](#11-lampiran-raw-query--performance-tips)

---

## 1. VISI & FILOSOFI DATABASE

Database Seekitar dirancang sebagai **Single Source of Truth** untuk seluruh data transaksi pasar lokal. Filosofi inti kami:

- **Integritas di level database** – Bukan hanya di aplikasi. Setiap aturan bisnis yang bisa diwakili oleh constraint (CHECK, UNIQUE, FOREIGN KEY) HARUS ada di database.
- **Tak kenal kompromi pada data yatim** – Setiap baris yang ada selalu merujuk ke entitas yang sah.
- **Geospasial adalah warga kelas satu** – Lokasi disimpan dua cara yang
  disesuaikan dengan pola pembacaannya: kolom `POINT` SRID 4326 untuk entitas
  yang dicari dengan radius SQL murni (`users.location`,
  `customer_requests.location`, `orders.shipping_location`), dan pasangan
  `DECIMAL` berindeks untuk `stores` yang pencarian radiusnya berjalan lewat
  `whereBetween` Eloquent + haversine di PHP (lihat §4.2 dan §11).
- **UUID mencegah prediksi** – Semua ID adalah UUID v4, menghilangkan risiko enumerasi dan memperkuat keamanan.
- **Soft delete wajib untuk data penting** – `users`, `stores`, `listings` tidak pernah dihapus permanen; hanya disembunyikan.
- **Siap dioperasikan oleh manusia** – Nama kolom deskriptif, constraint mencegah kesalahan input, dan default value masuk akal.

### MySQL 8.0.34+ adalah satu-satunya engine yang didukung

Bukan sekadar preferensi — skema ini memakai fitur yang **tidak punya padanan**
di engine lain, dan tanpa itu jaminan integritas di atas ikut gugur:

| Fitur | Dipakai untuk | Tidak ada di |
| :-- | :-- | :-- |
| `POINT` + `SRID 4326` | Lokasi yang dicari radius lewat SQL spasial: `users.location`, `customer_requests.location`, `orders.shipping_location` (§7.3) | SQLite, tanpa ekstensi |
| `SPATIAL INDEX` (R-tree) | Pencarian penyedia di sekitar permintaan (`cr_location_spatial`, §7.3) | SQLite |
| Tipe `SET` | `stores.store_type` kombinasi (§4.2) | SQLite, PostgreSQL |
| `CHECK` constraint | 7 aturan lintas kolom (§8) | SQLite (diabaikan diam-diam) |
| Kolom `GENERATED ... STORED` | Uniqueness nama toko per kabupaten (§4.2) | SQLite lama |
| `ST_Distance_Sphere` | Jarak dalam meter (tahap 2 pencarian radius, §7.3 & §11) | SQLite |

`config/database.php` karena itu **hanya** mendaftarkan koneksi `mysql`;
koneksi SQLite/PostgreSQL/SQL Server sengaja dihapus agar tidak ada yang
tanpa sengaja menjalankan aplikasi di engine yang diam-diam melonggarkan
aturan. Pengujian pun berjalan di MySQL sungguhan — lihat `CONTRIBUTING.md`.

---

## 2. STANDAR & KONVENSI PENAMAAN

> 📌 Padanan nama tabel dengan istilah UI dan API ada di
> [`TECH_STACK.md`](TECH_STACK.md) §6 (Glosarium Lintas Lapisan).

| Objek           | Aturan                                   | Contoh                       |
| --------------- | ---------------------------------------- | ---------------------------- |
| Tabel           | jamak, snake_case                        | `users`, `customer_requests` |
| Primary Key     | `id` CHAR(36)                            | –                            |
| Foreign Key     | `nama_tabel_tunggal_id`                  | `store_id`, `buyer_id`       |
| Indeks Biasa    | `{tabel}_{kolom}_idx`                    | `stores_user_id_idx`         |
| Indeks Unik     | `{tabel}_{kolom}_unique`                 | `users_phone_unique`         |
| Indeks Spasial  | `{tabel}_{kolom}_spatial`                | `cr_location_spatial`        |
| Indeks Fulltext | `{tabel}_ft_{kolom}`                     | `listings_fulltext`          |
| Timestamp       | `created_at`, `updated_at`, `deleted_at` | –                            |
| Kolom JSON      | Nama deskriptif, isi array/objek         | `operating_hours`, `images`  |

**Catalan:** Kami tidak menggunakan prefix tabel (`skt_`) untuk menjaga kompatibilitas Laravel default; jika diinginkan, tambahkan di file konfigurasi.

---

## 3. RINGKASAN TABEL & RELASI

| Tabel               | Deskripsi                          | Soft Delete | Relasi Utama                                                           |
| ------------------- | ---------------------------------- | ----------- | ---------------------------------------------------------------------- |
| `users`             | Semua pengguna (pembeli & penjual) | ✅          | Punya `stores`, `customer_requests`, `orders`, `reviews`, `disputes`   |
| `stores`            | Toko/lapak penyedia barang/jasa    | ✅          | Milik `users`, berisi `listings`, menerima `offers` & `orders`         |
| `categories`        | Kategori 2 level (induk & sub)     | ❌          | Digunakan oleh `stores` (via JSON) & `customer_requests`               |
| `listings`          | Produk/jasa yang dijual            | ✅          | Milik `stores`, bisa dipesan langsung (`orders`)                       |
| `customer_requests` | Permintaan dari pembeli (Reverse)  | ❌ (status) | Dibuat `users`, dijawab `offers` dari `stores`                         |
| `offers`            | Penawaran harga dari penyedia      | ❌ (status) | Terkait `customer_requests` & `stores`; pemenang menghasilkan `orders` |
| `orders`            | Transaksi yang terjadi             | ❌          | Pembeli (`users`), penjual (`stores`), sumber (`offers`/`listings`)    |
| `reviews`           | Ulasan pasca‑transaksi (dua arah)  | ❌          | Maks 2 per `orders`; ke `stores` (rating toko) atau ke `users`         |
| `disputes`          | Laporan masalah                    | ❌          | Terkait `orders`, dilaporkan `users`                                   |
| `user_addresses`    | Buku alamat pengiriman             | ❌          | Milik `users`                                                          |
| `conversations`     | Percakapan chat                    | ❌          | Terkait `orders` (opsional); peserta via `conversation_participants`   |
| `messages`          | Pesan chat (append‑only)           | ❌          | Milik `conversations`, dikirim `users`                                 |
| `wallets`           | Dompet saldo pengguna              | ❌          | Satu‑ke‑satu dengan `users`; mutasi di `wallet_transactions`           |
| `coupons`           | Kupon diskon                       | ❌          | Dipakai `orders` (via `coupon_id`); jejak di `coupon_usages`           |
| `notifications`     | Notifikasi dalam aplikasi          | ❌          | Milik `users`                                                          |
| `activity_logs`     | Jejak audit (morph)                | ❌          | Pelaku `users` (opsional), entitas morph                               |
| `consent_logs`      | Jejak persetujuan UU PDP           | ❌          | Milik `users`                                                          |
| `reports`           | Pelaporan konten/pengguna          | ❌          | Pelapor `users`, entitas morph                                         |
| `blog_posts`        | Artikel blog web publik            | ❌          | –                                                                      |
| `contact_messages`  | Pesan formulir kontak web          | ❌          | –                                                                      |
| `advertisements`    | Iklan banner lokal (Fase 2)        | ❌          | Pemesan `users` (opsional)                                             |
| `subscriptions`     | Langganan toko (Fase 2)            | ❌          | Milik `users`, terkait `stores` (opsional)                             |
| `personal_access_tokens`, `sessions` | Kerangka Sanctum/session (transisi JWT) | ❌ | Milik `users` |
| `roles`, `permissions`, `model_has_*`, `role_has_permissions` | RBAC Spatie | ❌ | Terikat `users` (guard `web`) |
| `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` | Kerangka Laravel | ❌ | – |

**Diagram Relasi (High Level)**

```
users 1──N stores
users 1──N customer_requests
users 1──N orders (as buyer)
users 1──N reviews (reviewer / reviewee)
users 1──N disputes (reporter)
stores 1──N listings
stores 1──N offers
stores 1──N orders
categories 1──N customer_requests
customer_requests 1──N offers
offers 0..1──1 orders (via offer_id)
listings 0..1──1 orders (via listing_id)
orders 1──2 reviews (maks satu per arah: buyer_to_store, store_to_buyer)
stores 1──N reviews (hanya arah buyer_to_store, sumber rating_avg)
orders 1──N disputes
```

---

## 4. SKEMA LENGKAP TABEL

Setiap tabel dilengkapi penjelasan tiap kolom, alasan pemilihan tipe, dan constraint lengkap.

> **Konvensi placeholder foto (sisi aplikasi).** Kolom foto tampilan yang
> kosong (`avatar_url`, `stores.photo`, `listings.images`) tidak pernah
> merender gambar hampa: aksesor model menjatuhkannya ke URL **berseed**
> (`App\Support\PlaceholderImg`, pola `picsum.photos/seed/{id}/{w}/{h}`)
> supaya entitas yang sama selalu mendapat foto yang sama. Karena itu,
> konteks VERIFIKASI wajib membaca kolom mentah (`getRawOriginal(...)`) —
> gambar hiasan bukan bukti. Dokumen identitas (KTP/selfie) sengaja TIDAK
> diberi placeholder: status "Belum diunggah" lebih jujur daripada foto acak.

### 4.1 `users`

| Kolom                | Tipe                 | Keterangan & Rasional                                                                |
| -------------------- | -------------------- | ------------------------------------------------------------------------------------ |
| `id`                 | CHAR(36)             | UUID v4, PRIMARY KEY. UUID dipilih agar tidak dapat ditebak, cocok untuk API publik. |
| `email`              | VARCHAR(255) NULL UNIQUE | **Khusus admin.** Login panel web; NULL untuk pengguna biasa. |
| `email_verified_at`  | TIMESTAMP NULL       | Bawaan Laravel; belum dipakai alur apa pun.                    |
| `password`           | VARCHAR(255) NULL    | **Khusus admin.** Hash bcrypt. NULL = akun hanya bisa OTP.     |
| `phone`              | VARCHAR(15)          | Nomor HP Indonesia (diawali 62), unik. Menghindari duplikasi akun. Keberadaannya di tabel ini SEKALIGUS jejak "nomor dibuktikan OTP" — kode hanya dikirim ke nomornya sendiri. |
| `name`               | VARCHAR(100) NULL   | Nama asli pengguna. NULL saat akun baru dibuat lewat OTP (nomor saja), diisi belakangan lewat profil.           |
| `avatar_url`         | VARCHAR(500) NULL    | URL foto profil, disimpan di cloud storage. Panjang 500 cukup untuk URL pre‑signed.  |
| `location`           | POINT SRID 4326 NULL | Lokasi default pengguna (misal rumah). NULL hanya saat onboarding belum selesai.     |
| `address`            | VARCHAR(255) NULL    | Alamat teks hasil reverse geocoding. Untuk ditampilkan, bukan untuk query.           |
| `notification_prefs` | JSON NULL            | Preferensi notifikasi per pengguna. **Khusus backend** — tidak ada di `$fillable`, jadi tidak bisa diisi lewat mass-assignment. |
| `blocked_user_ids`   | JSON NULL            | Daftar ID pengguna yang diblokir pengguna. **Khusus backend** — tidak ada di `$fillable`, jadi tidak bisa diisi lewat mass-assignment. |
| `status`             | ENUM('menunggu','terverifikasi','ditolak','diblokir') DEFAULT 'menunggu' | **Kedudukan akun, SATU kata.** `menunggu` = nomor sudah OTP tapi identitas belum disetujui admin (nilai awal setiap akun); `terverifikasi` = admin menyetujui wajah+KTP+alamat+titik; `ditolak` = berkas belum sesuai, boleh kirim ulang; `diblokir` = akun bermasalah — token dicabut & tokonya ikut nonaktif. INDEX. |
| `ktp_image`          | VARCHAR(500) NULL    | Path foto KTP di disk PRIVAT (UU PDP), bukan URL publik. Diisi saat mengajukan berkas. |
| `selfie_image`       | VARCHAR(500) NULL    | Path foto wajah di disk privat. Wajib bersama `ktp_image`.                          |
| `ktp_submitted_at`   | TIMESTAMP NULL       | Kapan berkas (terakhir) diajukan — dipakai SLA peninjauan 1×24 jam DAN pembeda "sudah antre" vs "belum pernah mengajukan" pada status menunggu. |
| `verified_by`        | CHAR(36) NULL FK → `users.id` | Admin yang menyetujui berkas identitas. Ditulis pasangan dengan `verified_at`, tulis-sekali per siklus. ON DELETE SET NULL. |
| `verified_at`        | TIMESTAMP NULL       | Kapan disetujui — kolom inilah yang membuat pengguna sah membuka toko (`User::canOpenStore()` membacanya LANGSUNG). |
| `rejected_by`        | CHAR(36) NULL FK → `users.id` | Admin yang menolak berkas. Dipertahankan sampai berkas pengganti DISETUJUI — konteks "periksa ulang apa" di antrian. ON DELETE SET NULL. |
| `rejected_at`        | TIMESTAMP NULL       | Kapan ditolak — pasangan `rejected_by`. |
| `rejected_reason`    | TEXT NULL            | Alasan penolakan, tampil di aplikasi pengguna agar tahu apa yang diperbaiki. |
| `blocked_by`         | CHAR(36) NULL FK → `users.id` | Admin yang memblokir. Dikosongkan saat blokir dicabut. ON DELETE SET NULL. |
| `blocked_at`         | TIMESTAMP NULL       | Kapan diblokir — untuk audit dan pencabutan token. |
| `blocked_reason`     | VARCHAR(255) NULL    | Alasan pemblokiran, ditampilkan ke pengguna saat login ditolak.                      |
| `rating_avg`         | DECIMAL(3,2) DEFAULT 0.00 | Rata-rata rating 1–5 sebagai PEMBELI — hanya dari ulasan `store_to_buyer`. Dihitung ulang `ReviewObserver`, bukan diisi manual. |
| `total_reviews`      | INT UNSIGNED DEFAULT 0      | Jumlah ulasan yang diterima sebagai pembeli — pasangan `rating_avg`. |
| `nik`                | VARCHAR(255) NULL    | NIK hasil pembacaan admin. **Terenkripsi** (cast `encrypted`), bukan plaintext.      |
| `nik_hash`           | CHAR(64) NULL        | SHA-256 dari NIK. Untuk mendeteksi NIK ganda, karena kolom terenkripsi tak bisa di-`WHERE`. |
| `remember_token`     | VARCHAR(100) NULL    | Bawaan Laravel. Tidak dipakai alur OTP, tetap ada agar `Authenticatable` utuh.       |
| `deleted_at`         | TIMESTAMP NULL       | Soft delete untuk pengguna yang menonaktifkan akun.                                  |
| `created_at`         | TIMESTAMP            | Otomatis diisi Laravel.                                                              |
| `updated_at`         | TIMESTAMP            | Otomatis diisi Laravel.                                                              |

> ### ⚠️ Kenapa `users` punya `email` & `password` padahal identitasnya nomor HP
>
> `Server_Implementation_Guide.md` §18A.5 menetapkan login panel admin memakai
> **email + kata sandi**, sementara §6.1 menyebut panel memakai sesi Laravel
> biasa. Tanpa kedua kolom ini, `Auth::attempt()` mustahil berhasil:
> `EloquentUserProvider::validateCredentials()` memanggil `getAuthPassword()`
> yang mengembalikan NULL — akun super-admin hasil seeder terbuat tetapi
> **tidak akan pernah bisa masuk**.
>
> Panel admin dibuka di browser desktop, sering tanpa WhatsApp di perangkat
> yang sama. Memaksa OTP di sana berarti admin harus meraih ponsel tiap kali
> sesi 120 menit habis.
>
> Keduanya **NULL-able**: hanya segelintir akun yang punya kredensial ini.
> Pengguna biasa tetap masuk lewat OTP dan tidak pernah punya kata sandi.
> MySQL memperlakukan tiap NULL sebagai nilai berbeda, jadi `UNIQUE(email)`
> tetap sah meski ribuan baris kosong.

**Constraint & Indeks:**

- PRIMARY KEY (`id`)
- UNIQUE KEY `users_email_unique` (`email`) — NULL-able, hanya akun admin
- UNIQUE KEY `users_phone_unique` (`phone`)
- SPATIAL INDEX `users_location_spatial` (`location`)
- INDEX `users_status_index` (`status`) — antrian & filter panel memfilternya
- INDEX `users_deleted_at_idx` (`deleted_at`) — untuk filter global scope soft delete.

> ⚠️ **Kenapa `location` tetap NULL-able (bukan NOT NULL).**
> PRD §5.3.1 memang mewajibkan pengguna mengisi lokasi, tetapi kewajiban itu
> berlaku **setelah** OTP terverifikasi. Barisnya sudah harus ada lebih dulu
> untuk menyimpan `phone` saat OTP dikirim, sehingga `NOT NULL` akan membuat
> registrasi mustahil diselesaikan (ayam-dan-telur).
>
> MySQL juga **tidak mendukung SPATIAL INDEX pada kolom NULL-able** — jadi
> pilih salah satu:
>
> 1. **Dua tahap (dipakai di sini):** kolom NULL-able, `location` diisi saat
>    onboarding, dan kewajibannya ditegakkan oleh middleware
>    `EnsureProfileComplete` — bukan oleh constraint. Konsekuensinya
>    `users_location_spatial` **tidak bisa dibuat**; pencarian berbasis lokasi
>    pengguna memakai koordinat yang dikirim aplikasi, bukan kolom ini.
> 2. **NOT NULL + default:** isi `POINT(0 0)` sebagai penanda "belum diisi".
>    SPATIAL INDEX bisa dibuat, tapi setiap query wajib menyaring titik nol —
>    mudah terlupa dan berisiko menampilkan hasil ngawur.
>
> Opsi 1 dipilih karena kolom ini hanya dipakai sebagai *default* saat
> pengguna membuka aplikasi; query radius yang sesungguhnya selalu memakai
> `stores.location` dan `customer_requests.location` yang keduanya NOT NULL.

- UNIQUE KEY `users_nik_hash_unique` (`nik_hash`) — satu NIK hanya untuk satu akun.

> ⚠️ **Kolom `encrypted` tidak bisa dicari.** Laravel memakai IV acak per baris,
> sehingga NIK yang sama menghasilkan ciphertext berbeda setiap kali disimpan —
> `WHERE nik = ?` selalu gagal. Karena itu `nik_hash` disimpan terpisah sebagai
> SHA-256 (dengan `APP_KEY` sebagai pepper) agar bisa diindeks dan diperiksa
> keunikannya. Lihat `Server_Implementation_Guide.md` §18A.3.

**Berkas identitas (UU PDP):** `ktp_image` dan `selfie_image` adalah data pribadi
sensitif. Simpan di disk privat, akses hanya lewat route berizin `verify-users`
(header `Cache-Control: private, no-store`), dan **jangan** pernah dikembalikan
di response API publik. Setelah disetujui, berkas boleh dihapus sesuai kebijakan
retensi.

**Kedudukan akun SATU kolom.** `status` menggantikan boolean berantai lama
("sudah OTP?" + "KTP lulus?" + "diblokir?") — empat keadaan itu saling
eksklusif, sedangkan boolean terpisah bisa menyatakan dua-duanya sekaligus.
Nilainya satu kata dan sumber kebenaran tunggal untuk panel, API, dan lencana:

| status | Kapan | Efek praktis |
| :----- | :---- | :----------- |
| `menunggu` | Default setiap akun (nomor sudah OTP); juga kedudukan setelah kirim/kirim-ulang berkas, atau setelah blokir dicabut tanpa stempel `verified_at` | Belum bisa membuka toko. Bila `ktp_submitted_at` terisi → masuk antrian tinjauan admin. |
| `terverifikasi` | Admin menyetujui wajah, KTP, alamat, dan titik domisili | Boleh membuka toko (`canOpenStore()`), ikon centang di panel, level API = 2. |
| `ditolak` | Admin menolak berkas dengan alasan | Keluar antrian; alasan tampil di aplikasi; kirim ulang mengembalikannya ke `menunggu`. |
| `diblokir` | Admin memblokir dengan alasan | Login ditolak (423), token dicabut, tokonya ikut nonaktif (pesanan berjalan tidak diusik). Membuka blokir mengembalikan `terverifikasi` bila `verified_at` masih terisi, kalau tidak kembali `menunggu`. |

**Verifikasi identitas SATU langkah.** Tidak ada tahap "verifikasi nomor HP":
kode OTP hanya dikirim ke nomornya sendiri, sehingga keberadaan akunnya sudah
merupakan bukti pemilikan nomor — tidak ada stempel tersendiri untuk itu.
Yang diverifikasi admin persis satu hal: berkas identitas (wajah, KTP/NIK,
alamat, titik domisili). Satu klik Setujui di antrian menulis
`verified_by`+`verified_at` pasangan (tulis-sekali, transaksi berkunci,
antrian `pendingVerification` = status `menunggu` DAN berkas sudah terkirim)
dan mengangkat `status` ke `terverifikasi`. Penolakan menulis `rejected_*`
dan menjatuhkan `status` ke `ditolak`.

**Kontrak penulisan stempel.** `verified_*` tidak pernah ditimpa; pengajuan
ulang OLEH PENGGUNA yang sebelumnya sudah disetujui mengosongkannya — berkas
baru belum diperiksa siapa pun. `rejected_*` justru dipertahankan sampai
siklus pengganti disetujui (itulah konteks admin "periksa ulang apa").
Perubahan dari sisi ADMIN lewat formulir panel tidak pernah menyentuh stempel
maupun `status` (aturan 5 — `Admin\UserController::update`).

**Level API DIHITUNG, bukan disimpan.** Tidak ada kolom level di tabel ini —
faktanya sudah dijawab lengkap oleh `status`/stempel + status toko, dan kolom
tersendiri adalah sumber kebenaran kedua yang pasti suatu hari berbeda
pendapat. Turunannya (`User::verificationLevel`, satu-satunya sumber untuk
`UserResource::verification_level`):

| Level | Label | Fakta penentunya |
| :---- | :---- | :--------------- |
| 1 | Nomor Terverifikasi | Selalu berlaku — setiap pengguna masuk lewat OTP (kedudukan menunggu/ditolak/diblokir). |
| 2 | Identitas Terverifikasi | `verified_at` terisi. Syarat membuka toko: `User::canOpenStore()` membaca kolom ini LANGSUNG, bukan levelnya, supaya toko verified warisan tidak bisa mengangkat pemilik tanpa persetujuan identitas. |
| 3 | Usaha Terverifikasi | Memiliki minimal 1 toko `verification_status = 'verified'` — jejaknya `stores.verified_by` / `verified_at`, bukan stempel ketiga di sini (usaha itu sendiri adalah berkasnya). |

**Kontrak berjenjang pengguna → toko.** Toko hanya bisa DISETUJUI bila
pemiliknya `terverifikasi` (`canOpenStore()`) DAN berkas tokonya memenuhi
syarat (foto etalase asli terunggah; dibaca dari kolom mentah karena aksesor
`Store::photo` menjatuhkan nilai kosong ke placeholder hiasan). Keduanya
diperiksa ulang SERVER pada klik Setujui, bukan hanya saat pengajuan —
kedudukan pemilik bisa berubah (diblokir/ditolak ulang) selama menunggu
antrian. Level 3 pemilik adalah KONSEKUENSI dari stempel `stores.verified_*`
tersebut, bukan penyetujuan terpisah di profil pengguna.

Karena level murni turunan, TIDAK ADA penyuntingan level manual di panel:
menaikkan pengguna = menyetujui berkasnya lewat antrian yang berjejak;
menurunkan haknya = memblokir akun (dengan alasan), bukan memangkas angka.
Konsekuensi filter admin: "level 3" = `WHERE EXISTS (toko verified)`,
"level 2" = `verified_at` terisi TANPA toko verified, "level 1" = sisanya —
satu definisi bersama di `User::scopeWhereVerificationLevel`.

### 4.2 `stores`

| Kolom                 | Tipe                                                    | Keterangan                                                                     |
| --------------------- | ------------------------------------------------------- | ------------------------------------------------------------------------------ |
| `id`                  | CHAR(36)                                                | PK, UUID.                                                                      |
| `user_id`             | CHAR(36)                                                | FK ke `users.id`. Pemilik toko. Satu user bisa punya banyak toko.              |
| `name`                | VARCHAR(100)                                            | Nama lapak, unik per kabupaten (`regency`). Lihat catatan uniqueness.          |
| `photo`               | VARCHAR(500) NULL                                       | URL foto etalase toko. **Publik** seperti `avatar_url` — tampil di hasil pencarian dan diperiksa admin saat verifikasi. |
| `regency`             | VARCHAR(100)                                            | Kabupaten/kota tempat toko berada. Diisi dari reverse geocoding saat pinpoint. |
| `regency_code`        | CHAR(4) NULL                                            | Kode wilayah BPS (mis. `3578`). Sumber kebenaran untuk geofencing.             |
| `npwp`                | VARCHAR(20) NULL                                        | NPWP usaha (opsional). Syarat pendukung verifikasi Level 3 (PRD §5.3.2).       |
| `bank_account`        | VARCHAR(100) NULL                                       | Rekening tujuan transfer, mis. `BCA 1234567890`. Ditampilkan ke pembeli saat `payment_method = 'transfer'` (§4.7). |
| `bank_account_name`   | VARCHAR(100) NULL                                       | Atas nama rekening tujuan transfer. Transfer manual butuh keduanya agar tidak salah kirim. |
| `store_type`          | SET('goods','services','rental')                        | Kombinasi jenis usaha. SET lebih efisien dari VARCHAR untuk pilihan tetap.     |
| `category_ids`        | JSON                                                    | Array ID dari `categories`. Contoh: `[1, 3, 7]`.                               |
| `address`             | VARCHAR(255) NULL                                       | Alamat teks toko. Diisi reverse geocoding, bisa disunting pemilik.             |
| `service_radius_km`   | DECIMAL(5,2) DEFAULT 5.00                               | Radius layanan toko dalam km. Presisi 2 desimal. Lihat catatan di bawah.       |
| `latitude`            | DECIMAL(11,8) NOT NULL                                  | Lintang lokasi toko — kolom BIASA, bukan POINT. Presisi ±1,1 mm. Dipakai pencarian radius dua tahap (§11). |
| `longitude`           | DECIMAL(12,8) NOT NULL                                  | Bujur lokasi toko. Berpasangan dengan `latitude` dalam indeks `stores_latlng_idx`. |
| `accepts_cod`         | TINYINT(1) DEFAULT 1                                    | Menerima bayar di tempat. Sumber badge “Bisa COD”.                             |
| `offers_delivery`     | TINYINT(1) DEFAULT 0                                    | Mengantar sendiri. Sumber badge “Bisa Diantar”.                                |
| `allows_pickup`       | TINYINT(1) DEFAULT 1                                    | Punya lokasi fisik yang bisa didatangi. Sumber badge “Ambil di Tempat”.        |
| `operating_hours`     | JSON                                                    | Jam operasional per hari. Contoh: `{"senin":{"open":"08:00","close":"17:00"}}` |
| `rating_avg`          | DECIMAL(3,2) DEFAULT 0.00                               | Rata‑rata rating, dihitung ulang setiap ada ulasan baru.                       |
| `total_reviews`       | INT UNSIGNED DEFAULT 0                                  | Jumlah total ulasan, counter untuk kalkulasi cepat.                            |
| `is_active`           | TINYINT(1) DEFAULT 1                                    | Toko nonaktif tidak muncul di pencarian.                                       |
| `status`              | ENUM('pending','verified','rejected','blocked') DEFAULT 'pending' | Kedudukan toko — empat keadaan saling eksklusif. Menggantikan `verification_status` (lihat catatan di bawah). |
| `rejected_reason`     | TEXT NULL                                               | Alasan penolakan admin. Wajib diisi saat status `rejected`.                    |
| `rejected_at`         | TIMESTAMP NULL                                          | Kapan penolakan terjadi. Jejak audit tulis‑sekali.                             |
| `rejected_by`         | CHAR(36) NULL FK → `users.id`                           | Admin yang menolak. ON DELETE SET NULL.                                        |
| `verified_at`         | TIMESTAMP NULL                                          | Kapan toko disetujui — untuk audit & SLA. Merangkap bendera "terverifikasi": NULL berarti belum. |
| `verified_by`         | CHAR(36) NULL FK → `users.id`                           | Admin yang menyetujui. Selalu diisi berpasangan dengan `verified_at`. ON DELETE SET NULL. |
| `blocked_at`          | TIMESTAMP NULL                                          | Kapan toko diblokir. Toko diblokir BERSAMA pemiliknya (lihat catatan di bawah). |
| `blocked_by`          | CHAR(36) NULL FK → `users.id`                           | Admin yang memblokir. ON DELETE SET NULL.                                       |
| `blocked_reason`      | VARCHAR(255) NULL                                       | Alasan pemblokiran. Wajib diisi saat `status = 'blocked'`.                      |
| `deleted_at`          | TIMESTAMP NULL                                          | Soft delete.                                                                   |
| `created_at`          | TIMESTAMP                                               | –                                                                              |
| `updated_at`          | TIMESTAMP                                               | –                                                                              |

**Constraint & Indeks:**

- PRIMARY KEY (`id`)
- FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL —
  admin yang menyetujui; akun admin dihapus permanen tidak menghapus tokonya.
- FOREIGN KEY (`rejected_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
- FOREIGN KEY (`blocked_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
- INDEX `stores_user_id_idx` (`user_id`)
- INDEX `stores_deleted_at_idx` (`deleted_at`)
- INDEX `stores_is_active_idx` (`is_active`) — mempercepat query toko aktif.
- INDEX `stores_delivery_idx` (`offers_delivery`)
- INDEX `stores_status_idx` (`status`)
- INDEX `stores_latlng_idx` (`latitude`, `longitude`) — kotak pembatas
  pencarian radius dibaca latitude dulu baru longitude (§11).
- INDEX `stores_status_active_idx` (`status`, `is_active`)
- CHECK `stores_fulfilment_chk` — minimal satu cara penyerahan aktif (lihat
  "Kapabilitas layanan" di bawah).

> **Lokasi toko adalah DECIMAL, bukan POINT.** `POINT SRID 4326` memaksa semua
> pembacaan lewat fungsi spasial mentah (`ST_Latitude`, `ST_Distance_Sphere`,
> `MBRContains`) yang tidak bisa ditulis murni lewat Eloquent. Pada skala satu
> kabupaten, kotak pembatas `whereBetween` berindeks (Eloquent murni) menyaring
> kandidat dalam milidetik, lalu haversine di PHP menyisakan lingkaran
> akuratnya — lebih cepat dari `ST_Distance_Sphere` per baris, tanpa jebakan
> `axis-order=long-lat` (implementasi: `Store::scopeWithinBox` +
> `App\Support\Jarak`). Kolom POINT tetap dipakai untuk
> `customer_requests.location` dan `orders.shipping_location` yang jarang
> disaring berindeks DECIMAL.

> **`status` menggantikan `verification_status`.** Empat keadaan eksklusif
> (`pending | verified | rejected | blocked`) dengan jejak audit tunggal per
> keadaan. "Diblokir" bukan turunan `is_active`: toko diblokir BERSAMA
> pemiliknya, dan membuka blokir orang mengembalikan kedudukan tokonya yang
> sebenarnya.

**SOP verifikasi toko di panel admin** — tiga langkah sebelum `verified_by`/`verified_at`
diisi, dan modal antrian menyusun berkas persis dalam urutan ini:

1. **Alamat & kabupaten** dicocokkan dengan berkas (`address`, `regency`).
2. **Foto toko** (`photo`) dipastikan jelas memperlihatkan tempat usaha.
3. **Koordinat** (`latitude`/`longitude`) dicocokkan dengan Google Maps lewat tautan
   langsung `google.com/maps/search/?api=1&query=lat,lng` yang disediakan
   modal — format resmi Google, tanpa API key.

Tombol persetujuan terkunci sampai ketiganya dicentang. Hasil ketiga
pemeriksaan tidak disimpan sebagai kolom terpisah: stempel
`verified_by` + `verified_at` sudah menyatakan seluruhnya lolos — kolom
`is_address_valid` dan sejenisnya hanya akan menduplikasi makna tersebut.

Di atas ketiga langkah penilaian itu ada dua SYARAT POKOK yang bersifat
fakta data, bukan penilaian admin. Karena itu server memeriksanya ulang saat
persetujuan — tidak cukup mengandalkan `StorePolicy::create` waktu pengajuan:

1. **Pemilik sudah terverifikasi** — identitasnya disetujui admin,
   `users.verified_at` terisi (syarat ini dibaca langsung dari stempelnya,
   bukan dari label apa pun).
   Dicek ulang saat menyetujui karena pengajuan bisa masuk antrian LALU
   keadaan pemiliknya berubah, dan persetujuan tidak boleh mengesahkan
   toko yang syaratnya sudah gugur.
2. **Foto etalase benar-benar terunggah** — dibaca lewat
   `getRawOriginal('photo')`. Aksesor `photo` menjatuhkan nilai kosong ke
   placeholder hiasan demi tampilan publik, dan gambar hiasan bukanlah bukti.

Stempel `verified_by`/`verified_at` sendiri tulis-sekali: persetujuan hanya
berlaku dari status `pending`, sehingga klik ganda atau POST ulang tidak
pernah menimpa siapa & kapan yang sudah tercatat.

> **Catatan: `service_radius_km` DEFAULT 5.00 sudah benar — jangan diubah ke 15.**
> Ada dua radius berbeda di sistem ini dan keduanya sering tertukar:
>
> | Kolom | Default | Milik | Arti |
> | :-- | :-- | :-- | :-- |
> | `stores.service_radius_km` | **5 km** | Penjual | Seberapa jauh toko bersedia melayani |
> | `customer_requests.radius_km` | **15 km** | Pembeli | Seberapa jauh pembeli mencari penyedia |
>
> Angka 15 km di `PRD.md` baris 168 adalah "radius maksimal penyedia" pada form
> **pasang kebutuhan** — itu milik pembeli, bukan toko. Untuk toko, PRD baris 230
> justru mencontohkan "5 km untuk toko kelontong, 20 km untuk tukang bangunan",
> yang konsisten dengan default 5 km.
>
> Menyamakan keduanya jadi 15 km akan membuat toko kelontong muncul di
> pencarian sejauh 15 km — bertentangan dengan premis *lokal* produk ini.

**Kapabilitas layanan (`accepts_cod`, `offers_delivery`, `allows_pickup`).**
Ketiganya menggambarkan **kemampuan toko**, berbeda dari
`orders.payment_method` / `orders.delivery_method` yang mencatat **pilihan pada
satu pesanan**. Kolom di sini yang menjadi sumber badge di
`BRANDING-GUIDELINE.md` §4.2 dan filter pencarian.

- CHECK: minimal satu cara penyerahan harus aktif —
  ```sql
  ALTER TABLE stores ADD CONSTRAINT stores_fulfilment_chk
    CHECK (offers_delivery = 1 OR allows_pickup = 1);
  ```
- Validasi silang saat membuat pesanan: `delivery_method = 'delivery'` pada toko
  ber-`offers_delivery = 0` ditolak `422`; begitu pula `payment_method = 'cod'`
  pada toko ber-`accepts_cod = 0`.
- INDEX `stores_delivery_idx` (`offers_delivery`) — untuk filter “bisa diantar”.

**Uniqueness nama toko per kabupaten.** PRD §5.3.3 mensyaratkan nama unik per
kabupaten, tetapi tanpa kolom wilayah aturan itu tidak bisa ditegakkan sama
sekali. Karena itu `regency` (teks nama kabupaten) dan `regency_code` (kode
BPS 4 digit) ditambahkan sebagai lingkup keunikan.

> ⚠️ Kolom generated `name_regency_active` (CONCAT nama + kabupaten) yang
> pernah direncanakan untuk menegakkan aturan ini di level engine **tidak
> diimplementasikan** — tidak ada kolom maupun indeks unik tambahan di
> migrasi `stores`. `regency`/`regency_code` tetap disimpan sebagai lingkup
> aturan, dan keunikannya dijaga di lapisan aplikasi (validasi saat membuat /
> memperbarui toko). Jika suatu saat aturan ini perlu dikunci di engine,
> migrasi baru harus menambahkan kolom generated + UNIQUE KEY — bukan
> `UNIQUE (name, regency, deleted_at)` polos, karena `NULL` pada `deleted_at`
> tidak pernah dianggap sama dengan `NULL` di SQL sehingga constraint itu
> tidak menegakkan apa pun (dua toko aktif bernama sama tetap lolos).

Perbandingan nama bergantung pada collation. Dengan `utf8mb4_unicode_ci`
(§Charset), "Warung Bu Sri" dan "warung bu sri" dianggap **sama** — memang
yang diinginkan, karena keduanya membingungkan pembeli.

**Geofencing kabupaten target (PRD §5.3.3).** Toko di luar kabupaten target
harus ditolak otomatis. Ada dua tingkat pemeriksaan:

1. **Cepat (saat submit):** `regency_code` hasil reverse geocoding dicocokkan
   dengan daftar kabupaten yang dilayani. Menolak sebagian besar kasus salah
   wilayah tanpa biaya spasial.
2. **Akurat (opsional):** simpan poligon batas kabupaten dan uji titiknya —
   reverse geocoding bisa meleset di dekat perbatasan.

**Geofencing kabupaten target (PRD §5.3.3).** Seekitar dikunci pada **satu**
kabupaten/kota yang ditetapkan lewat konfigurasi, bukan tabel wilayah:

```php
// config/seekitar.php
'regency'      => env('SEEKITAR_REGENCY', 'Kabupaten Pasuruan'),
'regency_code' => env('SEEKITAR_REGENCY_CODE', '3514'),   // kode BPS
```

Saat toko dibuat, `regency_code` diisi dari `config('seekitar.regency_code')`
(di `StoreController::store`), bukan dari input pengguna — karena aplikasi
hanya melayani satu kabupaten, tidak ada jalur ke toko di luar wilayah itu.
Tabel `service_areas` berpoligon (`POLYGON SRID 4326`) yang pernah
direncanakan untuk verifikasi presisi **tidak diimplementasikan**; verifikasi
administratif tetap menjadi lapis terakhir melalui peninjauan admin.

**Kenapa SET untuk store_type?**  
Karena tipe toko terbatas (3 pilihan), SET lebih hemat ruang dan memungkinkan pencarian dengan `FIND_IN_SET` atau `LIKE` jika perlu.

> **Catatan: SET tetap dipertahankan, JSON/pivot ditolak.**
> Sempat diusulkan mengganti `SET` menjadi `JSON` atau tabel pivot
> `store_types` dengan alasan "SET kurang fleksibel untuk kombinasi". Premis itu
> tidak tepat — **SET justru memang tipe MySQL untuk menyimpan kombinasi**, dan
> satu kolom bisa memuat `'goods,services'` sekaligus.
>
> Perbandingan untuk kasus 3 nilai tetap:
>
> | Aspek | `SET` (dipilih) | `JSON` | Pivot `store_types` |
> | :-- | :-- | :-- | :-- |
> | Ukuran | 1 byte | ~20 byte | 1 baris/tipe + indeks |
> | Nilai tak dikenal | Ditolak engine | Bisa lolos | Dijaga FK |
> | Query kombinasi | `FIND_IN_SET` | `JSON_CONTAINS` | perlu `JOIN` |
> | Cocok saat | pilihan tetap & sedikit | skema berubah-ubah | butuh atribut per tipe |
>
> Pivot baru sepadan jika tiap tipe perlu atribut sendiri (mis. radius berbeda
> per tipe). Selama belum ada kebutuhan itu, pivot hanya menambah `JOIN` pada
> query terpanas — pencarian toko dalam radius. **Ubah hanya jika `store_type`
> berkembang melampaui 3 nilai atau butuh atribut turunan.**

**Kenapa `store_type` jamak, sedangkan `listing_type` tunggal?**

Sempat diusulkan menyeragamkan semuanya menjadi tunggal atau semuanya jamak.
Yang benar adalah **membedakannya secara sengaja**, karena ketiganya menjawab
pertanyaan tata bahasa yang berbeda:

| Kolom | Tipe SQL | Pertanyaan | Nilai |
| :-- | :-- | :-- | :-- |
| `stores.store_type` | **SET** (banyak nilai) | Toko ini menjual *apa saja*? | `goods`, `services`, `rental` |
| `listings.listing_type` | ENUM (satu nilai) | Listing ini *sebuah* apa? | `product`, `service`, `rental` |
| `orders.order_type` | ENUM (satu nilai) | Pesanan ini *sebuah* apa? | `product`, `service`, `rental` |

`store_type` bersifat **SET** — satu toko bisa `'goods,services'` sekaligus.
Bentuk jamak wajar karena menggambarkan kumpulan: “toko ini menjual barang dan
jasa”. Sementara `listing_type` selalu satu nilai: sebuah listing adalah
*sebuah* produk, bukan “produk-produk”.

> ⚠️ `rental` tetap tunggal di ketiganya. `rentals` sebagai kata benda jamak
> terasa janggal dalam konteks ini, dan mengubahnya berarti migrasi tanpa
> manfaat nyata.
>
> Yang **wajib** identik adalah `listing_type` dan `order_type`, karena nilainya
> disalin langsung saat pesanan dibuat (§4.7). Perbedaan `store_type` tidak
> menimbulkan masalah karena nilainya tidak pernah disalin ke kolom lain.

**Kenapa `category_ids` JSON, bukan comma-separated?**  
`PRD.md` §8 menulis `VARCHAR(255)` dengan keterangan "JSON array atau
comma-separated" — ambigu. Yang berlaku adalah **JSON**, karena:

- MySQL memvalidasi struktur JSON; string comma-separated bisa berisi apa saja.
- Bisa diindeks lewat multi-valued index:
  `ALTER TABLE stores ADD INDEX idx_categories ((CAST(category_ids AS UNSIGNED ARRAY)));`
- Laravel meng-cast otomatis ke array PHP (`'category_ids' => 'array'`).

Pencarian toko per kategori memakai `JSON_CONTAINS`:

```sql
SELECT * FROM stores WHERE JSON_CONTAINS(category_ids, '7');
```

> ⚠️ `category_ids` **tidak punya foreign key** — JSON tidak mendukungnya.
> Validasi keberadaan kategori wajib dilakukan di Form Request
> (`exists:categories,id`), dan penghapusan kategori harus memeriksa
> pemakaiannya di JSON ini secara manual.

### 4.3 `categories`

| Kolom        | Tipe                        | Keterangan                                                         |
| ------------ | --------------------------- | ------------------------------------------------------------------ |
| `id`         | INT UNSIGNED AUTO_INCREMENT | PK. Karena jumlahnya terbatas, integer cukup.                      |
| `name`       | VARCHAR(50)                 | Nama kategori.                                                     |
| `slug`       | VARCHAR(50) UNIQUE          | URL-friendly, dibuat dari name.                                    |
| `parent_id`  | INT UNSIGNED NULL           | FK ke `id` sendiri, membentuk hierarki. NULL untuk kategori induk. |
| `icon`       | VARCHAR(50) NULL            | Nama ikon (FontAwesome, dll).                                      |
| `sort_order` | SMALLINT UNSIGNED DEFAULT 0 | Urutan tampilan di UI.                                             |
| `created_at` | TIMESTAMP                   | –                                                                  |
| `updated_at` | TIMESTAMP                   | –                                                                  |

**Constraint & Indeks:**

- PRIMARY KEY (`id`)
- FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
- INDEX `categories_parent_id_idx` (`parent_id`)

> **`parent_id` diubah dari `SET NULL` ke `RESTRICT`.**
> Dengan `SET NULL`, menghapus kategori induk membuat seluruh subkategorinya
> **naik menjadi kategori induk** secara diam-diam. Menghapus "Elektronik"
> mendadak memunculkan "AC", "Kulkas", dan "TV" di level teratas — hierarki
> rusak tanpa peringatan apa pun.
>
> `RESTRICT` memaksa admin memindahkan atau menghapus anaknya lebih dulu. Ini
> juga konsisten dengan `customer_requests.category_id` yang sudah memakai
> `RESTRICT`, sehingga aturan penghapusan kategori seragam di seluruh skema.
>
> Perlu diingat `stores.category_ids` (JSON) **tidak** terlindungi FK. Sebelum
> menghapus kategori, aplikasi wajib memeriksa pemakaiannya:
>
> ```php
> $used = Store::whereRaw('JSON_CONTAINS(category_ids, ?)', [(string) $category->id])->exists();
> if ($used) {
>     throw new CategoryInUseException();
> }
> ```

### 4.4 `listings`

| Kolom          | Tipe                                            | Keterangan                           |
| -------------- | ----------------------------------------------- | ------------------------------------ |
| `id`           | CHAR(36)                                        | PK, UUID.                            |
| `store_id`     | CHAR(36)                                        | FK ke `stores`.                      |
| `title`        | VARCHAR(200)                                    | Judul listing, akan di‑FULLTEXT.     |
| `description`  | TEXT                                            | Deskripsi panjang.                   |
| `listing_type` | ENUM('product','service','rental')              | Tipe listing.                        |
| `price`        | DECIMAL(12,2) NULL                              | Harga tetap; NULL jika “nego”.       |
| `stock_qty`    | INT UNSIGNED NULL                               | Stok (hanya untuk product & rental). |
| `slot`         | TINYINT UNSIGNED NULL                           | Slot jasa (hanya service).           |
| `images`       | JSON                                            | Array URL gambar (min 1, max 5).     |
| `status`       | ENUM('active','sold','hidden') DEFAULT 'active' |                                      |
| `deleted_at`   | TIMESTAMP NULL                                  | Soft delete.                         |
| `created_at`   | TIMESTAMP                                       | –                                    |
| `updated_at`   | TIMESTAMP                                       | –                                    |

**Constraint & Indeks:**

- PRIMARY KEY (`id`)
- FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE
- INDEX `listings_store_id_idx` (`store_id`)
- INDEX `listings_deleted_at_idx` (`deleted_at`)
- FULLTEXT INDEX `listings_fulltext` (`title`, `description`)
- INDEX `listings_status_idx` (`status`) — untuk filter aktif/tidak.
- CHECK `listings_price_required_chk` — harga wajib untuk product & rental.
- CHECK `listings_qty_slot_chk` — stok/slot sesuai tipe listing.

**Keterangan tambahan:** `price` NULL memungkinkan listing tanpa harga (misal jasa yang memerlukan survey). Validasi di level aplikasi: jika `listing_type` = ‘service’, price boleh NULL; untuk product/rental wajib diisi.

**CHECK constraint (ditegakkan di level engine):**

```sql
ALTER TABLE listings
  ADD CONSTRAINT listings_price_required_chk
  CHECK (listing_type = 'service' OR price IS NOT NULL);

ALTER TABLE listings
  ADD CONSTRAINT listings_qty_slot_chk
  CHECK (
    (listing_type IN ('product','rental') AND stock_qty IS NOT NULL AND slot IS NULL)
    OR
    (listing_type = 'service' AND slot IS NOT NULL AND stock_qty IS NULL)
  );
```

Constraint kedua sekaligus mencegah kombinasi tak masuk akal — misalnya sebuah
jasa yang punya `stock_qty`. Aturan yang sama diulang di Form Request agar
pengguna mendapat pesan error yang ramah, bukan error SQL:

```php
'price'     => ['nullable', 'decimal:0,2', 'min:0', Rule::requiredIf(
                   fn () => in_array($this->listing_type, ['product', 'rental'], true))],
'stock_qty' => ['prohibited_unless:listing_type,product,rental', 'required_if:listing_type,product,rental', 'integer', 'min:0'],
'slot'      => ['prohibited_unless:listing_type,service', 'required_if:listing_type,service', 'integer', 'min:1'],
```

> ⚠️ MySQL baru benar-benar menegakkan CHECK sejak **8.0.16**. Ini salah satu
> alasan target minimum proyek adalah MySQL 8.0.34+. Di versi lebih lama,
> constraint diterima tapi diam-diam diabaikan.

**Validasi `images` (min 1, maks 5):** JSON tidak bisa membatasi panjang array
lewat CHECK secara praktis, jadi aturan ini **hanya** ditegakkan aplikasi:

```php
'images'   => ['required', 'array', 'min:1', 'max:5'],
'images.*' => ['url', 'max:500'],
```

Batas maks 5 foto berasal dari `PRD.md` §4 ("foto maks 5").

### 4.5 `customer_requests`

| Kolom               | Tipe                                           | Keterangan                                                        |
| ------------------- | ---------------------------------------------- | ----------------------------------------------------------------- |
| `id`                | CHAR(36)                                       | PK, UUID.                                                         |
| `user_id`           | CHAR(36)                                       | FK ke `users` (pembeli).                                          |
| `title`             | VARCHAR(200)                                   |                                                                   |
| `description`       | TEXT                                           |                                                                   |
| `category_id`       | INT UNSIGNED                                   | FK ke `categories`. Wajib, karena broadcast berdasarkan kategori. |
| `budget_min`        | DECIMAL(12,2) NULL                             |                                                                   |
| `budget_max`        | DECIMAL(12,2) NULL                             |                                                                   |
| `images`            | JSON NULL                                      | Foto pendukung, maks 3 (PRD §5.2.1). NULL jika tidak ada.         |
| `location`          | POINT SRID 4326                                | Titik lokasi pembeli, wajib.                                      |
| `radius_km`         | DECIMAL(5,2) DEFAULT 15.00                     | Radius pencarian penyedia (default PRD: 15 km).                   |
| `required_date`     | TIMESTAMP NULL                                 | Kapan kebutuhan harus dipenuhi.                                   |
| `expires_at`        | TIMESTAMP NOT NULL                             | Waktu kedaluwarsa (default 24 jam sejak dibuat).                  |
| `extended_at`       | TIMESTAMP NULL                                 | Kapan terakhir diperpanjang. NULL = belum pernah.                 |
| `extension_count`   | TINYINT UNSIGNED DEFAULT 0                     | Berapa kali diperpanjang. Dibatasi agar tidak abadi.              |
| `status`            | ENUM('open','closed','expired') DEFAULT 'open' |                                                                   |
| `accepted_offer_id` | CHAR(36) NULL                                  | FK ke `offers`, penawaran pemenang. Diisi saat pembeli memilih.   |
| `created_at`        | TIMESTAMP                                      | –                                                                 |
| `updated_at`        | TIMESTAMP                                      | –                                                                 |

**Constraint & Indeks:**

- PRIMARY KEY (`id`)
- FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT — kategori tidak boleh dihapus jika ada permintaan terkait.
- FOREIGN KEY (`accepted_offer_id`) REFERENCES `offers`(`id`) ON DELETE SET NULL — jika offer dihapus, pemenang dibatalkan.
- INDEX `cr_user_id_idx` (`user_id`)
- INDEX `cr_category_id_idx` (`category_id`)
- INDEX `cr_status_expires_idx` (`status`, `expires_at`) — untuk scheduler menutup permintaan kadaluarsa.
- INDEX `cr_accepted_offer_id_idx` (`accepted_offer_id`)
- SPATIAL INDEX `cr_location_spatial` (`location`)
- **Tidak ada CHECK budget di database** — `budget_max` tidak boleh lebih kecil dari `budget_min` ditegakkan di FormRequest, bukan CHECK di DB.

**Perpanjangan masa aktif:** saat pembeli memperpanjang, `expires_at` didorong
maju, `extended_at` diisi waktu sekarang, dan `extension_count` bertambah.
Batasi maksimal 2 kali perpanjangan agar papan kebutuhan tidak dipenuhi
permintaan basi:

```php
if ($request->extension_count >= 2) {
    throw new TooManyExtensionsException();
}
$request->update([
    'expires_at'      => now()->addHours(24),
    'extended_at'     => now(),
    'extension_count' => $request->extension_count + 1,
]);
```

**Validasi `images`:** maks 3 foto, ditegakkan aplikasi (`'images' => 'nullable|array|max:3'`).

> ⚠️ **Menerima penawaran wajib atomik.** Saat satu offer diterima, tiga hal
> harus berubah bersamaan: request jadi `closed`, offer pemenang jadi
> `accepted`, dan **semua offer lain jadi `rejected`**. Tidak ada trigger
> database untuk ini — tanggung jawab aplikasi, di dalam satu transaksi:

```php
DB::transaction(function () use ($request, $winningOffer) {
    // Kunci baris agar tidak ada dua pemenang saat request bersamaan.
    $request = CustomerRequest::whereKey($request->id)->lockForUpdate()->first();

    if ($request->status !== RequestStatus::Open) {
        throw new RequestAlreadyClosedException();
    }

    Offer::where('request_id', $request->id)
        ->whereKeyNot($winningOffer->id)
        ->update(['status' => OfferStatus::Rejected]);

    $winningOffer->update(['status' => OfferStatus::Accepted]);

    $request->update([
        'status'            => RequestStatus::Closed,
        'accepted_offer_id' => $winningOffer->id,
    ]);

    $order = Order::create([...]);   // order terbentuk dari offer pemenang
    event(new OfferAccepted($winningOffer, $order));
});
```

`lockForUpdate()` penting: tanpa itu, dua pembeli yang menekan "Terima" nyaris
bersamaan bisa menghasilkan dua order dari satu permintaan.

### 4.6 `offers`

| Kolom             | Tipe                                                    | Keterangan                      |
| ----------------- | ------------------------------------------------------- | ------------------------------- |
| `id`              | CHAR(36)                                                | PK, UUID.                       |
| `request_id`      | CHAR(36)                                                | FK ke `customer_requests`.      |
| `store_id`        | CHAR(36)                                                | FK ke `stores`.                 |
| `price`           | DECIMAL(12,2)                                           | Harga jasa/barang saja, **belum** termasuk biaya tambahan. |
| `additional_cost` | DECIMAL(12,2) DEFAULT 0.00                              | Ongkos antar/transport/material. 0 jika tidak ada.         |
| `additional_cost_note` | VARCHAR(150) NULL                                  | Rincian singkat, mis. "Ongkos antar 3 km".                 |
| `estimation_time` | VARCHAR(100)                                            | Teks bebas yang ditampilkan ke pembeli, mis. "2–3 hari kerja". |
| `estimated_hours` | SMALLINT UNSIGNED NULL                                  | Bentuk numerik untuk sorting & analitik. Lihat catatan.        |
| `notes`           | TEXT NULL                                               | Catatan tambahan.               |
| `status`          | ENUM('pending','accepted','rejected') DEFAULT 'pending' |                                 |
| `expires_at`      | TIMESTAMP NOT NULL                                      | Kedaluwarsa penawaran (default 48 jam). Lihat catatan.         |
| `created_at`      | TIMESTAMP                                               | –                               |
| `updated_at`      | TIMESTAMP                                               | –                               |

**Constraint & Indeks:**

- PRIMARY KEY (`id`)
- FOREIGN KEY (`request_id`) REFERENCES `customer_requests`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE
- UNIQUE KEY `offers_req_store_status_unique` (`request_id`, `store_id`, `status`) — satu penawaran AKTIF (`pending`/`accepted`) per toko per permintaan; beberapa penawaran `rejected` diperbolehkan. Aturan bisnisnya dijaga di lapisan aplikasi/state, bukan UNIQUE penuh.
- INDEX `offers_status_idx` (`status`)

**Mengapa CASCADE pada store_id?** Jika toko dihapus (soft delete), penawaran menjadi tidak valid. Daripada memperumit, kita hapus cascade; data penawaran sudah tidak relevan. Order yang sudah terjadi tetap utuh karena `offer_id` di orders menggunakan `SET NULL`.

**Biaya tambahan dipisah dari harga.** PRD §5.2.4 menyebut pembeli mengurutkan
penawaran berdasarkan "Harga Terendah". Jika ongkos antar digabung ke `price`,
penyedia yang jujur mencantumkan ongkos akan selalu kalah urutan dari yang
menyembunyikannya lalu menagih di tempat.

- `price` = nilai pekerjaan/barang.
- `additional_cost` = ongkos antar, transport, atau material.
- **Total yang mengikat** = `price + additional_cost`, dan inilah yang menjadi
  `orders.total_amount` saat penawaran diterima.

Pengurutan "termurah" memakai total, bukan `price` saja:

```sql
ORDER BY (price + additional_cost) ASC
```

> ⚠️ UI **wajib** menampilkan rincian ("Rp150.000 + Rp15.000 ongkos antar"),
> bukan hanya total. Pembeli yang merasa ada biaya tersembunyi adalah salah
> satu pemicu dispute paling umum di marketplace lokal.

- `additional_cost >= 0` **tidak punya CHECK di database** — ditegakkan di FormRequest/UI.

**Kenapa `estimation_time` DIPERTAHANKAN dan `estimated_hours` DITAMBAHKAN.**  
Sempat diusulkan mengganti `estimation_time` menjadi kolom numerik. Mengganti
akan menghilangkan kemampuan penyedia menulis estimasi bernuansa yang justru
membangun kepercayaan — "2–3 hari kerja, tergantung stok". Karena itu keduanya
disimpan berdampingan:

- `estimation_time` — **yang dilihat pembeli**, teks apa adanya dari penyedia.
- `estimated_hours` — **yang dipakai sistem** untuk mengurutkan "tercepat" dan
  menghitung rata-rata waktu respons.

Aplikasi mengisi `estimated_hours` dari input terstruktur (angka + satuan
hari/jam) lalu merangkainya menjadi `estimation_time`. Kolom numerik dibuat
NULL-able karena penawaran lama belum memilikinya.

- INDEX `offers_estimated_hours_idx` (`estimated_hours`) — untuk sortir tercepat.

**Kedaluwarsa penawaran.** Tanpa `expires_at`, penawaran menggantung selamanya
dan pembeli bisa menerima harga yang sudah tidak relevan. Aturannya:

```sql
-- Default 48 jam, tapi tidak boleh melebihi masa aktif permintaannya.
ALTER TABLE offers ADD CONSTRAINT offers_expiry_chk CHECK (expires_at > created_at);
```

- INDEX `offers_status_expires_idx` (`status`, `expires_at`) — untuk scheduler.

> ⚠️ Penawaran **tidak boleh** hidup lebih lama dari permintaannya. Saat
> membuat offer, ambil nilai terkecil antara 48 jam dan `expires_at` milik
> request:
> ```php
> 'expires_at' => min(now()->addHours(48), $customerRequest->expires_at),
> ```

Scheduler menutup penawaran kedaluwarsa berbarengan dengan permintaan:

```php
Offer::where('status', OfferStatus::Pending)
    ->where('expires_at', '<', now())
    ->update(['status' => OfferStatus::Rejected]);
```

> Catatan: ENUM `offers.status` tidak punya nilai `expired`. Penawaran lewat
> waktu ditandai `rejected` agar tidak menambah nilai ENUM baru. Bedakan di UI
> lewat `expires_at < now()` jika perlu menampilkan alasannya.

### 4.7 `orders`

| Kolom            | Tipe                                                                                                            | Keterangan                             |
| ---------------- | --------------------------------------------------------------------------------------------------------------- | -------------------------------------- |
| `id`             | CHAR(36)                                                                                                        | PK, UUID.                              |
| `order_number`   | VARCHAR(20) UNIQUE                                                                                              | Nomor referensi manusiawi, mis. `SKT-20260727-0001`. |
| `buyer_id`       | CHAR(36)                                                                                                        | FK ke `users` (pembeli).               |
| `store_id`       | CHAR(36)                                                                                                        | FK ke `stores` (penyedia).             |
| `offer_id`       | CHAR(36) NULL                                                                                                   | FK ke `offers` (jika dari penawaran).  |
| `listing_id`     | CHAR(36) NULL                                                                                                   | FK ke `listings` (jika langsung beli). |
| `order_type`     | ENUM('product','service','rental')                                                                              | Menentukan alur status. Nilainya **sama persis** dengan `listings.listing_type`. |
| `quantity`       | INT UNSIGNED DEFAULT 1                                                                                          | Jumlah unit. Selalu 1 untuk `service`. |
| `total_amount`   | DECIMAL(12,2)                                                                                                   | Total transaksi (sebelum potongan).    |
| `discount_amount`| DECIMAL(10,2) DEFAULT 0.00                                                                                      | Potongan dari kupon yang dipakai.      |
| `service_fee`    | DECIMAL(12,2) DEFAULT 0.00                                                                                      | Biaya layanan platform per transaksi (Fase 2, `config/seekitar.php`). |
| `coupon_id`      | CHAR(36) NULL                                                                                                   | FK ke `coupons` — kupon yang dipakai.  |
| `status`         | ENUM('menunggu_konfirmasi','diproses','dikirim','selesai','dibatalkan','dispute') DEFAULT 'menunggu_konfirmasi' |                                        |
| `payment_method` | ENUM('cod','transfer')                                                                                          |                                        |
| `delivery_method`| ENUM('pickup','delivery') DEFAULT 'pickup'                                                                      | Ambil di tempat atau diantar penjual.  |
| `shipping_address` | TEXT NULL                                                                                                     | Alamat tujuan. Wajib jika `delivery`.  |
| `shipping_location` | POINT SRID 4326 NULL                                                                                         | Koordinat tujuan untuk navigasi penjual. |
| `payment_proof_url` | VARCHAR(500) NULL                                                                                            | Bukti transfer dari pembeli.           |
| `payment_confirmed_at` | TIMESTAMP NULL                                                                                            | Kapan penjual mengonfirmasi dana masuk. |
| `notes`          | TEXT NULL                                                                                                       | Catatan pembeli saat memesan (PRD §8). |
| `completed_at`   | TIMESTAMP NULL                                                                                                  | Waktu transaksi dianggap selesai.      |
| `cancelled_at`   | TIMESTAMP NULL                                                                                                  | Kapan dibatalkan.                      |
| `cancelled_by`   | CHAR(36) NULL                                                                                                   | FK ke `users`. Siapa yang membatalkan. |
| `cancel_reason`  | VARCHAR(255) NULL                                                                                               | Alasan pembatalan, untuk audit.        |
| `created_at`     | TIMESTAMP                                                                                                       | –                                      |
| `updated_at`     | TIMESTAMP                                                                                                       | –                                      |

**Constraint & Indeks:**

- PRIMARY KEY (`id`)
- FOREIGN KEY (`buyer_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT — pembeli tidak bisa dihapus jika punya order.
- FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE RESTRICT — toko tidak bisa dihapus jika punya order.
- FOREIGN KEY (`offer_id`) REFERENCES `offers`(`id`) ON DELETE SET NULL — jika offer dihapus, order tetap ada, sumber jadi null.
- FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`) ON DELETE SET NULL
- FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`) ON DELETE SET NULL —
  kupon yang dihapus tidak menghapus pesanannya, potongan tetap tercatat di `discount_amount`.
- INDEX `orders_buyer_id_idx` (`buyer_id`)
- INDEX `orders_store_id_idx` (`store_id`)
- INDEX `orders_order_type_idx` (`order_type`)
- INDEX `orders_created_status_idx` (`created_at`, `status`)
- FOREIGN KEY (`cancelled_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
- UNIQUE KEY `orders_order_number_unique` (`order_number`)
- INDEX `orders_status_idx` (`status`)
- INDEX `orders_created_at_idx` (`created_at`) — untuk laporan tanggal.
- CHECK `orders_shipping_chk` — alamat wajib saat metode `delivery`:

```sql
ALTER TABLE orders
  ADD CONSTRAINT orders_shipping_chk
  CHECK (delivery_method = 'pickup' OR shipping_address IS NOT NULL);
```

**Catatan:** Status menggunakan ENUM agar tidak ada nilai tak terduga. Daftar status sudah mencakup seluruh alur (termasuk `dispute`).

> ### ⚠️ Perubahan: `order_type` `'goods'` → `'product'`
>
> Sebelumnya `order_type` memakai `ENUM('goods','service','rental')` sementara
> `listings.listing_type` memakai `ENUM('product','service','rental')`.
>
> Pesanan langsung **lahir dari listing** (`orders.listing_id`), jadi nilainya
> harus disalin. Dengan ejaan yang berbeda, penyalinan itu mustahil dilakukan
> apa adanya:
>
> ```php
> // Sebelum perbaikan — GAGAL, 'product' bukan nilai sah di order_type:
> $order->order_type = $listing->listing_type;   // 'product' -> ditolak ENUM
> ```
>
> Akibatnya kode terpaksa memetakan diam-diam (`'product' => 'goods'`), dan
> pemetaan itu **tidak terdokumentasi di dokumen mana pun**. Setiap laporan
> yang mengelompokkan pesanan per tipe akan salah bila pemetaannya terlewat di
> satu tempat saja.
>
> Sekarang keduanya identik, sehingga penyalinan menjadi langsung dan aman:
>
> ```php
> $order->order_type = $listing->listing_type;   // selalu valid
> ```
>
> `stores.store_type` **tetap** memakai bentuk jamak (`goods`, `services`) —
> lihat §4.2 untuk alasannya.

### Nomor Pesanan (`order_number`)

UUID aman untuk API tapi tidak mungkin dibacakan lewat telepon atau WhatsApp.
`order_number` adalah identitas yang dipakai manusia; UUID tetap menjadi PK.

Format: `SKT-YYYYMMDD-NNNN` (`SKT-20260727-0001`), dengan urutan direset harian.

> ⚠️ **Jangan** membuat nomor ini dengan `COUNT(*) + 1` — dua pesanan bersamaan
> akan menghasilkan nomor kembar meski ada UNIQUE (yang satu gagal simpan).
> Pakai penghitung atomik di Redis, dengan verifikasi UNIQUE sebagai jaring
> pengaman terakhir:

```php
$date = now()->format('Ymd');
$seq  = Redis::incr("order_seq:$date");
Redis::expire("order_seq:$date", 172800);   // bersihkan setelah 2 hari
$orderNumber = sprintf('SKT-%s-%04d', $date, $seq);
```

### Pembayaran Transfer

MVP memakai transfer langsung — dana tidak melewati platform. Alurnya:

1. Pembeli memilih `payment_method = 'transfer'`.
2. Aplikasi menampilkan rekening penjual (dari `stores`).
3. Pembeli mengunggah bukti → `payment_proof_url`.
4. Penjual memverifikasi → `payment_confirmed_at` terisi, status lanjut ke `diproses`.

> ⚠️ `payment_proof_url` adalah **klaim sepihak**, bukan bukti terverifikasi.
> Platform tidak memvalidasi mutasi bank, jadi jangan pernah meloloskan status
> otomatis hanya karena berkas terunggah. Konfirmasi penjual bersifat wajib —
> ini juga alasan `disputes` tetap diperlukan di MVP.

### Audit Pembatalan

`cancelled_by` memungkinkan membedakan pembatalan oleh pembeli, penjual, atau
admin — informasi yang hilang jika hanya mengandalkan `status = 'dibatalkan'`.
Ketiganya diisi bersamaan saat transisi ke `dibatalkan`, dan dipakai untuk
menghitung tingkat pembatalan per pihak (KPI di PRD §13).

### 4.8 `reviews`

| Kolom         | Tipe      | Keterangan                                                |
| ------------- | --------- | --------------------------------------------------------- |
| `id`          | CHAR(36)  | PK, UUID.                                                 |
| `order_id`    | CHAR(36)  | FK ke `orders`. Satu order punya maks 2 ulasan (dua arah).|
| `reviewer_id` | CHAR(36)  | FK ke `users`, yang menulis ulasan.                       |
| `reviewee_id` | CHAR(36)  | FK ke `users`, yang diulas (pemilik toko / pembeli).      |
| `store_id`    | CHAR(36) NULL | FK ke `stores`. Diisi **hanya** saat pembeli menilai toko. |
| `direction`   | ENUM('buyer_to_store','store_to_buyer') | Arah penilaian. Menentukan apakah ulasan memengaruhi rating toko. |
| `rating`      | TINYINT UNSIGNED | 1 – 5. Rentang ditegakkan di FormRequest/UI, bukan CHECK di DB. |
| `comment`     | TEXT NULL |                                                           |
| `created_at`  | TIMESTAMP |                                                           |

**Constraint & Indeks:**

- PRIMARY KEY (`id`)
- FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`reviewer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`reviewee_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE
- UNIQUE KEY `reviews_order_direction_unique` (`order_id`, `direction`) — satu ulasan per arah per pesanan.
- INDEX `reviews_store_id_idx` (`store_id`) — **untuk menghitung rating toko.**
- INDEX `reviews_reviewee_id_idx` (`reviewee_id`) — untuk reputasi pengguna.
- CHECK `reviews_store_direction_chk`:

```sql
ALTER TABLE reviews
  ADD CONSTRAINT reviews_store_direction_chk
  CHECK (
    (direction = 'buyer_to_store' AND store_id IS NOT NULL)
    OR
    (direction = 'store_to_buyer' AND store_id IS NULL)
  );
```

> ### ⚠️ Perubahan penting: `store_id` ditambahkan, `reviewee_id` **tetap ada**
>
> **Bug yang diperbaiki.** Rating toko sebelumnya dihitung dari `reviewee_id`
> yang menunjuk ke `users`. Padahal `DATABASE.md` §3 menyatakan
> `users 1──N stores` — **satu pengguna boleh punya banyak toko**. Akibatnya
> ulasan untuk Toko A ikut menaikkan rating Toko B milik orang yang sama.
> Query `$store->reviews()` bahkan tidak punya jalur relasi yang benar.
>
> **Kenapa `reviewee_id` tidak diganti (seperti usulan awal).** Mengganti
> `reviewee_id` menjadi `store_id` akan **mematahkan ulasan dua arah** yang
> diwajibkan `PRD.md` §5.5: *"pembeli dan penjual bisa saling menilai"*.
> Saat penjual menilai pembeli, tidak ada toko yang dinilai — kolom `store_id`
> akan kosong dan reputasi pembeli kehilangan tempat penyimpanan.
>
> **Solusi:** simpan keduanya, dibedakan oleh `direction`.
>
> | `direction` | `reviewee_id` | `store_id` | `stores.rating_avg` | `users.rating_avg` (pembeli) |
> | :-- | :-- | :-- | :-- | :-- |
> | `buyer_to_store` | pemilik toko | **terisi** | ✅ Ya | ❌ |
> | `store_to_buyer` | pembeli | NULL | ❌ | ✅ Ya |
>
> Keduanya dijaga `ReviewObserver`: ulasan baru maupun yang dihapus memicu
> COUNT/AVG ulang penuh ke target masing-masing — angka tidak digeser
> inkremental, jadi tidak akan pernah selisih dari sumbernya.
>
> `UNIQUE(order_id, direction)` menggantikan `UNIQUE(order_id)` — kalau tidak,
> hanya satu pihak yang bisa memberi ulasan dan fitur dua arah tetap mustahil.

**Perhitungan rating toko yang benar** — memakai `store_id`, bukan `reviewee_id`:

```php
// Hanya ulasan berarah buyer_to_store yang dihitung.
$stats = Review::where('store_id', $store->id)
    ->where('direction', 'buyer_to_store')
    ->selectRaw('AVG(rating) AS avg_rating, COUNT(*) AS total')
    ->first();

$store->update([
    'rating_avg'    => round($stats->avg_rating ?? 0, 2),
    'total_reviews' => $stats->total,
]);
```

### 4.9 `disputes`

| Kolom             | Tipe                                   | Keterangan                            |
| ----------------- | -------------------------------------- | ------------------------------------- |
| `id`              | CHAR(36)                               | PK, UUID.                             |
| `order_id`        | CHAR(36)                               | FK ke `orders`.                       |
| `reported_by`     | CHAR(36)                               | FK ke `users`, pelapor.               |
| `reason`          | ENUM('barang_tidak_sesuai','jasa_tidak_profesional','penyedia_tidak_responsif','pembeli_fiktif','lainnya') | Alasan baku. Lihat catatan. |
| `description`     | TEXT NULL                              | Penjelasan tambahan.                  |
| `status`          | ENUM('open','resolved') DEFAULT 'open' |                                       |
| `response_deadline` | TIMESTAMP NOT NULL                   | Batas SLA tanggapan admin (1×24 jam, PRD §5.5). |
| `first_responded_at` | TIMESTAMP NULL                      | Kapan admin pertama kali menanggapi. Dasar hitung kepatuhan SLA. |
| `escalated_at`    | TIMESTAMP NULL                         | Kapan dieskalasi karena melewati SLA. |
| `assigned_to`     | CHAR(36) NULL                          | FK ke `users` (admin penangan).       |
| `resolution_note` | TEXT NULL                              | Catatan dari admin.                   |
| `resolved_at`     | TIMESTAMP NULL                         | Kapan selesai.                        |
| `created_at`      | TIMESTAMP                              |                                       |
| `updated_at`      | TIMESTAMP                              |                                       |

**Constraint & Indeks:**

- PRIMARY KEY (`id`)
- FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`reported_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
- INDEX `disputes_order_id_idx` (`order_id`)
- INDEX `disputes_status_idx` (`status`)
- **Tidak ada CHECK `disputes_reason_desc_chk` di database** — aturan "alasan `lainnya` wajib disertai penjelasan" ditegakkan di FormRequest.

**Pelacakan SLA (PRD §5.5: tanggapan wajib 1×24 jam).** Tanpa kolom tenggat,
kewajiban itu hanya kalimat di dokumen — tidak ada cara mengetahui mana laporan
yang terlambat, apalagi mengukurnya sebagai KPI.

- `response_deadline` diisi otomatis saat dispute dibuat:
  `now() + settings.dispute_sla_hours` (lihat `Server_Implementation_Guide.md` §9.12).
- `first_responded_at` **berbeda** dari `resolved_at`: SLA mengukur seberapa
  cepat admin *merespons*, bukan seberapa cepat kasus selesai. Kasus rumit
  boleh lama, tetapi pelapor tidak boleh didiamkan.
- Scheduler menandai yang lewat tenggat:

```php
Dispute::where('status', DisputeStatus::Open)
    ->whereNull('first_responded_at')
    ->whereNull('escalated_at')
    ->where('response_deadline', '<', now())
    ->each(fn ($d) => $d->update(['escalated_at' => now()])
        && Notification::send($supervisors, new DisputeOverdue($d)));
```

- INDEX `disputes_sla_idx` (`status`, `response_deadline`) — dipakai scheduler
  di atas; tanpa indeks ini, query berjalan penuh di seluruh tabel.

**Kenapa `reason` diubah dari VARCHAR ke ENUM.** Kolom lama menyebut "dipilih
dari enum di aplikasi", tapi tanpa penegakan di database nilai apa pun bisa
masuk lewat SQL langsung, seeder, atau bug. Karena laporan ini dipakai untuk
statistik penyalahgunaan, satu salah ketik saja merusak agregasi.

Nilainya diambil persis dari `API_DOCUMENTATION.md` §9.1, yang merupakan
padanan resmi dari daftar berbahasa Indonesia di `PRD.md` §5.5:

| Nilai ENUM                  | Label di aplikasi (PRD §5.5)      |
| :-------------------------- | :--------------------------------- |
| `barang_tidak_sesuai`       | Barang tidak sesuai                |
| `jasa_tidak_profesional`    | Jasa tidak selesai / tidak profesional |
| `penyedia_tidak_responsif`  | Penyedia tidak responsif           |
| `pembeli_fiktif`            | Pembeli fiktif / tidak bayar       |
| `lainnya`                   | Lainnya (isi teks)                 |

> ⚠️ Menambah alasan baru berarti `ALTER TABLE`. Kalau daftar ini diperkirakan
> sering berubah, pindahkan ke tabel `dispute_reasons` dengan FK. Untuk MVP
> dengan 5 nilai yang stabil, ENUM lebih sederhana dan lebih cepat.

### 4.9a `user_devices`

Satu pengguna bisa memakai beberapa perangkat (ponsel lama + baru, atau ponsel
dan tablet). Menyimpan satu `fcm_token` di kolom `users` berarti hanya
perangkat terakhir yang menerima notifikasi.

```sql
CREATE TABLE user_devices (
  id           CHAR(36) PRIMARY KEY,
  user_id      CHAR(36) NOT NULL,
  device_id    VARCHAR(100) NOT NULL,       -- pengenal perangkat dari klien
  fcm_token    VARCHAR(255) NOT NULL,
  platform     VARCHAR(10) NOT NULL,          -- 'android' | 'ios'
  last_used_at TIMESTAMP NULL,
  created_at   TIMESTAMP,
  updated_at   TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY user_devices_device_unique (device_id),
  INDEX user_devices_user_idx (user_id)
);
```

> ⚠️ **`UNIQUE` pada `device_id` saja, bukan `(user_id, device_id)`.**
> Satu perangkat fisik hanya boleh terikat ke satu akun pada satu waktu. Kalau
> pengguna B masuk di ponsel bekas pengguna A, baris lama harus **ditimpa** —
> kalau tidak, notifikasi milik A tetap terkirim ke ponsel yang kini dipakai B.
>
> ```php
> UserDevice::updateOrCreate(
>     ['device_id' => $deviceId],                 // kunci pencarian
>     ['user_id' => $user->id, 'fcm_token' => $token, 'platform' => $platform,
>      'last_used_at' => now()]
> );
> ```
>
> Token yang ditolak Firebase (`NotFound`/`InvalidMessage`) **wajib dihapus**
> dari tabel ini — lihat `Server_Implementation_Guide.md` §15.1. Tanpa
> pembersihan, antrian terus mencoba mengirim ke perangkat yang aplikasinya
> sudah dihapus.

### 4.9c `favorites`

Wishlist pribadi pengguna — mendukung aksi "Masukkan ke Wishlist" (PRD §5.1)
dan tiga endpoint di [`API_DOCUMENTATION.md`](API_DOCUMENTATION.md) §4.4.

| Kolom        | Tipe     | Keterangan                        |
| ------------ | -------- | --------------------------------- |
| `id`         | CHAR(36) | PK, UUID.                         |
| `user_id`    | CHAR(36) | FK ke `users`. ON DELETE CASCADE. |
| `listing_id` | CHAR(36) | FK ke `listings`. ON DELETE CASCADE. |
| `created_at` | TIMESTAMP | Urutan tampil: terbaru dulu.     |
| `updated_at` | TIMESTAMP | –                                 |

**Constraint & Indeks:**

- UNIQUE KEY `favorites_user_listing_unique` (`user_id`, `listing_id`)
- INDEX `favorites_user_created_idx` (`user_id`, `created_at`)

> ⚠️ **UNIQUE-nya bukan sekadar kerapian.** API §4.4 menjanjikan `POST`
> bersifat **idempoten**: memfavoritkan listing yang sudah difavoritkan tetap
> mengembalikan `200`, bukan `409`. Tombol *toggle* di klien bisa mengirim
> ulang karena jaringan tidak stabil, dan constraint inilah yang memastikan
> pengiriman ulang tidak menghasilkan baris ganda.
>
> `CASCADE` di kedua FK disengaja: favorit tidak punya nilai sendiri. Saat
> listing dihapus, entri wishlist-nya ikut hilang — berbeda dari `orders`
> yang memakai `RESTRICT` karena merupakan bukti transaksi.

### 4.9b `settings`

Angka yang sering diubah operasional (radius maksimum, masa berlaku
permintaan, SLA) **tidak boleh** ditanam di kode — mengubahnya berarti deploy
ulang. Tabel ini membuatnya bisa disunting admin lewat `PUT /admin/settings`.

```sql
CREATE TABLE settings (
  key        VARCHAR(100) PRIMARY KEY,   -- mis. max_search_radius_km
  value      TEXT NULL,
  type       VARCHAR(20) NOT NULL DEFAULT 'string',  -- string|integer|boolean|json
  `group`    VARCHAR(50) NOT NULL DEFAULT 'general', -- pengelompokan di form admin
  label      VARCHAR(255) NOT NULL,      -- teks yang tampil ke admin
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

> Kunci baku beserta nilai default dan tempat pemakaiannya didaftar di
> [`Server_Implementation_Guide.md`](Server_Implementation_Guide.md) §9.12,
> bersama `SettingService` yang meng-cache-nya. `key` dipakai sebagai PK
> (bukan `id` auto-increment) karena akses selalu berdasarkan nama kunci.
>
> ⚠️ Perubahan pengaturan hanya berlaku untuk data **baru**. Menurunkan
> `request_expiry_hours` tidak memperpendek permintaan yang sudah berjalan —
> `expires_at` sudah dihitung saat baris dibuat.

### 4.9d `personal_access_tokens` & `sessions` (tabel kerangka Sanctum/session)

> **Status saat ini:** sejak versi 2.3 autentikasi API mobile memakai **JWT
> stateless** (`auth:api`, lihat `TECH_STACK.md` §5 dan
> `Server_Implementation_Guide.md` §6.1) — token JWT **tidak** disimpan di
> tabel ini. `personal_access_tokens` tetap ada untuk token Sanctum lama yang
> masih hidup selama masa transisi dan untuk kompatibilitas
> (`HasApiTokens` masih di model `User`).

Dua tabel bawaan Laravel **diubah dari skema default-nya** karena `users.id`
Seekitar adalah UUID, bukan BIGINT:

| Tabel | Kolom | Default Laravel | Milik Seekitar |
|---|---|---|---|
| `sessions` | `user_id` | `foreignId` (BIGINT) | `CHAR(36)` UUID |
| `personal_access_tokens` | `tokenable_id` | `morphs` (BIGINT) | `uuidMorphs` (CHAR 36) |

Tanpa penyesuaian ini, **login admin gagal total** (session driver
`database` menulis UUID ke kolom integer) dan **`createToken()` API gagal
total** dengan error integer 1366 yang tidak menunjuk sebab sebenarnya.

**Kolom lengkap `personal_access_tokens`** (identik skema paket Sanctum,
kecuali `tokenable_id`):

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT | Primary key bawaan Sanctum. |
| `tokenable_type` | VARCHAR(255) | Kelas model pemilik token (`uuidMorphs`). |
| `tokenable_id` | CHAR(36) | UUID `users.id` pemilik token. |
| `name` | TEXT | Nama token (mis. `mobile`). |
| `token` | VARCHAR(64) UNIQUE | SHA-256 dari teks token — teks aslinya tidak pernah disimpan. |
| `abilities` | TEXT | JSON ability (mis. `["*"]`). |
| `last_used_at` | TIMESTAMP NULL | Terakhir token dipakai — untuk audit sesi. |
| `expires_at` | TIMESTAMP NULL | Kedaluwarsa token Sanctum (tidak dipakai alur JWT). |
| `created_at` | TIMESTAMP | Standar. |
| `updated_at` | TIMESTAMP | Standar. |

Migrasi Sanctum bawaan **tidak perlu dinonaktifkan**: sejak Sanctum 4.x
paket itu tidak lagi memuat migrasinya sendiri, melainkan hanya
*menerbitkannya* lewat `vendor:publish --tag=sanctum-migrations`
(`Sanctum::ignoreMigrations()` sendiri dihapus di 4.x). Karena perintah
publish itu tidak pernah dijalankan, versi lokal
`2026_07_27_100050_create_personal_access_tokens_table.php` — kolom lain
identik dengan skema paket agar perilaku Sanctum tidak berubah — adalah
satu-satunya yang membentuk tabel ini.

> ⚠️ Jangan pernah "mengembalikan" dua kolom ini ke BIGINT — itu akan
> memutus login panel dan API sekaligus. Dan jangan jalankan
> `vendor:publish --tag=sanctum-migrations`: migrasi BIGINT yang terbit
> akan bertabrakan dengan tabel yang sudah ada.

### 4.9e `activity_logs`

Jejak audit aplikasi (aksi admin maupun pengguna) untuk investigasi dan
pemenuhan UU PDP. Morfisme `loggable_*` memungkinkan satu tabel mencatat
perubahan pada entitas apa pun (listing, toko, order, …).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | CHAR(36) | PK, UUID. |
| `user_id` | CHAR(36) NULL FK → `users.id` | Pelaku aksi. NULL jika aksi sistem. ON DELETE SET NULL. |
| `loggable_type` | VARCHAR(255) NULL | Kelas entitas yang diubah (morph). |
| `loggable_id` | VARCHAR(255) NULL | ID entitas yang diubah (morph). |
| `event` | VARCHAR(100) | Nama peristiwa, mis. `store.verified`, `user.blocked`. |
| `description` | TEXT NULL | Ringkasan manusiawi untuk log. |
| `old_values` | JSON NULL | Nilai sebelum perubahan. |
| `new_values` | JSON NULL | Nilai sesudah perubahan. |
| `ip_address` | VARCHAR(45) NULL | IP pelaku (audit & keamanan). |
| `user_agent` | VARCHAR(500) NULL | UA pelaku. |
| `created_at` | TIMESTAMP | Kapan aksi terjadi. |

**Indeks:** `(user_id)`, `(loggable_type, loggable_id)`, `(event)`, `(created_at)`.
Ditulis lewat `App\Services\ActivityLogger`.

### 4.9f `notifications`

Notifikasi dalam aplikasi (in-app) — terpisah dari FCM: baris ini adalah
sumber kebenaran daftar notifikasi yang dilihat pengguna; FCM hanyalah
pengantar.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | CHAR(36) | PK, UUID. |
| `user_id` | CHAR(36) FK → `users.id` | Penerima. ON DELETE CASCADE. |
| `type` | VARCHAR(100) | Kelas/peristiwa, mis. `offer.accepted`. |
| `title` | VARCHAR(255) | Judul singkat. |
| `body` | TEXT NULL | Isi lengkap. |
| `data` | JSON NULL | Payload tambahan (mis. `order_id` untuk navigasi). |
| `read_at` | TIMESTAMP NULL | Kapan dibaca; NULL = belum. |
| `created_at` | TIMESTAMP | – |
| `updated_at` | TIMESTAMP | – |

**Indeks:** `(user_id, read_at)` — daftar belum‑dibaca; `(user_id, created_at)` —
daftar kronologis.

### 4.9g `conversations`

Percakapan (chat) antara dua pihak, dikaitkan ke order bila ada. Peserta
disimpan di tabel pivot `conversation_participants`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | CHAR(36) | PK, UUID. |
| `order_id` | CHAR(36) NULL FK → `orders.id` | Order yang melatari percakapan. ON DELETE SET NULL. |
| `created_at` | TIMESTAMP | – |
| `updated_at` | TIMESTAMP | – |

### 4.9h `conversation_participants`

Pivot peserta percakapan — bisa lebih dari dua bila nanti admin ikut serta.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | CHAR(36) | PK, UUID. |
| `conversation_id` | CHAR(36) FK → `conversations.id` | ON DELETE CASCADE. |
| `user_id` | CHAR(36) FK → `users.id` | Peserta. ON DELETE CASCADE. |
| `last_read_at` | TIMESTAMP NULL | Penanda baca per peserta. |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | – |

**Constraint:** UNIQUE `(conversation_id, user_id)` — satu orang sekali dalam
satu percakapan.

### 4.9i `messages`

Pesan dalam percakapan. Hanya bisa ditambah (append-only) — tidak ada kolom
`updated_at`, konsisten dengan sifat chat yang tidak bisa diedit.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | CHAR(36) | PK, UUID. |
| `conversation_id` | CHAR(36) FK → `conversations.id` | ON DELETE CASCADE. |
| `sender_id` | CHAR(36) FK → `users.id` | Pengirim. |
| `message` | TEXT NULL | Isi teks (NULL jika hanya lampiran). |
| `message_type` | VARCHAR(20) DEFAULT 'text' | `text`, `image`, `system`, dsb. |
| `attachment_url` | VARCHAR(500) NULL | URL lampiran. |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | – |

**Indeks:** `(conversation_id, created_at)` — membaca riwayat secara
kronologis.

### 4.9j `user_addresses`

Buku alamat pengguna untuk pengiriman.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | CHAR(36) | PK, UUID. |
| `user_id` | CHAR(36) FK → `users.id` | Pemilik. ON DELETE CASCADE. |
| `label` | VARCHAR(50) | Nama alamat, mis. "Rumah". |
| `address` | TEXT | Alamat lengkap. |
| `latitude` | DECIMAL(8,2) NULL | Koordinat (presisi rendah, cukup untuk alamat). |
| `longitude` | DECIMAL(8,2) NULL | Koordinat. |
| `regency` | VARCHAR(100) NULL | Kabupaten/kota. |
| `regency_code` | VARCHAR(10) NULL | Kode BPS. |
| `recipient_name` | VARCHAR(100) NULL | Nama penerima (jika berbeda dari pemilik). |
| `recipient_phone` | VARCHAR(20) NULL | Telepon penerima. |
| `is_default` | TINYINT(1) DEFAULT 0 | Alamat bawaan. |
| `created_at` | TIMESTAMP | – |
| `updated_at` | TIMESTAMP | – |

**Indeks:** `(user_id)`.

### 4.9k `wallets`

Dompet saldo per pengguna. Satu pengguna tepat satu dompet (UNIQUE).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | CHAR(36) | PK, UUID. |
| `user_id` | CHAR(36) FK → `users.id` | Pemilik. UNIQUE, ON DELETE CASCADE. |
| `balance` | DECIMAL(12,2) DEFAULT 0.00 | Saldo saat ini. |
| `total_earned` | DECIMAL(12,2) DEFAULT 0.00 | Akumulasi pemasukan (audit). |
| `total_withdrawn` | DECIMAL(12,2) DEFAULT 0.00 | Akumulasi penarikan (audit). |
| `created_at` | TIMESTAMP | – |
| `updated_at` | TIMESTAMP | – |

> Mutasi saldo **tidak pernah** menimpa kolom langsung tanpa baris
> `wallet_transactions` — lihat §4.9l.

### 4.9l `wallet_transactions`

Riwayat mutasi dompet — append-only (tanpa `updated_at`), setiap perubahan
saldo tercatat dengan saldo sebelum/sesudah.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | CHAR(36) | PK, UUID. |
| `reference` | VARCHAR(64) NULL | Referensi pembayaran unik (mis. `TOPT-{uuid}`) untuk top up & penarikan. UNIQUE agar idempoten — mencegah kredit/penarikan ganda. |
| `wallet_id` | CHAR(36) FK → `wallets.id` | Dompet. ON DELETE CASCADE. |
| `type` | VARCHAR(50) | `topup`, `withdraw`, `refund`, `service_fee`, dsb. |
| `amount` | DECIMAL(12,2) | Nominal mutasi. |
| `balance_before` | DECIMAL(12,2) | Saldo sebelum mutasi. |
| `balance_after` | DECIMAL(12,2) | Saldo sesudah mutasi. |
| `description` | VARCHAR(255) NULL | Keterangan. |
| `reference_type` | VARCHAR(255) NULL | Entitas terkait (morph), mis. order. |
| `reference_id` | VARCHAR(255) NULL | ID entitas terkait. |
| `status` | VARCHAR(50) DEFAULT 'completed' | `pending`, `completed`, `failed`. |
| `created_at` | TIMESTAMP | – |

**Indeks:** `(wallet_id, created_at)`, `UNIQUE (reference)`.

> Catatan: model `WalletTransaction` punya kolom `reference` sekaligus relasi morph `reference()` (`reference_type`/`reference_id`) — dua nama yang sama dalam satu model.

### 4.9m `coupons`

Kupon diskon. Nilai potongan dihitung dari `type`/`value`, dibatasi
`min_order_amount` & `max_discount`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | CHAR(36) | PK, UUID. |
| `code` | VARCHAR(50) | Kode kupon. UNIQUE. |
| `type` | VARCHAR(50) | `percent` atau `fixed`. |
| `value` | DECIMAL(10,2) | Besaran diskon (persen atau nominal). |
| `min_order_amount` | DECIMAL(10,2) NULL | Minimum total order agar berlaku. |
| `max_discount` | DECIMAL(10,2) NULL | Batas maksimum potongan (kupon persen). |
| `usage_limit` | INT UNSIGNED NULL | Batas pemakaian total; NULL = tak terbatas. |
| `usage_per_user` | INT UNSIGNED DEFAULT 1 | Batas pemakaian per pengguna. |
| `used_count` | INT UNSIGNED DEFAULT 0 | Pemakaian terkini (counter). |
| `is_active` | TINYINT(1) DEFAULT 1 | Aktif/nonaktif manual. |
| `starts_at` | TIMESTAMP NULL | Masa berlaku mulai. |
| `expires_at` | TIMESTAMP NULL | Masa berlaku selesai. |
| `description` | VARCHAR(255) NULL | Keterangan tampil ke pengguna. |
| `created_at` | TIMESTAMP | – |
| `updated_at` | TIMESTAMP | – |

**Indeks:** `(code)`, `(is_active, expires_at)` — pencarian kupon yang masih
berlaku.

### 4.9n `coupon_usages`

Jejak pemakaian kupon per order — dasar penghitungan `usage_limit` dan
`usage_per_user`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | CHAR(36) | PK, UUID. |
| `coupon_id` | CHAR(36) FK → `coupons.id` | Kupon. ON DELETE CASCADE. |
| `order_id` | CHAR(36) FK → `orders.id` | Order. ON DELETE CASCADE. |
| `user_id` | CHAR(36) FK → `users.id` | Pemakai. ON DELETE CASCADE. |
| `discount_amount` | DECIMAL(10,2) | Potongan aktual yang diterapkan (snapshot). |
| `created_at` | TIMESTAMP | – |

**Indeks:** `(coupon_id)`, `(user_id)`, `(order_id)`.

### 4.9o `consent_logs`

Jejak persetujuan pengguna (UU PDP) — kapan, untuk tujuan apa, dan dari mana.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | CHAR(36) | PK, UUID. |
| `user_id` | CHAR(36) FK → `users.id` | Subjek data. ON DELETE CASCADE. |
| `purpose` | VARCHAR(100) | Tujuan pemrosesan, mis. `privacy_policy`, `location`. |
| `granted` | TINYINT(1) | 1 = menyetujui, 0 = menolak. |
| `ip_address` | VARCHAR(45) NULL | Asal permintaan. |
| `user_agent` | VARCHAR(500) NULL | Peramban/perangkat. |
| `created_at` | TIMESTAMP NOT NULL | Kapan persetujuan diberikan/ditarik. |

**Indeks:** `(user_id)`.

### 4.9p `reports`

Pelaporan konten/pengguna (pelanggaran pedoman komunitas) dari pengguna ke
admin.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | CHAR(36) | PK, UUID. |
| `reporter_id` | CHAR(36) FK → `users.id` | Pelapor. ON DELETE CASCADE. |
| `reportable_type` | VARCHAR(255) | Entitas yang dilaporkan (morph): listing, user, dsb. |
| `reportable_id` | CHAR(36) | ID entitas. |
| `reason` | VARCHAR(50) | Alasan (daftar tetap di aplikasi). |
| `description` | TEXT NULL | Penjelasan pelapor. |
| `resolved_at` | TIMESTAMP NULL | Kapan admin menuntaskan. |
| `resolved_by` | CHAR(36) NULL | Admin penuntas (tanpa FK — admin bisa terhapus). |
| `created_at` | TIMESTAMP | – |
| `updated_at` | TIMESTAMP | – |

**Indeks:** `(reportable_type, reportable_id)`, `(resolved_at)`.

### 4.9q `blog_posts`

Artikel blog untuk halaman web publik (SEO) — dikelola dari panel admin.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT | PK. |
| `title` | VARCHAR(200) | Judul. |
| `slug` | VARCHAR(220) | Slug URL. UNIQUE. |
| `excerpt` | TEXT NULL | Ringkasan untuk daftar blog. |
| `body` | LONGTEXT | Isi artikel. |
| `author` | VARCHAR(100) DEFAULT 'Tim Seekitar' | Penulis. |
| `category` | VARCHAR(100) DEFAULT 'Edukasi' | Kategori artikel. |
| `image` | VARCHAR(500) NULL | Gambar sampul. |
| `image_alt` | VARCHAR(200) NULL | Teks alternatif gambar. |
| `published_at` | TIMESTAMP NULL | Jadwal terbit; NULL = draf. |
| `created_at` | TIMESTAMP | – |
| `updated_at` | TIMESTAMP | – |

### 4.9r `contact_messages`

Pesan dari formulir kontak halaman web publik, masuk ke antrean admin.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT | PK. |
| `name` | VARCHAR(120) | Nama pengirim. |
| `email` | VARCHAR(190) | Email pengirim. |
| `category` | VARCHAR(50) | Jenis pesan (umum, pengaduan, dsb.). |
| `message` | TEXT | Isi pesan. |
| `status` | VARCHAR(20) DEFAULT 'new' | `new`, `in_progress`, `resolved`. |
| `created_at` | TIMESTAMP | – |
| `updated_at` | TIMESTAMP | – |

### 4.9s `advertisements`

Iklan banner lokal (Fase 2) — posisi di feed, harga per hari, dan statistik
tayang/klik.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | CHAR(36) | PK, UUID. |
| `title` | VARCHAR(200) | Judul iklan. |
| `description` | TEXT NULL | Deskripsi. |
| `image_url` | VARCHAR(500) NULL | Gambar banner. |
| `link_url` | VARCHAR(500) NULL | Tujuan klik. |
| `position` | VARCHAR(50) DEFAULT 'feed' | Posisi penempatan. |
| `price_per_day` | DECIMAL(12,2) DEFAULT 50000.00 | Harga sewa per hari. |
| `buyer_id` | CHAR(36) NULL FK → `users.id` | Pemesan iklan. ON DELETE SET NULL. |
| `status` | VARCHAR(20) DEFAULT 'available' | `available`, `booked`, `running`, `finished`. |
| `starts_at` | TIMESTAMP NULL | Tayang mulai. |
| `ends_at` | TIMESTAMP NULL | Tayang selesai. |
| `impression_count` | INT UNSIGNED DEFAULT 0 | Jumlah tayang. |
| `click_count` | INT UNSIGNED DEFAULT 0 | Jumlah klik. |
| `created_at` | TIMESTAMP | – |
| `updated_at` | TIMESTAMP | – |

**Indeks:** `(status, position)` — iklan aktif per posisi; `(buyer_id, status)`.

### 4.10 `service_slots` (Fase 2)

PRD §5.1.3 menyebut pemesanan jasa memilih **slot waktu**, dan §5.1.1 menyebut
`listings.slot`. Keduanya hal berbeda dan sering tertukar:

| | `listings.slot` | `service_slots` |
| :-- | :-- | :-- |
| Arti | **Kapasitas per hari** (mis. 3 order/hari) | Jadwal konkret (mis. Senin 09:00) |
| Tipe | TINYINT UNSIGNED | tabel tersendiri |
| Status | ✅ ada di MVP | ⏳ Fase 2 |

**MVP:** `listings.slot` hanya membatasi berapa pesanan jasa yang diterima per
hari. Waktu spesifik dinegosiasikan lewat WhatsApp (PRD §5.6) dan dicatat pada
`offers.estimation_time` — konsisten dengan MVP yang belum punya chat in-app.

**Fase 2**, saat penjadwalan benar-benar dibutuhkan:

```sql
CREATE TABLE service_slots (
  id          CHAR(36) PRIMARY KEY,
  listing_id  CHAR(36) NOT NULL,
  start_at    TIMESTAMP NOT NULL,
  end_at      TIMESTAMP NOT NULL,
  order_id    CHAR(36) NULL,             -- terisi saat slot dipesan
  status      ENUM('available','booked','blocked') DEFAULT 'available',
  created_at  TIMESTAMP,
  updated_at  TIMESTAMP,
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE SET NULL,
  UNIQUE KEY service_slots_unique (listing_id, start_at),
  INDEX service_slots_lookup_idx (listing_id, status, start_at),
  CONSTRAINT service_slots_time_chk CHECK (end_at > start_at)
);
```

> ⚠️ `UNIQUE(listing_id, start_at)` mencegah dua pesanan pada slot yang sama.
> Pemesanan slot **wajib** `lockForUpdate()` seperti penerimaan penawaran
> (§4.5) — dua pembeli yang menekan tombol nyaris bersamaan bisa memesan slot
> yang sama.

### 4.11 `subscriptions`

Langganan berbayar toko (Fase 2 monetisasi): paket "Pro Monthly" dan "Boost
Listing". Tabel sudah dimigrasikan; harganya diambil dari
`config/seekitar.php` (`pro_monthly_price` = 30.000, `boost_listing_price` =
7.500) bukan dari dokumen.

```sql
CREATE TABLE subscriptions (
  id          CHAR(36) PRIMARY KEY,
  user_id     CHAR(36) NOT NULL,
  store_id    CHAR(36) NULL,             -- NULL = paket tingkat akun
  plan        ENUM('pro_monthly','boost_listing') NOT NULL DEFAULT 'pro_monthly',
  status      ENUM('pending','active','expired','cancelled') DEFAULT 'pending',
  amount      DECIMAL(12,2) NOT NULL,
  starts_at   TIMESTAMP NOT NULL,
  ends_at     TIMESTAMP NOT NULL,
  payment_ref VARCHAR(100) NULL,         -- referensi dari payment gateway
  notes       TEXT NULL,
  created_at  TIMESTAMP,
  updated_at  TIMESTAMP,
  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
  INDEX subscriptions_active_idx (status, ends_at),
  INDEX subscriptions_store_idx (store_id, status),
  INDEX subscriptions_user_active_idx (user_id, status, ends_at),
  CONSTRAINT subscriptions_period_chk CHECK (ends_at > starts_at)
);
```

**Catatan desain:**

- Satu tabel untuk kedua produk (`plan`), bukan dua tabel — keduanya sama-sama
  "hak berbatas waktu" dan hanya berbeda durasi serta cakupan.
- `boost_listing` memakai `store_id`; `pro_monthly` bisa `NULL` karena berlaku
  untuk seluruh toko milik pengguna.
- **Jangan** menyimpan status Pro sebagai boolean di `stores`. Boolean tidak
  punya masa berlaku, sehingga tidak bisa kedaluwarsa otomatis. Status aktif
  selalu diturunkan dari `status = 'active' AND ends_at > NOW()`.
- Prioritas broadcast untuk Pro bersifat **penambah bobot, bukan mutlak**
  (PRD §12) — jangan sampai penyedia berbayar menggeser penyedia terdekat yang
  jelas lebih relevan.

---

## 5. STRATEGI FOREIGN KEY & CASCADING

Keputusan `ON DELETE` dibuat berdasarkan dampak bisnis:

| Relasi                                  | ON DELETE | Alasan                                                                                          |
| --------------------------------------- | --------- | ----------------------------------------------------------------------------------------------- |
| `stores.user_id`                        | CASCADE   | Toko tidak berarti tanpa pemilik. Saat user dihapus (soft delete), toko ikut disembunyikan.     |
| `listings.store_id`                     | CASCADE   | Listing tidak bisa ada tanpa toko.                                                              |
| `offers.store_id`                       | CASCADE   | Penawaran dari toko yang sudah tidak ada tidak berguna.                                         |
| `orders.buyer_id` / `orders.store_id`   | RESTRICT  | Order adalah catatan transaksi. User/toko tidak bisa dihapus sebelum menyelesaikan semua order. |
| `customer_requests.category_id`         | RESTRICT  | Hindari penghapusan kategori yang masih digunakan permintaan aktif.                             |
| `offers.request_id`                     | CASCADE   | Jika permintaan dihapus, semua penawaran ikut hilang.                                           |
| `orders.offer_id` / `orders.listing_id` | SET NULL  | Order tetap ada meskipun penawaran/listing sumber dihapus.                                      |
| `reviews.order_id`                      | CASCADE   | Ulasan hanya ada jika order ada.                                                                |

---

## 6. SOFT DELETE: IMPLEMENTASI & CLEANUP

`users`, `stores`, `listings` menggunakan soft delete.  
**Yang kami lakukan di Laravel:**

1. Tambahkan `use SoftDeletes;` di model.
2. Daftarkan observer untuk membersihkan data terkait saat soft delete (contoh: saat store di‑soft delete, listing ikut soft delete, offers dihapus permanen).

```php
// StoreObserver@deleted
public function deleted(Store $store): void {
    $store->listings()->delete(); // soft delete listings
    $store->offers()->delete();   // hapus permanen offers
}
```

3. Admin dapat merestore dengan `restore()`, yang akan memulihkan semua listing terkait.

---

## 7. INDEKS KOMPREHENSIF & QUERY PATTERNS

Seluruh indeks dirancang berdasarkan pola query nyata.

| Tabel               | Indeks                                       | Tujuan                                                                        |
| ------------------- | -------------------------------------------- | ----------------------------------------------------------------------------- |
| `users`             | `users_phone_unique`                         | Login dengan nomor HP.                                                        |
| `stores`            | `stores_latlng_idx` (`latitude`, `longitude`) | Pencarian toko dalam radius — `whereBetween` kotak pembatas (§11).            |
| `stores`            | `stores_is_active_idx`                       | Hanya tampilkan toko aktif.                                                   |
| `listings`          | `listings_fulltext`                          | Pencarian teks produk/jasa.                                                   |
| `customer_requests` | `cr_status_expires_idx`                      | Job menutup permintaan expired: `WHERE status='open' AND expires_at < NOW()`. |
| `orders`            | `orders_store_id_idx`, `orders_buyer_id_idx` | Riwayat pesanan per toko/pembeli.                                             |
| `reviews`           | `reviews_store_id_idx`                       | Rata‑rata rating toko: `WHERE store_id = ? AND direction = 'buyer_to_store'`. |

### 7.1 Indeks Komposit untuk Pola Query Nyata

Indeks kolom-tunggal tidak cukup untuk query yang menyaring dua kolom
sekaligus. MySQL hanya memakai **satu** indeks per tabel per query (kecuali
*index merge* yang sering lebih lambat), jadi kombinasi yang sering dipakai
bersamaan perlu indeks komposit sendiri.

```sql
-- Broadcast: cari permintaan terbuka pada kategori tertentu
ALTER TABLE customer_requests
  ADD INDEX cr_category_status_idx (category_id, status);

-- Pembeli melihat penawaran masuk untuk permintaannya
ALTER TABLE offers
  ADD INDEX offers_request_status_idx (request_id, status);

-- Riwayat pesanan: "Pesanan Saya" difilter status, diurut terbaru
ALTER TABLE orders
  ADD INDEX orders_buyer_status_idx (buyer_id, status, created_at DESC),
  ADD INDEX orders_store_status_idx (store_id, status, created_at DESC);

-- Ulasan: hanya arah buyer_to_store yang menghitung rating toko
ALTER TABLE reviews
  ADD INDEX reviews_store_direction_idx (store_id, direction);
```

#### Aturan urutan kolom (kenapa `(status, expires_at)` sudah benar)

Poin #219 mempertanyakan urutan `cr_status_expires_idx`. Urutannya **sudah
optimal**, tetapi alasannya bukan soal kardinalitas — melainkan **jenis
perbandingan**:

```sql
WHERE status = 'open'          -- kesamaan (=)
  AND expires_at < NOW()       -- rentang (<)
```

| Aturan | Penjelasan |
| :-- | :-- |
| Kolom **kesamaan** dulu | `status = 'open'` mempersempit ke satu blok berurutan |
| Kolom **rentang** terakhir | Setelah rentang, kolom berikutnya tidak lagi terurut |

Jika dibalik menjadi `(expires_at, status)`, MySQL hanya bisa memakai bagian
`expires_at`; `status` tidak lagi dapat menyaring lewat indeks dan harus
diperiksa baris per baris.

> ⚠️ **Kardinalitas rendah di depan bukan aturan umum** — itu hanya kebetulan
> tepat di sini. Patokan sesungguhnya: **kesamaan sebelum rentang**, lalu kolom
> `ORDER BY` di posisi terakhir. Itulah alasan `orders_buyer_status_idx`
> disusun `(buyer_id, status, created_at)`: dua kesamaan, lalu pengurutan.

#### Verifikasi dengan `EXPLAIN`

Setiap indeks di atas harus dibuktikan terpakai, bukan diasumsikan:

```sql
EXPLAIN SELECT * FROM customer_requests
WHERE status = 'open' AND expires_at < NOW();
```

| Kolom `EXPLAIN` | Nilai yang diharapkan | Tanda bahaya |
| :-- | :-- | :-- |
| `type` | `range` / `ref` | `ALL` = pemindaian tabel penuh |
| `key` | `cr_status_expires_idx` | `NULL` = indeks tidak dipakai |
| `rows` | jauh lebih kecil dari total | mendekati total baris |
| `Extra` | `Using index condition` | `Using filesort` pada tabel besar |

> Jalankan `ANALYZE TABLE` setelah impor data besar. Statistik yang basi bisa
> membuat MySQL memilih indeks yang salah meski indeksnya sudah benar.

**Tips:** Hindari indeks berlebihan pada tabel yang sering ditulis (`offers`, `customer_requests`) — setiap indeks memperlambat `INSERT`/`UPDATE`. Evaluasi dengan `EXPLAIN` secara berkala.

### 7.2 Catatan Fulltext: Jangan Pakai Parser `ngram`

> ⚠️ **Rekomendasi memakai `WITH PARSER ngram` untuk Bahasa Indonesia keliru
> dan akan memperburuk hasil pencarian.**
>
> Dokumentasi MySQL menyatakan parser `ngram` disediakan untuk **CJK**
> (Mandarin, Jepang, Korea) — bahasa **tanpa spasi antar kata**. Bahasa
> Indonesia memakai spasi, sehingga parser bawaan sudah bekerja dengan benar.

Yang terjadi bila `ngram` dipaksakan (dengan `ngram_token_size=2`):

| Aspek | Parser bawaan | Parser `ngram` |
| :-- | :-- | :-- |
| "servis AC" dipecah jadi | `servis`, `AC` | `se`,`er`,`rv`,`vi`,`is`,`AC` |
| Ukuran indeks | wajar | membengkak berkali-kali lipat |
| Cari "beras" | cocok tepat | juga cocok "**beras**an", "kum**bera**s" |
| `innodb_ft_min_token_size` | berlaku | **diabaikan** |

Karena itu definisi indeks **dipertahankan apa adanya**:

```sql
FULLTEXT INDEX listings_fulltext (title, description)
```

**Yang justru perlu disetel** adalah panjang token minimum. Bawaan InnoDB
adalah 3 karakter, sehingga kata pendek yang umum di sini tidak terindeks:

```ini
[mysqld]
innodb_ft_min_token_size=2      # agar "AC", "TV", "HP" bisa dicari
```

> ⚠️ Mengubah nilai ini **wajib** diikuti pembangunan ulang indeks, kalau tidak
> perubahannya tidak berlaku pada data lama:
> ```sql
> ALTER TABLE listings DROP INDEX listings_fulltext;
> ALTER TABLE listings ADD FULLTEXT INDEX listings_fulltext (title, description);
> ```
>
> **Stopword bawaan MySQL berbahasa Inggris.** Kata seperti "yang", "untuk",
> "dan" tetap terindeks dan menurunkan relevansi. Buat daftar stopword sendiri
> bila kualitas pencarian mulai terasa mengganggu:
> ```ini
> innodb_ft_server_stopword_table=seekitar/stopwords_id
> ```

### 7.3 Indeks Spasial

Kolom `POINT SRID 4326` di skema ini: `users.location` (NULL),
`customer_requests.location` (NOT NULL), `orders.shipping_location` (NULL).
Hanya **satu** yang punya indeks spasial — `cr_location_spatial` di
`customer_requests.location` (§4.5), karena itulah satu-satunya kolom POINT
yang disaring radius secara rutin (pencocokan penyedia untuk permintaan).
Dua syarat mutlak agar indeksnya sah:

```sql
-- 1. Kolom WAJIB NOT NULL — MySQL menolak SPATIAL INDEX pada kolom NULL-able
-- 2. SRID WAJIB ditetapkan pada kolom, bukan hanya pada nilainya
ALTER TABLE customer_requests MODIFY location POINT NOT NULL SRID 4326;
ALTER TABLE customer_requests ADD SPATIAL INDEX cr_location_spatial (location);
```

Verifikasi bahwa SRID benar-benar melekat pada kolom:

```sql
SELECT COLUMN_NAME, SRS_ID FROM INFORMATION_SCHEMA.ST_GEOMETRY_COLUMNS
WHERE TABLE_NAME = 'customer_requests';
-- SRS_ID harus 4326, BUKAN NULL
```

> ⚠️ Tanpa atribut `SRID 4326` pada definisi kolom, MySQL memperlakukan kolom
> sebagai SRID tak tentu — indeks spasial **tidak akan dipakai** oleh
> pengoptimal, meski indeksnya ada. Inilah alasan `users.location` dan
> `orders.shipping_location` tidak punya indeks spasial (kolomnya NULL-able,
> lihat §4.1 dan §4.7). Pencarian radius **toko** memakai pasangan
> `stores.latitude`/`longitude` DECIMAL berindeks, bukan kolom POINT (§4.2,
> §11).

---

## 8. DATA INTEGRITY GUARD (ANTI HUMAN‑ERROR)

1. **UUID sebagai ID** – Tidak mungkin ada tabrakan, tidak bisa di‑tebak (IDOR prevention).
2. **Foreign Key Constraints** – Tidak akan ada order tanpa pembeli/penjual.
3. **UNIQUE constraint** pada offers (`request_id`, `store_id`, `status`) – mencegah toko mengirim penawaran AKTIF ganda pada permintaan yang sama, baik dari aplikasi maupun langsung dari SQL; beberapa penawaran `rejected` diperbolehkan.
4. **ENUM + CHECK** – Status pesanan, tipe listing, semua memiliki domain terbatas yang terverifikasi di level engine.
5. **Default value** – `rating_avg` = 0.00, `total_reviews` = 0, `users.status` = 'menunggu', `is_active` = 1, dll. Jejak verifikasi TIDAK punya kolom ber-default: stempel `verified_*`/`rejected_*`/`blocked_*` memang NULL sejak lahir dan hanya terisi oleh keputusan admin (§4.1).
6. **Aplikasi wajib gunakan transaksi** – setiap aksi multi‑tabel (contoh: menerima penawaran → update request, update offer, insert order) HARUS dalam `DB::transaction()` **dengan `lockForUpdate()`** pada baris yang jadi rebutan.
7. **Validasi data JSON** – Di Laravel, gunakan `$casts` dan Form Request untuk memastikan `category_ids` adalah array integer, `images` adalah array URL, dll.
8. **Mekanisme update rating toko** – `rating_avg` dihitung dari `reviews.store_id` (bukan `reviewee_id`) dan hanya arah `buyer_to_store`, diperbarui dalam transaksi bersama penulisan ulasan.
9. **CHECK constraint lintas kolom** – aturan yang tidak bisa diwakili ENUM ditegakkan engine (7 constraint, butuh **MySQL 8.0.16+**):
    - `stores_fulfilment_chk` — toko wajib melayani minimal satu cara penyerahan (`offers_delivery` atau `allows_pickup`).
    - `listings_price_required_chk` — harga wajib kecuali tipe `service`.
    - `listings_qty_slot_chk` — `product`/`rental` memakai `stock_qty` (bukan `slot`); `service` memakai `slot` (bukan `stock_qty`).
    - `offers_expiry_chk` — `expires_at > created_at`.
    - `orders_shipping_chk` — alamat wajib saat `delivery_method = 'delivery'`.
    - `reviews_store_direction_chk` — arah `buyer_to_store` wajib ber-`store_id`; `store_to_buyer` tidak boleh.
    - `subscriptions_period_chk` — `ends_at > starts_at`.
    Aturan yang **tidak** punya CHECK engine (mis. `budget_max ≥ budget_min`, dispute `lainnya` wajib berdeskripsi) dijaga di Form Request — lihat butir 10.
10. **Batas yang hanya bisa dijaga aplikasi** – panjang array JSON (`images` maks 5 untuk listing, maks 3 untuk request) dan keberadaan ID di `category_ids`. Tidak ada FK/CHECK untuk ini, jadi Form Request adalah satu-satunya penjaga.

---

## 8A. KEPUTUSAN DESAIN: USULAN YANG DITOLAK & DIKOREKSI

Beberapa usulan perubahan skema sengaja **tidak** diterapkan setelah
diverifikasi ke PRD dan skema yang ada. Dicatat di sini agar tidak diusulkan
ulang tanpa konteks.

| Usulan                                                   | Keputusan       | Alasan                                                                                                       |
| :------------------------------------------------------- | :-------------- | :------------------------------------------------------------------------------------------------------------ |
| `store_type`: `SET` → `JSON` / pivot                     | ❌ Ditolak      | Premisnya keliru — SET memang mendukung kombinasi. Untuk 3 nilai tetap, SET lebih hemat & divalidasi engine. |
| `reviewee_id` → diganti `store_id`                       | ⚠️ Dikoreksi    | Mengganti akan mematahkan ulasan dua arah (PRD §5.5). `store_id` **ditambahkan**, `reviewee_id` tetap.       |
| `users.location` → `NOT NULL`                            | ❌ Ditolak      | Baris user harus ada sebelum lokasi diisi (alur OTP). Ditegakkan middleware, bukan constraint.               |
| `service_radius_km` default → 15 km                      | ❌ Ditolak      | Tertukar dengan `customer_requests.radius_km`. Default toko 5 km sudah sesuai PRD §5.3.2.                    |
| `estimation_time` → diganti kolom numerik                | ⚠️ Dikoreksi    | Teks bernuansa tetap berguna bagi pembeli. `estimated_hours` **ditambahkan** berdampingan.                    |
| `rating_count` sebagai alias `total_reviews`             | ❌ Ditolak      | Dua kolom untuk satu makna justru sumber inkonsistensi. `total_reviews` sudah cukup.                          |
| `verification_level` tambah level 4                      | ❌ Ditolak      | PRD §5.3.2 hanya mendefinisikan Level 1–3 untuk MVP. Tidak ada level 4 di dokumen mana pun.                  |

**Catatan #36 (`rating_avg` vs `total_reviews`).** Tidak ada masalah tipe di
sini: `DECIMAL(3,2)` memuat 0.00–9.99 (cukup untuk maks 5.00) dan
`INT UNSIGNED` tepat untuk pencacah. Menambah `rating_count` sebagai alias
hanya menciptakan dua sumber kebenaran yang bisa berbeda.

**Catatan #21 (`verification_level`).** PRD, DATABASE, dan API sudah konsisten
di rentang 1–3. Yang ditambahkan hanyalah penegasan eksplisit "Hanya 1–3" di
deskripsi kolom, agar tidak ada yang mengarang level 4.

---

## 9. KEAMANAN & PENCEGAHAN SQL INJECTION

- **Laravel Eloquent / Query Builder** melindungi dari SQL injection. Untuk raw query (geospasial), gunakan binding parameter:

```php
->whereRaw("ST_Distance_Sphere(location, ST_GeomFromText(?, 4326, 'axis-order=long-lat')) <= ?", [$point, $radius])
```

- **User input tidak pernah langsung masuk raw query**.
- **Rate limiting** mencegah brute force dan pengurasan database.

---

## 10. MIGRASI, SEEDER & DEPLOYMENT DI LARAVEL 13

Urutan migrasi sesuai dependensi:

1. `users`
2. `categories`
3. `stores`
4. `listings`
5. `customer_requests`
6. `offers`
7. `orders`
8. `reviews`
9. `disputes`

**Seeder `Categories`** harus dijalankan saat deployment awal, minimal 24 kategori (makanan, jasa, dll). Jangan lupa menambahkan `parent_id` untuk subkategori.

**Contoh migrasi POINT (raw):**

```php
DB::statement("ALTER TABLE customer_requests ADD COLUMN location POINT NOT NULL SRID 4326");
DB::statement("CREATE SPATIAL INDEX cr_location_spatial ON customer_requests(location)");
```

**UUID di Laravel 13** – Gunakan `Str::orderedUuid()` saat creating model, atau trait `HasUuids` jika diinginkan.

---

## 11. LAMPIRAN: RAW QUERY & PERFORMANCE TIPS

### Pencarian Radius Toko

Lokasi toko adalah **kolom DECIMAL berindeks** (`latitude`/`longitude`), jadi
pencarian radius berjalan **dua tahap, keduanya tanpa SQL mentah**:

1. **SQL:** kotak pembatas lewat `whereBetween` berindeks (Eloquent murni) —
   `Store::scopeWithinBox()` memakai `Jarak::kotak()` untuk menghitung batas.
2. **PHP:** lingkaran akurat lewat `Jarak::haversineKm()` — membuang
   sudut-sudut kotak yang sebenarnya di luar radius.

```php
// App\Support\Jarak — satu-satunya tempat rumus jarak ditulis.
[$latMin, $latMax, $lngMin, $lngMax] = Jarak::kotak($lat, $lng, $radiusKm);

$stores = Store::query()
    // TAHAP 1 — kotak pembatas berindeks (stores_latlng_idx), Eloquent murni
    ->whereBetween('latitude',  [$latMin, $latMax])
    ->whereBetween('longitude', [$lngMin, $lngMax])
    ->where('is_active', 1)
    ->get();

// TAHAP 2 — haversine di PHP untuk kandidat yang lolos kotak.
$stores = $stores->filter(
    fn (Store $s) => Jarak::haversineKm($lat, $lng, $s->latitude, $s->longitude) <= $radiusKm
);
```

**Kenapa bukan `ST_Distance_Sphere` atas kolom POINT:** pada skala satu
kabupaten, `whereBetween` atas indeks DECIMAL menyaring kandidat dalam
milidetik tanpa deserialisasi geometri per baris maupun konversi WKT; indeks
DECIMAL biasa juga lebih ringan daripada SPATIAL INDEX untuk rentang kecil.
Inilah alasan skema 2.3 memensiunkan kolom POINT toko (lihat §4.2).

> ⚠️ **`ST_Distance_Sphere` di klausa `WHERE` TIDAK memakai indeks spasial.**
> Fungsi ini menghitung jarak untuk **setiap baris**. Untuk kolom POINT yang
> tetap ada (`customer_requests.location`), pola yang benar tetap
> dua-tahap: **saring dulu dengan bounding box, baru hitung jarak tepat** —
> persis yang dilakukan `HasLocation::scopeNearby()` (MBRContains memakai
> `cr_location_spatial`, lalu `ST_Distance_Sphere` hanya pada kandidat yang
> lolos kotak):

```php
// HasLocation::scopeNearby() — dipakai pencocokan penyedia (BroadcastService)
$meter  = $radiusKm * 1000;   // km → meter; derajat kotak dihitung dari meter
$latDeg = $meter / self::METER_PER_LAT_DEGREE;
$lngDeg = $meter / (self::METER_PER_LAT_DEGREE * cos(deg2rad($lat)));
$bbox   = sprintf('POLYGON((%1$F %2$F, %1$F %4$F, %3$F %4$F, %3$F %2$F, %1$F %2$F))',
    $lng - $lngDeg, $lat - $latDeg, $lng + $lngDeg, $lat + $latDeg);

return $q
    // TAHAP 1 — MBRContains memakai indeks spasial, membuang sebagian besar baris
    ->whereRaw('MBRContains('.SpatialSchema::geomFromTextSql().', location)', [$bbox])
    // TAHAP 2 — jarak akurat, hanya pada kandidat yang lolos tahap 1
    ->whereRaw('ST_Distance_Sphere(location, '.SpatialSchema::geomFromTextSql().') <= ?', [self::wkt($lat, $lng), $meter]);
```

Bounding box **selalu lebih besar** dari lingkaran radius (rasio lingkaran
terhadap kotak ≈ π/4 ≈ 78,5%), jadi tidak ada entitas sah yang terbuang;
tahap 2 hanya membuang sudut-sudut kotak yang di luar lingkaran.

> ⚠️ **Rekomendasi memakai `ST_Buffer` untuk ini keliru.** MySQL
> **tidak mendukung** `ST_Buffer` pada sistem koordinat geografis:
>
> ```
> ERROR 3618: st_buffer(POINT, ...) has not been implemented
>             for geographic spatial reference systems
> ```
>
> `ST_Buffer` hanya bekerja pada SRID 0 (Kartesius), sementara skema ini
> memakai SRID 4326. Memaksakannya lewat `ST_SRID(..., 0)` akan menghasilkan
> perhitungan datar — jarak menjadi salah, terutama pada rentang kilometer.
>
> `MBRContains` dengan poligon persegi adalah pengganti yang tepat: memakai
> indeks, dan poligonnya dihitung aplikasi sehingga tidak butuh dukungan
> geografis dari MySQL.

#### ⚠️ `'axis-order=long-lat'` wajib pada SETIAP WKT bersistem SRID 4326

Untuk SRID 4326 MySQL mengikuti definisi EPSG — `AXIS["Lat",NORTH],
AXIS["Lon",EAST]` — sehingga **sumbu pertama adalah latitude**, bukan
longitude. Menulis `POINT(107.6 -6.9)` tanpa opsi apa pun membuat MySQL
membaca 107,6 sebagai lintang dan langsung menolaknya:

```
ERROR 3617 (22S03): Latitude 107.600000 is out of range in function
st_geomfromtext. It must be within [-90.000000, 90.000000].
```

Ini bukan kasus tepi: **seluruh Indonesia berada di bujur 95°–141° BT**, semua
di luar rentang ±90. Tanpa opsi ini, setiap penulisan titik gagal.

Karena itu semua WKT di proyek ini memakai urutan `POINT(longitude latitude)`
— sesuai konvensi GeoJSON di API — dan **selalu** menyertakan argumen ketiga:

```php
ST_GeomFromText('POINT(112.7521 -7.2575)', 4326, 'axis-order=long-lat')
```

Alternatifnya menulis `POINT(lat lng)` tanpa opsi. Itu ditolak karena membuat
urutan di SQL berbeda dari payload API, dan **kesalahan seperti itu tidak
memicu error** — hanya lokasi yang salah diam-diam.

Seluruh pembuatan POINT dipusatkan di `HasLocation::setLocation()` dan
`SpatialSchema::pointExpression()` supaya opsi ini tidak mungkin terlupakan;
`tools/dev/check-mysql.mjs` menggagalkan build bila ada yang menghapusnya.

**Verifikasi indeks benar-benar terpakai:**

```sql
EXPLAIN SELECT * FROM customer_requests
WHERE MBRContains(ST_GeomFromText('POLYGON((...))', 4326, 'axis-order=long-lat'), location);
-- key harus 'cr_location_spatial', BUKAN NULL
```

> Pada MySQL 8.0.29 sempat ada regresi yang membuat `MBRContains` mengabaikan
> indeks spasial; sudah diperbaiki di **8.0.30**. Ini salah satu alasan target
> minimum proyek adalah **MySQL 8.0.34+**. Jika `EXPLAIN` tetap menunjukkan
> `NULL`, `FORCE INDEX` bisa dipakai sebagai penanganan sementara.

### Fulltext Search Listing

```php
// Sanitasi dulu: karakter operator boolean dari input pengguna bisa
// membuat query gagal atau memberi hasil tak terduga.
$safe = preg_replace('/[+\-><()~*"@]+/', ' ', $keyword);
$terms = collect(explode(' ', $safe))
    ->filter(fn ($w) => mb_strlen($w) >= 2)
    ->map(fn ($w) => $w.'*')          // awalan, agar "beras" cocok "berasan"
    ->implode(' ');

Listing::query()
    ->whereRaw('MATCH(title, description) AGAINST(? IN BOOLEAN MODE)', [$terms])
    // Skor relevansi dipakai untuk pengurutan, bukan hanya penyaringan.
    ->selectRaw('*, MATCH(title, description) AGAINST(?) AS relevance', [$safe])
    ->where('status', 'active')
    ->orderByDesc('relevance')
    ->get();
```

> ⚠️ **Input pengguna tidak boleh langsung masuk ke `BOOLEAN MODE`.** Karakter
> seperti `+`, `-`, `*`, `~`, dan `"` adalah operator; kata kunci
> `AC -bekas` justru **mengecualikan** hasil yang mengandung "bekas", padahal
> pengguna kemungkinan hanya mengetik tanda hubung biasa.
>
> Pencarian dengan awalan (`beras*`) tidak bisa dikombinasikan dengan
> pengurutan relevansi pada ekspresi yang sama — karena itu `AGAINST` ditulis
> dua kali: versi boolean untuk menyaring, versi natural untuk skor.
>
> **Kombinasi FULLTEXT + radius perlu perhatian.** MySQL hanya memakai satu
> indeks per tabel, jadi query yang menyaring teks *dan* lokasi sekaligus akan
> memilih salah satunya. Untuk katalog, filter radius biasanya lebih selektif —
> pertimbangkan menyaring lokasi lebih dulu di subquery, lalu `MATCH` pada
> hasilnya, dan bandingkan keduanya dengan `EXPLAIN` memakai data nyata.

### Menutup Permintaan Kadaluarsa (Scheduler)

```php
CustomerRequest::where('status', 'open')
    ->where('expires_at', '<', now())
    ->update(['status' => 'expired']);
```

Indeks `cr_status_expires_idx` akan sangat membantu.

### Menghitung Rata‑rata Rating Toko (Trigger / Aplikasi)

Setelah insert ulasan, update store:

```php
$store->update([
    'rating_avg' => $store->reviews()->avg('rating'),
    'total_reviews' => $store->reviews()->count(),
]);
```

Gunakan transaksi agar tetap konsisten.

### Ringkasan Indeks & Pola Query

| Query | Indeks yang dipakai | Pola wajib |
| :-- | :-- | :-- |
| Toko dalam radius | `stores_latlng_idx` | `whereBetween` kotak pembatas **lalu** haversine di PHP (§11) |
| Penyedia di sekitar permintaan | `cr_location_spatial` | `MBRContains` **lalu** `ST_Distance_Sphere` |
| Pencarian katalog | `listings_fulltext` | Sanitasi input sebelum `BOOLEAN MODE` |
| Broadcast per kategori | `cr_category_status_idx` | `category_id` (=) sebelum `status` (=) |
| Penawaran per permintaan | `offers_request_status_idx` | |
| Riwayat pesanan | `orders_buyer_status_idx` | Kesamaan dulu, `created_at` terakhir |
| Tutup permintaan expired | `cr_status_expires_idx` | Kesamaan (`status`) sebelum rentang (`expires_at`) |
| Rating toko | `reviews_store_direction_idx` | Saring `direction = 'buyer_to_store'` |

### Memantau Query Lambat

Aktifkan slow query log sejak awal — jauh lebih mudah menemukan query bermasalah
dari catatan nyata daripada menebaknya:

```ini
[mysqld]
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 0.5
log_queries_not_using_indexes = 1
min_examined_row_limit = 100      # abaikan tabel kecil (kategori, settings)
```

```bash
mysqldumpslow -s t -t 10 /var/log/mysql/slow.log    # 10 query terlambat
```

Laravel Pulse (`Server_Implementation_Guide.md` §21.6) menampilkan hal yang
sama lewat dasbor, tetapi slow query log tetap berguna karena mencatat query
dari sumber mana pun — termasuk scheduler dan perintah artisan.

> ⚠️ **`log_queries_not_using_indexes` bisa membanjiri disk** pada trafik
> tinggi. Aktifkan saat pengembangan dan minggu-minggu awal produksi, lalu
> matikan setelah query bermasalah teratasi.
>
> Ambang `long_query_time = 0.5` sengaja ketat. Untuk aplikasi mobile di area
> sinyal lemah, query 500 ms sudah terasa lambat karena masih ditambah latensi
> jaringan.
