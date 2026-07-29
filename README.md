# Seekitar

**Marketplace hyperlocal dua arah berbasis geolokasi**, dikunci dalam satu
wilayah kabupaten.

> _“Yang kamu butuhkan, ada di sekitar.”_

Seekitar menggabungkan dua model transaksi dalam satu aplikasi:

| Mesin | Alur |
| :-- | :-- |
| **Jelajahi** (marketplace katalog) | Penjual memajang produk/jasa → pembeli mencari & memesan |
| **Pasang Kebutuhan** (reverse marketplace) | Pembeli mengajukan kebutuhan → penyedia sekitar mengirim penawaran |

---

## Isi Repositori

```
seekitar-server/    Backend Laravel 13 (REST API + web SEO + panel admin)
seekitar_mobile/    Aplikasi Flutter (Android & iOS)
tools/dev/          Perkakas pengembangan & pemeriksa konsistensi dokumen
assets/brand/       Aset merek yang dikendalikan versi
```

## Dokumentasi

Dokumen dibaca sebagai **satu himpunan** — semuanya pada versi **2.2**.

| Dokumen | Isi | Baca saat |
| :-- | :-- | :-- |
| [`TECH_STACK.md`](TECH_STACK.md) | **Sumber kebenaran versi** & glosarium lintas lapisan | Sebelum menambah dependensi atau menamai sesuatu |
| [`PRD.md`](PRD.md) | Kebutuhan produk, alur pengguna, KPI, roadmap | Memahami *kenapa* sebuah fitur ada |
| [`DATABASE.md`](DATABASE.md) | Skema, constraint, indeks, keputusan desain | Menyentuh migrasi atau query |
| [`API_DOCUMENTATION.md`](API_DOCUMENTATION.md) | Kontrak REST & konvensi global | Membangun endpoint atau memanggilnya |
| [`Server_Implementation_Guide.md`](Server_Implementation_Guide.md) | Laravel: struktur, keamanan, job, deployment | Mengerjakan backend |
| [`Mobile_Implementation_Guide.md`](Mobile_Implementation_Guide.md) | Flutter: Riverpod 3, routing, FCM | Mengerjakan mobile |
| [`BRANDING-GUIDELINE.md`](BRANDING-GUIDELINE.md) | Identitas visual & verbal | Menyentuh UI atau materi publik |
| [`TODO_BUG.md`](TODO_BUG.md) | Daftar audit temuan | Melacak sisa pekerjaan |

## Tech Stack

| Layer | Teknologi |
| :-- | :-- |
| Backend | Laravel 13 · PHP 8.3+ · Sanctum 4 |
| Database | MySQL 8.0.34+ (Spatial) |
| Cache & Queue | Redis 7 |
| Mobile | Flutter 3.44+ · Dart 3.12+ · Riverpod 3 |
| Admin | Blade + Bootstrap 5.3.x + Yajra Datatables 13 |
| Penyimpanan | S3 / MinIO |
| Notifikasi | Firebase Cloud Messaging · WhatsApp (Twilio / Kirim WA) |

Versi lengkap & matriks kompatibilitas: [`TECH_STACK.md`](TECH_STACK.md).

---

## Menjalankan Backend

Kebutuhan: PHP 8.3+, Composer, MySQL 8.0.34+, Redis 7, Node 20+.

```bash
# 1. Basis data harus ada lebih dulu — Laravel tidak membuatnya sendiri.
mysql -u root -p -e "CREATE DATABASE seekitar
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Aplikasi
cd seekitar-server
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve
```

> ⚠️ **MySQL 8.0.34+ wajib, tidak ada alternatif.** Skema memakai `POINT SRID
> 4326`, `SPATIAL INDEX`, tipe `SET`, dan `CHECK` constraint — `config/database.php`
> sengaja hanya mendaftarkan koneksi `mysql`. Alasannya di [`DATABASE.md`](DATABASE.md) §1.

Jalankan worker antrian di terminal terpisah — tanpa ini, OTP dan broadcast
permintaan tidak akan terkirim:

```bash
php artisan queue:work redis --queue=high,default
```

### Tanpa PHP di mesin lokal

Repositori menyertakan runtime PHP mandiri untuk lingkungan terbatas:

```bash
./tools/dev/setup      # runtime PHP + dependensi Composer
./tools/dev/ddl        # DDL MySQL dari migrasi, tanpa perlu server
```

> ⚠️ `./tools/dev/serve`, `./tools/dev/test`, dan `artisan migrate` tetap
> **membutuhkan server MySQL 8.0.34+**. Runtime mandiri ini hanya
> menggantikan PHP, bukan basis datanya. Untuk memeriksa skema tanpa MySQL,
> pakai `./tools/dev/ddl` + `node tools/dev/check-mysql.mjs`.

Detailnya di [`tools/dev/README.md`](tools/dev/README.md).

## Menjalankan Mobile

Kebutuhan: Flutter 3.44+.

```bash
cd seekitar_mobile
flutter pub get
dart run build_runner build --delete-conflicting-outputs
flutter run --dart-define-from-file=config/dev.json
```

> ⚠️ `build_runner` **wajib** dijalankan sebelum `flutter run`. Berkas `.g.dart`
> tidak di-commit, jadi tanpa langkah ini build gagal dengan ratusan galat
> "tidak ditemukan".
>
> Emulator Android memetakan host ke `10.0.2.2`, bukan `localhost`.

---

## Menjaga Konsistensi Dokumen

Dokumen saling merujuk secara ketat. Dua puluh pemeriksa otomatis menjaga agar
perubahan di satu berkas tidak diam-diam membuat berkas lain keliru:

```bash
for c in versions structure datamodel api backend mobile brand prd terms security deploy dbperf docs schema-drift mysql seeders services http admin-menu peta; do
  node tools/dev/check-$c.mjs || exit 1
done
```

Delapan belas nama pertama menjaga konsistensi **dokumen ↔ kode**; dua nama
terakhir — `admin-menu` dan `peta` — satu lapis lebih dalam: me-render seluruh
halaman admin sebagai `admin` dan `super-admin`, menjalankan setiap kelas
DataTables ke basis data tiruan, dan menguji direktif peta, sehingga kelas bug
yang hanya muncul di browser ikut tertangkap.

Jalankan sebelum commit yang menyentuh dokumen. Semuanya keluar dengan status
bukan-nol saat gagal, sehingga cocok dipakai di CI.

## Berkontribusi

Lihat [`CONTRIBUTING.md`](CONTRIBUTING.md).
