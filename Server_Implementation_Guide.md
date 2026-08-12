# 📄 Seekitar – Server Implementation Guide

**Versi:** 2.3 (Ultra‑Detailed · Production‑Ready)  
**Tanggal:** 29 Juli 2026  
**Target:** Laravel 13 + PHP 8.3+ + MySQL 8.0.34+ + Bootstrap 5.3.x + Yajra Datatables 13 + Spatie Permission 8

> 📌 Versi mengacu pada [`TECH_STACK.md`](TECH_STACK.md) sebagai sumber kebenaran tunggal.

> **Perubahan 2.3** — verifikasi pengguna menjadi SATU langkah (§9.3):
> OTP yang dikirim ke nomornya sendiri sudah membuktikan pemilikan nomor
> HP, jadi "tahap 1" beserta stempelnya dihapus; penilaian admin yang tersisa
> (wajah ↔ KTP ↔ NIK ↔ alamat ↔ titik domisili) selesai satu klik. Jejak
> auditnya kini tunggal — `verified_*`, `rejected_*`, `blocked_*` — dan
> kedudukan eksplisitnya disimpan di `users.status` (`UserStatus`;
> `menunggu|terverifikasi|ditolak|diblokir`). Pengguna yang diblokir
> otomatis menonaktifkan tokonya. Formulir sunting pengguna kini lengkap:
> unggah avatar ber-pratinjau, alamat, dan pin lokasi di peta sebagaimana
> halaman toko (§9.4).
>
> **Perubahan 2.2** — panduan diselaraskan dengan implementasi terkini:
> antrian & logika verifikasi ditulis ulang sesuai kode (§9.3 — stempel
> tulis-sekali, OTP men-stempel tahap 1, satu klik semua tahap, syarat
> persetujuan toko diperiksa ulang server); manajemen pengguna mengikuti
> `UsersDataTable` + halaman sunting lengkap (§9.4); penyimpanan berkas KTP
> memakai disk `local` privat + route streaming berizin (§18A.3); seeder
> men-stempel `verified*_at`, bukan kolom level. (Ditulis ulang 2.3: dua
> stempel tahapnya sudah dilebur menjadi satu `verified_*`.)

---

## DAFTAR ISI
    
1. [Ikhtisar & Prinsip](#1-ikhtisar--prinsip)
2. [Tech Stack & Versi](#2-tech-stack--versi)
3. [Instalasi & Konfigurasi Library Tambahan](#3-instalasi--konfigurasi-library-tambahan)
4. [Struktur Proyek](#4-struktur-proyek)
   - 4.1 Kenapa `app/Services/` Diperlukan
   - 4.2 Kenapa `app/Enums/` Diperlukan
   - 4.3 Kenapa `app/Jobs/` & `app/Listeners/` Dipisah
5. [Middleware & Pipeline](#5-middleware--pipeline)
   - 5.1 Registrasi Middleware (`bootstrap/app.php`)
   - 5.3 Rate Limiter Kustom
   - 5.4 `EnsureProfileComplete`
6. [Autentikasi & Otorisasi](#6-autentikasi--otorisasi)
   - 6.1 JWT & Token (API)
   - 6.2 Spatie Permission (Roles & Abilities)
   - 6.3 Gates & Policies
7. [Routing Lengkap](#7-routing-lengkap)
8. [Admin Panel – Menu & Navigasi](#8-admin-panel--menu--navigasi)
9. [Halaman Admin – Detail Tampilan & Form](#9-halaman-admin--detail-tampilan--form)
   - 9.1 Dashboard Admin
   - 9.2 Manajemen Kategori
   - 9.3 Verifikasi Pengguna & Toko
   - 9.4 Manajemen Pengguna
   - 9.5 Manajemen Toko
   - 9.6 Manajemen Listing
   - 9.7 Manajemen Permintaan (Customer Requests)
   - 9.8 Manajemen Penawaran (Offers)
   - 9.9 Manajemen Pesanan (Orders)
   - 9.10 Manajemen Dispute
   - 9.11 Manajemen Ulasan (Reviews)
   - 9.12 Pengaturan Sistem
10. [Controllers & Actions](#10-controllers--actions)
11. [Form Requests & Validasi](#11-form-requests--validasi)
12. [Models & Relationships](#12-models--relationships)
13. [Observers & Events](#13-observers--events)
14. [Jobs & Queue](#14-jobs--queue)
15. [Notifikasi (Push & WhatsApp)](#15-notifikasi-push--whatsapp)
    - 15.1 Push Notification (FCM)
    - 15.2 WhatsApp OTP
16. [Geospasial & Query Radius](#16-geospasial--query-radius)
17. [API Response & Paginasi (Web & API)](#17-api-response--paginasi-web--api)
18. [Error Handling & Logging](#18-error-handling--logging)
18A. [Keamanan Aplikasi](#18a-keamanan-aplikasi)
    - 18A.1 Route Model Binding & UUID
    - 18A.2 CORS
    - 18A.3 Enkripsi Data Sensitif (KTP & NIK)
    - 18A.4 Proteksi XSS di Blade
    - 18A.5 Rate Limiting Login Admin
    - 18A.6 Validasi Nomor Telepon Indonesia
19. [Migration & Seeder (Lengkap)](#19-migration--seeder-lengkap)
20. [Testing](#20-testing)
21. [Deployment](#21-deployment)
    - 21.1 Environment Variables
    - 21.2 Perintah Deploy
    - 21.2a Penyiapan Redis
    - 21.2b Penyimpanan Objek (S3 / MinIO)
    - 21.2c SSL/TLS
    - 21.2d Backup Basis Data
    - 21.3 Queue Worker (Supervisor)
    - 21.4 Scheduler (Cron)
    - 21.5 Tooling Pengembangan
    - 21.6 Monitoring Produksi (Sentry & Pulse)
22. [Lampiran: Contoh Kode Blade & Controller](#22-lampiran-contoh-kode-blade--controller)

---

## 1. IKHTISAR & PRINSIP

Server Seekitar menyediakan:

- **REST API** untuk aplikasi Flutter.
- **Web Admin Panel** menggunakan Laravel Blade + Bootstrap 5.3.x + Yajra Datatables untuk pengelolaan internal (verifikasi, manajemen data, dispute).
- **Web Public** halaman katalog, landing page (SEO-friendly).

Prinsip desain:

- **Thin controllers, fat models** – logika bisnis di model/service.
- **Validasi ketat** – FormRequest + custom rule.
- **Database-first integrity** – FK, constraint, spatial index.
- **UUID sebagai primary key** – mencegah enumerasi.
- **Observers** untuk side‑effect (rating, notifikasi).
- **Job antrian** untuk broadcast permintaan ke penyedia.
- **Soft delete** untuk users, stores, listings.
- **Responsive UI** – Bootstrap 5.3.x dengan komponen siap pakai.

---

## 2. TECH STACK & VERSI

| Komponen          | Teknologi                              | Constraint          |
| ----------------- | -------------------------------------- | ------------------- |
| Bahasa            | PHP 8.3+                               | `^8.3`              |
| Framework         | Laravel 13                             | `^13.8`             |
| Database          | MySQL 8.0.34+ (InnoDB, Spatial)        | —                   |
| Cache & Queue     | Redis 7                                | —                   |
| Storage           | AWS S3 / MinIO                         | —                   |
| Push Notification | Firebase Cloud Messaging               | — (rencana, lihat §15.1) |
| WhatsApp          | Gateway kustom `whatsapp.driver` (Baileys / Kirim WA / email / log) | — |
| Admin UI          | Bootstrap 5.3.x, Yajra Datatables 13.x | `^13.0`             |
| Permission        | Spatie Laravel Permission 8.x          | `^8.0`              |
| API Auth          | JWT — tymon/jwt-auth 2.x (guard `api`) | `^2.2`              |
| CI/CD             | GitHub Actions                         | —                   |

### Catatan Kompatibilitas (penting)

Constraint di atas **wajib** dipakai persis. Kombinasi yang salah akan langsung
gagal saat `composer require`:

- **Yajra Datatables** — versi mayornya mengikuti versi mayor Laravel. Laravel 13
  → `^13.0`. Memakai `^11.0` akan ditolak Composer karena paket itu mengunci
  `illuminate/support: ^11`.
- **Spatie Permission** — `^8.0` adalah versi pertama yang mendukung
  `illuminate/auth: ^12.0|^13.0`. Versi `^6.0` mentok di Laravel 11/12 dan
  **tidak akan ter-install** di Laravel 13.
- **PHP 8.3+** — batas atasnya `< 9.0` (dari `^8.3`). Jangan mengunci ke versi
  patch tertentu seperti `8.3.30`.

---

## 3. INSTALASI & KONFIGURASI LIBRARY TAMBAHAN

### 3.1 Yajra Datatables

```bash
composer require yajra/laravel-datatables-oracle:^13.0
```

> ⚠️ Nama paketnya **`yajra/laravel-datatables-oracle`**, bukan
> `yajra/laravel-datatables` (itu nama repo GitHub-nya, bukan nama paket
> Composer). Versi `^13.0` wajib untuk Laravel 13.

Opsional, jika butuh tombol export/print:

```bash
composer require yajra/laravel-datatables-buttons:^13.0
```

**Konfigurasi:** Tidak ada file konfig khusus. Langsung gunakan facade `DataTables`.

#### Template UI: Modernize

Panel admin memakai template **Modernize Admin** (adminmart.com), disalin dari
`github.com/KikiAbdullah/mordenize-template-bs` folder `package/dist/`.

| Hal | Nilai |
| :-- | :-- |
| Aset lokal | `public/vendor/modernize/` (~1,6 MB dari repo 328 MB) |
| CSS inti | `css/style.min.css` — **sudah memuat Bootstrap 5.3.0** |
| Ikon | **Tabler 2.11.0** (`ti ti-*`), hanya font `.woff2` |
| JS | `app.min.js`, `sidebarmenu.js`, `custom.js`, `seekitar.init.js` |
| Provenance & lisensi | `public/vendor/modernize/SUMBER.md` |

Struktur wajib (dibaca CSS & JS template):

```
.page-wrapper#main-wrapper[data-sidebartype]
├── aside.left-sidebar → nav.sidebar-nav > ul#sidebarnav > li.sidebar-item > a.sidebar-link
└── .body-wrapper
    ├── header.app-header  (tombol .sidebartoggler)
    └── .container-fluid
```

##### Warna hijau ditulis ke dalam CSS vendor, bukan ditimpa

Biru bawaan `#5D87FF` diganti hijau Seekitar `#168A4A` **langsung di dalam
`style.min.css`**:

```bash
node tools/dev/recolor-modernize.mjs   # 164 penggantian, idempoten
```

> ⚠️ **Kenapa bukan sekadar menimpa `--bs-primary` dari `admin.css`?**
> Karena **117 dari 164** kemunculan biru itu ditulis sebagai nilai heksa
> langsung di dalam aturan, bukan lewat variabel CSS. Variabel tidak
> menjangkaunya — hasilnya tombol dan badge biru nyasar di halaman yang jarang
> dibuka. Diverifikasi dengan menghitung kemunculannya, bukan diasumsikan.

Skrip itu **wajib dijalankan ulang** setiap kali berkas vendor diperbarui.
`check-admin-menu.mjs` menolak build yang CSS-nya masih biru.

Skrip yang sama juga mewarnai **`images/backgrounds/login-security.svg`**.
Warna di dalam SVG ditulis sebagai atribut `fill`, jadi CSS tidak
menjangkaunya sama sekali — satu-satunya cara adalah mengganti nilainya di
dalam berkas.

##### Halaman masuk

Mengikuti `package/html/main/authentication-login.html`: dua kolom
(`col-xl-7` ilustrasi + `col-xl-5` formulir) di atas latar `.radial-gradient`.
Di bawah 1200px kolom ilustrasi disembunyikan (`d-none d-xl-flex`) dan
formulirnya menjadi satu kolom penuh.

Tiga elemen template **sengaja tidak disalin**, karena backend-nya tidak ada
dan tautan mati membuat admin mengira panelnya rusak:

| Elemen template | Alasan dilewati |
| :-- | :-- |
| Tombol "Sign in with Google / FB" | Socialite tidak dipasang (`composer.json`) |
| "New to Modernize? Create an account" | Akun admin dibuat seeder, bukan pendaftaran mandiri |
| "Forgot Password ?" | Route `password.request` belum ada — memakainya melempar `RouteNotFoundException` dan **mematikan halaman masuk sepenuhnya** |

Ketiganya ditegakkan `check-admin-menu.mjs`; tautan lupa kata sandi baru boleh
ditambahkan setelah route-nya benar-benar terdaftar.

##### Sidebar

Mengikuti `<aside class="left-sidebar">` di `package/html/main/index.html`:
`.brand-logo` → `nav.sidebar-nav.scroll-sidebar > ul#sidebarnav`, lalu kartu
`.fixed-profile.sidebar-ad` **di luar** `</nav>` (posisi yang sama dengan
template). Kelas `.sidebar-ad` wajib: template memakainya untuk menyembunyikan
kartu otomatis saat mini-sidebar.

Backdrop `<div class="dark-transparent sidebartoggler">` ada di `layout.blade.php`,
di luar `.page-wrapper`. Tanpa elemen itu sidebar ponsel terbuka tanpa
peredupan dan mengetuk di luar tidak menutupnya — `app.min.js` memasang
penutup pada setiap `.sidebartoggler`, termasuk backdrop ini.

##### Tiga cacat tata letak sidebar yang diperbaiki

Semuanya diukur di Chromium, bukan disimpulkan dari membaca CSS:

| Cacat | Bukti | Perbaikan |
| :-- | :-- | :-- |
| Lencana menabrak panah `.has-arrow` | Panah `x 213–220`, lencana `208,6–235` — tumpang tindih penuh | `margin-right: 28px` pada pembungkus lencana (15px jarak panah + 7px lebar + 6px sela) |
| Kartu wilayah jatuh di bawah lipatan | `.brand-logo` 70px + `.scroll-sidebar` `calc(100vh - 80px)` = `100vh - 10px`, tersisa 10px untuk kartu 83px | Tata letak **flex** pada `.left-sidebar > div`, bukan angka `calc()` baru yang akan salah lagi |
| Tiga elemen sidebar merender **10px** | `.fs-1` = `.625rem`; `getComputedStyle` → `10px` | Diganti `.fs-2` (12px) |
| Ikon submenu merender **7px** | Template mengunci `.first-level .ti` ke `7px`; menu induk 21px | Dinaikkan ke **16px** = ukuran kotak `.round-16` pembungkusnya |
| Submenu aktif hanya dibedakan **warna** | Hijau `#168A4A` di atas putih = **4,40:1** (AA butuh 4.5); beda dengan non-aktif 2,81:1 pada bobot sama | Latar `--bs-primary-bg-subtle` + `font-weight: 600`, teks `#11703C` → **4,93:1** |

> Kenapa 7px wajar di template tetapi tidak di sini: **seluruh 98 ikon submenu**
> template adalah `ti-circle` — titik penanda daftar, bukan lambang yang perlu
> dikenali. Seekitar memakai ikon bermakna (`ti-id`, `ti-building-store`), yang
> pada 7px menyusut jadi bintik tak terbedakan.

> ⚠️ Yang terakhir lolos dari pemeriksaan font yang sudah ada karena checker
> itu mencari deklarasi `font-size:Npx`, sedangkan ukurannya datang dari
> **kelas utilitas**. `check-admin-menu.mjs` kini menolak `.fs-1` di sidebar.

#### §9.2 Peta Toko (Leaflet + OpenStreetMap)

Halaman `admin/maps/stores` menggambar seluruh toko berkoordinat sebagai titik.
Terpisah dari `/admin/stores` karena menjawab pertanyaan berbeda: tabel
menjawab *"toko mana yang perlu saya tindak"*, peta menjawab *"wilayah mana
yang belum terlayani"* — dan pola sebaran tidak terlihat di tabel berpaginasi.

| Hal | Nilai |
| :-- | :-- |
| Pustaka | **Leaflet 1.9.4** (BSD-2-Clause), di-host sendiri di `public/vendor/leaflet/` (188 KB) |
| Ubin | `tile.openstreetmap.org` — gratis, **wajib atribusi** |
| Izin | `manage-stores` (halaman **dan** endpoint GeoJSON) |
| Data | `GET admin/maps/stores/data` → GeoJSON FeatureCollection |
| Penjaga | `tools/dev/check-peta.mjs` (15 pemeriksaan) |

##### Kenapa Leaflet, bukan Google Maps

Google Maps JS API **mewajibkan penagihan aktif** sejak Juni 2018; tanpa kartu
kredit, petanya ditimpa tulisan *"for development purposes only"*. Leaflet
gratis penuh dan tanpa kunci API.

##### Empat jebakan yang dijaga checker

Semuanya **gagal diam-diam** — tidak satu pun memunculkan error:

1. **Wadah tanpa tinggi CSS.** Leaflet menggambar ke div berposisi absolut;
   tanpa tinggi eksplisit hasilnya elemen 0px dan peta "tidak muncul".
2. **Atribusi OSM dihapus.** Melanggar
   [Tile Usage Policy](https://operations.osmfoundation.org/policies/tiles/)
   tanpa peringatan apa pun dari peramban.
3. **Nama toko dirangkai ke HTML popup → XSS.** Nama & alamat diisi pemilik
   toko. **Dibuktikan nyata di Chromium:** `<img src=x onerror=...>` benar-benar
   dieksekusi lewat `bindPopup(\`<strong>${nama}</strong>\`)`, dan tidak
   dieksekusi setelah diganti `textContent`. Leaflet **tidak** punya pelolos
   HTML bawaan — `L.Util.escapeHtml` tidak ada (dicari di `leaflet.js`: 0 hasil).
4. **`location` NULL lolos ke Leaflet.** Melempar `Invalid LatLng` yang
   mematikan **seluruh** peta, bukan satu titik. Disaring di SQL, bukan PHP.

##### Data: 50 toko, 24 kecamatan

`StoreMapSeeder` menempatkan tepat **50 toko** dan menjamin setiap kecamatan
kebagian minimal satu lebih dulu, baru sisanya dibagi ke kecamatan padat.
`DemoDataSeeder` memakai `Wilayah::acak()` yang wajar meninggalkan kecamatan
kosong — dan peta bolong membuat orang menyimpulkan *"belum ada toko di
Tosari"* padahal itu sekadar hasil undian.

> ⚠️ **`Wilayah::KECAMATAN` sempat salah.** Versi sebelumnya memuat **19**
> entri, salah satunya `Bangil Kota` yang **bukan kecamatan**, dan enam
> kecamatan resmi hilang (Lekok, Lumbang, Pasrepan, Puspo, Tosari, Tutur).
> Kini lengkap 24 sesuai kode Kemendagri 35.14.01–35.14.24, dengan koordinat
> terverifikasi. Kelengkapan itu ditegakkan `check-peta.mjs`, yang menyimpan
> daftar resminya **sendiri** — membacanya dari berkas yang diuji akan
> membuat checker selalu lulus apa pun isinya.

> ⚠️ **Geseran titik dijepit ke batas kabupaten.** Gempol berpusat di lintang
> `-7,5497` sedangkan batas utara `-7,5428` — selisih 0,0069°. Geseran acak
> ±0,010° melempar toko **343 m ke luar wilayah**, mengambang di Kabupaten
> Sidoarjo. Ditemukan dengan menjalankan seeder terhadap `Wilayah::KECAMATAN`
> sungguhan lewat refleksi, bukan terhadap salinan di skrip uji.

##### Dasbor

Mengikuti `package/html/main/index2.html`:

| Blok | Pola template yang dipakai |
| :-- | :-- |
| Kartu sambutan | `col-lg-8` + `card bg-light-primary` + ilustrasi `.welcome-bg-img` |
| Peran & wilayah | `col-lg-4`, daftar ikon kotak `p-6 bg-light-* rounded-2` (pola *Payment Gateways*) |
| Antrian kerja | Baris ikon + angka + label (pola *Payment Gateways*) |
| Kartu KPI | `card-title mb-9` + angka `h4` + ikon kotak (pola *Monthly Earnings*) |
| Tabel ringkas | `table align-middle text-nowrap` + `thead tr.text-muted fw-semibold` + `tbody.border-top` |

##### Tata letak padat

Dasbor dipadatkan dari **2818px (3,13 layar)** menjadi **1536px (1,71 layar)**
pada 1440×900 — turun **45%** — tanpa menghapus satu pun angka. Diukur di
Chromium, bukan diperkirakan.

| Keputusan | Alasan terukur |
| :-- | :-- |
| Kartu "Peran & Wilayah" **dihapus** | Isinya muncul **tiga kali**: peran ada di dropdown header, wilayah ada di kaki sidebar |
| Sambutan + antrian **satu baris** (`col-xl-5` / `col-xl-7`) | Dua blok terpisah memakan 508px hanya untuk 6 angka |
| Kartu KPI jadi **ikon + angka sebaris** | Pola `card-title mb-9` memakai 473px untuk 8 kartu; versi padat 216px |
| Dua tabel **berdampingan** (`col-xl-6`) | Ditumpuk ke bawah memakan 1147px |
| Kolom "Pembeli"/"Toko" dilebur ke baris kedua | Menghilangkan 1 kolom tanpa menghilangkan datanya |
| Tinggi grafik `clamp(200px, 26vh, 280px)` | `height: 300px` memakan 39% tinggi layar laptop 1366×768 |

> `col-xl-6` dipilih, bukan `col-lg-6`. Diverifikasi di 1200/1199/992/991px:
> di bawah 1200px kolomnya menumpuk penuh, jadi kasus 992px yang dulu
> memotong kolom Status **tidak pernah terjadi**.

##### Dua cacat tata letak yang ikut diperbaiki

1. **Sel judul tabel tidak mau menyusut.** `text-truncate` di dalam `<td>`
   tidak cukup — sel tabel melebar mengikuti isi terpanjang sehingga
   `text-overflow` tidak pernah aktif. Terukur: sel pertama tabel penawaran
   menolak turun di bawah 265px, membuat tabel meluber **13px di 1366px** dan
   **175px di ponsel**. Diperbaiki `.admin-ringkas` (`width:100%; max-width:0`).
2. **Ilustrasi sambutan menimpa teks.** Sebagai kolom grid (`col-5`), gambar
   282px tidak muat di kolom 226px pada 1366px dan **menimpa teks sampai
   103px**. Dijadikan latar berposisi absolut dengan `padding-right: 46%`
   pada teks — 0 tabrakan di sembilan lebar yang diuji.

Dua penyimpangan **disengaja**, keduanya berdasar ukuran:

1. **Tabel ringkas memakai `col-12`, bukan `col-lg-6`.** Tabelnya 4 kolom
   dengan sel panjang (judul permintaan, nama toko, rupiah). Pada `col-lg-6`
   (489px) kolom terakhir **Status terpotong** di 1440px, 1280px, dan 992px —
   terukur meluber sampai 262px. Template sendiri menaruh tabel selebar ini di
   `col-lg-8`, bukan berdampingan.
2. **Ilustrasi sambutan tanpa `mb-n7`.** Aset template aslinya punya ruang
   kosong di bagian bawah sehingga aman "menembus" tepi kartu.
   `welcome-bg2.png` **tidak** — diperiksa piksel demi piksel: baris konten
   terakhir ada di `y=195` dari tinggi 196, jadi ruang kosongnya **0px**.
   Dengan `mb-n7` gambarnya terpotong 30px.

##### Tiga cacat template yang diperbaiki di `admin.css`

Ketiganya hanya terlihat saat halaman dirender di peramban sungguhan, dan
tidak satu pun memunculkan error:

1. **`@keyframes gradient` tidak ada.** `.radial-gradient::before` memanggil
   `animation: … running gradient`, tetapi nama itu tidak terdefinisi di
   `style.min.css` **maupun** di `style.css` yang belum diminifikasi, di
   seluruh varian tema repositori sumber. Animasi yang menunjuk nama tak
   dikenal diabaikan peramban tanpa peringatan, sehingga latarnya membeku.
   Keyframes-nya didefinisikan di `admin.css`.
2. **Border fokus biru `#aec3ff`.** Nilai itu tidak ada di peta
   `recolor-modernize.mjs` (yang hanya memuat `#5D87FF` dan turunannya),
   jadi ia selamat dari pewarnaan — setiap kotak isian di **seluruh panel**
   berkedip biru di atas antarmuka hijau.
3. **Cincin fokus dimatikan template sendiri.** `:focus{outline:0;box-shadow:
   none!important}` berlaku global dan membunuh cincin fokus yang ditulis
   template satu baris di bawahnya. Penanda fokus tersisa hanyalah perubahan
   border tipis — melanggar **WCAG 2.4.7 (Focus Visible)**. Karena itu
   perbaikannya **wajib** memakai `!important`.

Ditemukan lewat `getComputedStyle` di Chromium, bukan dengan membaca berkas.

##### Pola halaman

Setiap halaman admin memakai susunan yang sama:

```blade
@extends('admin.layout')
@section('title', 'Toko')

@section('content')
    {{-- kartu judul + remah roti --}}
    <div class="card bg-light-primary shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <h4 class="fw-semibold mb-2">Toko</h4>
            <nav aria-label="Remah roti"><ol class="breadcrumb mb-0">…</ol></nav>
        </div>
    </div>

    <div class="card w-100">
        <div class="card-body">…</div>
    </div>
@endsection
```

Halaman tabel tidak menulisnya sendiri — `admin.partials.table-page` sudah
merender kartu judul, remah roti, filter, dan bilah aksi sekaligus.

**Pengumuman memakai kartu `bg-light-*`, bukan `.alert` polos.** Bentuknya
mengikuti komponen Modernize sehingga menyatu dengan kartu di sekitarnya:

```blade
<div class="card bg-light-info shadow-none border-0 mb-4">
    <div class="card-body py-3 px-4">
        <div class="d-flex align-items-start gap-3">
            <i class="ti ti-info-circle fs-6 text-info mt-1" aria-hidden="true"></i>
            <p class="mb-0 fs-3">…</p>
        </div>
    </div>
</div>
```

Nilai `tone` yang dikirim `DashboardController` **wajib** berupa nama warna
Bootstrap (`primary`, `secondary`, `success`, `warning`, `danger`, `info`).
Nama karangan seperti `green` atau `cyan` tidak menghasilkan kelas apa pun,
dan kartunya tampil tanpa warna sama sekali.

##### View bebas komentar naratif

Blade adalah lapisan presentasi. Penjelasan alasan, riwayat perbaikan, dan
catatan investigasi **tidak boleh** ada di sana — tempatnya di controller,
service, atau dokumen ini.

Yang masih boleh: dokumentasi **parameter** partial, karena itu kontrak bagi
pemanggilnya. Hanya tiga berkas yang memilikinya (`_datatable`, `table-page`,
`_reject_modal`).

> Ditegakkan `check-admin-menu.mjs` dan
> `DataTableQueryTest::test_blade_admin_bersih_dari_komentar_naratif`. Aturannya:
> komentar lebih dari 2 baris, atau memuat kata penanda seperti "KENAPA",
> "Sebabnya", "Diverifikasi", ditolak.

##### Tiga jebakan yang sudah ditangani

1. **Bootstrap ganda.** `style.min.css` sudah memuat Bootstrap 5.3.0. Memuat
   CSS Bootstrap lagi menggandakan ±200 KB dan membuat aturan yang belakangan
   menang secara acak. Yang dimuat terpisah hanya **JS**-nya.

2. **Font Tabler 404.** Repo aslinya membawa `.eot`/`.ttf`/`.woff`/`.woff2`
   (4,9 MB) demi IE8. Hanya `.woff2` (640 KB) yang disalin, dan
   `tabler-icons.min.css` **ditulis ulang** agar `@font-face`-nya tidak lagi
   meminta tiga berkas yang tidak ada — kalau tidak, tiga permintaan 404 di
   setiap halaman.

3. **`app.init.js` diganti.** Berkas bawaan menyetel `ThemeBg: "purple_theme"`
   yang memicu pemuatan stylesheet tema terpisah. Seekitar memakai satu
   stylesheet yang sudah hijau, jadi diganti `seekitar.init.js` yang hanya
   memanggil `AdminSettings` untuk mode mini-sidebar responsif.

> ⚠️ Kelas `sidebar-item`, `sidebar-link`, `has-arrow`, `first-level`, id
> `sidebarnav`, dan atribut `data-sidebartype` **bukan hiasan**.
> `sidebarmenu.js` memakainya untuk menandai menu aktif dan membuka submenu;
> `app.min.js` memakai `.sidebartoggler` untuk mode mini-sidebar. Mengganti
> nama kelasnya mematikan perilaku itu tanpa error apa pun.

#### Select2 untuk semua dropdown

Seluruh `<select>` panel memakai **Select2 4.1.0** + tema Bootstrap 5, dengan
kotak pencarian dan terjemahan Indonesia. Dipasang sekali di
`layout.blade.php` lewat helper `seekitarSelect2()`.

```blade
<select name="status" class="form-select js-select2" data-dt-filter="orders-table">
```

Keputusan penerapan:

- **Dipasang ke kelas `.js-select2`, bukan selektor `select` global.**
  Datatables merender pemilih "Tampilkan N entri" miliknya sendiri setiap
  tabel digambar ulang; membungkusnya dengan Select2 membuat kontrol itu
  hilang setelah sortir atau ganti halaman.
- **Varian `select2.full.min.js`, bukan `select2.min.js`.** Hanya varian full
  yang memuat modul terjemahan, sehingga `i18n/id.js` bisa mendaftarkan diri.
  Dengan varian biasa, `language: 'id'` diabaikan diam-diam.
- **`minimumResultsForSearch`** default 8, bisa ditimpa `data-min-search`.
  Untuk daftar 2–3 pilihan (Aktif/Nonaktif), kotak cari hanya menambah satu
  langkah tanpa manfaat.
- **Helper bersifat idempoten** (`select2-hidden-accessible` diperiksa dulu):
  memanggilnya dua kali pada elemen yang sama menumpuk kontainer dan
  menyisakan kotak kosong.
- **`z-index: 1060`** untuk `.select2-container--open`. Modal Bootstrap
  ber-z-index 1055 sedangkan dropdown Select2 default 1051, sehingga daftarnya
  tampil di bawah modal dan tidak bisa diklik.

> ⚠️ **Filter tabel WAJIB memakai jQuery `.on('change')`, bukan
> `addEventListener('change')`.**
>
> Select2 mengganti nilai lewat `$el.trigger('change')` milik jQuery. Event
> sintetis itu **tidak menyentuh listener native** — diverifikasi langsung di
> jsdom dengan Select2 4.1.0 sungguhan: listener `addEventListener` terpanggil
> **0 kali**, listener jQuery **1 kali**.
>
> Kalau ini terlewat, SELURUH filter tabel berhenti bekerja tanpa satu pun
> pesan error: dropdown-nya berubah, tabelnya tidak. Ditegakkan
> `check-admin-menu.mjs`.

#### Pola tabel admin: pilih baris, bukan kolom tombol

Tabel admin **tidak memakai kolom aksi**. Baris dipilih (satu saja), lalu
tombolnya muncul di bilah sebelah kanan judul halaman.

Alasannya bukan selera: kolom aksi memaksa setiap baris membawa tombolnya
sendiri — pada 1.000 baris itu 1.000 tombol di DOM — dan kolomnya ikut melebar
mengorbankan kolom data yang justru dibaca.

HTML tombolnya **tetap dirakit server** dan dikirim di field `action`. Field
yang tidak didaftarkan sebagai `columns` tidak dirender Datatables, tetapi
tetap ikut di `row().data()` (diverifikasi dengan Datatables 2.3.8). Dengan
begitu tombol tetap melewati `@csrf`, `@method`, dan `@can` di Blade — bukan
dirakit ulang di JavaScript, tempat otorisasi tidak bisa ditegakkan.

Seluruhnya terpusat di `resources/views/admin/partials/table-page.blade.php`:

```blade
@include('admin.partials.table-page', [
    'judul'      => 'Toko',
    'tableId'    => 'stores-table',
    'ajax'       => route('admin.stores.data'),
    'filterView' => 'admin.stores._filter',   // NAMA view, bukan view()
    'columns'    => [ ['data' => 'name', 'label' => 'Nama'], ... ],
])
```

> ⚠️ **Jangan pernah mengoper objek view sebagai nilai variabel.**
>
> `'filter' => view('admin.stores._filter')` yang ditampilkan dengan
> `{{ $filter }}` membuat seluruh filter tampil sebagai **teks mentah**
> (`&lt;select&gt;…`) alih-alih elemen form. Ini sudah pernah terjadi di
> kedelapan halaman tabel sekaligus.
>
> Sebabnya halus: `@include` me-**render** sub-view menjadi string sebelum
> mengopernya. Objek `View` sendiri `Htmlable` sehingga `e()` akan
> melewatkannya — tetapi yang sampai ke `{{ }}` sudah berupa string biasa,
> dan string biasa memang di-escape. Diverifikasi langsung: `e($view)` tidak
> meng-escape, `e($view->render())` meng-escape.
>
> Perbaikannya **bukan** `{!! !!}` — itu mematikan escaping dan justru membuka
> XSS. Yang benar: oper **nama** view lalu
> `@includeIf($filterView)`, sehingga tidak ada HTML yang pernah menjadi nilai
> variabel.
>
> Halaman tetap "berhasil dirender" dalam keadaan ini, jadi render harness pun
> tidak mengeluhkannya — hanya `check-admin-menu.mjs` yang menangkapnya.

Catatan penerapan:

- **Pemilihan tunggal tanpa ekstensi Select.** Ekstensi resmi menambah satu
  berkas CSS + JS demi perilaku yang di sini cukup belasan baris, dan
  defaultnya justru multi-baris. Polanya: buang kelas dari semua baris dulu,
  baru tandai yang diklik.
- **Pilihan dibatalkan pada event `draw`.** Tanpa itu, bilah aksi masih memuat
  tombol milik baris yang sudah tidak tampak setelah sortir/paginasi — dan
  menekannya mengubah data yang tidak sedang dilihat siapa pun.
- **Modal penolakan toko dipakai bersama satu untuk seluruh tabel.** Versi
  lama memberi tiap baris modalnya sendiri ber-id `reject-{uuid}`; begitu HTML
  aksi berpindah ke bilah yang isinya diganti-ganti, `data-bs-target` bisa
  menunjuk elemen yang sudah terhapus.
- **Tombolnya dipasang lewat delegasi event pada `document`**, karena elemen
  `.js-tolak-toko` baru dibuat setiap kali baris dipilih.
- Baris diberi `tabindex` dan menanggapi Enter/Spasi; Escape membatalkan
  pilihan (WCAG 2.1.1).
- **Bilah aksi kosong** sebelum ada baris dipilih — tanpa teks petunjuk dan
  tanpa nama entitas di samping tombol. Tingginya dipatok
  `.admin-rowactions { min-height }` supaya baris judul tidak melompat
  naik-turun saat tombol muncul lalu hilang.

#### ⚠️ Dua jebakan query yang hanya muncul di browser

Keduanya lolos `php -l`, lolos test statis, dan bahkan lolos `toSql()`.

**1. `select()` HARUS mendahului `withCount()`**

```php
// SALAH — subquery hitungnya terhapus, tanpa error apa pun
CustomerRequest::query()->withCount('offers')->select(['id', 'title']);
// SQL: select `id`, `title` from `customer_requests`

// BENAR
CustomerRequest::query()->select(['id', 'title'])->withCount('offers');
// SQL: select `id`, `title`, (select count(*) …) as `offers_count` …
```

`select()` menimpa **seluruh** daftar SELECT, termasuk subquery yang baru
ditambahkan `withCount()`. SQL-nya tetap sah, jadi tidak ada yang gagal — kolom
itu hanya tidak pernah ada. Gejalanya muncul di sisi klien:

```
DataTables warning: table id=requests-table -
Requested unknown parameter 'offers_count' for row 0, column 3
```

**2. Nama relasi tidak diperiksa sampai baris diambil**

`Store` punya kolom `user_id`, tetapi relasinya bernama **`owner()`** — bukan
`user()`. Menulis `with('user')` menghasilkan:

```
Call to undefined relationship [user] on model [App\Models\Store].
```

Eager loading bersifat **malas**: `toSql()` tetap berhasil dan tidak
menunjukkan apa-apa. Exception-nya baru dilempar saat `get()`/`paginate()`
dijalankan — artinya bug ini melewati semua pemeriksaan yang tidak menyentuh
basis data.

> Ditegakkan dua skrip: `tools/dev/check-relations.php` memverifikasi setiap
> relasi yang di-eager-load benar-benar terdefinisi lewat refleksi model, dan
> `tools/dev/check-datatables.php` merakit tiap query lalu mencocokkan kolom
> yang diminta view. Keduanya dipanggil `check-admin-menu.mjs`.

#### ⚠️ Berkas bahasa Datatables di-host sendiri, JANGAN dari CDN

Terjemahan Indonesia berada di `public/vendor/datatables/id.json` dan disetel
**sekali** di `resources/views/admin/layout.blade.php`:

```js
$.extend(true, $.fn.dataTable.defaults, {
    language: { url: @js(asset('vendor/datatables/id.json')) },
});
```

Sebelumnya tiap tabel memuatnya dari
`https://cdn.datatables.net/plug-ins/2.1.8/i18n/id.json`, dan **setiap** halaman
tabel memunculkan:

```
DataTables warning: table id=requests-table - i18n file loading error
```

**Sebabnya bukan salah ketik nomor versi.** `2.1.8` adalah versi **core**
Datatables, sedangkan repo `DataTables/Plugins` punya penomoran sendiri —
tag yang ada hanya `2.1.4`, `2.2.x`, `2.3.x`, `3.0.0`; **tidak pernah ada
`2.1.8`**. URL itu 404, dan Datatables melaporkannya sebagai galat i18n.

Menaikkan nomornya ke `2.3.6` hanya memindahkan masalah: nomor itu basi lagi
pada rilis berikutnya, dan seluruh tabel admin ikut rusak setiap kali pihak
ketiga mengubah jalurnya. Berkasnya hanya ±800 byte, jadi di-host sendiri —
panel admin juga tetap berbahasa Indonesia saat jaringan keluar diblokir,
yang lazim pada deployment intranet.

> Ditegakkan `tools/dev/check-admin-menu.mjs`: rujukan `cdn.datatables.net/plug-ins`
> di view mana pun akan menggagalkan pemeriksaan, begitu pula berkas i18n yang
> hilang, rusak, atau kehilangan kunci intinya.

### 3.2 Spatie Laravel Permission

```bash
composer require spatie/laravel-permission:^8.0
```

> ⚠️ Harus `^8.0`. Versi `^6.0` hanya mendukung sampai Laravel 11/12 dan akan
> gagal resolusi dependensi di Laravel 13.

Publish migration dan config:

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

Tambahkan trait `HasRoles` pada model `User`:

```php
use Spatie\Permission\Traits\HasRoles;
class User extends Authenticatable {
    use HasApiTokens, HasUuids, SoftDeletes, HasRoles;
}
```

**Guard:** kita gunakan `api` (JWT untuk mobile), `web` (sesi admin), dan
`sanctum` (kompatibilitas transisi). Di config/permission.php, pastikan guards
mencakup ketiganya.

### 3.3 Bootstrap 5 & Asset

Kita gunakan Laravel Breeze (opsional) atau langsung kompilasi Bootstrap 5.3.x via Vite (tidak dianjurkan karena Anda minta tanpa Vite).  
**Alternatif:** Gunakan Bootstrap 5.3.x CDN di layout Blade utama.

**Layout Admin (`resources/views/layouts/admin.blade.php`)** akan menyertakan CSS & JS Bootstrap 5.3.x, Datatables, dan Font Awesome.

---

## 4. STRUKTUR PROYEK

Struktur lengkap (bukan hanya bagian admin). Folder di luar bawaan Laravel
ditandai dengan penjelasan singkat alasan keberadaannya.

```
app/
├── Console/
│   └── Commands/
│       ├── PurgeExpiredRequests.php    # requests:purge-expired — tutup permintaan kadaluarsa
│       ├── PurgeExpiredOffers.php      # offers:purge-expired — tolak penawaran kadaluarsa
│       ├── CleanupPendingOrders.php    # orders:cleanup-pending — bersihkan pesanan menggantung
│       └── DataRetentionCommand.php    # privacy:retention — hapus data pribadi sesuai UU PDP
├── DataTables/                        # Server-side processing Yajra (admin), pola `DataTables::eloquent()` — lihat app/DataTables/README.md
│   ├── UsersDataTable.php
│   ├── StoresDataTable.php
│   ├── ListingsDataTable.php
│   ├── CustomerRequestsDataTable.php
│   ├── OffersDataTable.php
│   ├── OrdersDataTable.php
│   ├── DisputesDataTable.php
│   └── ReviewsDataTable.php
├── Enums/                             # Domain nilai tetap (backed enum)
│   ├── OrderStatus.php
│   ├── OrderType.php
│   ├── RequestStatus.php
│   ├── OfferStatus.php
│   ├── ListingStatus.php
│   ├── ListingType.php
│   ├── StoreType.php
│   ├── StoreStatus.php
│   ├── UserStatus.php
│   ├── VerificationLevel.php
│   ├── PaymentMethod.php
│   ├── DeliveryMethod.php
│   ├── DisputeStatus.php
│   ├── DisputeReason.php
│   ├── ReviewDirection.php
│   └── Concerns/HasValues.php
├── Events/
│   ├── CustomerRequestCreated.php
│   ├── OfferAccepted.php
│   └── OrderStatusChanged.php
├── Exceptions/
│   ├── BusinessException.php
│   ├── InvalidOrderTransitionException.php
│   └── OtpDeliveryException.php
├── Exports/
│   └── DataTableExport.php
├── Http/
│   ├── Concerns/
│   │   ├── ApiResponse.php             # Amplop JSON seragam untuk API (§17.1)
│   │   └── HasEscapePlan.php           # Pembungkus transaksional + respons degradasi
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── LoginController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── PasswordController.php
│   │   │   ├── VerificationController.php
│   │   │   ├── UserController.php
│   │   │   ├── StoreController.php
│   │   │   ├── StoreMapController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── ListingController.php
│   │   │   ├── CustomerRequestController.php
│   │   │   ├── OfferController.php
│   │   │   ├── OrderController.php
│   │   │   ├── ReviewController.php
│   │   │   ├── DisputeController.php
│   │   │   ├── SettingController.php
│   │   │   ├── SubscriptionController.php
│   │   │   ├── AdvertisementController.php
│   │   │   ├── FeeController.php
│   │   │   ├── BlogController.php
│   │   │   ├── WhatsAppController.php
│   │   │   └── WalletController.php
│   │   ├── Api/V1/...
│   │   └── Web/PageController.php
│   ├── Middleware/
│   │   ├── EnsureHttps.php
│   │   ├── EnsureUserNotBlocked.php
│   │   ├── EnsureProfileComplete.php
│   │   └── EnsureStoreOwner.php        # Terdaftar (alias store.owner), belum dipakai route (§5.2)
│   ├── Requests/
│   │   ├── Admin/
│   │   │   ├── LoginRequest.php
│   │   │   ├── UpdateProfileRequest.php
│   │   │   ├── UpdatePasswordRequest.php
│   │   │   └── RejectVerificationRequest.php
│   │   └── Api/
│   │       ├── RequestOtpRequest.php
│   │       ├── VerifyOtpRequest.php
│   │       ├── StoreStoreRequest.php
│   │       ├── UpdateStoreRequest.php
│   │       ├── StoreListingRequest.php
│   │       ├── UpdateListingRequest.php
│   │       ├── StoreCustomerRequestRequest.php
│   │       ├── UpdateCustomerRequestRequest.php
│   │       ├── StoreOfferRequest.php
│   │       ├── StoreOrderRequest.php
│   │       ├── TopUpWalletRequest.php
│   │       ├── WithdrawWalletRequest.php
│   │       ├── StoreUserAddressRequest.php
│   │       └── UpdateProfileRequest.php
│   └── Resources/                     # API Resource (transformer JSON)
│       ├── UserResource.php
│       ├── UserExportResource.php
│       ├── StoreResource.php
│       ├── CategoryResource.php
│       ├── ListingResource.php
│       ├── CustomerRequestResource.php
│       ├── OfferResource.php
│       ├── OrderResource.php
│       ├── ReviewResource.php
│       ├── DisputeResource.php
│       ├── WalletResource.php
│       ├── WalletTransactionResource.php
│       ├── CouponResource.php
│       ├── UserAddressResource.php
│       ├── ConversationResource.php
│       ├── ConversationParticipantResource.php
│       ├── MessageResource.php
│       └── NotificationResource.php
├── Jobs/                              # Antrian Redis (asinkron)
│   ├── BroadcastRequestJob.php        # Sebar permintaan ke penyedia dalam radius
│   └── SendOtpJob.php                 # Kirim OTP via gateway WhatsApp di background
├── Listeners/
│   ├── DispatchRequestBroadcast.php   # CustomerRequestCreated -> BroadcastRequestJob
│   ├── SendOfferAcceptedNotification.php
│   └── SendOrderStatusNotification.php
├── Models/
│   ├── User.php
│   ├── Store.php
│   ├── Category.php
│   ├── Listing.php
│   ├── CustomerRequest.php
│   ├── Offer.php
│   ├── Order.php
│   ├── Review.php
│   ├── Dispute.php
│   ├── Wallet.php
│   ├── WalletTransaction.php
│   ├── Favorite.php
│   ├── UserAddress.php
│   ├── UserDevice.php
│   ├── Coupon.php
│   ├── CouponUsage.php
│   ├── Subscription.php
│   ├── Advertisement.php
│   ├── BlogPost.php
│   ├── Conversation.php
│   ├── ConversationParticipant.php
│   ├── Message.php
│   ├── Notification.php
│   ├── ActivityLog.php
│   ├── Report.php
│   ├── Setting.php
│   ├── ContactMessage.php
│   └── Concerns/
│       ├── HasLocation.php
│       └── SerializesDatesAsUtc.php
├── Observers/                         # Side-effect otomatis pada model
│   ├── OrderObserver.php              # Nomor pesanan & penjaga transisi status
│   └── ReviewObserver.php             # Hitung ulang rating toko & pembeli (sinkron)
├── Policies/
│   ├── StorePolicy.php
│   ├── ListingPolicy.php
│   ├── CustomerRequestPolicy.php
│   ├── OrderPolicy.php
│   └── OfferPolicy.php
├── Providers/
│   └── AppServiceProvider.php         # binding, observer, event, rate limiter, gate
├── Rules/
│   └── NotAWeakPassword.php
├── Services/                          # Logika bisnis lintas controller
│   ├── Contracts/
│   │   ├── WhatsAppGateway.php        # Kontrak pengiriman OTP (sendOtp)
│   │   └── NotificationSender.php     # Kontrak notifikasi ke penyedia
│   ├── Notifications/
│   │   └── LogNotificationSender.php  # Satu-satunya implementasi saat ini (log)
│   ├── WhatsApp/
│   │   ├── BaileysGateway.php
│   │   ├── EmailOtpGateway.php
│   │   ├── KirimWaGateway.php
│   │   └── LogWhatsAppGateway.php
│   ├── BroadcastService.php           # Pencocokan penyedia untuk sebuah permintaan
│   ├── GeolocationService.php         # Query radius ST_Distance_Sphere
│   ├── OrderStateMachine.php          # Validasi transisi status pesanan
│   ├── OtpService.php                 # Generate, simpan (Redis), verifikasi OTP
│   ├── VerifikasiTokoService.php      # Persetujuan/penolakan toko — satu pintu
│   ├── SettingService.php             # Baca/tulis konfigurasi runtime + cache
│   ├── TransactionService.php         # Pembungkus transaksi DB (begin/commit/rollback)
│   ├── PrivacyService.php             # Anonimisasi & ekspor data (UU PDP)
│   ├── CacheService.php               # Cache terpusat dengan dukungan grup
│   ├── EscapePlanService.php          # Jaringan pengaman layanan (degradasi)
│   └── ActivityLogger.php             # Jejak aktivitas pengguna/admin
└── Support/
    ├── PhoneNumber.php                # Normalisasi nomor HP Indonesia → 62xxx
    ├── Angka.php
    ├── Jarak.php                      # Haversine di PHP
    ├── PlaceholderImg.php
    └── SpatialSchema.php

database/
├── factories/
│   ├── UserFactory.php
│   ├── StoreFactory.php
│   ├── ListingFactory.php
│   └── ...                            # 13 factory total + Support/Wilayah.php (24 kecamatan, lihat §19.2a)
├── migrations/
│   └── ...                            # Lihat DATABASE.md §10 untuk urutannya
└── seeders/
    ├── DatabaseSeeder.php
    ├── CategorySeeder.php             # 24 kategori, wajib saat deploy awal
    ├── RolesAndPermissionsSeeder.php   # Role & permission Spatie
    └── DummyDataSeeder.php            # Data contoh, hanya untuk development

routes/
├── api.php                            # Endpoint mobile (guard api / JWT)
├── web.php                            # Web publik SEO
├── admin.php                          # Panel admin (guard web + role)
└── console.php                        # Jadwal scheduler
```

### Kenapa `app/Services/` Diperlukan

Controller sebaiknya tipis: validasi masuk, panggil service, kembalikan response.
Tiga alur di Seekitar dipakai dari lebih dari satu tempat, jadi tidak layak
ditaruh di controller:

| Service               | Dipakai oleh                                            | Alasan                                                                 |
| :-------------------- | :------------------------------------------------------ | :---------------------------------------------------------------------- |
| `BroadcastService`    | API create request, admin re-broadcast, job antrian     | Aturan pencocokan penyedia cukup rumit dan harus konsisten             |
| `GeolocationService`  | Pencarian toko, pencarian listing, pencocokan broadcast | Raw query spasial terpusat di satu tempat, mudah diuji & dioptimasi    |
| `VerifikasiTokoService` | Panel web (antrian toko), aksi cepat tabel toko, endpoint admin API | Persetujuan/penolakan toko dipakai tiga jalur — disalin tiga kali dulu pernah menyimpang (§9.3) |

Contoh kerangka:

```php
// app/Services/GeolocationService.php
namespace App\Services;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;

class GeolocationService
{
    /**
     * WKT SRID 4326 dibaca MySQL sebagai (latitude longitude) sesuai EPSG.
     * Seluruh bujur Indonesia (95°-141° BT) di luar rentang lintang ±90,
     * jadi tanpa opsi ini setiap titik ditolak: ERROR 3617.
     */
    private const AXIS = 'axis-order=long-lat';

    /** Batasi query ke radius tertentu (meter) dari sebuah titik. */
    public function withinRadius(Builder $query, float $lat, float $lng, float $radiusKm): Builder
    {
        return $query->whereRaw(
            'ST_Distance_Sphere(location, ST_GeomFromText(?, 4326, ?)) <= ?',
            ["POINT($lng $lat)", self::AXIS, $radiusKm * 1000]
        );
    }

    /** Tambahkan kolom jarak (km) agar bisa diurutkan & ditampilkan. */
    public function selectDistance(Builder $query, float $lat, float $lng): Builder
    {
        return $query->selectRaw(
            '*, ST_Distance_Sphere(location, ST_GeomFromText(?, 4326, ?)) / 1000 AS distance_km',
            ["POINT($lng $lat)", self::AXIS]
        );
    }
}
```

> ⚠️ Perhatikan urutan `POINT(longitude latitude)` — terbalik dari kebiasaan
> menulis `lat, lng`. Ini sumber bug geospasial yang paling sering terjadi.
> Memusatkannya di `GeolocationService` mencegah kesalahan berulang.

### Kenapa `app/Enums/` Diperlukan

`DATABASE.md` memakai ENUM di level MySQL agar nilai tidak liar. Enum PHP
membuat jaminan yang sama berlaku di level aplikasi, sekaligus memberi
autocomplete dan mencegah salah ketik string.

```php
// app/Enums/OrderStatus.php
namespace App\Enums;

enum OrderStatus: string
{
    case MenungguKonfirmasi = 'menunggu_konfirmasi';
    case Diproses           = 'diproses';
    case Dikirim            = 'dikirim';
    case Selesai            = 'selesai';
    case Dibatalkan         = 'dibatalkan';
    case Dispute            = 'dispute';

    /** Label untuk UI admin & mobile. */
    public function label(): string
    {
        return match ($this) {
            self::MenungguKonfirmasi => 'Menunggu Konfirmasi',
            self::Diproses           => 'Diproses',
            self::Dikirim            => 'Dikirim / Siap Diambil',
            self::Selesai            => 'Selesai',
            self::Dibatalkan         => 'Dibatalkan',
            self::Dispute            => 'Dispute',
        };
    }

    /** Status akhir tidak boleh berubah lagi. */
    public function isFinal(): bool
    {
        return in_array($this, [self::Selesai, self::Dibatalkan], true);
    }
}
```

Nilai enum **wajib** sama persis dengan ENUM di `DATABASE.md`:

| Enum                 | Nilai                                                                        | Sumber                        |
| :------------------- | :--------------------------------------------------------------------------- | :---------------------------- |
| `OrderStatus`        | `menunggu_konfirmasi`, `diproses`, `dikirim`, `selesai`, `dibatalkan`, `dispute` | `orders.status`           |
| `OrderType`          | `product`, `service`, `rental`                                                | `orders.order_type`           |
| `RequestStatus`      | `open`, `closed`, `expired`                                                  | `customer_requests.status`    |
| `OfferStatus`        | `pending`, `accepted`, `rejected`                                            | `offers.status`               |
| `ListingStatus`      | `active`, `sold`, `hidden`                                                   | `listings.status`             |
| `ListingType`        | `product`, `service`, `rental`                                               | `listings.listing_type`       |
| `StoreType`          | `goods`, `services`, `rental`                                                | `stores.store_type` (SET)     |
| `StoreStatus`        | `pending`, `verified`, `rejected`, `blocked`                                 | `stores.status` (menggantikan `verification_status`) |
| `UserStatus`         | `menunggu`, `terverifikasi`, `ditolak`, `diblokir`                           | `users.status`                |
| `PaymentMethod`      | `cod`, `transfer`                                                            | `orders.payment_method`       |
| `DeliveryMethod`     | `pickup`, `delivery`                                                         | `orders.delivery_method`      |
| `DisputeStatus`      | `open`, `resolved`                                                           | `disputes.status`             |
| `DisputeReason`      | `barang_tidak_sesuai`, `jasa_tidak_profesional`, `penyedia_tidak_responsif`, `pembeli_fiktif`, `lainnya` | `disputes.reason` |
| `ReviewDirection`    | `buyer_to_store`, `store_to_buyer`                                           | `reviews.direction`           |
| `VerificationLevel`  | `1`, `2`, `3` (int)                                                          | **TURUNAN** — bukan kolom lagi; dihitung `User::verificationLevel` (DATABASE.md §4.1) |

> ℹ️ **Perubahan sesudah panduan ini ditulis:** kolom `users.verification_level`
> sudah dihapus. Level pengguna kini murni turunan dari stempel verifikasi +
> status toko, supaya tidak ada dua sumber kebenaran yang bisa berbeda
> pendapat. Cuplikan di panduan ini yang masih menulis `verification_level`
> ke `users` terbit dari desain awal — ikuti DATABASE.md §4.1 sebagai
> kebenaran terkini.

> ⚠️ **Perhatikan bedanya:** `StoreType` memakai bentuk **jamak**
> (`goods`, `services`) karena kolomnya bertipe SET dan menampung kombinasi —
> "toko ini menjual barang dan jasa". Sementara `ListingType` dan `OrderType`
> memakai bentuk **tunggal** (`product`, `service`) karena masing-masing hanya
> satu nilai.
>
> `ListingType` dan `OrderType` **wajib identik** — nilainya disalin langsung
> saat pesanan dibuat dari listing. Jangan "merapikan" salah satunya tanpa
> mengubah yang lain beserta migrasinya. Lihat `DATABASE.md` §4.2 dan §4.7.

`VerificationLevel` bertipe integer, bukan string:

```php
// app/Enums/VerificationLevel.php
namespace App\Enums;

enum VerificationLevel: int
{
    case Basic    = 1;  // Nomor HP terverifikasi
    case Verified = 2;  // KTP diverifikasi — syarat membuka toko
    case Pro      = 3;  // Usaha tervalidasi, prioritas broadcast lebih tinggi

    public function canOpenStore(): bool
    {
        return $this->value >= self::Verified->value;
    }
}
```

Pakai di model lewat casting agar konversi otomatis:

```php
// app/Models/Order.php
protected function casts(): array
{
    return [
        'status'         => OrderStatus::class,
        'order_type'     => OrderType::class,
        'payment_method' => PaymentMethod::class,
    ];
}
```

### ⚠️ Catatan: Status Alur Jasa Belum Ada di ENUM

`PRD.md` §5.4 menjelaskan alur pesanan **jasa** dengan status `Dijadwalkan`,
`Dalam Pengerjaan`, dan `Menunggu Konfirmasi Pembeli`. Ketiganya **tidak ada**
di ENUM `orders.status` pada `DATABASE.md`, yang hanya punya enam nilai.

Ini perlu diputuskan sebelum modul pesanan dibangun. Dua opsi:

1. **Petakan ke status yang ada** — `Dijadwalkan` dan `Dalam Pengerjaan`
   keduanya jadi `diproses`, detail waktunya disimpan di kolom terpisah.
   MVP lebih sederhana, tapi UI kehilangan sebagian informasi.
2. **Tambahkan nilai ENUM baru** — perlu migrasi `ALTER TABLE` dan
   memperluas `OrderStatus`. Lebih sesuai PRD, tapi state machine jadi
   bercabang per `order_type`.

Selama belum diputuskan, `OrderStatus` di atas mengikuti `DATABASE.md`
(sumber kebenaran skema).

### Kenapa `app/Jobs/` & `app/Listeners/` Dipisah

Alur broadcast sengaja dipecah agar request API tetap cepat:

```
POST /requests
  └─> simpan customer_requests
  └─> event CustomerRequestCreated        (sinkron, ringan)
        └─> listener DispatchRequestBroadcast
              └─> dispatch BroadcastRequestJob   (masuk antrian Redis)
                    └─> BroadcastService cari penyedia dalam radius
                    └─> NotificationSender->notifyStoresOfRequest(stores, request)
```

Response ke pembeli langsung kembali setelah data tersimpan; pencarian penyedia
dan pengiriman notifikasi berjalan di worker. Konsekuensinya notifikasi
**tidak instan** — ini sudah dicatat juga di Mobile Guide.

```php
// app/Jobs/BroadcastRequestJob.php
namespace App\Jobs;

use App\Models\CustomerRequest;
use App\Services\BroadcastService;
use App\Services\Contracts\NotificationSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BroadcastRequestJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(public readonly string $requestId) {}

    public function handle(BroadcastService $broadcast, NotificationSender $notifier): void
    {
        $request = CustomerRequest::withCoordinates()->find($this->requestId);

        // Permintaan bisa saja sudah ditutup sebelum job sempat jalan.
        if ($request === null || ! $request->isOpen()) {
            return;
        }

        $stores = $broadcast->matchingStores($request);

        if ($stores->isEmpty()) {
            return;
        }

        $notifier->notifyStoresOfRequest($stores, $request);
    }
}
```

> Kirim **ID**, bukan objek model, ke constructor job. Model yang di-serialize
> bisa basi saat job akhirnya dieksekusi.

---

## 5. MIDDLEWARE & PIPELINE

> ⚠️ **`app/Http/Kernel.php` sudah tidak ada sejak Laravel 11.** Semua
> registrasi middleware kini terpusat di `bootstrap/app.php`. Panduan lama yang
> menyuruh mendaftarkan alias di Kernel tidak berlaku untuk Laravel 13.

### 5.1 Registrasi Middleware (`bootstrap/app.php`)

```php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',
        api:      __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health:   '/up',
        then: function () {
            // Route admin dipisah agar prefix & middleware-nya tidak
            // tercampur dengan web publik SEO.
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias dari Spatie Permission — inilah yang membuat 'role:admin'
        // dan 'permission:manage-users' bisa dipakai di route.
        $middleware->alias([
            'role'              => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'        => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission'=> \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'store.owner'       => \App\Http\Middleware\EnsureStoreOwner::class,
            'profile.complete'  => \App\Http\Middleware\EnsureProfileComplete::class,
            'user.active'       => \App\Http\Middleware\EnsureUserNotBlocked::class,
            'https'             => \App\Http\Middleware\EnsureHttps::class,
        ]);

        // Terminasi HTTPS di produksi (redirect + HSTS); aman di dev (no-op).
        $middleware->append(\App\Http\Middleware\EnsureHttps::class);

        // Cookie-based auth hanya untuk domain di SANCTUM_STATEFUL_DOMAINS.
        $middleware->statefulApi();

        // Throttle bawaan untuk seluruh grup api.
        $middleware->api(append: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
        );
    })->create();
```

### 5.2 Daftar Middleware

| Alias | Dipakai di | Fungsi |
| :-- | :-- | :-- |
| `auth` | web admin | Sesi login Laravel |
| `auth:api` | `/api/*` | JWT Bearer token (guard `api`, driver `jwt`) |
| `role:admin\|super-admin` | web admin | Spatie — batasi ke admin (dua role panel) |
| `permission:manage-users` | per-route admin | Spatie — izin granular |
| `store.owner` | (tidak dipakai) | Terdaftar sebagai alias `store.owner`, tetapi **belum dipasang di route mana pun** — kepemilikan toko dicek lewat Policy |
| `profile.complete` | API transaksional | Menegakkan pengisian `users.location` |
| `user.active` | seluruh grup API ber-token | Menolak akun diblokir (alias dari `EnsureUserNotBlocked`) |
| `https` | global (append) | Terminasi HTTPS: redirect ke https + HSTS di produksi (`EnsureHttps`; no-op di dev) |
| `throttle:otp` | `/auth/request-otp` | 3 request/menit **per nomor** |

### 5.3 Rate Limiter Kustom

Batas OTP dihitung **per nomor telepon**, bukan per IP — kalau per IP, satu
orang bisa memanen OTP dengan berganti jaringan, dan sebaliknya pengguna satu
WiFi kantor saling memblokir.

```php
// AppServiceProvider — daftar lengkap limiter aplikasi:
RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
    ->by($request->user()?->id ?: $request->ip()));

RateLimiter::for('otp', fn (Request $request) => [
    Limit::perMinute(3)->by('otp:'.$request->input('phone')),
    Limit::perDay(10)->by('otp-daily:'.$request->input('phone')),
]);

// Verifikasi OTP dibatasi terpisah — kode 6 digit tidak boleh bisa ditebak.
RateLimiter::for('otp-verify', fn (Request $request) => Limit::perMinute(5)
    ->by('otp-verify:'.$request->input('phone')));

RateLimiter::for('offers', fn (Request $request) => Limit::perMinute(30)
    ->by('offers:'.$request->user()?->id));

// Login admin (kata sandi): dua sumbu — per akun+IP dan per IP,
// untuk menahan tebak-paksa sekaligus password spraying.
RateLimiter::for('admin-login', fn (Request $request) => [
    Limit::perMinute(5)->by('admin-login:'.$request->input('email').'|'.$request->ip()),
    Limit::perMinute(20)->by('admin-login-ip:'.$request->ip()),
]);
```

### 5.4 `EnsureProfileComplete`

`users.location` sengaja NULL-able (lihat `DATABASE.md` §4.1), sehingga
kewajiban mengisi lokasi ditegakkan middleware — bukan constraint database.

```php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    // Satu definisi kelengkapan (User::isProfileComplete): nama + lokasi.
    // Bila syaratnya berubah, middleware tidak ikut ketinggalan.
    if ($user && ! $user->isProfileComplete()) {
        return response()->json([
            'success' => false,
            'message' => 'Lengkapi profil (nama & lokasi) terlebih dahulu.',
            'errors'  => ['profile' => ['incomplete']],
        ], 403);
    }

    return $next($request);
}
```

Middleware ini **tidak** dipasang pada `PATCH /auth/profile` sendiri — kalau
dipasang, pengguna baru terkunci dan tidak akan pernah bisa melengkapi profil.

---

## 6. AUTENTIKASI & OTORISASI

### 6.1 JWT & Token (API)

Autentikasi API mobile memakai **JWT stateless** (`tymon/jwt-auth`), sementara
panel admin memakai **sesi cookie Laravel**. Dua kanal ini tidak boleh
tertukar — API mobile tidak pernah mengirim cookie, dan panel admin tidak
pernah mengirim Bearer token.

| Kanal                     | Mode          | Mekanisme                                  |
| :------------------------ | :------------ | :------------------------------------------ |
| **Mobile app** (`/api/*`) | **Stateless** | Bearer JWT (`auth:api`, driver `jwt`), tanpa cookie |
| **Admin panel** (web)     | **Stateful**  | Session cookie Laravel biasa (guard `web`)  |

**Alur masuk (OTP → JWT):**

```php
// AuthController::verifyOtp — setelah OTP cocok:
$token = auth('api')->login($user);   // JWT stateless, TIDAK disimpan di DB

return $this->ok([
    'token'      => $token,
    'token_type' => 'Bearer',
    'expires_in' => self::TOKEN_TTL_MINUTES * 60,   // 30 hari (config/jwt.php ttl)
    'user'       => new UserResource($user),
]);
```

**Konfigurasi (`config/jwt.php` & `.env`):**

```env
JWT_SECRET=<hasil php artisan jwt:secret>
JWT_TTL=43200          # 30 hari — umur token
JWT_REFRESH_TTL=20160  # 14 hari — jendela refresh sejak token pertama
JWT_BLACKLIST_ENABLED=true
```

**Refresh tanpa login ulang** (`POST /auth/refresh`) — token lama di-blacklist,
token baru diterbitkan; hanya berlaku selama token saat ini masih valid dan
masih dalam jendela `refresh_ttl`:

```php
$newToken = auth('api')->refresh();
return $this->ok(['token' => $newToken, 'token_type' => 'Bearer', 'expires_in' => ...]);
```

**Logout** (`POST /auth/logout`) — JWT tidak punya tabel token (berbeda dari
Sanctum yang menghapus baris DB), jadi logout dilakukan dengan mem-blacklist
token saat ini:

```php
auth('api')->logout();   // token masuk blacklist sampai kedaluwarsa
```

**Melindungi route API:**

```php
Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    // ...
});
```

**Sanctum masih terpasang untuk dua hal:** (1) autentikasi stateful berbasis
cookie pada domain `SANCTUM_STATEFUL_DOMAINS` (web admin via
`$middleware->statefulApi()` — jangan pernah memasukkan `api.seekitar.id` ke
daftar itu, atau request mobile akan menuntut CSRF dan gagal dengan 419), dan
(2) kompatibilitas token Sanctum lama yang masih hidup selama masa transisi
(guard `sanctum` tetap ada di `config/auth.php`).

**Guard untuk Spatie Permission** — karena ada dua kanal, `config/permission.php`
harus mengenali keduanya. Model `User` perlu tahu guard mana yang dipakai:

```php
// config/auth.php — pastikan ketiga guard ada
'guards' => [
    'web'     => ['driver' => 'session', 'provider' => 'users'],
    'api'     => ['driver' => 'jwt',     'provider' => 'users'],
    'sanctum' => ['driver' => 'sanctum', 'provider' => 'users'],   // transisi
],
```

### 6.2 Spatie Permission (Roles & Abilities)

**Role:**

- `super-admin` (akses semua, kelola admin)
- `admin` (akses panel admin kecuali manajemen admin lain)
- `user` (default pengguna biasa)

**Permissions (abilities) untuk Admin (17, sesuai `RolesAndPermissionsSeeder::PERMISSIONS`):**

- `manage-users`
- `verify-users`
- `manage-stores`
- `verify-stores`
- `manage-categories`
- `manage-listings`
- `manage-requests`
- `manage-offers`
- `manage-orders`
- `manage-disputes`
- `manage-reviews`
- `manage-settings`
- `manage-subscriptions`
- `manage-advertisements`
- `manage-fees`
- `manage-blog`
- `manage-whatsapp`

Semua permission diberikan pada role `super-admin`. Role `admin` bisa diberikan sebagian (misal tidak bisa `manage-users` untuk mencegah hapus sesama admin — `ADMIN_EXCLUDED` di seeder berisi `manage-users`, `manage-settings`, dan `manage-fees`).

**Cara assign (Seeder):** lihat bagian 19.

#### ⚠️ Guard: sumber kebingungan utama

Spatie menyimpan `guard_name` **di setiap baris** role dan permission. Sebuah
permission bermilik `guard_name = 'web'` **tidak terlihat** oleh pengguna yang
diautentikasi lewat guard `api`, meskipun namanya sama persis. Ini penyebab
`can()` mendadak mengembalikan `false` di API padahal berfungsi di web admin.

Seekitar sengaja memakai pembagian berikut:

| Kanal | Guard | Otorisasi memakai |
| :-- | :-- | :-- |
| Web admin | `web` | **Spatie** role & permission (`role:admin\|super-admin`, lalu `permission:*` granular per route/blok) |
| API mobile | `api` (JWT) | **Policy** kepemilikan (Store/Offer/Order/dsb.) + middleware `permission:*` untuk endpoint admin API |

Artinya seluruh role/permission Spatie cukup dibuat untuk guard `web` saja —
persis seperti seeder di §19.2. Pengguna biasa di aplikasi mobile tidak
membutuhkan baris permission sama sekali; haknya ditentukan kepemilikan data
(lewat Policy).

```php
// config/permission.php
'models' => [
    'permission' => Spatie\Permission\Models\Permission::class,
    'role'       => Spatie\Permission\Models\Role::class,
],

// Guard default saat membuat role/permission tanpa menyebut guard.
'defaults' => ['guard' => 'web'],
```

> Endpoint admin API **memang** memakai `permission:*` di atas Bearer JWT —
> dan itu bekerja karena model `User` tidak mendefinisikan `guard_name`,
> sehingga Spatie mengecek role/permission guard `web` (default) apa pun
> kanal autentikasinya. Yang perlu dicegah justru mengganti guard default ke
> `sanctum` atau `api`: itu akan mematikan seluruh otorisasi web admin.

### 6.3 Gates & Policies

Policies untuk API (StorePolicy, ListingPolicy, dll.) tetap sama.  
Untuk admin web, otorisasi dibaca langsung dari permission Spatie: middleware `permission:*` di route, `@can(...)` di blade, dan `$request->user()->can(...)` di controller — tanpa Gate tambahan karena permission-nya sendiri sudah granular.

#### `Gate::before` untuk super-admin

Tanpa ini, setiap Policy harus mengulang pengecualian untuk super-admin.
Satu pintu lebih aman daripada belasan pemeriksaan yang bisa terlupa:

```php
// AppServiceProvider::boot()
Gate::before(function (User $user, string $ability) {
    // Mengembalikan true = izinkan semua; null = lanjutkan ke Policy.
    // JANGAN kembalikan false — itu memblokir Policy lain ikut memutuskan.
    return $user->hasRole('super-admin') ? true : null;
});
```

> ⚠️ Perhatikan `null`, bukan `false`. Mengembalikan `false` membuat seluruh
> Policy dilewati dan semua orang selain super-admin ditolak.

#### Policy untuk data milik pengguna

```php
class ListingPolicy
{
    public function update(User $user, Listing $listing): bool
    {
        return $user->id === $listing->store->user_id;
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $this->update($user, $listing)
            || $user->can('manage-listings');   // admin boleh menghapus
    }
}
```

| Policy | Aturan inti |
| :-- | :-- |
| `StorePolicy` | Hanya pemilik yang boleh `update`/`delete`; `deactivate` untuk admin |
| `ListingPolicy` | Pemilik toko; admin boleh `delete` (konten bermasalah) |
| `OfferPolicy` | Pemilik toko boleh membuat; hanya pemilik request boleh `accept` |
| `OrderPolicy` | Pembeli **atau** pemilik toko terkait; transisi status dicek `OrderStateMachine` |
| `CustomerRequestPolicy` | Pemilik boleh `update`/`delete`/`extend` permintaannya sendiri |

> Ulasan tidak memakai Policy terpisah (`ReviewPolicy` tidak ada) — pembuatan
> ulasan dijalankan lewat `OrderController::review` yang otorisasinya ditangani
> `OrderPolicy::review` (pihak pesanan `selesai`, dalam jendela 7 hari).

---

## 7. ROUTING LENGKAP

### Admin Routes (`routes/admin.php`)

Route admin dipisah dari `web.php` (didaftarkan lewat `then:` di
`bootstrap/app.php`, lihat §5.1) supaya web publik SEO tidak ikut terbebani
middleware admin.

Karena prefix `admin` dan name `admin.` sudah disetel saat pendaftaran grup,
di dalam berkas ini **tidak perlu** mengulanginya.

```php
// Login & logout berada DI LUAR grup 'auth' — kalau di dalam, halaman login
// sendiri menuntut login dan tidak ada yang bisa masuk sama sekali.
Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])
        ->middleware('throttle:admin-login')->name('login.store');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')->name('logout');

// Grup utama: role 'admin|super-admin' (bukan 'role:admin').
Route::middleware(['auth', 'role:admin|super-admin'])->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/chart', [DashboardController::class, 'chartData'])->name('dashboard.chart');

    // Akun sendiri — TANPA permission: admin berizin minimum tetap harus
    // bisa memperbaiki nama & kata sandinya sendiri.
    Route::get('profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('kata-sandi', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('kata-sandi', [PasswordController::class, 'update'])->name('password.update');

    // Endpoint AJAX Datatables — WAJIB didaftarkan SEBELUM route ber-parameter,
    // dan masing-masing memakai permission yang sama dengan halaman induknya.
    Route::get('users/data',    [UserController::class, 'data'])->middleware('permission:manage-users')->name('users.data');
    Route::get('stores/data',   [StoreController::class, 'data'])->middleware('permission:manage-stores')->name('stores.data');
    Route::get('verifications/users/data', [VerificationController::class, 'userData'])->middleware('permission:verify-users')->name('verifications.users.data');
    Route::get('verifications/stores/data', [VerificationController::class, 'storesData'])->middleware('permission:verify-stores')->name('verifications.stores.data');
    Route::get('disputes/data', [DisputeController::class, 'data'])->middleware('permission:manage-disputes')->name('disputes.data');
    Route::get('listings/data', [ListingController::class, 'data'])->middleware('permission:manage-listings')->name('listings.data');
    Route::get('orders/data',   [OrderController::class, 'data'])->middleware('permission:manage-orders')->name('orders.data');
    Route::get('requests/data', [CustomerRequestController::class, 'data'])->middleware('permission:manage-requests')->name('requests.data');
    Route::get('offers/data',   [OfferController::class, 'data'])->middleware('permission:manage-offers')->name('offers.data');
    Route::get('reviews/data',  [ReviewController::class, 'data'])->middleware('permission:manage-reviews')->name('reviews.data');
    Route::get('blog/data',     [BlogController::class, 'data'])->middleware('permission:manage-blog')->name('blog.data');

    // --- Pengguna -------------------------------------------------------
    Route::middleware('permission:manage-users')->group(function (): void {
        Route::get('users/export', [UserController::class, 'exportCsv'])->name('users.export');
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        // Blokir SATU route — field `action` pada body memutuskan blokir/buka
        // blokir (tidak ada route block & unblock terpisah).
        Route::post('users/{user}/block', [UserController::class, 'block'])->name('users.block');
    });

    // --- Toko -----------------------------------------------------------
    Route::middleware('permission:manage-stores')->group(function (): void {
        Route::get('stores/export', [StoreController::class, 'exportCsv'])->name('stores.export');
        Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
        Route::get('stores/{store}', [StoreController::class, 'show'])->name('stores.show');
        Route::get('stores/{store}/edit', [StoreController::class, 'edit'])->name('stores.edit');
        Route::put('stores/{store}', [StoreController::class, 'update'])->name('stores.update');
        // Peta sebaran toko — permission sama dengan daftar toko.
        Route::get('maps/stores', [StoreMapController::class, 'index'])->name('maps.stores');
        Route::get('maps/stores/data', [StoreMapController::class, 'data'])->name('maps.stores.data');
    });

    Route::middleware('permission:verify-stores')->group(function (): void {
        Route::post('stores/{store}/approve', [StoreController::class, 'approve'])->name('stores.approve');
        Route::post('stores/{store}/reject', [StoreController::class, 'reject'])->name('stores.reject');
    });

    // --- Verifikasi (dua halaman, dua permission) -----------------------
    Route::middleware('permission:verify-users')->group(function (): void {
        Route::get('verifications/users', [VerificationController::class, 'users'])->name('verifications.users');
        Route::post('verifications/users/{user}/verify', [VerificationController::class, 'verifyUser'])->name('verifications.users.verify');
        Route::post('verifications/users/{user}/reject', [VerificationController::class, 'rejectUser'])->name('verifications.users.reject');
        // Berkas privat (KTP/selfie) di-stream lewat PHP; `kind` dibatasi.
        Route::get('verifications/users/{user}/media/{kind}', [VerificationController::class, 'media'])
            ->name('verifications.users.media')->whereIn('kind', ['ktp', 'selfie']);
    });

    Route::middleware('permission:verify-stores')->group(function (): void {
        Route::get('verifications/stores', [VerificationController::class, 'stores'])->name('verifications.stores');
        Route::post('verifications/stores/{store}/approve', [VerificationController::class, 'approveStore'])->name('verifications.stores.approve');
        Route::post('verifications/stores/{store}/reject', [VerificationController::class, 'rejectStore'])->name('verifications.stores.reject');
    });

    // --- Kategori — resource penuh, TANPA route /data (lihat §9.2) -----
    Route::middleware('permission:manage-categories')->group(function (): void {
        Route::resource('categories', CategoryController::class)->except(['show']);
    });

    // --- Listing — hanya index/show/destroy, TANPA toggle-status -------
    Route::middleware('permission:manage-listings')->group(function (): void {
        Route::resource('listings', ListingController::class)->only(['index', 'show', 'destroy']);
    });

    // --- Permintaan -----------------------------------------------------
    Route::middleware('permission:manage-requests')->group(function (): void {
        Route::resource('requests', CustomerRequestController::class)
            ->only(['index', 'show'])->parameters(['requests' => 'customerRequest']);
        Route::post('requests/{customerRequest}/extend', [CustomerRequestController::class, 'extend'])
            ->name('requests.extend');
    });

    // --- Penawaran (hanya baca) -----------------------------------------
    Route::middleware('permission:manage-offers')->group(function (): void {
        Route::get('offers/export', [OfferController::class, 'exportCsv'])->name('offers.export');
        Route::get('offers', [OfferController::class, 'index'])->name('offers.index');
        Route::get('offers/{offer}', [OfferController::class, 'show'])->name('offers.show');
    });

    // --- Pesanan (hanya baca + bukti bayar) -----------------------------
    Route::middleware('permission:manage-orders')->group(function (): void {
        Route::get('orders/export', [OrderController::class, 'exportCsv'])->name('orders.export');
        Route::resource('orders', OrderController::class)->only(['index', 'show']);
        Route::get('orders/{order}/payment-proof', [OrderController::class, 'paymentProofMedia'])
            ->name('orders.payment-proof');
    });

    // --- Ulasan ---------------------------------------------------------
    Route::middleware('permission:manage-reviews')->group(function (): void {
        Route::resource('reviews', ReviewController::class)->only(['index', 'destroy']);
    });

    // --- Blog -----------------------------------------------------------
    Route::middleware('permission:manage-blog')->group(function (): void {
        Route::resource('blog', BlogController::class)
            ->parameters(['blog' => 'post'])->except(['show']);
    });

    // --- Pengaturan sistem (hanya super-admin lewat permission) ---------
    Route::middleware('permission:manage-settings')->group(function (): void {
        Route::get('settings', [SettingController::class, 'index'])->name('settings');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        // Wallet — verifikasi top-up & selesaikan/tolak penarikan.
        Route::get('wallet', [WalletController::class, 'index'])->name('wallet.index');
        Route::post('wallet/topups/{transaction}/confirm', [WalletController::class, 'confirmTopup'])->name('wallet.topups.confirm');
        Route::post('wallet/topups/{transaction}/cancel', [WalletController::class, 'cancelTopup'])->name('wallet.topups.cancel');
        Route::post('wallet/withdrawals/{transaction}/complete', [WalletController::class, 'completeWithdrawal'])->name('wallet.withdrawals.complete');
        Route::post('wallet/withdrawals/{transaction}/reject', [WalletController::class, 'rejectWithdrawal'])->name('wallet.withdrawals.reject');
    });

    // --- Laporan masalah -------------------------------------------------
    Route::middleware('permission:manage-disputes')->group(function (): void {
        Route::get('disputes', [DisputeController::class, 'index'])->name('disputes.index');
        Route::get('disputes/{dispute}', [DisputeController::class, 'show'])->name('disputes.show');
        Route::get('disputes/{dispute}/info', [DisputeController::class, 'info'])->name('disputes.info');
        Route::post('disputes/{dispute}/resolve', [DisputeController::class, 'resolve'])->name('disputes.resolve');
    });

    // --- Langganan & Boost Listing --------------------------------------
    Route::middleware('permission:manage-subscriptions')->group(function (): void {
        Route::get('subscriptions/data', [SubscriptionController::class, 'data'])->name('subscriptions.data');
        Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
        Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    });

    // --- Iklan Banner ----------------------------------------------------
    Route::middleware('permission:manage-advertisements')->group(function (): void {
        Route::get('advertisements/data', [AdvertisementController::class, 'data'])->name('advertisements.data');
        Route::resource('advertisements', AdvertisementController::class)
            ->parameters(['advertisements' => 'advertisement']);
    });

    // --- Biaya Layanan ---------------------------------------------------
    Route::middleware('permission:manage-fees')->group(function (): void {
        Route::get('fees/data', [FeeController::class, 'data'])->name('fees.data');
        Route::get('fees', [FeeController::class, 'index'])->name('fees.index');
        Route::post('fees/update-settings', [FeeController::class, 'updateSettings'])->name('fees.update-settings');
    });

    // --- Gateway WhatsApp (Baileys) — scan QR & status koneksi -----------
    Route::middleware('permission:manage-whatsapp')->group(function (): void {
        Route::get('whatsapp', [WhatsAppController::class, 'index'])->name('whatsapp.index');
        Route::get('whatsapp/status', [WhatsAppController::class, 'status'])->name('whatsapp.status');
        Route::get('whatsapp/qr', [WhatsAppController::class, 'qr'])->name('whatsapp.qr');
        Route::post('whatsapp/logout', [WhatsAppController::class, 'logout'])->name('whatsapp.logout');
        Route::post('whatsapp/reset', [WhatsAppController::class, 'reset'])->name('whatsapp.reset');
        Route::post('whatsapp/send-test', [WhatsAppController::class, 'sendTest'])->name('whatsapp.send-test');
    });
});
```

### API Routes (`routes/api.php`)

Kontrak lengkapnya ada di [`API_DOCUMENTATION.md`](API_DOCUMENTATION.md).
Yang penting diperhatikan di sisi routing adalah **penempatan middleware**:

```php
Route::prefix('v1')->group(function (): void {

    // --- Publik — tanpa token (hanya dua ini) ---------------------------
    Route::post('auth/request-otp', [AuthController::class, 'requestOtp'])
        ->middleware('throttle:otp');                    // per nomor, bukan IP
    Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:otp-verify');

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('config', [SettingController::class, 'publicConfig']);

    // --- Perlu login: auth:api + user.active ----------------------------
    // Middleware `user.active` menjaga seluruh endpoint ber-token — akun
    // yang diblokir ditolak 423 apa pun yang dimintanya.
    Route::middleware(['auth:api', 'user.active'])->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::patch('auth/profile', [AuthController::class, 'updateProfile']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::post('auth/phone/request-otp', [AuthController::class, 'requestPhoneChangeOtp'])
            ->middleware('throttle:otp');
        Route::post('auth/phone/verify-otp', [AuthController::class, 'verifyPhoneChangeOtp'])
            ->middleware('throttle:otp-verify');
        Route::get('auth/export-data', [AuthController::class, 'exportData']);
        Route::delete('auth/account', [AuthController::class, 'requestDeletion']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        // Registrasi perangkat — token FCM aplikasi disimpan di user_devices.
        Route::post('auth/fcm-token', [DeviceController::class, 'store']);
        Route::delete('auth/fcm-token', [DeviceController::class, 'destroy']);
        Route::post('auth/verification/ktp', [VerificationController::class, 'uploadKtp']);
        Route::get('auth/verification/photo/{kind}', [VerificationController::class, 'myPhoto'])
            ->whereIn('kind', ['ktp', 'selfie']);

        // Unggah gambar — LANGSUNG di grup authed, bukan di profile.complete.
        Route::post('uploads/images', [UploadController::class, 'store']);
        Route::delete('uploads/images', [UploadController::class, 'destroy']);

        // --- Home / pencarian / penjelajahan (perlu login) --------------
        Route::get('home', [HomeController::class, 'index']);
        Route::get('search/suggestions', [SearchController::class, 'suggestions']);
        Route::get('stores/nearby', [StoreController::class, 'nearby']);
        Route::get('stores/mine', [StoreController::class, 'mine']);
        Route::get('stores/{store}', [StoreController::class, 'show']);
        Route::get('stores/{store}/reviews', [StoreController::class, 'reviews']);
        Route::get('stores/{store}/dashboard', [StoreDashboardController::class, 'show']);
        Route::get('listings', [ListingController::class, 'index']);
        Route::get('listings/{listing}', [ListingController::class, 'show']);
        Route::get('listings/{listing}/share', [ShareController::class, 'listing']);

        // --- Wishlist / notifikasi / chat / blokir / laporan -------------
        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('listings/{listing}/favorite', [FavoriteController::class, 'store']);
        Route::delete('listings/{listing}/favorite', [FavoriteController::class, 'destroy']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::get('notifications/preferences', [NotificationPreferenceController::class, 'show']);
        Route::patch('notifications/preferences', [NotificationPreferenceController::class, 'update']);

        Route::get('conversations', [ConversationController::class, 'index']);
        Route::post('conversations', [ConversationController::class, 'store']);
        Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
        Route::get('conversations/{conversation}/messages', [ConversationController::class, 'messages']);
        Route::post('conversations/{conversation}/messages', [ConversationController::class, 'send']);

        Route::get('users/blocked', [BlockController::class, 'index']);
        Route::post('users/{user}/block', [BlockController::class, 'block']);
        Route::delete('users/{user}/block', [BlockController::class, 'unblock']);

        Route::post('reports', [ReportController::class, 'store']);

        // --- Transaksional (wajib profil lengkap: nama + lokasi) ---------
        Route::middleware('profile.complete')->group(function (): void {
            Route::post('stores', [StoreController::class, 'store']);
            Route::patch('stores/{store}', [StoreController::class, 'update']);

            Route::post('listings', [ListingController::class, 'store']);
            Route::match(['put', 'patch'], 'listings/{listing}', [ListingController::class, 'update']);
            Route::delete('listings/{listing}', [ListingController::class, 'destroy']);

            Route::get('requests', [CustomerRequestController::class, 'index']);
            Route::get('requests/mine', [CustomerRequestController::class, 'mine']);
            Route::post('requests', [CustomerRequestController::class, 'store']);
            Route::get('requests/{customerRequest}', [CustomerRequestController::class, 'show']);
            Route::patch('requests/{customerRequest}', [CustomerRequestController::class, 'update']);
            Route::delete('requests/{customerRequest}', [CustomerRequestController::class, 'destroy']);
            Route::post('requests/{customerRequest}/extend', [CustomerRequestController::class, 'extend']);
            Route::get('requests/{customerRequest}/offers', [CustomerRequestController::class, 'offers']);

            // Penawaran & pesanan — review/dispute lewat OrderController,
            // BUKAN controller terpisah.
            Route::post('requests/{customerRequest}/offers', [OfferController::class, 'store'])
                ->middleware('throttle:offers');
            Route::get('offers/{offer}', [OfferController::class, 'show']);
            Route::patch('offers/{offer}/accept', [OfferController::class, 'accept']);

            Route::get('orders', [OrderController::class, 'index']);
            Route::post('orders', [OrderController::class, 'store']);
            Route::get('orders/{order}', [OrderController::class, 'show']);
            Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
            Route::post('orders/{order}/payment-proof', [OrderController::class, 'uploadPaymentProof']);
            Route::get('orders/{order}/payment-proof', [OrderController::class, 'paymentProof']);
            Route::post('orders/{order}/review', [OrderController::class, 'review']);
            Route::post('orders/{order}/disputes', [OrderController::class, 'dispute']);

            // Wallet & alamat & kupon
            Route::get('wallet', [WalletController::class, 'show']);
            Route::get('wallet/transactions', [WalletController::class, 'transactions']);
            Route::post('wallet/topup', [WalletController::class, 'topup']);
            Route::post('wallet/withdraw', [WalletController::class, 'withdraw']);

            Route::get('addresses', [UserAddressController::class, 'index']);
            Route::post('addresses', [UserAddressController::class, 'store']);
            Route::patch('addresses/{address}', [UserAddressController::class, 'update']);
            Route::delete('addresses/{address}', [UserAddressController::class, 'destroy']);
            Route::patch('addresses/{address}/default', [UserAddressController::class, 'setDefault']);

            Route::post('coupons/validate', [CouponController::class, 'validate']);
            Route::post('coupons/apply', [CouponController::class, 'apply']);
        });

        // --- Endpoint admin API — permission granular Spatie ------------
        Route::prefix('admin')->group(function (): void {
            Route::get('verifications/pending', [AdminVerificationController::class, 'pending'])
                ->middleware('permission:verify-users');
            Route::post('verifications/users/{user}/approve', [AdminVerificationController::class, 'approveUser'])
                ->middleware('permission:verify-users');
            Route::post('verifications/users/{user}/reject', [AdminVerificationController::class, 'rejectUser'])
                ->middleware('permission:verify-users');
            Route::post('verifications/stores/{store}/approve', [AdminVerificationController::class, 'approveStore'])
                ->middleware('permission:verify-stores');
            Route::post('verifications/stores/{store}/reject', [AdminVerificationController::class, 'rejectStore'])
                ->middleware('permission:verify-stores');

            Route::get('users', [AdminUserController::class, 'index'])
                ->middleware('permission:manage-users');
            Route::patch('users/{user}/block', [AdminUserController::class, 'block'])
                ->middleware('permission:manage-users');

            Route::get('categories', [AdminCategoryController::class, 'index'])
                ->middleware('permission:manage-categories');
            Route::post('categories', [AdminCategoryController::class, 'store'])
                ->middleware('permission:manage-categories');
            Route::put('categories/{category}', [AdminCategoryController::class, 'update'])
                ->middleware('permission:manage-categories');
            Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])
                ->middleware('permission:manage-categories');

            Route::get('disputes', [AdminDisputeController::class, 'index'])
                ->middleware('permission:manage-disputes');
            Route::patch('disputes/{dispute}/resolve', [AdminDisputeController::class, 'resolve'])
                ->middleware('permission:manage-disputes');

            Route::get('settings', [AdminSettingController::class, 'index'])
                ->middleware('permission:manage-settings');
            Route::post('settings', [AdminSettingController::class, 'update'])
                ->middleware('permission:manage-settings');
        });
    });
});
```

> ⚠️ **Penempatan middleware.** Hanya `auth/request-otp`, `auth/verify-otp`,
> `categories`, dan `config` yang publik. `stores/nearby`, `listings*`, dan
> seluruh endpoint lain menuntut token (`auth:api` + `user.active`) — tidak
> ada endpoint publik pembaca toko/listing. `uploads/images` berada langsung
> di grup authed, **bukan** di bawah `profile.complete`.
>
> ⚠️ **Urutan `requests/mine` sebelum `requests/{customerRequest}`.** Jika
> terbalik, kata `mine` akan ditangkap sebagai `{customerRequest}` dan
> menghasilkan 404. Masalah yang sama berlaku untuk seluruh route `*/data`
> di admin.

---

## 8. ADMIN PANEL – MENU & NAVIGASI

Layout admin menggunakan sidebar Bootstrap 5.3.x.  
**Sidebar Menu:**

- Dashboard (icon: home)
- Verifikasi (dropdown)
  - Verifikasi Pengguna
  - Verifikasi Toko
- Manajemen Data (divider)
  - Pengguna (icon: users)
  - Kategori (icon: tags)
  - Toko (icon: store)
  - Listing (icon: boxes)
  - Permintaan (icon: question-circle)
  - Penawaran (icon: hand-paper)
  - Pesanan (icon: shopping-cart)
  - Dispute (icon: exclamation-triangle)
  - Ulasan (icon: star)
- Pengaturan (icon: gear)

**Top Navbar:** Brand “Seekitar Admin”, profil admin, tombol logout.

**Breadcrumb:** Setiap halaman menampilkan breadcrumb dinamis (misal: Dashboard > Manajemen Data > Kategori).

```blade
{{-- resources/views/layouts/admin.blade.php --}}
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item">
      <a href="{{ route('admin.dashboard') }}">Dashboard</a>
    </li>
    @yield('breadcrumb')
  </ol>
</nav>
```

```blade
{{-- resources/views/admin/categories/index.blade.php --}}
@section('breadcrumb')
  <li class="breadcrumb-item">Manajemen Data</li>
  <li class="breadcrumb-item active" aria-current="page">Kategori</li>
@endsection
```

> `aria-label` dan `aria-current` bukan hiasan — tanpa keduanya, pembaca layar
> membacakan breadcrumb sebagai deretan tautan tanpa konteks.

**Menu sidebar mengikuti permission.** Menu yang tidak bisa diakses **tidak
ditampilkan**, bukan ditampilkan lalu ditolak saat diklik:

```blade
@can('manage-categories')
  <li class="nav-item">
    <a href="{{ route('admin.categories.index') }}"
       class="nav-link text-white {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
      <i class="fa-solid fa-tags me-2"></i> Kategori
    </a>
  </li>
@endcan

@canany(['verify-users', 'verify-stores'])
  <li class="nav-item">
    <a href="#verifyMenu" data-bs-toggle="collapse" class="nav-link text-white">
      <i class="fa-solid fa-circle-check me-2"></i> Verifikasi
    </a>
    <ul class="collapse list-unstyled ps-3" id="verifyMenu">
      @can('verify-users')
        <li><a href="{{ route('admin.verifications.users') }}" class="nav-link text-white">Pengguna</a></li>
      @endcan
      @can('verify-stores')
        <li><a href="{{ route('admin.verifications.stores') }}" class="nav-link text-white">Toko</a></li>
      @endcan
    </ul>
  </li>
@endcanany
```

> ⚠️ **`@can` di menu hanya menyembunyikan tautan, bukan mengamankan halaman.**
> Otorisasi sesungguhnya tetap di middleware route dan `$this->authorize()` pada
> controller (§6.3) — pengguna bisa saja mengetik URL-nya langsung.
>
> `@canany` dipakai untuk induk dropdown: menu "Verifikasi" harus tetap muncul
> bila admin punya **salah satu** dari dua izin tersebut.

#### 8.1 Menu dan route WAJIB memakai permission yang sama

Ketidakcocokan antara `@can` di menu dan `permission:` di route **tidak
menimbulkan error di mana pun**. Akibatnya salah satu dari dua hal, yang
keduanya hanya ketahuan dari keluhan pengguna:

| Kesalahan | Gejala |
| :-- | :-- |
| Menu lebih longgar dari route | Menu tampil, diklik, lalu **403** — menu berbohong |
| Menu lebih ketat dari route | Menu tersembunyi padahal admin berhak — **fitur hilang diam-diam** |

Karena itu pasangannya ditegakkan otomatis oleh `tools/dev/check-admin-menu.mjs`,
yang membaca `@can` dari `resources/views/admin/partials/sidebar.blade.php` dan
membandingkannya dengan middleware hasil `artisan route:list --json`.

**Endpoint JSON Datatables ikut dijaga.** `admin/users/data` mengembalikan isi
tabel yang sama dengan halamannya; kalau hanya halamannya yang diberi
`permission:manage-users`, admin tanpa izin tetap bisa memanggil endpoint
datanya langsung meski menunya tersembunyi.

**Halaman akun sendiri justru TIDAK boleh menuntut permission.** `admin/profil`
dan `admin/kata-sandi` hanya menyentuh `$request->user()`. Kalau keduanya
diletakkan di dalam grup `permission:`, admin dengan izin paling sedikit tidak
akan pernah bisa mengganti kata sandi default-nya sendiri.

#### 8.2 Dua permission verifikasi = dua halaman

`verify-users` dan `verify-stores` adalah permission terpisah (§6.2), sehingga
antriannya juga dipisah menjadi `admin/verifications/users` dan
`admin/verifications/stores`. Satu halaman gabungan akan memaksa admin yang
hanya punya salah satunya melihat data yang bukan haknya.

---

## 9. HALAMAN ADMIN – DETAIL TAMPILAN & FORM

### 9.1 Dashboard Admin

- **Antrian kerja (paling atas):** KTP menunggu, toko menunggu, laporan terbuka,
  pesanan dalam sengketa. Ditaruh sebelum KPI karena dasbor pertama-tama harus
  menjawab _"apa yang harus saya kerjakan"_, baru _"bagaimana keadaannya"_.
- **Cards:** Total Pengguna, Toko Aktif, Pesanan Bulan Ini, Permintaan Terbuka,
  Laporan Lewat SLA, Ulasan Masuk, Listing Aktif, Penawaran Menunggu.
- **Chart:** Permintaan & pesanan baru per hari (Chart.js), rentang 7/14/30 hari.
- **Tabel ringkas:** 5 permintaan terbaru, 5 penawaran terbaru.

> ⚠️ **Kartu KPI adalah data, dan ikut disaring permission.** Angka
> "4.812 pengguna" tetap membocorkan ukuran basis pengguna kepada admin yang
> tidak punya `manage-users`. Karena itu tiap blok dibungkus
> `Gate::allows()` di controller — **query-nya tidak dijalankan sama sekali**
> bila admin tidak berhak, bukan sekadar disembunyikan di Blade. Menyembunyikan
> di view berarti server tetap membayar biaya `COUNT` untuk angka yang tidak
> akan pernah ditampilkan.

**Definisi "Laporan Lewat SLA"** sama persis dengan `Dispute::isOverdue()`:
status `open`, `first_responded_at` masih NULL, dan `response_deadline` sudah
lewat. Menghitung semua laporan terbuka membuat lencana ini selalu menyala dan
berhenti berarti apa-apa.

**Lencana sidebar memakai View Composer, bukan variabel dari controller.**
Sidebar tampil di semua halaman; mengirim angkanya dari tiap controller berarti
12 tempat mengulang query yang sama, dan satu yang lupa membuat lencana hilang
di halaman itu saja — tampak seperti antrian yang mendadak kosong. Lihat
`app/View/Composers/SidebarComposer.php`; hasilnya di-cache 60 detik.

**Controller** — data grafik disajikan lewat endpoint terpisah supaya halaman
tidak menunggu agregasi selesai:

```php
public function chartData(): JsonResponse
{
    $this->authorize('viewAny', CustomerRequest::class);

    // Rentang 7 hari termasuk hari ini.
    $from = now()->subDays(6)->startOfDay();

    $rows = CustomerRequest::query()
        ->where('created_at', '>=', $from)
        ->selectRaw('DATE(created_at) AS d, COUNT(*) AS total')
        ->groupBy('d')
        ->pluck('total', 'd');

    // Isi hari kosong dengan 0 — tanpa ini grafik "melompat".
    $labels = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'));

    return response()->json([
        'labels' => $labels->map(fn ($d) => Carbon::parse($d)->translatedFormat('D, d M')),
        'data'   => $labels->map(fn ($d) => (int) ($rows[$d] ?? 0)),
    ]);
}
```

**Blade + Chart.js:**

```blade
<canvas id="requestChart" height="80"></canvas>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
fetch("{{ route('admin.dashboard.chart') }}")
  .then(r => r.json())
  .then(({ labels, data }) => {
    new Chart(document.getElementById('requestChart'), {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Permintaan Baru',
          data,
          borderColor: '#0d6efd',
          backgroundColor: 'rgba(13,110,253,.1)',
          fill: true,
          tension: .3,
        }],
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
      },
    });
  });
</script>
@endpush
```

> Query di atas memakai `GROUP BY DATE(created_at)` yang **tidak** bisa
> memanfaatkan indeks. Untuk 7 hari data masih murah, tetapi jika dashboard
> nanti menampilkan rentang setahun, simpan agregat harian di tabel terpisah
> lewat scheduler.

### 9.2 Manajemen Kategori

**Index (`/admin/categories`):**

- **Bukan Datatables** — daftar induk (list-group) dengan subkategori di
  bawahnya (`Category::with('children')->whereNull('parent_id')->orderBy('sort_order')`).
  Taksonomi kategori kecil dan selalu dimuat penuh, jadi server-side
  processing justru menambah kerumitan tanpa manfaat.
- Tombol "Tambah Kategori" (halaman form terpisah).
- Aksi: Edit (halaman), Hapus (konfirmasi delete — ditolak bila masih punya
  subkategori, masih dipakai permintaan, atau masih dipakai toko; lihat §10).

**Create/Edit (halaman form `admin.categories.form`):**

- Nama (text)
- Slug (text, auto-generated dari nama bila kosong)
- Induk (select dari kategori **teratas** existing, nullable)
- Ikon (text, nama icon — divalidasi bebas, tanpa daftar putih)
- Urutan (number, default 0)

#### ⚠️ Mencegah loop hierarki

Hierarki dibatasi **2 level** (`DATABASE.md` §3): hanya kategori teratas yang
boleh menjadi induk, sehingga subkategori tidak punya anak. Satu-satunya siklus
yang mungkin adalah kategori menjadi induk **dirinya sendiri** (A → A) — dan
itu dicegah `Rule::notIn` pada validasi server (controller, bukan FormRequest
terpisah):

```php
// CategoryController::validated() — validasi inline, bukan Rule kustom:
'parent_id' => [
    'nullable', 'integer', 'exists:categories,id',
    Rule::notIn([$category?->id]),
],
```

Batas 2 level juga dijaga dari sisi UI: dropdown induk di form hanya memuat
kategori teratas (`Category::whereNull('parent_id')`), sehingga admin tidak
bisa memilih subkategori sebagai induk. Tidak ada aturan `NotADescendant` di
repo ini — `app/Rules/` hanya memuat `NotAWeakPassword` (§11).

### 9.3 Verifikasi Pengguna & Toko

**Index Verifikasi Pengguna (`/admin/verifications/users`):**

- Antriannya BUKAN filter level maupun tebakan kolom — satu definisi
  bersama `User::pendingVerification()`: kedudukan `menunggu` DAN
  berkasnya sudah masuk (`ktp_submitted_at` terisi), diurutkan dari
  pengajuan TERLAMA (SLA peninjauan 1×24 jam — yang paling lama menunggu
  didahulukan). Otomatis tidak antre: akun baru tanpa berkas (belum minta
  dinilai), yang sudah terverifikasi, yang ditolak sambil menyiapkan
  perbaikan, dan yang diblokir.
- Kolom: Pengguna (+ konteks "pengajuan ulang" bila jejak `rejected_*`
  masih terisi — berkas ini pernah dikembalikan, dan admin menilai apakah
  masalah lamanya sudah beres), Nomor HP (bertanda "dibuktikan OTP" —
  nomornya bukan barang yang masih dinilai di sini), Domisili, Umur
  pengajuan, Aksi.
- Aksi: klik baris → modal SOP 3 langkah membandingkan wajah ↔ KTP ↔
  NIK ↔ alamat ↔ titik koordinat (lengkap peta kecil dan tombol "Cek
  Titik di Google Maps") → satu klik **Verifikasi Pengguna** menstempel
  `verified_by`/`verified_at` sekaligus menaikkan kedudukan ke
  `terverifikasi`. Tombol Setuju terkunci sampai seluruh checklist
  dicentang (`public/js/checklist-gate.js`, dipakai bersama antrian toko).
- Tolak: collapse form di dalam modal yang sama, alasan wajib — tanpa
  alasan pengguna akan mengirim ulang berkas yang sama. Penolakan
  menjatuhkan kedudukan ke `ditolak` beserta jejak `rejected_*` yang
  juga sampai ke aplikasi pengguna.

**Index Verifikasi Toko (`/admin/verifications/stores`):**

- Antrian: toko `status = 'pending'` (kolom `stores.status` — JSON API
  tetap menyebutnya `verification_status` untuk klien lama, lihat
  `StoreResource`), terlama dulu; pemilik ikut dimuat
  (`owner:id,name,phone,verified_at`) — kelayakan pemilik adalah bagian
  dari penilaian.
- Modal SOP 3 langkah (alamat & pemilik → foto etalase ASLI → koordinat di
  peta) + checklist wajib. Foto dibaca dari kolom MENTAH
  (`getRawOriginal('photo')`): aksesor `Store::photo` menjatuhkan nilai
  kosong ke placeholder hiasan, dan bukti tidak boleh berupa dekorasi.

#### Logika Approve & Reject (implementasi aktual)

**Pengguna — SATU verifikasi, stempel tulis-sekali, baris dikunci:**

```php
public function verifyUser(Request $request, User $user): RedirectResponse
{
    $adminId = $request->user()->id;

    $disetujui = DB::transaction(function () use ($user, $adminId): bool {
        $antrian = User::lockForUpdate()->findOrFail($user->id);

        // Diblokir, atau sudah punya stempel → bukan antrian lagi; stempel
        // tulis-sekali tidak pernah ditimpa.
        if ($antrian->isBlocked() || $antrian->verified_at !== null) {
            return false;
        }

        $antrian->verified_by     = $adminId;
        $antrian->verified_at     = now();
        $antrian->status          = \App\Enums\UserStatus::Terverifikasi;

        // Jejak penolakan dibersihkan di SINI — bukan saat pengguna kirim
        // ulang berkas — supaya selama menunggu, admin tetap melihat
        // konteks "pengajuan ulang: terakhir ditolak karena X".
        $antrian->rejected_by     = null;
        $antrian->rejected_at     = null;
        $antrian->rejected_reason = null;
        $antrian->save();

        return true;
    });

    if (! $disetujui) {
        return back()->with('error',
            "Verifikasi {$user->name} sudah diproses sebelumnya — stempel tidak bisa ditimpa.");
    }

    return back()->with('success', "Identitas {$user->name} terverifikasi.");
}
```

**Pengguna — penolakan menjatuhkan kedudukan berikut jejaknya:**

```php
public function rejectUser(RejectVerificationRequest $request, User $user): RedirectResponse
{
    $user->status          = \App\Enums\UserStatus::Ditolak;
    $user->rejected_by     = $request->user()->id;
    $user->rejected_at     = now();
    $user->rejected_reason = $request->reason();
    $user->save();

    return back()->with('success', "Berkas {$user->name} ditolak — alasan terkirim ke aplikasinya.");
}
```

Stempel `verified_*` TIDAK disentuh oleh penolakan: ia berbicara tentang
pengajuan yang sedang terbuka. Kalau pemiliknya mengganti berkas lewat
API, kedudukan kembali `menunggu` dan siklusnya dimulai lagi — jejak
`rejected_*` sengaja dipertahankan sebagai konteks pengajuan ulang,
baru dibersihkan saat identitasnya akhirnya disetujui.

**Toko — dua syarat diperiksa ulang SERVER di titik persetujuan.** Logika
kedudukan toko hidup **sekali** di `App\Services\VerifikasiTokoService`
(`setujui`/`tolak`), dipakai panel web, aksi cepat di tabel toko, DAN
endpoint admin API — sebelumnya logika itu disalin tiga kali dan sudah
pernah menyimpang:

```php
// App\Services\VerifikasiTokoService::setujui
public function setujui(Store $store, int|string $adminId): string
{
    return DB::transaction(function () use ($store, $adminId): string {
        $toko = Store::lockForUpdate()->findOrFail($store->getKey());

        if ($toko->status !== StoreStatus::Pending) {
            return 'bukan-antrian';                    // stempel tulis-sekali
        }

        // Syarat (1): pemilik terverifikasi (no HP + KTP). Level bisa turun
        // selama menunggu antrian, jadi mengecek hanya saat pengajuan tidak cukup.
        $pemilik = User::lockForUpdate()->find($toko->user_id);
        if ($pemilik === null || ! $pemilik->canOpenStore()) {
            return 'pemilik-belum-terverifikasi';
        }

        // Syarat (2): foto ASLI terunggah — baca kolom mentah, bukan aksesor.
        if (empty($toko->getRawOriginal('photo'))) {
            return 'foto-belum-diunggah';
        }

        $toko->status          = StoreStatus::Verified;
        $toko->verified_at     = now();
        $toko->verified_by     = $adminId;
        $toko->rejected_at     = null;   // jejak penolakan dibersihkan di sini
        $toko->rejected_by     = null;
        $toko->rejected_reason = null;
        $toko->save();

        return 'disetujui';
    });
}
```

**Toko — penolakan juga hanya sah dari antrian** (menolak toko yang sudah
disetujui akan menyisakan stempel `verified_*` pada status `rejected` —
keadaan kontradiktif tanpa makna alur):

```php
// VerifikasiTokoService::tolak — mengembalikan bool
$toko->status          = StoreStatus::Rejected;
$toko->rejected_at     = now();
$toko->rejected_by     = $adminId;
$toko->rejected_reason = $reason;      // wajib diisi pemanggil
$toko->save();
```

Alasan wajib diisi saat menolak — tanpa itu pengguna mengajukan ulang
berkas yang sama. Saat pemilik diblokir, `seretBersamaPemilik()` menyeret
semua tokonya ke `blocked` (dengan jejak `blocked_*`), dan membuka blokir
mengembalikan kedudukan tiap toko dari jejaknya sendiri.

> ⚠️ **Verifikasi pengguna itu SATU, bukan bertahap.** Desain awal punya
> tahap 1 ("verifikasi nomor") dan tahap 2 ("verifikasi KTP") — padahal
> tahap 1 tidak menilai apa-apa: OTP yang dikirim ke nomor itu sendiri
> sudah membuktikan pemilikannya, lebih kuat daripada mata admin. Maka
> bukti nomor cukup menjadi prasyarat sistem (tanpa stempel), dan
> satu-satunya penilaian manusia adalah identitas: wajah ↔ KTP ↔ NIK ↔
> alamat ↔ titik, diselesaikan satu klik dengan stempel `verified_*`
> tunggal. Yang tidak berubah: stempel tulis-sekali dan penguncian baris.

### 9.4 Manajemen Pengguna

**Index (`/admin/users`):**

- Datatables: Nama (+ ikon centang hijau bila identitasnya terverifikasi),
  Telepon, Rating, Kedudukan, Terdaftar, Email, Alamat, Aksi.
- Filter: Kedudukan (select, opsinya persis `UserStatus::values()` —
  `menunggu`, `terverifikasi`, `ditolak`, `diblokir` — jadi pilihan di UI
  tidak bisa menyimpang dari enum). Tidak ada filter level: level murni
  turunan dan sengaja tidak ditampilkan sebagai kolom (DATABASE.md §4.1).
  Untuk API admin, filter `verification_level` tetap tersedia lewat satu
  definisi bersama `User::scopeWhereVerificationLevel()`.
- Aksi: Detail, Sunting, Blokir/Buka blokir.

**Sumber data dipisah ke kelas `App\DataTables\UsersDataTable`** supaya
definisi kolom/filter bisa diuji tanpa lapisan HTTP. Kolom nama dan
lencana kedudukan dirender lewat Blade (`admin.users._nama` dan
`admin.users._status` — warnanya satu sumber dari `UserStatus::color()`),
masuk `rawColumns(['name','status','action'])` — sisanya tetap lolos
escaping DataTables:

```php
$query = User::query()->select([
    'id', 'phone', 'name', 'email', 'address',
    'rating_avg', 'total_reviews',
    // status & verified_at ikut dipilih untuk lencana kedudukan
    // dan ikon centang pada nama — bukan kolom tampilan sendiri.
    'status', 'ktp_submitted_at', 'verified_at', 'created_at',
]);

if ($request->filled('status')) {
    $query->where('status', $request->string('status')->toString());
}

return DataTables::eloquent($query)
    ->editColumn('name', fn (User $u) => view('admin.users._nama', ['user' => $u])->render())
    ->editColumn('status', fn (User $u) => view('admin.users._status', ['user' => $u])->render())
    ->addColumn('action', fn (User $u) => view('admin.users._actions', ['user' => $u])->render())
    ->rawColumns(['name', 'status', 'action'])
    ->toJson();
```

Pola filter yang sama (nilai filter dikirim DataTables → dibaca kelas
DataTable di server) dipakai seluruh tabel lain yang punya filter
(toko, listing, permintaan, pesanan, dispute) — masing-masing lewat kelas
`App\DataTables\*` miliknya.

**Sunting Pengguna (`/admin/users/{user}/edit`, halaman penuh):**

- Strip identitas anti-salah-orang (avatar + nama + nomor + lencana
  `UserStatus`) — admin mengedit ORANG, jadi siapa yang sedang terbuka
  harus jelas di setiap layar.
- **Data Akun**: Nama; Nomor WA readonly — nomor adalah kredensial masuk
  (OTP), penggantiannya hanya milik pemilik lewat aplikasi dengan OTP ke
  nomor baru; Email (unik, nullable — hanya staf panel yang masuk pakai
  email); Foto Profil (choose file dengan pratinjau instan di browser
  sebelum tersimpan, JPEG/PNG ≤ 2 MB, disk public).
- **Domisili**: Alamat + titik koordinat lewat peta Leaflet — sama seperti
  halaman toko: klik/geser penanda mengisi kolom lintang/bujur (sinkron
  dua arah; koma desimal ditoleransi), plus tombol hapus titik. Server
  hanya menerima pasangan lengkap:

  ```php
  'latitude'  => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
  'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
  ```

  lalu menulisnya lewat `HasLocation::setLocation()` — kolom POINT memang
  tidak bisa diisi mass-assign (DATABASE.md §4.1).
- **Identitas (KTP)**: NIK (`digits:16`; keunikan dicek lewat `nik_hash`
  karena kolomnya terenkripsi tidak bisa di-`WHERE`); unggah ulang berkas
  KTP/selfie khusus pemegang izin `verify-users` — digerbang di blade
  DAN controller, disimpan di disk privat `local` (UU PDP).
- **Aturan 5**: penyuntingan admin TIDAK pernah menyentuh stempel maupun
  kedudukan — `verified_at`/`verified_by` adalah fakta audit "siapa
  menyetujui, kapan". Kebalikannya, unggah ulang berkas oleh pengguna
  sendiri lewat API mengosongkan stempelnya dan mengembalikan kedudukan
  ke `menunggu` — kepercayaan tidak boleh berdiri di atas bukti yang
  sudah diganti.

**Blokir/Buka blokir (`/admin/users/{user}/block`):**

- Memblokir menjatuhkan kedudukan ke `diblokir` (jejaknya di
  `blocked_by`/`blocked_at`/`blocked_reason`) dan WAJIB mencabut token
  Sanctum-nya — tanpa itu sesi yang sudah berjalan tetap hidup sampai
  token kedaluwarsa — sekaligus menonaktifkan seluruh tokonya
  (`is_active = false`): orang yang diblokir tidak boleh tokonya tetap
  menerima pesanan. Alasan blokir wajib diisi dan ditampilkan ke pengguna
  saat loginnya ditolak (respons `423`).
- Membuka blokir mengembalikan kedudukan ke keadaan sebelum blokir
  (stempel `verified_at` masih ada → `terverifikasi`; kalau belum ada →
  `menunggu`) dan mengosongkan jejak `blocked_*`, tetapi tokonya TIDAK
  otomatis ikut aktif lagi — pengaktifan kembali adalah keputusan terpisah
  yang layak ditinjau ulang satu per satu.

### 9.5 Manajemen Toko

**Index (`/admin/stores`):**

- Datatables: Nama, Pemilik, Tipe, Rating, Status Verifikasi, Aksi.
- Aksi: Lihat Detail, Edit, dan (permission `verify-stores`) **Setujui/Tolak**
  lewat `POST stores/{store}/approve` / `reject` — keduanya memakai
  `VerifikasiTokoService` (§9.3). Tidak ada tombol "deactivate" terpisah.

**Show Toko (`/admin/stores/{store}`):**

- Detail lengkap toko, daftar listing (tab atau table kecil), rating, ulasan.

**Edit Toko:** Nama, service_radius, operating_hours, status verifikasi.

#### Editor `operating_hours`

**Jangan** memakai textarea JSON mentah — satu koma salah membuat jam
operasional toko rusak tanpa pesan yang berguna. Pakai form terstruktur:
tujuh baris, masing-masing dengan checkbox "Buka" dan dua input `time`.

```blade
@php $days = ['senin','selasa','rabu','kamis','jumat','sabtu','minggu']; @endphp

@foreach ($days as $day)
  @php $h = old("operating_hours.$day", $store->operating_hours[$day] ?? null); @endphp
  <div class="row align-items-center mb-2">
    <div class="col-3 text-capitalize">{{ $day }}</div>
    <div class="col-2 form-check form-switch">
      <input type="checkbox" class="form-check-input day-toggle"
             name="operating_hours[{{ $day }}][is_open]" value="1"
             data-day="{{ $day }}" @checked($h)>
    </div>
    <div class="col-3">
      <input type="time" class="form-control" name="operating_hours[{{ $day }}][open]"
             value="{{ $h['open'] ?? '08:00' }}" @disabled(! $h)>
    </div>
    <div class="col-3">
      <input type="time" class="form-control" name="operating_hours[{{ $day }}][close]"
             value="{{ $h['close'] ?? '17:00' }}" @disabled(! $h)>
    </div>
  </div>
@endforeach
```

Controller mengubahnya menjadi struktur yang disimpan database — hari yang
tidak dicentang bernilai `null`, bukan dihilangkan:

```php
$hours = collect($request->validated('operating_hours'))
    ->map(fn ($v) => ($v['is_open'] ?? false)
        ? ['open' => $v['open'], 'close' => $v['close']]
        : null)
    ->all();

$store->update(['operating_hours' => $hours]);
```

Validasinya menegakkan aturan di `API_DOCUMENTATION.md` §3.1 — tujuh kunci
wajib ada, dan jam tutup harus setelah jam buka:

```php
'operating_hours'          => ['required', 'array', 'size:7'],
'operating_hours.*.open'   => ['required_with:operating_hours.*.is_open', 'date_format:H:i'],
'operating_hours.*.close'  => ['required_with:operating_hours.*.is_open', 'date_format:H:i', 'after:operating_hours.*.open'],
```

### 9.6 Manajemen Listing

**Index (`/admin/listings`):**

- Datatables: Judul, Toko, Tipe, Harga, Status, Aksi.
- Filter: status (active/sold/hidden), tipe, toko.
- Aksi: Lihat, Hapus (soft delete). Route-nya `resource` **hanya**
  `index/show/destroy` — **tidak ada** `toggle-status` di admin: admin tidak
  menyembunyikan/menampilkan listing, ia hanya menghapus konten bermasalah.
  (Sembunyikan/tampilkan oleh admin sempat direncanakan tetapi tidak
  diimplementasikan.)

> Penyembunyian oleh admin dihilangkan demi kesederhanaan: perubahan status
> `active` ↔ `hidden` dibiarkan menjadi keputusan penjual di aplikasi, dan
> admin cukup menghapus konten yang melanggar.

### 9.7 Manajemen Permintaan

**Index (`/admin/requests`):**

- Datatables: Judul, Pembeli, Kategori, Status, Tanggal Expired.
- Filter: status (open, closed, expired).
- Aksi: Lihat detail.

**Show Permintaan (`/admin/requests/{request}`):**

- Detail permintaan + tabel offers yang masuk.
- Aksi **Perpanjang** (opsional): mendorong `expires_at` maju 24 jam.

Berguna saat permintaan kedaluwarsa akibat gangguan sistem, bukan karena
pembeli membiarkannya. Admin **tidak** dibatasi kuota 2 kali seperti pembeli
(`DATABASE.md` §4.5), tetapi setiap perpanjangan tetap tercatat:

```php
public function extend(CustomerRequest $request): RedirectResponse
{
    $this->authorize('manage-requests');

    if ($request->status === RequestStatus::Closed) {
        return back()->withErrors('Permintaan yang sudah ditutup tidak dapat diperpanjang.');
    }

    $request->update([
        'status'          => RequestStatus::Open,   // hidupkan lagi jika expired
        'expires_at'      => now()->addHours(24),
        'extended_at'     => now(),
        'extension_count' => $request->extension_count + 1,
    ]);

    return back()->with('success', 'Permintaan diperpanjang 24 jam.');
}
```

### 9.8 Manajemen Penawaran

**Index (`/admin/offers`):**

- Datatables: Permintaan, Toko, Harga, Status.
- Filter: status.
- Tidak ada aksi edit/hapus (read-only).

### 9.9 Manajemen Pesanan

**Index (`/admin/orders`):**

- Datatables: ID Pesanan, Pembeli, Toko, Total, Status, Tanggal.
- Filter: status.
- Aksi: Lihat detail.

**Show Pesanan (`/admin/orders/{order}`):**

- Timeline status, detail barang/jasa, informasi pembayaran, tombol “Lihat Dispute” jika ada.

### 9.10 Manajemen Dispute

**Index (`/admin/disputes`):**

- Datatables: Pesanan, Pelapor, Alasan, Status, Tanggal.
- Filter: status.
- Aksi: Lihat Detail, Selesaikan (resolve).

**Show Dispute (`/admin/disputes/{dispute}`):**

- Detail dispute + form resolution_note.
- Tombol "Selesaikan" → isi catatan, pilih keputusan akhir pesanan.

#### Menyelesaikan dispute juga membuka kunci pesanan

Saat dispute dibuat, `orders.status` menjadi `dispute` dan **semua transisi
status pesanan terkunci** (`API_DOCUMENTATION.md` §9.1). Kalau admin hanya
menandai dispute `resolved` tanpa menyentuh pesanan, pesanan itu **terkunci
selamanya** — tidak bisa diselesaikan maupun dibatalkan.

Karena itu form resolusi wajib memuat keputusan akhir pesanan:

| Keputusan admin | `orders.status` menjadi | Kapan dipakai |
| :-- | :-- | :-- |
| Lanjutkan transaksi | `selesai` | Laporan tidak terbukti; barang/jasa sudah diterima |
| Batalkan transaksi | `dibatalkan` | Laporan terbukti; transaksi dibatalkan |

```php
public function resolve(Request $request, Dispute $dispute): RedirectResponse
{
    $data = $request->validate([
        'resolution'      => ['required', Rule::in(['selesai', 'dibatalkan'])],
        'resolution_note' => ['required', 'string', 'max:2000'],
    ]);

    if ($dispute->status === DisputeStatus::Resolved) {
        return back()->with('error', 'Laporan sudah diselesaikan.');
    }

    DB::transaction(function () use ($dispute, $data, $request): void {
        $dispute->status             = DisputeStatus::Resolved;
        $dispute->resolution_note    = $data['resolution_note'];
        $dispute->resolved_at        = now();
        $dispute->assigned_to        = $request->user()->id;
        $dispute->first_responded_at ??= now();
        $dispute->save();

        $order = $dispute->order()->firstOrFail();
        // Transisi lewat OrderStateMachine (isAdmin = true) — bukan update()
        // mentah, agar kolom completed_at/cancelled_at ikut diatur.
        $this->states->transition($order, OrderStatus::from($data['resolution']),
            $data['resolution'] === 'dibatalkan' ? $data['resolution_note'] : null,
            $request->user()->id, true);
        $order->save();
    });

    return redirect()->route('admin.disputes.index')
        ->with('success', 'Laporan diselesaikan.');
}
```

> Tidak ada FormRequest `ResolveDisputeRequest` di repo ini — validasi
> inline. (Pemberitahuan ke kedua pihak ikut menjadi tanggung jawab alur
> notifikasi pesanan, bukan objek `DisputeResolved`.)

> ⚠️ Menyelesaikan dispute ke `selesai` **membuka jendela ulasan 7 hari**
> terhitung dari `completed_at` yang baru diisi. Ini disengaja: pihak yang
> dirugikan tetap berhak memberi penilaian.

### 9.11 Manajemen Ulasan

**Index (`/admin/reviews`):**

- Datatables: Pesanan, Penilai, Dinilai, Rating, Aksi.
- Aksi: Hapus (soft, hanya jika mengandung kata-kata kasar, dsb).

### 9.12 Pengaturan Sistem

**Halaman (`/admin/settings`):** hanya `super-admin` (lihat `API_DOCUMENTATION.md` §10.5).

Nilai yang di dokumen lain tampak sebagai angka tetap sebenarnya dikendalikan
dari sini: radius maksimum pencarian, masa berlaku permintaan & penawaran,
batas perpanjangan, jendela ulasan, dan SLA admin.

#### Tabel `settings`

Belum ada di `DATABASE.md` §4 karena bukan tabel domain — ini penyimpanan
key-value untuk konfigurasi runtime.

```php
Schema::create('settings', function (Blueprint $table) {
    $table->string('key', 100)->primary();
    $table->text('value')->nullable();
    $table->string('type', 20)->default('string'); // string|integer|boolean|json
    $table->string('group', 50)->default('general');
    $table->string('label');                        // teks yang tampil di form admin
    $table->timestamps();
});
```

| `key` | Tipe | Default | Dipakai di |
| :-- | :-- | :-- | :-- |
| `max_search_radius_km` | integer | `25` | Pencarian listing (PRD §5.1) |
| `default_request_radius_km` | integer | `15` | `customer_requests.radius_km` |
| `request_expiry_hours` | integer | `24` | `expires_at` permintaan |
| `offer_expiry_hours` | integer | `48` | `expires_at` penawaran |
| `max_request_extensions` | integer | `2` | Batas perpanjangan pembeli |
| `review_window_days` | integer | `7` | Jendela ulasan (PRD §5.4.1) |
| `ktp_review_sla_hours` | integer | `24` | SLA verifikasi |
| `dispute_sla_hours` | integer | `24` | SLA dispute (PRD §5.5) |

#### Service dengan cache

Pengaturan dibaca hampir di setiap request, jadi wajib di-cache — dan cache
**harus** dibersihkan saat nilainya berubah:

```php
class SettingService
{
    private const CACHE_KEY = 'settings:all';

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () =>
            Setting::all()->mapWithKeys(fn ($s) => [
                $s->key => match ($s->type) {
                    'integer' => (int) $s->value,
                    'boolean' => filter_var($s->value, FILTER_VALIDATE_BOOL),
                    'json'    => json_decode($s->value, true),
                    default   => $s->value,
                },
            ])->all()
        );
    }

    public function set(array $values): void
    {
        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                Setting::where('key', $key)->update(['value' => $value]);
            }
        });

        Cache::forget(self::CACHE_KEY);   // WAJIB, kalau tidak nilai lama bertahan
    }
}
```

> ⚠️ Perubahan pengaturan berlaku untuk data **baru** saja. Menurunkan
> `request_expiry_hours` tidak memperpendek permintaan yang sudah berjalan,
> karena `expires_at` sudah terhitung saat permintaan dibuat.

---

## 10. CONTROLLERS & ACTIONS (ADMIN)

Struktur lengkap `CategoryController` — **Eloquent polos**, tanpa `CategoryService`
dan tanpa `CategoriesDataTable` (keduanya tidak ada di repo ini). Otorisasi
datang dari middleware `permission:manage-categories` di route (§7), dan
validasi dilakukan inline di dalam controller:

```php
class CategoryController extends Controller
{
    public function index(): View
    {
        // Bukan Datatables — daftar induk + subkategori dimuat penuh (§9.2).
        return view('admin.categories.index', [
            'categories' => Category::with('children')
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new Category(),
            'parents'  => Category::whereNull('parent_id')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori dibuat.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            // Kategori tidak boleh menjadi induk dirinya sendiri.
            'parents'  => Category::whereNull('parent_id')
                ->whereKeyNot($category->id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // Penghapusan punya banyak prasyarat (anak, permintaan, JSON store) —
        // dicek di controller, bukan service.
        if ($category->children()->exists()) {
            return back()->with('error', 'Pindahkan atau hapus subkategori terlebih dahulu.');
        }

        if ($category->customerRequests()->exists()) {
            return back()->with('error', 'Kategori masih dipakai permintaan.');
        }

        if (Store::whereRaw('JSON_CONTAINS(category_ids, ?)', [(string) $category->id])->exists()) {
            return back()->with('error', 'Kategori masih dipakai toko.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori dihapus.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name'       => ['required', 'string', 'max:50'],
            'slug'       => ['required', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/',
                             Rule::unique('categories', 'slug')->ignore($category?->id)],
            'parent_id'  => ['nullable', 'integer', 'exists:categories,id',
                             Rule::notIn([$category?->id])],
            'icon'       => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
```

#### Pola Datatables admin — facade `DataTables::eloquent()`, bukan turunan `DataTable`

Tabel admin yang besar (pengguna, toko, permintaan, dsb.) memakai kelas di
`app/DataTables/` yang **membungkus facade**, karena kelas turunan
`Yajra\DataTables\Services\DataTable` tidak ada di paket inti
`yajra/laravel-datatables-oracle` (ia berasal dari paket terpisah
`laravel-datatables-buttons`, yang tidak dipasang — lihat
`app/DataTables/README.md`):

```php
// app/DataTables/UsersDataTable.php
class UsersDataTable
{
    public function json(Request $request): JsonResponse
    {
        $query = User::query()->select([...]);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return DataTables::eloquent($query)
            ->editColumn('name', fn (User $u) => view('admin.users._nama', ['user' => $u])->render())
            ->editColumn('status', fn (User $u) => view('admin.users._status', ['user' => $u])->render())
            ->rawColumns(['name', 'status'])
            ->toJson();
    }
}
```

Controller admin untuk tabel memanggil kelas ini dari endpoint `data`
(`admin/users/data`, `admin/requests/data`, dst. — lihat §7).

#### Aturan FormRequest

Aksi tulis admin **tidak semuanya** memakai FormRequest — di repo ini
FormRequest khusus hanya ada untuk empat hal, selebihnya memvalidasi inline
dengan `$request->validate()`:

| Controller | FormRequest / cara validasi |
| :-- | :-- |
| `LoginController@store` | `LoginRequest` |
| `ProfileController@update` | `UpdateProfileRequest` |
| `PasswordController@update` | `UpdatePasswordRequest` |
| `VerificationController@rejectUser/rejectStore` | `RejectVerificationRequest` |
| `CategoryController`, `StoreController`, `UserController`, `DisputeController@resolve`, `SettingController@update`, dsb. | `Request` biasa + `$request->validate()` inline |

Tidak ada `CategoryRequest`, `UserRequest`, `BlockUserRequest`, `StoreRequest`,
`ResolveDisputeRequest`, maupun `SettingRequest` di repo ini.

---

## 11. FORM REQUESTS & VALIDASI (ADMIN)

### FormRequest khusus (empat saja)

Semua FormRequest admin berada di `app/Http/Requests/Admin/`:

| Berkas | Dipakai di | Aturan inti |
| :-- | :-- | :-- |
| `LoginRequest` | `LoginController@store` | email wajib + format, password wajib |
| `UpdateProfileRequest` | `ProfileController@update` | nama, avatar (JPEG/PNG ≤ 2 MB), alamat, titik koordinat berpasangan |
| `UpdatePasswordRequest` | `PasswordController@update` | password saat ini harus cocok, password baru tidak lemah (`NotAWeakPassword`), konfirmasi sama |
| `RejectVerificationRequest` | `VerificationController@rejectUser` / `@rejectStore` | `reason` wajib — tanpa alasan pengguna mengirim ulang berkas yang sama |

### Validasi inline untuk aksi admin lain

`CategoryController`, `StoreController`, `UserController`, `DisputeController@resolve`,
`SettingController@update`, dan aksi tulis admin lain **memvalidasi inline** lewat
`$request->validate()` — tidak ada FormRequest terpisah untuk mereka. Contoh aturan
kategori (§10):

```php
'name'       => ['required', 'string', 'max:50'],
'slug'       => ['required', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/',
                 Rule::unique('categories', 'slug')->ignore($category?->id)],
'parent_id'  => ['nullable', 'integer', 'exists:categories,id',
                 Rule::notIn([$category?->id])],
'icon'       => ['nullable', 'string', 'max:50'],
'sort_order' => ['nullable', 'integer', 'min:0'],
```

> `Rule::unique(...)->ignore()` menggantikan penggabungan string
> `'unique:categories,slug,'.$id`. Bentuk string akan rusak jika `$id` bernilai
> `null` (menghasilkan `unique:categories,slug,`) — yang justru terjadi pada
> aksi *create*.

### Rule kustom: hanya `NotAWeakPassword`

`app/Rules/` hanya memuat satu kelas:

```php
// app/Rules/NotAWeakPassword.php
class NotAWeakPassword implements ValidationRule
{
    // Menolak sandi bawaan seeder & variasi kata umum Indonesia.
    // Sengaja TIDAK memakai Password::uncompromised(): aturan bawaan itu
    // memanggil api.pwnedpasswords.com dan GAGAL-TERBUKA (fail open) saat
    // jaringan diblokir — pemeriksaan yang diam-diam mati lebih buruk
    // daripada tidak ada.
}
```

Tidak ada `ValidFontAwesomeIcon` maupun `NotADescendant` di repo ini. Ikon
kategori divalidasi sebagai string bebas (`nullable|string|max:50`), dan
kategori tidak memakai daftar putih ikon FontAwesome — template Modernize
memakai ikon Tabler (`ti ti-*`), bukan FontAwesome.

---

## 12. MODELS & RELATIONSHIPS (Tambah Spatie)

User model sudah mencakup `HasRoles`.  
Tambahkan accessor: `getRoleNamesAttribute()` atau langsung gunakan `$user->roles`.

---

## 13. OBSERVERS & EVENTS

Pemetaan event → listener didaftarkan di **`AppServiceProvider::registerEventListeners()`**
— tidak ada `EventServiceProvider`. Tiga event yang benar-benar ada:

| Event                    | Listener                          | Efek                                                        |
| :----------------------- | :-------------------------------- | :----------------------------------------------------------- |
| `CustomerRequestCreated` | `DispatchRequestBroadcast`        | Dispatch `BroadcastRequestJob` ke antrian                   |
| `OfferAccepted`          | `SendOfferAcceptedNotification`   | Notifikasi penyedia pemenang + tolak offer lain             |
| `OrderStatusChanged`     | `SendOrderStatusNotification`     | Notifikasi pihak terkait sesuai status baru                 |

Tidak ada event `ReviewSubmitted`: rating **tidak** memakai event/job — ia
ditangani `ReviewObserver` secara sinkron (lihat catatan di
`AppServiceProvider::registerEventListeners()`: "jangan tambahkan listener
rating di sini").

**Observers** dipakai untuk side-effect yang selalu terjadi apa pun jalur
masuknya (API, admin panel, atau seeder). Hanya ada **dua**:

| Observer           | Hook                 | Efek                                                          |
| :----------------- | :------------------- | :-------------------------------------------------------------- |
| `OrderObserver`    | `creating`           | Buat `order_number` (`SKT-YYYYMMDD-NNNN`) lewat `cache->add` + `increment` |
| `OrderObserver`    | `updating`           | Jaring pengaman transisi; isi `completed_at` / `cancelled_at`  |
| `ReviewObserver`   | `created`, `deleted` | Hitung ulang rating toko (`stores.rating_avg`) & pembeli (`users.rating_avg`) — **sinkron** via `DB::table` |

Tidak ada `StoreObserver`, `ListingObserver`, maupun `CustomerRequestObserver`.

**`OrderObserver` — nomor pesanan & penjaga transisi:**

```php
class OrderObserver
{
    public function __construct(
        private readonly OrderStateMachine $states,
        private readonly CacheRepository $cache,
    ) {}

    public function creating(Order $order): void
    {
        $order->order_number ??= $this->generateNumber();
    }

    public function updating(Order $order): void
    {
        if (! $order->isDirty('status')) {
            return;
        }

        $from = $order->getOriginal('status');

        // Jaring pengaman terakhir: transisi tidak sah ditolak walau
        // datangnya dari seeder, tinker, atau admin panel. Observer tidak
        // punya konteks user, jadi transisi yang SAH untuk penjual ATAU
        // pembeli sama-sama diloloskan (keputusan final di Policy/controller).
        $allowedForSeller = $this->states->allowedFrom($from, true);
        $allowedForBuyer  = $this->states->allowedFrom($from, false);
        $allAllowed       = array_merge($allowedForSeller, $allowedForBuyer);

        if (! in_array($order->status, $allAllowed, true) && $from !== $order->status) {
            throw new InvalidOrderTransitionException(
                sprintf('Transisi dari "%s" ke "%s" tidak diizinkan.', $from->value, $order->status->value)
            );
        }

        match ($order->status) {
            OrderStatus::Selesai    => $order->completed_at ??= now(),
            OrderStatus::Dibatalkan => $order->cancelled_at ??= now(),
            default                 => null,
        };
    }

    private function generateNumber(): string
    {
        $date = now()->format('Ymd');
        $key  = "order_seq:$date";

        // Kontrak cache (bukan facade Redis) supaya bisa diuji tanpa server.
        // add() hanya berhasil bila kunci belum ada — penyetelan awal aman
        // dari balapan antar-proses; TTL 48 jam cukup melewati pergantian hari.
        $this->cache->add($key, 0, now()->addHours(48));
        $seq = $this->cache->increment($key);

        return sprintf('SKT-%s-%04d', $date, $seq);
    }
}
```

> `OrderStateMachine::assertCanTransition()` menerima tiga argumen:
> `(OrderStatus $from, OrderStatus $to, bool $isSeller)`. Di `Policy` dan
> controller, `$isSeller` ditentukan dari pemanggil; di observer nilainya
> "longgar" — sah untuk salah satu peran pun diloloskan.

**`ReviewObserver` — perhatikan filter arah & perhitungan SINKRON:**

```php
private function recalculate(Review $review): void
{
    // Rating TOKO — HANYA dari arah buyer_to_store; tanpa filter ini penjual
    // bisa mengerek ratingnya sendiri lewat penilaian ke pembeli.
    if ($review->direction === ReviewDirection::BuyerToStore && $review->store_id !== null) {
        $stats = DB::table('reviews')
            ->selectRaw('COUNT(*) AS total, COALESCE(AVG(rating), 0) AS average')
            ->where('store_id', $review->store_id)
            ->where('direction', ReviewDirection::BuyerToStore->value)
            ->first();

        Store::whereKey($review->store_id)->update([
            'rating_avg'    => round((float) $stats->average, 2),
            'total_reviews' => (int) $stats->total,
        ]);
    }

    // Rating PEMBELI — hanya dari arah store_to_buyer, ditujukan ke reviewee.
    if ($review->direction === ReviewDirection::StoreToBuyer) {
        $stats = DB::table('reviews')
            ->selectRaw('COUNT(*) AS total, COALESCE(AVG(rating), 0) AS average')
            ->where('reviewee_id', $review->reviewee_id)
            ->where('direction', ReviewDirection::StoreToBuyer->value)
            ->first();

        User::whereKey($review->reviewee_id)->update([
            'rating_avg'    => round((float) $stats->average, 2),
            'total_reviews' => (int) $stats->total,
        ]);
    }
}
```

Dihitung dari COUNT/AVG **penuh**, bukan inkremental — satu ulasan yang
terhapus atau tertulis dua kali tidak membuat selisih permanen. Tidak ada
`RecalculateStoreRatingJob`.

**Registrasi** — observer didaftarkan di `AppServiceProvider::boot()` lewat
`registerObservers()`, bukan atribut `#[ObservedBy]`:

```php
// AppServiceProvider::registerObservers()
Order::observe(OrderObserver::class);
Review::observe(ReviewObserver::class);
```

> Observer cocok untuk konsistensi data, bukan untuk pekerjaan berat.
> Pengiriman notifikasi tetap lewat event → job antrian.
>
> ⚠️ Observer **tidak terpanggil** pada operasi massal seperti
> `Order::where(...)->update([...])`, karena query builder melewati model.
> Untuk pembaruan massal (mis. scheduler menutup permintaan kedaluwarsa), efek
> sampingnya harus ditulis eksplisit.

---

## 14. JOBS & QUEUE

Semua job berjalan di Redis. Jalankan worker dengan:

```bash
php artisan queue:work redis --queue=high,default --tries=3
```

| Job                          | Antrian   | Fungsi                                                  |
| :--------------------------- | :-------- | :-------------------------------------------------------- |
| `BroadcastRequestJob`        | `high`    | Cari penyedia dalam radius, kirim notifikasi ke mereka  |
| `SendOtpJob`                 | `high`    | Kirim OTP via gateway `WhatsAppGateway` di background   |

Hanya dua job — tidak ada `SendPushNotificationJob` (FCM server-side belum
diimplementasikan, lihat §15.1), `SendWhatsAppOtpJob`, maupun
`RecalculateStoreRatingJob` (rating dihitung `ReviewObserver` sinkron, §13).

### 14.1 `BroadcastRequestJob` — implementasi lengkap

Inti mesin kedua Seekitar: menyebar permintaan pembeli ke penyedia yang relevan.

```php
class BroadcastRequestJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(public readonly string $requestId) {}

    public function handle(BroadcastService $broadcast, NotificationSender $notifier): void
    {
        $request = CustomerRequest::withCoordinates()->find($this->requestId);

        // Permintaan bisa sudah ditutup/kedaluwarsa sebelum job dieksekusi.
        if ($request === null || ! $request->isOpen()) {
            return;
        }

        $stores = $broadcast->matchingStores($request);

        if ($stores->isEmpty()) {
            return;
        }

        // Notifikasi dikirim lewat kontrak NotificationSender (saat ini
        // terikat ke LogNotificationSender — FCM belum diimplementasikan).
        $notifier->notifyStoresOfRequest($stores, $request);
    }
}
```

**`BroadcastService::matchingStores()`** — kriteria pencocokan dari PRD §5.2.2.
Pencocokan bersifat **dua arah**, dan karena lokasi toko adalah kolom DECIMAL
(bukan POINT), kotak pembatasnya disaring `whereBetween` (scope `withinBox`),
lalu jarak akuratnya dihitung **di PHP** lewat `Jarak::haversineKm`:

```php
public function matchingStores(CustomerRequest $request): Collection
{
    [$lat, $lng] = $this->coordinatesOf($request);   // scope withCoordinates()

    return Store::query()
        ->where('is_active', true)
        ->where('status', StoreStatus::Verified)
        // Toko tidak boleh menawar pada permintaannya sendiri.
        ->where('user_id', '!=', $request->user_id)
        // Kategori toko memuat kategori permintaan (JSON, bukan FK).
        ->whereJsonContains('category_ids', $request->category_id)
        // Kotak kasar SQL-murni untuk KEDUA arah — saring radius terbesar.
        ->withinBox($lat, $lng, max((float) $request->radius_km, 50.0))
        ->get()
        ->map(fn (Store $s) => $s->setAttribute('distance_km',
            Jarak::haversineKm($lat, $lng, (float) $s->latitude, (float) $s->longitude)))
        // ARAH 1 — toko dalam radius pembeli.
        ->filter(fn (Store $s) => $s->distance_km <= (float) $request->radius_km)
        // ARAH 2 — pembeli dalam radius layanan toko.
        ->filter(fn (Store $s) => $s->distance_km <= (float) $s->service_radius_km)
        // Prioritas: rating tertinggi, lalu terdekat.
        ->sortBy([['rating_avg', 'desc'], ['distance_km', 'asc']])
        ->values()
        ->take(BroadcastService::MAX_RECIPIENTS);   // 50
}
```

> ⚠️ Pencocokan sengaja **dua arah**. Toko kelontong beradius 5 km tidak akan
> menerima permintaan dari pembeli 12 km jauhnya, meski pembeli menyetel radius
> 15 km. Tanpa syarat kedua, penyedia dibanjiri permintaan di luar jangkauan.

**Scheduler** (`routes/console.php`) — hanya empat perintah:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('offers:purge-expired')->hourly();   // tolak penawaran kadaluarsa
Schedule::command('requests:purge-expired')->hourly(); // tutup permintaan kadaluarsa
Schedule::command('orders:cleanup-pending')->daily();  // bersihkan pesanan menggantung
Schedule::command('privacy:retention')->daily()->withoutOverlapping();  // hapus data pribadi (UU PDP)
```

`privacy:retention` memakai `->withoutOverlapping()` karena menulis ke banyak
tabel — dua run bersamaan (mis. retensi data dan dedupe) akan saling mendahului.

Perintah penutupan kadaluarsa memakai indeks `cr_status_expires_idx` seperti
dijelaskan di `DATABASE.md` §11. Tidak ada `requests:close-expired` maupun
`uploads:prune`/`stores:recalculate-ratings`.

---

## 15. NOTIFIKASI (PUSH & WHATSAPP)

### 15.1 Push Notification (FCM) — belum diimplementasikan di sisi server

> ⚠️ **Status aktual: rencana / belum diimplementasikan.** Tidak ada
> `kreait/laravel-firebase` di `composer.json`, tidak ada channel FCM, dan
> tidak ada job pengiriman push. Yang ada saat ini hanyalah **registrasi
> token dari aplikasi mobile**: `POST /auth/fcm-token` dan
> `DELETE /auth/fcm-token` (`DeviceController`) menyimpan/menghapus token di
> tabel `user_devices`. Token itu **belum** dipakai server untuk mengirim
> notifikasi apa pun.

- **Kontrak sudah siap** — `App\Services\Contracts\NotificationSender` dengan
  implementasi tunggal `LogNotificationSender`, di-bind di
  `AppServiceProvider::register()` (komentarnya eksplisit: *"Implementasi FCM
  sungguhan belum ada"*). `BroadcastRequestJob` dan listener notifikasi
  mengirim lewat kontrak ini, sehingga saat FCM nanti diimplementasikan cukup
  mengganti binding-nya — pemanggil tidak berubah.
- **Rencana saat diimplementasikan:** pakai FCM **HTTP v1** berbasis service
  account (bukan legacy API); satu pengguna bisa punya banyak perangkat
  (`user_devices`), jadi kirim multicast (batas **500 token per panggilan**),
  dan token yang ditolak (`invalid`/`unknown`) **wajib dihapus** agar antrian
  tidak terus mengirim ke perangkat yang aplikasinya sudah dihapus.
- **Yang bisa diukur** hanyalah "diterima server FCM", bukan "dibaca
  pengguna" — keterbacaan hanya bisa diukur dari sisi aplikasi (event
  `notification_opened`). Log pengiriman cukup log terstruktur, jangan
  disimpan di tabel database.

> Tidak ada `SendPushNotificationJob` maupun `RequestBroadcastNotification` di
> repo ini — keduanya bagian dari rencana FCM di atas. Saat diimplementasikan,
> pertimbangkan `sendMulticast` (bukan satu job per token) dan jangan mengulang
> seluruh job bila sebagian token gagal: token tidak valid adalah kondisi
> permanen, bukan galat sementara.

### 15.2 WhatsApp OTP

Tidak ada paket resmi Laravel untuk provider lokal, jadi bungkus HTTP client
sendiri di balik satu interface — supaya provider bisa diganti tanpa menyentuh
kode pemanggil.

```php
// app/Services/Contracts/WhatsAppGateway.php
interface WhatsAppGateway
{
    public function sendOtp(string $phone, string $code): void;
}
```

| Driver (`WHATSAPP_DRIVER`) | Cocok untuk | Catatan |
| :-- | :-- | :-- |
| **Baileys** (`BaileysGateway`) | Gratis, kendali penuh | WhatsApp Web tak-resmi via Node sidecar; QR di-scan dari panel admin. ⚠️ melanggar ToS WhatsApp — pakai nomor gateway khusus |
| **Kirim WA** (`KirimWaGateway`) | Pasar Indonesia, biaya lebih murah | REST sederhana, cukup `Http::post()`; wajib terisi `services.kirimwa` di produksi |
| **Email** (`EmailOtpGateway`) | Development | OTP dikirim lewat email, bukan WhatsApp |
| **Log** (`LogWhatsAppGateway`) | Development | OTP ditulis ke `storage/logs` |

> Twilio **tidak dipasang** (`twilio/sdk` tidak ada di `composer.json`).
> Baris "produksi lintas negara" itu hanya rencana; driver yang tersedia saat
> ini adalah Baileys / Kirim WA / email / log.
>
> **Nomor telepon disensor di log** — semua driver mencatat nomor dalam bentuk
> tersamarkan (mis. `62812••••890`) dan tidak pernah menulis kode OTP ke log.
> Ini kewajiban UU PDP, bukan sekadar praktik baik.

**Baileys (gateway WhatsApp Web lokal).** Service Node di
`seekitar-server/whatsapp-gateway/` (`npm start` → `http://127.0.0.1:3001`)
mengekspos `GET /api/status`, `GET /api/qr`, `POST /api/logout`, dan
`POST /api/send`. `BaileysGateway` memanggilnya dari Laravel; panel admin
memakai endpoint `admin.whatsapp.*` (permission `manage-whatsapp`) untuk
**scan QR**, menampilkan **status online/offline**, mencabut sesi, dan uji
kirim. Setup lengkap di `seekitar-server/whatsapp-gateway/README.md`.

**Kirim OTP berjalan DI BACKGROUND (queue), bukan di dalam request:**

1. `AuthController::requestOtp` hanya `SendOtpJob::dispatch(...)` lalu
   langsung membalas — login terasa instan, tidak menunggu WhatsApp.
2. Worker (`php artisan queue:work redis --queue=high,default`) yang
   meneruskan via `BaileysGateway::sendOtp`; gagal → retry/backoff →
   `failed_jobs`.
3. **Jalur cepat socket (opsional):** set `BAILEYS_REDIS_URL` (Laravel)
   dan `REDIS_URL` (gateway) → `sendOtp` mem-publish ke channel Redis
   `seekitar:wa:send` yang disubscribe gateway (koneksi socket persisten,
   tanpa HTTP handshake per pesan). Kosongkan untuk HTTP biasa.
4. **Daemon background:** gateway dijalankan dengan PM2
   (`ecosystem.config.js` / `start-background.bat` di Windows) atau systemd
   (`seekitar-wa.service`) — tidak perlu terminal terbuka.
5. **QR muncul cepat & anti-macet:** versi Baileys dipin (tanpa fetch GitHub),
   QR di-cache (bukan dibuat per polling), sesi korup di-reset otomatis
   setelah beberapa kali putus, dan panel admin punya tombol
   **Reset & QR Baru** (`POST /admin/whatsapp/reset` → hapus sesi → QR fresh).

```env
WHATSAPP_DRIVER=baileys
BAILEYS_URL=http://127.0.0.1:3001
BAILEYS_TOKEN=<token service>          # wajib di produksi
# BAILEYS_REDIS_URL=redis://127.0.0.1:6379   # jalur cepat socket (opsional)
```

```php
class KirimWaGateway implements WhatsAppGateway
{
    public function sendOtp(string $phone, string $code): void
    {
        $response = Http::withToken(config('services.kirimwa.token'))
            ->timeout(10)
            ->retry(2, 500)
            ->post(config('services.kirimwa.url').'/send', [
                'phone'   => $phone,
                'message' => "Kode OTP Seekitar Anda: {$code}. Berlaku 5 menit. "
                           . "Jangan bagikan kode ini kepada siapa pun.",
            ]);

        if ($response->failed()) {
            throw new OtpDeliveryException($response->body());
        }
    }
}
```

Binding di `AppServiceProvider::register()` — driver dipilih lewat
`config('whatsapp.driver')`, bukan `app()->isProduction()` secara buta:

```php
// AppServiceProvider::register()
$this->app->bind(WhatsAppGateway::class, function () {
    $driver = strtolower((string) config('whatsapp.driver', 'log'));

    if ($this->app->isProduction()) {
        // Gagal cepat: tanpa konfigurasi yang benar, tidak ada yang bisa masuk.
        return match ($driver) {
            'baileys' => $this->requireBaileysConfig(new BaileysGateway()),
            'kirimwa' => $this->requireKirimwaConfig(new KirimWaGateway()),
            default   => throw new RuntimeException(
                "WHATSAPP_DRIVER='{$driver}' tidak valid untuk produksi; pakai 'baileys' atau 'kirimwa'."
            ),
        };
    }

    return match ($driver) {
        'baileys' => new BaileysGateway(),
        'email'   => new EmailOtpGateway(),
        'kirimwa' => new KirimWaGateway(),
        default   => new LogWhatsAppGateway(),
    };
});

// Pengirim notifikasi — FCM sungguhan belum ada, jadi cukup LogNotificationSender.
$this->app->bind(NotificationSender::class, LogNotificationSender::class);
```

> 🔒 OTP disimpan di Redis dengan TTL 5 menit dan **di-hash**, bukan plaintext:
> `Redis::setex("otp:$phone", 300, Hash::make($code))`. Batasi juga percobaan
> verifikasi (5×) agar tidak bisa ditebak paksa.

---

## 16. GEOSPASIAL & QUERY RADIUS

Semua query radius memakai `GeolocationService` (§4) yang dipanggil lewat
**Eloquent scope**, sehingga raw SQL tidak tersebar ke banyak controller.

```php
// app/Models/Concerns/HasLocation.php
trait HasLocation
{
    /**
     * WKT SRID 4326 dibaca MySQL sebagai (latitude longitude) sesuai EPSG.
     * Seluruh bujur Indonesia (95°-141° BT) di luar rentang lintang ±90,
     * jadi tanpa opsi ini setiap titik ditolak: ERROR 3617.
     */
    private const AXIS = 'axis-order=long-lat';

    /**
     * Batasi hasil pada radius (km) dari sebuah titik.
     *
     * DUA TAHAP dan urutannya penting:
     *   1. MBRContains  -> memakai SPATIAL INDEX, membuang mayoritas baris
     *   2. ST_Distance_Sphere -> jarak akurat, hanya pada kandidat tersisa
     *
     * ST_Distance_Sphere sendirian TIDAK memakai indeks: ia dihitung untuk
     * setiap baris tabel. Lihat DATABASE.md §11.
     */
    public function scopeNearby(Builder $q, float $lat, float $lng, float $radiusKm): Builder
    {
        $meter  = $radiusKm * 1000;
        $latDeg = $meter / 111320;
        $lngDeg = $meter / (111320 * cos(deg2rad($lat)));

        $bbox = sprintf(
            'POLYGON((%1$F %2$F, %1$F %4$F, %3$F %4$F, %3$F %2$F, %1$F %2$F))',
            $lng - $lngDeg, $lat - $latDeg,
            $lng + $lngDeg, $lat + $latDeg
        );

        return $q
            ->whereRaw(
                'MBRContains(ST_GeomFromText(?, 4326, ?), location)',
                [$bbox, self::AXIS]
            )
            ->whereRaw(
                'ST_Distance_Sphere(location, ST_GeomFromText(?, 4326, ?)) <= ?',
                ["POINT($lng $lat)", self::AXIS, $meter]
            );
    }

    /** Tambahkan kolom distance_km agar bisa diurutkan & ditampilkan. */
    public function scopeWithDistance(Builder $q, float $lat, float $lng): Builder
    {
        return $q->select('*')->selectRaw(
            'ST_Distance_Sphere(location, ST_GeomFromText(?, 4326, ?)) / 1000 AS distance_km',
            ["POINT($lng $lat)", self::AXIS]
        );
    }

    public function scopeOrderByDistance(Builder $q, string $dir = 'asc'): Builder
    {
        return $q->orderBy('distance_km', $dir);
    }
}
```

Dipakai di model yang punya kolom `location` POINT: `CustomerRequest`,
`User`, dan `Order` (`shipping_location`). **`Store` TIDAK memakai trait ini**
— lokasi toko adalah kolom `latitude`/`longitude` DECIMAL, jadi pencarian
radius toko memakai `scopeWithinBox` (`whereBetween`) + `Jarak::haversineKm`
di PHP (lihat `DATABASE.md` §11):

```php
class CustomerRequest extends Model
{
    use HasLocation, ...;
}

// Pemakaian di BroadcastService (pencocokan penyedia untuk permintaan):
$permintaan = CustomerRequest::query()
    ->withDistance($lat, $lng)
    ->nearby($lat, $lng, $radiusKm)
    ->orderByDistance()
    ->get();
```

> ⚠️ **Urutan `POINT(longitude latitude)`** — terbalik dari kebiasaan menulis
> "lat, lng". Menukarnya tidak menimbulkan error, hanya hasil yang salah diam-diam.
> Karena itu semua penulisan POINT dipusatkan di trait ini.
>
> ⚠️ `withDistance()` harus dipanggil **sebelum** `orderByDistance()`, karena
> alias `distance_km` belum ada sebelum kolomnya dipilih.
>
> ⚠️ **Jangan menghapus tahap `MBRContains` dari `scopeNearby()`** dengan alasan
> "menyederhanakan query". Tanpa tahap itu, indeks spasial tidak terpakai sama
> sekali dan pencarian melambat sebanding dengan jumlah baris. Verifikasi
> dengan `EXPLAIN`: kolom `key` harus berisi `cr_location_spatial`.

**Memastikan indeks terpakai:**

```php
// Jalankan sekali di tinker/test setelah data uji dimuat.
DB::enableQueryLog();
CustomerRequest::nearby(-7.2575, 112.7521, 5)->get();
$sql = DB::getQueryLog()[0]['query'];

dd(DB::select("EXPLAIN $sql", DB::getQueryLog()[0]['bindings']));
// key => 'cr_location_spatial'  ✅
// key => null                    ❌ indeks tidak terpakai
```

**Menyimpan koordinat** (kolom POINT tidak bisa diisi string biasa — semua
penulisan dipusatkan di `HasLocation::setLocation()`):

```php
$permintaan->setLocation($lat, $lng);   // memakai ST_GeomFromText + axis-order
```

### 16.1 Reverse Geocoding (Koordinat → Alamat)

> ⚠️ **Tidak ada `GeocodingService` maupun `ResolveStoreAddressJob` di repo
> ini.** Server tidak memanggil Google Maps Geocoding sama sekali; `config/
> services.google_maps` tidak ada. Alamat teks (`stores.address`,
> `users.address`) diisi **langsung oleh klien**: aplikasi mobile yang
> melakukan reverse geocoding (sisi klien, Mobile Guide §12.1) dan mengirim
> hasilnya sebagai teks saat menyimpan toko/profil. Server hanya menyimpan —
> koordinat (POINT/DECIMAL) dipakai untuk query, alamat teks untuk tampilan.

- Keduanya disimpan terpisah, bukan dihitung ulang tiap kali — karena itu
  alamat yang dikirim klien **tidak dipakai untuk apa pun selain tampilan**;
  kebenaran spasial tetap di koordinat.
- Kalau kelak geocoding server-side dibutuhkan (mis. validasi `regency_code`
  untuk geofencing kabupaten, `DATABASE.md` §4.2), wajib: (1) kegagalan
  layanan tidak menggagalkan pembuatan toko — koordinat cukup untuk seluruh
  fungsi pencarian; (2) cache hasilnya (pembulatan 4 desimal ≈ 11 meter,
  dua pin berdekatan berbagi hasil) karena layanan geocoding ditagih per
  panggilan.
- `GeolocationService` (ada di repo) tetap menjadi pusat query radius —
  `withinRadius()`, `selectDistance()`, `distanceKm()`, `boundingBox()`, dan
  `isWithinRadius()` — dan tidak berurusan dengan geocoding.

---

## 17. API RESPONSE & PAGINASI (WEB & API)

- **Web:** Datatables menangani server-side processing dan paginasi otomatis.
- **API:** Paginasi standar (`page`, `per_page`; lihat `API_DOCUMENTATION.md` §12.2).

### 17.1 Trait `ApiResponse`

Format response di `API_DOCUMENTATION.md` §1 hanya konsisten jika dibungkus
satu helper — jangan menyusun array `['success' => ...]` manual di controller.

```php
trait ApiResponse
{
    protected function ok(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json(array_filter([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ], fn ($v) => $v !== null), $status);
    }

    protected function created(mixed $data, string $message = 'Data berhasil dibuat'): JsonResponse
    {
        return $this->ok($data, $message, 201);
    }

    protected function fail(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    /** Paginator -> struktur data + meta yang seragam. */
    protected function paginated(LengthAwarePaginator $p, ?string $resource = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $resource ? $resource::collection($p->items()) : $p->items(),
            'meta'    => [
                'current_page' => $p->currentPage(),
                'per_page'     => $p->perPage(),
                'total'        => $p->total(),
                'last_page'    => $p->lastPage(),
            ],
        ]);
    }
}
```

### 17.2 Response Web (Admin)

Admin panel memakai **redirect + flash message**, bukan JSON — tidak ada trait
`WebResponse` di repo ini. Controller menulisnya langsung secara konsisten:
`redirect()->route('admin.*')->with('success', …)` untuk sukses dan
`back()->with('error', …)` untuk galat, persis seperti contoh di §10.

Dua trait yang benar-benar ada di `app/Http/Concerns/`:

| Trait | Dipakai di | Fungsi |
| :-- | :-- | :-- |
| `ApiResponse` | Controller API | Amplop JSON seragam (`ok`, `created`, `fail`, `paginated`, `perPage`) — §17.1 |
| `HasEscapePlan` | Controller API transaksional | `safely()` membungkus callback dengan `EscapePlanService::transactional()`; `degradedResponse()` menanggapi 503 saat fitur sedang dinonaktifkan |

Layout admin menampilkan flash message di satu tempat:

```blade
@if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if (session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif
```

---

## 18. ERROR HANDLING & LOGGING (Tetap)

Khusus web, kita bisa menggunakan `abort(403)` jika tidak punya permission, dan tampilkan halaman error custom.

---

## 18A. KEAMANAN APLIKASI

Bab ini mengimplementasikan janji keamanan di `PRD.md` §11. Setiap poin di PRD
harus punya padanan kode di sini — kalau tidak, janji itu tidak berlaku.

### 18A.1 Route Model Binding & UUID

Semua ID publik memakai UUID (`DATABASE.md` §8). Tanpa pembatasan format,
setiap ID yang salah bentuk tetap menembus ke query database.

```php
// bootstrap/app.php — di dalam withRouting(then: ...)
Route::pattern('id', '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}');
Route::pattern('store', '[0-9a-fA-F-]{36}');
Route::pattern('listing', '[0-9a-fA-F-]{36}');
Route::pattern('order', '[0-9a-fA-F-]{36}');
```

Manfaatnya bukan sekadar kerapian:

| Tanpa pattern | Dengan pattern |
| :-- | :-- |
| `/orders/1 OR 1=1` masuk ke query | Ditolak router → **404** |
| `/orders/../../etc/passwd` diproses | Ditolak router |
| Pemindai otomatis membebani DB | Ditolak sebelum menyentuh DB |

> ⚠️ **Pattern bukan pengganti otorisasi.** UUID memang sulit ditebak, tetapi
> ID yang bocor lewat tangkapan layar atau riwayat browser tetap sah. Policy
> (§6.3) tetap wajib memeriksa kepemilikan pada setiap akses.

**Kunci `orders` lewat `order_number`, bukan UUID**, saat dipakai di URL yang
dibagikan pengguna — supaya UUID internal tidak tersebar:

```php
// app/Models/Order.php
public function getRouteKeyName(): string
{
    return request()->is('admin/*') ? 'id' : 'order_number';
}
```

### 18A.2 CORS

API dipakai aplikasi mobile (tanpa origin) dan panel admin (dengan origin).
Konfigurasi bawaan Laravel membuka `allowed_origins => ['*']`, yang **tidak
boleh** dipakai bersama kredensial.

```php
// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PATCH', 'PUT', 'DELETE'],

    // JANGAN '*' — nilai ini dipasangkan dengan supports_credentials
    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', ''))),

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With'],
    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'Retry-After'],
    'max_age' => 86400,
    'supports_credentials' => true,
];
```

```env
CORS_ALLOWED_ORIGINS=https://admin.seekitar.id,https://seekitar.id
```

> ⚠️ **`allowed_origins => ['*']` + `supports_credentials => true` adalah
> kombinasi terlarang** — peramban menolaknya, dan bila dipaksa lewat wildcard
> pola, situs mana pun bisa mengirim request ber-cookie atas nama admin yang
> sedang login.
>
> Aplikasi mobile **tidak terpengaruh CORS sama sekali** (itu mekanisme
> peramban), jadi daftar ini cukup memuat domain web saja.
>
> `exposed_headers` diperlukan agar klien bisa membaca header kuota
> (`API_DOCUMENTATION.md` §1); tanpa itu, peramban menyembunyikannya.

### 18A.3 Enkripsi Data Sensitif (KTP & NIK)

`PRD.md` §11.1 mewajibkan KTP dan selfie dienkripsi AES-256. Cara
penerapannya **berbeda** untuk berkas dan untuk teks pendek.

> ⚠️ **`Crypt::encryptString()` TIDAK cocok untuk berkas gambar.** Sempat
> diusulkan demikian, tetapi hasil pengukuran menunjukkan:
>
> | Ukuran KTP | Setelah base64 | Pembengkakan |
> | :-- | :-- | :-- |
> | 2 MB | 2,7 MB | +33% |
> | 5 MB | 6,7 MB | +33% |
>
> Selain membengkak, `Crypt` memuat **seluruh berkas ke memori** (baca + hasil
> base64 + salinan JSON), sehingga beberapa unggahan serentak mudah menembus
> `memory_limit`. Berkas terenkripsi juga tidak bisa disajikan lewat URL
> pre-signed, sehingga setiap tampilan gambar harus melewati PHP.

**Yang dipakai (implementasi repo ini):**

| Data | Cara | Alasan |
| :-- | :-- | :-- |
| Berkas KTP & selfie | **Disk `local` (privat)** — bucket S3 privat SSE-KMS di produksi masih **rencana** | Tidak bisa diakses lewat URL tebakan; enkripsi at-rest mengikuti penyedia penyimpanan |
| NIK | **cast `encrypted`** di kolom DB | Teks pendek; hasil 216 byte, muat di `VARCHAR(255)` |
| Akses berkas | Route berizin yang **mengalirkan berkas lewat PHP** | Tidak ada URL publik/pre-signed yang bocor; otorisasi `verify-users` dicek setiap akses |

```php
// Unggah ke disk privat — simpan PATH, bukan URL.
$user->ktp_image    = $request->file('ktp_image')->store("ktp/{$user->id}", 'local');
$user->selfie_image = $request->file('selfie_image')->store("ktp/{$user->id}", 'local');
$user->ktp_submitted_at = now();
```

> **Disk yang ada di `config/filesystems.php` hanya tiga:** `local` (privat),
> `public` (avatar, visibilitas publik), dan `s3` (media produksi, bucket
> `AWS_BUCKET`). **Tidak ada disk `s3-private`.** Konfigurasi `s3-private`
> dengan SSE-KMS (`AWS_BUCKET_PRIVATE` + `AWS_KMS_KEY_ID`) hanyalah rencana
> untuk produksi — saat ini KTP & selfie selalu di disk `local`.

**Kolom terenkripsi** memakai cast bawaan Laravel — otomatis terenkripsi saat
simpan, terdekripsi saat baca:

```php
// app/Models/User.php
protected function casts(): array
{
    return [
        'nik' => 'encrypted',   // satu-satunya kolom terenkripsi di users
    ];
}
```

> ⚠️ Kolom `encrypted` **tidak bisa di-`WHERE`** — setiap baris memakai IV
> berbeda, jadi nilai sama menghasilkan ciphertext berbeda. Untuk memeriksa
> duplikasi NIK, simpan `nik_hash` (`hash('sha256', $nik.config('app.key'))`)
> sebagai kolom terpisah yang bisa diindeks.

**Akses admin** lewat route berizin `verify-users` yang mengalirkan berkas
langsung dari disk privat — tanpa URL publik sama sekali, dan responsnya
dilarang di-cache (`no-store`):

```php
public function media(User $user, string $kind): StreamedResponse
{
    $path = match ($kind) {
        'ktp'    => $user->ktp_image,
        'selfie' => $user->selfie_image,
    };

    abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

    return Storage::disk('local')
        ->response($path)
        ->header('Cache-Control', 'private, no-store');
}
```

**Retensi:** setelah identitasnya disetujui (`verified_at` terisi), berkas KTP
boleh dihapus sesuai kebijakan retensi — data yang tidak disimpan tidak
bisa bocor.

### 18A.4 Proteksi XSS di Blade

> ⚠️ Rekomendasi yang menyarankan *“untuk atribut gunakan `{!! !!}` dengan
> hati-hati”* **keliru dan berbahaya.** `{!! !!}` justru **mematikan**
> escaping — itu sumber XSS, bukan solusinya.

Aturannya sederhana:

| Sintaks | Perilaku | Kapan dipakai |
| :-- | :-- | :-- |
| `{{ $x }}` | `e($x)`, HTML-escaped | **Hampir selalu** |
| `{{ $x }}` di dalam atribut | Aman, asalkan atributnya dikutip | Nilai atribut |
| `@json($x)` | JSON + escaped | Mengirim data ke JavaScript |
| `{!! $x !!}` | **Mentah, tanpa escaping** | Hanya untuk HTML yang dihasilkan sistem sendiri |

**Titik paling rawan di proyek ini adalah `rawColumns()` pada Datatables**,
karena kolom aksi memang dirender sebagai HTML mentah:

```php
// ❌ BERBAHAYA — nama toko diisi pengguna, bisa memuat </button><script>
->addColumn('action', fn ($s) => '<button data-name="'.$s->name.'">Edit</button>')
->rawColumns(['action'])

// ✅ AMAN — render lewat view; Blade meng-escape otomatis
->addColumn('action', fn ($s) => view('admin.stores.actions', ['store' => $s]))
->rawColumns(['action'])
```

Untuk mengirim data ke atribut, pakai `@json` agar tanda kutip ikut aman:

```blade
<button class="edit-btn" data-store='@json($store->only(["id","name"]))'>Edit</button>
```

**Aturan tambahan:**

- `{!! $dataTable->table() !!}` dan `->scripts()` **aman** — keluarannya
  dihasilkan Yajra, bukan masukan pengguna.
- Jangan pernah menaruh input pengguna di dalam `<script>` secara langsung.
  Selalu lewat `@json`.
- Aktifkan Content-Security-Policy sebagai lapis kedua (masih **rencana**):

> ⚠️ **Middleware `SecurityHeaders` tidak ada di repo ini** — proteksi header
> saat ini hanya `EnsureHttps` (redirect HTTP→HTTPS + HSTS di produksi, di-append
> global lewat `bootstrap/app.php`). Bila nanti ditambahkan, header yang layak
> dipasang: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
> `Referrer-Policy: strict-origin-when-cross-origin`, dan CSP yang mengizinkan
> CDN yang dipakai layout admin (§22.3) — persempit menjadi `'self'` bila aset
> di-bundel sendiri.

### 18A.5 Rate Limiting Login Admin

Rate limiter untuk OTP dan API sudah didefinisikan di §5.3. Login admin
memerlukan pembatas tersendiri karena memakai kata sandi — sasaran empuk
serangan tebak-paksa.

```php
// AppServiceProvider::boot()
RateLimiter::for('admin-login', fn (Request $request) => [
    // Dua sumbu: per akun DAN per IP.
    Limit::perMinute(5)->by('admin-login:'.$request->input('email').'|'.$request->ip()),
    Limit::perMinute(20)->by('admin-login-ip:'.$request->ip()),
]);
```

```php
// routes/admin.php — controller-nya `LoginController` (bukan AdminLoginController),
// dan memakai FormRequest `LoginRequest`.
Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])
        ->middleware('throttle:admin-login')->name('login.store');
});
```

> ⚠️ **Dua sumbu itu perlu.** Pembatas per-email saja bisa dilewati dengan
> mencoba banyak email dari satu IP (*password spraying*); pembatas per-IP saja
> bisa dilewati dengan botnet yang menyerang satu akun dari banyak IP.
>
> Batas per-IP dibuat lebih longgar (20) karena beberapa admin bisa berbagi
> satu IP kantor.

Melengkapi janji `PRD.md` §11.3 (*“gagal 5x → blokir sementara”*):

```php
// Catat kegagalan untuk audit & deteksi anomali.
Event::listen(Failed::class, function (Failed $event) {
    Log::channel('security')->warning('Login admin gagal', [
        'email' => $event->credentials['email'] ?? null,
        'ip'    => request()->ip(),
    ]);
});

Event::listen(Lockout::class, fn (Lockout $e) =>
    Log::channel('security')->alert('Login admin diblokir sementara', ['ip' => request()->ip()])
);
```

Sesi admin juga dipersingkat dibanding pengguna biasa:

```env
SESSION_LIFETIME=120          # menit
SESSION_EXPIRE_ON_CLOSE=true
```

### 18A.6 Validasi Nomor Telepon Indonesia

`PRD.md` §5.3.1 mensyaratkan nomor HP Indonesia, dan `DATABASE.md` §4.1
menyimpannya sebagai `VARCHAR(15)` berawalan `62`. Tanpa normalisasi, satu
orang bisa membuat beberapa akun dengan menulis nomor yang sama dalam format
berbeda: `08123456789`, `+628123456789`, `628123456789`.

**Tidak memakai `propanistas/laravel-phone`** (paketnya tidak dipasang).
Normalisasi ditulis sendiri sekali di `app/Support/PhoneNumber.php` — satu
kelas statis dengan `normalize()` (bentuk `62xxxxxxxxxx`) dan `forDisplay()`
(`+62 812-3456-789` untuk UI/WhatsApp):

```php
// app/Support/PhoneNumber.php
final class PhoneNumber
{
    public const MAX_LENGTH = 15;   // panjang kolom users.phone

    public static function normalize(?string $input): ?string
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $input) ?? '';

        return match (true) {
            str_starts_with($digits, '0')  => '62'.substr($digits, 1),
            str_starts_with($digits, '62') => $digits,
            default                        => '62'.$digits,
        };
    }
}
```

Dipanggil dari `prepareForValidation()` tiap FormRequest API yang menerima
nomor (mis. `RequestOtpRequest`) — normalisasi **sebelum** validasi:

```php
// FormRequest — mis. app/Http/Requests/Api/RequestOtpRequest.php
protected function prepareForValidation(): void
{
    $this->merge(['phone' => PhoneNumber::normalize($this->input('phone'))]);
}

public function rules(): array
{
    return [
        'phone' => ['required', 'string', 'regex:/^62[0-9]{8,13}$/', 'max:'.PhoneNumber::MAX_LENGTH],
    ];
}
```

| Masukan pengguna | Tersimpan |
| :-- | :-- |
| `08123456789` | `628123456789` |
| `+62 812-3456-789` | `628123456789` |
| `628123456789` | `628123456789` |

> ⚠️ Normalisasi harus dilakukan di **satu tempat** (`PhoneNumber::normalize`),
> bukan disalin ke tiap controller. Jika satu jalur masuk lupa menormalkan,
> `UNIQUE` pada `users.phone` tidak akan menangkap duplikatnya — dan OTP
> terkirim ke nomor yang sama untuk dua akun berbeda. Kelas yang sama juga
> dipakai **rate limiter OTP** (`AppServiceProvider::configureRateLimiting()`)
> agar `0812…` dan `62812…` dihitung sebagai nomor yang sama — throttle
> berjalan sebelum FormRequest sempat menormalkan.
>
> Logika normalisasi yang sama diterapkan di sisi klien
> (`Mobile_Implementation_Guide.md` §7.1) agar pengguna melihat format yang
> konsisten, tetapi **server tetap menormalkan ulang** — masukan dari klien
> tidak pernah dipercaya.

### 18A.7 Ringkasan Pemetaan ke PRD §11

| Janji di PRD | Implementasi |
| :-- | :-- |
| KTP & selfie disimpan privat | §18A.3 — disk `local` privat (S3 privat SSE-KMS di produksi: rencana) + cast `encrypted` untuk NIK |
| Koordinat tidak ditampilkan mentah | Lokasi pembeli dibulatkan (PRD §5.2.3) |
| Nomor telepon bertahap | Disaring di API Resource, bukan di klien |
| HTTPS/TLS 1.3 | Konfigurasi server + `SESSION_SECURE_COOKIE=true` + `EnsureHttps` |
| Validasi input ketat | FormRequest khusus (login/profil/kata sandi/reject verifikasi) + `$request->validate()` inline (§11) |
| Rate limiting OTP & penawaran | §5.3 |
| Blokir setelah 5x gagal login | §18A.5 |
| Cegah XSS & SQL Injection | §18A.4 + Eloquent binding (`DATABASE.md` §9) |

---

## 19. MIGRATION & SEEDER (LENGKAP)

### 19.1 Migration: Tidak berubah dari dokumen database.

#### ⚠️ Tabel permission Spatie butuh kolom morph UUID

Migrasi bawaan Spatie (`create_permission_tables.php.stub`) mendeklarasikan
kolom penghubung sebagai `unsignedBigInteger`. `users.id` di Seekitar adalah
**UUID `CHAR(36)`**, sehingga stub itu menghasilkan tipe yang tidak cocok dan
`assignRole()` gagal.

Dua penyesuaian yang wajib dilakukan:

```php
// config/permission.php
'model_morph_key' => 'model_uuid',   // bukan 'model_id'
```

```php
// database/migrations/..._create_permission_tables.php
$table->uuid($morphKey);             // bukan unsignedBigInteger()
```

Karena itu migrasi permission **ditulis sendiri** di repo ini, tidak memakai
`vendor:publish` mentah. `name` dan `guard_name` juga dipangkas ke
`VARCHAR(125)`: dengan utf8mb4 (4 byte/karakter), `UNIQUE(name, guard_name)`
pada dua kolom `VARCHAR(255)` melewati batas 3072 byte indeks InnoDB dan
memicu `ERROR 1071: Specified key was too long`.

Model `User` juga harus memakai trait `Spatie\Permission\Traits\HasRoles`,
kalau tidak `assignRole()`/`hasRole()` tidak tersedia sama sekali.

### 19.2 Seeder

**CategorySeeder:** 24 kategori dua level — 8 induk dari
`BRANDING-GUIDELINE.md` §3.7 (Makanan & Harian, Jasa Rumah, Servis & Bengkel,
Material Bangunan, Pertanian & Ternak, Sewa Acara, Barang Bekas, UMKM &
Kerajinan), masing-masing dengan 2 subkategori. Kedalaman dibatasi dua level
sesuai PRD §5.1.

> Seeder ini **wajib** jalan saat deploy awal: `customer_requests.category_id`
> NOT NULL dengan FK `RESTRICT`, jadi tanpa kategori tidak ada permintaan yang
> bisa dibuat sama sekali.

**RolesAndPermissionsSeeder:**

```php
public function run(): void
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    DB::transaction(function () {
        $permissions = [];
        foreach (self::PERMISSIONS as $name) {          // 17 permission, §6.2
            $permissions[$name] = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(array_values($permissions));

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(array_values(array_diff_key(
            $permissions,
            array_flip(['manage-users', 'manage-settings', 'manage-fees']),
        )));

        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Nomor, email & sandi dari config, BUKAN ditanam di kode — kalau
        // tidak, kredensial contoh yang sama menjadi super-admin di produksi.
        $user = User::withTrashed()->firstOrCreate(
            ['phone' => config('seekitar.super_admin_phone')],
            [
                'name'  => 'Sinta Wijaya',
                'email' => config('seekitar.super_admin_email'),
                // Stempel + status == "terverifikasi"; khusus akun staf ini
                // identitasnya dianggap sudah ditinjau. "Pro" tidak perlu
                // ditulis — ia turunan dari toko tervalidasi.
                'status'      => \App\Enums\UserStatus::Terverifikasi,
                'verified_at' => now(),
            ],
        );
        $user->password ??= config('seekitar.super_admin_password');   // hanya saat akun baru
        $user->syncRoles([$superAdmin->name]);
    });

    app(PermissionRegistrar::class)->forgetCachedPermissions();
}
```

> ⚠️ Tiga hal yang mudah salah di seeder ini:
>
> 1. **`firstOrCreate`, bukan `create`.** Seeder produksi ikut jalan setiap
>    deploy; `Permission::create()` akan melempar unique violation pada deploy
>    kedua — bertentangan dengan syarat idempoten di bawah.
> 2. **Oper objek Permission ke `syncPermissions()`, bukan namanya.** Dengan
>    string, Spatie memanggil `findByName()` yang membaca cache permission, dan
>    cache itu bisa belum memuat baris yang baru dibuat pada transaksi sama.
> 3. **`manage-users`, `manage-settings`, dan `manage-fees` ditahan dari role
>    `admin`** (`ADMIN_EXCLUDED` di seeder) — supaya admin biasa tidak bisa
>    mengubah sesama admin, halaman pengaturan tetap khusus super-admin
>    (`API_DOCUMENTATION.md` §10.5), dan biaya layanan hanya diatur pemilik.

> Daftar permission di atas **sudah** memuat 17 permission sesuai §6.2.
> Pastikan keduanya tetap sinkron saat menambah permission baru.

**`DatabaseSeeder`** — satu pintu, dan urutannya penting karena
`RolesAndPermissionsSeeder` membuat user super-admin yang butuh role:

```php
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,  // 1. role & permission dulu
            CategorySeeder::class,             // 2. 24 kategori (wajib produksi)
            SettingSeeder::class,              // 3. nilai default tabel settings
        ]);

        // Data contoh HANYA untuk pengembangan.
        if (app()->environment('local', 'testing')) {
            $this->call(DummyDataSeeder::class);
        }
    }
}
```

```bash
php artisan db:seed                          # semua
php artisan db:seed --class=CategorySeeder   # satu saja
```

> ⚠️ Seeder produksi (`RolesAndPermissionsSeeder`, `CategorySeeder`,
> `SettingSeeder`) wajib **idempoten** — pakai `firstOrCreate`/`updateOrCreate`,
> bukan `create`. Menjalankan ulang saat deploy tidak boleh menggandakan data
> atau melempar error unique.

---


#### 19.2a Factory & data demo bervolume

Selain seeder produksi, tersedia **13 factory** dan `DemoDataSeeder` yang
mengisi SELURUH tabel dengan ratusan baris realistis (local/testing saja).

| Berkas | Isi |
| :-- | :-- |
| `database/factories/Support/Wilayah.php` | 24 titik kecamatan nyata di Kabupaten Pasuruan |
| `UserFactory` | state `basic`, `verified`, `pro`, `menungguKtp`, `diblokir` |
| `StoreFactory` | `terverifikasi`, `menunggu`, `ditolak`, `nonaktif`, `tipe()`, `diTitik()` |
| `ListingFactory` | `produk`, `jasa`, `sewa`, `stokHabis` — 36 judul katalog nyata |
| `CustomerRequestFactory` | `terbuka`, `ditutup`, `kedaluwarsa`, `diperpanjang`, `mendesak` |
| `OfferFactory` | `menunggu`, `diterima`, `ditolak`, `lewatWaktu`, `denganHarga()` |
| `OrderFactory` | tiap status ENUM + `diantar`/`diambil`, `cod`/`transfer` |
| `ReviewFactory` | `keToko()`, `kePembeli()`, `bintang()` |
| `DisputeFactory` | `terbuka`, `lewatSla`, `direspons`, `selesai` |
| `UserDeviceFactory`, `FavoriteFactory`, `CategoryFactory`, `NotificationFactory`, `UserAddressFactory` | pelengkap |

Volume default `DemoDataSeeder` menghasilkan ±1.300 baris; bisa diskalakan
lewat `SEEKITAR_DEMO_SCALE=0.1 php artisan db:seed`.

**Semua koordinat berada di Kabupaten Pasuruan.** Titik acak sedunia membuat
`scopeNearby()` tidak pernah mengembalikan apa pun — fitur utama produk justru
tidak bisa dicoba dengan data contohnya sendiri.

> ⚠️ **Jangan mengacak status dengan `$i % n`.** Pola itu tampak rapi tetapi
> diam-diam rapuh: dengan 11 baris, `$i % 11 === 0` hanya benar sekali, dan
> bila indeks itu sudah diambil cabang lain, statusnya **tidak pernah lahir**.
> Nyata terjadi — status `closed` dan `pending` hilang sama sekali pada volume
> kecil, membuat filter panel admin selalu kosong. Dipakai
> `alokasiStatus()` yang menjamin tiap keadaan muncul minimal satu kali.

> ⚠️ **Kolom POINT tidak bisa diisi lewat `definition()`.** Isinya harus
> ekspresi `ST_GeomFromText(..., 'axis-order=long-lat')`, jadi factory
> memakai `afterMaking()` yang memanggil `setLocation()`. Menaruh koordinat
> sebagai atribut semu (`_lat`) **gagal**: factory membuat model lewat
> `new Model($attributes)` yang menghormati `$fillable`, dan atribut di luar
> daftar itu melempar `MassAssignmentException` — bukan diabaikan.

**Verifikasi:** `tools/dev/run-seeders.php` benar-benar MENJALANKAN seluruh
seeder terhadap SQLite in-memory dan memeriksa 18 hal — termasuk apakah setiap
nilai ENUM terwakili, `rating_avg` cocok dengan ulasannya, dan setiap WKT
memuat `axis-order=long-lat`. Dipanggil otomatis oleh `check-seeders.mjs`.

SQLite tidak punya CHECK bergaya MySQL, jadi pelanggarannya diperiksa manual
lewat query di skrip itu. Perilaku constraint sesungguhnya tetap harus
diverifikasi di MySQL 8.

## 20. TESTING

Target minimum sebelum rilis: **seluruh endpoint API punya feature test**, dan
setiap Service punya unit test.

```bash
php artisan test                      # semua
php artisan test --filter=OfferTest   # satu berkas
php artisan test --parallel           # lebih cepat di CI
```

### 20.1 Feature Test (API)

| Berkas | Yang diuji |
| :-- | :-- |
| `AuthTest` | OTP terkirim, rate limit per nomor, OTP salah/kedaluwarsa, token terbit, logout mencabut token |
| `ProfileTest` | Update profil, validasi avatar, `EnsureProfileComplete` memblokir profil kosong |
| `VerificationTest` | Unggah KTP, approve menaikkan level ke 2, reject **tetap** level 1 |
| `StoreTest` | Buat toko butuh level 2, pencarian radius, toko `pending` tidak muncul |
| `ListingTest` | CHECK harga/stok/slot per tipe, maks 5 gambar, hanya pemilik boleh ubah |
| `CustomerRequestTest` | Buat permintaan memicu broadcast, batas 2 perpanjangan, auto-expire |
| `OfferTest` | Satu toko satu penawaran, harga di luar budget ditolak, kedaluwarsa mengikuti request |
| `AcceptOfferTest` | Offer lain ter-reject, request `closed`, order terbentuk — **dalam satu transaksi** |
| `OrderTest` | `listing_id` XOR `offer_id`, transisi status sah/tidak sah, `order_number` unik |
| `ReviewTest` | Dua arah, satu per arah, jendela 7 hari, hanya `buyer_to_store` mengubah rating |
| `DisputeTest` | Order terkunci saat dispute, resolve membuka kunci |
| `AdminTest` | Non-admin ditolak, blokir mencabut token, super-admin lolos `Gate::before` |

Contoh yang menguji aturan paling rawan — balapan saat menerima penawaran:

```php
it('hanya menghasilkan satu order meski accept dipanggil dua kali', function () {
    $request = CustomerRequest::factory()->has(Offer::factory()->count(3))->create();
    $offer   = $request->offers->first();

    $this->actingAs($request->user)
        ->patchJson("/api/v1/offers/{$offer->id}/accept")
        ->assertOk();

    // Percobaan kedua harus ditolak, bukan membuat order kedua.
    $this->actingAs($request->user)
        ->patchJson("/api/v1/offers/{$offer->id}/accept")
        ->assertStatus(409);

    expect(Order::where('offer_id', $offer->id)->count())->toBe(1);
    expect($request->fresh()->status)->toBe(RequestStatus::Closed);
    expect($request->offers()->where('status', OfferStatus::Rejected)->count())->toBe(2);
});
```

### 20.2 Unit Test (Service)

| Berkas | Fokus |
| :-- | :-- |
| `OrderStateMachineTest` | Matriks transisi lengkap, termasuk yang ditolak |
| `GeolocationServiceTest` | Urutan `POINT(lng lat)` benar, konversi km↔meter |
| `BroadcastServiceTest` | Pencocokan dua arah, toko sendiri dikecualikan, batas 50 |
| `OtpServiceTest` | OTP di-hash, TTL 5 menit, batas 5 percobaan |
| `SettingServiceTest` | Cache dibersihkan setelah `set()` |

### 20.3 Konfigurasi

```xml
<!-- phpunit.xml -->
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="seekitar_testing"/>
<env name="DB_HOST" value="127.0.0.1"/>
<env name="DB_PORT" value="3306"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="CACHE_STORE" value="array"/>
```

> ⚠️ **Test dijalankan terhadap MySQL sungguhan, bukan SQLite.** Seluruh
> skema bergantung pada fitur yang tidak ada di engine lain: `POINT SRID
> 4326`, `SPATIAL INDEX`, tipe `SET`, dan `CHECK` constraint. Menguji di
> SQLite berarti menguji skema yang berbeda dari yang dijalankan produksi —
> `nearby()` bisa hijau padahal `ST_Distance_Sphere` tidak pernah dieksekusi.
>
> Nama basis data test **wajib mengandung `test`**. `Tests\RefreshesDatabase`
> menolak berjalan jika tidak, karena `migrate:fresh` men-DROP semua tabel dan
> salah konfigurasi akan menghapus data pengembangan tanpa peringatan.

**Menyiapkan basis data test:**

```sql
CREATE DATABASE seekitar_testing
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Di GitHub Actions, pakai service container agar tidak perlu memasang MySQL:

```yaml
services:
  mysql:
    image: mysql:8.0
    env:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: seekitar_testing
    ports: ['3306:3306']
    options: >-
      --health-cmd="mysqladmin ping -h 127.0.0.1"
      --health-interval=10s --health-timeout=5s --health-retries=5
```

---

## 21. DEPLOYMENT

### 21.1 Environment Variables

```env
# --- Aplikasi -------------------------------------------------------------
APP_NAME=Seekitar
APP_ENV=production
APP_KEY=                       # php artisan key:generate
APP_DEBUG=false                # WAJIB false di produksi
APP_URL=https://api.seekitar.id
APP_TIMEZONE=UTC               # simpan UTC, konversi di klien

# --- Database -------------------------------------------------------------
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=seekitar
DB_USERNAME=seekitar
DB_PASSWORD=

# --- Redis (cache, session, queue) ---------------------------------------
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=
REDIS_PORT=6379
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# --- JWT & Sanctum/Session ------------------------------------------------
JWT_SECRET=                          # php artisan jwt:secret
JWT_TTL=43200                        # 30 hari
JWT_REFRESH_TTL=20160                # 14 hari
JWT_BLACKLIST_ENABLED=true
SANCTUM_STATEFUL_DOMAINS=admin.seekitar.id
SESSION_DOMAIN=.seekitar.id
SESSION_SECURE_COOKIE=true

# --- Penyimpanan berkas ---------------------------------------------------
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=seekitar-media
AWS_URL=https://cdn.seekitar.id
# AWS_BUCKET_PRIVATE=seekitar-ktp   # RENCANA — KTP & selfie, TIDAK publik (disk s3-private belum ada, §18A.3)
# AWS_KMS_KEY_ID=                   # RENCANA — kunci SSE-KMS untuk bucket privat (§18A.3)

# --- Keamanan ---------------------------------------------------------------
CORS_ALLOWED_ORIGINS=https://admin.seekitar.id,https://seekitar.id
SESSION_EXPIRE_ON_CLOSE=true
TRUSTED_PROXIES=*                   # disetel EnsureHttps/trustProxies di bootstrap/app.php

# --- Firebase (FCM) — RENCANA, belum diimplementasikan (§15.1) --------------
# FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json
# FIREBASE_PROJECT_ID=seekitar-prod

# --- WhatsApp OTP ---------------------------------------------------------
WHATSAPP_DRIVER=kirimwa        # baileys | kirimwa | email | log
KIRIMWA_URL=https://api.kirimwa.id/v1
KIRIMWA_TOKEN=
BAILEYS_URL=http://127.0.0.1:3001
BAILEYS_TOKEN=                  # wajib di produksi bila WHATSAPP_DRIVER=baileys
# BAILEYS_REDIS_URL=redis://127.0.0.1:6379   # jalur cepat socket (opsional)
# TWILIO_SID=                   # RENCANA — twilio/sdk tidak dipasang (§15.2)
# TWILIO_TOKEN=                 # RENCANA
# TWILIO_WHATSAPP_FROM=         # RENCANA

# --- Monitoring -----------------------------------------------------------
SENTRY_LARAVEL_DSN=
LOG_CHANNEL=stack
LOG_LEVEL=warning              # 'debug' membanjiri disk di produksi
```

### 21.2 Perintah Deploy

```bash
php artisan down --render="errors::503"
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force            # idempoten, aman diulang
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart              # worker memuat ulang kode baru
php artisan up
```

> ⚠️ `queue:restart` **wajib** setelah deploy. Tanpa itu, worker lama terus
> berjalan dengan kode versi sebelumnya sampai proses dimatikan manual.
>
> ⚠️ Jangan pakai `config:cache` bila ada `env()` di luar berkas `config/` —
> nilainya akan menjadi `null` setelah cache dibuat.

### 21.2a Penyiapan Redis

Redis menangani tiga hal sekaligus di Seekitar: cache, session, dan antrian.
Ketiganya punya sifat berbeda, dan menyatukannya tanpa pemisahan adalah sumber
masalah yang sulit dilacak.

```bash
sudo apt install redis-server
sudo systemctl enable --now redis-server
redis-cli ping     # -> PONG
```

**`/etc/redis/redis.conf`:**

```conf
bind 127.0.0.1 ::1              # JANGAN 0.0.0.0 — Redis tanpa auth = terbuka
requirepass <sandi-panjang-acak>
maxmemory 512mb
maxmemory-policy allkeys-lru    # lihat peringatan di bawah
appendonly yes                  # AOF: antrian tidak hilang saat restart
appendfsync everysec
```

**Pisahkan database** agar `cache:clear` tidak ikut menghapus antrian:

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=
REDIS_PORT=6379

REDIS_DB=0            # cache
REDIS_CACHE_DB=1      # cache tag
REDIS_QUEUE_DB=2      # antrian job
```

```php
// config/database.php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'default' => ['host' => env('REDIS_HOST'), 'password' => env('REDIS_PASSWORD'),
                  'port' => env('REDIS_PORT', 6379), 'database' => env('REDIS_DB', 0)],
    'cache'   => ['host' => env('REDIS_HOST'), 'password' => env('REDIS_PASSWORD'),
                  'port' => env('REDIS_PORT', 6379), 'database' => env('REDIS_CACHE_DB', 1)],
    'queue'   => ['host' => env('REDIS_HOST'), 'password' => env('REDIS_PASSWORD'),
                  'port' => env('REDIS_PORT', 6379), 'database' => env('REDIS_QUEUE_DB', 2)],
],
```

> ⚠️ **`allkeys-lru` bisa membuang job antrian.** Kebijakan itu menghapus kunci
> apa pun saat memori penuh — termasuk `BroadcastRequestJob` yang belum
> diproses. Permintaan pembeli lalu tidak pernah disebar, tanpa error apa pun.
>
> Karena itu database antrian **wajib** dipisah dan diberi kebijakan berbeda:
>
> ```bash
> redis-cli -n 2 CONFIG SET maxmemory-policy noeviction
> ```
>
> Dengan `noeviction`, Redis menolak penulisan baru saat penuh (job gagal
> dengan error yang terlihat) alih-alih membuang job lama secara diam-diam.
>
> Alternatif yang lebih aman untuk produksi: jalankan **dua instance Redis**
> pada port berbeda — satu untuk cache (boleh evict), satu untuk antrian
> (tidak boleh).

### 21.2b Penyimpanan Objek (S3 / MinIO)

**Bucket untuk media** (`seekitar-media`); bucket privat `seekitar-ktp`
(SSE-KMS) untuk KTP & selfie adalah **rencana** — saat ini berkas itu di
disk `local` privat (§18A.3):

| Bucket | Akses | Isi | Status |
| :-- | :-- | :-- | :-- |
| `seekitar-media` | publik lewat CDN | Foto listing, avatar | **Aktif** |
| `seekitar-ktp` | **privat**, SSE-KMS | KTP & selfie | **Rencana** (kini di disk `local`) |

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=seekitar-media
AWS_URL=https://cdn.seekitar.id
AWS_USE_PATH_STYLE_ENDPOINT=false
```

**MinIO untuk pengembangan lokal** — API-nya kompatibel S3, jadi kode tidak
perlu diubah:

```bash
docker run -d --name minio -p 9000:9000 -p 9001:9001 \
  -e MINIO_ROOT_USER=seekitar -e MINIO_ROOT_PASSWORD=rahasia123 \
  -v minio-data:/data minio/minio server /data --console-address ":9001"
```

```env
AWS_ENDPOINT=http://localhost:9000
AWS_USE_PATH_STYLE_ENDPOINT=true    # WAJIB true untuk MinIO
```

> ⚠️ `AWS_USE_PATH_STYLE_ENDPOINT=true` wajib untuk MinIO. Tanpa itu, klien
> memakai gaya *virtual-host* (`bucket.localhost:9000`) yang tidak bisa
> di-resolve, dan unggahan gagal dengan galat DNS yang membingungkan.
>
> Kebijakan bucket privat harus benar-benar diuji, bukan diasumsikan:
> ```bash
> curl -I https://seekitar-ktp.s3.ap-southeast-1.amazonaws.com/ktp/contoh.jpg
> # harus 403, BUKAN 200
> ```

### 21.2c SSL/TLS

Dua pilihan, keduanya sah:

| Cara | Cocok untuk | Catatan |
| :-- | :-- | :-- |
| **Let's Encrypt** (certbot) | Server sendiri | Gratis, perpanjangan otomatis |
| **Cloudflare** | Butuh CDN + proteksi DDoS | Pakai mode **Full (strict)** |

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d seekitar.id -d www.seekitar.id \
  -d api.seekitar.id -d admin.seekitar.id
sudo certbot renew --dry-run     # uji perpanjangan otomatis
```

```nginx
# Paksa HTTPS + HSTS (PRD §11.3: TLS 1.3)
server {
    listen 443 ssl http2;
    server_name api.seekitar.id;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers off;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
}

server {
    listen 80;
    server_name api.seekitar.id;
    return 301 https://$host$request_uri;
}
```

> ⚠️ Mode Cloudflare **"Flexible" berbahaya**: lalu lintas Cloudflare→server
> berjalan tanpa enkripsi, sehingga token Bearer melintas sebagai teks biasa.
> Selalu gunakan **Full (strict)**.
>
> Aktifkan HSTS **setelah** HTTPS terbukti stabil. `max-age` satu tahun tidak
> bisa dibatalkan cepat — peramban akan menolak HTTP sampai masa itu habis.

### 21.2d Backup Basis Data

Tanpa backup teruji, seluruh data transaksi bergantung pada satu disk.

```bash
#!/usr/bin/env bash
# /usr/local/bin/seekitar-backup.sh
set -euo pipefail

STAMP=$(date +%Y%m%d-%H%M)
FILE="/tmp/seekitar-${STAMP}.sql.gz"

# --single-transaction: konsisten tanpa mengunci tabel (InnoDB)
# --routines --triggers: ikut sertakan objek non-tabel
mysqldump --single-transaction --quick --routines --triggers \
  -u"$DB_USER" -p"$DB_PASS" seekitar | gzip -9 > "$FILE"

aws s3 cp "$FILE" "s3://seekitar-backup/db/${STAMP}.sql.gz" \
  --storage-class STANDARD_IA --sse aws:kms

rm -f "$FILE"
```

```cron
0 2 * * * /usr/local/bin/seekitar-backup.sh >> /var/log/seekitar-backup.log 2>&1
```

| Aspek | Nilai |
| :-- | :-- |
| Frekuensi | Harian pukul 02:00 WIB |
| Retensi | 30 harian + 12 bulanan (aturan lifecycle S3) |
| Enkripsi | SSE-KMS, bucket terpisah dari media |
| **Uji pemulihan** | **Wajib tiap bulan** ke basis data sementara |

> ⚠️ **Backup yang tidak pernah diuji bukan backup.** Jadwalkan pemulihan
> bulanan ke database uji dan pastikan jumlah barisnya masuk akal:
> ```bash
> gunzip -c backup.sql.gz | mysql -u root seekitar_restore_test
> mysql -e "SELECT COUNT(*) FROM seekitar_restore_test.orders;"
> ```
>
> Bucket backup **tidak boleh** berada di akun/kredensial yang sama dengan
> server aplikasi. Penyerang yang menguasai server juga akan menghapus
> backup-nya. Pakai IAM user terpisah yang hanya punya izin `PutObject`.
>
> Berkas di S3 (foto listing, KTP) **tidak** tercakup `mysqldump` — aktifkan
> *versioning* dan *cross-region replication* pada bucket media secara terpisah.

### 21.3 Queue Worker (Supervisor)

Antrian `high` diproses lebih dulu agar OTP dan broadcast tidak tertahan di
belakang pekerjaan berat.

```ini
; /etc/supervisor/conf.d/seekitar-worker.conf
[program:seekitar-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/seekitar/artisan queue:work redis --queue=high,default --tries=3 --max-time=3600 --sleep=1
directory=/var/www/seekitar
autostart=true
autorestart=true
stopwaitsecs=3600
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/seekitar/storage/logs/worker.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start seekitar-worker:*
```

`--max-time=3600` mendaur ulang worker tiap jam untuk mencegah kebocoran
memori. `stopwaitsecs` dibuat besar agar job yang sedang berjalan tidak
terpotong saat restart.

### 21.4 Scheduler (Cron)

Laravel hanya butuh **satu** entri cron; sisanya diatur di `routes/console.php`:

```cron
* * * * * cd /var/www/seekitar && php artisan schedule:run >> /dev/null 2>&1
```

Verifikasi jadwal yang terdaftar:

```bash
php artisan schedule:list
```

**Alternatif tanpa cron — `schedule:work` di Supervisor.** Berguna pada
kontainer yang tidak menjalankan cron, dan membuat semua proses latar
terpantau lewat satu perkakas:

```ini
; /etc/supervisor/conf.d/seekitar-schedule.conf
[program:seekitar-schedule]
command=php /var/www/seekitar/artisan schedule:work
directory=/var/www/seekitar
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/seekitar/storage/logs/schedule.log
```

> ⚠️ **Pilih salah satu — cron ATAU `schedule:work`, jangan keduanya.**
> Menjalankan bersamaan membuat setiap tugas terjadwal dieksekusi dua kali:
> permintaan kedaluwarsa diproses ganda, dan notifikasi terkirim dua kali.
>
> ⚠️ `numprocs=1` mutlak untuk scheduler. Tidak seperti worker antrian yang
> boleh diperbanyak, dua proses scheduler berarti dua kali eksekusi. Pada
> beberapa server, tambahkan `->onOneServer()` pada tugasnya (sudah dipakai di
> §14.1) dan pastikan cache terpusat di Redis.

**Memantau semua proses:**

```bash
sudo supervisorctl status
# seekitar-worker:seekitar-worker_00   RUNNING   pid 1234, uptime 2:14:03
# seekitar-schedule                    RUNNING   pid 1240, uptime 2:14:03
```

### 21.5 Tooling Pengembangan

Hanya untuk lokal — **jangan** dipasang di produksi:

```bash
composer require --dev laravel/telescope barryvdh/laravel-debugbar
php artisan telescope:install && php artisan migrate
```

```php
// AppServiceProvider::register()
if ($this->app->environment('local')) {
    $this->app->register(TelescopeServiceProvider::class);
}
```

Telescope sangat membantu untuk mengintip **job antrian yang gagal** dan query
lambat — dua hal yang paling sering bermasalah di sistem berbasis broadcast
seperti ini. Batasi perekamannya agar tidak membengkak:

```php
Telescope::filter(fn (IncomingEntry $entry) =>
    $entry->isReportableException() || $entry->isFailedJob() || $entry->isSlowQuery()
);
```

### 21.6 Monitoring Produksi

Telescope tidak dipakai di produksi (menyimpan setiap request ke database).
Untuk produksi dipakai dua perkakas dengan peran berbeda:

| Perkakas | Menjawab | Lingkup |
| :-- | :-- | :-- |
| **Sentry** | “Apa yang rusak, di baris mana?” | Galat & exception |
| **Laravel Pulse** | “Apa yang lambat, apa yang menumpuk?” | Performa & antrian |

#### Sentry — pelacakan galat

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=<dsn-anda>
```

```env
SENTRY_LARAVEL_DSN=https://xxx@xxx.ingest.sentry.io/xxx
SENTRY_TRACES_SAMPLE_RATE=0.1     # 10% request; 1.0 terlalu mahal & bising
SENTRY_ENVIRONMENT=production
```

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    Integration::handles($exceptions);

    // Galat yang MEMANG alur normal jangan mencemari laporan —
    // kalau semua dilaporkan, yang penting jadi tenggelam.
    $exceptions->dontReport([
        ValidationException::class,
        AuthenticationException::class,
        ModelNotFoundException::class,
        ThrottleRequestsException::class,
    ]);
})
```

> 🔒 **Jangan sampai data pribadi ikut terkirim ke Sentry.** Body request bisa
> memuat OTP, NIK, atau token:
>
> ```php
> // config/sentry.php
> 'send_default_pii' => false,
> 'before_send' => function (Event $event): ?Event {
>     $request = $event->getRequest();
>     foreach (['phone', 'otp', 'nik', 'token', 'password'] as $key) {
>         if (isset($request['data'][$key])) {
>             $request['data'][$key] = '[disaring]';
>         }
>     }
>     $event->setRequest($request);
>     return $event;
> },
> ```
>
> Tambahkan konteks yang berguna tanpa membocorkan identitas — cukup ID:
> ```php
> Sentry::configureScope(fn (Scope $s) => $s->setUser(['id' => auth()->id()]));
> ```

#### Laravel Pulse — pemantauan performa

```bash
composer require laravel/pulse
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
php artisan migrate
```

Yang paling relevan untuk Seekitar:

| Kartu Pulse | Kenapa penting di sini |
| :-- | :-- |
| **Slow Queries** | Query radius `ST_Distance_Sphere` adalah yang terberat (`DATABASE.md` §11) |
| **Queues** | Antrian `high` menumpuk = OTP & broadcast tertunda |
| **Slow Jobs** | `BroadcastRequestJob` melambat saat penyedia bertambah |
| **Exceptions** | Ringkasan cepat, detailnya tetap di Sentry |
| **Slow Requests** | Endpoint pencarian biasanya yang pertama melambat |

```php
// Pulse hanya untuk admin — dasbornya memuat data operasional sensitif.
Gate::define('viewPulse', fn (User $user) => $user->hasRole('super-admin'));
```

```env
PULSE_ENABLED=true
PULSE_SAMPLE_RATE=0.1        # rekam 10% agar tidak membebani
```

> ⚠️ Tabel Pulse tumbuh cepat. Pangkas berkala, kalau tidak ia sendiri yang
> menjadi penyebab lambatnya database:
> ```php
> Schedule::command('pulse:trim')->daily();
> ```
>
> **Uptime monitoring** ditangani layanan eksternal (UptimeRobot/Better Stack)
> yang menembak `GET /up` — endpoint health check bawaan Laravel yang sudah
> aktif di `bootstrap/app.php`. Pemantauan dari dalam server tidak berguna saat
> servernya sendiri mati.

---

## 22. LAMPIRAN: CONTOH KODE BLADE & CONTROLLER

### 22.1 Datatables Controller — pola facade `DataTables::eloquent()`

> ⚠️ **Kategori TIDAK memakai Datatables** — `CategoriesDataTable` tidak ada
> di repo ini; halaman kategorinya daftar induk + subkategori dimuat penuh
> (§9.2, §10). Kelas turunan `Yajra\DataTables\Services\DataTable` juga **tidak
> ada** di paket inti `yajra/laravel-datatables-oracle` (ia berasal dari paket
> terpisah `laravel-datatables-buttons`, yang tidak dipasang). Pola yang benar
> adalah membungkus facade `DataTables::eloquent()` di kelas biasa
> (`app/DataTables/`), lalu controller memanggilnya dari endpoint `data`:

```php
// app/DataTables/CustomerRequestsDataTable.php
class CustomerRequestsDataTable
{
    public function json(Request $request): JsonResponse
    {
        $query = CustomerRequest::query()
            ->with(['user:id,name,phone,verified_at,avatar_url', 'category:id,name'])
            // select() HARUS mendahului withCount() — kalau terbalik,
            // subquery offers_count ikut terhapus tanpa error apa pun.
            ->select([...])
            ->withCount('offers');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return DataTables::eloquent($query)
            // Sel kaya dirender lewat partial — Blade meng-escape otomatis;
            // hanya kolom di daftar rawColumns yang tidak di-escape ulang.
            ->editColumn('title', fn (CustomerRequest $r) => view('admin.requests._judul', ['request' => $r])->render())
            ->editColumn('status', fn (CustomerRequest $r) => view('admin.requests._status', ['request' => $r])->render())
            ->rawColumns(['title', 'status'])
            ->toJson();
    }
}
```

**Controller** cukup memanggilnya dari endpoint AJAX (tidak ada objek
`$dataTable` yang di-render ke view — tabelnya dibangun `table-page.blade.php`,
lihat §8 "Pola halaman"):

```php
// Admin\CustomerRequestController
public function data(Request $request, CustomerRequestsDataTable $table): JsonResponse
{
    return $table->json($request);
}
```

> Satu-satunya hal yang **tidak boleh** dicampur: jangan memakai kelas
> `Services\DataTable` (tidak ada) dengan ekspektasi `$dataTable->table()` —
> tabel admin ditulis sebagai `<table>` + inisialisasi DataTables oleh
> `admin.partials.table-page`, bukan oleh kelas turunan Yajra.

### 22.2 Blade Partial (categories/index.blade.php) — daftar polos

Kategori memakai **daftar induk + subkategori** (list-group), bukan Datatables
dan bukan modal. Tombol aksi berupa tautan Edit dan form Hapus di setiap baris;
tombol "Tambah Kategori" menuju halaman form terpisah:

```blade
@extends('admin.layouts.admin')
@section('title', 'Manajemen Kategori — Seekitar')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card w-100 shadow-sm">
        <div class="card-body">
          <div class="d-md-flex align-items-center justify-content-between mb-4">
            <h4 class="card-title">Kategori & Subkategori</h4>
            @can('manage-categories')
              <a href="{{ route('admin.categories.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="ti ti-plus fs-4"></i> Tambah Kategori
              </a>
            @endcan
          </div>

          <div class="list-group list-group-flush border rounded-3 overflow-hidden">
            @forelse ($categories as $parent)
              <div class="list-group-item bg-light-subtle p-3 border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <h6 class="mb-0 fw-bold fs-4 text-dark">{{ $parent->name }}</h6>
                    <span class="fs-2 text-muted">Slug: <code>{{ $parent->slug }}</code></span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.categories.edit', $parent) }}" class="btn btn-sm btn-light-primary">Edit</a>
                    <form action="{{ route('admin.categories.destroy', $parent) }}" method="POST"
                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-light-danger" type="submit">Hapus</button>
                    </form>
                  </div>
                </div>

                @if ($parent->children->isNotEmpty())
                  <div class="ps-5 mt-3 border-start ms-4">
                    @foreach ($parent->children as $child)
                      <div class="d-flex align-items-center justify-content-between py-2">
                        <span>{{ $child->name }}</span>
                        <a href="{{ route('admin.categories.edit', $child) }}">Edit</a>
                      </div>
                    @endforeach
                  </div>
                @endif
              </div>
            @empty
              <p class="text-muted p-4 mb-0">Belum ada kategori.</p>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
```

### 22.2a Halaman Form (create/edit)

Create dan edit memakai **satu halaman form terpisah** (`admin.categories.form`),
bukan modal dan bukan AJAX. Controller mengisi `$parents` (hanya kategori
teratas), menyimpan/update lewat `Category::create()` / `->update()`, lalu
redirect + flash:

```php
// Controller (ringkas, lihat §10 untuk versi penuh)
public function create(): View
{
    return view('admin.categories.form', [
        'category' => new Category(),
        'parents'  => Category::whereNull('parent_id')->orderBy('name')->get(),
    ]);
}

public function store(Request $request): RedirectResponse
{
    Category::create($this->validated($request));

    return redirect()->route('admin.categories.index')->with('success', 'Kategori dibuat.');
}
```

> Tidak ada modal create/edit kategori: halaman form dipilih karena memuat
> pemilihan induk yang butuh validasi hierarki (§9.2), dan menampilkan error
> validasi di halaman penuh jauh lebih ramah daripada di dalam modal.
> `_method=PUT` tetap dipakai — form HTML hanya mengenal GET/POST.

### 22.3 Layout Admin (`layouts/admin.blade.php`)

```html
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <title>Seekitar Admin</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css"
    />
  </head>
  <body>
    <div class="d-flex">
      <!-- Sidebar -->
      <nav class="bg-dark text-white p-3" style="width:250px;">
        <h4>Seekitar</h4>
        <ul class="nav flex-column">
          @can('manage-categories')
          <li class="nav-item">
            <a
              href="{{ route('admin.categories.index') }}"
              class="nav-link text-white"
              >Kategori</a
            >
          </li>
          @endcan
          <!-- menu lain sesuai permission -->
        </ul>
      </nav>
      <!-- Main content -->
      <div class="flex-grow-1 p-4">@yield('content')</div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
  </body>
</html>
```
