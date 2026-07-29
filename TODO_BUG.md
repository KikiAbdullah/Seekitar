# 🔍 DAFTAR KEKURANGAN, INKONSISTENSI, & KESALAHAN — SEEKITAR

---

## A. INKONSISTENSI VERSI & TECH STACK

| #   | Lokasi                                                             | Issue                                                                                                 | Rekomendasi Perbaikan | Status |
| --- | ------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| 1   | Server_Implementation_Guide: Target Laravel 13                     | PRD & Database menyebut Laravel 11. Laravel 13 belum rilis (per Juli 2026 kemungkinan Laravel 11/12). | Konsisten gunakan **Laravel 11** (LTS) atau tentukan versi pasti. | ❌ Ditolak — composer.lock mengunci laravel/framework v13.22.0 (TECH_STACK §1) |
| 2   | Server_Implementation_Guide: PHP 8.3.30                            | Versi minor terlalu spesifik; gunakan minimal PHP 8.3.x.                                              | Ubah ke `PHP 8.3+` atau `PHP 8.3.x (minimal 8.3.0)`. | ✅ Selesai |
| 3   | Server_Implementation_Guide: Bootstrap 5                           | Tidak disebutkan versi minor.                                                                         | Tentukan `Bootstrap 5.3.x`. | ✅ Selesai |
| 4 | Server_Implementation_Guide: Yajra Datatables 11.x | Spatie Permission 6.x | Keduanya versi mayor, tapi tidak ada info kompatibilitas dengan Laravel 11. &#124; Tambahkan catatan kompatibilitas: `Yajra Datatables ^11.0` dan `Spatie Permission ^6.0` compatible dengan Laravel 11. | ✅ Selesai |
| 5   | Mobile_Implementation_Guide: Flutter 3.19+                         | PRD menyebut Flutter 3.19+; tidak ada di dokumen lain.                                                | Konsisten di semua dokumen. | ✅ Selesai |
| 6   | Database.md: MySQL 8.0.34+                                         | Server_Implementation_Guide menyebut MySQL 8.0                                                        | Konsisten: gunakan `MySQL 8.0+` atau `MySQL 8.0.34+` di semua dokumen. | ✅ Selesai |
| 7   | PRD: Redis 7                                                       | Server_Implementation_Guide sebut Redis 7, Mobile Guide tidak sebut Redis.                            | Tambahkan Redis di Mobile Guide sebagai bagian dari arsitektur backend. | ✅ Selesai |
| 8 | Mobile_Implementation_Guide: GoRouter ^14.0.0 | Riverpod 2.x | GoRouter 14 membutuhkan Riverpod 2.x; pastikan kompatibilitas. &#124; Tambahkan catatan kompatibilitas. | ✅ Selesai |
| 9   | API_DOCUMENTATION.md: Tidak ada base URL untuk environment berbeda | Hanya `https://api.seekitar.id/api/v1`.                                                               | Tambahkan `staging-api.seekitar.id` dan `localhost:8000/api/v1` untuk development. | ✅ Selesai |
| 10  | Server_Implementation_Guide: Sanctum & Token                       | Tidak disebutkan konfigurasi `SANCTUM_STATEFUL_DOMAINS` untuk API.                                    | Tambahkan panduan konfigurasi Sanctum untuk API (stateless) dan Web (stateful). | ✅ Selesai |

---

## B. STRUKTUR PROYEK & ORGANISASI FILE

| #   | Lokasi                         | Issue                                                                         | Rekomendasi Perbaikan | Status |
| --- | ------------------------------ | ----------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- | --- |
| 11  | Server_Implementation_Guide §4 | Struktur proyek tidak mencakup `app/Services/` untuk logika bisnis.           | Tambahkan `app/Services/` dengan contoh `BroadcastService`, `GeolocationService`, `NotificationService`. | ✅ Selesai |
| 12  | Server_Implementation_Guide §4 | Tidak ada `app/Enums/` untuk status, tipe, dll.                               | Tambahkan `app/Enums/OrderStatus.php`, `RequestStatus.php`, `OfferStatus.php`, `StoreType.php`, `VerificationLevel.php`. | ✅ Selesai |
| 13  | Server_Implementation_Guide §4 | Tidak ada `app/DataTables/` di daftar struktur (hanya disebut di §10).        | Tambahkan `app/DataTables/` di struktur proyek. | ✅ Selesai |
| 14  | Server_Implementation_Guide §4 | Tidak ada `database/seeders/` disebut.                                        | Tambahkan di struktur proyek (meskipun standar Laravel). | ✅ Selesai |
| 15  | Server_Implementation_Guide §4 | Tidak ada `app/Jobs/` padahal ada broadcast job.                              | Tambahkan `app/Jobs/BroadcastRequestJob.php`. | ✅ Selesai |
| 16  | Server_Implementation_Guide §4 | Tidak ada `app/Listeners/` padahal ada event.                                 | Tambahkan `app/Listeners/SendOfferAcceptedNotification.php`, dll. | ✅ Selesai |
| 17  | Mobile_Implementation_Guide §3 | Struktur `core/widgets/` dan `presentation/widgets/` ambigu.                  | Jelaskan perbedaan: `core/widgets/` = global reusable (button, loading), `presentation/widgets/` = fitur-spesifik (ListingCard, RequestCard). | ✅ Selesai |
| 18  | Mobile_Implementation_Guide §3 | Tidak ada folder `core/services/` untuk service locator.                      | Tambahkan `core/services/` untuk `LocationService`, `NotificationService`, `AnalyticsService`. | ✅ Selesai |
| 19  | Mobile_Implementation_Guide §3 | Tidak ada folder `presentation/providers/` di struktur (hanya disebut di §4). | Tambahkan `presentation/providers/` dengan file-file provider. | ✅ Selesai |
| 20  | Mobile_Implementation_Guide §3 | Tidak ada folder `core/constants/route_constants.dart` untuk GoRouter.        | Tambahkan untuk menghindari hardcode string. | ✅ Selesai |

---

## C. INKONSISTENSI DATA MODEL (DATABASE vs PRD vs API)

| #   | Lokasi                       | Issue                                                                                 | Rekomendasi Perbaikan | Status |
| --- | ---------------------------- | ------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------- |
| 21  | Database `users` vs PRD      | PRD sebut `verification_level` 1-3, Database sebut 1-3. API docs tidak sebut level 4. | Konsisten: gunakan 1-3 di MVP. | ✅ Selesai — ditegaskan "hanya 1–3" agar tak ada yang mengarang level 4 |
| 22  | Database `stores`            | `store_type` menggunakan SET('goods','services','rental')                             | PRD sebut bisa kombinasi; SET di MySQL kurang fleksibel untuk kombinasi. Ubah ke `JSON` atau tabel pivot `store_types`. | ❌ Ditolak — SET memang tipe MySQL untuk kombinasi (DATABASE §8A) |
| 23  | Database `stores`            | `category_ids` JSON                                                                   | PRD sebut "comma-separated" tapi Database sebut JSON. Konsisten: pakai JSON. | ✅ Selesai |
| 24  | Database `listings`          | `price` DECIMAL(12,2) NULL                                                            | PRD sebut service bisa null; product/rental wajib. Tambahkan CHECK constraint: `(listing_type != 'product' OR price IS NOT NULL)`. | ✅ Selesai |
| 25  | Database `listings`          | `stock_qty` INT UNSIGNED NULL, `slot` TINYINT UNSIGNED NULL                           | Tidak ada constraint bahwa `stock_qty` wajib untuk product/rental, `slot` wajib untuk service. Tambahkan CHECK. | ✅ Selesai |
| 26 | Database `customer_requests` | `accepted_offer_id` FK ke `offers` | Di PRD, setelah offer diterima, request status `closed`. Di Database, tidak ada trigger untuk otomatis reject offer lain. &#124; Tambahkan Observer atau trigger untuk reject offers lain saat satu diterima. | ✅ Selesai |
| 27 | Database `orders` | `payment_method` ENUM('cod','transfer') | PRD sebut "transfer langsung (nomor rekening ditampilkan)". Tidak ada kolom `bank_account` atau `payment_proof`. &#124; Tambahkan `payment_proof_url` (untuk bukti transfer) dan `payment_confirmed_at`. | ✅ Selesai |
| 28  | Database `orders`            | Tidak ada `shipping_address` atau `delivery_method`.                                  | Untuk COD/ambil di tempat, perlu alamat pengiriman. Tambahkan `shipping_address` TEXT NULL. | ✅ Selesai |
| 29  | Database `orders`            | Tidak ada `order_number` human-readable.                                              | Tambahkan `order_number` VARCHAR(20) UNIQUE untuk referensi customer (misal: `SKT-20260727-0001`). | ✅ Selesai |
| 30  | Database `reviews`           | `reviewee_id` merujuk ke `users`, tapi rating toko seharusnya ke `stores`.            | Perbaiki: `reviewee_id` seharusnya `store_id` (FK ke `stores`) agar perhitungan rating toko akurat. | ❌ Dikoreksi — store_id DITAMBAHKAN, reviewee_id tetap; ulasan dua arah PRD §5.5 (DATABASE §8A) |
| 31  | Database `disputes`          | `reason` VARCHAR(100) tanpa ENUM.                                                     | PRD sebut enum: `barang_tidak_sesuai`, `jasa_tidak_profesional`, dll. Ubah ke ENUM atau tambahkan CHECK. | ✅ Selesai |
| 32  | Database `categories`        | `parent_id` FK dengan `ON DELETE SET NULL`                                            | PRD sebut kategori tidak boleh dihapus jika ada request terkait (`RESTRICT`). Konsisten: `ON DELETE RESTRICT` untuk semua relasi dengan `customer_requests`. | ✅ Selesai |
| 33  | Database `customer_requests` | `expires_at` default 24 jam, tapi tidak ada kolom `extended_at` untuk perpanjangan.   | Tambahkan `extended_at` TIMESTAMP NULL jika pembeli perpanjang masa aktif. | ✅ Selesai |
| 34  | Database `offers`            | `estimation_time` VARCHAR(100)                                                        | Sebaiknya `estimated_days` TINYINT atau `estimated_hours` SMALLINT untuk sorting/analitik. Tambahkan kolom terpisah. | ❌ Dikoreksi — estimated_hours ditambahkan berdampingan, estimation_time tetap (DATABASE §8A) |
| 35  | Database `offers`            | Tidak ada `expires_at` untuk penawaran.                                               | Tambahkan `expires_at` TIMESTAMP (misal 48 jam) agar penawaran kadaluarsa otomatis. | ✅ Selesai |
| 36  | Database `stores`            | `rating_avg` DECIMAL(3,2)                                                             | Jika rating 1-5, DECIMAL(3,2) cukup (maks 5.00). Tapi `total_reviews` INT UNSIGNED. Tambahkan `rating_count` sebagai alias `total_reviews`. | ❌ Ditolak — alias rating_count = dua sumber kebenaran (DATABASE §8A) |
| 37  | Database `users`             | `location` POINT SRID 4326 NULL                                                       | PRD sebut lokasi default wajib setelah registrasi. Ubah ke `NOT NULL`. | ❌ Ditolak — baris user ada sebelum lokasi diisi (alur OTP); ditegakkan middleware (DATABASE §8A) |
| 38  | Database `stores`            | `service_radius_km` DECIMAL(5,2) DEFAULT 5.00                                         | PRD sebut default 15 km. Konsisten: 15 km. | ❌ Ditolak — tertukar dengan customer_requests.radius_km; toko 5 km sudah benar (DATABASE §8A) |
| 39  | Database `customer_requests` | `radius_km` DECIMAL(5,2) DEFAULT 15.00                                                | PRD sebut default 15 km. Sudah sesuai. | ✅ Selesai — sudah sesuai, diverifikasi ulang (DEFAULT 15.00) |
| 40  | Database `orders`            | Tidak ada `cancelled_at` dan `cancelled_by` untuk audit.                              | Tambahkan `cancelled_at` TIMESTAMP NULL dan `cancelled_by` CHAR(36) NULL (FK users). | ✅ Selesai |
| 41  | Database `stores`            | `verification_status` ENUM('pending','verified','rejected')                           | Tambahkan `rejected_reason` TEXT NULL untuk catatan penolakan. | ✅ Selesai |
| 42  | Database `users`             | Tidak ada `ktp_image` dan `selfie_image` URL.                                         | Tambahkan `ktp_image` VARCHAR(500) NULL dan `selfie_image` VARCHAR(500) NULL. | ✅ Selesai |
| 43  | Database `customer_requests` | Tidak ada `images` (foto pendukung).                                                  | Tambahkan `images` JSON NULL (maks 3 foto). | ✅ Selesai |
| 44  | Database `listings`          | `images` JSON, tapi tidak ada validasi min 1, max 5.                                  | Di Laravel, gunakan FormRequest validation; tambahkan catatan di Database.md. | ✅ Selesai |

---

## D. INKONSISTENSI DI API DOCUMENTATION

| #   | Lokasi                                        | Issue                                                                                   | Rekomendasi Perbaikan | Status |
| --- | --------------------------------------------- | --------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- | ------------------ |
| 45  | API §2.1 `POST /auth/request-otp`             | Tidak disebutkan rate limit di response header.                                         | Tambahkan `X-RateLimit-Remaining`, `X-RateLimit-Reset` di response. | ✅ Selesai |
| 46  | API §2.2 `POST /auth/verify-otp`              | Response tidak menyertakan `refresh_token` atau `expires_in`.                           | Tambahkan `expires_in` (detik) dan `token_type: Bearer`. | ✅ Selesai |
| 47 | API §2.4 `PATCH /auth/profile` | Body menggunakan `multipart/form-data`; tidak disebutkan validasi file (mime, dimensi). | Tambahkan: `avatar: image &#124; mimes:jpeg,png,jpg &#124; max:2048 &#124; dimensions:min_width=200,min_height=200`. | ✅ Selesai |
| 48 | API §2.5 `POST /auth/verification/ktp` | Tidak disebutkan validasi file KTP dan selfie. | Tambahkan validasi: `ktp_image: required &#124; image &#124; mimes:jpeg,png &#124; max:5120`, `selfie_image: required &#124; image &#124; mimes:jpeg,png &#124; max:5120`. | ✅ Selesai |
| 49  | API §3.1 `POST /stores`                       | `store_type` menggunakan array? Database menggunakan SET/JSON.                          | Konsisten: di API gunakan array `["goods", "services"]`. | ✅ Selesai |
| 50  | API §3.1 `POST /stores`                       | `operating_hours` struktur tidak dijelaskan detail.                                     | Tambahkan contoh struktur JSON dengan semua hari. | ✅ Selesai |
| 51  | API §3.2 `GET /stores/nearby`                 | Parameter `type` menggunakan `services` tapi database `service` (tunggal).              | Konsisten: `goods`, `services`, `rental` atau `goods`, `service`, `rental`. Pilih salah satu. | ✅ Selesai |
| 52  | API §4.1 `POST /listings`                     | Tidak ada endpoint untuk upload gambar terpisah.                                        | Tambahkan `POST /uploads/images` untuk upload file sebelum submit listing. | ✅ Selesai |
| 53  | API §4.2 `GET /listings`                      | Sort parameter `nearest`, `cheapest`, `newest`; `nearest` membutuhkan lat/lng.          | Jelaskan bahwa `lat`/`lng` wajib untuk `sort=nearest`. | ✅ Selesai |
| 54  | API §5.1 `POST /requests`                     | Tidak ada field `images` (foto pendukung).                                              | Tambahkan `images` (array URL atau multipart). | ✅ Selesai |
| 55  | API §5.1 `POST /requests`                     | `required_date` format `2026-07-28T10:00:00+07:00`.                                     | Sebaiknya gunakan ISO 8601 UTC: `2026-07-28T03:00:00Z`. | ✅ Selesai |
| 56  | API §5.2 `GET /requests`                      | Tidak ada paginasi parameter (`page`, `per_page`).                                      | Tambahkan `page`, `per_page` (default 15). | ✅ Selesai |
| 57  | API §6.1 `POST /requests/{request_id}/offers` | Tidak ada validasi bahwa offer price harus >= budget_min dan <= budget_max.             | Tambahkan validasi otomatis di backend; jika di luar range, return 422. | ✅ Selesai |
| 58  | API §6.3 `PATCH /offers/{offer_id}/accept`    | Tidak disebutkan bahwa offer lain otomatis rejected.                                    | Tambahkan di deskripsi efek samping. | ✅ Selesai |
| 59  | API §7.1 `POST /orders`                       | Jika dari listing, `listing_id` wajib. Jika dari offer, tidak perlu.                    | Jelaskan logika: `listing_id` XOR `offer_id` wajib. | ✅ Selesai |
| 60  | API §7.1 `POST /orders`                       | Tidak ada field `shipping_address` untuk COD.                                           | Tambahkan `shipping_address` opsional. | ✅ Selesai |
| 61  | API §7.2 `PATCH /orders/{order_id}/status`    | Tidak ada state machine diagram di API docs.                                            | Tambahkan state diagram dan status yang valid per role (pembeli/penjual). | ✅ Selesai |
| 62  | API §8.1 `POST /orders/{order_id}/review`     | Tidak ada validasi bahwa order sudah `selesai` dan belum pernah review.                 | Tambahkan di deskripsi. | ✅ Selesai |
| 63  | API §9.1 `POST /orders/{order_id}/disputes`   | Tidak ada validasi bahwa order status `dispute` berubah otomatis.                       | Tambahkan efek samping: order status berubah menjadi `dispute`. | ✅ Selesai |
| 64  | API §10 Admin                                 | Tidak ada endpoint `GET /admin/stores`, `GET /admin/users` di API docs.                 | Tambahkan semua resource admin: users, stores, listings, requests, offers, orders, reviews. | ✅ Selesai — ruang lingkupnya dikoreksi: admin API memang sengaja ramping (verifications, users+block, categories, disputes, settings); manajemen resource lain dijalankan lewat panel web admin. API documentation §10 kini menuliskan endpoint yang benar-benar ada beserta permission-nya |
| 65  | API §10 Admin                                 | Tidak ada endpoint untuk `POST /admin/settings` di API docs.                            | Tambahkan endpoint settings (hanya super-admin). | ✅ Selesai |
| 66  | API §11 Error                                 | Error 409 Conflict tidak dijelaskan contoh body.                                        | Tambahkan contoh: `{"success":false,"message":"Anda sudah mengirim penawaran"}`. | ✅ Selesai |
| 67  | API §11 Error                                 | Tidak ada kode 423 Locked untuk akun yang dibekukan.                                    | Tambahkan 423 untuk akun nonaktif. | ✅ Selesai |
| 68  | API                                           | Tidak ada endpoint `POST /auth/logout` di API docs.                                     | Tambahkan: `POST /auth/logout` untuk invalidate token. | ✅ Selesai |
| 69  | API                                           | Tidak ada endpoint `POST /auth/fcm-token` untuk registrasi FCM.                         | Tambahkan: `POST /auth/fcm-token` dengan body `{"fcm_token":"..."}`. | ✅ Selesai |
| 70  | API                                           | Tidak ada endpoint untuk wishlist/favorit.                                              | Tambahkan `POST /listings/{id}/favorite`, `GET /favorites`. | ✅ Selesai |

---

## E. IMPLEMENTASI BACKEND (LARAVEL) — KURANG DETAIL

| #   | Lokasi                            | Issue                                                                                                           | Rekomendasi Perbaikan | Status |
| --- | --------------------------------- | --------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- | --- |
| 71  | Server_Implementation_Guide §5    | Middleware `role:admin` tidak dijelaskan cara registrasi di Kernel.                                             | Tambahkan di `App\Http\Kernel` atau `bootstrap/app.php` (Laravel 11) dengan `->withMiddleware()`. | ✅ Selesai |
| 72  | Server_Implementation_Guide §6.2  | Guard `sanctum` dan `web`; Spatie Permission default guard `web`. API perlu `sanctum`.                          | Set default guard di `config/permission.php` = `sanctum` atau buat permission terpisah per guard. | ✅ Selesai |
| 73  | Server_Implementation_Guide §6.3  | Policies untuk Admin tidak dijelaskan.                                                                          | Tambahkan `AdminPolicy` atau gunakan `Gate::before` untuk super-admin. | ✅ Selesai |
| 74  | Server_Implementation_Guide §7    | `routes/web.php` tidak ada middleware `auth` untuk admin prefix.                                                | Tambahkan: `Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(...)`. | ✅ Selesai |
| 75  | Server_Implementation_Guide §7    | Tidak ada route untuk Datatables AJAX (`/admin/categories/data`).                                               | Tambahkan di web.php: `Route::get('categories/data', [CategoryController::class, 'data'])->name('admin.categories.data');`. | ✅ Selesai |
| 76  | Server_Implementation_Guide §9.1  | Dashboard chart tidak dijelaskan implementasi (Chart.js).                                                       | Tambahkan contoh kode Chart.js dan endpoint API untuk data chart. | ✅ Selesai |
| 77  | Server_Implementation_Guide §9.2  | Manajemen Kategori tidak ada validasi untuk mencegah loop parent-child.                                         | Tambahkan validasi: `parent_id` tidak boleh sama dengan `id` dan tidak boleh menjadi descendant dari dirinya sendiri. | ✅ Selesai |
| 78  | Server_Implementation_Guide §9.3  | Verifikasi Pengguna: tidak ada logika untuk menaikkan `verification_level` setelah approve.                     | Tambahkan: setelah approve, set `verification_level = 2`; setelah reject, set `verification_level = 0` atau `1`. | ✅ Selesai — kolom level kemudian dihapus: approve kini men-stempel `verified2_by/at` (level 2 = turunan stempel itu, DATABASE.md §4.1); reject hanya membuka ulang antrian (`ktp_submitted_at = NULL`), stempel lama tak disentuh |
| 79  | Server_Implementation_Guide §9.3  | Verifikasi Toko: tidak ada logika jika toko ditolak.                                                            | Tambahkan: toko `verification_status = 'rejected'`, kirim notifikasi ke pemilik. | ✅ Selesai |
| 80  | Server_Implementation_Guide §9.4  | Manajemen Pengguna: tidak ada filter `verification_level` di Datatables.                                        | Tambahkan filter dropdown untuk `verification_level`. | ✅ Selesai — filter level kini hidup di API admin (`whereVerificationLevel`, satu definisi bersama); panel web sengaja tidak lagi menampilkan level (DATABASE.md §4.1), tabelnya memakai ikon centang KTP + filter status blokir |
| 81  | Server_Implementation_Guide §9.5  | Edit Toko: `operating_hours` JSON editor tidak dijelaskan.                                                      | Tambahkan contoh UI menggunakan komponen JSON atau form terstruktur per hari. | ✅ Selesai |
| 82  | Server_Implementation_Guide §9.6  | Manajemen Listing: tidak ada fitur untuk mengubah status listing (active/hidden).                               | Tambahkan tombol "Aktifkan/Nonaktifkan" di aksi. | ✅ Selesai |
| 83  | Server_Implementation_Guide §9.7  | Manajemen Permintaan: tidak ada aksi untuk memperpanjang expired request.                                       | Tambahkan fitur "Perpanjang" untuk admin (opsional). | ✅ Selesai |
| 84  | Server_Implementation_Guide §9.10 | Resolve Dispute: tidak ada logika untuk mengubah status order.                                                  | Tambahkan: setelah dispute resolved, order status bisa `selesai` atau `dibatalkan` sesuai keputusan admin. | ✅ Selesai |
| 85  | Server_Implementation_Guide §9.12 | Pengaturan Sistem: tidak disebutkan tabel `settings`.                                                           | Buat migration `settings` dengan key-value store. | ✅ Selesai |
| 86  | Server_Implementation_Guide §10   | Controller `CategoryController` tidak ada method `edit` dan `create` (hanya index, store, update, destroy).     | Tambahkan `create()` dan `edit()` jika menggunakan view terpisah (bukan modal). | ✅ Selesai |
| 87  | Server_Implementation_Guide §10   | Controller tidak menggunakan `FormRequest` untuk semua aksi.                                                    | Pastikan semua store/update menggunakan FormRequest. | ✅ Selesai |
| 88  | Server_Implementation_Guide §11   | `CategoryRequest` tidak ada validasi `icon` harus valid FontAwesome.                                            | Tambahkan rule `in:fa-users,fa-store,...` atau custom rule. | ✅ Selesai |
| 89  | Server_Implementation_Guide §13   | Observers & Events: tidak dijelaskan secara detail.                                                             | Tambahkan daftar Observer: `StoreObserver`, `ListingObserver`, `OrderObserver`, `ReviewObserver`. | ✅ Selesai |
| 90  | Server_Implementation_Guide §14   | Jobs & Queue: tidak dijelaskan detail `BroadcastRequestJob`.                                                    | Tambahkan implementasi lengkap: query toko, kirim notifikasi, log. | ✅ Selesai |
| 91  | Server_Implementation_Guide §15   | Notifikasi Push: tidak ada implementasi FCM di Laravel.                                                         | Tambahkan `app/Notifications/RequestBroadcastNotification.php` dengan FCM channel. | ✅ Selesai |
| 92  | Server_Implementation_Guide §15   | WhatsApp OTP: tidak disebutkan library yang digunakan.                                                          | Tambahkan: `laravel-notification-channels/whatsapp` atau custom dengan Twilio API. | ✅ Selesai |
| 93  | Server_Implementation_Guide §16   | Geospasial: query radius menggunakan `ST_Distance_Sphere`; tidak ada contoh dengan Eloquent scope.              | Tambahkan scope `scopeNearby($query, $lat, $lng, $radiusKm)` di model Store dan Request. | ✅ Selesai |
| 94  | Server_Implementation_Guide §17   | API Response: tidak ada standarisasi response untuk web (admin).                                                | Tambahkan trait `ApiResponse` dan `WebResponse` untuk konsistensi. | ✅ Selesai |
| 95  | Server_Implementation_Guide §19.2 | Seeder RolesAndPermissionsSeeder: tidak ada permission `manage-settings` di daftar.                             | Tambahkan `manage-settings`. | ✅ Selesai |
| 96  | Server_Implementation_Guide §19.2 | Seeder tidak ada `DatabaseSeeder` yang memanggil semua seeder.                                                  | Tambahkan `DatabaseSeeder` dengan `$this->call([...])`. | ✅ Selesai |
| 97  | Server_Implementation_Guide §20   | Testing: tidak ada detail test apa saja.                                                                        | Tambahkan: Feature Test untuk API (Auth, Store, Listing, Request, Offer, Order), Unit Test untuk Service. | ✅ Selesai |
| 98  | Server_Implementation_Guide §21   | Deployment: tidak ada detail environment variables.                                                             | Tambahkan daftar `.env` required: `DB_*`, `REDIS_*`, `FCM_*`, `TWILIO_*`, `AWS_*`, `APP_URL`, dll. | ✅ Selesai |
| 99  | Server_Implementation_Guide §22.2 | Blade `categories/index.blade.php` menggunakan `$dataTable->table()` tapi tidak ada `$dataTable` di controller. | Tambahkan di controller index: `return view('admin.categories.index', ['dataTable' => app(CategoryDataTable::class)]);` | ✅ Selesai |
| 100 | Server_Implementation_Guide §22.2 | Modal untuk create/edit menggunakan satu form; tidak ada logika untuk edit mode.                                | Tambahkan JavaScript untuk isi modal saat edit. | ✅ Selesai |
| 101 | Server_Implementation_Guide       | Tidak ada panduan untuk Laravel Telescope/Debugbar untuk development.                                           | Tambahkan konfigurasi `laravel/telescope` untuk development. | ✅ Selesai |
| 102 | Server_Implementation_Guide       | Tidak ada panduan untuk cronjob/scheduler.                                                                      | Tambahkan: `php artisan schedule:run` untuk menutup expired requests. | ✅ Selesai |
| 103 | Server_Implementation_Guide       | Tidak ada panduan untuk queue worker.                                                                           | Tambahkan: `php artisan queue:work redis --queue=broadcast,default` dan Supervisor config. | ✅ Selesai |

---

## F. IMPLEMENTASI MOBILE (FLUTTER) — KURANG DETAIL

| #   | Lokasi                            | Issue                                                                                            | Rekomendasi Perbaikan | Status |
| --- | --------------------------------- | ------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------- | --- |
| 104 | Mobile_Implementation_Guide §4.1  | `ProviderScope` overrides: tidak ada cara untuk mengakses dio di seluruh aplikasi.               | Tambahkan `@riverpod` untuk `dioProvider`, `secureStorageProvider`. | ✅ Selesai |
| 105 | Mobile_Implementation_Guide §4.2  | `AuthNotifier` build method: jika token ada, getProfile; error handling tidak dijelaskan.        | Tambahkan error handling: jika token invalid, hapus token dan return null. | ✅ Selesai |
| 106 | Mobile_Implementation_Guide §4.3  | `nearbyStores` provider tidak memiliki paginasi/infinite scroll.                                 | Tambahkan `family` + `keepAlive` atau gunakan `AsyncNotifier` dengan loadMore. | ✅ Selesai |
| 107 | Mobile_Implementation_Guide §5.1  | Interceptor `AuthInterceptor` tidak menangani refresh token.                                     | Tambahkan mekanisme refresh token atau gunakan token yang long-lived (Sanctum). | ✅ Selesai |
| 108 | Mobile_Implementation_Guide §5.2  | Model UserModel menggunakan `@freezed`, tapi tidak ada `@JsonSerializable` di kode.              | Konsisten: gunakan `freezed` dengan `fromJson`/`toJson`. | ✅ Selesai |
| 109 | Mobile_Implementation_Guide §5.3  | `AuthRepositoryImpl` tidak ada error handling untuk DioException.                                | Tambahkan try-catch dan mapping ke custom exceptions. | ✅ Selesai |
| 110 | Mobile_Implementation_Guide §7.1  | `LoginPage`: setelah minta OTP, tidak ada navigasi ke OTP page.                                  | Tambahkan: `context.go('/otp', extra: phoneCtrl.text)`. | ✅ Selesai |
| 111 | Mobile_Implementation_Guide §7.1  | Tidak ada loading overlay untuk submit.                                                          | Tambahkan `CircularProgressIndicator` atau overlay. | ✅ Selesai |
| 112 | Mobile_Implementation_Guide §7.2  | ListingDetailPage: tidak ada implementasi untuk "Pesan Sekarang".                                | Tambahkan: bottom sheet atau navigasi ke form order. | ✅ Selesai |
| 113 | Mobile_Implementation_Guide §8    | GoRouter redirect: `ref.read(authNotifierProvider)` tidak bisa dipanggil di `redirect` callback. | Gunakan `ProviderScope` dengan `ref` atau gunakan `GoRouter.redirect` dengan `StatefulShellRoute`. | ✅ Selesai |
| 114 | Mobile_Implementation_Guide §10   | BottomNavigationBar: tidak ada contoh kode untuk `MainShell`.                                    | Tambahkan contoh kode lengkap dengan `BottomNavigationBar` dan `GoRouter` state. | ✅ Selesai |
| 115 | Mobile_Implementation_Guide §11   | ExplorePage: search field tidak dijelaskan implementasi debounce.                                | Tambahkan `debounce` (500ms) untuk search. | ✅ Selesai |
| 116 | Mobile_Implementation_Guide §11   | Filter chips: tidak ada contoh kode untuk filter kategori.                                       | Tambahkan: `Consumer` widget untuk listen kategori provider. | ✅ Selesai |
| 117 | Mobile_Implementation_Guide §12   | CreateRequestPage: lokasi pilih dari map; tidak ada integrasi Google Maps.                       | Tambahkan contoh `GoogleMap` widget dengan `onTap` untuk set lokasi. | ✅ Selesai |
| 118 | Mobile_Implementation_Guide §12   | RequestsPage: dua tab ("Kebutuhan Terbaru", "Permintaan Saya"); tidak ada contoh kode.           | Tambahkan `TabBar` + `TabBarView`. | ✅ Selesai |
| 119 | Mobile_Implementation_Guide §13   | CompareOffersPage: sortable; tidak ada contoh implementasi sorting.                              | Tambahkan dropdown sort dengan `List.generate`. | ✅ Selesai |
| 120 | Mobile_Implementation_Guide §14   | FCM: tidak ada kode untuk request permission iOS.                                                | Tambahkan: `await FirebaseMessaging.instance.requestPermission()` dan handling untuk iOS. | ✅ Selesai |
| 121 | Mobile_Implementation_Guide §14   | FCM: tidak ada kode untuk menangani notifikasi saat app di foreground.                           | Tambahkan: `FirebaseMessaging.onMessage.listen(...)`. | ✅ Selesai |
| 122 | Mobile_Implementation_Guide §15   | WhatsApp: tidak ada fallback jika WA tidak terinstall.                                           | Tambahkan: cek `canLaunchUrl`; jika false, show dialog. | ✅ Selesai |
| 123 | Mobile_Implementation_Guide §16   | Font Plus Jakarta Sans: tidak disebutkan cara integrasi.                                         | Tambahkan: `google_fonts` package dengan `GoogleFonts.plusJakartaSans()`. | ✅ Selesai |
| 124 | Mobile_Implementation_Guide §16   | Skeleton shimmer: tidak disebutkan package.                                                      | Tambahkan: `shimmer` package. | ✅ Selesai |
| 125 | Mobile_Implementation_Guide §19.2 | `NearbyStoresPaginated`: `loadMore` tidak ada guard jika state loading.                          | Tambahkan `if (state.isLoading) return;`. | ✅ Selesai |
| 126 | Mobile_Implementation_Guide §19.3 | `secureStorageProvider`: tidak ada error handling jika storage gagal.                            | Tambahkan try-catch. | ✅ Selesai |
| 127 | Mobile_Implementation_Guide       | Tidak ada panduan untuk environment (dev/staging/prod).                                          | Tambahkan: `--dart-define` atau `.env` dengan `flutter_dotenv`. | ✅ Selesai |
| 128 | Mobile_Implementation_Guide       | Tidak ada panduan untuk error handling global.                                                   | Tambahkan: `FlutterError.onError`, `ErrorWidget.builder`. | ✅ Selesai |
| 129 | Mobile_Implementation_Guide       | Tidak ada panduan untuk logging dan analytics.                                                   | Tambahkan: `analytics` provider dengan Firebase Analytics. | ✅ Selesai |
| 130 | Mobile_Implementation_Guide       | Tidak ada panduan untuk deep link handling dari notifikasi.                                      | Tambahkan: `GetIt` atau `GoRouter` untuk handle deep link. | ✅ Selesai |

---

## G. BRANDING GUIDELINE — KEKURANGAN

| #   | Lokasi       | Issue                                                                                                                  | Rekomendasi Perbaikan | Status |
| --- | ------------ | ---------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------- | --- |
| 131 | Brand §3.1   | Logo: "Huruf e kedua memiliki potongan kecil melingkar" tidak ada visual di dokumen.                                   | Tambahkan gambar referensi logo. | ✅ Selesai |
| 132 | Brand §3.2   | Konstruksi grid logo: tidak ada gambar sketsa grid.                                                                    | Tambahkan gambar di lampiran. | ✅ Selesai |
| 133 | Brand §3.5.3 | Warna Aksen: `Biru Kepercayaan #2563EB`; tidak ada warna untuk `success`, `warning`, `danger` di tabel palet sekunder. | Tambahkan di palet warna aksen. | ✅ Selesai |
| 134 | Brand §3.5.4 | Warna Netral: `Teks Utama #1F2933`; tidak ada warna untuk link.                                                        | Tambahkan `Link #168A4A` (hijau) atau `Link #2563EB`. | ✅ Selesai |
| 135 | Brand §3.6.1 | Type scale: tidak ada ukuran untuk `Small` (10px) atau `Micro` (8px).                                                  | Tambahkan untuk kebutuhan UI yang sangat kecil (badge, caption). | ❌ Ditolak — WCAG/Material menetapkan batas 11–12 px; target pengguna 40+ (BRANDING §4) |
| 136 | Brand §3.6.1 | Tidak ada line-height untuk `Button` dan `Caption`.                                                                    | Tambahkan line-height untuk semua role. | ✅ Selesai |
| 137 | Brand §3.7   | Ikonografi: tidak ada referensi ke library ikon (FontAwesome, Material Icons, Heroicons).                              | Tentukan: gunakan Heroicons + FontAwesome untuk ikon khusus. | ✅ Selesai |
| 138 | Brand §3.8   | Ilustrasi: tidak ada spesifikasi resolusi/format (SVG, PNG).                                                           | Tambahkan: semua ilustrasi dalam SVG vektor untuk skalabilitas. | ✅ Selesai |
| 139 | Brand §3.9   | Motion & Animasi: tidak ada spesifikasi untuk `easing` curve.                                                          | Tambahkan: `Curves.easeInOut`, `Curves.fastOutSlowIn`. | ✅ Selesai |
| 140 | Brand §4.1   | Level Verifikasi 4 "Keahlian Terverifikasi" tidak ada di database/PRD.                                                 | Hapus level 4 atau tambahkan ke database dan PRD. | ✅ Selesai |
| 141 | Brand §4.2   | Badge "Bisa COD", "Bisa Diantar", "Ambil di Tempat" tidak ada di database.                                             | Tambahkan kolom di `stores` atau `listings` untuk flag ini. | ✅ Selesai |
| 142 | Brand §5.1   | App Icon: tidak ada spesifikasi untuk adaptive icon Android.                                                           | Tambahkan: foreground, background, monochrome. | ✅ Selesai |
| 143 | Brand §5.1   | Splash Screen: tidak ada spesifikasi untuk Android 12+ splash.                                                         | Tambahkan: drawable/ic_launcher_background, windowSplashScreenAnimatedIcon. | ✅ Selesai |
| 144 | Brand §5.2   | Media Sosial: tidak ada spesifikasi untuk ukuran cover foto (Facebook, Twitter, LinkedIn).                             | Tambahkan dimensi: Facebook 820x312, Twitter 1500x500, LinkedIn 1584x396. | ✅ Selesai |
| 145 | Brand §5.3   | Merchandise: tidak ada panduan untuk warna dasar merchandise selain putih/abu/hijau.                                   | Tambahkan: hitam, navy, krem sebagai alternatif. | ✅ Selesai |
| 146 | Brand §5.4   | Kop Surat: tidak ada spesifikasi font size untuk alamat dan footer.                                                    | Tambahkan: alamat 10pt, footer 9pt. | ✅ Selesai |
| 147 | Brand §5.6   | Co-Branding: tidak ada aturan jika logo mitra lebih besar dari logo Seekitar.                                          | Tambahkan: logo harus memiliki proporsi visual yang sama (tidak lebih besar). | ✅ Selesai |
| 148 | Brand §6.2   | Email & Notifikasi: tidak ada template HTML untuk email.                                                               | Tambahkan contoh template email transaksional. | ✅ Selesai |
| 149 | Brand §7     | DO's & DON'Ts: tidak ada larangan untuk menggunakan logo di background berwarna tanpa cukup kontras.                   | Tambahkan: "Jangan gunakan logo di atas gambar/foto tanpa overlay cukup kontras". | ✅ Selesai |
| 150 | Brand §8.2   | Domain: tidak disebutkan `seekitar.com` (jika ada).                                                                    | Tambahkan semua domain yang dimiliki. | ✅ Selesai |
| 151 | Brand §8.3   | PSE: tidak ada detail tentang kewajiban menyediakan kontak pengaduan.                                                  | Tambahkan: email `pengaduan@seekitar.id`, nomor WA resmi. | ✅ Selesai |
| 152 | Brand §9     | Lampiran: tidak ada daftar lengkap file yang disediakan.                                                               | Tambahkan link ke repository aset atau drive. | ✅ Selesai |

---

## H. PRD — KEKURANGAN & KETIDAKSESUAIAN

| #   | Lokasi     | Issue                                                                                                                        | Rekomendasi Perbaikan | Status |
| --- | ---------- | ---------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------- | --- |
| 153 | PRD §2.2   | Target 3 bulan: 500 user, 150 toko, GMV 50 juta.                                                                             | Apakah target ini realistis untuk 1 kabupaten? Tambahkan asumsi. | ✅ Selesai |
| 154 | PRD §3.2   | Persona B: "teknisi AC lepas" — tapi di PRD tidak ada fitur untuk "keahlian" spesifik.                                       | Tambahkan: daftar keahlian/portofolio di profil penyedia. | ✅ Selesai |
| 155 | PRD §4     | Fase 2: "auto-bidding" — tidak dijelaskan di dokumen lain.                                                                   | Tambahkan detail di roadmap atau hapus. | ✅ Selesai |
| 156 | PRD §5.1.1 | Listing: "slot" untuk service, tapi di database `slot` TINYINT.                                                              | Jelaskan: `slot` = kapasitas per hari atau total slot? Perjelas. | ✅ Selesai |
| 157 | PRD §5.1.2 | Radius maksimal 25 km — di database `service_radius_km` default 15, PRD default 25.                                          | Konsisten: default 15 km untuk permintaan, maks 25 km. | ✅ Selesai |
| 158 | PRD §5.1.3 | "Pesan Sekarang" untuk jasa: form pemilihan slot waktu — tidak ada di database.                                              | Tambahkan `listings.available_slots` JSON atau tabel terpisah. | ✅ Selesai |
| 159 | PRD §5.2.1 | Form permintaan: foto pendukung "maks 3 foto" — di database `customer_requests` tidak ada `images`.                          | Tambahkan kolom `images` JSON di database. | ✅ Selesai |
| 160 | PRD §5.2.2 | Algoritma broadcast: "kategori layanan yang cocok" — relasi `stores.category_ids` dengan `customer_requests.category_id`.    | Tambahkan query di backend: `FIND_IN_SET(category_id, category_ids)` atau gunakan JSON `JSON_CONTAINS`. | ✅ Selesai |
| 161 | PRD §5.2.2 | "Prioritas Broadcast: rating tertinggi" — tidak ada logika sorting di backend.                                               | Tambahkan: sort by `rating_avg DESC`, `total_reviews DESC`. | ✅ Selesai |
| 162 | PRD §5.2.3 | Penyedia tidak melihat identitas lengkap pembeli — di database, saat offer dibuat, tidak ada akses ke `users`.               | Implementasi: hanya tampilkan `name` (first name) dan rating. | ✅ Selesai |
| 163 | PRD §5.2.4 | Pembeli bisa mengurutkan penawaran: Harga Terendah, Rating Tertinggi, Jarak Terdekat.                                        | API harus mendukung parameter `sort_by` dan `order`. | ✅ Selesai |
| 164 | PRD §5.3.2 | Level Verifikasi: "Level 3 Penyedia Pro" butuh NPWP — di database tidak ada `npwp`.                                          | Tambahkan `npwp` VARCHAR(20) NULL di `stores` atau `users`. | ✅ Selesai |
| 165 | PRD §5.3.3 | "Nama Toko uniqueness per kabupaten" — di database tidak ada kolom `district` atau `regency`.                                | Tambahkan `regency` VARCHAR(100) untuk scope uniqueness. | ✅ Selesai |
| 166 | PRD §5.3.3 | "Toko berstatus pending review, admin verifikasi lokasi di dalam kabupaten target" — tidak ada mekanisme geofencing.         | Tambahkan: admin dapat set polygon kabupaten, atau verifikasi manual. | ✅ Selesai |
| 167 | PRD §5.4.1 | State Diagram Barang: "penjual kirim/siap ambil" → status `Dikirim/Siap Diambil`.                                            | Tambahkan dua status terpisah: `dikirim` dan `siap_diambil`. | ✅ Selesai — label UI diturunkan dari status + order_type + delivery_method |
| 168 | PRD §5.4.2 | State Diagram Jasa: "penyedia mulai" → status `penyedia_mulai` tidak ada di database.                                        | Tambahkan status `dimulai` atau gunakan `diproses` dengan catatan. | ✅ Selesai — dipetakan ke `diproses`; label kontekstual, bukan nilai ENUM baru |
| 169 | PRD §5.4.3 | Sewa: status `Dikembalikan` — tidak ada di database `orders.status`.                                                         | Tambahkan `dikembalikan` ke ENUM. | ✅ Selesai — label "Dikembalikan" diturunkan dari order_type, bukan ENUM baru |
| 170 | PRD §5.5   | Rating: "pembeli dan penjual bisa saling menilai" — database `reviews` hanya satu arah (reviewer → reviewee).                | Tambahkan: dua ulasan per order (pembeli ke penjual, penjual ke pembeli). | ✅ Selesai |
| 171 | PRD §5.5   | Dispute: "Tim admin wajib menanggapi dispute dalam 1x24 jam" — tidak ada SLA tracking di database.                           | Tambahkan `escalated_at`, `response_deadline`. | ✅ Selesai |
| 172 | PRD §5.6   | Deep link WhatsApp: "ID Permintaan" — tidak disebutkan format.                                                               | Format: `https://seekitar.id/request/REQ-123` atau `https://wa.me/...?text=...`. | ✅ Selesai |
| 173 | PRD §6.1   | Alur Pembeli: "Halaman 'Permintaan Saya' menampilkan status open, jumlah penawaran 0" — tidak ada UI detail.                 | Tambahkan wireframe atau deskripsi UI. | ✅ Selesai |
| 174 | PRD §6.2   | Alur Penyedia: "Notifikasi push: 'Permintaan Baru: Servis Kulkas (3.2 km)'" — tetapi API tidak mengirim jarak di notifikasi. | Tambahkan `distance_km` di payload notifikasi. | ✅ Selesai |
| 175 | PRD §7.3   | Query geospasial: `$radius_meter` dikonversi dari km × 1000.                                                                 | Database menggunakan `ST_Distance_Sphere` yang output meter; konversi sudah benar. | ✅ Selesai — sudah benar, diverifikasi ulang |
| 176 | PRD §8     | Skema `stores.category_ids` disebut VARCHAR(255) JSON atau comma-separated — di database JSON.                               | Konsisten: JSON. | ✅ Selesai |
| 177 | PRD §8     | Skema `offers` tidak ada `delivery_fee` atau `additional_cost`.                                                              | Tambahkan `additional_cost` DECIMAL(12,2) DEFAULT 0. | ✅ Selesai |
| 178 | PRD §8     | Skema `orders` tidak ada `notes` untuk catatan pembeli.                                                                      | Tambahkan `notes` TEXT NULL. | ✅ Selesai |
| 179 | PRD §9.1   | `GET /api/listings` — parameter `sort=nearest` membutuhkan lat/lng; tidak dijelaskan.                                        | Tambahkan: `lat` dan `lng` wajib untuk sort nearest. | ✅ Selesai |
| 180 | PRD §9.2   | Contoh response `POST /requests/:id/offers` tidak menunjukkan `store_id`.                                                    | Tambahkan `store_id` di response. | ✅ Selesai |
| 181 | PRD §10.1  | Notifikasi "Permintaan baru cocok" — "Jarak [X] km" — tidak ada penjelasan bagaimana jarak dihitung.                         | Gunakan `ST_Distance_Sphere` antara `stores.location` dan `customer_requests.location`. | ✅ Selesai |
| 182 | PRD §11.2  | PSE: "Sistem didaftarkan ke Kominfo" — tidak ada timeline.                                                                   | Tambahkan: daftarkan sebelum publikasi (2 bulan sebelum rilis). | ✅ Selesai |
| 183 | PRD §12    | Monetisasi: "Paket Penyedia Pro Rp49.000/bulan" — tidak ada fitur di database untuk langganan.                               | Tambahkan tabel `subscriptions` atau `user_subscriptions`. | ✅ Selesai |
| 184 | PRD §13    | KPI: "Match Rate ≥ 75%" — bagaimana menghitungnya di backend?                                                                | Tambahkan: `match_rate = requests_with_at_least_one_offer / total_requests`. | ✅ Selesai |
| 185 | PRD §13    | KPI: "Time to First Offer ≤ 20 menit" — perlu timestamp di `offers.created_at` dan `customer_requests.created_at`.           | Sudah ada; hanya perlu query. | ✅ Selesai |
| 186 | PRD §14    | Roadmap: Minggu 1-2 "CI/CD pipeline" — tidak ada detail tools.                                                               | Tambahkan: GitHub Actions + Laravel Forge / Ploi. | ✅ Selesai |
| 187 | PRD §14    | Roadmap: Minggu 3-4 "admin verifikasi toko" — tidak ada jadwal untuk admin dashboard.                                        | Dashboard admin sebaiknya dimulai minggu 5-6, bukan 13-14. | ✅ Selesai |
| 188 | PRD §15    | Risiko: "AI OCR di fase 2" — tidak ada di roadmap.                                                                           | Tambahkan di Fase 2 atau hapus dari risiko. | ✅ Selesai |
| 189 | PRD §16    | Wireframe: tidak ada link Figma atau gambar.                                                                                 | Tambahkan link atau embed gambar. | ✅ Selesai |

---

## I. KONSISTENSI TERMINOLOGI & PENAMAAN

| #   | Lokasi   | Issue                                                                                  | Rekomendasi Perbaikan | Status |
| --- | -------- | -------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ | --- |
| 190 | Umum     | "Customer Requests" vs "Permintaan" vs "Kebutuhan"                                     | Konsisten: gunakan **"Permintaan"** di UI, **"customer_requests"** di database, **"Request"** di API. | ✅ Selesai |
| 191 | Umum     | "Offers" vs "Penawaran"                                                                | Konsisten: **"Penawaran"** di UI, **"offers"** di database, **"Offer"** di API. | ✅ Selesai |
| 192 | Umum     | "Listings" vs "Katalog"                                                                | Konsisten: **"Listing"** di database, **"Jelajahi"** di UI. | ✅ Selesai |
| 193 | Umum     | "Stores" vs "Toko" vs "Lapak"                                                          | Konsisten: **"Toko"** di UI, **"Lapak"** di branding, **"stores"** di database. | ✅ Selesai |
| 194 | Umum     | "Users" vs "Pengguna" vs "Warga"                                                       | Konsisten: **"Pengguna"** di UI, **"Warga"** di branding, **"users"** di database. | ✅ Selesai |
| 195 | Database | `customer_requests` — kenapa "customer" bukan "user"?                                  | Semua pengguna adalah customer. Gunakan `user_requests` atau tetap `customer_requests` (konsisten di semua dokumen). | ❌ Ditolak — akun terpadu, prefiks customer_ menegaskan PERAN (TECH_STACK §6) |
| 196 | API      | `verification_level` di User, `verification_status` di Store — tidak konsisten suffix. | Konsisten: `verification_level` untuk user, `verification_status` untuk store (sudah benar). | ✅ Selesai |
| 197 | Brand    | "Kebutuhan Sekitar" vs "Pasang Kebutuhan"                                              | Konsisten: **"Pasang Kebutuhan"** untuk aksi pembeli, **"Kebutuhan Sekitar"** untuk menu penyedia. | ✅ Selesai |
| 198 | Database | `store_type` SET('goods','services','rental') — plural vs singular.                    | Konsisten: gunakan singular `good`, `service`, `rental` atau plural `goods`, `services`, `rentals`. Pilih dan konsisten. | ✅ Selesai |

---

## J. IMPLEMENTASI KEAMANAN — KURANG DETAIL

| #   | Lokasi                          | Issue                                                                                             | Rekomendasi Perbaikan | Status |
| --- | ------------------------------- | ------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- | --- |
| 199 | Server_Implementation_Guide §11 | Validasi: tidak ada validasi untuk UUID di route model binding.                                   | Tambahkan: `Route::pattern('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')`; atau gunakan `Route::bind` untuk validasi. | ✅ Selesai |
| 200 | Server_Implementation_Guide     | Tidak ada implementasi CORS untuk API.                                                            | Tambahkan `config/cors.php` dan middleware `HandleCors`. | ✅ Selesai |
| 201 | Server_Implementation_Guide     | Tidak ada implementasi encryption untuk data sensitif (KTP).                                      | Tambahkan: gunakan `Crypt::encryptString()` untuk menyimpan KTP. | ❌ Dikoreksi — Crypt untuk NIK; berkas KTP pakai S3 SSE-KMS (Server Guide §18A.3) |
| 202 | Server_Implementation_Guide     | Tidak ada proteksi XSS di Blade admin.                                                            | Tambahkan: `{{ }}` sudah auto-escape; untuk atribut gunakan `{!! !!}` dengan hati-hati. | ❌ Ditolak — {!! !!} justru MEMATIKAN escaping; pakai {{ }} + @json() (Server Guide §18A) |
| 203 | Server_Implementation_Guide     | Tidak ada rate limiting untuk admin login.                                                        | Tambahkan: `throttle:5,1` untuk login admin. | ✅ Selesai |
| 204 | API Documentation               | Tidak ada mekanisme refresh token.                                                                | Sanctum tidak memiliki refresh token; gunakan token lifetime (1 tahun) atau implementasikan refresh token sendiri. | ✅ Selesai |
| 205 | Mobile_Implementation_Guide     | Token disimpan di `flutter_secure_storage` — baik, tapi tidak ada auto-logout jika token expired. | Tambahkan interceptor untuk handle 401 dan logout. | ✅ Selesai |
| 206 | PRD §11.1                       | "KTP & Selfie dienkripsi AES-256" — tidak ada implementasi di Server Guide.                       | Tambahkan: Laravel `Crypt` atau `Storage::disk('s3')->put('...', $file, 'private')` dengan enkripsi client-side. | ✅ Selesai |
| 207 | PRD §11.3                       | "Validasi input ketat" — tidak ada contoh custom rule untuk format nomor HP Indonesia.            | Tambahkan custom rule: `phone:ID` (gunakan library `propaganistas/laravel-phone`). | ✅ Selesai |

---

## K. DEPLOYMENT & OPERATIONAL — KURANG

| #   | Lokasi                      | Issue                                                              | Rekomendasi Perbaikan | Status |
| --- | --------------------------- | ------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------ | --- |
| 208 | Server_Implementation_Guide | Tidak ada panduan untuk setup Supervisor (queue worker, schedule). | Tambahkan konfigurasi supervisor: `[program:seekitar-worker]`, `[program:seekitar-schedule]`. | ✅ Selesai |
| 209 | Server_Implementation_Guide | Tidak ada panduan untuk setup Redis.                               | Tambahkan: `redis-server`, `redis-cli` config, dan Laravel `config/database.php` untuk Redis. | ✅ Selesai |
| 210 | Server_Implementation_Guide | Tidak ada panduan untuk setup S3/MinIO.                            | Tambahkan: environment variables `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`. | ✅ Selesai |
| 211 | Server_Implementation_Guide | Tidak ada panduan untuk setup SSL/TLS.                             | Tambahkan: gunakan Let's Encrypt atau Cloudflare. | ✅ Selesai |
| 212 | Server_Implementation_Guide | Tidak ada panduan untuk backup database.                           | Tambahkan: cron job untuk `mysqldump` dan upload ke S3. | ✅ Selesai |
| 213 | Server_Implementation_Guide | Tidak ada panduan untuk monitoring error (Sentry).                 | Tambahkan: install `sentry/sentry-laravel`, konfigurasi DSN. | ✅ Selesai |
| 214 | Server_Implementation_Guide | Tidak ada panduan untuk performance monitoring (Laravel Pulse).    | Tambahkan: Laravel Pulse untuk dashboard performa. | ✅ Selesai |
| 215 | Mobile_Implementation_Guide | Tidak ada panduan untuk build release Android (keystore, signing). | Tambahkan: `key.properties`, `build.gradle` signing config. | ✅ Selesai |
| 216 | Mobile_Implementation_Guide | Tidak ada panduan untuk build release iOS (distribution cert).     | Tambahkan: Fastlane atau Xcode Archive. | ✅ Selesai |
| 217 | Mobile_Implementation_Guide | Tidak ada panduan untuk environment config di Flutter.             | Tambahkan: `flutter_dotenv` atau `--dart-define` untuk API URL, FCM key, dll. | ✅ Selesai |
| 218 | PRD §14                     | Roadmap: tidak ada alokasi waktu untuk UAT dan bug fixing.         | Tambahkan 2 minggu buffer di akhir. | ✅ Selesai |

---

## L. DATABASE — PERFORMANCE & INDEX

| #   | Lokasi       | Issue                                                                                                    | Rekomendasi Perbaikan | Status |
| --- | ------------ | -------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- | --- |
| 219 | Database §7  | Index `cr_status_expires_idx` (`status`, `expires_at`) — urutan kolom penting.                           | Pastikan urutan: `status` (kardinalitas rendah) + `expires_at` (kardinalitas tinggi) sudah optimal; tes `EXPLAIN`. | ✅ Selesai |
| 220 | Database §7  | Tidak ada index untuk `customer_requests.category_id` + `status` untuk query broadcast.                  | Tambahkan index: `cr_category_status_idx` (`category_id`, `status`). | ✅ Selesai |
| 221 | Database §7  | Tidak ada index untuk `offers.request_id` + `status` untuk pembeli lihat penawaran.                      | Sudah ada FK index; tambahkan `offers_status_request_idx` (`request_id`, `status`). | ✅ Selesai |
| 222 | Database §7  | Tidak ada index untuk `orders.buyer_id` + `status` untuk riwayat.                                        | Tambahkan `orders_buyer_status_idx` (`buyer_id`, `status`). | ✅ Selesai |
| 223 | Database §7  | Fulltext index `listings_ft_title_desc` — MySQL 8.0 mendukung `ngram` parser untuk Bahasa Indonesia.     | Tambahkan: `FULLTEXT INDEX ft_title_desc (title, description) WITH PARSER ngram`. | ❌ Ditolak — parser ngram untuk CJK, bukan B. Indonesia (DATABASE §7.2) |
| 224 | Database §7  | Spatial index sudah ada; pastikan SRID 4326.                                                             | Sudah, tapi tambahkan `SPATIAL INDEX stores_location_spatial (location)`. | ✅ Selesai |
| 225 | Database §11 | Query radius: menggunakan `ST_Distance_Sphere` — untuk performa, gunakan `ST_Buffer` atau `MBRContains`. | Untuk optimasi, gunakan `ST_Within(location, ST_Buffer(point, radius_in_meters))` untuk filter awal sebelum `ST_Distance_Sphere`. | ❌ Ditolak — ST_Buffer tak didukung SRID 4326; pakai MBRContains (DATABASE §11) |

---

## M. UI/UX — KURANG SPESIFIK

| #   | Lokasi                            | Issue                                                                          | Rekomendasi Perbaikan | Status |
| --- | --------------------------------- | ------------------------------------------------------------------------------ | -------------------------------------------------------------------------- | --- |
| 226 | Server_Implementation_Guide §8    | Admin sidebar: tidak ada kondisi menu berdasarkan permission.                  | Tambahkan `@can` di setiap menu item. | ✅ Selesai |
| 227 | Server_Implementation_Guide §9    | Halaman admin: tidak ada breadcrumb di contoh kode.                            | Tambahkan `@section('breadcrumb')` di layout. | ✅ Selesai |
| 228 | Server_Implementation_Guide §22.2 | Datatables: tidak ada server-side processing untuk `parent_name` dan `action`. | Sudah ada, tapi tambahkan `orderable` dan `searchable` di DataTable class. | ✅ Selesai |
| 229 | Mobile_Implementation_Guide §16   | Shimmer: tidak ada contoh implementasi.                                        | Tambahkan: `shimmer` package dengan `Shimmer.fromColors`. | ✅ Selesai |
| 230 | Mobile_Implementation_Guide §16   | Empty state: tidak ada contoh ilustrasi.                                       | Tambahkan: `lottie` atau `svg` untuk empty state. | ✅ Selesai |
| 231 | Brand §5.1                        | UI Global: tidak ada spesifikasi untuk `FloatingActionButton`.                 | Tambahkan: FAB dengan icon "plus", warna hijau, posisi kanan bawah. | ✅ Selesai |
| 232 | Brand §5.1                        | Bottom Navigation: tidak ada spesifikasi untuk badge notifikasi (angka merah). | Tambahkan: badge muncul jika ada notifikasi baru. | ✅ Selesai |

---

## N. QUEUE & JOB — TIDAK DIJELASKAN DETAIL

| #   | Lokasi                          | Issue                                                      | Rekomendasi Perbaikan | Status |
| --- | ------------------------------- | ---------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ | --- |
| 233 | Server_Implementation_Guide §14 | `BroadcastRequestJob` — tidak ada detail query dan logika. | Tambahkan implementasi: query stores dengan `JSON_CONTAINS(category_ids, category_id)`, filter radius, kirim notifikasi. | ✅ Selesai |
| 234 | Server_Implementation_Guide §14 | Tidak ada Job untuk `UpdateStoreRating` setelah review.    | Tambahkan: `UpdateStoreRating` job. | ✅ Selesai |
| 235 | Server_Implementation_Guide §14 | Tidak ada Job untuk `CloseExpiredRequests` (scheduler).    | Tambahkan: `CloseExpiredRequests` command dan schedule. | ✅ Selesai |
| 236 | Server_Implementation_Guide §14 | Tidak ada retry policy untuk job gagal.                    | Tambahkan: `$tries = 3`, `$backoff = [30, 60, 120]`. | ✅ Selesai |

---

## O. TES — TIDAK ADA DETAIL

| #   | Lokasi                          | Issue                                              | Rekomendasi Perbaikan | Status |
| --- | ------------------------------- | -------------------------------------------------- | --------------------------------------------------------------------------------------------- | --- |
| 237 | Server_Implementation_Guide §20 | Testing: tidak ada daftar test untuk Admin Panel.  | Tambahkan: Feature test untuk `CategoryController`, `UserController`, `StoreController`, dll. | ✅ Selesai |
| 238 | Server_Implementation_Guide §20 | Tidak ada contoh test untuk geospasial.            | Tambahkan: test query radius dengan mock koordinat. | ✅ Selesai |
| 239 | Server_Implementation_Guide §20 | Tidak ada test untuk API (Sanctum authentication). | Tambahkan: test untuk endpoint `/auth/me` dengan token valid/invalid. | ✅ Selesai |
| 240 | Mobile_Implementation_Guide §17 | Widget test: tidak ada contoh.                     | Tambahkan: test untuk `LoginPage` dengan provider override. | ✅ Selesai |
| 241 | Mobile_Implementation_Guide §17 | Integration test: tidak ada detail.                | Tambahkan: test flow login → explore → create request. | ✅ Selesai |

---

## P. NOTIFIKASI — KURANG SPESIFIK

| #   | Lokasi                          | Issue                                                             | Rekomendasi Perbaikan | Status |
| --- | ------------------------------- | ----------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- | --- |
| 242 | Server_Implementation_Guide §15 | FCM: tidak ada detail payload notifikasi.                         | Tambahkan payload: `{"notification":{"title":"...","body":"..."},"data":{"type":"request","id":"..."}}`. | ✅ Selesai |
| 243 | Server_Implementation_Guide §15 | WhatsApp: tidak ada template OTP.                                 | Tambahkan template: "Kode OTP Seekitar Anda: {{otp}}. Jangan bagikan ke siapa pun." | ✅ Selesai |
| 244 | Server_Implementation_Guide §15 | Tidak ada mekanisme untuk mengirim notifikasi ke multiple device. | Tambahkan: user punya multiple FCM token; kirim ke semua. | ✅ Selesai |
| 245 | Server_Implementation_Guide §15 | Tidak ada mekanisme untuk mengecek status delivery notifikasi.    | Tambahkan: Firebase Analytics untuk tracking notifikasi. | ✅ Selesai |
| 246 | Mobile_Implementation_Guide §14 | FCM: tidak ada kode untuk `onMessageOpenedApp` navigasi.          | Tambahkan: `GoRouter` untuk handle `data.screen` dan `data.entity_id`. | ✅ Selesai |

---

## Q. EVENT & LISTENER — TIDAK DIJELASKAN

| #   | Lokasi                          | Issue                                         | Rekomendasi Perbaikan | Status |
| --- | ------------------------------- | --------------------------------------------- | ------------------------------------------------------------------------------------------------------ | --- |
| 247 | Server_Implementation_Guide §13 | Event: tidak ada daftar event yang digunakan. | Tambahkan: `RequestCreated`, `OfferSubmitted`, `OfferAccepted`, `OrderStatusChanged`, `ReviewCreated`. | ✅ Selesai |
| 248 | Server_Implementation_Guide §13 | Listener: tidak ada daftar listener.          | Tambahkan: `BroadcastRequestListener`, `SendOfferNotificationListener`, `UpdateStoreRatingListener`. | ✅ Selesai |

---

## R. API RESPONSE — STANDARISASI

| #   | Lokasi                          | Issue                                                                                  | Rekomendasi Perbaikan | Status |
| --- | ------------------------------- | -------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------- | --- |
| 249 | API Documentation               | Response untuk list data: `data` langsung array, tidak ada `meta`.                     | Tambahkan `meta` untuk paginasi: `{"current_page":1,"per_page":15,"total":120,"last_page":8}`. | ✅ Selesai |
| 250 | API Documentation               | Error 422: `errors` object dengan field errors; tidak ada contoh untuk multiple error. | Tambahkan contoh: `{"errors":{"phone":["Format nomor tidak valid"],"otp":["Kode OTP harus 6 digit"]}}`. | ✅ Selesai |
| 251 | Server_Implementation_Guide §17 | Tidak ada trait `ApiResponse` untuk konsistensi.                                       | Tambahkan trait dengan method `successResponse()`, `errorResponse()`, `validationErrorResponse()`. | ✅ Selesai |

---

## S. GEOSPASIAL — DETAIL QUERY

| #   | Lokasi                          | Issue                                                                                                  | Rekomendasi Perbaikan | Status |
| --- | ------------------------------- | ------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------- | --- |
| 252 | Database §11                    | Query radius: `ST_GeomFromText('POINT({$longitude} {$latitude})', 4326)` — parameter posisi (lng lat). | Pastikan format: `POINT(lng lat)` bukan `POINT(lat lng)`; dokumentasikan. | ✅ Selesai |
| 253 | Server_Implementation_Guide §16 | Tidak ada contoh untuk reverse geocoding (koordinat → alamat).                                         | Tambahkan: menggunakan Google Maps Geocoding API atau paket `geocoder`. | ✅ Selesai |
| 254 | PRD §7.3                        | $radius_meter = $radius_km \* 1000;                                                                    | Sudah benar. | ✅ Selesai — sudah benar, diverifikasi ulang |
| 255 | Database                        | Tidak ada kolom `address` di `stores` dan `users` untuk alamat teks.                                   | Tambahkan `address` TEXT NULL untuk display di UI. | ✅ Selesai |

---

## T. FORMAT & DOKUMENTASI

| #   | Lokasi                      | Issue                                                      | Rekomendasi Perbaikan | Status |
| --- | --------------------------- | ---------------------------------------------------------- | ------------------------------------------------------------------------------- | --- |
| 256 | Semua dokumen               | Tanggal "27 Juli 2026" — apakah ini future date atau typo? | Jika dokumen dibuat 2026, pastikan konsisten; jika typo, perbaiki ke 2025/2024. | ✅ Selesai |
| 257 | Semua dokumen               | Versi dokumen berbeda-beda (2.0, 1.0, 3.0).                | Konsisten: gunakan versi global (misal `v2.0`) di semua dokumen. | ✅ Selesai |
| 258 | Server_Implementation_Guide | Tidak ada `README.md` atau `CONTRIBUTING.md`.              | Tambahkan untuk onboarding developer. | ✅ Selesai |
| 259 | Mobile_Implementation_Guide | Tidak ada `analysis_options.yaml` untuk linting.           | Tambahkan aturan linting. | ✅ Selesai |
| 260 | API Documentation           | Tidak ada Postman/Insomnia collection.                     | Tambahkan link ke collection. | ✅ Selesai |

---

## KESIMPULAN

Total **260+** poin temuan yang mencakup:

- **Inkonsistensi versi & tech stack** (10)
- **Struktur proyek** (10)
- **Data model** (24)
- **API documentation** (26)
- **Backend implementation** (33)
- **Mobile implementation** (27)
- **Branding guideline** (22)
- **PRD** (37)
- **Terminologi** (9)
- **Keamanan** (9)
- **Deployment** (11)
- **Database performance** (7)
- **UI/UX** (7)
- **Queue & Job** (4)
- **Testing** (5)
- **Notifikasi** (5)
- **Event & Listener** (2)
- **API response** (3)
- **Geospasial** (4)
- **Format dokumentasi** (5)

Semua poin di atas harus diperbaiki secara sistematis sebelum memulai implementasi untuk memastikan konsistensi dan menghindari kesalahan teknis di kemudian hari.
