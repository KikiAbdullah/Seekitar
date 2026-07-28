# Leaflet 1.9.4

## Asal

Paket npm resmi **`leaflet@1.9.4`** (folder `dist/`), disalin apa adanya —
tidak ada berkas yang disunting.

| Berkas | Ukuran | Catatan |
| :-- | --: | :-- |
| `leaflet.js` | 147 KB | Sudah diminifikasi, produksi |
| `leaflet.css` | 15 KB | Wajib; tanpa ini ubin peta bertumpuk kacau |
| `images/*.png` | 26 KB | Ikon penanda & bayangannya, dirujuk dari CSS |
| `LICENSE` | — | BSD-2-Clause |

Total ~188 KB.

## Kenapa di-host sendiri, bukan dari CDN

Panel admin sudah punya riwayat buruk dengan CDN: berkas i18n DataTables
pernah 404 karena nomor versi plugin berbeda dari versi core, dan seluruh
tabel memunculkan "i18n file loading error" (lihat
`public/vendor/datatables/id.json`). Leaflet memuat `images/marker-icon.png`
lewat **jalur relatif terhadap CSS-nya**, jadi CDN yang berpindah struktur
akan membuat semua penanda peta hilang tanpa satu pun pesan error.

## Kenapa Leaflet, bukan Google Maps

Google Maps JavaScript API **mewajibkan penagihan aktif** sejak Juni 2018.
Tanpa kartu kredit terdaftar, petanya tetap tampil tetapi ditimpa tulisan
"for development purposes only" — tidak layak untuk panel yang dipakai
sehari-hari. Leaflet gratis penuh, dan ubinnya diambil dari OpenStreetMap.

## ⚠️ Kewajiban atribusi ubin

Lisensi BSD Leaflet **tidak** menuntut atribusi di layar. Yang menuntut adalah
**ubin OpenStreetMap**: setiap peta yang memakai `tile.openstreetmap.org`
wajib mencantumkan `© OpenStreetMap contributors`
(https://osmfoundation.org/wiki/Licence).

Atribusi itu **ditegakkan otomatis** oleh `tools/dev/check-admin-menu.mjs` —
menghapusnya membuat checker gagal, bukan diam-diam melanggar lisensi.

## Kebijakan penggunaan ubin OSM

Server ubin OSM adalah layanan sukarela dengan
[Tile Usage Policy](https://operations.osmfoundation.org/policies/tiles/).
Untuk panel internal satu kabupaten volumenya jauh di bawah batas wajar.
**Kalau Seekitar nanti dibuka ke publik**, pindahkan ke penyedia ubin
berbayar/berkuota (mis. MapTiler, Stadia) — cukup ganti satu URL di
`resources/views/admin/maps/stores.blade.php`.
