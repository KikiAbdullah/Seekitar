# Dokumentasi Isi View — Seekitar (Admin & Web)

Dokumen ini merangkum isi/konten lengkap dari seluruh view Blade pada:
- `seekitar-server/resources/views/admin/` (panel admin)
- `seekitar-server/resources/views/web/` (situs web publik)

Disusun per folder/per file: judul halaman, isi, kolom tabel, filter, aksi, partial, dan catatan teknis penting.

---

## A. Panel Admin (`resources/views/admin/`)

### A.1 Kerangka & Partial

| File | Isi |
|------|-----|
| `layouts/admin.blade.php` | Kerangka panel. Font **Plus Jakarta Sans** (Google Fonts), ikon **Tabler** (`vendor/mordenize`), tema `vendor/mordenize/css/style.min.css` (sudah diwarnai hijau Seekitar), DataTables di-host sendiri (`vendor/mordenize/libs/datatables.net/`), i18n `vendor/datatables/id.json`. `@yield('title')`, `@yield('content')`, `@stack('styles')`, `@stack('scripts')`. Preconnect ke fonts.googleapis / tile.openstreetmap. |
| `partials/sidebar.blade.php` | Menu navigasi: logo lockup; **Dasbor, Pengguna, Toko, Verifikasi KTP, Verifikasi Toko, Kategori, Listing, Permintaan, Penawaran, Pesanan, Ulasan, Blog, Iklan Banner, Langganan, Laporan Masalah, Biaya Layanan, Pengaturan, Dompet, WhatsApp Gateway**. Tidak ada item "Peta Toko" — peta tersarang: item Toko ikut aktif via `routeIs('admin.maps.stores')` (sidebar.blade.php:40). Semua ikon **Tabler `ti ti-*`**, seragam terpusat; sidebar mini (ikon 24px) saat dicollapse; tiap item dibungkus `@can`. |
| `partials/header.blade.php` | Bar atas: tombol toggle sidebar + dropdown profil kanan (**Profil Saya, Ubah Kata Sandi, Log Out**). Tidak ada judul dinamis/notifikasi. |
| `partials/table-page.blade.php` | Kerangka tabel DataTables generik (judul + breadcrumb, filter `data-dt-filter`, tombol Ekspor CSV, toolbar aksi baris terpilih, server-side). **Ada di disk tetapi TIDAK di-`@include` oleh view mana pun** — halaman index ditulis manual (lihat pola di bawah). |

**Pola umum index:** semua view `@extends('admin.layouts.admin')` lalu **menulis halaman DataTable sendiri** (kartu + header aksi, toolbar filter `_filter` per modul via `data-dt-filter`, tabel `serverSide`, DataTables di-`@push('scripts')` per halaman). `partials/table-page.blade.php` menyediakan pola generik tapi tidak dipakai. Cell partial (`_actions`, `_nama`, `_status`, dll.) di-`include` per baris.

### A.2 Dashboard

| File | Isi |
|------|-----|
| `dashboard.blade.php` | Kartu sambutan (tanggal lokal berbahasa Indonesia, nama admin, sorotan nilai + label), kartu **"Antrian Kerja"** (daftar hal yang menunggu tindakan admin), statistik ringkas. |

### A.3 Autentikasi

| File | Isi |
|------|-----|
| `auth/login.blade.php` | Halaman login panel (layout mandiri, tidak `@extends`), latar `radial-gradient`, form email + sandi, logo lockup, meta `noindex`. |

### A.4 Profil

| File | Isi |
|------|-----|
| `profile/edit.blade.php` | **Data Akun**: nama, email, telepon + foto profil. |
| `profile/password.blade.php` | **Ubah Sandi**: dengan peringatan — semua sesi perangkat lain akan dikeluarkan dan token aplikasi dicabut. |

### A.5 Verifikasi (`verifications/`)

| File | Isi |
|------|-----|
| `users.blade.php` | **Antrian Verifikasi Identitas** (pengguna yang mau buka toko). SLA **1×24 jam**, diurutkan paling lama dulu. Klik baris → modal `_user_modal`. |
| `_user_modal.blade.php` | Modal verifikasi identitas 3 langkah: **(1)** foto wajah ↔ KTP berdampingan, **(2)** data berkas (nama, NIK, alamat KTP), **(3)** domisili: alamat vs titik peta. Ada checklist gerbang — tombol **Setujui** terkunci sampai checklist tercentang. Penolakan memakai collapse di dalam modal. |
| `stores.blade.php` | **Antrian Verifikasi Toko** — hanya toko yang pemiliknya sudah terverifikasi. Modal `_store_modal`. |
| `_store_modal.blade.php` | Modal verifikasi toko: **(1)** alamat & identitas pemilik, **(2)** foto toko, **(3)** koordinat toko vs Google Maps. Tombol **Verifikasi** terkunci sampai checklist tercentang. `getRawOriginal('photo')` dipakai untuk cek file foto asli. |

### A.6 Pengguna (`users/`)

| File | Isi |
|------|-----|
| `index.blade.php` | Kolom: **Nama, Telepon, Rating, Status, Terdaftar, Email, Alamat**. Urutan default `[[4,'desc']]` (Terdaftar terbaru dulu). Filter status `UserStatus`. Modal blokir. Export `admin.users.export`. |
| `show.blade.php` | Identitas + avatar (lightbox), foto KTP, alamat + **peta Leaflet**, kredensial akun, status, aksi Blokir/Buka Blokir. |
| `edit.blade.php` | Form multipart: nama, email, telepon, alamat, koordinat (peta), avatar, KTP. |
| `_nama.blade.php` | Nama + ikon centang terverifikasi bila `verified_at` terisi. |
| `_status.blade.php` | Badge dari enum `UserStatus` (`->color()`, `->label()`). |
| `_actions.blade.php` | Tombol **Detail / Sunting / Blokir / Buka Blokir**. |

### A.7 Toko (`stores/`)

| File | Isi |
|------|-----|
| `index.blade.php` | Kolom: **Nama, Pemilik, Kabupaten, Kedudukan, Rating, Dibuat**. Filter `StoreStatus`. Modal tolak. Export. |
| `show.blade.php` | Header + peringatan merah bila toko terblokir, info toko, peta Leaflet, pemilik, rating, listing milik toko, aksi Setujui/Tolak/Blokir. |
| `edit.blade.php` | Form multipart + strip identitas (hanya untuk foto/identitas). Foto, nama, kategori, kabupaten, alamat, koordinat, status aktif, slug. |
| `_nama.blade.php` | Nama + centang terverifikasi bila `StoreStatus::Verified`. |
| `_status.blade.php` | Badge status + label **"Nonaktif"** bila `!is_active`. |
| `_actions.blade.php` | **Detail / Sunting** + aksi cepat **Setujui / Tolak** saat status `Pending`. |

### A.8 Listing (`listings/`)

| File | Isi |
|------|-----|
| `index.blade.php` | Kolom: **Listing, Toko, Tipe, Harga, Favorit, Status, Dibuat**. Urutan default `[[6,'desc']]`. Filter **Status** (`ListingStatus`) + **Tipe** (`ListingType`). |
| `show.blade.php` | Judul + badge tipe/status, galeri foto, deskripsi, harga, kapasitas (slot/stok), statistik `['total','selesai','omzet']`, **pesanan terbaru (maks 8)**, **penggemar (maks 5)**. |
| `_judul.blade.php` | Foto mini (46px) + judul + keterangan **"slot X / hari"** (layanan) atau **"stok X unit"** (barang). |
| `_tipe.blade.php` | Badge + ikon dari enum `ListingType`. |
| `_status.blade.php` | Badge `ListingStatus`. |
| `_actions.blade.php` | **Lihat + Hapus**. |

### A.9 Pesanan (`orders/`)

| File | Isi |
|------|-----|
| `index.blade.php` | Kolom: **Pesanan, Pembeli, Toko, Total, Status, Dibuat**. Urutan default `[[5,'desc']]`. Filter: **Status** (`OrderStatus`), **Tipe** (`OrderType`), **Pemenuhan** (`DeliveryMethod`). Export. |
| `show.blade.php` | Nomor order monospace + badge status (`_order_badge`) + tipe·qty, pembeli, toko, alamat + peta Leaflet (bila ada `latitude`), rincian pesanan, riwayat status. |
| `_nomor.blade.php` | Nomor order + baris kecil **"tipe · ×qty · diantar/ambil sendiri"**. |
| `_pembeli.blade.php` | Nama + centang bila `verified_at` + nomor telepon. |
| `_toko.blade.php` | Nama toko + centang terverifikasi. |

### A.10 Penawaran (`offers/`) — read-only

| File | Isi |
|------|-----|
| `index.blade.php` | Banner info (mode baca saja). Kolom: **Permintaan, Toko, Harga, Total, Estimasi, Status, Kedaluwarsa, Dibuat**. Filter `OfferStatus`. |
| `show.blade.php` | Ringkasan harga, status, detail permintaan terkait, aksi. |

### A.11 Permintaan (`requests/`)

| File | Isi |
|------|-----|
| `index.blade.php` | Kolom: **Judul, Pembeli, Status, Penawaran, Kedaluwarsa, Dibuat**. Filter `RequestStatus`. |
| `show.blade.php` | Detail permintaan + peta Leaflet + daftar penawaran masuk. |
| `_actions.blade.php` | **Lihat** + **Perpanjang 24 jam** (kecuali status sudah Closed). |

### A.12 Laporan Masalah (`disputes/`)

| File | Isi |
|------|-----|
| `index.blade.php` | **"Laporan Masalah"**. Kolom: **Pesanan, Alasan, Status, Batas SLA, Lewat SLA**. Urutan default `[[3,'asc']]` — yang paling dekat/lewat SLA tampil duluan. Filter `DisputeStatus`. |
| `show.blade.php` | Peringatan SLA **overdue** mencolok, detail pesanan, alasan, riwayat, aksi. |

### A.13 Ulasan (`reviews/`)

| File | Isi |
|------|-----|
| `index.blade.php` | Banner peringatan: *"menghapus ulasan menghitung ulang rating toko"*. Kolom: **Toko, Pengulas, Arah, Rating, Komentar, Tanggal**. Filter rating 1–5. |
| `_actions.blade.php` | Tombol **Hapus**. |

### A.14 Kategori (`categories/`)

| File | Isi |
|------|-----|
| `index.blade.php` | Tabel kategori + tombol **"Kategori"** (buat baru). |
| `form.blade.php` | Form tambah/sunting: nama, slug, deskripsi, kategori induk (parent), urutan, aktif. |
| — | Aksi **Edit / Hapus** dirender inline per baris (index.blade.php:44-55), tanpa partial `_actions`. |

### A.15 Blog (`blog/`)

| File | Isi |
|------|-----|
| `index.blade.php` | Tabel artikel + filter kategori (hardcoded: Edukasi, Keamanan, Panduan, Berita, Cerita) + tombol **"Artikel Baru"**. |
| `form.blade.php` | Form: judul, slug, kategori, ringkasan, konten, gambar, tanggal, status. |
| `_actions.blade.php` | **Sunting / Hapus**. |

### A.16 Langganan & Boost (`subscriptions/`)

| File | Isi |
|------|-----|
| `index.blade.php` | **"Langganan & Boost"**. Kolom: **Pengguna, Paket, Jumlah, Status, Ref. Bayar, Mulai, Berakhir, Dibuat**. Filter plan (`pro_monthly`, `boost_listing`) + status (`pending`, `active`, `expired`, `cancelled`). |
| `show.blade.php` | Detail langganan. |
| `_user.blade.php` | Nama pengguna + nama toko. |
| `_status.blade.php` | Badge status + **"Lewat"** bila status `active` tapi `ends_at` sudah lewat. |
| `_actions.blade.php` | **Lihat** + **Batalkan** (bila status `active`). |

### A.17 Iklan Banner (`advertisements/`)

| File | Isi |
|------|-----|
| `index.blade.php` | **"Iklan Banner"**. Kolom: **Judul, Posisi, Pembeli, Harga-Hari, Mulai, Berakhir, Dibuat**. Filter posisi (`feed`, `sidebar`, `search`, `category`) + status (`available`, `active`, `inactive`). Tombol **"Iklan Baru"**. |
| `create.blade.php` / `edit.blade.php` | Form: judul, posisi, pembeli, harga/hari, rentang tanggal mulai–berakhir, target URL, gambar banner. |
| `_actions.blade.php` | **Sunting / Hapus**. |

### A.18 Biaya Layanan (`fees/`)

| File | Isi |
|------|-----|
| `index.blade.php` | Ringkasan **Total Biaya Layanan** + **Bulan Ini** + tabel rincian biaya layanan. |

### A.19 Pengaturan Sistem (`settings/`)

| File | Isi |
|------|-----|
| `index.blade.php` | **"Pengaturan Sistem"** — form grup pengaturan + banner: *"perubahan berlaku untuk data baru saja"*. |

### A.20 Peta Toko (`maps/`)

| File | Isi |
|------|-----|
| `stores.blade.php` | **"Peta Toko"** — Leaflet, sebaran semua toko di `config('seekitar.regency')` yang memiliki koordinat. |

### A.21 Gateway WhatsApp (`whatsapp/`)

| File | Isi |
|------|-----|
| `index.blade.php` | **"Gateway WhatsApp"** (permission `manage-whatsapp`, driver `baileys`): kartu status (Online/Offline, nomor tersambung, terakhir tersambung) + tombol Muat Ulang & Cabut Sesi (konfirmasi SweetAlert2), kotak QR code untuk scan (auto-poll 3 dtk saat offline), langkah scan, dan form uji kirim pesan. Bila driver bukan `baileys`, menampilkan pesan cara mengaktifkannya. |

### A.22 Dompet (`wallet/`)

| File | Isi |
|------|-----|
| `index.blade.php` | **"Dompet — Verifikasi Pembayaran"** (permission `manage-settings`, route `admin.wallet.index`): kartu **Top Up Pending** (referensi monospace, pengguna, jumlah, diajukan, aksi **Konfirmasi/Batal**) + kartu **Penarikan Pending (payout)** (referensi, pengguna, jumlah, rekening, diajukan, aksi **Selesai/Tolak**). Keduanya dirender server-side (loop `@foreach`), bukan DataTables; ada empty state tiap kartu. |

---

## B. Situs Web (`resources/views/web/`)

### B.1 Kerangka & Partial

| File | Isi |
|------|-----|
| `layout.blade.php` | Kerangka web: meta dasar + Open Graph / Twitter / canonical, favicon + apple-touch-icon, **Google Fonts Plus Jakarta Sans via `<link>` + preconnect** (bukan `@import`), CSS `vendor/mordenize-lp/css/style.min.css` + partial `_styles`, header nav (**Beranda, Cari, Tentang, Untuk Penjual, Bantuan, Blog, Kontak, Masuk**), footer (info platform, link halaman statis, sosial), include `partials/_cookie-consent`, `@stack('head' / 'styles' / 'scripts')`. Skrip global **defer**: `aos.js`, `bootstrap.bundle.min.js`, `custom.js` — **jQuery & Owl tidak dimuat global** (hanya di halaman yang butuh, lihat B.2). Navbar `position: fixed` dan **transparan di posisi paling atas** (mengambang di atas hero), berubah putih + bayangan setelah discroll (`.fixed-header`); semua hero diberi `padding-top` agar tidak tertutup navbar. |
| `partials/_hero.blade.php` | Hero mini reusable — breadcrumb, pill `$kicker`, judul `$judul`, subjudul opsional. Dua mode: **berfoto** (pakai `$gambar`/`$gambarAlt`; `img.hero-img` dengan `fetchpriority="high"` + `decoding="async"`, fallback `placehold.co` via `onerror`) atau **dekoratif** (blob/dots). Dipakai semua halaman statis. Padding `pt-13 pb-11` (desktop `pt-lg-13 pb-lg-12`) — top membersihkan navbar fixed, bottom tidak mepet ke section bawah. |
| `partials/_styles.blade.php` | Gaya murni yang dibagi semua halaman: `.eyebrow`, `.icon-soft`, `.card-lift`, `.check-item`, `.stat-num`, `.feature-icon`, **hero full** (`.hero-wrap`, `.hero-figure`, `.hero-badge`), hero mini (`.page-hero`), dokumen legal (`.doc-toc`, `.doc-content`), accordion FAQ. |
| `partials/_cookie-consent.blade.php` | Banner cookie: localStorage key `seekitar_cookie_consent`, tautan ke `web.privacy`, tombol **"Setuju"**. |

### B.2 Halaman

| File | Judul | Isi |
|------|-------|-----|
| `home.blade.php` | Beranda | Hero full (`.hero-wrap` + `img/web/hero.webp` dengan `fetchpriority="high"`, pill **"Pasar lokal · {regency}"**, H1 *"Yang kamu butuhkan, ada di sekitar"*), pita kepercayaan, bagian **layanan** (barang/jasa/sewa), **kategori**, **cara kerja**, **dua arah** (beli & jual), **fitur**, **testimoni** (`.review-slider .owl-carousel`), CTA. Satu-satunya halaman yang memuat **jQuery + Owl Carousel** (CSS lewat `@push('styles')`, JS lewat `@push('scripts')`). |
| `about.blade.php` | Tentang Seekitar | Gagasan inti: pasar lokal dua arah dalam satu kabupaten; *"Bukan sekadar etalase"*; fakta singkat; CTA. |
| `blog.blade.php` | Blog | Daftar artikel `$posts`; empty state; kartu `card card-lift` + `post-thumb` (gambar + badge kategori + tanggal + kutipan); kolom `md-6`; animasi `data-aos`. |
| `blog-post.blade.php` | Detail Artikel | Hero + meta `article:published_time/author/section` (lewat `@push('head')`); konten `.doc-content`; tombol kembali ke Blog. |
| `careers.blade.php` | Karier | Empty state *"Belum ada lowongan terbuka"*; email lamaran ke `config('seekitar.contacts.complaint')` dengan subjek **"Lamaran — [Posisi]"**. |
| `contact.blade.php` | Kontak & Pengaduan | **4 kanal email**: pengaduan umum (**≤2×24 jam**), konten ilegal/abuse (**≤1×24 jam**), data pribadi/UU PDP (**≤3×24 jam**), celah keamanan (**≤1×24 jam**). + alamat, jam operasional, WhatsApp, catatan laporan via aplikasi. |
| `cookie.blade.php` | Kebijakan Cookie | `.doc-toc` 5 bagian: apa itu cookie, cookie yang dipakai (tabel), pihak ketiga, kontrol, pembaruan. *"Terakhir diperbarui: 30 Juli 2026"*. |
| `for-sellers.blade.php` | Untuk Penjual & Penyedia Jasa | Hero berfoto (`_hero` + `penyedia.webp`, pill **"Gratis · Tanpa komisi"**, judul *"Buka toko, jangkau tetangga"*); **statistik** (`$stats`: toko terverifikasi, listing aktif, wilayah operasi); **7 keuntungan** (Gratis Selamanya, Pelanggan Terdekat, Verifikasi Terpercaya, Siaran Kebutuhan, Transaksi Terpantau, Rating & Reputasi, Fitur Premium Opsional); **4 langkah** (unduh & daftar, verifikasi identitas, atur toko, listing barang); **3 testimonial** (Warung Sembako Ibu Wati, Servis AC Barokah, Sewa Tenda Rizki); **CTA** → "Buka Toko Gratis" (ke `web.help`) + "Lihat Detail Biaya" (ke `web.pricing`). |
| `guidelines.blade.php` | Pedoman Komunitas | `.doc-toc` 6 bagian: Prinsip dasar, Konten dilarang, Perilaku dilarang, Pelaporan, Sanksi (tabel Ringan/Sedang/Berat), Banding. *"Terakhir diperbarui: 30 Juli 2026"*. |
| `help.blade.php` | Pusat Bantuan | Hero berfoto (`bantuan.webp`). **Accordion FAQ 4 grup**: Akun & Keamanan (3), Pembayaran (2), Permintaan & Penawaran (3), Verifikasi & Ulasan (2). CTA → Kontak & Tentang. |
| `listing-detail.blade.php` | Detail Listing | Route `/listing/{listing}`. Breadcrumb Beranda → Cari; galeri `detail-gallery` (badge tipe, `fetchpriority="high"`, fallback `placehold.co`); harga + badge **"per hari / per periode sewa"** untuk sewa; kartu toko (avatar, nama + centang, lokasi); CTA **Download di Google Play** (web hanya katalog — transaksi lewat aplikasi); info ringkas (tipe, stok/slot, tanggal, COD, radius layanan); deskripsi `.doc-content`; statistik toko (listing, rating, ulasan); "Lainnya dari {toko}"; CTA bawah. |
| `listings.blade.php` | Cari (Katalog Publik) | Route `/cari`. Hero `hero-wrap` (pill "Katalog Publik", H1 *"Temukan kebutuhanmu di sekitar"*); form cari **keyword + kategori + tipe** (Barang/Jasa/Sewa); hasil grid kartu `listing-card` (thumb `listing-thumb`, badge tipe, nama toko + centang terverifikasi, harga `Angka::rupiah` / "Hubungi Penjual", tanggal); pill filter cepat per tipe + **Reset**; empty state; pagination `$listings->links()`. |
| `pricing.blade.php` | Biaya & Harga | Alert "gratis untuk semua"; tabel **biaya pembeli** (semua gratis); tabel **biaya penjual** (Sekarang vs Kedepan: Boost, Pro, Iklan Banner — diambil dari `config('seekitar.monetization.*')`); **biaya flat per transaksi** (mulai Rp1.500, bukan persentase); pembayaran langsung; fitur premium opsional; perubahan biaya diumumkan ≥30 hari sebelum berlaku. |
| `privacy.blade.php` | Kebijakan Privasi | Legal UU PDP. `.doc-toc` 8 bagian: Data dikumpulkan, Cara dilindungi, Data dibagikan, Tidak dijual, Cookie & teknologi, Pelanggaran data (1×24 jam penanganan, 3×24 jam notifikasi, lapor ke Kominfo), Hak subjek data, Kontak. *"Terakhir diperbarui: 30 Juli 2026"*. |
| `refund.blade.php` | Kebijakan Pengembalian & Sengketa | `.doc-toc` 6 bagian: Posisi Seekitar (platform penghubung, mediator), Jenis sengketa (6), Cara melapor (lewat app — membekukan pesanan; email — tidak), Proses mediasi (4 langkah), Keputusan, Pembatasan tanggung jawab. *"Terakhir diperbarui: 30 Juli 2026"*. |
| `security.blade.php` | Pusat Keamanan | Hero berfoto (`kontak.webp`). `.doc-toc` 5 bagian: Keamanan akun, OTP & kata sandi (kunci 30 menit setelah 5× gagal), Transaksi aman (4 tips), Modus penipuan umum (4), Melaporkan masalah keamanan. |
| `sitemap.blade.php` | — (XML) | Bukan HTML: menghasilkan **sitemap XML** (`<?xml …?>` dicetak lewat blok PHP karena parse error). Loop `$pages` → `<url><loc/><changefreq/><priority/></url>`. |
| `status.blade.php` | Status Layanan | Alert "seluruh layanan berjalan normal"; tabel **status komponen** (API & Aplikasi, Situs Web, Notifikasi, Database — semua badge "Berjalan Normal" + pembaruan `now()->subMinutes(3)`); **riwayat insiden** (belum ada); **pemeliharaan terjadwal** (tidak ada). |
| `terms.blade.php` | Syarat & Ketentuan | `.doc-toc` 7 bagian: Peran Seekitar, Akun, Pembayaran, Yang dilarang, Ulasan, Penyelesaian sengketa, Perubahan ketentuan. *"Terakhir diperbarui: 30 Juli 2026"*. |
| `verification.blade.php` | Kebijakan Verifikasi Identitas | Legal. `.doc-toc` 7 bagian: Mengapa verifikasi, Data diperlukan (foto KTP, swafoto, NIK), Proses, Keamanan data (privat, NIK terenkripsi), Tenggat (1×24 jam), Penolakan & pengajuan ulang (gratis), Pembaruan data. *"Terakhir diperbarui: 30 Juli 2026"*. |

---

## C. Catatan

- Semua halaman legal/statis memakai `@include('web.partials._hero')`; halaman legal memakai daftar isi sticky `.doc-toc` dan konten `.doc-content` (didefinisikan di `partials/_styles.blade.php`).
- Semua halaman admin memakai `@extends('admin.layouts.admin')` (kecuali `auth/login.blade.php` yang mandiri).
- Daftar partial per modul bersifat **indikatif** — sebagian partial nyata (mis. `orders/_filter`, `orders/_actions`, `users/_filter`, `stores/_filter`, `verifications/_user_info`, `_user_action`, `_user_submitted`, `_user_context`, `_store_info`, `_store_action`, `_store_owner`, `partials/_order_badge`) tidak selalu dicantumkan. `admin/users/_actions.blade.php` ada tetapi tidak dipakai — index memakai aksi inline `action-show`/`action-edit`.
- Badge warna/label dibaca dari enum (`->color()`, `->label()`, `->icon()`), tidak hardcoded di blade.
- **Optimasi performa (31 Jul 2026):** `@import` Google Fonts dihapus dari `style.min.css` → font dimuat lewat `<link>` + preconnect; jQuery & Owl Carousel dipindah dari layout global ke `home` saja (via `@push`); `custom.js` ditulis ulang murni vanilla JS (tanpa jQuery) dengan guard tiap slider; CSS yang dibagi (`.hero-wrap`, `.stat-num`, `.feature-icon`, `.check-item`, dll.) dipusatkan di `partials/_styles.blade.php` sehingga halaman statis/legal tidak perlu `@push('styles')` lagi; `img.hero-img` memakai `fetchpriority="high"` + `decoding="async"`. Skrip global memakai `defer`.
- Data kontak & nama perusahaan diambil dari `config('seekitar.contacts.*')` dan `config('seekitar.company.*')`; nama wilayah dari `config('seekitar.regency')`.
