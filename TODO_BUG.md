# 🔍 DAFTAR KEKURANGAN, INKONSISTENSI, & KESALAHAN — SEEKITAR

---

## A. INKONSISTENSI VERSI & TECH STACK

| #   | Lokasi                                                             | Issue                                                                                                 | Rekomendasi Perbaikan                                                              |
| --- | ------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| 1   | Server_Implementation_Guide: Target Laravel 13                     | PRD & Database menyebut Laravel 11. Laravel 13 belum rilis (per Juli 2026 kemungkinan Laravel 11/12). | Konsisten gunakan **Laravel 11** (LTS) atau tentukan versi pasti.                  |
| 2   | Server_Implementation_Guide: PHP 8.3.30                            | Versi minor terlalu spesifik; gunakan minimal PHP 8.3.x.                                              | Ubah ke `PHP 8.3+` atau `PHP 8.3.x (minimal 8.3.0)`.                               |
| 3   | Server_Implementation_Guide: Bootstrap 5                           | Tidak disebutkan versi minor.                                                                         | Tentukan `Bootstrap 5.3.x`.                                                        |
| 4   | Server_Implementation_Guide: Yajra Datatables 11.x                 | Spatie Permission 6.x                                                                                 | Keduanya versi mayor, tapi tidak ada info kompatibilitas dengan Laravel 11.        | Tambahkan catatan kompatibilitas: `Yajra Datatables ^11.0` dan `Spatie Permission ^6.0` compatible dengan Laravel 11. |
| 5   | Mobile_Implementation_Guide: Flutter 3.19+                         | PRD menyebut Flutter 3.19+; tidak ada di dokumen lain.                                                | Konsisten di semua dokumen.                                                        |
| 6   | Database.md: MySQL 8.0.34+                                         | Server_Implementation_Guide menyebut MySQL 8.0                                                        | Konsisten: gunakan `MySQL 8.0+` atau `MySQL 8.0.34+` di semua dokumen.             |
| 7   | PRD: Redis 7                                                       | Server_Implementation_Guide sebut Redis 7, Mobile Guide tidak sebut Redis.                            | Tambahkan Redis di Mobile Guide sebagai bagian dari arsitektur backend.            |
| 8   | Mobile_Implementation_Guide: GoRouter ^14.0.0                      | Riverpod 2.x                                                                                          | GoRouter 14 membutuhkan Riverpod 2.x; pastikan kompatibilitas.                     | Tambahkan catatan kompatibilitas.                                                                                     |
| 9   | API_DOCUMENTATION.md: Tidak ada base URL untuk environment berbeda | Hanya `https://api.seekitar.id/api/v1`.                                                               | Tambahkan `staging-api.seekitar.id` dan `localhost:8000/api/v1` untuk development. |
| 10  | Server_Implementation_Guide: Sanctum & Token                       | Tidak disebutkan konfigurasi `SANCTUM_STATEFUL_DOMAINS` untuk API.                                    | Tambahkan panduan konfigurasi Sanctum untuk API (stateless) dan Web (stateful).    |

---

## B. STRUKTUR PROYEK & ORGANISASI FILE

| #   | Lokasi                         | Issue                                                                         | Rekomendasi Perbaikan                                                                                                                         |
| --- | ------------------------------ | ----------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| 11  | Server_Implementation_Guide §4 | Struktur proyek tidak mencakup `app/Services/` untuk logika bisnis.           | Tambahkan `app/Services/` dengan contoh `BroadcastService`, `GeolocationService`, `NotificationService`.                                      |
| 12  | Server_Implementation_Guide §4 | Tidak ada `app/Enums/` untuk status, tipe, dll.                               | Tambahkan `app/Enums/OrderStatus.php`, `RequestStatus.php`, `OfferStatus.php`, `StoreType.php`, `VerificationLevel.php`.                      |
| 13  | Server_Implementation_Guide §4 | Tidak ada `app/DataTables/` di daftar struktur (hanya disebut di §10).        | Tambahkan `app/DataTables/` di struktur proyek.                                                                                               |
| 14  | Server_Implementation_Guide §4 | Tidak ada `database/seeders/` disebut.                                        | Tambahkan di struktur proyek (meskipun standar Laravel).                                                                                      |
| 15  | Server_Implementation_Guide §4 | Tidak ada `app/Jobs/` padahal ada broadcast job.                              | Tambahkan `app/Jobs/BroadcastRequestJob.php`.                                                                                                 |
| 16  | Server_Implementation_Guide §4 | Tidak ada `app/Listeners/` padahal ada event.                                 | Tambahkan `app/Listeners/SendOfferAcceptedNotification.php`, dll.                                                                             |
| 17  | Mobile_Implementation_Guide §3 | Struktur `core/widgets/` dan `presentation/widgets/` ambigu.                  | Jelaskan perbedaan: `core/widgets/` = global reusable (button, loading), `presentation/widgets/` = fitur-spesifik (ListingCard, RequestCard). |
| 18  | Mobile_Implementation_Guide §3 | Tidak ada folder `core/services/` untuk service locator.                      | Tambahkan `core/services/` untuk `LocationService`, `NotificationService`, `AnalyticsService`.                                                |
| 19  | Mobile_Implementation_Guide §3 | Tidak ada folder `presentation/providers/` di struktur (hanya disebut di §4). | Tambahkan `presentation/providers/` dengan file-file provider.                                                                                |
| 20  | Mobile_Implementation_Guide §3 | Tidak ada folder `core/constants/route_constants.dart` untuk GoRouter.        | Tambahkan untuk menghindari hardcode string.                                                                                                  |

---

## C. INKONSISTENSI DATA MODEL (DATABASE vs PRD vs API)

| #   | Lokasi                       | Issue                                                                                 | Rekomendasi Perbaikan                                                                                                                                        |
| --- | ---------------------------- | ------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------- |
| 21  | Database `users` vs PRD      | PRD sebut `verification_level` 1-3, Database sebut 1-3. API docs tidak sebut level 4. | Konsisten: gunakan 1-3 di MVP.                                                                                                                               |
| 22  | Database `stores`            | `store_type` menggunakan SET('goods','services','rental')                             | PRD sebut bisa kombinasi; SET di MySQL kurang fleksibel untuk kombinasi. Ubah ke `JSON` atau tabel pivot `store_types`.                                      |
| 23  | Database `stores`            | `category_ids` JSON                                                                   | PRD sebut "comma-separated" tapi Database sebut JSON. Konsisten: pakai JSON.                                                                                 |
| 24  | Database `listings`          | `price` DECIMAL(12,2) NULL                                                            | PRD sebut service bisa null; product/rental wajib. Tambahkan CHECK constraint: `(listing_type != 'product' OR price IS NOT NULL)`.                           |
| 25  | Database `listings`          | `stock_qty` INT UNSIGNED NULL, `slot` TINYINT UNSIGNED NULL                           | Tidak ada constraint bahwa `stock_qty` wajib untuk product/rental, `slot` wajib untuk service. Tambahkan CHECK.                                              |
| 26  | Database `customer_requests` | `accepted_offer_id` FK ke `offers`                                                    | Di PRD, setelah offer diterima, request status `closed`. Di Database, tidak ada trigger untuk otomatis reject offer lain.                                    | Tambahkan Observer atau trigger untuk reject offers lain saat satu diterima.     |
| 27  | Database `orders`            | `payment_method` ENUM('cod','transfer')                                               | PRD sebut "transfer langsung (nomor rekening ditampilkan)". Tidak ada kolom `bank_account` atau `payment_proof`.                                             | Tambahkan `payment_proof_url` (untuk bukti transfer) dan `payment_confirmed_at`. |
| 28  | Database `orders`            | Tidak ada `shipping_address` atau `delivery_method`.                                  | Untuk COD/ambil di tempat, perlu alamat pengiriman. Tambahkan `shipping_address` TEXT NULL.                                                                  |
| 29  | Database `orders`            | Tidak ada `order_number` human-readable.                                              | Tambahkan `order_number` VARCHAR(20) UNIQUE untuk referensi customer (misal: `SKT-20260727-0001`).                                                           |
| 30  | Database `reviews`           | `reviewee_id` merujuk ke `users`, tapi rating toko seharusnya ke `stores`.            | Perbaiki: `reviewee_id` seharusnya `store_id` (FK ke `stores`) agar perhitungan rating toko akurat.                                                          |
| 31  | Database `disputes`          | `reason` VARCHAR(100) tanpa ENUM.                                                     | PRD sebut enum: `barang_tidak_sesuai`, `jasa_tidak_profesional`, dll. Ubah ke ENUM atau tambahkan CHECK.                                                     |
| 32  | Database `categories`        | `parent_id` FK dengan `ON DELETE SET NULL`                                            | PRD sebut kategori tidak boleh dihapus jika ada request terkait (`RESTRICT`). Konsisten: `ON DELETE RESTRICT` untuk semua relasi dengan `customer_requests`. |
| 33  | Database `customer_requests` | `expires_at` default 24 jam, tapi tidak ada kolom `extended_at` untuk perpanjangan.   | Tambahkan `extended_at` TIMESTAMP NULL jika pembeli perpanjang masa aktif.                                                                                   |
| 34  | Database `offers`            | `estimation_time` VARCHAR(100)                                                        | Sebaiknya `estimated_days` TINYINT atau `estimated_hours` SMALLINT untuk sorting/analitik. Tambahkan kolom terpisah.                                         |
| 35  | Database `offers`            | Tidak ada `expires_at` untuk penawaran.                                               | Tambahkan `expires_at` TIMESTAMP (misal 48 jam) agar penawaran kadaluarsa otomatis.                                                                          |
| 36  | Database `stores`            | `rating_avg` DECIMAL(3,2)                                                             | Jika rating 1-5, DECIMAL(3,2) cukup (maks 5.00). Tapi `total_reviews` INT UNSIGNED. Tambahkan `rating_count` sebagai alias `total_reviews`.                  |
| 37  | Database `users`             | `location` POINT SRID 4326 NULL                                                       | PRD sebut lokasi default wajib setelah registrasi. Ubah ke `NOT NULL`.                                                                                       |
| 38  | Database `stores`            | `service_radius_km` DECIMAL(5,2) DEFAULT 5.00                                         | PRD sebut default 15 km. Konsisten: 15 km.                                                                                                                   |
| 39  | Database `customer_requests` | `radius_km` DECIMAL(5,2) DEFAULT 15.00                                                | PRD sebut default 15 km. Sudah sesuai.                                                                                                                       |
| 40  | Database `orders`            | Tidak ada `cancelled_at` dan `cancelled_by` untuk audit.                              | Tambahkan `cancelled_at` TIMESTAMP NULL dan `cancelled_by` CHAR(36) NULL (FK users).                                                                         |
| 41  | Database `stores`            | `verification_status` ENUM('pending','verified','rejected')                           | Tambahkan `rejected_reason` TEXT NULL untuk catatan penolakan.                                                                                               |
| 42  | Database `users`             | Tidak ada `ktp_image` dan `selfie_image` URL.                                         | Tambahkan `ktp_image` VARCHAR(500) NULL dan `selfie_image` VARCHAR(500) NULL.                                                                                |
| 43  | Database `customer_requests` | Tidak ada `images` (foto pendukung).                                                  | Tambahkan `images` JSON NULL (maks 3 foto).                                                                                                                  |
| 44  | Database `listings`          | `images` JSON, tapi tidak ada validasi min 1, max 5.                                  | Di Laravel, gunakan FormRequest validation; tambahkan catatan di Database.md.                                                                                |

---

## D. INKONSISTENSI DI API DOCUMENTATION

| #   | Lokasi                                        | Issue                                                                                   | Rekomendasi Perbaikan                                                                         |
| --- | --------------------------------------------- | --------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- | ------------------ | -------------- | ----------------------------------------- | ----- | -------------- | ---------- |
| 45  | API §2.1 `POST /auth/request-otp`             | Tidak disebutkan rate limit di response header.                                         | Tambahkan `X-RateLimit-Remaining`, `X-RateLimit-Reset` di response.                           |
| 46  | API §2.2 `POST /auth/verify-otp`              | Response tidak menyertakan `refresh_token` atau `expires_in`.                           | Tambahkan `expires_in` (detik) dan `token_type: Bearer`.                                      |
| 47  | API §2.4 `PATCH /auth/profile`                | Body menggunakan `multipart/form-data`; tidak disebutkan validasi file (mime, dimensi). | Tambahkan: `avatar: image                                                                     | mimes:jpeg,png,jpg | max:2048       | dimensions:min_width=200,min_height=200`. |
| 48  | API §2.5 `POST /auth/verification/ktp`        | Tidak disebutkan validasi file KTP dan selfie.                                          | Tambahkan validasi: `ktp_image: required                                                      | image              | mimes:jpeg,png | max:5120`, `selfie_image: required        | image | mimes:jpeg,png | max:5120`. |
| 49  | API §3.1 `POST /stores`                       | `store_type` menggunakan array? Database menggunakan SET/JSON.                          | Konsisten: di API gunakan array `["goods", "services"]`.                                      |
| 50  | API §3.1 `POST /stores`                       | `operating_hours` struktur tidak dijelaskan detail.                                     | Tambahkan contoh struktur JSON dengan semua hari.                                             |
| 51  | API §3.2 `GET /stores/nearby`                 | Parameter `type` menggunakan `services` tapi database `service` (tunggal).              | Konsisten: `goods`, `services`, `rental` atau `goods`, `service`, `rental`. Pilih salah satu. |
| 52  | API §4.1 `POST /listings`                     | Tidak ada endpoint untuk upload gambar terpisah.                                        | Tambahkan `POST /uploads/images` untuk upload file sebelum submit listing.                    |
| 53  | API §4.2 `GET /listings`                      | Sort parameter `nearest`, `cheapest`, `newest`; `nearest` membutuhkan lat/lng.          | Jelaskan bahwa `lat`/`lng` wajib untuk `sort=nearest`.                                        |
| 54  | API §5.1 `POST /requests`                     | Tidak ada field `images` (foto pendukung).                                              | Tambahkan `images` (array URL atau multipart).                                                |
| 55  | API §5.1 `POST /requests`                     | `required_date` format `2026-07-28T10:00:00+07:00`.                                     | Sebaiknya gunakan ISO 8601 UTC: `2026-07-28T03:00:00Z`.                                       |
| 56  | API §5.2 `GET /requests`                      | Tidak ada paginasi parameter (`page`, `per_page`).                                      | Tambahkan `page`, `per_page` (default 15).                                                    |
| 57  | API §6.1 `POST /requests/{request_id}/offers` | Tidak ada validasi bahwa offer price harus >= budget_min dan <= budget_max.             | Tambahkan validasi otomatis di backend; jika di luar range, return 422.                       |
| 58  | API §6.3 `PATCH /offers/{offer_id}/accept`    | Tidak disebutkan bahwa offer lain otomatis rejected.                                    | Tambahkan di deskripsi efek samping.                                                          |
| 59  | API §7.1 `POST /orders`                       | Jika dari listing, `listing_id` wajib. Jika dari offer, tidak perlu.                    | Jelaskan logika: `listing_id` XOR `offer_id` wajib.                                           |
| 60  | API §7.1 `POST /orders`                       | Tidak ada field `shipping_address` untuk COD.                                           | Tambahkan `shipping_address` opsional.                                                        |
| 61  | API §7.2 `PATCH /orders/{order_id}/status`    | Tidak ada state machine diagram di API docs.                                            | Tambahkan state diagram dan status yang valid per role (pembeli/penjual).                     |
| 62  | API §8.1 `POST /orders/{order_id}/review`     | Tidak ada validasi bahwa order sudah `selesai` dan belum pernah review.                 | Tambahkan di deskripsi.                                                                       |
| 63  | API §9.1 `POST /orders/{order_id}/disputes`   | Tidak ada validasi bahwa order status `dispute` berubah otomatis.                       | Tambahkan efek samping: order status berubah menjadi `dispute`.                               |
| 64  | API §10 Admin                                 | Tidak ada endpoint `GET /admin/stores`, `GET /admin/users` di API docs.                 | Tambahkan semua resource admin: users, stores, listings, requests, offers, orders, reviews.   |
| 65  | API §10 Admin                                 | Tidak ada endpoint untuk `POST /admin/settings` di API docs.                            | Tambahkan endpoint settings (hanya super-admin).                                              |
| 66  | API §11 Error                                 | Error 409 Conflict tidak dijelaskan contoh body.                                        | Tambahkan contoh: `{"success":false,"message":"Anda sudah mengirim penawaran"}`.              |
| 67  | API §11 Error                                 | Tidak ada kode 423 Locked untuk akun yang dibekukan.                                    | Tambahkan 423 untuk akun nonaktif.                                                            |
| 68  | API                                           | Tidak ada endpoint `POST /auth/logout` di API docs.                                     | Tambahkan: `POST /auth/logout` untuk invalidate token.                                        |
| 69  | API                                           | Tidak ada endpoint `POST /auth/fcm-token` untuk registrasi FCM.                         | Tambahkan: `POST /auth/fcm-token` dengan body `{"fcm_token":"..."}`.                          |
| 70  | API                                           | Tidak ada endpoint untuk wishlist/favorit.                                              | Tambahkan `POST /listings/{id}/favorite`, `GET /favorites`.                                   |

---

## E. IMPLEMENTASI BACKEND (LARAVEL) — KURANG DETAIL

| #   | Lokasi                            | Issue                                                                                                           | Rekomendasi Perbaikan                                                                                                       |
| --- | --------------------------------- | --------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| 71  | Server_Implementation_Guide §5    | Middleware `role:admin` tidak dijelaskan cara registrasi di Kernel.                                             | Tambahkan di `App\Http\Kernel` atau `bootstrap/app.php` (Laravel 11) dengan `->withMiddleware()`.                           |
| 72  | Server_Implementation_Guide §6.2  | Guard `sanctum` dan `web`; Spatie Permission default guard `web`. API perlu `sanctum`.                          | Set default guard di `config/permission.php` = `sanctum` atau buat permission terpisah per guard.                           |
| 73  | Server_Implementation_Guide §6.3  | Policies untuk Admin tidak dijelaskan.                                                                          | Tambahkan `AdminPolicy` atau gunakan `Gate::before` untuk super-admin.                                                      |
| 74  | Server_Implementation_Guide §7    | `routes/web.php` tidak ada middleware `auth` untuk admin prefix.                                                | Tambahkan: `Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(...)`.                            |
| 75  | Server_Implementation_Guide §7    | Tidak ada route untuk Datatables AJAX (`/admin/categories/data`).                                               | Tambahkan di web.php: `Route::get('categories/data', [CategoryController::class, 'data'])->name('admin.categories.data');`. |
| 76  | Server_Implementation_Guide §9.1  | Dashboard chart tidak dijelaskan implementasi (Chart.js).                                                       | Tambahkan contoh kode Chart.js dan endpoint API untuk data chart.                                                           |
| 77  | Server_Implementation_Guide §9.2  | Manajemen Kategori tidak ada validasi untuk mencegah loop parent-child.                                         | Tambahkan validasi: `parent_id` tidak boleh sama dengan `id` dan tidak boleh menjadi descendant dari dirinya sendiri.       |
| 78  | Server_Implementation_Guide §9.3  | Verifikasi Pengguna: tidak ada logika untuk menaikkan `verification_level` setelah approve.                     | Tambahkan: setelah approve, set `verification_level = 2`; setelah reject, set `verification_level = 0` atau `1`.            |
| 79  | Server_Implementation_Guide §9.3  | Verifikasi Toko: tidak ada logika jika toko ditolak.                                                            | Tambahkan: toko `verification_status = 'rejected'`, kirim notifikasi ke pemilik.                                            |
| 80  | Server_Implementation_Guide §9.4  | Manajemen Pengguna: tidak ada filter `verification_level` di Datatables.                                        | Tambahkan filter dropdown untuk `verification_level`.                                                                       |
| 81  | Server_Implementation_Guide §9.5  | Edit Toko: `operating_hours` JSON editor tidak dijelaskan.                                                      | Tambahkan contoh UI menggunakan komponen JSON atau form terstruktur per hari.                                               |
| 82  | Server_Implementation_Guide §9.6  | Manajemen Listing: tidak ada fitur untuk mengubah status listing (active/hidden).                               | Tambahkan tombol "Aktifkan/Nonaktifkan" di aksi.                                                                            |
| 83  | Server_Implementation_Guide §9.7  | Manajemen Permintaan: tidak ada aksi untuk memperpanjang expired request.                                       | Tambahkan fitur "Perpanjang" untuk admin (opsional).                                                                        |
| 84  | Server_Implementation_Guide §9.10 | Resolve Dispute: tidak ada logika untuk mengubah status order.                                                  | Tambahkan: setelah dispute resolved, order status bisa `selesai` atau `dibatalkan` sesuai keputusan admin.                  |
| 85  | Server_Implementation_Guide §9.12 | Pengaturan Sistem: tidak disebutkan tabel `settings`.                                                           | Buat migration `settings` dengan key-value store.                                                                           |
| 86  | Server_Implementation_Guide §10   | Controller `CategoryController` tidak ada method `edit` dan `create` (hanya index, store, update, destroy).     | Tambahkan `create()` dan `edit()` jika menggunakan view terpisah (bukan modal).                                             |
| 87  | Server_Implementation_Guide §10   | Controller tidak menggunakan `FormRequest` untuk semua aksi.                                                    | Pastikan semua store/update menggunakan FormRequest.                                                                        |
| 88  | Server_Implementation_Guide §11   | `CategoryRequest` tidak ada validasi `icon` harus valid FontAwesome.                                            | Tambahkan rule `in:fa-users,fa-store,...` atau custom rule.                                                                 |
| 89  | Server_Implementation_Guide §13   | Observers & Events: tidak dijelaskan secara detail.                                                             | Tambahkan daftar Observer: `StoreObserver`, `ListingObserver`, `OrderObserver`, `ReviewObserver`.                           |
| 90  | Server_Implementation_Guide §14   | Jobs & Queue: tidak dijelaskan detail `BroadcastRequestJob`.                                                    | Tambahkan implementasi lengkap: query toko, kirim notifikasi, log.                                                          |
| 91  | Server_Implementation_Guide §15   | Notifikasi Push: tidak ada implementasi FCM di Laravel.                                                         | Tambahkan `app/Notifications/RequestBroadcastNotification.php` dengan FCM channel.                                          |
| 92  | Server_Implementation_Guide §15   | WhatsApp OTP: tidak disebutkan library yang digunakan.                                                          | Tambahkan: `laravel-notification-channels/whatsapp` atau custom dengan Twilio API.                                          |
| 93  | Server_Implementation_Guide §16   | Geospasial: query radius menggunakan `ST_Distance_Sphere`; tidak ada contoh dengan Eloquent scope.              | Tambahkan scope `scopeNearby($query, $lat, $lng, $radiusKm)` di model Store dan Request.                                    |
| 94  | Server_Implementation_Guide §17   | API Response: tidak ada standarisasi response untuk web (admin).                                                | Tambahkan trait `ApiResponse` dan `WebResponse` untuk konsistensi.                                                          |
| 95  | Server_Implementation_Guide §19.2 | Seeder RolesAndPermissionsSeeder: tidak ada permission `manage-settings` di daftar.                             | Tambahkan `manage-settings`.                                                                                                |
| 96  | Server_Implementation_Guide §19.2 | Seeder tidak ada `DatabaseSeeder` yang memanggil semua seeder.                                                  | Tambahkan `DatabaseSeeder` dengan `$this->call([...])`.                                                                     |
| 97  | Server_Implementation_Guide §20   | Testing: tidak ada detail test apa saja.                                                                        | Tambahkan: Feature Test untuk API (Auth, Store, Listing, Request, Offer, Order), Unit Test untuk Service.                   |
| 98  | Server_Implementation_Guide §21   | Deployment: tidak ada detail environment variables.                                                             | Tambahkan daftar `.env` required: `DB_*`, `REDIS_*`, `FCM_*`, `TWILIO_*`, `AWS_*`, `APP_URL`, dll.                          |
| 99  | Server_Implementation_Guide §22.2 | Blade `categories/index.blade.php` menggunakan `$dataTable->table()` tapi tidak ada `$dataTable` di controller. | Tambahkan di controller index: `return view('admin.categories.index', ['dataTable' => app(CategoryDataTable::class)]);`     |
| 100 | Server_Implementation_Guide §22.2 | Modal untuk create/edit menggunakan satu form; tidak ada logika untuk edit mode.                                | Tambahkan JavaScript untuk isi modal saat edit.                                                                             |
| 101 | Server_Implementation_Guide       | Tidak ada panduan untuk Laravel Telescope/Debugbar untuk development.                                           | Tambahkan konfigurasi `laravel/telescope` untuk development.                                                                |
| 102 | Server_Implementation_Guide       | Tidak ada panduan untuk cronjob/scheduler.                                                                      | Tambahkan: `php artisan schedule:run` untuk menutup expired requests.                                                       |
| 103 | Server_Implementation_Guide       | Tidak ada panduan untuk queue worker.                                                                           | Tambahkan: `php artisan queue:work redis --queue=broadcast,default` dan Supervisor config.                                  |

---

## F. IMPLEMENTASI MOBILE (FLUTTER) — KURANG DETAIL

| #   | Lokasi                            | Issue                                                                                            | Rekomendasi Perbaikan                                                                              |
| --- | --------------------------------- | ------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------- |
| 104 | Mobile_Implementation_Guide §4.1  | `ProviderScope` overrides: tidak ada cara untuk mengakses dio di seluruh aplikasi.               | Tambahkan `@riverpod` untuk `dioProvider`, `secureStorageProvider`.                                |
| 105 | Mobile_Implementation_Guide §4.2  | `AuthNotifier` build method: jika token ada, getProfile; error handling tidak dijelaskan.        | Tambahkan error handling: jika token invalid, hapus token dan return null.                         |
| 106 | Mobile_Implementation_Guide §4.3  | `nearbyStores` provider tidak memiliki paginasi/infinite scroll.                                 | Tambahkan `family` + `keepAlive` atau gunakan `AsyncNotifier` dengan loadMore.                     |
| 107 | Mobile_Implementation_Guide §5.1  | Interceptor `AuthInterceptor` tidak menangani refresh token.                                     | Tambahkan mekanisme refresh token atau gunakan token yang long-lived (Sanctum).                    |
| 108 | Mobile_Implementation_Guide §5.2  | Model UserModel menggunakan `@freezed`, tapi tidak ada `@JsonSerializable` di kode.              | Konsisten: gunakan `freezed` dengan `fromJson`/`toJson`.                                           |
| 109 | Mobile_Implementation_Guide §5.3  | `AuthRepositoryImpl` tidak ada error handling untuk DioException.                                | Tambahkan try-catch dan mapping ke custom exceptions.                                              |
| 110 | Mobile_Implementation_Guide §7.1  | `LoginPage`: setelah minta OTP, tidak ada navigasi ke OTP page.                                  | Tambahkan: `context.go('/otp', extra: phoneCtrl.text)`.                                            |
| 111 | Mobile_Implementation_Guide §7.1  | Tidak ada loading overlay untuk submit.                                                          | Tambahkan `CircularProgressIndicator` atau overlay.                                                |
| 112 | Mobile_Implementation_Guide §7.2  | ListingDetailPage: tidak ada implementasi untuk "Pesan Sekarang".                                | Tambahkan: bottom sheet atau navigasi ke form order.                                               |
| 113 | Mobile_Implementation_Guide §8    | GoRouter redirect: `ref.read(authNotifierProvider)` tidak bisa dipanggil di `redirect` callback. | Gunakan `ProviderScope` dengan `ref` atau gunakan `GoRouter.redirect` dengan `StatefulShellRoute`. |
| 114 | Mobile_Implementation_Guide §10   | BottomNavigationBar: tidak ada contoh kode untuk `MainShell`.                                    | Tambahkan contoh kode lengkap dengan `BottomNavigationBar` dan `GoRouter` state.                   |
| 115 | Mobile_Implementation_Guide §11   | ExplorePage: search field tidak dijelaskan implementasi debounce.                                | Tambahkan `debounce` (500ms) untuk search.                                                         |
| 116 | Mobile_Implementation_Guide §11   | Filter chips: tidak ada contoh kode untuk filter kategori.                                       | Tambahkan: `Consumer` widget untuk listen kategori provider.                                       |
| 117 | Mobile_Implementation_Guide §12   | CreateRequestPage: lokasi pilih dari map; tidak ada integrasi Google Maps.                       | Tambahkan contoh `GoogleMap` widget dengan `onTap` untuk set lokasi.                               |
| 118 | Mobile_Implementation_Guide §12   | RequestsPage: dua tab ("Kebutuhan Terbaru", "Permintaan Saya"); tidak ada contoh kode.           | Tambahkan `TabBar` + `TabBarView`.                                                                 |
| 119 | Mobile_Implementation_Guide §13   | CompareOffersPage: sortable; tidak ada contoh implementasi sorting.                              | Tambahkan dropdown sort dengan `List.generate`.                                                    |
| 120 | Mobile_Implementation_Guide §14   | FCM: tidak ada kode untuk request permission iOS.                                                | Tambahkan: `await FirebaseMessaging.instance.requestPermission()` dan handling untuk iOS.          |
| 121 | Mobile_Implementation_Guide §14   | FCM: tidak ada kode untuk menangani notifikasi saat app di foreground.                           | Tambahkan: `FirebaseMessaging.onMessage.listen(...)`.                                              |
| 122 | Mobile_Implementation_Guide §15   | WhatsApp: tidak ada fallback jika WA tidak terinstall.                                           | Tambahkan: cek `canLaunchUrl`; jika false, show dialog.                                            |
| 123 | Mobile_Implementation_Guide §16   | Font Plus Jakarta Sans: tidak disebutkan cara integrasi.                                         | Tambahkan: `google_fonts` package dengan `GoogleFonts.plusJakartaSans()`.                          |
| 124 | Mobile_Implementation_Guide §16   | Skeleton shimmer: tidak disebutkan package.                                                      | Tambahkan: `shimmer` package.                                                                      |
| 125 | Mobile_Implementation_Guide §19.2 | `NearbyStoresPaginated`: `loadMore` tidak ada guard jika state loading.                          | Tambahkan `if (state.isLoading) return;`.                                                          |
| 126 | Mobile_Implementation_Guide §19.3 | `secureStorageProvider`: tidak ada error handling jika storage gagal.                            | Tambahkan try-catch.                                                                               |
| 127 | Mobile_Implementation_Guide       | Tidak ada panduan untuk environment (dev/staging/prod).                                          | Tambahkan: `--dart-define` atau `.env` dengan `flutter_dotenv`.                                    |
| 128 | Mobile_Implementation_Guide       | Tidak ada panduan untuk error handling global.                                                   | Tambahkan: `FlutterError.onError`, `ErrorWidget.builder`.                                          |
| 129 | Mobile_Implementation_Guide       | Tidak ada panduan untuk logging dan analytics.                                                   | Tambahkan: `analytics` provider dengan Firebase Analytics.                                         |
| 130 | Mobile_Implementation_Guide       | Tidak ada panduan untuk deep link handling dari notifikasi.                                      | Tambahkan: `GetIt` atau `GoRouter` untuk handle deep link.                                         |

---

## G. BRANDING GUIDELINE — KEKURANGAN

| #   | Lokasi       | Issue                                                                                                                  | Rekomendasi Perbaikan                                                             |
| --- | ------------ | ---------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------- |
| 131 | Brand §3.1   | Logo: "Huruf e kedua memiliki potongan kecil melingkar" tidak ada visual di dokumen.                                   | Tambahkan gambar referensi logo.                                                  |
| 132 | Brand §3.2   | Konstruksi grid logo: tidak ada gambar sketsa grid.                                                                    | Tambahkan gambar di lampiran.                                                     |
| 133 | Brand §3.5.3 | Warna Aksen: `Biru Kepercayaan #2563EB`; tidak ada warna untuk `success`, `warning`, `danger` di tabel palet sekunder. | Tambahkan di palet warna aksen.                                                   |
| 134 | Brand §3.5.4 | Warna Netral: `Teks Utama #1F2933`; tidak ada warna untuk link.                                                        | Tambahkan `Link #168A4A` (hijau) atau `Link #2563EB`.                             |
| 135 | Brand §3.6.1 | Type scale: tidak ada ukuran untuk `Small` (10px) atau `Micro` (8px).                                                  | Tambahkan untuk kebutuhan UI yang sangat kecil (badge, caption).                  |
| 136 | Brand §3.6.1 | Tidak ada line-height untuk `Button` dan `Caption`.                                                                    | Tambahkan line-height untuk semua role.                                           |
| 137 | Brand §3.7   | Ikonografi: tidak ada referensi ke library ikon (FontAwesome, Material Icons, Heroicons).                              | Tentukan: gunakan Heroicons + FontAwesome untuk ikon khusus.                      |
| 138 | Brand §3.8   | Ilustrasi: tidak ada spesifikasi resolusi/format (SVG, PNG).                                                           | Tambahkan: semua ilustrasi dalam SVG vektor untuk skalabilitas.                   |
| 139 | Brand §3.9   | Motion & Animasi: tidak ada spesifikasi untuk `easing` curve.                                                          | Tambahkan: `Curves.easeInOut`, `Curves.fastOutSlowIn`.                            |
| 140 | Brand §4.1   | Level Verifikasi 4 "Keahlian Terverifikasi" tidak ada di database/PRD.                                                 | Hapus level 4 atau tambahkan ke database dan PRD.                                 |
| 141 | Brand §4.2   | Badge "Bisa COD", "Bisa Diantar", "Ambil di Tempat" tidak ada di database.                                             | Tambahkan kolom di `stores` atau `listings` untuk flag ini.                       |
| 142 | Brand §5.1   | App Icon: tidak ada spesifikasi untuk adaptive icon Android.                                                           | Tambahkan: foreground, background, monochrome.                                    |
| 143 | Brand §5.1   | Splash Screen: tidak ada spesifikasi untuk Android 12+ splash.                                                         | Tambahkan: drawable/ic_launcher_background, windowSplashScreenAnimatedIcon.       |
| 144 | Brand §5.2   | Media Sosial: tidak ada spesifikasi untuk ukuran cover foto (Facebook, Twitter, LinkedIn).                             | Tambahkan dimensi: Facebook 820x312, Twitter 1500x500, LinkedIn 1584x396.         |
| 145 | Brand §5.3   | Merchandise: tidak ada panduan untuk warna dasar merchandise selain putih/abu/hijau.                                   | Tambahkan: hitam, navy, krem sebagai alternatif.                                  |
| 146 | Brand §5.4   | Kop Surat: tidak ada spesifikasi font size untuk alamat dan footer.                                                    | Tambahkan: alamat 10pt, footer 9pt.                                               |
| 147 | Brand §5.6   | Co-Branding: tidak ada aturan jika logo mitra lebih besar dari logo Seekitar.                                          | Tambahkan: logo harus memiliki proporsi visual yang sama (tidak lebih besar).     |
| 148 | Brand §6.2   | Email & Notifikasi: tidak ada template HTML untuk email.                                                               | Tambahkan contoh template email transaksional.                                    |
| 149 | Brand §7     | DO's & DON'Ts: tidak ada larangan untuk menggunakan logo di background berwarna tanpa cukup kontras.                   | Tambahkan: "Jangan gunakan logo di atas gambar/foto tanpa overlay cukup kontras". |
| 150 | Brand §8.2   | Domain: tidak disebutkan `seekitar.com` (jika ada).                                                                    | Tambahkan semua domain yang dimiliki.                                             |
| 151 | Brand §8.3   | PSE: tidak ada detail tentang kewajiban menyediakan kontak pengaduan.                                                  | Tambahkan: email `pengaduan@seekitar.id`, nomor WA resmi.                         |
| 152 | Brand §9     | Lampiran: tidak ada daftar lengkap file yang disediakan.                                                               | Tambahkan link ke repository aset atau drive.                                     |

---

## H. PRD — KEKURANGAN & KETIDAKSESUAIAN

| #   | Lokasi     | Issue                                                                                                                        | Rekomendasi Perbaikan                                                                                   |
| --- | ---------- | ---------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| 153 | PRD §2.2   | Target 3 bulan: 500 user, 150 toko, GMV 50 juta.                                                                             | Apakah target ini realistis untuk 1 kabupaten? Tambahkan asumsi.                                        |
| 154 | PRD §3.2   | Persona B: "teknisi AC lepas" — tapi di PRD tidak ada fitur untuk "keahlian" spesifik.                                       | Tambahkan: daftar keahlian/portofolio di profil penyedia.                                               |
| 155 | PRD §4     | Fase 2: "auto-bidding" — tidak dijelaskan di dokumen lain.                                                                   | Tambahkan detail di roadmap atau hapus.                                                                 |
| 156 | PRD §5.1.1 | Listing: "slot" untuk service, tapi di database `slot` TINYINT.                                                              | Jelaskan: `slot` = kapasitas per hari atau total slot? Perjelas.                                        |
| 157 | PRD §5.1.2 | Radius maksimal 25 km — di database `service_radius_km` default 15, PRD default 25.                                          | Konsisten: default 15 km untuk permintaan, maks 25 km.                                                  |
| 158 | PRD §5.1.3 | "Pesan Sekarang" untuk jasa: form pemilihan slot waktu — tidak ada di database.                                              | Tambahkan `listings.available_slots` JSON atau tabel terpisah.                                          |
| 159 | PRD §5.2.1 | Form permintaan: foto pendukung "maks 3 foto" — di database `customer_requests` tidak ada `images`.                          | Tambahkan kolom `images` JSON di database.                                                              |
| 160 | PRD §5.2.2 | Algoritma broadcast: "kategori layanan yang cocok" — relasi `stores.category_ids` dengan `customer_requests.category_id`.    | Tambahkan query di backend: `FIND_IN_SET(category_id, category_ids)` atau gunakan JSON `JSON_CONTAINS`. |
| 161 | PRD §5.2.2 | "Prioritas Broadcast: rating tertinggi" — tidak ada logika sorting di backend.                                               | Tambahkan: sort by `rating_avg DESC`, `total_reviews DESC`.                                             |
| 162 | PRD §5.2.3 | Penyedia tidak melihat identitas lengkap pembeli — di database, saat offer dibuat, tidak ada akses ke `users`.               | Implementasi: hanya tampilkan `name` (first name) dan rating.                                           |
| 163 | PRD §5.2.4 | Pembeli bisa mengurutkan penawaran: Harga Terendah, Rating Tertinggi, Jarak Terdekat.                                        | API harus mendukung parameter `sort_by` dan `order`.                                                    |
| 164 | PRD §5.3.2 | Level Verifikasi: "Level 3 Penyedia Pro" butuh NPWP — di database tidak ada `npwp`.                                          | Tambahkan `npwp` VARCHAR(20) NULL di `stores` atau `users`.                                             |
| 165 | PRD §5.3.3 | "Nama Toko uniqueness per kabupaten" — di database tidak ada kolom `district` atau `regency`.                                | Tambahkan `regency` VARCHAR(100) untuk scope uniqueness.                                                |
| 166 | PRD §5.3.3 | "Toko berstatus pending review, admin verifikasi lokasi di dalam kabupaten target" — tidak ada mekanisme geofencing.         | Tambahkan: admin dapat set polygon kabupaten, atau verifikasi manual.                                   |
| 167 | PRD §5.4.1 | State Diagram Barang: "penjual kirim/siap ambil" → status `Dikirim/Siap Diambil`.                                            | Tambahkan dua status terpisah: `dikirim` dan `siap_diambil`.                                            |
| 168 | PRD §5.4.2 | State Diagram Jasa: "penyedia mulai" → status `penyedia_mulai` tidak ada di database.                                        | Tambahkan status `dimulai` atau gunakan `diproses` dengan catatan.                                      |
| 169 | PRD §5.4.3 | Sewa: status `Dikembalikan` — tidak ada di database `orders.status`.                                                         | Tambahkan `dikembalikan` ke ENUM.                                                                       |
| 170 | PRD §5.5   | Rating: "pembeli dan penjual bisa saling menilai" — database `reviews` hanya satu arah (reviewer → reviewee).                | Tambahkan: dua ulasan per order (pembeli ke penjual, penjual ke pembeli).                               |
| 171 | PRD §5.5   | Dispute: "Tim admin wajib menanggapi dispute dalam 1x24 jam" — tidak ada SLA tracking di database.                           | Tambahkan `escalated_at`, `response_deadline`.                                                          |
| 172 | PRD §5.6   | Deep link WhatsApp: "ID Permintaan" — tidak disebutkan format.                                                               | Format: `https://seekitar.id/request/REQ-123` atau `https://wa.me/...?text=...`.                        |
| 173 | PRD §6.1   | Alur Pembeli: "Halaman 'Permintaan Saya' menampilkan status open, jumlah penawaran 0" — tidak ada UI detail.                 | Tambahkan wireframe atau deskripsi UI.                                                                  |
| 174 | PRD §6.2   | Alur Penyedia: "Notifikasi push: 'Permintaan Baru: Servis Kulkas (3.2 km)'" — tetapi API tidak mengirim jarak di notifikasi. | Tambahkan `distance_km` di payload notifikasi.                                                          |
| 175 | PRD §7.3   | Query geospasial: `$radius_meter` dikonversi dari km × 1000.                                                                 | Database menggunakan `ST_Distance_Sphere` yang output meter; konversi sudah benar.                      |
| 176 | PRD §8     | Skema `stores.category_ids` disebut VARCHAR(255) JSON atau comma-separated — di database JSON.                               | Konsisten: JSON.                                                                                        |
| 177 | PRD §8     | Skema `offers` tidak ada `delivery_fee` atau `additional_cost`.                                                              | Tambahkan `additional_cost` DECIMAL(12,2) DEFAULT 0.                                                    |
| 178 | PRD §8     | Skema `orders` tidak ada `notes` untuk catatan pembeli.                                                                      | Tambahkan `notes` TEXT NULL.                                                                            |
| 179 | PRD §9.1   | `GET /api/listings` — parameter `sort=nearest` membutuhkan lat/lng; tidak dijelaskan.                                        | Tambahkan: `lat` dan `lng` wajib untuk sort nearest.                                                    |
| 180 | PRD §9.2   | Contoh response `POST /requests/:id/offers` tidak menunjukkan `store_id`.                                                    | Tambahkan `store_id` di response.                                                                       |
| 181 | PRD §10.1  | Notifikasi "Permintaan baru cocok" — "Jarak [X] km" — tidak ada penjelasan bagaimana jarak dihitung.                         | Gunakan `ST_Distance_Sphere` antara `stores.location` dan `customer_requests.location`.                 |
| 182 | PRD §11.2  | PSE: "Sistem didaftarkan ke Kominfo" — tidak ada timeline.                                                                   | Tambahkan: daftarkan sebelum publikasi (2 bulan sebelum rilis).                                         |
| 183 | PRD §12    | Monetisasi: "Paket Penyedia Pro Rp49.000/bulan" — tidak ada fitur di database untuk langganan.                               | Tambahkan tabel `subscriptions` atau `user_subscriptions`.                                              |
| 184 | PRD §13    | KPI: "Match Rate ≥ 75%" — bagaimana menghitungnya di backend?                                                                | Tambahkan: `match_rate = requests_with_at_least_one_offer / total_requests`.                            |
| 185 | PRD §13    | KPI: "Time to First Offer ≤ 20 menit" — perlu timestamp di `offers.created_at` dan `customer_requests.created_at`.           | Sudah ada; hanya perlu query.                                                                           |
| 186 | PRD §14    | Roadmap: Minggu 1-2 "CI/CD pipeline" — tidak ada detail tools.                                                               | Tambahkan: GitHub Actions + Laravel Forge / Ploi.                                                       |
| 187 | PRD §14    | Roadmap: Minggu 3-4 "admin verifikasi toko" — tidak ada jadwal untuk admin dashboard.                                        | Dashboard admin sebaiknya dimulai minggu 5-6, bukan 13-14.                                              |
| 188 | PRD §15    | Risiko: "AI OCR di fase 2" — tidak ada di roadmap.                                                                           | Tambahkan di Fase 2 atau hapus dari risiko.                                                             |
| 189 | PRD §16    | Wireframe: tidak ada link Figma atau gambar.                                                                                 | Tambahkan link atau embed gambar.                                                                       |

---

## I. KONSISTENSI TERMINOLOGI & PENAMAAN

| #   | Lokasi   | Issue                                                                                  | Rekomendasi Perbaikan                                                                                                    |
| --- | -------- | -------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| 190 | Umum     | "Customer Requests" vs "Permintaan" vs "Kebutuhan"                                     | Konsisten: gunakan **"Permintaan"** di UI, **"customer_requests"** di database, **"Request"** di API.                    |
| 191 | Umum     | "Offers" vs "Penawaran"                                                                | Konsisten: **"Penawaran"** di UI, **"offers"** di database, **"Offer"** di API.                                          |
| 192 | Umum     | "Listings" vs "Katalog"                                                                | Konsisten: **"Listing"** di database, **"Jelajahi"** di UI.                                                              |
| 193 | Umum     | "Stores" vs "Toko" vs "Lapak"                                                          | Konsisten: **"Toko"** di UI, **"Lapak"** di branding, **"stores"** di database.                                          |
| 194 | Umum     | "Users" vs "Pengguna" vs "Warga"                                                       | Konsisten: **"Pengguna"** di UI, **"Warga"** di branding, **"users"** di database.                                       |
| 195 | Database | `customer_requests` — kenapa "customer" bukan "user"?                                  | Semua pengguna adalah customer. Gunakan `user_requests` atau tetap `customer_requests` (konsisten di semua dokumen).     |
| 196 | API      | `verification_level` di User, `verification_status` di Store — tidak konsisten suffix. | Konsisten: `verification_level` untuk user, `verification_status` untuk store (sudah benar).                             |
| 197 | Brand    | "Kebutuhan Sekitar" vs "Pasang Kebutuhan"                                              | Konsisten: **"Pasang Kebutuhan"** untuk aksi pembeli, **"Kebutuhan Sekitar"** untuk menu penyedia.                       |
| 198 | Database | `store_type` SET('goods','services','rental') — plural vs singular.                    | Konsisten: gunakan singular `good`, `service`, `rental` atau plural `goods`, `services`, `rentals`. Pilih dan konsisten. |

---

## J. IMPLEMENTASI KEAMANAN — KURANG DETAIL

| #   | Lokasi                          | Issue                                                                                             | Rekomendasi Perbaikan                                                                                                                         |
| --- | ------------------------------- | ------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| 199 | Server_Implementation_Guide §11 | Validasi: tidak ada validasi untuk UUID di route model binding.                                   | Tambahkan: `Route::pattern('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')`; atau gunakan `Route::bind` untuk validasi. |
| 200 | Server_Implementation_Guide     | Tidak ada implementasi CORS untuk API.                                                            | Tambahkan `config/cors.php` dan middleware `HandleCors`.                                                                                      |
| 201 | Server_Implementation_Guide     | Tidak ada implementasi encryption untuk data sensitif (KTP).                                      | Tambahkan: gunakan `Crypt::encryptString()` untuk menyimpan KTP.                                                                              |
| 202 | Server_Implementation_Guide     | Tidak ada proteksi XSS di Blade admin.                                                            | Tambahkan: `{{ }}` sudah auto-escape; untuk atribut gunakan `{!! !!}` dengan hati-hati.                                                       |
| 203 | Server_Implementation_Guide     | Tidak ada rate limiting untuk admin login.                                                        | Tambahkan: `throttle:5,1` untuk login admin.                                                                                                  |
| 204 | API Documentation               | Tidak ada mekanisme refresh token.                                                                | Sanctum tidak memiliki refresh token; gunakan token lifetime (1 tahun) atau implementasikan refresh token sendiri.                            |
| 205 | Mobile_Implementation_Guide     | Token disimpan di `flutter_secure_storage` — baik, tapi tidak ada auto-logout jika token expired. | Tambahkan interceptor untuk handle 401 dan logout.                                                                                            |
| 206 | PRD §11.1                       | "KTP & Selfie dienkripsi AES-256" — tidak ada implementasi di Server Guide.                       | Tambahkan: Laravel `Crypt` atau `Storage::disk('s3')->put('...', $file, 'private')` dengan enkripsi client-side.                              |
| 207 | PRD §11.3                       | "Validasi input ketat" — tidak ada contoh custom rule untuk format nomor HP Indonesia.            | Tambahkan custom rule: `phone:ID` (gunakan library `propaganistas/laravel-phone`).                                                            |

---

## K. DEPLOYMENT & OPERATIONAL — KURANG

| #   | Lokasi                      | Issue                                                              | Rekomendasi Perbaikan                                                                                              |
| --- | --------------------------- | ------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------ |
| 208 | Server_Implementation_Guide | Tidak ada panduan untuk setup Supervisor (queue worker, schedule). | Tambahkan konfigurasi supervisor: `[program:seekitar-worker]`, `[program:seekitar-schedule]`.                      |
| 209 | Server_Implementation_Guide | Tidak ada panduan untuk setup Redis.                               | Tambahkan: `redis-server`, `redis-cli` config, dan Laravel `config/database.php` untuk Redis.                      |
| 210 | Server_Implementation_Guide | Tidak ada panduan untuk setup S3/MinIO.                            | Tambahkan: environment variables `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`. |
| 211 | Server_Implementation_Guide | Tidak ada panduan untuk setup SSL/TLS.                             | Tambahkan: gunakan Let's Encrypt atau Cloudflare.                                                                  |
| 212 | Server_Implementation_Guide | Tidak ada panduan untuk backup database.                           | Tambahkan: cron job untuk `mysqldump` dan upload ke S3.                                                            |
| 213 | Server_Implementation_Guide | Tidak ada panduan untuk monitoring error (Sentry).                 | Tambahkan: install `sentry/sentry-laravel`, konfigurasi DSN.                                                       |
| 214 | Server_Implementation_Guide | Tidak ada panduan untuk performance monitoring (Laravel Pulse).    | Tambahkan: Laravel Pulse untuk dashboard performa.                                                                 |
| 215 | Mobile_Implementation_Guide | Tidak ada panduan untuk build release Android (keystore, signing). | Tambahkan: `key.properties`, `build.gradle` signing config.                                                        |
| 216 | Mobile_Implementation_Guide | Tidak ada panduan untuk build release iOS (distribution cert).     | Tambahkan: Fastlane atau Xcode Archive.                                                                            |
| 217 | Mobile_Implementation_Guide | Tidak ada panduan untuk environment config di Flutter.             | Tambahkan: `flutter_dotenv` atau `--dart-define` untuk API URL, FCM key, dll.                                      |
| 218 | PRD §14                     | Roadmap: tidak ada alokasi waktu untuk UAT dan bug fixing.         | Tambahkan 2 minggu buffer di akhir.                                                                                |

---

## L. DATABASE — PERFORMANCE & INDEX

| #   | Lokasi       | Issue                                                                                                    | Rekomendasi Perbaikan                                                                                                             |
| --- | ------------ | -------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| 219 | Database §7  | Index `cr_status_expires_idx` (`status`, `expires_at`) — urutan kolom penting.                           | Pastikan urutan: `status` (kardinalitas rendah) + `expires_at` (kardinalitas tinggi) sudah optimal; tes `EXPLAIN`.                |
| 220 | Database §7  | Tidak ada index untuk `customer_requests.category_id` + `status` untuk query broadcast.                  | Tambahkan index: `cr_category_status_idx` (`category_id`, `status`).                                                              |
| 221 | Database §7  | Tidak ada index untuk `offers.request_id` + `status` untuk pembeli lihat penawaran.                      | Sudah ada FK index; tambahkan `offers_status_request_idx` (`request_id`, `status`).                                               |
| 222 | Database §7  | Tidak ada index untuk `orders.buyer_id` + `status` untuk riwayat.                                        | Tambahkan `orders_buyer_status_idx` (`buyer_id`, `status`).                                                                       |
| 223 | Database §7  | Fulltext index `listings_ft_title_desc` — MySQL 8.0 mendukung `ngram` parser untuk Bahasa Indonesia.     | Tambahkan: `FULLTEXT INDEX ft_title_desc (title, description) WITH PARSER ngram`.                                                 |
| 224 | Database §7  | Spatial index sudah ada; pastikan SRID 4326.                                                             | Sudah, tapi tambahkan `SPATIAL INDEX stores_location_spatial (location)`.                                                         |
| 225 | Database §11 | Query radius: menggunakan `ST_Distance_Sphere` — untuk performa, gunakan `ST_Buffer` atau `MBRContains`. | Untuk optimasi, gunakan `ST_Within(location, ST_Buffer(point, radius_in_meters))` untuk filter awal sebelum `ST_Distance_Sphere`. |

---

## M. UI/UX — KURANG SPESIFIK

| #   | Lokasi                            | Issue                                                                          | Rekomendasi Perbaikan                                                      |
| --- | --------------------------------- | ------------------------------------------------------------------------------ | -------------------------------------------------------------------------- |
| 226 | Server_Implementation_Guide §8    | Admin sidebar: tidak ada kondisi menu berdasarkan permission.                  | Tambahkan `@can` di setiap menu item.                                      |
| 227 | Server_Implementation_Guide §9    | Halaman admin: tidak ada breadcrumb di contoh kode.                            | Tambahkan `@section('breadcrumb')` di layout.                              |
| 228 | Server_Implementation_Guide §22.2 | Datatables: tidak ada server-side processing untuk `parent_name` dan `action`. | Sudah ada, tapi tambahkan `orderable` dan `searchable` di DataTable class. |
| 229 | Mobile_Implementation_Guide §16   | Shimmer: tidak ada contoh implementasi.                                        | Tambahkan: `shimmer` package dengan `Shimmer.fromColors`.                  |
| 230 | Mobile_Implementation_Guide §16   | Empty state: tidak ada contoh ilustrasi.                                       | Tambahkan: `lottie` atau `svg` untuk empty state.                          |
| 231 | Brand §5.1                        | UI Global: tidak ada spesifikasi untuk `FloatingActionButton`.                 | Tambahkan: FAB dengan icon "plus", warna hijau, posisi kanan bawah.        |
| 232 | Brand §5.1                        | Bottom Navigation: tidak ada spesifikasi untuk badge notifikasi (angka merah). | Tambahkan: badge muncul jika ada notifikasi baru.                          |

---

## N. QUEUE & JOB — TIDAK DIJELASKAN DETAIL

| #   | Lokasi                          | Issue                                                      | Rekomendasi Perbaikan                                                                                                    |
| --- | ------------------------------- | ---------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| 233 | Server_Implementation_Guide §14 | `BroadcastRequestJob` — tidak ada detail query dan logika. | Tambahkan implementasi: query stores dengan `JSON_CONTAINS(category_ids, category_id)`, filter radius, kirim notifikasi. |
| 234 | Server_Implementation_Guide §14 | Tidak ada Job untuk `UpdateStoreRating` setelah review.    | Tambahkan: `UpdateStoreRating` job.                                                                                      |
| 235 | Server_Implementation_Guide §14 | Tidak ada Job untuk `CloseExpiredRequests` (scheduler).    | Tambahkan: `CloseExpiredRequests` command dan schedule.                                                                  |
| 236 | Server_Implementation_Guide §14 | Tidak ada retry policy untuk job gagal.                    | Tambahkan: `$tries = 3`, `$backoff = [30, 60, 120]`.                                                                     |

---

## O. TES — TIDAK ADA DETAIL

| #   | Lokasi                          | Issue                                              | Rekomendasi Perbaikan                                                                         |
| --- | ------------------------------- | -------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| 237 | Server_Implementation_Guide §20 | Testing: tidak ada daftar test untuk Admin Panel.  | Tambahkan: Feature test untuk `CategoryController`, `UserController`, `StoreController`, dll. |
| 238 | Server_Implementation_Guide §20 | Tidak ada contoh test untuk geospasial.            | Tambahkan: test query radius dengan mock koordinat.                                           |
| 239 | Server_Implementation_Guide §20 | Tidak ada test untuk API (Sanctum authentication). | Tambahkan: test untuk endpoint `/auth/me` dengan token valid/invalid.                         |
| 240 | Mobile_Implementation_Guide §17 | Widget test: tidak ada contoh.                     | Tambahkan: test untuk `LoginPage` dengan provider override.                                   |
| 241 | Mobile_Implementation_Guide §17 | Integration test: tidak ada detail.                | Tambahkan: test flow login → explore → create request.                                        |

---

## P. NOTIFIKASI — KURANG SPESIFIK

| #   | Lokasi                          | Issue                                                             | Rekomendasi Perbaikan                                                                                    |
| --- | ------------------------------- | ----------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- |
| 242 | Server_Implementation_Guide §15 | FCM: tidak ada detail payload notifikasi.                         | Tambahkan payload: `{"notification":{"title":"...","body":"..."},"data":{"type":"request","id":"..."}}`. |
| 243 | Server_Implementation_Guide §15 | WhatsApp: tidak ada template OTP.                                 | Tambahkan template: "Kode OTP Seekitar Anda: {{otp}}. Jangan bagikan ke siapa pun."                      |
| 244 | Server_Implementation_Guide §15 | Tidak ada mekanisme untuk mengirim notifikasi ke multiple device. | Tambahkan: user punya multiple FCM token; kirim ke semua.                                                |
| 245 | Server_Implementation_Guide §15 | Tidak ada mekanisme untuk mengecek status delivery notifikasi.    | Tambahkan: Firebase Analytics untuk tracking notifikasi.                                                 |
| 246 | Mobile_Implementation_Guide §14 | FCM: tidak ada kode untuk `onMessageOpenedApp` navigasi.          | Tambahkan: `GoRouter` untuk handle `data.screen` dan `data.entity_id`.                                   |

---

## Q. EVENT & LISTENER — TIDAK DIJELASKAN

| #   | Lokasi                          | Issue                                         | Rekomendasi Perbaikan                                                                                  |
| --- | ------------------------------- | --------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| 247 | Server_Implementation_Guide §13 | Event: tidak ada daftar event yang digunakan. | Tambahkan: `RequestCreated`, `OfferSubmitted`, `OfferAccepted`, `OrderStatusChanged`, `ReviewCreated`. |
| 248 | Server_Implementation_Guide §13 | Listener: tidak ada daftar listener.          | Tambahkan: `BroadcastRequestListener`, `SendOfferNotificationListener`, `UpdateStoreRatingListener`.   |

---

## R. API RESPONSE — STANDARISASI

| #   | Lokasi                          | Issue                                                                                  | Rekomendasi Perbaikan                                                                                   |
| --- | ------------------------------- | -------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| 249 | API Documentation               | Response untuk list data: `data` langsung array, tidak ada `meta`.                     | Tambahkan `meta` untuk paginasi: `{"current_page":1,"per_page":15,"total":120,"last_page":8}`.          |
| 250 | API Documentation               | Error 422: `errors` object dengan field errors; tidak ada contoh untuk multiple error. | Tambahkan contoh: `{"errors":{"phone":["Format nomor tidak valid"],"otp":["Kode OTP harus 6 digit"]}}`. |
| 251 | Server_Implementation_Guide §17 | Tidak ada trait `ApiResponse` untuk konsistensi.                                       | Tambahkan trait dengan method `successResponse()`, `errorResponse()`, `validationErrorResponse()`.      |

---

## S. GEOSPASIAL — DETAIL QUERY

| #   | Lokasi                          | Issue                                                                                                  | Rekomendasi Perbaikan                                                     |
| --- | ------------------------------- | ------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------- |
| 252 | Database §11                    | Query radius: `ST_GeomFromText('POINT({$longitude} {$latitude})', 4326)` — parameter posisi (lng lat). | Pastikan format: `POINT(lng lat)` bukan `POINT(lat lng)`; dokumentasikan. |
| 253 | Server_Implementation_Guide §16 | Tidak ada contoh untuk reverse geocoding (koordinat → alamat).                                         | Tambahkan: menggunakan Google Maps Geocoding API atau paket `geocoder`.   |
| 254 | PRD §7.3                        | $radius_meter = $radius_km \* 1000;                                                                    | Sudah benar.                                                              |
| 255 | Database                        | Tidak ada kolom `address` di `stores` dan `users` untuk alamat teks.                                   | Tambahkan `address` TEXT NULL untuk display di UI.                        |

---

## T. FORMAT & DOKUMENTASI

| #   | Lokasi                      | Issue                                                      | Rekomendasi Perbaikan                                                           |
| --- | --------------------------- | ---------------------------------------------------------- | ------------------------------------------------------------------------------- |
| 256 | Semua dokumen               | Tanggal "27 Juli 2026" — apakah ini future date atau typo? | Jika dokumen dibuat 2026, pastikan konsisten; jika typo, perbaiki ke 2025/2024. |
| 257 | Semua dokumen               | Versi dokumen berbeda-beda (2.0, 1.0, 3.0).                | Konsisten: gunakan versi global (misal `v2.0`) di semua dokumen.                |
| 258 | Server_Implementation_Guide | Tidak ada `README.md` atau `CONTRIBUTING.md`.              | Tambahkan untuk onboarding developer.                                           |
| 259 | Mobile_Implementation_Guide | Tidak ada `analysis_options.yaml` untuk linting.           | Tambahkan aturan linting.                                                       |
| 260 | API Documentation           | Tidak ada Postman/Insomnia collection.                     | Tambahkan link ke collection.                                                   |

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
