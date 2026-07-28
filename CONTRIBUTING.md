# Panduan Berkontribusi

## Sebelum Menulis Kode

Baca tiga hal ini lebih dulu — urutannya disengaja:

1. **[`TECH_STACK.md`](TECH_STACK.md) §6 — Glosarium.** Satu konsep punya nama
   berbeda di UI, database, dan API. Menamai sesuatu di luar glosarium akan
   membuat pencarian kode gagal di kemudian hari.
2. **[`DATABASE.md`](DATABASE.md) §8A — Keputusan Desain.** Berisi usulan yang
   sudah **ditolak** beserta alasannya. Menghemat waktu mengusulkan ulang hal
   yang sudah dipertimbangkan.
3. **Panduan implementasi** sesuai bagian yang dikerjakan (server/mobile).

## Alur Kerja

```bash
git switch -c fitur/nama-singkat
# ... kerjakan ...
```

Sebelum commit:

```bash
# 1. Uji kode
cd seekitar-server && php artisan test
cd seekitar_mobile && flutter analyze && flutter test

# 2. Periksa konsistensi dokumen (bila menyentuh berkas .md)
for c in versions structure datamodel api backend mobile brand prd terms security deploy dbperf docs schema-drift mysql seeders services http; do
  node tools/dev/check-$c.mjs || exit 1
done
```

## Aturan Penting

### Dokumen adalah bagian dari kode

Mengubah skema atau kontrak API **wajib** disertai pembaruan dokumennya dalam
commit yang sama. Dokumen yang tertinggal lebih berbahaya daripada tidak ada
dokumen — orang akan memercayainya.

| Mengubah | Perbarui juga |
| :-- | :-- |
| Migrasi / kolom | `DATABASE.md` |
| Endpoint / bentuk response | `API_DOCUMENTATION.md` |
| Dependensi | `TECH_STACK.md` §1–3 |
| Nama entitas | `TECH_STACK.md` §6 |

### Jangan melewati pemeriksa

Jika sebuah pemeriksa gagal, ada dua kemungkinan: perubahan Anda memang
membuat dokumen tidak konsisten, **atau** aturan pemeriksanya sudah usang.
Perbaiki penyebabnya — jangan menghapus aturannya.

### Yang mudah terlewat

| Hal | Kenapa penting |
| :-- | :-- |
| `POINT(longitude latitude)` | Terbalik dari kebiasaan "lat, lng". Salah urutan tidak memicu error, hanya hasil yang keliru |
| Normalisasi nomor telepon | Tanpa itu, satu nomor bisa membuat beberapa akun meski kolomnya `UNIQUE` |
| `MBRContains` sebelum `ST_Distance_Sphere` | Tanpa pra-filter, indeks spasial tidak terpakai sama sekali |
| Semua aksi tulis pakai FormRequest | Jangan `$request->validate()` di controller |
| `ref` pada Riverpod 3 | Subclass `Ref` hasil codegen sudah dihapus |
| `if (!mounted) return;` setelah `await` | Penyebab crash paling umum di Flutter |

### Commit

Format: `<tipe>: <ringkasan singkat>` — `feat`, `fix`, `docs`, `refactor`,
`test`, `chore`.

Jelaskan **kenapa**, bukan hanya *apa*. Jika sebuah keputusan diambil setelah
menolak alternatif, tulis alasannya di badan commit; itulah konteks yang
dicari orang enam bulan kemudian.

## Pengujian

| Jenis | Perintah | Wajib untuk |
| :-- | :-- | :-- |
| Feature (API) | `php artisan test` | Setiap endpoint |
| Unit (Service) | `php artisan test --testsuite=Unit` | Setiap Service |
| Widget | `flutter test` | Halaman dengan state |
| Integration | `flutter test integration_test` | Alur utama |

### Menyiapkan MySQL untuk test

Seekitar **hanya mendukung MySQL 8.0.34+** — tidak ada jalur SQLite. Skema
memakai `POINT SRID 4326`, `SPATIAL INDEX`, tipe `SET`, dan `CHECK`
constraint, yang semuanya tidak ada di engine lain. Menguji di SQLite berarti
menguji skema yang berbeda dari produksi.

```sql
CREATE DATABASE seekitar_testing
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

> ⚠️ Nama basis data test **wajib mengandung `test`**. `Tests\RefreshesDatabase`
> menolak berjalan jika tidak — `migrate:fresh` men-DROP semua tabel, dan salah
> konfigurasi akan menghapus data pengembangan tanpa peringatan.

### Masuk ke panel admin

```bash
php artisan key:generate      # WAJIB — lihat catatan di bawah
php artisan migrate --seed
```

Buka `/admin/login`. Ada dua kelompok akun:

**1. Akun pemilik** — dibuat di semua environment, kredensial dari `.env`:

| Kunci | Default |
| :-- | :-- |
| `SEEKITAR_SUPER_ADMIN_EMAIL` | `admin@seekitar.test` |
| `SEEKITAR_SUPER_ADMIN_PASSWORD` | `password` |

**2. Akun contoh per peran** — HANYA `local`/`testing`, sandi semuanya
`password`:

| Peran | Email | Akses panel |
| :-- | :-- | :-- |
| `super-admin` | `superadmin@seekitar.test` | ya — 12 permission |
| `admin` | `admin.staf@seekitar.test` | ya — 10 permission |
| `user` | `warga@seekitar.test` | **ditolak** (akun kontrol) |

Perbedaan `super-admin` dan `admin`: hanya super-admin yang punya
`manage-users` dan `manage-settings`. Admin biasa tidak bisa mengubah sesama
admin atau menyentuh pengaturan sistem — menunya pun tidak muncul.

Akun `warga@seekitar.test` sengaja ada untuk **membuktikan penolakan
bekerja**: kredensialnya benar, tetapi `canAccessAdminPanel()` menolaknya dan
sesinya langsung dibuang.

> ⚠️ **`APP_KEY` kosong = login selalu gagal, tanpa pesan error.** Sesi tidak
> bisa dienkripsi, sehingga browser dilempar kembali ke halaman masuk seolah
> kredensialnya salah. Ini penyebab paling umum "sudah di-seed tapi tidak
> bisa masuk". `./tools/dev/setup` kini menolak selesai bila APP_KEY kosong.

> ⚠️ Kata sandi hanya disetel saat akun **baru dibuat**. Menjalankan
> `db:seed` ulang tidak mengembalikannya ke default — itu disengaja, supaya
> sandi produksi yang sudah diganti tidak tertimpa. Bila lupa, ubah lewat
> tinker: `User::where('email', '...')->update(['password' => Hash::make('baru')])`.

Setelah masuk, kata sandi bisa diganti sendiri lewat **menu profil → Ubah Kata
Sandi** (`/admin/kata-sandi`). Halaman itu menuntut sandi lama, minimal 12
karakter, dan menolak kata umum seperti `password` atau `seekitar2026`.
Berhasil mengganti akan **mengeluarkan sesi di perangkat lain** dan mencabut
token API akun tersebut.

### Menambah menu baru di panel admin

Menu dan route **wajib** memakai permission yang sama. Kalau berbeda, menu akan
tampil lalu menolak dengan 403, atau tersembunyi padahal admin berhak — dan
tidak ada error yang muncul di mana pun.

1. Tambahkan route di `routes/admin.php` di dalam
   `Route::middleware('permission:nama-izin')`.
2. Tambahkan butir menu di `resources/views/admin/partials/sidebar.blade.php`
   dibungkus `@can('nama-izin')` yang **sama persis**.
3. Kalau halamannya memakai Datatables, endpoint `.../data`-nya juga harus
   diberi `permission:` yang sama — kalau tidak, JSON-nya bisa ditarik langsung
   meski menunya tersembunyi.
4. Jalankan penjaganya:

```bash
node tools/dev/check-admin-menu.mjs
node tools/dev/check-peta.mjs
```

Checker itu membandingkan `@can` di sidebar dengan middleware hasil
`route:list`, lalu **me-render seluruh halaman admin sebagai `admin` dan
`super-admin`** untuk memastikan menu benar-benar berbeda per izin.

Untuk memeriksa DDL yang dihasilkan migrasi **tanpa** server MySQL:

```bash
./tools/dev/ddl              # cetak seluruh CREATE TABLE / ALTER TABLE
node tools/dev/check-mysql.mjs
```

## Melaporkan Masalah

Sertakan: langkah reproduksi, hasil yang diharapkan vs yang terjadi, versi
(Laravel/Flutter/MySQL), dan potongan log yang relevan.

Untuk masalah keamanan, **jangan** buka issue publik — kirim ke
`security@seekitar.id`.
