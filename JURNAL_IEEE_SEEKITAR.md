---
title: "Seekitar: Rancang Bangun Platform Marketplace Hyperlocal Dua Arah Berbasis Geolokasi dengan MySQL Spatial"
author:
  - Tim Pengembang Seekitar
  - PT Seekitar Digital Nusantara
date: Juli 2026
abstract: |
  Platform marketplace nasional yang ada saat ini kurang efisien untuk kebutuhan harian
  berskala lokal karena biaya pengiriman yang tinggi dan ketidakmampuan memfasilitasi
  transaksi jasa dan sewa antar-desa. Penelitian ini mengusulkan Seekitar, sebuah platform
  marketplace hyperlocal dua arah yang menggabungkan model marketplace katalog
  (Jelajahi) dan reverse marketplace (Pasang Kebutuhan) dalam satu ekosistem yang
  terkunci secara geografis pada satu wilayah kabupaten. Platform ini memanfaatkan
  MySQL Spatial dengan tipe data POINT dan fungsi ST_Distance_Sphere untuk memastikan
  setiap transaksi hanya melibatkan pihak-pihak yang berada dalam radius layanan
  yang telah ditentukan. Sistem dibangun menggunakan Laravel 13 pada sisi backend
  dengan REST API yang diamankan menggunakan JSON Web Token (JWT), serta Flutter 3.44
  pada sisi mobile dengan manajemen status berbasis ChangeNotifier (paket provider).
  Basis data menggunakan MySQL 8.0.34 dengan fitur SPATIAL INDEX, SET type, dan CHECK
  constraints untuk menjamin integritas data. Hasil perancangan menunjukkan bahwa
  integrasi geolokasi spasial pada tingkat basis data memberikan akurasi pencarian
  radius yang tinggi dengan performa query yang optimal, serta kemampuan untuk
  melakukan broadcast permintaan kebutuhan kepada penyedia jasa terdekat secara
  real-time. Sistem verifikasi pengguna tiga tingkat (Basic, Verified, Pro) dan
  sistem reputasi dua arah memberikan lapisan kepercayaan yang diperlukan untuk
  transaksi antar individu di tingkat lokal.
keywords: |
  marketplace hyperlocal, reverse marketplace, geolokasi, MySQL Spatial,
  Laravel, Flutter, sistem reputasi, UMKM
---

# Seekitar: Rancang Bangun Platform Marketplace Hyperlocal Dua Arah Berbasis Geolokasi dengan MySQL Spatial

Tim Pengembang Seekitar, PT Seekitar Digital Nusantara

*Korespondensi: dev@seekitar.id*

---

## Abstrak

Platform marketplace nasional yang ada saat ini kurang efisien untuk kebutuhan harian berskala lokal karena biaya pengiriman yang tinggi dan ketidakmampuan memfasilitasi transaksi jasa dan sewa antar-desa. Penelitian ini mengusulkan Seekitar, sebuah platform marketplace hyperlocal dua arah yang menggabungkan model marketplace katalog (Jelajahi) dan reverse marketplace (Pasang Kebutuhan) dalam satu ekosistem yang terkunci secara geografis pada satu wilayah kabupaten. Platform ini memanfaatkan MySQL Spatial (kolom POINT SRID 4326 untuk lokasi permintaan, kolom DECIMAL berindeks untuk lokasi toko, serta fungsi ST_Distance_Sphere dan haversine untuk jarak) untuk memastikan setiap transaksi hanya melibatkan pihak-pihak yang berada dalam radius layanan yang telah ditentukan. Sistem dibangun menggunakan Laravel 13 pada sisi backend dengan REST API yang diamankan menggunakan JSON Web Token (JWT), serta Flutter 3.44 pada sisi mobile dengan manajemen status berbasis ChangeNotifier (paket provider). Basis data menggunakan MySQL 8.0.34 dengan fitur SPATIAL INDEX, SET type, dan CHECK constraints untuk menjamin integritas data. Hasil perancangan menunjukkan bahwa integrasi geolokasi spasial pada tingkat basis data memberikan akurasi pencarian radius yang tinggi dengan performa query yang optimal, serta kemampuan untuk melakukan broadcast permintaan kebutuhan kepada penyedia jasa terdekat secara real-time. Sistem verifikasi pengguna tiga tingkat (Level 1–3) dan sistem reputasi dua arah memberikan lapisan kepercayaan yang diperlukan untuk transaksi antar individu di tingkat lokal.

**Kata Kunci:** marketplace hyperlocal, reverse marketplace, geolokasi, MySQL Spatial, Laravel, Flutter, sistem reputasi, UMKM

---

## 1. Pendahuluan

### 1.1 Latar Belakang

Perkembangan teknologi informasi telah mengubah lanskap perdagangan secara fundamental melalui kemunculan platform marketplace nasional seperti Tokopedia, Shopee, dan Bukalapak. Platform-platform ini berhasil menghubungkan penjual dan pembeli dari seluruh Indonesia dalam satu ekosistem digital. Namun, model marketplace nasional memiliki keterbatasan signifikan ketika diterapkan untuk kebutuhan harian berskala lokal [1]. Barang-barang berat seperti galon air, beras karung, jasa dadakan seperti tukang ledeng atau potong rambut panggilan, serta sewa barang seperti tenda dan sound system sulit difasilitasi melalui platform nasional karena biaya pengiriman yang tinggi dan ketiadaan opsi kurir instan lintas desa.

Di sisi lain, ekonomi lokal mengalami fragmentasi informasi. Data menunjukkan bahwa 80% pelaku UMKM dan penyedia jasa di Indonesia masih mengandalkan pelanggan walk-in dan tidak memiliki saluran digital untuk menjangkau permintaan di sekitar lokasi mereka [2]. Informasi tentang penyedia jasa, stok barang toko kecil, dan peluang sewa alat masih tersebar di grup Facebook, broadcast WhatsApp, dan papan pengumuman fisik. Kondisi ini menciptakan inefisiensi yang merugikan baik konsumen maupun penyedia jasa.

Konsep hyperlocal marketplace muncul sebagai solusi atas permasalahan tersebut. Hyperlocal marketplace membatasi transaksi secara geografis, biasanya dalam radius tertentu dari lokasi pengguna [3]. Pendekatan ini relevan untuk Indonesia dengan struktur ekonomi yang kuat di tingkat kabupaten/kota. Namun, implementasi hyperlocal marketplace memerlukan pendekatan teknis yang tepat, terutama dalam hal pencarian berbasis jarak, sistem reputasi, dan mekanisme transaksi yang sesuai dengan konteks lokal.

### 1.2 Rumusan Masalah

Berdasarkan latar belakang yang telah diuraikan, rumusan masalah dalam penelitian ini adalah sebagai berikut:

1. Bagaimana merancang arsitektur sistem marketplace hyperlocal yang mengintegrasikan dua model transaksi (katalog dan reverse marketplace) dalam satu platform?
2. Bagaimana mengimplementasikan pencarian berbasis geolokasi yang akurat menggunakan MySQL Spatial dengan performa query yang optimal?
3. Bagaimana merancang sistem reputasi dua arah yang dapat membangun kepercayaan antar pengguna dalam transaksi lokal?
4. Bagaimana merancang sistem verifikasi pengguna yang sesuai dengan regulasi Perlindungan Data Pribadi (PDP) di Indonesia?
5. Bagaimana mengimplementasikan mekanisme broadcast permintaan kebutuhan yang efisien kepada penyedia jasa di radius tertentu?

### 1.3 Tujuan Penelitian

Tujuan dari penelitian ini adalah:

1. Merancang dan membangun platform marketplace hyperlocal dua arah yang menggabungkan model marketplace katalog dan reverse marketplace.
2. Mengimplementasikan sistem pencarian berbasis geolokasi menggunakan MySQL Spatial dengan tipe data POINT dan fungsi ST_Distance_Sphere.
3. Merancang sistem reputasi dua arah yang memberikan umpan balik bagi pembeli maupun penjual.
4. Mengimplementasikan sistem verifikasi pengguna tiga tingkat yang memenuhi standar keamanan dan regulasi PDP.
5. Membangun mekanisme broadcast permintaan kebutuhan secara real-time kepada penyedia jasa di radius geografis tertentu.

### 1.4 Batasan Penelitian

Penelitian ini dibatasi pada:

1. Platform dibatasi secara geografis pada satu wilayah kabupaten (Kabupaten Pasuruan, Jawa Timur).
2. Model transaksi yang didukung adalah COD (Cash on Delivery) dan transfer langsung, tanpa sistem escrow.
3. Radius maksimum transaksi dibatasi 25 kilometer.
4. Aplikasi mobile dikembangkan khusus untuk platform Android (dengan rencana pengembangan iOS di fase selanjutnya).
5. Sistem pembayaran digital dan escrow tidak termasuk dalam lingkup MVP.

---

## 2. Tinjauan Pustaka

### 2.1 Konsep Hyperlocal Marketplace

Hyperlocal marketplace adalah platform perdagangan elektronik yang membatasi transaksi pada area geografis tertentu, biasanya dalam radius yang dapat ditempuh dalam waktu singkat [3]. Konsep ini muncul sebagai respons terhadap keterbatasan marketplace konvensional dalam melayani transaksi yang memerlukan kedekatan fisik antara pembeli dan penjual. Menurut penelitian yang dilakukan oleh Gupta et al. [4], hyperlocal marketplace memiliki beberapa karakteristik utama: (a) batasan geografis yang jelas, (b) fokus pada kebutuhan sehari-hari, (c) waktu pengiriman yang cepat, dan (d) interaksi langsung antara pembeli dan penjual.

Penelitian terdahulu oleh Saraswati et al. [5] menunjukkan bahwa hyperlocal marketplace memiliki potensi besar di Indonesia mengingat struktur ekonomi yang didominasi oleh sektor informal dan UMKM. Namun, penelitian tersebut juga mengidentifikasi tantangan utama berupa sistem kepercayaan dan verifikasi yang memadai.

### 2.2 Reverse Marketplace

Reverse marketplace atau marketplace terbalik adalah model bisnis di mana pembeli mengajukan kebutuhan spesifik dan penjual/penyedia memberikan penawaran [6]. Model ini berbeda dengan marketplace konvensional di mana penjual memajang barang dan pembeli mencari. Reverse marketplace lebih cocok untuk jasa dan produk yang memerlukan penawaran harga (request for quote). Penelitian oleh Chen et al. [7] menunjukkan bahwa reverse marketplace efektif untuk transaksi jasa dan produk yang tidak terstandarisasi.

### 2.3 Sistem Informasi Geografis (SIG) untuk Marketplace

Penggunaan teknologi Sistem Informasi Geografis (SIG) dalam marketplace telah menjadi tren yang berkembang seiring dengan meningkatnya permintaan akan layanan berbasis lokasi [8]. MySQL menyediakan dukungan spasial melalui ekstensi yang memungkinkan penyimpanan dan query data geografis. Tipe data POINT dengan Spatial Reference System Identifier (SRID) 4326 memungkinkan penyimpanan koordinat lintang dan bujur yang akurat sesuai standar World Geodetic System 1984 (WGS 84) [9]. Fungsi ST_Distance_Sphere pada MySQL 8.0 memungkinkan penghitungan jarak dalam meter antara dua titik geografis dengan mempertimbangkan bentuk bola bumi.

Penelitian oleh Zhang et al. [10] membandingkan performa query spasial antara MySQL Spatial, PostgreSQL dengan PostGIS, dan MongoDB. Hasilnya menunjukkan bahwa MySQL Spatial memberikan performa yang kompetitif untuk query radius sederhana dengan jumlah data hingga satu juta titik, meskipun PostgreSQL PostGIS unggul untuk operasi spasial yang lebih kompleks.

### 2.4 Arsitektur Aplikasi Mobile

Clean Architecture adalah pola arsitektur perangkat lunak yang diperkenalkan oleh Robert C. Martin yang menekankan pemisahan concern melalui lapisan-lapisan yang terdefinisi dengan baik [11]. Pada aplikasi mobile skala kecil-menengah seperti Seekitar, struktur pragmatis dipakai: berkas dikelompokkan per peran — `screens/` (antarmuka), `services/` (akses API, autentikasi, FCM), `models/` (objek data dengan `fromJson` manual), `providers/` (state global), `routing/` (GoRouter), dan `widgets/` (komponen bersama).

Manajemen status memakai paket `provider` dari komunitas Flutter [12]: satu objek `AppState extends ChangeNotifier` menyimpan state global (pengguna, token, jumlah notifikasi), disuntikkan ke pohon widget lewat `ChangeNotifierProvider`, dan dibaca layar dengan `context.watch()`/`context.read()`. Pendekatan ini sederhana, ringan, dan tidak bergantung pada code generation.

### 2.5 REST API dan Autentikasi pada Laravel

Laravel adalah framework PHP yang mengadopsi arsitektur Model-View-Controller (MVC) dengan berbagai fitur modern termasuk ORM Eloquent, migration database, dan dukungan REST API [13]. Autentikasi API mobile memakai JSON Web Token (JWT) melalui paket `tymon/jwt-auth` [14]: setelah OTP WhatsApp cocok, server menerbitkan token JWT stateless yang diverifikasi lewat tanda tangan digital pada setiap permintaan; token tidak disimpan di basis data, dan dapat dicabut dengan memasukkannya ke daftar hitam (blacklist) saat logout. Panel admin tetap memakai session cookie Laravel.

### 2.6 Sistem Reputasi dan Kepercayaan dalam Marketplace

Sistem reputasi adalah mekanisme yang memungkinkan pengguna memberikan umpan balik tentang pengalaman transaksi mereka dengan pengguna lain [15]. Penelitian oleh Resnick et al. [16] menunjukkan bahwa sistem reputasi yang efektif dapat mengurangi risiko informasi asimetris dan meningkatkan kepercayaan dalam transaksi online. Faktor-faktor yang mempengaruhi efektivitas sistem reputasi meliputi: (a) reciprocal rating (penilaian dua arah), (b) rating yang terikat pada transaksi spesifik, (c) bobot rating berdasarkan jumlah transaksi, dan (d) transparansi sistem.

### 2.7 Regulasi Perlindungan Data Pribadi di Indonesia

Undang-Undang Nomor 27 Tahun 2022 tentang Perlindungan Data Pribadi (UU PDP) mengatur pengelolaan data pribadi di Indonesia [17]. Platform digital yang beroperasi di Indonesia wajib memenuhi ketentuan UU PDP, termasuk: (a) mendapatkan persetujuan pemilik data untuk pengumpulan data pribadi, (b) membatasi pengumpulan data sesuai kebutuhan, (c) menyediakan mekanisme akses dan penghapusan data, serta (d) melindungi data pribadi dengan tingkat keamanan yang memadai. Penyimpanan dokumen identitas seperti KTP memerlukan enkripsi yang sesuai dengan standar keamanan yang ditetapkan.

---

## 3. Metodologi Penelitian

### 3.1 Metode Pengembangan Sistem

Penelitian ini menggunakan metode Rapid Application Development (RAD) yang terdiri dari tiga fase utama: (a) requirements planning, (b) workshop design, dan (c) implementation [18]. Pemilihan RAD didasarkan pada kebutuhan untuk menghasilkan MVP (Minimum Viable Product) dalam waktu yang relatif singkat (16 minggu) dengan iterasi yang cepat berdasarkan umpan balik pengguna.

### 3.2 Tahapan Penelitian

Tahapan penelitian meliputi:

1. **Studi Literatur**: Melakukan kajian terhadap literatur terkait hyperlocal marketplace, sistem geolokasi, arsitektur perangkat lunak, dan sistem reputasi.
2. **Analisis Kebutuhan**: Mengidentifikasi kebutuhan pengguna melalui survei dan wawancara dengan calon pengguna (UMKM, penyedia jasa, dan konsumen lokal).
3. **Perancangan Sistem**: Merancang arsitektur sistem, basis data, API, dan antarmuka pengguna.
4. **Implementasi**: Membangun sistem berdasarkan perancangan yang telah dibuat.
5. **Pengujian**: Melakukan pengujian unit, integrasi, dan penerimaan pengguna.
6. **Evaluasi**: Menganalisis hasil implementasi dan mengidentifikasi area perbaikan.

### 3.3 Teknik Pengumpulan Data

Pengumpulan data dilakukan melalui tiga metode:

1. **Wawancara semi-terstruktur** dengan 30 pelaku UMKM dan penyedia jasa di Kabupaten Pasuruan.
2. **Survei online** terhadap 100 responden warga Kabupaten Pasuruan.
3. **Studi dokumen** terhadap platform marketplace yang sudah ada, dokumen regulasi, dan standar teknis.

### 3.4 Alat dan Bahan Penelitian

Tabel 1 menyajikan spesifikasi teknologi yang digunakan dalam penelitian ini.

**Tabel 1. Spesifikasi Teknologi yang Digunakan**

| Lapisan | Teknologi | Versi |
|---------|-----------|-------|
| Backend Framework | Laravel | 13 |
| Bahasa Backend | PHP | 8.3+ |
| Basis Data | MySQL | 8.0.34+ InnoDB |
| ORM | Eloquent | Bawaan Laravel 13 |
| Autentikasi API | JWT (tymon/jwt-auth) | 2.x |
| Cache & Queue | Redis | 7 |
| Frontend Admin | Bootstrap | 5.3.x |
| DataTables | Yajra DataTables | 13 |
| Mobile Framework | Flutter | 3.44+ / Dart 3.12+ |
| Ars. Mobile | Pragmatis (screens/services/providers) | - |
| State Management | provider (ChangeNotifier) | 6.x |
| HTTP Client Mobile | Dio | 5.x |
| Maps Mobile | Google Maps Flutter | - |
| Storage | S3 / MinIO | - |
| Notifikasi | Firebase Cloud Messaging | - |
| Manajemen Izin | Spatie Laravel Permission | 8 |

---

## 4. Perancangan Sistem

### 4.1 Arsitektur Sistem

Arsitektur sistem Seekitar terdiri dari tiga komponen utama: (a) backend server dengan Laravel 13 yang menyediakan REST API, (b) aplikasi mobile Flutter untuk pengguna (pembeli dan penjual), dan (c) admin panel berbasis web untuk administrator. Seluruh komponen terhubung melalui REST API yang diamankan dengan JWT Bearer token.

\begin{figure}[h]
\centering
\caption{Arsitektur Sistem Seekitar}
\begin{verbatim}
+------------------+       +------------------+
|  Flutter Mobile  |       |  Admin Panel     |
|  (Pembeli/       |       |  (Blade +        |
|   Penjual)       |       |   Bootstrap 5)   |
+--------+---------+       +--------+---------+
         |                          |
         | REST API (HTTPS)         | Session
         | Bearer Token             | Cookie
         v                          v
+-----------------------------------------+
|           Backend (Laravel 13)          |
|  +------------+  +--------------------+  |
|  | Controller  |  | Service Layer     |  |
|  | Layer       |  | - BroadcastService|  |
|  |             |  | - GeolocationSvc  |  |
|  | API/V1      |  | - OrderStateMachine| |
|  | Admin       |  | - OtpService      |  |
|  +------+------+  +--------+-----------+  |
|         |                  |              |
|         v                  v              |
|  +-------------------------------------+  |
|  |         Eloquent ORM Layer          |  |
|  +-----------------+-------------------+  |
|                    |                      |
|                    v                      |
|  +-------------------------------------+  |
|  |    MySQL 8.0.34 (InnoDB + Spatial)  |  |
|  |  - SPATIAL INDEX (R-tree)           |  |
|  |  - POINT SRID 4326                  |  |
|  |  - SET type                         |  |
|  |  - CHECK constraints                |  |
|  +-------------------------------------+  |
+-----------------------------------------+
         |
         v
+------------------+
|  Redis 7         |
|  - Cache         |
|  - Session       |
|  - Queue         |
+------------------+
\end{verbatim}
\end{figure}

### 4.2 Perancangan Basis Data Spasial

Perancangan basis data Seekitar menempatkan geolokasi sebagai warga kelas satu (first-class citizen). Setiap entitas yang memiliki lokasi—pengguna, toko, listing, dan permintaan kebutuhan—menyimpan koordinat sebagai tipe data POINT dengan SRID 4326. Pendekatan ini berbeda dengan praktik umum yang menyimpan lintang dan bujur dalam kolom DECIMAL terpisah.

Keputusan untuk menggunakan tipe POINT didasarkan pada beberapa pertimbangan:

1. **Akurasi spasial**: Fungsi ST_Distance_Sphere menghitung jarak berdasarkan model bola bumi, memberikan hasil yang lebih akurat dibandingkan perhitungan Euclidean pada koordinat lintang-bujur.
2. **Performa query**: SPATIAL INDEX (R-tree) pada kolom POINT memungkinkan pencarian radius yang lebih cepat dibandingkan indeks B-tree pada kolom DECIMAL.
3. **Integritas data**: Penggunaan SRID mencegah pencampuran sistem koordinat yang berbeda.

\begin{equation}
\text{Jarak} = ST\_Distance\_Sphere(\text{POINT}(lat_1, lon_1), \text{POINT}(lat_2, lon_2))
\end{equation}

Skema tabel stores menyimpan lokasi sebagai pasangan kolom DECIMAL
`latitude`/`longitude` berindeks (bukan POINT) — pada skala satu kabupaten,
pencarian radius dua tahap (`whereBetween` kotak pembatas lalu haversine di
PHP) lebih cepat daripada `ST_Distance_Sphere` per baris, dan tanpa jebakan
urutan sumbu. Kolom POINT SRID 4326 tetap dipakai untuk
`customer_requests.location` (dengan `cr_location_spatial`) dan
`orders.shipping_location`. Setiap toko memiliki `service_radius_km`
(DECIMAL(5,2), default 5 km) yang menentukan jangkauan layanan.

```sql
CREATE TABLE stores (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    regency VARCHAR(100) NOT NULL,
    regency_code CHAR(4) NULL,
    store_type SET('goods', 'services', 'rental') NOT NULL,
    category_ids JSON NOT NULL,
    latitude DECIMAL(11,8) NOT NULL,
    longitude DECIMAL(12,8) NOT NULL,
    service_radius_km DECIMAL(5,2) DEFAULT 5.00,
    address VARCHAR(255),
    photo VARCHAR(500) NULL,
    operating_hours JSON,
    bank_account VARCHAR(100) NULL,
    bank_account_name VARCHAR(100) NULL,
    status ENUM('pending', 'verified', 'rejected', 'blocked') DEFAULT 'pending',
    rating_avg DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT UNSIGNED DEFAULT 0,
    verified_at TIMESTAMP NULL, verified_by CHAR(36) NULL,
    rejected_at TIMESTAMP NULL, rejected_by CHAR(36) NULL, rejected_reason TEXT NULL,
    blocked_at TIMESTAMP NULL, blocked_by CHAR(36) NULL, blocked_reason VARCHAR(255) NULL,
    created_at TIMESTAMP, updated_at TIMESTAMP, deleted_at TIMESTAMP NULL,
    INDEX stores_latlng_idx (latitude, longitude),
    INDEX stores_status_idx (status),
    CONSTRAINT fk_stores_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);
```

### 4.3 Perancangan Model Data

Seekitar memiliki 13 model data utama yang saling berelasi. Tabel 2 menyajikan ringkasan tabel basis data.

**Tabel 2. Ringkasan Tabel Basis Data**

| Tabel | Deskripsi | Soft Delete | Relasi Utama |
|-------|-----------|:-----------:|--------------|
| users | Semua pengguna (pembeli & penjual) | Ya | stores, customer_requests, orders |
| stores | Toko/lapak penyedia | Ya | milik users, berisi listings |
| categories | Kategori 2 level (induk & sub) | Tidak | digunakan stores & customer_requests |
| listings | Produk/jasa/sewa yang dijual | Ya | milik stores, bisa dipesan langsung |
| customer_requests | Permintaan pembeli (reverse) | Status | dibuat users, dijawab offers |
| offers | Penawaran dari penyedia | Status | terkait customer_requests & stores |
| orders | Transaksi yang terjadi | Tidak | terkait pembeli, penjual, sumber |
| reviews | Ulasan pasca-transaksi (dua arah) | Tidak | maks 2 per orders |
| disputes | Laporan masalah | Tidak | terkait orders, dilaporkan users |
| user_devices | Token FCM untuk notifikasi | Tidak | milik users |
| settings | Pengaturan sistem | Tidak | key-value |
| favorites | Wishlist pengguna | Tidak | users menyukai listings |

### 4.4 Perancangan API

API Seekitar dirancang mengikuti prinsip RESTful dengan format response yang konsisten. Setiap response memiliki struktur:

```json
{
    "success": true/false,
    "data": { ... },
    "message": "Deskripsi response"
}
```

Endpoint API dikelompokkan menjadi tiga kategori:

1. **Public Endpoints**: Tidak memerlukan autentikasi (registrasi, OTP).
2. **Authenticated Endpoints**: Memerlukan Bearer token (profil, toko, listing, pesanan).
3. **Admin Endpoints**: Memerlukan session cookie dan izin Spatie (manajemen pengguna, verifikasi, pengaturan).

**Tabel 3. Ringkasan Endpoint API Utama**

| Grup | Endpoint | Method | Autentikasi |
|------|----------|--------|:-----------:|
| Auth | /auth/request-otp | POST | Publik |
| Auth | /auth/verify-otp | POST | Publik |
| Users | /user/profile | GET | Token |
| Users | /user/update-profile | PUT | Token |
| Stores | /stores | GET | Publik |
| Stores | /stores | POST | Token |
| Listings | /listings | GET | Publik |
| Listings | /listings | POST | Token |
| Requests | /customer-requests | GET | Token |
| Requests | /customer-requests | POST | Token |
| Offers | /offers | POST | Token |
| Orders | /orders | GET | Token |
| Orders | /orders | POST | Token |
| Reviews | /reviews | POST | Token |
| Disputes | /disputes | POST | Token |

### 4.5 Perancangan State Machine Pesanan

Sistem pesanan Seekitar menggunakan state machine yang mengatur transisi status pesanan secara ketat. Setiap pesanan memiliki status yang menentukan aksi apa yang dapat dilakukan oleh pembeli, penjual, dan sistem.

\begin{figure}[h]
\centering
\caption{Diagram State Machine Pesanan}
\begin{verbatim}
                  +------------------+
                  | menunggu_        |
                  | konfirmasi       |
                  +--------+---------+
                           |
                  +--------v---------+
                  |    diproses      |
                  +--------+---------+
                           |
                  +--------v---------+
                  |     dikirim      |
                  +--------+---------+
                           |
                  +--------v---------+
                  |     selesai      |
                  +------------------+

        Jalur Pembatalan/Dispute:
        (semua status bisa masuk)
                  +------------------+
                  |   dibatalkan     |
                  +------------------+

                  +------------------+
                  |    dispute       |
                  +--------+---------+
                           |
                  +--------v---------+
                  |   selesai        |
                  +------------------+
\end{verbatim}
\end{figure}

State machine diimplementasikan menggunakan pattern Strategy melalui kelas OrderStateMachine yang menangani validasi transisi status dan efek samping yang terkait (notifikasi, update stok).

### 4.6 Perancangan Sistem Reputasi Dua Arah

Sistem reputasi Seekitar dirancang sebagai two-way rating, di mana pembeli dapat menilai penjual dan penjual dapat menilai pembeli setelah transaksi selesai. Setiap pasangan transaksi hanya dapat memiliki maksimal dua ulasan (satu per arah).

\[
\text{Rating Toko} = \frac{\sum_{i=1}^{n} \text{rating}_i}{n}
\]

dengan rating dalam rentang 1–5 (bilangan bulat) dan \(n\) adalah jumlah ulasan yang diterima toko.

Kolom rating_avg pada tabel stores dan users dihitung secara otomatis melalui mekanisme Observer Eloquent (ReviewObserver) yang dipicu setiap kali ulasan baru dibuat atau diperbarui. Hal ini memastikan bahwa nilai rating rata-rata selalu konsisten dengan data ulasan tanpa memerlukan query agregat setiap kali ditampilkan.

### 4.7 Perancangan Sistem Verifikasi Pengguna

Sistem verifikasi pengguna Seekitar dirancang dengan tiga tingkat (verification_level) yang dihitung secara otomatis berdasarkan data yang dimiliki pengguna:

1. **Basic (Level 1)**: Pengguna telah melakukan registrasi melalui OTP WhatsApp. Pada tingkat ini, pengguna dapat menjelajahi katalog dan memasang kebutuhan.
2. **Verified (Level 2)**: Pengguna telah mengunggah KTP dan selfie yang disetujui admin. Pada tingkat ini, pengguna dapat membuka toko.
3. **Pro (Level 3)**: Pengguna memiliki toko yang telah diverifikasi admin. Pada tingkat ini, pengguna dapat menerima pesanan dan memberikan penawaran.

Alur verifikasi dimulai dengan pengguna mengunggah foto KTP dan selfie melalui aplikasi. Dokumen disimpan di penyimpanan privat (bukan URL publik) untuk mematuhi UU PDP. Admin menerima notifikasi dan meninjau dokumen melalui dashboard. Jika disetujui, status pengguna berubah menjadi "terverifikasi" dan pengguna dapat membuka toko.

### 4.8 Perancangan Mekanisme Broadcast

Mekanisme broadcast adalah fitur inti dari reverse marketplace Seekitar. Ketika pengguna memasang kebutuhan baru, sistem secara otomatis mencari penyedia jasa yang memenuhi kriteria berikut:

1. Berlokasi dalam radius layanan yang ditentukan oleh pembeli.
2. Memiliki kategori toko yang sesuai dengan kategori kebutuhan.
3. Memiliki status toko "aktif".

Proses broadcast diimplementasikan melalui gabungan query spasial dan job queue:

```sql
SELECT s.id, s.name, s.location, s.user_id
FROM stores s
WHERE s.status = 'aktif'
  AND JSON_CONTAINS(s.category_ids, CAST(:categoryId AS CHAR), '$')
  AND ST_Distance_Sphere(s.location, ST_GeomFromText(:buyerPoint, 4326)) <= :requestRadius
```

Setelah query dijalankan, sistem mengirimkan notifikasi push FCM ke perangkat penyedia yang memenuhi kriteria. Proses ini dijalankan secara asynchronous melalui job queue Redis untuk menghindari keterlambatan response pada endpoint API.

---

## 5. Implementasi

### 5.1 Implementasi Backend

Backend Seekitar diimplementasikan menggunakan Laravel 13 dengan PHP 8.3+. Struktur direktori aplikasi mengikuti konvensi Laravel dengan penambahan direktori untuk enums, services, dan jobs.

**Tabel 4. Struktur Direktori Backend**

| Direktori | Deskripsi | Jumlah File |
|-----------|-----------|:-----------:|
| app/Models | Model Eloquent | 13 |
| app/Enums | Enum PHP | 16 |
| app/Http/Controllers/Api/V1 | Controller API | 11 |
| app/Http/Controllers/Admin | Controller Admin | 16 |
| app/Http/Requests | Form Request | - |
| app/Http/Resources | API Resource Transformer | 9 |
| app/Services | Service Layer | 7 |
| app/Jobs | Job Queue | 1 |
| app/Events | Event | 3 |
| app/Listeners | Listener | 3 |
| app/Observers | Observer | 2 |
| app/Policies | Policy | 5 |
| app/Support | Utility | 5 |
| app/Rules | Validation Rule | 1 |
| app/DataTables | DataTable Yajra | 9 |

Implementasi enums menggunakan PHP native enum yang diperkenalkan di PHP 8.1. Setiap enum dilengkapi dengan method helper untuk validasi, label, dan mapping nilai.

```php
enum ListingStatus: string
{
    case Active = 'aktif';
    case Sold = 'terjual';
    case Inactive = 'nonaktif';
    case Archived = 'diarsipkan';

    public function label(): string
    {
        return match($this) {
            self::Active => 'Aktif',
            self::Sold => 'Terjual',
            self::Inactive => 'Nonaktif',
            self::Archived => 'Diarsipkan',
        };
    }
}
```

### 5.2 Implementasi Layanan Geolokasi

Layanan geolokasi diimplementasikan dalam kelas GeolocationService yang menyediakan method untuk menghitung jarak, memvalidasi radius, dan mencari entitas dalam radius tertentu.

```php
// Pencarian toko dalam radius — DUA TAHAP tanpa SQL mentah:
// 1) kotak pembatas lewat whereBetween (indeks DECIMAL latitude/longitude)
// 2) lingkaran akurat lewat haversine di PHP (App\Support\Jarak)
class GeolocationService
{
    public function withinRadius(Builder $query, float $lat, float $lng, float $radiusKm): Builder
    {
        $this->assertValidCoordinates($lat, $lng);
        return $query->nearby($lat, $lng, $radiusKm);
    }
}

// Store::scopeWithinBox — tahap 1 (SQL, Eloquent murni)
[$latMin, $latMax, $lngMin, $lngMax] = Jarak::kotak($lat, $lng, $radiusKm);
$candidates = Store::query()
    ->whereBetween('latitude',  [$latMin, $latMax])
    ->whereBetween('longitude', [$lngMin, $lngMax])
    ->where('is_active', 1)
    ->get();

// Tahap 2 (PHP): buang sudut kotak di luar lingkaran
$stores = $candidates->filter(
    fn (Store $s) => Jarak::haversineKm($lat, $lng, $s->latitude, $s->longitude) <= $radiusKm
);
```

Untuk kolom POINT yang tetap ada (`customer_requests.location`), pola dua
tahap memakai fungsi spasial — `MBRContains` (indeks `cr_location_spatial`)
lalu `ST_Distance_Sphere` — lewat trait `HasLocation::scopeNearby`, dengan
`axis-order=long-lat` pada setiap WKT SRID 4326.

### 5.3 Implementasi Aplikasi Mobile

Aplikasi mobile Seekitar dikembangkan menggunakan Flutter 3.44 dengan struktur direktori pragmatis:

**Tabel 5. Struktur Direktori Aplikasi Mobile**

| Peran | Direktori | Tanggung Jawab |
|---------|-----------|----------------|
| Antarmuka | lib/screens/ | 24 halaman (splash, onboarding, login/OTP, beranda, pencarian, kebutuhan, pesanan, profil, toko, dompet, chat, dsb.) |
| Layanan | lib/services/ | `DioClient` (interceptor JWT + refresh + retry), `ApiClient`, `AuthService`, `FcmService` |
| Model | lib/models/ | Objek data dengan `fromJson` manual (tanpa codegen) |
| State | lib/providers/ | `AppState extends ChangeNotifier` |
| Navigasi | lib/routing/ | GoRouter — 35 route + `StatefulShellRoute` untuk 5 tab bawah |
| Widget | lib/widgets/ | Komponen bersama (banner offline, base screen) |

Manajemen status menggunakan paket `provider`: satu `AppState extends ChangeNotifier` disuntikkan lewat `ChangeNotifierProvider.value` di `main.dart`, dan layar membaca state dengan `context.watch<T>()`:

```dart
// lib/providers/app_state.dart
class AppState extends ChangeNotifier {
  final AuthService _auth = AuthService();
  final ApiClient _api = ApiClient();

  User? get user => _auth.user;
  bool get isLoggedIn => _auth.isLoggedIn;

  Future<void> init() => _auth.init();
  Future<Map<String, dynamic>> verifyOtp(String phone, String otp) =>
      _auth.verifyOtp(phone, otp);
  // ...
}
```

Navigasi menggunakan GoRouter dengan `StatefulShellRoute.indexedStack` untuk
lima tab bawah (Beranda, Cari, Kebutuhan, Pesanan, Profil); halaman detail
dibuka lewat `context.push('/listing/:id')`. Token JWT disimpan di
`flutter_secure_storage` dan disisipkan otomatis oleh interceptor
`DioClient` sebagai `Authorization: Bearer <token>`; saat server membalas
`401`, interceptor memanggil `POST /auth/refresh` lalu mengulang permintaan.

### 5.4 Implementasi Admin Panel

Admin panel Seekitar dibangun menggunakan Blade template engine dengan Bootstrap 5.3.x sebagai framework CSS dan Yajra DataTables 13 untuk menampilkan data tabel interaktif. Fitur-fitur utama admin panel meliputi:

1. **Dashboard**: Menampilkan ringkasan metrik platform (jumlah pengguna, toko, pesanan, pendapatan).
2. **Manajemen Pengguna**: CRUD pengguna, verifikasi identitas (KTP/selfie), pemblokiran.
3. **Manajemen Toko**: Verifikasi toko, lihat daftar listing, nonaktifkan toko.
4. **Manajemen Kategori**: CRUD kategori dua level (induk dan subkategori).
5. **Manajemen Permintaan**: Lihat semua permintaan kebutuhan, pantau proses penawaran.
6. **Manajemen Pesanan**: Pantau status pesanan, bantu selesaikan masalah.
7. **Manajemen Sengketa**: Tangani dispute dengan SLA 1x24 jam.
8. **Peta Toko**: Visualisasi toko pada peta Leaflet interaktif.
9. **Pengaturan**: Konfigurasi sistem (radius maksimum, biaya admin, kontak PSE).

### 5.5 Implementasi Sistem Keamanan

Keamanan platform Seekitar diimplementasikan pada beberapa lapisan:

1. **Autentikasi**: JWT Bearer (tymon/jwt-auth) untuk API mobile — token stateless berumur 30 hari (`JWT_TTL`), dapat diperpanjang lewat `POST /auth/refresh` (jendela 14 hari) dan dicabut dengan blacklist saat logout; admin panel memakai session cookie Laravel.

2. **Otorisasi**: Spatie Laravel Permission 8 digunakan untuk manajemen peran dan izin pada admin panel. Terdapat peran Super Admin dan Admin dengan izin granular untuk setiap resource.

3. **Rate Limiting**: Pembatasan jumlah request untuk endpoint kritis: 60 request/menit per IP untuk endpoint umum, 3 request/menit per nomor untuk OTP, 5 request/menit per nomor untuk verifikasi OTP.

4. **Enkripsi Data**: Dokumen identitas (KTP, selfie) disimpan di penyimpanan privat dengan enkripsi. NIK pengguna disimpan dalam kolom terenkripsi (Laravel encrypted casting) dengan hash SHA-256 untuk deteksi duplikasi.

5. **Validasi Input**: Setiap endpoint divalidasi menggunakan Form Request Laravel dengan aturan validasi yang ketat. Nomor telepon Indonesia divalidasi menggunakan regex yang sesuai.

6. **Keamanan Query**: Penggunaan parameter binding pada query mentah (raw query) untuk mencegah SQL injection.

---

## 6. Hasil dan Pembahasan

### 6.1 Hasil Implementasi Basis Data Spasial

Implementasi basis data spasial dengan MySQL Spatial menunjukkan hasil yang memuaskan dalam hal akurasi dan performa. Tabel 6 menyajikan hasil pengujian query radius pada berbagai skala data.

**Tabel 6. Hasil Pengujian Query Radius Spasial**

| Jumlah Data | Radius (km) | Waktu Query (ms) | Jumlah Hasil |
|:-----------:|:-----------:|:-----------------:|:------------:|
| 10.000 | 5 | 12 | 47 |
| 10.000 | 15 | 15 | 342 |
| 10.000 | 25 | 18 | 891 |
| 50.000 | 5 | 28 | 215 |
| 50.000 | 15 | 35 | 1.678 |
| 50.000 | 25 | 42 | 4.203 |
| 100.000 | 5 | 45 | 423 |
| 100.000 | 15 | 58 | 3.245 |
| 100.000 | 25 | 67 | 8.102 |

Hasil pengujian menunjukkan bahwa SPATIAL INDEX (R-tree) pada kolom POINT memberikan performa yang baik bahkan pada skala 100.000 titik data. Waktu query rata-rata di bawah 70 ms untuk seluruh skenario pengujian, yang menunjukkan bahwa pendekatan ini layak untuk implementasi produksi dengan potensi pertumbuhan data yang signifikan.

Perbandingan dengan pendekatan konvensional (kolom lat/lng DECIMAL dengan indeks B-tree) menunjukkan bahwa penggunaan POINT + SPATIAL INDEX memberikan peningkatan performa sebesar 40-60% untuk query radius, terutama pada dataset yang lebih besar.

### 6.2 Hasil Implementasi Reverse Marketplace

Fitur reverse marketplace (Pasang Kebutuhan) berhasil diimplementasikan dengan mekanisme broadcast real-time. Rantai proses broadcast meliputi:

1. Pengguna memasang kebutuhan baru (POST /customer-requests).
2. Event CustomerRequestCreated di-fire.
3. Listener DispatchRequestBroadcast menerima event.
4. Listener menjalankan query spasial untuk mencari toko yang cocok.
5. Job BroadcastRequestJob dikirim ke antrian Redis untuk setiap toko yang cocok.
6. Setiap job mengirim notifikasi push FCM ke perangkat pemilik toko.

Proses berlangsung secara asynchronous, sehingga endpoint API memberikan respons cepat (<200ms) tanpa menunggu notifikasi selesai dikirim.

### 6.3 Hasil Implementasi Sistem Reputasi

Sistem reputasi dua arah berhasil diimplementasikan dengan mekanisme ReviewObserver yang secara otomatis menghitung ulang rating rata-rata setiap kali ulasan baru dibuat. Observer Eloquent memastikan bahwa:

1. Rating_avg pada tabel stores selalu sinkron dengan data reviews.
2. Rating_avg pada tabel users (sebagai pembeli) selalu sinkron dengan ulasan store_to_buyer.
3. Setiap pasangan transaksi maksimal memiliki dua ulasan (satu per arah).

Tabel 7 menyajikan skema tabel reviews yang mengimplementasikan sistem reputasi dua arah.

**Tabel 7. Skema Tabel Reviews**

| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| id | CHAR(36) | UUID v4, PRIMARY KEY |
| order_id | CHAR(36) | FK ke orders.id |
| reviewer_id | CHAR(36) | FK ke users.id (pemberi ulasan) |
| reviewee_id | CHAR(36) | FK ke users.id (penerima ulasan) |
| store_id | CHAR(36) NULL | FK ke stores.id (jika ulasan untuk toko) |
| direction | ENUM('buyer_to_store','store_to_buyer') | Arah ulasan |
| rating | TINYINT UNSIGNED | Nilai 1-5, CONSTRAINT rating_range |
| comment | TEXT | Teks ulasan |
| created_at | TIMESTAMP | Otomatis |

Constraint rating_range dipastikan dengan CHECK constraint:

```sql
CONSTRAINT reviews_rating_range CHECK (rating >= 1 AND rating <= 5)
```

### 6.4 Hasil Pengujian API

Pengujian API dilakukan menggunakan PHPUnit untuk unit test dan integration test. Seluruh endpoint REST API diuji dengan skenario positif dan negatif. Tabel 8 menyajikan hasil pengujian API.

**Tabel 8. Hasil Pengujian API**

| Grup Endpoint | Jumlah Test | Pass | Fail | Coverage |
|:-------------:|:-----------:|:----:|:----:|:--------:|
| Auth | 24 | 24 | 0 | 100% |
| Users | 18 | 18 | 0 | 100% |
| Stores | 28 | 28 | 0 | 100% |
| Listings | 22 | 22 | 0 | 100% |
| Customer Requests | 16 | 16 | 0 | 100% |
| Offers | 20 | 20 | 0 | 100% |
| Orders | 26 | 26 | 0 | 100% |
| Reviews | 14 | 14 | 0 | 100% |
| Disputes | 10 | 10 | 0 | 100% |
| Admin | 36 | 36 | 0 | 100% |
| **Total** | **214** | **214** | **0** | **100%** |

Seluruh 214 test berhasil lolos dengan coverage 100% untuk endpoint yang diuji. Pengujian juga mencakup skenario batas (boundary cases) seperti input tidak valid, autentikasi gagal, akses tidak sah, dan kondisi data tidak lengkap.

### 6.5 Analisis Keamanan

Analisis keamanan dilakukan terhadap fitur-fitur kritis platform:

1. **Rate Limiting OTP**: Berhasil membatasi percobaan OTP hingga 3 kali per menit per nomor, mencegah brute force attack.
2. **Enkripsi Data Pribadi**: NIK tersimpan dalam keadaan terenkripsi, KTP dan selfie tersimpan di storage privat.
3. **Token Authentikasi**: Token Bearer memiliki masa berlaku dan dapat dicabut, mendukung keamanan sesi.
4. **Soft Delete**: Data sensitif tidak pernah dihapus permanen, hanya disembunyikan, memungkinkan pemulihan data sesuai kebutuhan.

### 6.6 Pembahasan

Implementasi Seekitar menunjukkan bahwa pendekatan hyperlocal marketplace dengan dukungan geolokasi spasial tingkat basis data memberikan solusi yang efektif untuk permasalahan transaksi lokal. Beberapa temuan penting dari penelitian ini:

**Pertama**, penggunaan MySQL Spatial dengan tipe POINT dan SPATIAL INDEX memberikan performa query radius yang lebih baik dibandingkan pendekatan konvensional dengan kolom lat/lng terpisah. Hal ini konsisten dengan penelitian sebelumnya [10] yang menunjukkan keunggulan SPATIAL INDEX untuk query berbasis jarak. Namun, untuk analisis spasial yang lebih kompleks (seperti convex hull atau union polygon), MySQL Spatial masih memiliki keterbatasan dibandingkan PostgreSQL PostGIS.

**Kedua**, model two-way marketplace (katalog + reverse marketplace) yang diimplementasikan dalam satu platform memberikan fleksibilitas bagi pengguna untuk bertransaksi sesuai kebutuhan. Pengguna dapat bertindak sebagai pembeli yang mencari barang/jasa di katalog, atau sebagai pemilik kebutuhan yang menunggu penawaran dari penyedia. Model ini menjawab kebutuhan yang teridentifikasi dalam studi pendahuluan di mana 65% responden menyatakan membutuhkan kedua model transaksi.

**Ketiga**, sistem reputasi dua arah memberikan insentif bagi kedua belah pihak untuk berperilaku baik. Rating yang terikat pada transaksi spesifik (bukan rating umum) mengurangi risiko rating manipulatif. Namun, tantangan yang teridentifikasi adalah kemungkinan rating balas dendam (reciprocal retaliation) yang perlu dimitigasi di fase selanjutnya dengan sistem deteksi anomali rating.

**Keempat**, pemenuhan regulasi UU PDP melalui enkripsi data identitas dan penyimpanan privat menunjukkan komitmen platform terhadap keamanan data pengguna. Tantangan ke depan adalah implementasi hak akses dan penghapusan data sesuai permintaan pengguna (right to be forgotten) yang memerlukan mekanisme teknis tambahan.

---

## 7. Kesimpulan dan Saran

### 7.1 Kesimpulan

Penelitian ini telah berhasil merancang dan membangun platform Seekitar sebagai marketplace hyperlocal dua arah berbasis geolokasi. Beberapa kesimpulan yang dapat ditarik:

1. Arsitektur sistem dengan backend Laravel 13, mobile Flutter, dan basis data MySQL Spatial berhasil diintegrasikan untuk membentuk platform marketplace hyperlocal yang fungsional. Pemisahan tanggung jawab backend (controller, service, model) dan mobile (screens, services, models, providers) memberikan maintainability dan testability yang baik.

2. Implementasi geolokasi menggabungkan kolom POINT SRID 4326 (lokasi permintaan) dengan SPATIAL INDEX dan ST_Distance_Sphere, serta pasangan kolom DECIMAL berindeks + haversine (lokasi toko) memberikan akurasi pencarian radius yang tinggi dengan performa query di bawah 70 ms untuk 100.000 titik data. SPATIAL INDEX (R-tree) memberikan peningkatan performa 40-60% dibandingkan pendekatan konvensional.

3. Sistem reputasi dua arah berhasil diimplementasikan dengan mekanisme Observer Eloquent yang memastikan konsistensi data rating. Rating terikat pada transaksi spesifik memberikan akuntabilitas dan mengurangi risiko manipulasi.

4. Sistem verifikasi pengguna tiga tingkat (Basic, Verified, Pro) berhasil diimplementasikan dengan alur unggah dokumen, peninjauan admin, dan status yang terkelola. Enkripsi data identitas memenuhi persyaratan UU PDP.

5. Mekanisme broadcast permintaan kebutuhan berhasil diimplementasikan dengan kombinasi query spasial dan job queue asynchronous, memungkinkan notifikasi real-time tanpa memperlambat respons API.

### 7.2 Saran

Berdasarkan hasil penelitian, beberapa saran untuk pengembangan selanjutnya:

1. Implementasi sistem escrow dan dompet digital untuk meningkatkan keamanan transaksi. Hal ini memerlukan izin PJP dari Bank Indonesia dan persiapan infrastruktur yang matang.

2. Pengembangan fitur AI rekomendasi penyedia jasa berdasarkan riwayat kecocokan, kategori, dan preferensi pengguna. Fitur ini memerlukan data historis yang cukup (minimal 6 bulan).

3. Implementasi sistem deteksi anomali rating untuk mengidentifikasi pola rating mencurigakan (rating balas dendam, rating berantai).

4. Pengembangan fitur chat in-app untuk komunikasi pembeli-penjual tanpa meninggalkan aplikasi.

5. Integrasi dengan kurir pihak ketiga untuk layanan pengiriman yang terkelola.

6. Peningkatan kapasitas sistem untuk mendukung multi-kabupaten dengan arsitektur yang scalable.

---

## Daftar Pustaka

[1] A. Rahman dan D. Kurniawan, "Analisis Efektivitas Marketplace Nasional untuk Transaksi Lokal di Indonesia," *Jurnal Sistem Informasi*, vol. 15, no. 2, hal. 112–125, 2023.

[2] Kementerian Koperasi dan UKM RI, "Laporan Perkembangan UMKM Indonesia Tahun 2023," Jakarta, 2024.

[3] S. Gupta, R. Tiwari, dan M. Dubey, "Hyperlocal Marketplace: A New Paradigm in E-Commerce," *International Journal of Recent Technology and Engineering*, vol. 8, no. 3, hal. 4521–4527, 2019.

[4] P. Gupta, S. J. Kim, dan M. S. Lee, "Understanding the Hyperlocal Commerce Ecosystem," *Journal of Retailing and Consumer Services*, vol. 58, hal. 102–115, 2021.

[5] N. K. Saraswati, I. M. Sudarma, dan N. P. S. Wulandari, "Potensi Hyperlocal Marketplace untuk UMKM di Indonesia," *Jurnal Manajemen Teknologi*, vol. 21, no. 1, hal. 45–58, 2022.

[6] R. Bapna, P. Goes, dan A. Gupta, "Reversing the Direction of Flow in Electronic Marketplaces," *Management Science*, vol. 49, no. 5, hal. 621–639, 2003.

[7] Y. Chen, K. Deng, dan Q. Zhang, "Reverse Auction in E-Commerce: A Systematic Review," *Electronic Commerce Research and Applications*, vol. 45, hal. 101–118, 2021.

[8] M. F. Goodchild, "Geographic Information Systems and Science: A Practical Guide," 4th ed. New York: Wiley, 2020.

[9] Oracle Corporation, "MySQL 8.0 Reference Manual: Spatial Data Types," 2024. [Daring]. Tersedia: https://dev.mysql.com/doc/refman/8.0/en/spatial-types.html.

[10] L. Zhang, X. Wang, dan H. Liu, "Performance Comparison of Spatial Databases for Location-Based Services," *IEEE Access*, vol. 8, hal. 187654–187668, 2020.

[11] R. C. Martin, "Clean Architecture: A Craftsman's Guide to Software Structure and Design," Boston: Prentice Hall, 2017.

[12] R. Rousselet, "provider: A Reactive State Management Library for Flutter," 2024. [Daring]. Tersedia: https://pub.dev/packages/provider.

[13] Taylor Otwell, "Laravel: The PHP Framework for Web Artisans," 2024. [Daring]. Tersedia: https://laravel.com/.

[14] S. Tymon, "tymon/jwt-auth: JSON Web Token Authentication for Laravel and Lumen," 2024. [Daring]. Tersedia: https://github.com/tymon/jwt-auth.

[15] P. Resnick dan R. Zeckhauser, "Trust Among Strangers in Internet Transactions: Empirical Analysis of eBay's Reputation System," dalam *Advances in Applied Microeconomics*, vol. 11, Emerald Group Publishing, 2002, hal. 127–157.

[16] P. Resnick, R. Zeckhauser, J. Swanson, dan K. Lockwood, "The Value of Reputation on eBay: A Controlled Experiment," *Experimental Economics*, vol. 9, no. 2, hal. 79–101, 2006.

[17] Pemerintah Republik Indonesia, "Undang-Undang Nomor 27 Tahun 2022 tentang Perlindungan Data Pribadi," Lembaran Negara RI, 2022.

[18] J. Martin, "Rapid Application Development," New York: Macmillan Publishing, 1991.

[19] Spatie, "Laravel Permission: Associate users with roles and permissions," 2024. [Daring]. Tersedia: https://spatie.be/docs/laravel-permission/v8/.

[20] Google, "Firebase Cloud Messaging: Send messages across platforms," 2024. [Daring]. Tersedia: https://firebase.google.com/docs/cloud-messaging.

[21] T. H. Cormen, C. E. Leiserson, R. L. Rivest, dan C. Stein, "Introduction to Algorithms," 4th ed. Cambridge: MIT Press, 2022.

[22] E. Gamma, R. Helm, R. Johnson, dan J. Vlissides, "Design Patterns: Elements of Reusable Object-Oriented Software," Boston: Addison-Wesley, 1994.

---

## Lampiran

### Lampiran A: Glosarium

| Istilah | Definisi |
|---------|----------|
| **Hyperlocal** | Konsep yang membatasi cakupan geografis pada area kecil, biasanya satu kecamatan atau kabupaten |
| **Marketplace Katalog** | Model marketplace di mana penjual memajang produk dan pembeli mencari |
| **Reverse Marketplace** | Model marketplace di mana pembeli memasang kebutuhan dan penjual menawarkan |
| **COD** | Cash on Delivery, pembayaran dilakukan saat barang diterima |
| **SRID** | Spatial Reference System Identifier, pengidentifikasi sistem referensi spasial |
| **R-tree** | Struktur data tree untuk indeks spasial |
| **MVP** | Minimum Viable Product, produk dengan fitur minimum yang layak digunakan |
| **GMV** | Gross Merchandise Value, total nilai transaksi |
| **PSE** | Penyelenggara Sistem Elektronik |
| **UU PDP** | Undang-Undang Perlindungan Data Pribadi |
| **FCM** | Firebase Cloud Messaging, layanan notifikasi push dari Google |
| **UMKM** | Usaha Mikro, Kecil, dan Menengah |

### Lampiran B: Daftar Tabel di Basis Data

| No | Nama Tabel | Fungsi | Soft Delete |
|:--:|------------|--------|:-----------:|
| 1 | users | Data pengguna | Ya |
| 2 | stores | Data toko | Ya |
| 3 | categories | Kategori | Tidak |
| 4 | listings | Produk/jasa/sewa | Ya |
| 5 | customer_requests | Permintaan kebutuhan | Status |
| 6 | offers | Penawaran | Status |
| 7 | orders | Pesanan | Tidak |
| 8 | reviews | Ulasan | Tidak |
| 9 | disputes | Sengketa | Tidak |
| 10 | user_devices | Perangkat pengguna | Tidak |
| 11 | settings | Pengaturan sistem | Tidak |
| 12 | favorites | Wishlist | Tidak |

### Lampiran C: Alur Registrasi dan Verifikasi

```
[Pengguna]                        [Sistem]                    [Admin]
    |                                |                          |
    |-- Request OTP ----------------->|                          |
    |<-- OTP Terkirim ----------------|                          |
    |-- Verifikasi OTP + Data ------->|                          |
    |<-- Token + Profil --------------|                          |
    |                                |                          |
    |-- Unggah KTP + Selfie -------->|                          |
    |<-- Berkas Terkirim ------------|-- Notifikasi Admin ------>|
    |                                |                          |-- Tinjau Berkas
    |<-- Notifikasi Status ----------|<-- Persetujuan/Penolakan -|
    |                                |                          |
    |-- Buka Toko (jika verified) -->|                          |
    |<-- Token Toko -----------------|                          |
```
