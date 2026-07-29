# Aset SweetAlert2

## Asal

Disalin dari **`github.com/KikiAbdullah/mordenize-template-bs`**, berkas
`package/dist/libs/sweetalert2/dist/sweetalert2.all.min.js` — paket bawaan
template Modernize: **sweetalert2 v11.7.12** (MIT, © SweetAlert2).

`all` berarti JS **sudah termasuk CSS** di dalamnya — jangan muat
`sweetalert2.min.css` terpisah, dan jangan mengambil versi CDN yang lebih
baru selagi template belum ikut naik: tema tombolnya diracik untuk versi
ini.

Dipakai oleh `admin/layout.blade.php` (flash toast & dialog error) dan
skrip konfirmasi tindakan destruktif — lewat `js/seekitar-flash.js`.
