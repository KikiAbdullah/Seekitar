# Seekitar Server

Backend **Seekitar** — marketplace hyperlocal dua arah berbasis geolokasi:
REST API untuk aplikasi Flutter (`/api/v1`) + panel admin web (`/admin`).

## Tumpukan

| Komponen | Versi |
| :-- | :-- |
| Laravel | 13.x (`laravel/framework ^13.8`) |
| PHP | 8.3+ |
| Database | MySQL 8.0.34+ (**wajib** — `POINT SRID 4326`, `SPATIAL INDEX`, `SET`, `CHECK`) |
| Auth API | Sanctum 4 (token Bearer, masuk via OTP WhatsApp) |
| Panel admin | Blade + Bootstrap 5.3 (template Modernize, divendor di `public/vendor/`) + Yajra DataTables 13 + SweetAlert2 (divendor di `public/vendor/sweetalert2/`) |
| Peran & izin | Spatie Permission 8 |
| Queue/Cache | Redis 7 |

## Menyiapkan

```bash
composer install
cp .env.example .env && php artisan key:generate
# isi kredensial DB_* (MySQL 8.0.34+ wajib ada — Laravel tidak membuatkan basis data)
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

`migrate:fresh --seed` adalah jalan resmi: perubahan skema SELALU dimerge
ke migrasi dasar (tidak ada migrasi `add_*`), jadi skema lengkap selalu
terbentuk dari nol. Seed menjalankan, berurutan: role & permission
satu akun super-admin (dari `config/seekitar.php`), kategori, pengaturan,
akun contoh per peran (local/testing saja), lalu data dummy.

### Kredensial contoh (local/testing)

Dicetak seeder ke console; kata sandi semuanya `password`:

| Peran | Email | Panel |
| :-- | :-- | :-- |
| super-admin | `superadmin@seekitar.test` | ya |
| admin | `admin.staf@seekitar.test` | ya |
| user | `warga@seekitar.test` | **ditolak** (kontrol uji pembatasan peran) |

## Menjalankan test

```bash
php artisan test
```

14 file test — kontrak seeder/factory/enum/rute/state machine diuji tanpa
basis data (lihat `tests/Unit`), kontrak skema lewat basis data test
(`tests/Feature`).

## Peta struktur

```
app/
├── DataTables/        Definisi tabel panel (sumber data DataTables, terpisah dari controller)
├── Enums/             Vocabulary status/tipe (OrderStatus, VerificationLevel, …)
├── Http/
│   ├── Concerns/      ApiResponse (bentuk respons standar API)
│   ├── Controllers/
│   │   ├── Admin/     Panel web (Blade)
│   │   └── Api/V1/    REST API + subfolder Admin/ (endpoint admin API)
│   ├── Middleware/    EnsureProfileComplete, alias permission, …
│   ├── Requests/      FormRequest validasi
│   └── Resources/     Bentuk JSON keluar
├── Models/            Eloquent + docblock alasan kolom/relasi
├── Services/          OtpService, WhatsAppGateway, OrderStateMachine, BroadcastService, …
└── Support/           PhoneNumber, PlaceholderImg, SpatialSchema, …
```

## Aturan main yang sering terlewat

- **Level verifikasi adalah turunan**, bukan kolom — jangan menulis
  `verification_level` ke `users`; tulis stempel `verified*_at/by`.
  Lihat `DATABASE.md` §4.1.
- **Stempel verifikasi tulis-sekali** dan hanya berubah lewat alur
  berjejak (antrian admin / OTP / unggah ulang berkas pengguna).
- **Foto verifikasi** (KTP/selfie) hidup di disk `local` privat dan hanya
  disajikan lewat route berizin; jangan pernah meletakkannya di disk
  `public` (UU PDP).
- Aksesor foto (`avatar_url`, `Store::photo`, `Listing::images`) menjatuhkan
  nilai kosong ke placeholder — konteks verifikasi wajib membaca
  `getRawOriginal(...)`.
- Komentar/docblock menjelaskan **kenapa**, bukan apa — baca sebelum
  mengubah perilaku.

## Dokumentasi

Semua dokumen induk ada di root repositori: [`PRD.md`](../PRD.md),
[`DATABASE.md`](../DATABASE.md), [`API_DOCUMENTATION.md`](../API_DOCUMENTATION.md),
[`Server_Implementation_Guide.md`](../Server_Implementation_Guide.md),
[`TECH_STACK.md`](../TECH_STACK.md).
