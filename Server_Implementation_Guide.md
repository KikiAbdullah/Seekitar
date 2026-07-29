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
   - 6.1 Sanctum & Token
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
| Push Notification | Firebase Cloud Messaging               | —                   |
| WhatsApp          | Twilio / Kirim WA API                  | —                   |
| Admin UI          | Bootstrap 5.3.x, Yajra Datatables 13.x | `^13.0`             |
| Permission        | Spatie Laravel Permission 8.x          | `^8.0`              |
| API Auth          | Laravel Sanctum 4.x                    | `^4.0`              |
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

**Guard:** kita gunakan `sanctum` (api) dan `web` (admin). Di config/permission.php, pastikan guards mencakup keduanya.

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
│       └── CloseExpiredRequests.php   # Scheduler: tutup permintaan kadaluarsa
├── DataTables/                        # Server-side processing Yajra (admin)
│   ├── UsersDataTable.php
│   ├── StoresDataTable.php
│   ├── ListingsDataTable.php
│   ├── RequestsDataTable.php
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
│   ├── VerificationStatus.php
│   ├── VerificationLevel.php
│   ├── PaymentMethod.php
│   └── DisputeStatus.php
├── Events/
│   ├── CustomerRequestCreated.php
│   ├── OfferAccepted.php
│   ├── OrderStatusChanged.php
│   └── ReviewSubmitted.php
├── Exceptions/
│   └── InvalidOrderTransitionException.php
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── UserController.php
│   │   │   ├── StoreController.php
│   │   │   ├── ListingController.php
│   │   │   ├── CustomerRequestController.php
│   │   │   ├── OfferController.php
│   │   │   ├── OrderController.php
│   │   │   ├── ReviewController.php
│   │   │   ├── DisputeController.php
│   │   │   └── SettingController.php
│   │   └── Api/v1/...
│   ├── Middleware/
│   │   └── EnsureStoreOwner.php
│   ├── Requests/
│   │   ├── Admin/
│   │   │   ├── CategoryRequest.php
│   │   │   ├── UserRequest.php
│   │   │   ├── StoreRequest.php
│   │   │   ├── SettingRequest.php
│   │   │   └── ...
│   │   └── Api/
│   │       ├── CreateStoreRequest.php
│   │       ├── CreateListingRequest.php
│   │       ├── CreateCustomerRequestRequest.php
│   │       └── CreateOfferRequest.php
│   └── Resources/                     # API Resource (transformer JSON)
│       ├── StoreResource.php
│       ├── ListingResource.php
│       ├── OrderResource.php
│       └── OfferResource.php
├── Jobs/                              # Antrian Redis (asinkron)
│   ├── BroadcastRequestJob.php        # Sebar permintaan ke penyedia dalam radius
│   ├── SendPushNotificationJob.php
│   ├── SendWhatsAppOtpJob.php
│   └── RecalculateStoreRatingJob.php
├── Listeners/
│   ├── DispatchRequestBroadcast.php   # CustomerRequestCreated -> BroadcastRequestJob
│   ├── SendOfferAcceptedNotification.php
│   ├── SendOrderStatusNotification.php
│   └── UpdateStoreRatingOnReview.php
├── Models/
│   ├── User.php
│   ├── Store.php
│   ├── Category.php
│   ├── Listing.php
│   ├── CustomerRequest.php
│   ├── Offer.php
│   ├── Order.php
│   ├── Review.php
│   └── Dispute.php
├── Observers/                         # Side-effect otomatis pada model
│   ├── ReviewObserver.php             # Perbarui rating_avg & total_reviews
│   └── OrderObserver.php              # Catat completed_at saat status selesai
├── Policies/
│   ├── StorePolicy.php
│   ├── ListingPolicy.php
│   ├── OrderPolicy.php
│   └── OfferPolicy.php
├── Providers/
│   ├── AppServiceProvider.php
│   └── EventServiceProvider.php
└── Services/                          # Logika bisnis lintas controller
    ├── BroadcastService.php           # Pencocokan penyedia untuk sebuah permintaan
    ├── GeolocationService.php         # Query radius ST_Distance_Sphere
    ├── NotificationService.php        # Abstraksi FCM + WhatsApp
    ├── OrderStateMachine.php          # Validasi transisi status pesanan
    ├── OtpService.php                 # Generate, simpan (Redis), verifikasi OTP
    └── WhatsAppService.php            # Klien Twilio / Kirim WA

database/
├── factories/
│   ├── UserFactory.php
│   ├── StoreFactory.php
│   └── ListingFactory.php
├── migrations/
│   └── ...                            # Lihat DATABASE.md §10 untuk urutannya
└── seeders/
    ├── DatabaseSeeder.php
    ├── CategorySeeder.php             # 24 kategori, wajib saat deploy awal
    ├── RolesAndPermissionsSeeder.php   # Role & permission Spatie
    └── DummyDataSeeder.php            # Data contoh, hanya untuk development

routes/
├── api.php                            # Endpoint mobile (guard sanctum)
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
| `NotificationService` | Listener, job, controller admin                         | Satu pintu ke FCM & WhatsApp, memudahkan mock saat testing             |

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
| `VerificationStatus` | `pending`, `verified`, `rejected`                                            | `stores.verification_status`  |
| `PaymentMethod`      | `cod`, `transfer`                                                            | `orders.payment_method`       |
| `DisputeStatus`      | `open`, `resolved`                                                           | `disputes.status`             |
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
                    └─> dispatch SendPushNotificationJob per penyedia
```

Response ke pembeli langsung kembali setelah data tersimpan; pencarian penyedia
dan pengiriman notifikasi berjalan di worker. Konsekuensinya notifikasi
**tidak instan** — ini sudah dicatat juga di Mobile Guide.

```php
// app/Jobs/BroadcastRequestJob.php
namespace App\Jobs;

use App\Models\CustomerRequest;
use App\Services\BroadcastService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BroadcastRequestJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public string $customerRequestId) {}

    public function handle(BroadcastService $broadcast): void
    {
        $request = CustomerRequest::find($this->customerRequestId);

        // Permintaan bisa saja sudah ditutup sebelum job sempat jalan.
        if (! $request || $request->status !== \App\Enums\RequestStatus::Open) {
            return;
        }

        $broadcast->notifyMatchingStores($request);
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
        ]);

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
| `auth:sanctum` | `/api/*` | Bearer token |
| `role:admin` | web admin | Spatie — batasi ke admin |
| `permission:manage-users` | per-route admin | Spatie — izin granular |
| `store.owner` | API penjual | Memastikan pemanggil pemilik toko terkait |
| `profile.complete` | API | Menegakkan pengisian `users.location` |
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

### 6.1 Sanctum & Token (API)

Seekitar memakai Sanctum dalam **dua mode sekaligus**. Membedakan keduanya itu
penting, karena salah konfigurasi di sini adalah penyebab paling umum error
`419 CSRF token mismatch` di aplikasi mobile.

| Kanal                     | Mode          | Mekanisme                                  |
| :------------------------ | :------------ | :------------------------------------------ |
| **Mobile app** (`/api/*`) | **Stateless** | Bearer personal access token, tanpa cookie  |
| **Admin panel** (web)     | **Stateful**  | Session cookie Laravel biasa                |

**Instalasi:**

```bash
composer require laravel/sanctum:^4.0
php artisan install:api
```

**Konfigurasi `.env`:**

```env
# Domain yang boleh memakai autentikasi berbasis cookie (web admin).
# Mobile app TIDAK dimasukkan ke sini — ia memakai Bearer token.
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:8000,127.0.0.1,127.0.0.1:8000,admin.seekitar.id

SESSION_DOMAIN=.seekitar.id
SESSION_DRIVER=redis
```

> ⚠️ **Jangan** memasukkan `api.seekitar.id` ke `SANCTUM_STATEFUL_DOMAINS`.
> Jika dimasukkan, Sanctum memperlakukan request mobile sebagai stateful dan
> mulai menuntut CSRF token, sehingga request dari aplikasi gagal dengan 419.

**Middleware stateful hanya untuk web admin** (`bootstrap/app.php`):

```php
->withMiddleware(function (Middleware $middleware) {
    // Hanya grup 'web'/admin yang perlu cookie-based auth.
    $middleware->statefulApi();

    $middleware->alias([
        'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    ]);
})
```

**Membuat token dengan ability terbatas:**

```php
// Batasi cakupan token sesuai peran, jangan beri akses penuh.
$token = $user->createToken('mobile-app', ['user'])->plainTextToken;

// Untuk pemilik toko:
$token = $user->createToken('mobile-app', ['user', 'store-owner'])->plainTextToken;
```

Melindungi route dengan ability:

```php
Route::middleware(['auth:sanctum', 'ability:store-owner'])->group(function () {
    Route::post('/listings', [ListingController::class, 'store']);
});
```

**Guard untuk Spatie Permission** — karena ada dua kanal, `config/permission.php`
harus mengenali keduanya. Model `User` perlu tahu guard mana yang dipakai:

```php
// config/auth.php — pastikan kedua guard ada
'guards' => [
    'web'     => ['driver' => 'session', 'provider' => 'users'],
    'sanctum' => ['driver' => 'sanctum', 'provider' => 'users'],
],
```

### 6.2 Spatie Permission (Roles & Abilities)

**Role:**

- `super-admin` (akses semua, kelola admin)
- `admin` (akses panel admin kecuali manajemen admin lain)
- `user` (default pengguna biasa)

**Permissions (abilities) untuk Admin:**

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

Semua permission diberikan pada role `super-admin`. Role `admin` bisa diberikan sebagian (misal tidak bisa `manage-users` untuk mencegah hapus sesama admin).

**Cara assign (Seeder):** lihat bagian 19.

#### ⚠️ Guard: sumber kebingungan utama

Spatie menyimpan `guard_name` **di setiap baris** role dan permission. Sebuah
permission bermilik `guard_name = 'web'` **tidak terlihat** oleh pengguna yang
diautentikasi lewat guard `sanctum`, meskipun namanya sama persis. Ini penyebab
`can()` mendadak mengembalikan `false` di API padahal berfungsi di web admin.

Seekitar sengaja memakai pembagian berikut:

| Kanal | Guard | Otorisasi memakai |
| :-- | :-- | :-- |
| Web admin | `web` | **Spatie** role & permission (`role:admin\|super-admin`, lalu `permission:*` granular per route/blok) |
| API mobile | `sanctum` | Token `['*']` + **Policy** kepemilikan (Store/Offer/Order/dsb.) + middleware `permission:*` untuk endpoint admin API |

Artinya seluruh role/permission Spatie cukup dibuat untuk guard `web` saja —
persis seperti seeder di §19.2. Pengguna biasa di aplikasi mobile tidak
membutuhkan baris permission sama sekali; haknya ditentukan kepemilikan data
(lewat Policy) dan ability token.

```php
// config/permission.php
'models' => [
    'permission' => Spatie\Permission\Models\Permission::class,
    'role'       => Spatie\Permission\Models\Role::class,
],

// Guard default saat membuat role/permission tanpa menyebut guard.
'defaults' => ['guard' => 'web'],
```

> Endpoint admin API **memang** memakai `permission:*` di atas token
> Sanctum — dan itu bekerja karena model `User` tidak mendefinisikan
> `guard_name`, sehingga Spatie mengecek role/permission guard `web`
> (default) apa pun kanal autentikasinya. Yang perlu dicegah justru
> mengganti guard default ke `sanctum`: itu akan mematikan seluruh
> otorisasi web admin.

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
| `StorePolicy` | Hanya pemilik yang boleh `update`; admin boleh `deactivate` |
| `ListingPolicy` | Pemilik toko; admin boleh `delete` (konten bermasalah) |
| `OfferPolicy` | Pemilik toko boleh membuat; hanya pemilik request boleh `accept` |
| `OrderPolicy` | Pembeli **atau** pemilik toko terkait; transisi status dicek `OrderStateMachine` |
| `ReviewPolicy` | Hanya pihak pada pesanan `selesai` dan dalam jendela 7 hari |

---

## 7. ROUTING LENGKAP

### Admin Routes (`routes/admin.php`)

Route admin dipisah dari `web.php` (didaftarkan lewat `then:` di
`bootstrap/app.php`, lihat §5.1) supaya web publik SEO tidak ikut terbebani
middleware admin.

Karena prefix `admin` dan name `admin.` sudah disetel saat pendaftaran grup,
di dalam berkas ini **tidak perlu** mengulanginya.

```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // --- Endpoint AJAX Datatables ---------------------------------------
    // WAJIB didaftarkan SEBELUM Route::resource, kalau tidak 'data' akan
    // tertangkap sebagai {category} pada route show/edit.
    Route::get('categories/data', [CategoryController::class, 'data'])->name('categories.data');
    Route::get('users/data',      [UserController::class, 'data'])->name('users.data');
    Route::get('stores/data',     [StoreController::class, 'data'])->name('stores.data');
    Route::get('listings/data',   [ListingController::class, 'data'])->name('listings.data');
    Route::get('requests/data',   [CustomerRequestController::class, 'data'])->name('requests.data');
    Route::get('offers/data',     [OfferController::class, 'data'])->name('offers.data');
    Route::get('orders/data',     [OrderController::class, 'data'])->name('orders.data');
    Route::get('disputes/data',   [DisputeController::class, 'data'])->name('disputes.data');
    Route::get('reviews/data',    [ReviewController::class, 'data'])->name('reviews.data');

    // Data untuk grafik dashboard (§9.1)
    Route::get('dashboard/chart', [DashboardController::class, 'chartData'])->name('dashboard.chart');

    // Categories
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Users
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Verifikasi massal
    Route::post('verifications/users/{user}/approve', [VerificationController::class, 'approveUser'])->name('verify.user.approve');
    Route::post('verifications/users/{user}/reject', [VerificationController::class, 'rejectUser'])->name('verify.user.reject');
    Route::post('verifications/stores/{store}/approve', [VerificationController::class, 'approveStore'])->name('verify.store.approve');
    Route::post('verifications/stores/{store}/reject', [VerificationController::class, 'rejectStore'])->name('verify.store.reject');

    // Stores
    Route::resource('stores', StoreController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::patch('stores/{store}/deactivate', [StoreController::class, 'deactivate'])->name('stores.deactivate');

    // Blokir pengguna (mencabut semua token, lihat API §10.4)
    Route::patch('users/{user}/block',   [UserController::class, 'block'])->name('users.block');
    Route::patch('users/{user}/unblock', [UserController::class, 'unblock'])->name('users.unblock');

    // Listings
    Route::resource('listings', ListingController::class)->only(['index', 'show', 'destroy']);
    Route::patch('listings/{listing}/toggle-status', [ListingController::class, 'toggleStatus'])
        ->name('listings.toggle-status');

    // Customer Requests
    Route::resource('requests', CustomerRequestController::class)->only(['index', 'show']);
    Route::patch('requests/{request}/extend', [CustomerRequestController::class, 'extend'])
        ->name('requests.extend');

    // Offers
    Route::resource('offers', OfferController::class)->only(['index']);

    // Orders
    Route::resource('orders', OrderController::class)->only(['index', 'show']);

    // Disputes
    Route::get('disputes', [DisputeController::class, 'index'])->name('disputes.index');
    Route::get('disputes/{dispute}', [DisputeController::class, 'show'])->name('disputes.show');
    Route::patch('disputes/{dispute}/resolve', [DisputeController::class, 'resolve'])->name('disputes.resolve');

    // Reviews
    Route::resource('reviews', ReviewController::class)->only(['index', 'destroy']);

    // Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
});
```

### API Routes (`routes/api.php`)

Kontrak lengkapnya ada di [`API_DOCUMENTATION.md`](API_DOCUMENTATION.md).
Yang penting diperhatikan di sisi routing adalah **penempatan middleware**:

```php
Route::prefix('v1')->group(function () {

    // --- Publik --------------------------------------------------------
    Route::post('auth/request-otp', [AuthController::class, 'requestOtp'])
        ->middleware('throttle:otp');                     // per nomor, bukan IP
    Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:6,1');

    Route::get('stores/nearby',   [StoreController::class, 'nearby']);
    Route::get('stores/{store}',  [StoreController::class, 'show']);
    Route::get('listings',        [ListingController::class, 'index']);
    Route::get('listings/{listing}', [ListingController::class, 'show']);
    Route::get('categories',      [CategoryController::class, 'index']);

    // --- Perlu login ---------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout',     [AuthController::class, 'logout']);
        Route::get('auth/me',          [AuthController::class, 'me']);
        Route::patch('auth/profile',   [AuthController::class, 'updateProfile']);
        Route::post('auth/verification/ktp', [VerificationController::class, 'submitKtp']);
        Route::post('auth/fcm-token',  [DeviceController::class, 'store']);
        Route::delete('auth/fcm-token',[DeviceController::class, 'destroy']);

        // Butuh profil lengkap (nama + lokasi) sebelum bertransaksi.
        Route::middleware('profile.complete')->group(function () {
            Route::post('uploads/images', [UploadController::class, 'store']);

            Route::post('requests',              [CustomerRequestController::class, 'store']);
            Route::get('requests',               [CustomerRequestController::class, 'index']);
            Route::get('requests/mine',          [CustomerRequestController::class, 'mine']);
            Route::post('requests/{request}/extend', [CustomerRequestController::class, 'extend']);

            Route::post('orders',                [OrderController::class, 'store']);
            Route::patch('orders/{order}/status',[OrderController::class, 'updateStatus']);
            Route::post('orders/{order}/review', [ReviewController::class, 'store']);
            Route::post('orders/{order}/disputes', [DisputeController::class, 'store']);

            Route::post('listings/{listing}/favorite',   [FavoriteController::class, 'store']);
            Route::delete('listings/{listing}/favorite', [FavoriteController::class, 'destroy']);
            Route::get('favorites',                      [FavoriteController::class, 'index']);

            // --- Khusus pemilik toko (ability token) --------------------
            Route::middleware('ability:store-owner')->group(function () {
                Route::post('stores',   [StoreController::class, 'store']);
                Route::apiResource('listings', ListingController::class)
                    ->only(['store', 'update', 'destroy']);
                Route::post('requests/{request}/offers', [OfferController::class, 'store'])
                    ->middleware('throttle:offers');
            });
        });
    });
});
```

> ⚠️ **Urutan `requests/mine` sebelum `requests/{request}`.** Jika terbalik,
> kata `mine` akan ditangkap sebagai `{request}` dan menghasilkan 404. Masalah
> yang sama berlaku untuk seluruh route `*/data` di admin.

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

- Tabel Yajra Datatables: kolom: Nama, Slug, Induk, Ikon, Aksi.
- Tombol “Tambah Kategori” (modal atau halaman create).
- Aksi: Edit (modal), Hapus (konfirmasi delete, hanya jika tidak ada anak/request terkait).

**Create/Edit Modal:**

- Nama (text)
- Slug (text, auto-generated dari nama)
- Induk (select dari kategori existing, nullable)
- Ikon (text, nama icon FontAwesome)
- Urutan (number, default 0)

#### ⚠️ Mencegah loop hierarki

`not_in:{id}` saja **tidak cukup**. Aturan itu hanya mencegah kategori menjadi
induk dirinya sendiri (A → A), tapi tidak mencegah siklus tak langsung:

```
A (induk B)  →  jadikan induknya D
B (induk C)
C (induk D)
D            →  A jadi keturunan D, sekaligus D jadi keturunan A
```

Hasilnya cabang A–B–C–D terlepas dari pohon dan **menghilang** dari semua
query rekursif — tanpa error apa pun. Karena itu perlu validasi keturunan:

```php
// app/Rules/NotADescendant.php
class NotADescendant implements ValidationRule
{
    public function __construct(private ?int $categoryId) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->categoryId || ! $value) {
            return;                       // kategori baru tidak mungkin punya anak
        }

        if ((int) $value === $this->categoryId) {
            $fail('Kategori tidak boleh menjadi induk dirinya sendiri.');
            return;
        }

        // Telusuri ke atas dari calon induk; jika bertemu diri sendiri, itu siklus.
        $ancestorId = $value;
        $guard = 0;

        while ($ancestorId && $guard++ < 10) {
            if ((int) $ancestorId === $this->categoryId) {
                $fail('Kategori tidak boleh dipindahkan ke dalam turunannya sendiri.');
                return;
            }
            $ancestorId = Category::whereKey($ancestorId)->value('parent_id');
        }
    }
}
```

`$guard` membatasi penelusuran agar tidak berputar selamanya seandainya sudah
terlanjur ada siklus di data lama.

> Hierarki kategori dibatasi **2 level** (`DATABASE.md` §3). Tambahkan juga
> validasi bahwa calon induk adalah kategori teratas:
> ```php
> 'parent_id' => [... , Rule::exists('categories', 'id')->whereNull('parent_id')],
> ```

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

- Antrian: toko `verification_status = 'pending'`, terlama dulu; pemilik
  ikut dimuat (`owner:id,name,phone,verified_at`) — kelayakan pemilik
  adalah bagian dari penilaian.
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

**Toko — dua syarat diperiksa ulang SERVER di titik persetujuan:**

```php
$hasil = DB::transaction(function () use ($store, $request): string {
    $toko = Store::lockForUpdate()->findOrFail($store->id);

    if ($toko->verification_status !== VerificationStatus::Pending) {
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

    $toko->verification_status = VerificationStatus::Verified;
    $toko->rejected_reason     = null;
    $toko->verified_at         = now();
    $toko->verified_by         = $request->user()->id;
    $toko->save();

    return 'disetujui';
});
```

**Toko — penolakan juga hanya sah dari antrian** (menolak toko yang sudah
disetujui akan menyisakan stempel `verified_*` pada status `rejected` —
keadaan kontradiktif tanpa makna alur):

```php
$toko->verification_status = VerificationStatus::Rejected;
$toko->rejected_reason     = $request->reason();   // wajib: min 10, maks 500
$toko->save();
```

`reason` wajib diisi saat menolak (`RejectVerificationRequest`:
`'reason' => 'required|string|min:10|max:500'`) — penolakan tanpa alasan
membuat pengguna mengajukan ulang berkas yang sama.

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
- Aksi: Lihat Detail, Edit, Nonaktifkan (soft delete).

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
- Aksi: Lihat, **Aktifkan/Sembunyikan**, Hapus (soft delete).

Menyembunyikan listing lebih proporsional daripada menghapusnya — konten yang
melanggar bisa ditinjau ulang, dan penjual tidak kehilangan datanya:

```php
public function toggleStatus(Listing $listing): RedirectResponse
{
    $this->authorize('manage-listings');

    // 'sold' diatur penjual, bukan admin — admin hanya menyembunyikan/menampilkan.
    if ($listing->status === ListingStatus::Sold) {
        return back()->withErrors('Listing berstatus terjual tidak dapat diubah admin.');
    }

    $listing->update([
        'status' => $listing->status === ListingStatus::Active
            ? ListingStatus::Hidden
            : ListingStatus::Active,
    ]);

    return back()->with('success', 'Status listing diperbarui.');
}
```

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
- Tombol “Selesaikan” → isi catatan, pilih keputusan, kirim notifikasi ke **kedua** pihak.

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
public function resolve(ResolveDisputeRequest $request, Dispute $dispute): RedirectResponse
{
    $this->authorize('manage-disputes');

    DB::transaction(function () use ($request, $dispute) {
        $order    = $dispute->order()->lockForUpdate()->first();
        $decision = $request->validated('decision');   // 'selesai' | 'dibatalkan'

        $dispute->update([
            'status'          => DisputeStatus::Resolved,
            'resolution_note' => $request->validated('resolution_note'),
            'resolved_at'     => now(),
        ]);

        $order->update(array_filter([
            'status'        => OrderStatus::from($decision),
            'completed_at'  => $decision === 'selesai'    ? now() : null,
            'cancelled_at'  => $decision === 'dibatalkan' ? now() : null,
            'cancelled_by'  => $decision === 'dibatalkan' ? auth()->id() : null,
            'cancel_reason' => $decision === 'dibatalkan' ? 'Dibatalkan admin melalui dispute' : null,
        ], fn ($v) => $v !== null));

        // Kedua pihak diberi tahu, bukan hanya pelapor.
        Notification::send(
            [$order->buyer, $order->store->owner],
            new DisputeResolved($dispute, $decision)
        );
    });

    return back()->with('success', 'Dispute diselesaikan.');
}
```

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

Struktur lengkap `CategoryController` — perhatikan bahwa **setiap** aksi
tulis memakai FormRequest, dan `index()` mengirim `$dataTable` ke view:

```php
class CategoryController extends Controller
{
    public function __construct(private CategoryService $categories)
    {
        // Satu baris ini menggantikan authorize() berulang di tiap method.
        $this->middleware('permission:manage-categories');
    }

    public function index(CategoriesDataTable $dataTable)
    {
        // Blade memanggil $dataTable->table() & ->scripts(), jadi objeknya
        // WAJIB dikirim — tanpa ini view melempar "Undefined variable".
        return $dataTable->render('admin.categories.index');
    }

    public function create()
    {
        return view('admin.categories.form', [
            'category' => new Category(),
            'parents'  => Category::whereNull('parent_id')->orderBy('name')->get(),
        ]);
    }

    public function edit(Category $category)
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

    public function store(CategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->validated());

        return to_route('admin.categories.index')
            ->with('success', 'Kategori ditambahkan.');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, $request->validated());

        return to_route('admin.categories.index')
            ->with('success', 'Kategori diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // Penghapusan punya banyak prasyarat (anak, request, JSON store) —
        // logikanya di service, bukan di controller.
        $this->categories->delete($category);

        return back()->with('success', 'Kategori dihapus.');
    }
}
```

#### `create()`/`edit()` — kapan diperlukan?

Keduanya **hanya** dibutuhkan jika memakai halaman terpisah. Bila form berupa
modal (seperti §22.2), keduanya tidak perlu dan route-nya dipangkas:

```php
Route::resource('categories', CategoryController::class)
    ->except(['show', 'create', 'edit']);
```

Untuk kategori, halaman terpisah lebih disarankan — form-nya memuat pemilihan
induk yang butuh validasi hierarki (§9.2), dan menampilkan error validasi di
dalam modal jauh lebih merepotkan.

#### Aturan FormRequest

**Semua** aksi tulis (`store`, `update`, dan aksi kustom seperti `resolve`,
`reject`, `extend`) wajib memakai FormRequest — jangan pernah memvalidasi
dengan `$request->validate()` di dalam controller.

| Controller | FormRequest |
| :-- | :-- |
| `CategoryController@store/update` | `CategoryRequest` |
| `UserController@update` | `UserRequest` |
| `UserController@block` | `BlockUserRequest` |
| `StoreController@update` | `StoreRequest` |
| `VerificationController@rejectUser/rejectStore` | `RejectVerificationRequest` |
| `DisputeController@resolve` | `ResolveDisputeRequest` |
| `SettingController@update` | `SettingRequest` |

Alasannya: aturan validasi jadi bisa diuji terpisah, otorisasi tambahan bisa
ditaruh di `authorize()`, dan pesan error terkumpul di satu tempat.

---

## 11. FORM REQUESTS & VALIDASI (ADMIN)

`CategoryRequest`:

```php
class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-categories');
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name' => ['required', 'string', 'min:3', 'max:50'],

            'slug' => [
                'required', 'alpha_dash', 'max:50',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],

            'parent_id' => [
                'nullable',
                // Hanya kategori level teratas yang boleh jadi induk (maks 2 level).
                Rule::exists('categories', 'id')->whereNull('parent_id'),
                new NotADescendant($categoryId),   // cegah siklus, lihat §9.2
            ],

            'icon'       => ['nullable', 'string', 'max:50', new ValidFontAwesomeIcon()],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Slug dibuat otomatis jika kosong, supaya admin tidak perlu mengetiknya.
        $this->merge([
            'slug' => $this->slug ?: Str::slug($this->name ?? ''),
        ]);
    }
}
```

> `Rule::unique(...)->ignore()` menggantikan penggabungan string
> `'unique:categories,slug,'.$id`. Bentuk string akan rusak jika `$id` bernilai
> `null` (menghasilkan `unique:categories,slug,`) — yang justru terjadi pada
> aksi *create*.

#### Validasi ikon FontAwesome

Daftar kelas FontAwesome ada ribuan, jadi `in:` tidak praktis. Yang dibutuhkan
adalah pembatasan **format** plus daftar putih yang bisa dirawat:

```php
class ValidFontAwesomeIcon implements ValidationRule
{
    /** Ikon yang disediakan untuk kategori Seekitar. */
    private const ALLOWED = [
        'fa-utensils', 'fa-store', 'fa-screwdriver-wrench', 'fa-bolt',
        'fa-truck', 'fa-house', 'fa-shirt', 'fa-mobile-screen',
        'fa-motorcycle', 'fa-leaf', 'fa-graduation-cap', 'fa-scissors',
        'fa-paint-roller', 'fa-camera', 'fa-heart-pulse', 'fa-basket-shopping',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^fa-[a-z0-9-]+$/', (string) $value)) {
            $fail('Format ikon tidak valid. Contoh: fa-store');
            return;
        }

        if (! in_array($value, self::ALLOWED, true)) {
            $fail('Ikon tidak tersedia. Pilih dari daftar ikon yang disediakan.');
        }
    }
}
```

Di form admin, tampilkan sebagai **grid ikon yang bisa diklik**, bukan input
teks bebas — admin tidak perlu menghafal nama kelas, dan validasi di atas
menjadi jaring pengaman terakhir saja.

---

## 12. MODELS & RELATIONSHIPS (Tambah Spatie)

User model sudah mencakup `HasRoles`.  
Tambahkan accessor: `getRoleNamesAttribute()` atau langsung gunakan `$user->roles`.

---

## 13. OBSERVERS & EVENTS

Pemetaan event → listener didaftarkan di `EventServiceProvider`. Semua listener
yang memicu notifikasi berjalan lewat antrian.

| Event                    | Listener                          | Efek                                                        |
| :----------------------- | :-------------------------------- | :----------------------------------------------------------- |
| `CustomerRequestCreated` | `DispatchRequestBroadcast`        | Dispatch `BroadcastRequestJob` ke antrian                   |
| `OfferAccepted`          | `SendOfferAcceptedNotification`   | Notifikasi penyedia pemenang + tolak offer lain             |
| `OrderStatusChanged`     | `SendOrderStatusNotification`     | Notifikasi pihak terkait sesuai status baru                 |
| `ReviewSubmitted`        | `UpdateStoreRatingOnReview`       | Dispatch `RecalculateStoreRatingJob`                        |

**Observers** dipakai untuk side-effect yang selalu terjadi apa pun jalur
masuknya (API, admin panel, atau seeder):

| Observer           | Hook                 | Efek                                                          |
| :----------------- | :------------------- | :-------------------------------------------------------------- |
| `ReviewObserver`   | `created`, `deleted` | Hitung ulang `rating_avg` & `total_reviews` (hanya `buyer_to_store`) |
| `OrderObserver`    | `creating`           | Buat `order_number` (`SKT-YYYYMMDD-NNNN`)                      |
| `OrderObserver`    | `updating`           | Isi `completed_at` / `cancelled_at`; blokir transisi tidak sah |
| `StoreObserver`    | `deleted`, `restored`| Soft delete listing terkait; pulihkan saat restore             |
| `ListingObserver`  | `saving`             | Normalkan `images` (buang duplikat, batasi 5)                  |
| `CustomerRequestObserver` | `created`     | Set `expires_at` dari `SettingService`                         |

**`OrderObserver` — nomor pesanan & penjaga transisi:**

```php
class OrderObserver
{
    public function __construct(private OrderStateMachine $states) {}

    public function creating(Order $order): void
    {
        $order->order_number ??= $this->generateNumber();
    }

    public function updating(Order $order): void
    {
        if (! $order->isDirty('status')) {
            return;
        }

        // Jaring pengaman terakhir: transisi tidak sah ditolak walau
        // datangnya dari seeder, tinker, atau admin panel.
        $this->states->assertCanTransition(
            $order->getOriginal('status'),
            $order->status,
        );

        match ($order->status) {
            OrderStatus::Selesai    => $order->completed_at ??= now(),
            OrderStatus::Dibatalkan => $order->cancelled_at ??= now(),
            default                 => null,
        };
    }

    private function generateNumber(): string
    {
        $date = now()->format('Ymd');
        $seq  = Redis::incr("order_seq:$date");
        Redis::expire("order_seq:$date", 172800);

        return sprintf('SKT-%s-%04d', $date, $seq);
    }
}
```

**`ReviewObserver` — perhatikan filter arah:**

```php
public function created(Review $review): void
{
    // Penilaian penjual->pembeli TIDAK memengaruhi rating toko.
    if ($review->direction !== ReviewDirection::BuyerToStore) {
        return;
    }

    RecalculateStoreRatingJob::dispatch($review->store_id);
}

public function deleted(Review $review): void
{
    // Admin menghapus ulasan bermasalah -> rating harus dihitung ulang.
    if ($review->direction === ReviewDirection::BuyerToStore) {
        RecalculateStoreRatingJob::dispatch($review->store_id);
    }
}
```

**Registrasi** (Laravel 11+ memakai atribut, bukan `EventServiceProvider`):

```php
#[ObservedBy([OrderObserver::class])]
class Order extends Model { /* ... */ }
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
| `SendPushNotificationJob`    | `high`    | Satu pesan FCM ke satu perangkat                        |
| `SendWhatsAppOtpJob`         | `high`    | Kirim OTP via Twilio / Kirim WA                         |
| `RecalculateStoreRatingJob`  | `default` | Hitung ulang `rating_avg` dari seluruh ulasan toko      |

### 14.1 `BroadcastRequestJob` — implementasi lengkap

Inti mesin kedua Seekitar: menyebar permintaan pembeli ke penyedia yang relevan.

```php
class BroadcastRequestJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 120;

    public function __construct(public string $customerRequestId) {}

    public function handle(BroadcastService $broadcast): void
    {
        $request = CustomerRequest::with('category')->find($this->customerRequestId);

        // Permintaan bisa sudah ditutup/kedaluwarsa sebelum job dieksekusi.
        if (! $request || $request->status !== RequestStatus::Open) {
            Log::info('Broadcast dilewati', ['request' => $this->customerRequestId]);
            return;
        }

        $stores = $broadcast->matchingStores($request);

        if ($stores->isEmpty()) {
            Log::warning('Tidak ada penyedia cocok', [
                'request'   => $request->id,
                'category'  => $request->category_id,
                'radius_km' => $request->radius_km,
            ]);
            return;
        }

        // Satu job kecil per penerima: satu FCM gagal tidak menggagalkan sisanya.
        $stores->each(fn (Store $store) =>
            SendPushNotificationJob::dispatch(
                storeId: $store->id,
                notification: new RequestBroadcastNotification($request),
            )
        );

        Log::info('Permintaan disebar', [
            'request'    => $request->id,
            'recipients' => $stores->count(),
        ]);
    }

    /** Dipanggil setelah percobaan terakhir gagal. */
    public function failed(?Throwable $e): void
    {
        Log::error('BroadcastRequestJob gagal total', [
            'request' => $this->customerRequestId,
            'error'   => $e?->getMessage(),
        ]);
    }
}
```

**`BroadcastService::matchingStores()`** — kriteria pencocokan dari PRD §5.2.2:

```php
public function matchingStores(CustomerRequest $request): Collection
{
    [$lng, $lat] = [$request->longitude, $request->latitude];

    return Store::query()
        ->where('is_active', true)
        ->where('verification_status', VerificationStatus::Verified)
        // Toko tidak boleh menawar pada permintaannya sendiri.
        ->where('user_id', '!=', $request->user_id)
        // Kategori toko memuat kategori permintaan (JSON, bukan FK).
        ->whereRaw('JSON_CONTAINS(category_ids, ?)', [(string) $request->category_id])
        // Dua arah: toko dalam radius pembeli, DAN pembeli dalam radius layanan toko.
        ->nearby($lat, $lng, $request->radius_km)
        ->whereRaw(
            'ST_Distance_Sphere(location, ST_GeomFromText(?, 4326, ?)) <= service_radius_km * 1000',
            ["POINT($lng $lat)", 'axis-order=long-lat']
        )
        // Prioritas: rating tertinggi, lalu terdekat.
        ->orderByDesc('rating_avg')
        ->orderBy('distance_km')
        ->limit(50)          // batasi ledakan notifikasi
        ->get();
}
```

> ⚠️ Pencocokan sengaja **dua arah**. Toko kelontong beradius 5 km tidak akan
> menerima permintaan dari pembeli 12 km jauhnya, meski pembeli menyetel radius
> 15 km. Tanpa syarat kedua, penyedia dibanjiri permintaan di luar jangkauan.

**Scheduler** (`routes/console.php`):

```php
use Illuminate\Support\Facades\Schedule;

// Menutup permintaan & penawaran kedaluwarsa.
Schedule::command('requests:close-expired')
    ->everyFifteenMinutes()
    ->withoutOverlapping()          // cegah tumpang tindih jika eksekusi lambat
    ->onOneServer();                // aman saat multi-server

// Membersihkan berkas unggahan sementara yang tidak jadi dipakai.
Schedule::command('uploads:prune')->hourly();

// Menghitung ulang rating (jaring pengaman bila ada observer terlewat).
Schedule::command('stores:recalculate-ratings')->dailyAt('03:00');
```

Perintah ini memakai indeks `cr_status_expires_idx` seperti dijelaskan di
`DATABASE.md` §11.

---

## 15. NOTIFIKASI (PUSH & WHATSAPP)

### 15.1 Push Notification (FCM)

Firebase menghentikan API legacy, jadi pakai **HTTP v1** yang berbasis service
account:

```bash
composer require kreait/laravel-firebase
```

```env
FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json
FIREBASE_PROJECT_ID=seekitar-prod
```

Notification memakai channel kustom agar `toFcm()` bisa dites terpisah:

```php
class RequestBroadcastNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CustomerRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['fcm', 'database'];
    }

    public function toFcm(object $notifiable): CloudMessage
    {
        return CloudMessage::new()
            ->withNotification([
                'title' => 'Ada kebutuhan baru di sekitar Anda',
                'body'  => Str::limit($this->request->title, 80),
            ])
            // data payload dipakai app untuk deep-link (Mobile Guide §12)
            ->withData([
                'screen'    => 'request_detail',
                'entity_id' => $this->request->id,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return ['request_id' => $this->request->id, 'title' => $this->request->title];
    }
}
```

#### Mengirim ke Semua Perangkat Pengguna

Satu pengguna bisa punya beberapa perangkat (`DATABASE.md` §4.9a). Notifikasi
harus sampai ke semuanya, karena tidak ada cara mengetahui perangkat mana yang
sedang dipegang.

```php
class SendPushNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        public string $userId,
        public array $payload,          // title, body, data
    ) {}

    public function handle(Messaging $messaging): void
    {
        $devices = UserDevice::where('user_id', $this->userId)->get();

        if ($devices->isEmpty()) {
            Log::info('Tidak ada perangkat terdaftar', ['user' => $this->userId]);
            return;
        }

        $message = CloudMessage::new()
            ->withNotification($this->payload['notification'])
            ->withData($this->payload['data']);

        // sendMulticast: satu panggilan API untuk semua token, bukan satu per token.
        $report = $messaging->sendMulticast(
            $message,
            $devices->pluck('fcm_token')->all()
        );

        // Token yang ditolak WAJIB dihapus. Jika diabaikan, antrian terus
        // mencoba mengirim ke perangkat yang aplikasinya sudah dihapus.
        foreach ($report->invalidTokens() as $token) {
            UserDevice::where('fcm_token', $token)->delete();
        }
        foreach ($report->unknownTokens() as $token) {
            UserDevice::where('fcm_token', $token)->delete();
        }

        $this->logDelivery($report, $devices->count());
    }
}
```

> ⚠️ **Jangan mengulang seluruh job saat sebagian token gagal.** `sendMulticast`
> mengembalikan laporan per-token; token tidak valid adalah kondisi permanen,
> bukan galat sementara. Mengulang job hanya akan mengirim ulang ke perangkat
> yang sudah berhasil menerima.
>
> Batas `sendMulticast` adalah **500 token per panggilan**. Untuk broadcast ke
> banyak penyedia, `BroadcastRequestJob` sudah memecahnya menjadi satu job per
> toko (§14.1), jadi batas ini tidak akan tersentuh.

#### Melacak Keberhasilan Pengiriman

Firebase melaporkan apakah pesan **diterima server FCM**, bukan apakah
**dibaca pengguna**. Keduanya sering tertukar.

| Yang bisa diukur | Sumbernya |
| :-- | :-- |
| Terkirim ke FCM | `$report->successes()->count()` |
| Token tidak valid | `$report->invalidTokens()` |
| Notifikasi **dibuka** | Event `notification_opened` dari aplikasi (Mobile Guide §22) |

```php
private function logDelivery(MulticastSendReport $report, int $total): void
{
    Log::channel('notifications')->info('Pengiriman push', [
        'user'    => $this->userId,
        'type'    => $this->payload['data']['type'] ?? null,
        'devices' => $total,
        'sukses'  => $report->successes()->count(),
        'gagal'   => $report->failures()->count(),
    ]);
}
```

> ⚠️ **Tingkat keterbacaan sesungguhnya hanya bisa diukur dari sisi aplikasi.**
> Server tidak akan pernah tahu apakah notifikasi ditampilkan — pengguna bisa
> mematikan izin notifikasi, atau OS menundanya demi hemat baterai. Karena itu
> event `notification_opened` dikirim aplikasi ke Firebase Analytics, lalu
> dibandingkan dengan jumlah `sukses` di log ini.
>
> Jangan menyimpan log pengiriman di tabel database: volumenya besar dan
> nilainya rendah. Cukup log terstruktur yang dibersihkan berkala.

### 15.2 WhatsApp OTP

Tidak ada paket resmi Laravel untuk provider lokal, jadi bungkus HTTP client
sendiri di balik satu interface — supaya provider bisa diganti tanpa menyentuh
kode pemanggil.

```php
interface WhatsAppGateway
{
    public function sendOtp(string $phone, string $code): void;
}
```

| Provider | Cocok untuk | Catatan |
| :-- | :-- | :-- |
| Twilio (`twilio/sdk`) | Produksi lintas negara | Perlu template WhatsApp Business terdaftar |
| Kirim WA / Wablas | Pasar Indonesia, biaya lebih murah | REST sederhana, cukup `Http::post()` |
| `LogWhatsAppGateway` | Development | OTP ditulis ke `storage/logs` |

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

Binding di `AppServiceProvider` — di lokal, OTP tidak benar-benar dikirim:

```php
$this->app->bind(WhatsAppGateway::class, fn () =>
    app()->isProduction() ? new KirimWaGateway() : new LogWhatsAppGateway()
);
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

Dipakai di `Store` dan `CustomerRequest` (keduanya punya kolom `location`):

```php
class Store extends Model
{
    use HasLocation, SoftDeletes;
}

// Pemakaian di controller:
$stores = Store::query()
    ->withDistance($lat, $lng)
    ->nearby($lat, $lng, $radiusKm)
    ->where('is_active', true)
    ->orderByDistance()
    ->paginate($perPage);
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
> dengan `EXPLAIN`: kolom `key` harus berisi `stores_location_spatial`.

**Memastikan indeks terpakai:**

```php
// Jalankan sekali di tinker/test setelah data uji dimuat.
DB::enableQueryLog();
Store::nearby(-7.2575, 112.7521, 5)->get();
$sql = DB::getQueryLog()[0]['query'];

dd(DB::select("EXPLAIN $sql", DB::getQueryLog()[0]['bindings']));
// key => 'stores_location_spatial'  ✅
// key => null                        ❌ indeks tidak terpakai
```

**Menyimpan koordinat** (kolom POINT tidak bisa diisi string biasa):

```php
$store->location = DB::raw("ST_GeomFromText('POINT($lng $lat)', 4326, 'axis-order=long-lat')");
```

### 16.1 Reverse Geocoding (Koordinat → Alamat)

Koordinat dipakai untuk query; **alamat teks** dipakai untuk ditampilkan.
Keduanya disimpan (`stores.address`, `users.address`) — bukan dihitung ulang
tiap kali, karena panggilan geocoding berbayar dan lambat.

Reverse geocoding juga menghasilkan `regency_code` yang dipakai geofencing
kabupaten (`DATABASE.md` §4.2).

```php
class GeocodingService
{
    public function __construct(private CacheRepository $cache) {}

    /** Koordinat -> alamat + kode wilayah. Null jika layanan gagal. */
    public function reverse(float $lat, float $lng): ?ResolvedAddress
    {
        // Bulatkan ke ~11 meter: dua pin berdekatan berbagi hasil cache,
        // dan kuota API tidak habis untuk titik yang praktis sama.
        $key = sprintf('geocode:%.4F,%.4F', $lat, $lng);

        return $this->cache->remember($key, now()->addDays(30), function () use ($lat, $lng) {
            $res = Http::timeout(5)->retry(2, 300)->get(
                'https://maps.googleapis.com/maps/api/geocode/json',
                [
                    'latlng'      => "$lat,$lng",
                    'key'         => config('services.google_maps.key'),
                    'language'    => 'id',
                    'result_type' => 'street_address|administrative_area_level_2',
                ]
            );

            if ($res->failed() || ($res->json('status') !== 'OK')) {
                Log::warning('Reverse geocoding gagal', ['status' => $res->json('status')]);
                return null;
            }

            $first = $res->json('results.0');

            return new ResolvedAddress(
                address: $first['formatted_address'],
                regency: $this->component($first, 'administrative_area_level_2'),
            );
        });
    }

    private function component(array $result, string $type): ?string
    {
        return collect($result['address_components'] ?? [])
            ->firstWhere(fn ($c) => in_array($type, $c['types'], true))['long_name'] ?? null;
    }
}
```

> ⚠️ **Kegagalan geocoding tidak boleh menggagalkan pembuatan toko.** Layanan
> pihak ketiga bisa mati atau kuotanya habis; koordinat sudah cukup untuk
> seluruh fungsi pencarian. Simpan `address` sebagai `null` dan isi belakangan
> lewat job, alih-alih menolak permintaan pengguna:
>
> ```php
> $store = Store::create([...]);              // koordinat sudah cukup
> ResolveStoreAddressJob::dispatch($store->id)->afterCommit();
> ```
>
> **Cache wajib.** Google Maps Geocoding ditagih per panggilan, dan pemilih
> lokasi di aplikasi memanggilnya setiap kali peta berhenti digeser (Mobile
> Guide §12.1 sudah men-*debounce*-nya di sisi klien). Pembulatan 4 desimal
> membuat pergeseran kecil memakai hasil yang sama.
>
> Alternatif gratis: **Nominatim (OpenStreetMap)**. Kualitas datanya untuk
> kabupaten di Indonesia lebih bervariasi, dan kebijakan pemakaiannya membatasi
> 1 request/detik — cukup untuk pengembangan, berisiko untuk produksi.

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

Admin panel memakai redirect + flash message, bukan JSON. Seragamkan dengan
helper kecil supaya nama kunci flash tidak berbeda-beda antar controller:

```php
trait WebResponse
{
    protected function redirectSuccess(string $route, string $message, array $params = []): RedirectResponse
    {
        return to_route($route, $params)->with('success', $message);
    }

    protected function backError(string $message): RedirectResponse
    {
        return back()->withInput()->with('error', $message);
    }
}
```

Layout admin menampilkannya di satu tempat:

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
| Berkas KTP & selfie | **Disk `local` (privat)** — di produksi boleh diganti bucket S3 dengan SSE-KMS | Tidak bisa diakses lewat URL tebakan; enkripsi at-rest mengikuti penyedia penyimpanan |
| NIK | **cast `encrypted`** di kolom DB | Teks pendek; hasil 216 byte, muat di `VARCHAR(255)` |
| Akses berkas | Route berizin yang **mengalirkan berkas lewat PHP** | Tidak ada URL publik/pre-signed yang bocor; otorisasi `verify-users` dicek setiap akses |

```php
// Unggah ke disk privat — simpan PATH, bukan URL.
$user->ktp_image    = $request->file('ktp_image')->store("ktp/{$user->id}", 'local');
$user->selfie_image = $request->file('selfie_image')->store("ktp/{$user->id}", 'local');
$user->ktp_submitted_at = now();
```

```php
// config/filesystems.php
's3-private' => [
    'driver' => 's3',
    'bucket' => env('AWS_BUCKET_PRIVATE'),
    'options' => [
        'ServerSideEncryption' => 'aws:kms',
        'SSEKMSKeyId'          => env('AWS_KMS_KEY_ID'),
    ],
    'visibility' => 'private',
],
```

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
- Aktifkan Content-Security-Policy sebagai lapis kedua:

```php
// app/Http/Middleware/SecurityHeaders.php
$response->headers->add([
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options'        => 'DENY',
    'Referrer-Policy'        => 'strict-origin-when-cross-origin',
    'Content-Security-Policy' => "default-src 'self'; img-src 'self' https://cdn.seekitar.id data:; script-src 'self' https://cdn.jsdelivr.net https://code.jquery.com https://cdn.datatables.net",
]);
```

> CSP di atas mengizinkan CDN yang dipakai layout admin (§22.3). Bila kelak
> aset di-bundel sendiri, persempit menjadi `'self'` saja.

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
// routes/admin.php
Route::post('login', [AdminLoginController::class, 'store'])
    ->middleware(['guest', 'throttle:admin-login']);
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

```bash
composer require propaganistas/laravel-phone:^6.0
```

Paket ini mendukung `illuminate/support: ^11.0|^12.0|^13.0`, jadi kompatibel
dengan Laravel 13.

```php
// FormRequest
public function rules(): array
{
    return [
        'phone' => ['required', 'phone:ID', 'max:15'],
    ];
}

// Normalisasi SEBELUM validasi & penyimpanan — ini yang mencegah akun ganda.
protected function prepareForValidation(): void
{
    $this->merge(['phone' => $this->normalizePhone($this->phone)]);
}

private function normalizePhone(?string $input): ?string
{
    if (! $input) return null;

    $digits = preg_replace('/\D/', '', $input);

    return match (true) {
        str_starts_with($digits, '0')  => '62'.substr($digits, 1),
        str_starts_with($digits, '62') => $digits,
        default                        => '62'.$digits,
    };
}
```

| Masukan pengguna | Tersimpan |
| :-- | :-- |
| `08123456789` | `628123456789` |
| `+62 812-3456-789` | `628123456789` |
| `628123456789` | `628123456789` |

> ⚠️ Normalisasi harus dilakukan di **satu tempat** (FormRequest), bukan di
> tiap controller. Jika satu jalur masuk lupa menormalkan, `UNIQUE` pada
> `users.phone` tidak akan menangkap duplikatnya — dan OTP terkirim ke nomor
> yang sama untuk dua akun berbeda.
>
> Logika normalisasi yang sama diterapkan di sisi klien
> (`Mobile_Implementation_Guide.md` §7.1) agar pengguna melihat format yang
> konsisten, tetapi **server tetap menormalkan ulang** — masukan dari klien
> tidak pernah dipercaya.

### 18A.7 Ringkasan Pemetaan ke PRD §11

| Janji di PRD | Implementasi |
| :-- | :-- |
| KTP & selfie disimpan privat | §18A.3 — disk `local` privat (S3 SSE-KMS di produksi) + cast `encrypted` untuk NIK |
| Koordinat tidak ditampilkan mentah | Lokasi pembeli dibulatkan (PRD §5.2.3) |
| Nomor telepon bertahap | Disaring di API Resource, bukan di klien |
| HTTPS/TLS 1.3 | Konfigurasi server + `SESSION_SECURE_COOKIE=true` |
| Validasi input ketat | FormRequest di semua aksi tulis (§11) |
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
        foreach (self::PERMISSIONS as $name) {          // 12 permission, §6.2
            $permissions[$name] = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(array_values($permissions));

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(array_values(array_diff_key(
            $permissions,
            array_flip(['manage-users', 'manage-settings']),
        )));

        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Nomor, email & sandi dari config, BUKAN ditanam di kode — kalau
        // tidak, kredensial contoh yang sama menjadi super-admin di produksi.
        $user = User::withTrashed()->firstOrCreate(
            ['phone' => config('seekitar.super_admin_phone')],
            [
                'name'  => 'Super Admin',
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
> 3. **`manage-users` dan `manage-settings` ditahan dari role `admin`** —
>    supaya admin biasa tidak bisa mengubah sesama admin, dan halaman
>    pengaturan tetap khusus super-admin (`API_DOCUMENTATION.md` §10.5).

> Daftar permission di atas **sudah** memuat `manage-settings` (12 permission,
> sesuai §6.2). Pastikan keduanya tetap sinkron saat menambah permission baru.

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

Selain seeder produksi, tersedia **12 factory** dan `DemoDataSeeder` yang
mengisi SELURUH tabel dengan ratusan baris realistis (local/testing saja).

| Berkas | Isi |
| :-- | :-- |
| `database/factories/Support/Wilayah.php` | 19 titik kecamatan nyata di Kabupaten Pasuruan |
| `UserFactory` | state `basic`, `verified`, `pro`, `menungguKtp`, `diblokir` |
| `StoreFactory` | `terverifikasi`, `menunggu`, `ditolak`, `nonaktif`, `tipe()`, `diTitik()` |
| `ListingFactory` | `produk`, `jasa`, `sewa`, `stokHabis` — 36 judul katalog nyata |
| `CustomerRequestFactory` | `terbuka`, `ditutup`, `kedaluwarsa`, `diperpanjang`, `mendesak` |
| `OfferFactory` | `menunggu`, `diterima`, `ditolak`, `lewatWaktu`, `denganHarga()` |
| `OrderFactory` | tiap status ENUM + `diantar`/`diambil`, `cod`/`transfer` |
| `ReviewFactory` | `keToko()`, `kePembeli()`, `bintang()` |
| `DisputeFactory` | `terbuka`, `lewatSla`, `direspons`, `selesai` |
| `UserDeviceFactory`, `FavoriteFactory`, `CategoryFactory` | pelengkap |

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

# --- Sanctum & Session ----------------------------------------------------
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
AWS_BUCKET_PRIVATE=seekitar-ktp     # KTP & selfie, TIDAK publik
AWS_KMS_KEY_ID=                     # kunci SSE-KMS untuk bucket privat (§18A.3)

# --- Keamanan ---------------------------------------------------------------
CORS_ALLOWED_ORIGINS=https://admin.seekitar.id,https://seekitar.id
SESSION_EXPIRE_ON_CLOSE=true

# --- Firebase (FCM) -------------------------------------------------------
FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json
FIREBASE_PROJECT_ID=seekitar-prod

# --- WhatsApp OTP ---------------------------------------------------------
WHATSAPP_DRIVER=kirimwa        # kirimwa | twilio | log
KIRIMWA_URL=https://api.kirimwa.id/v1
KIRIMWA_TOKEN=
TWILIO_SID=
TWILIO_TOKEN=
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886

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

Dua bucket dengan sifat berbeda (lihat §18A.3):

| Bucket | Akses | Isi |
| :-- | :-- | :-- |
| `seekitar-media` | publik lewat CDN | Foto listing, avatar |
| `seekitar-ktp` | **privat**, SSE-KMS | KTP & selfie |

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

### 22.1 Datatables Controller (Category)

Yajra menyediakan dua pendekatan. Yang dipakai di §22.2 adalah **kelas
DataTable** (`app/DataTables/`), karena Blade-nya memanggil `$dataTable->table()`
dan `$dataTable->scripts()`.

```php
// app/DataTables/CategoriesDataTable.php
class CategoriesDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('parent_name', fn (Category $c) => $c->parent?->name ?? '-')
            // Kirim data lewat view, bukan rangkaian string HTML — atribut
            // yang mengandung kutip (mis. nama "Bengkel \"Jaya\"") akan
            // merusak markup jika digabung manual.
            ->addColumn('action', fn (Category $c) => view('admin.categories.actions', ['category' => $c]))
            ->rawColumns(['action'])
            ->setRowId('id');
    }

    public function query(Category $model): QueryBuilder
    {
        return $model->newQuery()->with('parent')->select('categories.*');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('categoriesTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
            ->parameters(['language' => ['url' => asset('js/datatables-id.json')]]);
    }

    protected function getColumns(): array
    {
        return [
            Column::make('name')->title('Nama'),
            Column::make('slug')->title('Slug'),
            Column::make('parent_name')->title('Induk')->orderable(false),
            Column::make('icon')->title('Ikon'),
            Column::computed('action')->title('Aksi')
                ->exportable(false)->printable(false)->width(120)->addClass('text-center'),
        ];
    }
}
```

**Controller wajib mengirim objeknya ke view** — Blade di §22.2 memanggil
`$dataTable->table()`, jadi tanpa ini muncul `Undefined variable $dataTable`:

```php
public function index(CategoriesDataTable $dataTable)
{
    // render() sekaligus menyuntikkan $dataTable ke view.
    return $dataTable->render('admin.categories.index');
}
```

> Alternatifnya memakai facade `DataTables::of()` di method `data()` seperti
> §10 — tapi kalau memilih itu, Blade-nya **tidak boleh** memanggil
> `$dataTable->table()`; tabel `<table>` ditulis manual dan inisialisasi
> DataTables dilakukan di JavaScript. Jangan mencampur kedua pendekatan.

### 22.2 Blade Partial (categories/index.blade.php)

```blade
@extends('layouts.admin')
@section('content')
<div class="card">
  <div class="card-header">
    <h5>Daftar Kategori</h5>
    <button class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#categoryModal">Tambah Kategori</button>
  </div>
  <div class="card-body">
    {!! $dataTable->table() !!}
  </div>
</div>
<!-- Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
  <div class="modal-dialog">
    <form id="categoryForm">
      @csrf
      <input type="hidden" name="_method" value="POST" id="method">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Tambah/Edit Kategori</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Nama</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <!-- field lainnya -->
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
  // handling modal, AJAX store/update — lihat 22.2a
</script>
@endpush
```

### 22.2a Logika Modal Create vs Edit

Satu form dipakai untuk dua mode. Yang membedakan hanyalah URL tujuan dan
method — dan **method spoofing** (`_method=PUT`) wajib, karena form HTML hanya
mengenal GET/POST.

```js
const modal   = new bootstrap.Modal('#categoryModal');
const form    = document.getElementById('categoryForm');
const titleEl = document.querySelector('#categoryModal .modal-title');

/** Bersihkan sisa state sebelumnya, lalu isi sesuai mode. */
function openModal(mode, category = null) {
  form.reset();
  form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

  if (mode === 'edit') {
    titleEl.textContent = 'Edit Kategori';
    form.action = `/admin/categories/${category.id}`;
    form.querySelector('#method').value = 'PUT';   // spoofing
    form.name.value       = category.name;
    form.slug.value       = category.slug;
    form.parent_id.value  = category.parent_id ?? '';
    form.icon.value       = category.icon ?? '';
    form.sort_order.value = category.sort_order ?? 0;
  } else {
    titleEl.textContent = 'Tambah Kategori';
    form.action = '/admin/categories';
    form.querySelector('#method').value = 'POST';
  }

  modal.show();
}

document.getElementById('btnAddCategory')
  .addEventListener('click', () => openModal('create'));

// Tombol Edit dibuat ulang setiap tabel dimuat, jadi pakai event delegation —
// listener langsung akan hilang setelah paginasi/pencarian.
document.querySelector('#categoriesTable').addEventListener('click', (e) => {
  const btn = e.target.closest('.edit-btn');
  if (btn) openModal('edit', JSON.parse(btn.dataset.category));
});

form.addEventListener('submit', async (e) => {
  e.preventDefault();

  const res = await fetch(form.action, {
    method: 'POST',                       // method asli tetap POST
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    body: new FormData(form),
  });

  if (res.status === 422) {
    const { errors } = await res.json();
    Object.entries(errors).forEach(([field, messages]) => {
      const input = form.querySelector(`[name="${field}"]`);
      if (!input) return;
      input.classList.add('is-invalid');
      input.insertAdjacentHTML('afterend',
        `<div class="invalid-feedback">${messages[0]}</div>`);
    });
    return;                               // modal tetap terbuka
  }

  if (res.ok) {
    modal.hide();
    window.LaravelDataTables.categoriesTable.ajax.reload(null, false); // pertahankan halaman
  }
});
```

Tombol Edit membawa datanya sebagai satu atribut JSON, sehingga aman terhadap
tanda kutip di nama kategori:

```blade
{{-- resources/views/admin/categories/actions.blade.php --}}
@can('manage-categories')
  <button class="btn btn-sm btn-warning edit-btn"
          data-category='@json($category->only(["id","name","slug","parent_id","icon","sort_order"]))'>
    Edit
  </button>
  <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline"
        onsubmit="return confirm('Hapus kategori {{ $category->name }}?')">
    @csrf @method('DELETE')
    <button class="btn btn-sm btn-danger">Hapus</button>
  </form>
@endcan
```

> ⚠️ `ajax.reload(null, false)` — argumen kedua `false` menjaga posisi halaman.
> Tanpa itu, admin selalu terlempar ke halaman 1 setiap menyimpan.

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
