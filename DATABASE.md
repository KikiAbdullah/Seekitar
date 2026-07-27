# 🗄️ DATABASE DESIGN — SEEKITAR

**Dokumen Lengkap, Optimal, & Production‑Ready**  
**Versi:** 3.0 (Production‑Hardened)  
**Tanggal:** 27 Juli 2026  
**Target Deployment:** MySQL 8.0.34+ InnoDB  
**Charset:** utf8mb4 – Collation: utf8mb4_unicode_ci

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
5. [Strategi Foreign Key & Cascading](#5-strategi-foreign-key--cascading)
6. [Soft Delete: Implementasi & Cleanup](#6-soft-delete-implementasi--cleanup)
7. [Indeks Komprehensif & Query Patterns](#7-indeks-komprehensif--query-patterns)
8. [Data Integrity Guard (Anti Human‑Error)](#8-data-integrity-guard-anti-human-error)
9. [Keamanan & Pencegahan SQL Injection](#9-keamanan--pencegahan-sql-injection)
10. [Migrasi, Seeder & Deployment di Laravel 11](#10-migrasi-seeder--deployment-di-laravel-11)
11. [Lampiran: Raw Query & Performance Tips](#11-lampiran-raw-query--performance-tips)

---

## 1. VISI & FILOSOFI DATABASE

Database Seekitar dirancang sebagai **Single Source of Truth** untuk seluruh data transaksi marketplace hyperlocal. Filosofi inti kami:

- **Integritas di level database** – Bukan hanya di aplikasi. Setiap aturan bisnis yang bisa diwakili oleh constraint (CHECK, UNIQUE, FOREIGN KEY) HARUS ada di database.
- **Tak kenal kompromi pada data yatim** – Setiap baris yang ada selalu merujuk ke entitas yang sah.
- **Geospasial adalah warga kelas satu** – Semua titik disimpan sebagai `POINT` SRID 4326, bukan kolom `lat`/`lng` terpisah.
- **UUID mencegah prediksi** – Semua ID adalah UUID v4, menghilangkan risiko enumerasi dan memperkuat keamanan.
- **Soft delete wajib untuk data penting** – `users`, `stores`, `listings` tidak pernah dihapus permanen; hanya disembunyikan.
- **Siap dioperasikan oleh manusia** – Nama kolom deskriptif, constraint mencegah kesalahan input, dan default value masuk akal.

---

## 2. STANDAR & KONVENSI PENAMAAN

| Objek           | Aturan                                   | Contoh                       |
| --------------- | ---------------------------------------- | ---------------------------- |
| Tabel           | jamak, snake_case                        | `users`, `customer_requests` |
| Primary Key     | `id` CHAR(36)                            | –                            |
| Foreign Key     | `nama_tabel_tunggal_id`                  | `store_id`, `buyer_id`       |
| Indeks Biasa    | `{tabel}_{kolom}_idx`                    | `stores_user_id_idx`         |
| Indeks Unik     | `{tabel}_{kolom}_unique`                 | `users_phone_unique`         |
| Indeks Spasial  | `{tabel}_{kolom}_spatial`                | `stores_location_spatial`    |
| Indeks Fulltext | `{tabel}_ft_{kolom}`                     | `listings_ft_title_desc`     |
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
| `reviews`           | Ulasan pasca‑transaksi             | ❌          | Satu per `orders`, dari/ke `users`                                     |
| `disputes`          | Laporan masalah                    | ❌          | Terkait `orders`, dilaporkan `users`                                   |

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
orders 1──1 reviews
orders 1──N disputes
```

---

## 4. SKEMA LENGKAP TABEL

Setiap tabel dilengkapi penjelasan tiap kolom, alasan pemilihan tipe, dan constraint lengkap.

### 4.1 `users`

| Kolom                | Tipe                 | Keterangan & Rasional                                                                |
| -------------------- | -------------------- | ------------------------------------------------------------------------------------ |
| `id`                 | CHAR(36)             | UUID v4, PRIMARY KEY. UUID dipilih agar tidak dapat ditebak, cocok untuk API publik. |
| `phone`              | VARCHAR(15)          | Nomor HP Indonesia (diawali 62), unik. Menghindari duplikasi akun.                   |
| `name`               | VARCHAR(100)         | Nama asli pengguna, wajib diisi.                                                     |
| `avatar_url`         | VARCHAR(500) NULL    | URL foto profil, disimpan di cloud storage. Panjang 500 cukup untuk URL pre‑signed.  |
| `location`           | POINT SRID 4326 NULL | Lokasi default pengguna (misal rumah). NULL jika belum diisi.                        |
| `verification_level` | TINYINT DEFAULT 1    | 1 = nomor HP, 2 = KTP diverifikasi, 3 = Pro (usaha tervalidasi).                     |
| `deleted_at`         | TIMESTAMP NULL       | Soft delete untuk pengguna yang menonaktifkan akun.                                  |
| `created_at`         | TIMESTAMP            | Otomatis diisi Laravel.                                                              |
| `updated_at`         | TIMESTAMP            | Otomatis diisi Laravel.                                                              |

**Constraint & Indeks:**

- PRIMARY KEY (`id`)
- UNIQUE KEY `users_phone_unique` (`phone`)
- SPATIAL INDEX `users_location_spatial` (`location`)
- INDEX `users_deleted_at_idx` (`deleted_at`) — untuk filter global scope soft delete.

### 4.2 `stores`

| Kolom                 | Tipe                                                    | Keterangan                                                                     |
| --------------------- | ------------------------------------------------------- | ------------------------------------------------------------------------------ |
| `id`                  | CHAR(36)                                                | PK, UUID.                                                                      |
| `user_id`             | CHAR(36)                                                | FK ke `users.id`. Pemilik toko. Satu user bisa punya banyak toko.              |
| `name`                | VARCHAR(100)                                            | Nama lapak, unik per kabupaten (unik dijamin aplikasi).                        |
| `store_type`          | SET('goods','services','rental')                        | Kombinasi jenis usaha. SET lebih efisien dari VARCHAR untuk pilihan tetap.     |
| `category_ids`        | JSON                                                    | Array ID dari `categories`. Contoh: `[1, 3, 7]`.                               |
| `location`            | POINT SRID 4326                                         | Titik koordinat toko (longitude, latitude). Wajib.                             |
| `service_radius_km`   | DECIMAL(5,2) DEFAULT 5.00                               | Radius layanan dalam km. Presisi 2 desimal.                                    |
| `operating_hours`     | JSON                                                    | Jam operasional per hari. Contoh: `{"senin":{"open":"08:00","close":"17:00"}}` |
| `rating_avg`          | DECIMAL(3,2) DEFAULT 0.00                               | Rata‑rata rating, dihitung ulang setiap ada ulasan baru.                       |
| `total_reviews`       | INT UNSIGNED DEFAULT 0                                  | Jumlah total ulasan, counter untuk kalkulasi cepat.                            |
| `is_active`           | TINYINT(1) DEFAULT 1                                    | Toko nonaktif tidak muncul di pencarian.                                       |
| `verification_status` | ENUM('pending','verified','rejected') DEFAULT 'pending' | Status verifikasi admin.                                                       |
| `deleted_at`          | TIMESTAMP NULL                                          | Soft delete.                                                                   |
| `created_at`          | TIMESTAMP                                               | –                                                                              |
| `updated_at`          | TIMESTAMP                                               | –                                                                              |

**Constraint & Indeks:**

- PRIMARY KEY (`id`)
- FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
- INDEX `stores_user_id_idx` (`user_id`)
- INDEX `stores_deleted_at_idx` (`deleted_at`)
- SPATIAL INDEX `stores_location_spatial` (`location`)
- INDEX `stores_is_active_idx` (`is_active`) — mempercepat query toko aktif.

**Kenapa SET untuk store_type?**  
Karena tipe toko terbatas (3 pilihan), SET lebih hemat ruang dan memungkinkan pencarian dengan `FIND_IN_SET` atau `LIKE` jika perlu.

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
- FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
- INDEX `categories_parent_id_idx` (`parent_id`)

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
- FULLTEXT INDEX `listings_ft_title_desc` (`title`, `description`)
- INDEX `listings_status_idx` (`status`) — untuk filter aktif/tidak.

**Keterangan tambahan:** `price` NULL memungkinkan listing tanpa harga (misal jasa yang memerlukan survey). Validasi di level aplikasi: jika `listing_type` = ‘service’, price boleh NULL; untuk product/rental wajib diisi.

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
| `location`          | POINT SRID 4326                                | Titik lokasi pembeli, wajib.                                      |
| `radius_km`         | DECIMAL(5,2) DEFAULT 15.00                     | Radius pencarian penyedia.                                        |
| `required_date`     | TIMESTAMP NULL                                 | Kapan kebutuhan harus dipenuhi.                                   |
| `expires_at`        | TIMESTAMP NOT NULL                             | Waktu kedaluwarsa (default 24 jam).                               |
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

### 4.6 `offers`

| Kolom             | Tipe                                                    | Keterangan                      |
| ----------------- | ------------------------------------------------------- | ------------------------------- |
| `id`              | CHAR(36)                                                | PK, UUID.                       |
| `request_id`      | CHAR(36)                                                | FK ke `customer_requests`.      |
| `store_id`        | CHAR(36)                                                | FK ke `stores`.                 |
| `price`           | DECIMAL(12,2)                                           | Harga penawaran.                |
| `estimation_time` | VARCHAR(100)                                            | Estimasi pengerjaan/pengiriman. |
| `notes`           | TEXT NULL                                               | Catatan tambahan.               |
| `status`          | ENUM('pending','accepted','rejected') DEFAULT 'pending' |                                 |
| `created_at`      | TIMESTAMP                                               | –                               |
| `updated_at`      | TIMESTAMP                                               | –                               |

**Constraint & Indeks:**

- PRIMARY KEY (`id`)
- FOREIGN KEY (`request_id`) REFERENCES `customer_requests`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE
- UNIQUE KEY `offers_req_store_unique` (`request_id`, `store_id`) — **satu toko hanya boleh satu penawaran per permintaan**.
- INDEX `offers_status_idx` (`status`)

**Mengapa CASCADE pada store_id?** Jika toko dihapus (soft delete), penawaran menjadi tidak valid. Daripada memperumit, kita hapus cascade; data penawaran sudah tidak relevan. Order yang sudah terjadi tetap utuh karena `offer_id` di orders menggunakan `SET NULL`.

### 4.7 `orders`

| Kolom            | Tipe                                                                                                            | Keterangan                             |
| ---------------- | --------------------------------------------------------------------------------------------------------------- | -------------------------------------- |
| `id`             | CHAR(36)                                                                                                        | PK, UUID.                              |
| `buyer_id`       | CHAR(36)                                                                                                        | FK ke `users` (pembeli).               |
| `store_id`       | CHAR(36)                                                                                                        | FK ke `stores` (penyedia).             |
| `offer_id`       | CHAR(36) NULL                                                                                                   | FK ke `offers` (jika dari penawaran).  |
| `listing_id`     | CHAR(36) NULL                                                                                                   | FK ke `listings` (jika langsung beli). |
| `order_type`     | ENUM('goods','service','rental')                                                                                | Menentukan alur status.                |
| `total_amount`   | DECIMAL(12,2)                                                                                                   | Total transaksi.                       |
| `status`         | ENUM('menunggu_konfirmasi','diproses','dikirim','selesai','dibatalkan','dispute') DEFAULT 'menunggu_konfirmasi' |                                        |
| `payment_method` | ENUM('cod','transfer')                                                                                          |                                        |
| `completed_at`   | TIMESTAMP NULL                                                                                                  | Waktu transaksi dianggap selesai.      |
| `created_at`     | TIMESTAMP                                                                                                       | –                                      |
| `updated_at`     | TIMESTAMP                                                                                                       | –                                      |

**Constraint & Indeks:**

- PRIMARY KEY (`id`)
- FOREIGN KEY (`buyer_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT — pembeli tidak bisa dihapus jika punya order.
- FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE RESTRICT — toko tidak bisa dihapus jika punya order.
- FOREIGN KEY (`offer_id`) REFERENCES `offers`(`id`) ON DELETE SET NULL — jika offer dihapus, order tetap ada, sumber jadi null.
- FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`) ON DELETE SET NULL
- INDEX `orders_buyer_id_idx` (`buyer_id`)
- INDEX `orders_store_id_idx` (`store_id`)
- INDEX `orders_status_idx` (`status`)
- INDEX `orders_created_at_idx` (`created_at`) — untuk laporan tanggal.

**Catatan:** Status menggunakan ENUM agar tidak ada nilai tak terduga. Daftar status sudah mencakup seluruh alur (termasuk `dispute`).

### 4.8 `reviews`

| Kolom         | Tipe      | Keterangan                                                |
| ------------- | --------- | --------------------------------------------------------- |
| `id`          | CHAR(36)  | PK, UUID.                                                 |
| `order_id`    | CHAR(36)  | FK ke `orders`, UNIK. Satu order hanya boleh satu ulasan. |
| `reviewer_id` | CHAR(36)  | FK ke `users`, yang menulis ulasan.                       |
| `reviewee_id` | CHAR(36)  | FK ke `users`, yang diulas.                               |
| `rating`      | TINYINT   | 1 – 5. CHECK (rating BETWEEN 1 AND 5)                     |
| `comment`     | TEXT NULL |                                                           |
| `created_at`  | TIMESTAMP |                                                           |

**Constraint & Indeks:**

- PRIMARY KEY (`id`)
- FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`reviewer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`reviewee_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
- UNIQUE KEY `reviews_order_id_unique` (`order_id`)
- INDEX `reviews_reviewee_id_idx` (`reviewee_id`) — untuk menghitung rating toko.

### 4.9 `disputes`

| Kolom             | Tipe                                   | Keterangan                            |
| ----------------- | -------------------------------------- | ------------------------------------- |
| `id`              | CHAR(36)                               | PK, UUID.                             |
| `order_id`        | CHAR(36)                               | FK ke `orders`.                       |
| `reported_by`     | CHAR(36)                               | FK ke `users`, pelapor.               |
| `reason`          | VARCHAR(100)                           | Alasan dipilih dari enum di aplikasi. |
| `description`     | TEXT NULL                              | Penjelasan tambahan.                  |
| `status`          | ENUM('open','resolved') DEFAULT 'open' |                                       |
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
| `stores`            | `stores_location_spatial`                    | Pencarian toko dalam radius.                                                  |
| `stores`            | `stores_is_active_idx`                       | Hanya tampilkan toko aktif.                                                   |
| `listings`          | `listings_ft_title_desc`                     | Pencarian teks produk/jasa.                                                   |
| `customer_requests` | `cr_status_expires_idx`                      | Job menutup permintaan expired: `WHERE status='open' AND expires_at < NOW()`. |
| `orders`            | `orders_store_id_idx`, `orders_buyer_id_idx` | Riwayat pesanan per toko/pembeli.                                             |
| `reviews`           | `reviews_reviewee_id_idx`                    | Rata‑rata rating toko: `WHERE reviewee_id = ?`.                               |

**Tips:** Hindari indeks berlebihan pada tabel yang sering ditulis (`offers`, `customer_requests`). Evaluasi dengan `EXPLAIN` secara berkala.

---

## 8. DATA INTEGRITY GUARD (ANTI HUMAN‑ERROR)

1. **UUID sebagai ID** – Tidak mungkin ada tabrakan, tidak bisa di‑tebak (IDOR prevention).
2. **Foreign Key Constraints** – Tidak akan ada order tanpa pembeli/penjual.
3. **UNIQUE constraint** pada offers (`request_id`, `store_id`) – mencegah toko mengirim dua penawaran pada permintaan yang sama, baik dari aplikasi maupun langsung dari SQL.
4. **ENUM + CHECK** – Status pesanan, tipe listing, rating, semua memiliki domain terbatas yang terverifikasi di level engine.
5. **Default value** – `verification_level` = 1, `status` = ‘active’, dll.
6. **Aplikasi wajib gunakan transaksi** – setiap aksi multi‑tabel (contoh: menerima penawaran → update request, update offer, insert order) HARUS dalam `DB::transaction()`.
7. **Validasi data JSON** – Di Laravel, gunakan `$casts` dan Form Request untuk memastikan `category_ids` adalah array integer, `images` adalah array URL, dll.
8. **Mekanisme update rating toko** – rating_avg diperbarui dalam transaksi bersama penulisan ulasan, menghindari inkonsistensi.

---

## 9. KEAMANAN & PENCEGAHAN SQL INJECTION

- **Laravel Eloquent / Query Builder** melindungi dari SQL injection. Untuk raw query (geospasial), gunakan binding parameter:

```php
->whereRaw("ST_Distance_Sphere(location, ST_GeomFromText(?, 4326)) <= ?", [$point, $radius])
```

- **User input tidak pernah langsung masuk raw query**.
- **Rate limiting** mencegah brute force dan pengurasan database.

---

## 10. MIGRASI, SEEDER & DEPLOYMENT DI LARAVEL 11

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
DB::statement("ALTER TABLE stores ADD COLUMN location POINT SRID 4326 AFTER category_ids");
DB::statement("CREATE SPATIAL INDEX stores_location_spatial ON stores(location)");
```

**UUID di Laravel 11** – Gunakan `Str::orderedUuid()` saat creating model, atau trait `HasUuids` jika diinginkan.

---

## 11. LAMPIRAN: RAW QUERY & PERFORMANCE TIPS

### Pencarian Radius Toko

```php
$meter = $radiusKm * 1000;
Store::whereRaw(
    "ST_Distance_Sphere(location, ST_GeomFromText(?, 4326)) <= ?",
    ["POINT({$longitude} {$latitude})", $meter]
)->where('is_active', 1)->get();
```

**Performance:** Pastikan spatial index digunakan; hasil `EXPLAIN` harus menunjukkan `Using index`.

### Fulltext Search Listing

```php
Listing::whereRaw(
    "MATCH(title, description) AGAINST(? IN BOOLEAN MODE)",
    [$keyword]
)->get();
```

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
