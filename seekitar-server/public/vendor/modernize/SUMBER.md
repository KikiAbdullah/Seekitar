# Aset Template Modernize

## Asal

Disalin dari **`github.com/KikiAbdullah/mordenize-template-bs`**, folder
`package/dist/`, cabang `master`.

Template aslinya **Modernize Admin** oleh **adminmart.com** — versi berbayar
(PRO), bukan versi gratis. `package/package.json` mencantumkan
`"author": "adminmart.com"` dan `"license": "ISC"`, tetapi repositori sumber
tidak menyertakan berkas lisensi apa pun.

> ⚠️ **Catatan lisensi.** Karena ini varian berbayar dan tidak ada teks lisensi
> yang menyertainya, kepatuhan lisensinya bergantung pada pembelian yang
> dilakukan pemilik repositori. Jika Seekitar akan didistribusikan atau
> dipasang di lebih dari satu proyek, periksa dulu ketentuan lisensi AdminMart
> — jumlah proyek yang diizinkan berbeda antara lisensi Single dan Extended.

## Yang disalin (dan yang sengaja tidak)

| Berkas | Asal | Catatan |
| :-- | :-- | :-- |
| `css/style.min.css` | `dist/css/style.min.css` | Sudah memuat **Bootstrap 5.3.0** — jangan muat Bootstrap CSS lagi |
| `css/icons/tabler-icons/` | `dist/css/icons/tabler-icons/` | **Hanya `.woff2`** |
| `js/app.min.js` | `dist/js/app.min.js` | Mendefinisikan `$.fn.AdminSettings` |
| `js/sidebarmenu.js` | `dist/js/sidebarmenu.js` | Menandai menu aktif & membuka submenu |
| `js/custom.js` | `dist/js/custom.js` | Inisialisasi tooltip & popover |
| `js/seekitar.init.js` | **buatan sendiri** | Pengganti `app.init.js` |
| `images/svgs/`, `images/backgrounds/` | `dist/images/` | Hanya yang dirujuk CSS |

**Tidak disalin** (repo aslinya 328 MB, yang dipakai hanya ~1,6 MB):

- `dist/libs/` (50 MB) — jQuery, Bootstrap JS, simplebar diambil dari CDN.
- `dist/images/` selain 5 berkas di atas (51 MB).
- Font Tabler `.eot` / `.ttf` / `.woff` (4,3 MB) — hanya `.woff2` yang
  disalin. Semua browser yang didukung Bootstrap 5 mengerti woff2, dan
  `tabler-icons.min.css` sudah ditulis ulang agar tidak meminta tiga berkas
  yang tidak ada (kalau tidak, 404 di setiap halaman).
- Berkas tema warna lain (`style-aqua`, `style-purple`, dst., 22 MB total) —
  Seekitar hanya memakai satu stylesheet yang sudah diwarnai hijau.
- `app.init.js` & `app-style-switcher.js` — pemilih tema tidak dipakai.

## Pewarnaan hijau

Biru bawaan template (`#5D87FF`) diganti hijau Seekitar (`#168A4A`) **langsung
di dalam `style.min.css`** oleh:

```bash
node tools/dev/recolor-modernize.mjs
```

Skrip itu melakukan **164 penggantian** dan bersifat **idempoten** —
menjalankannya dua kali tidak mengubah apa pun.

**Kenapa bukan sekadar menimpa `--bs-primary` dari `admin.css`?** Karena 117
dari 164 kemunculan biru itu ditulis sebagai nilai heksa langsung di dalam
aturan, bukan lewat variabel CSS. Variabel tidak menjangkaunya, dan hasilnya
tombol biru nyasar di halaman yang jarang dibuka.

## Memperbarui template

1. Salin ulang berkas dari `package/dist/` sesuai tabel di atas.
2. Jalankan `node tools/dev/recolor-modernize.mjs`.
3. Jalankan `node tools/dev/check-admin-menu.mjs` untuk memastikan tidak ada
   aset yang hilang dan tidak ada Bootstrap ganda.
