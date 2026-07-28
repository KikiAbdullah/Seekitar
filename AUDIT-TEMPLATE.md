# Audit Template Modernize vs Implementasi Seekitar

Tanggal: 28 Juli 2026 · Commit: `7cb37f5`

Sumber template: `github.com/KikiAbdullah/mordenize-template-bs` → `package/`

---

## Ringkasan

| Kategori | Status |
| :-- | :-- |
| Kerangka layout (page-wrapper, sidebar, header) | ✅ Sesuai |
| Warna hijau Seekitar | ✅ 164 penggantian, idempoten |
| Ikon Tabler | ✅ 40 ikon terverifikasi |
| Komponen kartu & tabel dasbor | ✅ Sesuai pola asli |
| Halaman masuk | ✅ Pola dua kolom `authentication-login.html` |
| ~~Fokus isian form~~ | ✅ **Diperbaiki** — biru `#aec3ff` & cincin fokus hilang |
| ~~Animasi latar `.radial-gradient`~~ | ✅ **Diperbaiki** — `@keyframes` tidak ada di template |
| **Paginasi Laravel** | ❌ **Rusak — markup Tailwind, tanpa gaya** |
| **Tabel responsif** | ⚠️ **2 tabel bisa meluber di ponsel** |
| Halaman error (403/404/500) | ⚠️ Belum ada, template menyediakan |
| Lupa kata sandi | ⚠️ Belum ada, template menyediakan |
| Form floating label | ℹ️ Beda gaya, bukan cacat |
| Dark mode / RTL / style-switcher | ℹ️ Sengaja tidak dipakai |

**Temuan yang perlu ditindak: 2 bug + 2 halaman hilang.**
**Sudah ditindak sejak audit ini: 3 cacat template (§5).**

---

## ❌ 1. Paginasi Laravel memakai markup Tailwind

**Dampak: tinggi.** Terlihat langsung oleh pengguna.

Laravel 11+ memakai Tailwind sebagai tema paginasi bawaan. Panel Seekitar
memakai Bootstrap, jadi markupnya tidak punya gaya sama sekali — tombol
halaman tampil sebagai teks polos berjejer, dan labelnya berbahasa Inggris.

Diverifikasi dengan merender paginator sungguhan:

```
kelas Tailwind (sm:flex)?  YA  -> markup Tailwind
kelas Bootstrap (.pagination)? TIDAK
panjang HTML: 6377 byte
teks: « Previous  Next »  Showing 21 to 23 of 60 results
```

Terpakai di 2 halaman: `verifications/users`, `verifications/stores`.

**Perbaikan:** daftarkan `Paginator::useBootstrapFive()` di
`AppServiceProvider::boot()`, lalu terjemahkan label lewat
`lang/en/pagination.php`.

---

## ⚠️ 2. Tabel `text-nowrap` tanpa `.table-responsive`

**Dampak: sedang.** Hanya terlihat di layar sempit.

Template selalu membungkus tabel dengan `.table-responsive`. Dua tabel di
Seekitar tidak:

| Berkas | Kelas tabel | Akibat |
| :-- | :-- | :-- |
| `partials/table-page.blade.php` | `table align-middle text-nowrap` | 8 halaman tabel meluber di ponsel |
| `categories/index.blade.php` | `table table-hover align-middle` | ikut meluber |

`text-nowrap` mencegah teks membungkus, jadi tabel **pasti** lebih lebar dari
layar ponsel dan memotong kolom terakhir tanpa cara menggulirnya.

`verifications/users` & `verifications/stores` sudah benar — keduanya memakai
`.table-responsive`.

---

## ⚠️ 3. Halaman yang tersedia di template tapi belum dibuat

### 3a. Halaman error

Template: `authentication-error.html`, `authentication-maintenance.html`

Seekitar: `resources/views/errors/` **tidak ada**, sehingga 403/404/500
memakai halaman bawaan Laravel yang tidak bergaya Modernize sama sekali.

Relevan karena panel ini memang bisa memunculkan 403: middleware
`permission:` menolak admin yang mengetik URL langsung.

### 3b. Lupa kata sandi

Template: `authentication-forgot-password.html`

Halaman login Seekitar tidak punya tautan "Lupa kata sandi", dan rutenya belum
ada. Satu-satunya cara memulihkan akun admin saat ini adalah lewat tinker di
server.

> Catatan: ini butuh keputusan produk lebih dulu — pemulihan lewat email
> mensyaratkan konfigurasi SMTP yang belum ada di `.env` produksi.

> ⚠️ Tautannya **sengaja belum dipasang** di halaman masuk, meski template
> menyediakannya. `route('admin.password.request')` yang belum terdaftar
> melempar `RouteNotFoundException` saat Blade dirender — bukan tautan mati,
> melainkan **halaman masuk yang mati total** dan panel tidak bisa diakses
> sama sekali. Dibuktikan dengan memanggil `route()` sungguhan, dan dijaga
> `check-admin-menu.mjs`.

---

## ✅ 5. Tiga cacat template yang sudah diperbaiki

Ditemukan dengan merender halaman di **Chromium sungguhan** lalu membaca
`getComputedStyle` — bukan dari membaca berkas. Tidak satu pun memunculkan
error, dan ketiganya lolos seluruh pemeriksaan statis sebelumnya.

| # | Cacat | Bukti | Perbaikan |
| :-- | :-- | :-- | :-- |
| 1 | `.radial-gradient::before` memanggil `animation: … gradient`, tetapi `@keyframes gradient` **tidak ada** di `style.min.css` maupun `style.css` di seluruh varian tema | `grep -c '@keyframes gradient'` → `0` pada berkas sumber yang belum diminifikasi | Keyframes didefinisikan di `admin.css` |
| 2 | `.form-control:focus` memakai border biru `#aec3ff` — lolos dari `recolor-modernize.mjs` yang petanya hanya memuat `#5D87FF` | `getComputedStyle` → `rgb(174, 195, 255)` | Dipaksa `var(--seekitar-green)` |
| 3 | `:focus{outline:0;box-shadow:none!important}` global membunuh cincin fokus milik template sendiri (**WCAG 2.4.7**) | `box-shadow` → `none` saat elemen difokus | Cincin hijau dengan `!important` |

Cacat 2 dan 3 **tidak terbatas pada halaman masuk** — keduanya berlaku untuk
setiap isian form di seluruh panel.

---

## ℹ️ 4. Perbedaan yang DISENGAJA — bukan cacat

| Hal | Template | Seekitar | Alasan |
| :-- | :-- | :-- | :-- |
| Dark mode | `style-dark.css` + switcher | Tidak dipakai | Satu stylesheet sudah diwarnai hijau; pemilih tema akan menimpanya |
| RTL / horizontal / minisidebar | 4 varian layout | Hanya vertical | Panel berbahasa Indonesia, satu tata letak |
| `app-style-switcher.js` | Ada | Dibuang | Mengganti tema saat runtime akan mengembalikan warna ke biru |
| Preloader | Ada | Tidak dipakai | Panel dirender server, tidak ada jeda muat yang perlu ditutupi |
| Form floating label | 29 tempat | Label biasa di atas input | Lihat catatan koreksi di bawah |
| Owl Carousel di dasbor | Kartu ringkasan bergeser | Grid statis | Angka penting tidak boleh tersembunyi di balik geseran |
| ApexCharts | Dipakai template | Chart.js | Sudah terpasang lebih dulu & lebih ringan untuk 2 deret data |

> **Koreksi atas alasan `form-floating`.** Awalnya saya menulis label melayang
> menyusut di bawah batas 11px BRANDING §4. Itu **keliru** — dihitung dari
> CSS-nya: `--bs-body-font-size: 0.875rem` (14px) × `transform: scale(.85)`
> = **11,9px**, masih di atas batas.
>
> Alasan sebenarnya lebih lemah: label biasa sudah terpasang di 17 berkas form dan
> mengubahnya tidak memberi manfaat fungsional. Kalau Anda lebih suka gaya
> floating template, itu **sah dan bisa dikerjakan** — masukkan saja ke daftar
> permintaan.

---

## ℹ️ 5. Pustaka template yang belum dimanfaatkan

Template membawa **51 pustaka** di `dist/libs/`. Yang relevan tapi belum
dipakai:

| Pustaka | Kegunaan potensial |
| :-- | :-- |
| `sweetalert2` | Mengganti `confirm()` bawaan browser pada aksi Hapus/Blokir |
| `dropzone` | Unggah berkas KTP/bukti bayar dari panel |
| `daterangepicker` | Filter rentang tanggal di tabel pesanan |
| `quill` / `tinymce` | Editor kaya untuk catatan penyelesaian laporan |
| `fullcalendar` | Kalender jadwal jasa (Fase 2, `service_slots`) |

Tidak satu pun mendesak. Yang paling terasa: **SweetAlert2** — `confirm()`
bawaan browser masih dipakai di **5 partial aksi** (Hapus, Blokir, Perpanjang)
dan tampak asing di tengah panel yang sudah rapi.

**Catatan aset:** DataTables dan Select2 sebenarnya **ada** di `dist/libs/`
template, tetapi Seekitar memuatnya dari CDN. Ini disengaja — versi CDN lebih
baru (DataTables **2.1.8** vs **1.13** di template) dan sudah dipasang sebelum template
ini masuk. Tidak perlu diubah.

---

## Rekomendasi urutan pengerjaan

1. **Paginasi Bootstrap + terjemahan** — bug terlihat, perbaikannya 5 baris.
2. **`.table-responsive`** — bug terlihat di ponsel, perbaikannya 2 baris.
3. **Halaman error 403/404/500** — melengkapi konsistensi visual.
4. **SweetAlert2** — peningkatan rasa, bukan perbaikan.
5. **Lupa kata sandi** — tunggu keputusan SMTP.
