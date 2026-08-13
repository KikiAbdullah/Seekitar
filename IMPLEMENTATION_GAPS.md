# Catatan Gap Implementasi

Terakhir diaudit: **12 Agustus 2026**. Dokumen ini hanya memuat pekerjaan yang
memiliki bukti langsung di source tree atau konfigurasi. Ini bukan daftar ide.

| Prioritas | Gap | Bukti audit | Dampak / langkah selesai |
| :-- | :-- | :-- | :-- |
| P0 | Pengiriman push produksi | Binding `NotificationSender` memakai `LogNotificationSender`; belum ada provider FCM server-side atau kredensialnya. | Notifikasi tersimpan, tetapi push perangkat tidak terkirim dari server. Implementasikan sender FCM, konfigurasi rahasia, retry, dan uji perangkat nyata. |
| P0 | Otomasi CI/CD | Tidak ada direktori workflow GitHub Actions pada repositori. | Build, lint, dan test belum memiliki gate otomatis. Tambahkan workflow terpisah untuk Laravel dan Flutter Android. |
| P1 | Target iOS | `seekitar_mobile/` tidak memiliki direktori `ios/`. | Klaim Android+iOS belum valid. Buat platform iOS, konfigurasi Firebase/izin lokasi/notifikasi, lalu uji perangkat iOS. |
| P1 | Verifikasi build/test penuh | `php artisan test` dan `flutter analyze` melewati batas eksekusi audit 60 detik. | Status hijau penuh belum dapat diklaim dari audit ini. Jalankan di CI atau lokal dengan batas waktu memadai dan rekam hasilnya. |
| P1 | Gateway WhatsApp produksi | Adapter Baileys tersedia, tetapi README gateway sendiri memperingatkan bahwa Baileys bukan API resmi. | Pilih dan konfigurasi provider resmi sebelum penggunaan produksi berskala. |
| P2 | Pembayaran terintegrasi | Wallet memiliki endpoint top-up/withdraw, tetapi tidak terlihat adaptor payment gateway pada service/config. | Top-up dan pencairan perlu proses operasional/manual; integrasikan provider dan webhook tervalidasi jika pembayaran otomatis dibutuhkan. |

## Yang sudah ada dan bukan gap

- REST API Laravel `/api/v1`, panel admin Blade, migrasi MySQL, JWT, peran
  Spatie, OTP, katalog, kebutuhan, penawaran, pesanan, dompet, percakapan,
  notifikasi dalam aplikasi, serta ekspor CSV admin.
- Flutter Android dengan GoRouter, Provider, Dio, FCM client, geolokasi, dan
  87 metode `ApiClient`. Parser pengguna menerima GeoJSON API dan parser
  percakapan memakai `unread_count` dari server.

## Aturan pemeliharaan

Pindahkan item ke changelog atau hapus setelah implementasi, konfigurasi, dan
verifikasi selesai. Jangan memasukkan gagasan spekulatif; gunakan
[FUTURE_IDEAS.md](FUTURE_IDEAS.md) untuk itu.
