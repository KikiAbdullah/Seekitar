# Seekitar WhatsApp Gateway (Baileys)

Gateway WhatsApp Web lokal memakai
[Baileys](https://github.com/WhiskeySockets/Baileys) — sidecar Node.js yang
dipanggil backend Laravel (`WHATSAPP_DRIVER=baileys`) untuk mengirim OTP dan
mengelola sesi WhatsApp.

## ⚠️ Sebelum dipakai

**Baileys BUKAN API resmi WhatsApp.** Menggunakan library tak-resmi ini
menyalahi Ketentuan Layanan WhatsApp dan nomor yang dipakai bisa diblokir.
Gunakan nomor **khusus gateway** (bukan nomor pribadi), dan pertimbangkan
provider resmi (Kirim WA / Twilio) untuk produksi berskala besar. Keunggulan
Baileys: gratis, tanpa kuota per pesan, dan QR bisa di-scan dari panel admin.

## Menjalankan

```bash
cd seekitar-server/whatsapp-gateway
npm install
npm start          # http://127.0.0.1:3001
```

| Variabel env | Default | Keterangan |
| :-- | :-- | :-- |
| `PORT` | `3001` | Port HTTP service |
| `HOST` | `127.0.0.1` | **Jangan expose ke jaringan** (kredensial WhatsApp ada di sini) |
| `BAILEYS_TOKEN` | *(kosong)* | Wajib di produksi; Laravel mengirimnya sebagai `Authorization: Bearer` |
| `SESSION_DIR` | `./session` | Folder kredensial sesi (di-`gitignore`) |
| `LOG_LEVEL` | `silent` | `error` / `warn` / `info` / `debug` untuk troubleshooting |
| `QR_TTL_MS` | `45000` | Masa berlaku QR yang dikembalikan `/api/qr` |
| `RECONNECT_DELAY_MS` | `5000` | Jeda coba sambung ulang setelah koneksi putus |
| `SEND_TIMEOUT_MS` | `8000` | Batas waktu kirim pesan; lewat batas → balas `504` (mencegah request menggantung) |
| `REDIS_URL` | *(kosong)* | URL Redis untuk **jalur cepat socket** (contoh `redis://127.0.0.1:6379`). Jika diisi, gateway men-subscribe `WA_CHANNEL_SEND` dan kirim pesan tanpa HTTP handshake per pesan |
| `WA_CHANNEL_SEND` | `seekitar:wa:send` | Channel Redis tempat Laravel mem-publish pesan |
| `WA_CHANNEL_RESULT` | `seekitar:wa:result` | Channel Redis hasil kirim (`{id, ok, error}`) |

## Menjalankan di background (daemon)

| Platform | Cara |
| :-- | :-- |
| **Windows** | `start-background.bat` (memakai PM2; auto-restart & ikut boot) |
| **Linux** | `seekitar-wa.service` (systemd) — `systemctl enable --now seekitar-wa` |
| **Semua** | `npm i -g pm2 && pm2 start ecosystem.config.js && pm2 save` |

Isi `BAILEYS_TOKEN` (sama dengan `.env` Laravel) dan `REDIS_URL` pada
`ecosystem.config.js` / unit systemd / env sebelum start.

## Jalur cepat: Redis pub/sub (socket)

Agar OTP terkirim **sangat cepat** tanpa HTTP handshake per pesan:

1. Gateway: set `REDIS_URL` (mis. `redis://127.0.0.1:6379`) → otomatis
   subscribe `seekitar:wa:send`.
2. Laravel: set `BAILEYS_REDIS_URL=redis://127.0.0.1:6379` di `.env` →
   `BaileysGateway::sendOtp` mem-publish ke channel itu (bukan HTTP).
3. OTP dikirim via **Laravel Queue** (`SendOtpJob`, antrean `high`) —
   request `POST /auth/request-otp` langsung membalas; worker yang
   meneruskan. Jalankan worker:
   `php artisan queue:work redis --queue=high,default`

> Redis pub/sub adalah fire-and-forget: bila gateway mati saat publish,
> pesan tidak antre. Untuk keandalan penuh tetap jalankan worker queue
> (retry) dan pantau `failed_jobs`. Bila butuh garansi kirim, biarkan
> `BAILEYS_REDIS_URL` kosong (HTTP fallback tetap andal di localhost).

## Endpoint HTTP

| Method | Path | Fungsi |
| :-- | :-- | :-- |
| `GET` | `/healthz` | Health check sederhana |
| `GET` | `/api/status` | `{ online, phone, last_connected_at, qr_available, logged_out }` |
| `GET` | `/api/qr` | QR code sebagai **data URL PNG** untuk di-scan; `null` bila belum tersedia |
| `POST` | `/api/logout` | Putuskan sesi & hapus kredensial → QR baru diminta |
| `POST` | `/api/send` | Kirim pesan teks: `{ "to": "62812...", "text": "..." }` |

Semua `/api/*` mewajibkan `Authorization: Bearer <BAILEYS_TOKEN>` bila token
di-set.

## Integrasi Laravel

```env
WHATSAPP_DRIVER=baileys
BAILEYS_URL=http://127.0.0.1:3001
BAILEYS_TOKEN=<sama dengan service>
```

- OTP dikirim lewat `App\Services\WhatsApp\BaileysGateway`.
- Panel admin → **WhatsApp Gateway** (permission `manage-whatsapp`): scan QR,
  status online/offline, cabut sesi, uji kirim.

## Alur scan QR

1. Pastikan service berjalan (`npm start`) dan Laravel memakai driver
   `baileys`.
2. Buka panel admin → **WhatsApp Gateway**.
3. Buka WhatsApp di ponsel → **Perangkat Tertaut** → **Tautkan Perangkat** →
   scan QR yang tampil.
4. Status berubah **Online**; OTP mulai terkirim lewat nomor itu.

Sesi tersimpan di `session/` — saat service restart, tersambung ulang tanpa
scan (selama belum logout).

## Troubleshooting

- **QR tidak muncul**: cek `npm start` berjalan; lihat log dengan
  `LOG_LEVEL=debug`. Pastikan mesin bisa menjangkau server WhatsApp
  (jaringan diblokir firewall/proxy akan membuat QR tidak pernah keluar).
- **`409 belum tersambung` saat kirim**: scan QR dulu.
- **Tiba-tiba offline**: cek log; koneksi akan coba disambung otomatis.
  Kalau `logged_out` (nomor di-logout dari perangkat lain), scan ulang.
- **Kirim menggantung / timeout (cURL 28)**: koneksi WhatsApp mati diam-diam
  (network drop tanpa event close) sehingga `sendMessage` tidak pernah
  selesai. Service kini mendeteksi WebSocket tidak OPEN (status diturunkan ke
  offline) dan membalas `504` dalam `SEND_TIMEOUT_MS`; pastikan koneksi
  internet stabil, atau scan ulang QR.
- **Web tetap Offline & QR muncul padahal HP sudah "Perangkat Tertaut"**:
  (1) pastikan hanya **satu** proses gateway yang berjalan — kalau ada dua
  (mis. `npm start` lama + PM2), yang dipanggil Laravel bisa bukan yang
  ter-scan. Cek dengan `pm2 status` / tutup terminal `npm start` lain.
  (2) Versi gateway harus yang terbaru (`git pull` lalu restart): versi lama
  salah mendeteksi WebSocket (memakai `readyState` yang tidak ada di Baileys
  6.7.x) sehingga status di-reset ke offline terus. (3) Lihat log gateway —
  harus ada baris `✅ TERSAMBUNG — nomor: …` saat scan berhasil; kalau tidak
  ada, sesi belum benar-benar open.
- **Nomor diblokir**: hentikan pemakaian, hubungi WhatsApp untuk pemulihan —
  ini risiko Baileys (lihat peringatan di atas).
