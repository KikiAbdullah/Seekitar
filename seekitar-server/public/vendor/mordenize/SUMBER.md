# SUMBER — Aset Panel Admin Modernize

Provenance & catatan lisensi untuk `public/vendor/mordenize/`.

## Template UI

- **Modernize Admin** — adminmart.com, versi gratis.
- Disalin dari `github.com/KikiAbdullah/mordenize-template-bs`, folder `package/dist/`.
- Lisensi: versi gratis Modernize dirilis di bawah lisensi MIT (lihat
  adminmart.com/license). Sumber tetap milik adminmart.com.

## Ikon

- **Tabler Icons** — tabler.io, dirilis di bawah **MIT**
  (https://github.com/tabler/tabler-icons/blob/main/LICENSE).
- Versi berkas `tabler-icons.min.css`: **3.46.0** (ditulis di header berkas).
- Hanya font `tabler-icons.woff2` yang dirujuk CSS; referensi `.eot/.ttf/.woff`
  telah dihapus dari `@font-face` agar tidak ada permintaan 404 (berkas itu
  tidak disalin). Font `.ttf/.woff` di direktori `fonts/` adalah sisa unduhan
  dan tidak dipakai.

## Pustaka yang ikut tersalin

| Pustaka | Lisensi |
| :-- | :-- |
| Bootstrap 5.3.0 (termuat di `style.min.css`) | MIT |
| DataTables (jquery.dataTables.min.js + bootstrap5) | MIT |
| Select2 4.1.0 | MIT |
| SimpleBar | MIT |
| SweetAlert2 | MIT |
| TinyMCE | Perkakas — GNU/GPL (gratis untuk web publik) |
| Summernote | MIT |
| Quill | BSD-3-Clause |
| Prism.js | MIT |
| Feather Icons | MIT |

## Perubahan yang dilakukan Seekitar

- `css/style.min.css` diwarnai ulang dari biru bawaan `#5D87FF` ke hijau
  Seekitar `#168A4A` oleh `tools/dev/recolor-modernize.mjs` (idempoten).
- `js/seekitar.init.js` menggantikan `js/app.init.js` bawaan yang menyetel
  `ThemeBg: "purple_theme"` (pemicu stylesheet tema terpisah); Seekitar memakai
  satu stylesheet hijau, jadi cukup memanggil `AdminSettings`.
- `css/icons/tabler-icons/tabler-icons.min.css` dipangkas menjadi rujukan
  `woff2` saja.
