# 🗄️ DATABASE DESIGN — SEEKITAR

**Dokumen Lengkap, Optimal, & Production‑Ready**  
**Versi:** 2.1 (Production‑Hardened)  
**Tanggal:** 27 Juli 2026  
**Target Deployment:** MySQL 8.0.34+ InnoDB  
**Charset:** utf8mb4 – Collation: utf8mb4_unicode_ci  
**ORM:** Laravel 13 (Eloquent) · PHP 8.3+

> 📌 Versi mengacu pada [`TECH_STACK.md`](TECH_STACK.md) sebagai sumber kebenaran tunggal.

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
10. [Migrasi, Seeder & Deployment di Laravel 13](#10-migrasi-seeder--deployment-di-laravel-13)
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

> 📌 Padanan nama tabel dengan istilah UI dan API ada di
> [`TECH_STACK.md`](TECH_STACK.md) §6 (Glosarium Lintas Lapisan).

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
| `reviews`           | Ulasan pasca‑transaksi (dua arah)  | ❌          | Maks 2 per `orders`; ke `stores` (rating toko) atau ke `users`         |
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
orders 1──2 reviews (maks satu per arah: buyer_to_store, store_to_buyer)
stores 1──N reviews (hanya arah buyer_to_store, sumber rating_avg)
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
| `location`           | POINT SRID 4326 NULL | Lokasi default pengguna (misal rumah). NULL hanya saat onboarding belum selesai.     |
| `address`            | VARCHAR(255) NULL    | Alamat teks hasil reverse geocoding. Untuk ditampilkan, bukan untuk query.           |
| `verification_level` | TINYINT DEFAULT 1    | 1 = nomor HP, 2 = KTP diverifikasi, 3 = Pro (usaha tervalidasi). Hanya 1–3.          |
| `ktp_image`          | VARCHAR(500) NULL    | URL foto KTP (terenkripsi at-rest). Diisi saat pengajuan verifikasi Level 2.         |
| `selfie_image`       | VARCHAR(500) NULL    | URL selfie memegang KTP. Wajib bersama `ktp_image`.                                  |
| `ktp_submitted_at`   | TIMESTAMP NULL       | Kapan berkas diajukan — dipakai SLA peninjauan admin 1×24 jam.                       |
| `ktp_rejected_reason`| TEXT NULL            | Alasan penolakan agar pengguna tahu apa yang harus diperbaiki.                       |
| `nik`                | VARCHAR(255) NULL    | NIK hasil pembacaan admin. **Terenkripsi** (cast `encrypted`), bukan plaintext.      |
| `nik_hash`           | CHAR(64) NULL        | SHA-256 dari NIK. Untuk mendeteksi NIK ganda, karena kolom terenkripsi tak bisa di-`WHERE`. |
| `deleted_at`         | TIMESTAMP NULL       | Soft delete untuk pengguna yang menonaktifkan akun.                                  |
| `created_at`         | TIMESTAMP            | Otomatis diisi Laravel.                                                              |
| `updated_at`         | TIMESTAMP            | Otomatis diisi Laravel.                                                              |

**Constraint & Indeks:**

- PRIMARY KEY (`id`)
- UNIQUE KEY `users_phone_unique` (`phone`)
- SPATIAL INDEX `users_location_spatial` (`location`)
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

**Verifikasi KTP (Level 2):** `ktp_image` dan `selfie_image` adalah data pribadi
sensitif menurut UU PDP. Simpan di bucket privat, akses hanya lewat URL
pre-signed berumur pendek, dan **jangan** pernah dikembalikan di response API
publik. Setelah `verification_level` naik ke 2, berkas boleh dihapus sesuai
kebijakan retensi.

### 4.2 `stores`

| Kolom                 | Tipe                                                    | Keterangan                                                                     |
| --------------------- | ------------------------------------------------------- | ------------------------------------------------------------------------------ |
| `id`                  | CHAR(36)                                                | PK, UUID.                                                                      |
| `user_id`             | CHAR(36)                                                | FK ke `users.id`. Pemilik toko. Satu user bisa punya banyak toko.              |
| `name`                | VARCHAR(100)                                            | Nama lapak, unik per kabupaten (`regency`). Lihat catatan uniqueness.          |
| `regency`             | VARCHAR(100)                                            | Kabupaten/kota tempat toko berada. Diisi dari reverse geocoding saat pinpoint. |
| `regency_code`        | CHAR(4) NULL                                            | Kode wilayah BPS (mis. `3578`). Sumber kebenaran untuk geofencing.             |
| `npwp`                | VARCHAR(20) NULL                                        | NPWP usaha (opsional). Syarat pendukung verifikasi Level 3 (PRD §5.3.2).       |
| `store_type`          | SET('goods','services','rental')                        | Kombinasi jenis usaha. SET lebih efisien dari VARCHAR untuk pilihan tetap.     |
| `category_ids`        | JSON                                                    | Array ID dari `categories`. Contoh: `[1, 3, 7]`.                               |
| `location`            | POINT SRID 4326                                         | Titik koordinat toko (longitude, latitude). Wajib.                             |
| `address`             | VARCHAR(255) NULL                                       | Alamat teks toko. Diisi reverse geocoding, bisa disunting pemilik.             |
| `service_radius_km`   | DECIMAL(5,2) DEFAULT 5.00                               | Radius layanan toko dalam km. Presisi 2 desimal. Lihat catatan di bawah.       |
| `accepts_cod`         | TINYINT(1) DEFAULT 1                                    | Menerima bayar di tempat. Sumber badge “Bisa COD”.                             |
| `offers_delivery`     | TINYINT(1) DEFAULT 0                                    | Mengantar sendiri. Sumber badge “Bisa Diantar”.                                |
| `allows_pickup`       | TINYINT(1) DEFAULT 1                                    | Punya lokasi fisik yang bisa didatangi. Sumber badge “Ambil di Tempat”.        |
| `operating_hours`     | JSON                                                    | Jam operasional per hari. Contoh: `{"senin":{"open":"08:00","close":"17:00"}}` |
| `rating_avg`          | DECIMAL(3,2) DEFAULT 0.00                               | Rata‑rata rating, dihitung ulang setiap ada ulasan baru.                       |
| `total_reviews`       | INT UNSIGNED DEFAULT 0                                  | Jumlah total ulasan, counter untuk kalkulasi cepat.                            |
| `is_active`           | TINYINT(1) DEFAULT 1                                    | Toko nonaktif tidak muncul di pencarian.                                       |
| `verification_status` | ENUM('pending','verified','rejected') DEFAULT 'pending' | Status verifikasi admin.                                                       |
| `rejected_reason`     | TEXT NULL                                               | Alasan penolakan admin. Wajib diisi saat status `rejected`.                    |
| `verified_at`         | TIMESTAMP NULL                                          | Kapan toko disetujui — untuk audit & SLA.                                      |
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
> pencarian sejauh 15 km — bertentangan dengan premis *hyperlocal* produk ini.

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
sekali. Karena itu `regency` ditambahkan sebagai lingkup keunikan.

> ⚠️ **`UNIQUE (name, regency, deleted_at)` TIDAK BEKERJA.** Dalam SQL, `NULL`
> tidak pernah dianggap sama dengan `NULL`, sehingga dua toko aktif
> (`deleted_at IS NULL`) bernama sama di kabupaten sama **tetap lolos**.
> Constraint-nya ada, tapi tidak menegakkan apa pun — jenis bug yang baru
> ketahuan setelah ada data duplikat di produksi.

Solusinya memakai **kolom generated** yang bernilai `NULL` saat baris sudah
di-soft-delete. Karena `NULL` diabaikan indeks unik, nama otomatis bebas
dipakai ulang setelah toko dihapus:

```sql
ALTER TABLE stores
  ADD COLUMN name_regency_active VARCHAR(210)
    GENERATED ALWAYS AS (
      CASE WHEN deleted_at IS NULL THEN CONCAT(name, '|', regency) END
    ) STORED,
  ADD UNIQUE KEY stores_name_regency_active_unique (name_regency_active);
```

Perilaku yang dihasilkan (sudah diuji):

| Kasus | Hasil |
| :-- | :-- |
| Nama sama, kabupaten berbeda | ✅ Diizinkan |
| Nama sama, kabupaten sama, keduanya aktif | ❌ Ditolak |
| Nama sama, yang lama sudah di-soft-delete | ✅ Diizinkan |
| Beberapa toko terhapus dengan nama sama | ✅ Diizinkan |

> Perbandingan nama bergantung pada collation. Dengan `utf8mb4_unicode_ci`
> (§Charset), "Warung Bu Sri" dan "warung bu sri" dianggap **sama** — memang
> yang diinginkan, karena keduanya membingungkan pembeli.

**Geofencing kabupaten target (PRD §5.3.3).** Toko di luar kabupaten target
harus ditolak otomatis. Ada dua tingkat pemeriksaan:

1. **Cepat (saat submit):** `regency_code` hasil reverse geocoding dicocokkan
   dengan daftar kabupaten yang dilayani. Menolak sebagian besar kasus salah
   wilayah tanpa biaya spasial.
2. **Akurat (opsional):** simpan poligon batas kabupaten dan uji titiknya —
   reverse geocoding bisa meleset di dekat perbatasan.

```sql
CREATE TABLE service_areas (
  id           SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  regency_code CHAR(4) NOT NULL UNIQUE,
  name         VARCHAR(100) NOT NULL,
  boundary     POLYGON SRID 4326 NULL,   -- opsional, dari data BPS/OSM
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  SPATIAL INDEX service_areas_boundary_spatial (boundary)
);
```

```php
// Verifikasi presisi saat poligon tersedia.
$inside = DB::selectOne(
    'SELECT ST_Contains(boundary, ST_GeomFromText(?, 4326)) AS ok
     FROM service_areas WHERE regency_code = ? AND is_active = 1',
    ["POINT($lng $lat)", $regencyCode]
)?->ok;
```

> Kolom `boundary` dibuat NULL-able supaya MVP bisa jalan hanya dengan
> pencocokan `regency_code`; poligon ditambahkan belakangan tanpa migrasi ulang.
> Admin tetap meninjau manual sebagai lapis terakhir — geofencing menyaring,
> bukan menggantikan verifikasi.

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
- FULLTEXT INDEX `listings_ft_title_desc` (`title`, `description`)
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
- CHECK `cr_budget_range_chk` — `budget_max` tidak boleh lebih kecil dari `budget_min`:

```sql
ALTER TABLE customer_requests
  ADD CONSTRAINT cr_budget_range_chk
  CHECK (budget_min IS NULL OR budget_max IS NULL OR budget_max >= budget_min);
```

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
- UNIQUE KEY `offers_req_store_unique` (`request_id`, `store_id`) — **satu toko hanya boleh satu penawaran per permintaan**.
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

- CHECK: `additional_cost >= 0`.

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
| `total_amount`   | DECIMAL(12,2)                                                                                                   | Total transaksi.                       |
| `status`         | ENUM('menunggu_konfirmasi','diproses','dikirim','selesai','dibatalkan','dispute') DEFAULT 'menunggu_konfirmasi' |                                        |
| `payment_method` | ENUM('cod','transfer')                                                                                          |                                        |
| `delivery_method`| ENUM('pickup','delivery') DEFAULT 'pickup'                                                                      | Ambil di tempat atau diantar penjual.  |
| `shipping_address` | TEXT NULL                                                                                                     | Alamat tujuan. Wajib jika `delivery`.  |
| `shipping_location` | POINT SRID 4326 NULL                                                                                         | Koordinat tujuan untuk navigasi penjual. |
| `payment_proof_url` | VARCHAR(500) NULL                                                                                            | Bukti transfer dari pembeli.           |
| `payment_confirmed_at` | TIMESTAMP NULL                                                                                            | Kapan penjual mengonfirmasi dana masuk. |
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
- INDEX `orders_buyer_id_idx` (`buyer_id`)
- INDEX `orders_store_id_idx` (`store_id`)
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
| `rating`      | TINYINT   | 1 – 5. CHECK (rating BETWEEN 1 AND 5)                     |
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
> | `direction` | `reviewee_id` | `store_id` | Memengaruhi `stores.rating_avg`? |
> | :-- | :-- | :-- | :-- |
> | `buyer_to_store` | pemilik toko | **terisi** | ✅ Ya |
> | `store_to_buyer` | pembeli | NULL | ❌ Tidak |
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
- CHECK `disputes_reason_desc_chk` — alasan `lainnya` wajib disertai penjelasan:

```sql
ALTER TABLE disputes
  ADD CONSTRAINT disputes_reason_desc_chk
  CHECK (reason <> 'lainnya' OR description IS NOT NULL);
```

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
  platform     ENUM('android','ios') NOT NULL,
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

### 4.11 `subscriptions` (Fase 2)

PRD §12 mencantumkan paket "Penyedia Pro" Rp49.000/bulan dan "Boost Listing"
Rp9.900/hari, tetapi **tidak ada tabel** untuk menyimpannya. Selama MVP gratis
(PRD §12: "Fase 1 seluruh fitur gratis"), tabel ini belum dibuat — dicantumkan
di sini agar rancangannya sudah disepakati.

```sql
CREATE TABLE subscriptions (
  id          CHAR(36) PRIMARY KEY,
  user_id     CHAR(36) NOT NULL,
  store_id    CHAR(36) NULL,             -- NULL = paket tingkat akun
  plan        ENUM('pro_monthly','boost_listing') NOT NULL,
  status      ENUM('pending','active','expired','cancelled') DEFAULT 'pending',
  amount      DECIMAL(12,2) NOT NULL,
  starts_at   TIMESTAMP NOT NULL,
  ends_at     TIMESTAMP NOT NULL,
  payment_ref VARCHAR(100) NULL,         -- referensi dari payment gateway
  created_at  TIMESTAMP,
  updated_at  TIMESTAMP,
  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
  INDEX subscriptions_active_idx (status, ends_at),
  INDEX subscriptions_store_idx (store_id, status),
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
| `stores`            | `stores_location_spatial`                    | Pencarian toko dalam radius.                                                  |
| `stores`            | `stores_is_active_idx`                       | Hanya tampilkan toko aktif.                                                   |
| `listings`          | `listings_ft_title_desc`                     | Pencarian teks produk/jasa.                                                   |
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
FULLTEXT INDEX listings_ft_title_desc (title, description)
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
> ALTER TABLE listings DROP INDEX listings_ft_title_desc;
> ALTER TABLE listings ADD FULLTEXT INDEX listings_ft_title_desc (title, description);
> ```
>
> **Stopword bawaan MySQL berbahasa Inggris.** Kata seperti "yang", "untuk",
> "dan" tetap terindeks dan menurunkan relevansi. Buat daftar stopword sendiri
> bila kualitas pencarian mulai terasa mengganggu:
> ```ini
> innodb_ft_server_stopword_table=seekitar/stopwords_id
> ```

### 7.3 Indeks Spasial

`stores_location_spatial` dan `cr_location_spatial` sudah didefinisikan di §4.2
dan §4.5. Dua syarat mutlak agar indeksnya sah:

```sql
-- 1. Kolom WAJIB NOT NULL — MySQL menolak SPATIAL INDEX pada kolom NULL-able
-- 2. SRID WAJIB ditetapkan pada kolom, bukan hanya pada nilainya
ALTER TABLE stores MODIFY location POINT NOT NULL SRID 4326;
ALTER TABLE stores ADD SPATIAL INDEX stores_location_spatial (location);
```

Verifikasi bahwa SRID benar-benar melekat pada kolom:

```sql
SELECT COLUMN_NAME, SRS_ID FROM INFORMATION_SCHEMA.ST_GEOMETRY_COLUMNS
WHERE TABLE_NAME = 'stores';
-- SRS_ID harus 4326, BUKAN NULL
```

> ⚠️ Tanpa atribut `SRID 4326` pada definisi kolom, MySQL memperlakukan kolom
> sebagai SRID tak tentu — indeks spasial **tidak akan dipakai** oleh
> pengoptimal, meski indeksnya ada. Inilah alasan `users.location` tidak punya
> indeks spasial (kolomnya NULL-able, lihat §4.1).

---

## 8. DATA INTEGRITY GUARD (ANTI HUMAN‑ERROR)

1. **UUID sebagai ID** – Tidak mungkin ada tabrakan, tidak bisa di‑tebak (IDOR prevention).
2. **Foreign Key Constraints** – Tidak akan ada order tanpa pembeli/penjual.
3. **UNIQUE constraint** pada offers (`request_id`, `store_id`) – mencegah toko mengirim dua penawaran pada permintaan yang sama, baik dari aplikasi maupun langsung dari SQL.
4. **ENUM + CHECK** – Status pesanan, tipe listing, rating, semua memiliki domain terbatas yang terverifikasi di level engine.
5. **Default value** – `verification_level` = 1, `status` = ‘active’, dll.
6. **Aplikasi wajib gunakan transaksi** – setiap aksi multi‑tabel (contoh: menerima penawaran → update request, update offer, insert order) HARUS dalam `DB::transaction()` **dengan `lockForUpdate()`** pada baris yang jadi rebutan.
7. **Validasi data JSON** – Di Laravel, gunakan `$casts` dan Form Request untuk memastikan `category_ids` adalah array integer, `images` adalah array URL, dll.
8. **Mekanisme update rating toko** – `rating_avg` dihitung dari `reviews.store_id` (bukan `reviewee_id`) dan hanya arah `buyer_to_store`, diperbarui dalam transaksi bersama penulisan ulasan.
9. **CHECK constraint lintas kolom** – aturan yang tidak bisa diwakili ENUM ditegakkan engine: harga wajib untuk product/rental, stok vs slot sesuai tipe, alamat wajib saat `delivery`, `budget_max ≥ budget_min`, dan dispute `lainnya` wajib berdeskripsi. Semua butuh **MySQL 8.0.16+**.
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
->whereRaw("ST_Distance_Sphere(location, ST_GeomFromText(?, 4326)) <= ?", [$point, $radius])
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
DB::statement("ALTER TABLE stores ADD COLUMN location POINT SRID 4326 AFTER category_ids");
DB::statement("CREATE SPATIAL INDEX stores_location_spatial ON stores(location)");
```

**UUID di Laravel 13** – Gunakan `Str::orderedUuid()` saat creating model, atau trait `HasUuids` jika diinginkan.

---

## 11. LAMPIRAN: RAW QUERY & PERFORMANCE TIPS

### Pencarian Radius Toko

> ⚠️ **`ST_Distance_Sphere` di klausa `WHERE` TIDAK memakai indeks spasial.**
> Fungsi ini menghitung jarak untuk **setiap baris** — pada 50 toko tidak
> terasa, pada 50.000 toko query ini menjadi hambatan utama aplikasi.

**Pola yang benar: saring dulu dengan bounding box, baru hitung jarak tepat.**

```php
$meter  = $radiusKm * 1000;
$latDeg = $meter / 111320;
$lngDeg = $meter / (111320 * cos(deg2rad($latitude)));

$stores = Store::query()
    // TAHAP 1 — MBRContains memakai indeks spasial, membuang sebagian besar baris
    ->whereRaw(
        'MBRContains(ST_GeomFromText(?, 4326), location)',
        [sprintf(
            'POLYGON((%1$F %2$F, %1$F %4$F, %3$F %4$F, %3$F %2$F, %1$F %2$F))',
            $longitude - $lngDeg, $latitude - $latDeg,
            $longitude + $lngDeg, $latitude + $latDeg
        )]
    )
    // TAHAP 2 — jarak akurat, hanya pada kandidat yang lolos tahap 1
    ->whereRaw(
        'ST_Distance_Sphere(location, ST_GeomFromText(?, 4326)) <= ?',
        ["POINT($longitude $latitude)", $meter]
    )
    ->where('is_active', 1)
    ->get();
```

**Kenapa dua tahap** (hasil pengukuran):

| Aspek | Nilai |
| :-- | :-- |
| Titik dalam bbox yang benar-benar dalam radius | **78,8%** (sesuai teori π/4 ≈ 78,5%) |
| Baris yang dibuang sebelum perhitungan jarak | **~21%** dari kandidat bbox |
| Baris yang dibuang sebelum bbox | seluruh tabel di luar kotak — inilah penghematan utamanya |
| Titik tepi radius yang terlewat | **0 dari 72 arah yang diuji** |

Bounding box **selalu lebih besar** dari lingkaran radius, jadi tidak ada toko
sah yang terbuang. Tahap 2 membuang sisa sudut kotak yang di luar lingkaran.

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

**Perhatikan urutan `POINT(longitude latitude)`** — terbalik dari kebiasaan
menulis "lat, lng". Kesalahan ini tidak memicu error, hanya hasil yang salah
diam-diam. Karena itu seluruh pembuatan POINT dipusatkan di
`GeolocationService` (`Server_Implementation_Guide.md` §16).

**Verifikasi indeks benar-benar terpakai:**

```sql
EXPLAIN SELECT * FROM stores
WHERE MBRContains(ST_GeomFromText('POLYGON((...))', 4326), location);
-- key harus 'stores_location_spatial', BUKAN NULL
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
| Toko dalam radius | `stores_location_spatial` | `MBRContains` **lalu** `ST_Distance_Sphere` |
| Pencarian katalog | `listings_ft_title_desc` | Sanitasi input sebelum `BOOLEAN MODE` |
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
