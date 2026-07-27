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
for c in versions structure datamodel api backend mobile brand prd terms security deploy dbperf docs schema-drift mysql seeders; do
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
