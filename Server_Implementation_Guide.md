# 📄 Seekitar – Server Implementation Guide

**Versi:** 2.0 (Ultra‑Detailed · Production‑Ready)  
**Tanggal:** 27 Juli 2026  
**Target:** Laravel 13 + PHP 8.3+ + MySQL 8.0.34+ + Bootstrap 5.3.x + Yajra Datatables 13 + Spatie Permission 8

> 📌 Versi mengacu pada [`TECH_STACK.md`](TECH_STACK.md) sebagai sumber kebenaran tunggal.

---

## DAFTAR ISI
    
1. [Ikhtisar & Prinsip](#1-ikhtisar--prinsip)
2. [Tech Stack & Versi](#2-tech-stack--versi)
3. [Instalasi & Konfigurasi Library Tambahan](#3-instalasi--konfigurasi-library-tambahan)
4. [Struktur Proyek](#4-struktur-proyek)
   - 4.1 Kenapa `app/Services/` Diperlukan
   - 4.2 Kenapa `app/Enums/` Diperlukan
   - 4.3 Kenapa `app/Jobs/` & `app/Listeners/` Dipisah
5. [Middleware & Pipeline](#5-middleware--pipeline)
6. [Autentikasi & Otorisasi](#6-autentikasi--otorisasi)
   - 6.1 Sanctum & Token
   - 6.2 Spatie Permission (Roles & Abilities)
   - 6.3 Gates & Policies
7. [Routing Lengkap](#7-routing-lengkap)
8. [Admin Panel – Menu & Navigasi](#8-admin-panel--menu--navigasi)
9. [Halaman Admin – Detail Tampilan & Form](#9-halaman-admin--detail-tampilan--form)
   - 9.1 Dashboard Admin
   - 9.2 Manajemen Kategori
   - 9.3 Verifikasi Pengguna & Toko
   - 9.4 Manajemen Pengguna
   - 9.5 Manajemen Toko
   - 9.6 Manajemen Listing
   - 9.7 Manajemen Permintaan (Customer Requests)
   - 9.8 Manajemen Penawaran (Offers)
   - 9.9 Manajemen Pesanan (Orders)
   - 9.10 Manajemen Dispute
   - 9.11 Manajemen Ulasan (Reviews)
   - 9.12 Pengaturan Sistem
10. [Controllers & Actions](#10-controllers--actions)
11. [Form Requests & Validasi](#11-form-requests--validasi)
12. [Models & Relationships](#12-models--relationships)
13. [Observers & Events](#13-observers--events)
14. [Jobs & Queue](#14-jobs--queue)
15. [Notifikasi (Push & WhatsApp)](#15-notifikasi-push--whatsapp)
16. [Geospasial & Query Radius](#16-geospasial--query-radius)
17. [API Response & Paginasi (Web & API)](#17-api-response--paginasi-web--api)
18. [Error Handling & Logging](#18-error-handling--logging)
19. [Migration & Seeder (Lengkap)](#19-migration--seeder-lengkap)
20. [Testing](#20-testing)
21. [Deployment](#21-deployment)
22. [Lampiran: Contoh Kode Blade & Controller](#22-lampiran-contoh-kode-blade--controller)

---

## 1. IKHTISAR & PRINSIP

Server Seekitar menyediakan:

- **REST API** untuk aplikasi Flutter.
- **Web Admin Panel** menggunakan Laravel Blade + Bootstrap 5.3.x + Yajra Datatables untuk pengelolaan internal (verifikasi, manajemen data, dispute).
- **Web Public** halaman katalog, landing page (SEO-friendly).

Prinsip desain:

- **Thin controllers, fat models** – logika bisnis di model/service.
- **Validasi ketat** – FormRequest + custom rule.
- **Database-first integrity** – FK, constraint, spatial index.
- **UUID sebagai primary key** – mencegah enumerasi.
- **Observers** untuk side‑effect (rating, notifikasi).
- **Job antrian** untuk broadcast permintaan ke penyedia.
- **Soft delete** untuk users, stores, listings.
- **Responsive UI** – Bootstrap 5.3.x dengan komponen siap pakai.

---

## 2. TECH STACK & VERSI

| Komponen          | Teknologi                              | Constraint          |
| ----------------- | -------------------------------------- | ------------------- |
| Bahasa            | PHP 8.3+                               | `^8.3`              |
| Framework         | Laravel 13                             | `^13.8`             |
| Database          | MySQL 8.0.34+ (InnoDB, Spatial)        | —                   |
| Cache & Queue     | Redis 7                                | —                   |
| Storage           | AWS S3 / MinIO                         | —                   |
| Push Notification | Firebase Cloud Messaging               | —                   |
| WhatsApp          | Twilio / Kirim WA API                  | —                   |
| Admin UI          | Bootstrap 5.3.x, Yajra Datatables 13.x | `^13.0`             |
| Permission        | Spatie Laravel Permission 8.x          | `^8.0`              |
| API Auth          | Laravel Sanctum 4.x                    | `^4.0`              |
| CI/CD             | GitHub Actions                         | —                   |

### Catatan Kompatibilitas (penting)

Constraint di atas **wajib** dipakai persis. Kombinasi yang salah akan langsung
gagal saat `composer require`:

- **Yajra Datatables** — versi mayornya mengikuti versi mayor Laravel. Laravel 13
  → `^13.0`. Memakai `^11.0` akan ditolak Composer karena paket itu mengunci
  `illuminate/support: ^11`.
- **Spatie Permission** — `^8.0` adalah versi pertama yang mendukung
  `illuminate/auth: ^12.0|^13.0`. Versi `^6.0` mentok di Laravel 11/12 dan
  **tidak akan ter-install** di Laravel 13.
- **PHP 8.3+** — batas atasnya `< 9.0` (dari `^8.3`). Jangan mengunci ke versi
  patch tertentu seperti `8.3.30`.

---

## 3. INSTALASI & KONFIGURASI LIBRARY TAMBAHAN

### 3.1 Yajra Datatables

```bash
composer require yajra/laravel-datatables-oracle:^13.0
```

> ⚠️ Nama paketnya **`yajra/laravel-datatables-oracle`**, bukan
> `yajra/laravel-datatables` (itu nama repo GitHub-nya, bukan nama paket
> Composer). Versi `^13.0` wajib untuk Laravel 13.

Opsional, jika butuh tombol export/print:

```bash
composer require yajra/laravel-datatables-buttons:^13.0
```

**Konfigurasi:** Tidak ada file konfig khusus. Langsung gunakan facade `DataTables`.

### 3.2 Spatie Laravel Permission

```bash
composer require spatie/laravel-permission:^8.0
```

> ⚠️ Harus `^8.0`. Versi `^6.0` hanya mendukung sampai Laravel 11/12 dan akan
> gagal resolusi dependensi di Laravel 13.

Publish migration dan config:

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

Tambahkan trait `HasRoles` pada model `User`:

```php
use Spatie\Permission\Traits\HasRoles;
class User extends Authenticatable {
    use HasApiTokens, HasUuids, SoftDeletes, HasRoles;
}
```

**Guard:** kita gunakan `sanctum` (api) dan `web` (admin). Di config/permission.php, pastikan guards mencakup keduanya.

### 3.3 Bootstrap 5 & Asset

Kita gunakan Laravel Breeze (opsional) atau langsung kompilasi Bootstrap 5.3.x via Vite (tidak dianjurkan karena Anda minta tanpa Vite).  
**Alternatif:** Gunakan Bootstrap 5.3.x CDN di layout Blade utama.

**Layout Admin (`resources/views/layouts/admin.blade.php`)** akan menyertakan CSS & JS Bootstrap 5.3.x, Datatables, dan Font Awesome.

---

## 4. STRUKTUR PROYEK

Struktur lengkap (bukan hanya bagian admin). Folder di luar bawaan Laravel
ditandai dengan penjelasan singkat alasan keberadaannya.

```
app/
├── Console/
│   └── Commands/
│       └── CloseExpiredRequests.php   # Scheduler: tutup permintaan kadaluarsa
├── DataTables/                        # Server-side processing Yajra (admin)
│   ├── UsersDataTable.php
│   ├── StoresDataTable.php
│   ├── ListingsDataTable.php
│   ├── RequestsDataTable.php
│   ├── OffersDataTable.php
│   ├── OrdersDataTable.php
│   ├── DisputesDataTable.php
│   └── ReviewsDataTable.php
├── Enums/                             # Domain nilai tetap (backed enum)
│   ├── OrderStatus.php
│   ├── OrderType.php
│   ├── RequestStatus.php
│   ├── OfferStatus.php
│   ├── ListingStatus.php
│   ├── ListingType.php
│   ├── StoreType.php
│   ├── VerificationStatus.php
│   ├── VerificationLevel.php
│   ├── PaymentMethod.php
│   └── DisputeStatus.php
├── Events/
│   ├── CustomerRequestCreated.php
│   ├── OfferAccepted.php
│   ├── OrderStatusChanged.php
│   └── ReviewSubmitted.php
├── Exceptions/
│   └── InvalidOrderTransitionException.php
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── UserController.php
│   │   │   ├── StoreController.php
│   │   │   ├── ListingController.php
│   │   │   ├── CustomerRequestController.php
│   │   │   ├── OfferController.php
│   │   │   ├── OrderController.php
│   │   │   ├── ReviewController.php
│   │   │   ├── DisputeController.php
│   │   │   └── SettingController.php
│   │   └── Api/v1/...
│   ├── Middleware/
│   │   └── EnsureStoreOwner.php
│   ├── Requests/
│   │   ├── Admin/
│   │   │   ├── CategoryRequest.php
│   │   │   ├── UserRequest.php
│   │   │   ├── StoreRequest.php
│   │   │   ├── SettingRequest.php
│   │   │   └── ...
│   │   └── Api/
│   │       ├── CreateStoreRequest.php
│   │       ├── CreateListingRequest.php
│   │       ├── CreateCustomerRequestRequest.php
│   │       └── CreateOfferRequest.php
│   └── Resources/                     # API Resource (transformer JSON)
│       ├── StoreResource.php
│       ├── ListingResource.php
│       ├── OrderResource.php
│       └── OfferResource.php
├── Jobs/                              # Antrian Redis (asinkron)
│   ├── BroadcastRequestJob.php        # Sebar permintaan ke penyedia dalam radius
│   ├── SendPushNotificationJob.php
│   ├── SendWhatsAppOtpJob.php
│   └── RecalculateStoreRatingJob.php
├── Listeners/
│   ├── DispatchRequestBroadcast.php   # CustomerRequestCreated -> BroadcastRequestJob
│   ├── SendOfferAcceptedNotification.php
│   ├── SendOrderStatusNotification.php
│   └── UpdateStoreRatingOnReview.php
├── Models/
│   ├── User.php
│   ├── Store.php
│   ├── Category.php
│   ├── Listing.php
│   ├── CustomerRequest.php
│   ├── Offer.php
│   ├── Order.php
│   ├── Review.php
│   └── Dispute.php
├── Observers/                         # Side-effect otomatis pada model
│   ├── ReviewObserver.php             # Perbarui rating_avg & total_reviews
│   └── OrderObserver.php              # Catat completed_at saat status selesai
├── Policies/
│   ├── StorePolicy.php
│   ├── ListingPolicy.php
│   ├── OrderPolicy.php
│   └── OfferPolicy.php
├── Providers/
│   ├── AppServiceProvider.php
│   └── EventServiceProvider.php
└── Services/                          # Logika bisnis lintas controller
    ├── BroadcastService.php           # Pencocokan penyedia untuk sebuah permintaan
    ├── GeolocationService.php         # Query radius ST_Distance_Sphere
    ├── NotificationService.php        # Abstraksi FCM + WhatsApp
    ├── OrderStateMachine.php          # Validasi transisi status pesanan
    ├── OtpService.php                 # Generate, simpan (Redis), verifikasi OTP
    └── WhatsAppService.php            # Klien Twilio / Kirim WA

database/
├── factories/
│   ├── UserFactory.php
│   ├── StoreFactory.php
│   └── ListingFactory.php
├── migrations/
│   └── ...                            # Lihat DATABASE.md §10 untuk urutannya
└── seeders/
    ├── DatabaseSeeder.php
    ├── CategorySeeder.php             # 24 kategori, wajib saat deploy awal
    ├── RolesAndPermissionsSeeder.php   # Role & permission Spatie
    └── DummyDataSeeder.php            # Data contoh, hanya untuk development

routes/
├── api.php                            # Endpoint mobile (guard sanctum)
├── web.php                            # Web publik SEO
├── admin.php                          # Panel admin (guard web + role)
└── console.php                        # Jadwal scheduler
```

### Kenapa `app/Services/` Diperlukan

Controller sebaiknya tipis: validasi masuk, panggil service, kembalikan response.
Tiga alur di Seekitar dipakai dari lebih dari satu tempat, jadi tidak layak
ditaruh di controller:

| Service               | Dipakai oleh                                            | Alasan                                                                 |
| :-------------------- | :------------------------------------------------------ | :---------------------------------------------------------------------- |
| `BroadcastService`    | API create request, admin re-broadcast, job antrian     | Aturan pencocokan penyedia cukup rumit dan harus konsisten             |
| `GeolocationService`  | Pencarian toko, pencarian listing, pencocokan broadcast | Raw query spasial terpusat di satu tempat, mudah diuji & dioptimasi    |
| `NotificationService` | Listener, job, controller admin                         | Satu pintu ke FCM & WhatsApp, memudahkan mock saat testing             |

Contoh kerangka:

```php
// app/Services/GeolocationService.php
namespace App\Services;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;

class GeolocationService
{
    /** Batasi query ke radius tertentu (meter) dari sebuah titik. */
    public function withinRadius(Builder $query, float $lat, float $lng, float $radiusKm): Builder
    {
        return $query->whereRaw(
            'ST_Distance_Sphere(location, ST_GeomFromText(?, 4326)) <= ?',
            ["POINT($lng $lat)", $radiusKm * 1000]
        );
    }

    /** Tambahkan kolom jarak (km) agar bisa diurutkan & ditampilkan. */
    public function selectDistance(Builder $query, float $lat, float $lng): Builder
    {
        return $query->selectRaw(
            '*, ST_Distance_Sphere(location, ST_GeomFromText(?, 4326)) / 1000 AS distance_km',
            ["POINT($lng $lat)"]
        );
    }
}
```

> ⚠️ Perhatikan urutan `POINT(longitude latitude)` — terbalik dari kebiasaan
> menulis `lat, lng`. Ini sumber bug geospasial yang paling sering terjadi.
> Memusatkannya di `GeolocationService` mencegah kesalahan berulang.

### Kenapa `app/Enums/` Diperlukan

`DATABASE.md` memakai ENUM di level MySQL agar nilai tidak liar. Enum PHP
membuat jaminan yang sama berlaku di level aplikasi, sekaligus memberi
autocomplete dan mencegah salah ketik string.

```php
// app/Enums/OrderStatus.php
namespace App\Enums;

enum OrderStatus: string
{
    case MenungguKonfirmasi = 'menunggu_konfirmasi';
    case Diproses           = 'diproses';
    case Dikirim            = 'dikirim';
    case Selesai            = 'selesai';
    case Dibatalkan         = 'dibatalkan';
    case Dispute            = 'dispute';

    /** Label untuk UI admin & mobile. */
    public function label(): string
    {
        return match ($this) {
            self::MenungguKonfirmasi => 'Menunggu Konfirmasi',
            self::Diproses           => 'Diproses',
            self::Dikirim            => 'Dikirim / Siap Diambil',
            self::Selesai            => 'Selesai',
            self::Dibatalkan         => 'Dibatalkan',
            self::Dispute            => 'Dispute',
        };
    }

    /** Status akhir tidak boleh berubah lagi. */
    public function isFinal(): bool
    {
        return in_array($this, [self::Selesai, self::Dibatalkan], true);
    }
}
```

Nilai enum **wajib** sama persis dengan ENUM di `DATABASE.md`:

| Enum                 | Nilai                                                                        | Sumber                        |
| :------------------- | :--------------------------------------------------------------------------- | :---------------------------- |
| `OrderStatus`        | `menunggu_konfirmasi`, `diproses`, `dikirim`, `selesai`, `dibatalkan`, `dispute` | `orders.status`           |
| `OrderType`          | `goods`, `service`, `rental`                                                 | `orders.order_type`           |
| `RequestStatus`      | `open`, `closed`, `expired`                                                  | `customer_requests.status`    |
| `OfferStatus`        | `pending`, `accepted`, `rejected`                                            | `offers.status`               |
| `ListingStatus`      | `active`, `sold`, `hidden`                                                   | `listings.status`             |
| `ListingType`        | `product`, `service`, `rental`                                               | `listings.listing_type`       |
| `StoreType`          | `goods`, `services`, `rental`                                                | `stores.store_type` (SET)     |
| `VerificationStatus` | `pending`, `verified`, `rejected`                                            | `stores.verification_status`  |
| `PaymentMethod`      | `cod`, `transfer`                                                            | `orders.payment_method`       |
| `DisputeStatus`      | `open`, `resolved`                                                           | `disputes.status`             |
| `VerificationLevel`  | `1`, `2`, `3` (int)                                                          | `users.verification_level`    |

> ⚠️ **Perhatikan bedanya:** `StoreType` memakai `services` (jamak), sedangkan
> `ListingType` dan `OrderType` memakai `service` (tunggal). Ini memang berbeda
> di skema database — jangan "dirapikan" tanpa mengubah migrasi.

`VerificationLevel` bertipe integer, bukan string:

```php
// app/Enums/VerificationLevel.php
namespace App\Enums;

enum VerificationLevel: int
{
    case Basic    = 1;  // Nomor HP terverifikasi
    case Verified = 2;  // KTP diverifikasi — syarat membuka toko
    case Pro      = 3;  // Usaha tervalidasi, prioritas broadcast lebih tinggi

    public function canOpenStore(): bool
    {
        return $this->value >= self::Verified->value;
    }
}
```

Pakai di model lewat casting agar konversi otomatis:

```php
// app/Models/Order.php
protected function casts(): array
{
    return [
        'status'         => OrderStatus::class,
        'order_type'     => OrderType::class,
        'payment_method' => PaymentMethod::class,
    ];
}
```

### ⚠️ Catatan: Status Alur Jasa Belum Ada di ENUM

`PRD.md` §5.4 menjelaskan alur pesanan **jasa** dengan status `Dijadwalkan`,
`Dalam Pengerjaan`, dan `Menunggu Konfirmasi Pembeli`. Ketiganya **tidak ada**
di ENUM `orders.status` pada `DATABASE.md`, yang hanya punya enam nilai.

Ini perlu diputuskan sebelum modul pesanan dibangun. Dua opsi:

1. **Petakan ke status yang ada** — `Dijadwalkan` dan `Dalam Pengerjaan`
   keduanya jadi `diproses`, detail waktunya disimpan di kolom terpisah.
   MVP lebih sederhana, tapi UI kehilangan sebagian informasi.
2. **Tambahkan nilai ENUM baru** — perlu migrasi `ALTER TABLE` dan
   memperluas `OrderStatus`. Lebih sesuai PRD, tapi state machine jadi
   bercabang per `order_type`.

Selama belum diputuskan, `OrderStatus` di atas mengikuti `DATABASE.md`
(sumber kebenaran skema).

### Kenapa `app/Jobs/` & `app/Listeners/` Dipisah

Alur broadcast sengaja dipecah agar request API tetap cepat:

```
POST /requests
  └─> simpan customer_requests
  └─> event CustomerRequestCreated        (sinkron, ringan)
        └─> listener DispatchRequestBroadcast
              └─> dispatch BroadcastRequestJob   (masuk antrian Redis)
                    └─> BroadcastService cari penyedia dalam radius
                    └─> dispatch SendPushNotificationJob per penyedia
```

Response ke pembeli langsung kembali setelah data tersimpan; pencarian penyedia
dan pengiriman notifikasi berjalan di worker. Konsekuensinya notifikasi
**tidak instan** — ini sudah dicatat juga di Mobile Guide.

```php
// app/Jobs/BroadcastRequestJob.php
namespace App\Jobs;

use App\Models\CustomerRequest;
use App\Services\BroadcastService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BroadcastRequestJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public string $customerRequestId) {}

    public function handle(BroadcastService $broadcast): void
    {
        $request = CustomerRequest::find($this->customerRequestId);

        // Permintaan bisa saja sudah ditutup sebelum job sempat jalan.
        if (! $request || $request->status !== \App\Enums\RequestStatus::Open) {
            return;
        }

        $broadcast->notifyMatchingStores($request);
    }
}
```

> Kirim **ID**, bukan objek model, ke constructor job. Model yang di-serialize
> bisa basi saat job akhirnya dieksekusi.

---

## 5. MIDDLEWARE & PIPELINE (Tambahan Web)

**Web Routes Middleware:**

- `auth` – memastikan user login.
- `role:admin` – hanya user dengan role admin yang bisa akses admin panel. Kita daftarkan di `Kernel.php` atau gunakan middleware Spatie.

---

## 6. AUTENTIKASI & OTORISASI

### 6.1 Sanctum & Token (API)

Seekitar memakai Sanctum dalam **dua mode sekaligus**. Membedakan keduanya itu
penting, karena salah konfigurasi di sini adalah penyebab paling umum error
`419 CSRF token mismatch` di aplikasi mobile.

| Kanal                     | Mode          | Mekanisme                                  |
| :------------------------ | :------------ | :------------------------------------------ |
| **Mobile app** (`/api/*`) | **Stateless** | Bearer personal access token, tanpa cookie  |
| **Admin panel** (web)     | **Stateful**  | Session cookie Laravel biasa                |

**Instalasi:**

```bash
composer require laravel/sanctum:^4.0
php artisan install:api
```

**Konfigurasi `.env`:**

```env
# Domain yang boleh memakai autentikasi berbasis cookie (web admin).
# Mobile app TIDAK dimasukkan ke sini — ia memakai Bearer token.
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:8000,127.0.0.1,127.0.0.1:8000,admin.seekitar.id

SESSION_DOMAIN=.seekitar.id
SESSION_DRIVER=redis
```

> ⚠️ **Jangan** memasukkan `api.seekitar.id` ke `SANCTUM_STATEFUL_DOMAINS`.
> Jika dimasukkan, Sanctum memperlakukan request mobile sebagai stateful dan
> mulai menuntut CSRF token, sehingga request dari aplikasi gagal dengan 419.

**Middleware stateful hanya untuk web admin** (`bootstrap/app.php`):

```php
->withMiddleware(function (Middleware $middleware) {
    // Hanya grup 'web'/admin yang perlu cookie-based auth.
    $middleware->statefulApi();

    $middleware->alias([
        'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    ]);
})
```

**Membuat token dengan ability terbatas:**

```php
// Batasi cakupan token sesuai peran, jangan beri akses penuh.
$token = $user->createToken('mobile-app', ['user'])->plainTextToken;

// Untuk pemilik toko:
$token = $user->createToken('mobile-app', ['user', 'store-owner'])->plainTextToken;
```

Melindungi route dengan ability:

```php
Route::middleware(['auth:sanctum', 'ability:store-owner'])->group(function () {
    Route::post('/listings', [ListingController::class, 'store']);
});
```

**Guard untuk Spatie Permission** — karena ada dua kanal, `config/permission.php`
harus mengenali keduanya. Model `User` perlu tahu guard mana yang dipakai:

```php
// config/auth.php — pastikan kedua guard ada
'guards' => [
    'web'     => ['driver' => 'session', 'provider' => 'users'],
    'sanctum' => ['driver' => 'sanctum', 'provider' => 'users'],
],
```

### 6.2 Spatie Permission (Roles & Abilities)

**Role:**

- `super-admin` (akses semua, kelola admin)
- `admin` (akses panel admin kecuali manajemen admin lain)
- `user` (default pengguna biasa)

**Permissions (abilities) untuk Admin:**

- `manage-users`
- `verify-users`
- `manage-stores`
- `verify-stores`
- `manage-categories`
- `manage-listings`
- `manage-requests`
- `manage-offers`
- `manage-orders`
- `manage-disputes`
- `manage-reviews`
- `manage-settings`

Semua permission diberikan pada role `super-admin`. Role `admin` bisa diberikan sebagian (misal tidak bisa `manage-users` untuk mencegah hapus sesama admin).

**Cara assign (Seeder):** lihat bagian 19.

### 6.3 Gates & Policies

Policies untuk API (StorePolicy, ListingPolicy, dll.) tetap sama.  
Untuk admin web, kita bisa menggunakan `Gate::allow` berdasarkan permission. Di controller, gunakan `$this->authorize('manage-users')` atau cek dengan `if (auth()->user()->can('manage-users'))`.

---

## 7. ROUTING LENGKAP

### Web Routes (`routes/web.php`)

```php
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Categories
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Users
    Route::get('users', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

    // Verifikasi massal
    Route::post('verifications/users/{user}/approve', [VerificationController::class, 'approveUser'])->name('admin.verify.user.approve');
    Route::post('verifications/users/{user}/reject', [VerificationController::class, 'rejectUser'])->name('admin.verify.user.reject');
    Route::post('verifications/stores/{store}/approve', [VerificationController::class, 'approveStore'])->name('admin.verify.store.approve');
    Route::post('verifications/stores/{store}/reject', [VerificationController::class, 'rejectStore'])->name('admin.verify.store.reject');

    // Stores
    Route::resource('stores', StoreController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);

    // Listings
    Route::resource('listings', ListingController::class)->only(['index', 'show', 'destroy']);

    // Customer Requests
    Route::resource('requests', CustomerRequestController::class)->only(['index', 'show']);

    // Offers
    Route::resource('offers', OfferController::class)->only(['index']);

    // Orders
    Route::resource('orders', OrderController::class)->only(['index', 'show']);

    // Disputes
    Route::get('disputes', [DisputeController::class, 'index'])->name('admin.disputes.index');
    Route::get('disputes/{dispute}', [DisputeController::class, 'show'])->name('admin.disputes.show');
    Route::patch('disputes/{dispute}/resolve', [DisputeController::class, 'resolve'])->name('admin.disputes.resolve');

    // Reviews
    Route::resource('reviews', ReviewController::class)->only(['index', 'destroy']);

    // Settings
    Route::get('settings', [SettingController::class, 'index'])->name('admin.settings');
    Route::put('settings', [SettingController::class, 'update'])->name('admin.settings.update');
});
```

### API Routes (`routes/api.php`)

Sesuai dokumen API sebelumnya.

---

## 8. ADMIN PANEL – MENU & NAVIGASI

Layout admin menggunakan sidebar Bootstrap 5.3.x.  
**Sidebar Menu:**

- Dashboard (icon: home)
- Verifikasi (dropdown)
  - Verifikasi Pengguna
  - Verifikasi Toko
- Manajemen Data (divider)
  - Pengguna (icon: users)
  - Kategori (icon: tags)
  - Toko (icon: store)
  - Listing (icon: boxes)
  - Permintaan (icon: question-circle)
  - Penawaran (icon: hand-paper)
  - Pesanan (icon: shopping-cart)
  - Dispute (icon: exclamation-triangle)
  - Ulasan (icon: star)
- Pengaturan (icon: gear)

**Top Navbar:** Brand “Seekitar Admin”, profil admin, tombol logout.

**Breadcrumb:** Setiap halaman menampilkan breadcrumb dinamis (misal: Dashboard > Manajemen Data > Kategori).

---

## 9. HALAMAN ADMIN – DETAIL TAMPILAN & FORM

### 9.1 Dashboard Admin

- **Cards:** Total Pengguna, Total Toko, Total Pesanan Bulan Ini, Dispute Aktif.
- **Chart:** (opsional, bisa gunakan Chart.js) Grafik permintaan baru per hari dalam 7 hari terakhir.
- **Tabel ringkas:** 5 permintaan terbaru, 5 penawaran terbaru.

### 9.2 Manajemen Kategori

**Index (`/admin/categories`):**

- Tabel Yajra Datatables: kolom: Nama, Slug, Induk, Ikon, Aksi.
- Tombol “Tambah Kategori” (modal atau halaman create).
- Aksi: Edit (modal), Hapus (konfirmasi delete, hanya jika tidak ada anak/request terkait).

**Create/Edit Modal:**

- Nama (text)
- Slug (text, auto-generated dari nama)
- Induk (select dari kategori existing, nullable)
- Ikon (text, nama icon FontAwesome)
- Urutan (number, default 0)

### 9.3 Verifikasi Pengguna & Toko

**Index Verifikasi Pengguna (`/admin/verifications/users`):**

- Tabel pengguna yang `verification_level` = 1 (menunggu verifikasi KTP).
- Kolom: Nama, No HP, Tanggal Daftar, Aksi.
- Aksi: Tombol “Approve” (hijau) & “Reject” (merah) → konfirmasi, lalu kirim notifikasi ke pengguna.

**Index Verifikasi Toko (`/admin/verifications/stores`):**

- Tabel toko dengan `verification_status` = ‘pending’.
- Kolom: Nama Toko, Pemilik, Tanggal Daftar, Aksi.
- Aksi: Approve/Reject.

### 9.4 Manajemen Pengguna

**Index (`/admin/users`):**

- Datatables: Nama, Telepon, Level Verifikasi, Role, Aksi.
- Filter: role (select).
- Aksi: Edit (role, verification_level), Delete (soft delete, hanya jika tidak punya order aktif).

**Edit Pengguna (modal atau halaman terpisah):**

- Form: Nama, Phone (readonly), Verification Level (dropdown), Role (dropdown, jika admin yang login adalah super-admin).

### 9.5 Manajemen Toko

**Index (`/admin/stores`):**

- Datatables: Nama, Pemilik, Tipe, Rating, Status Verifikasi, Aksi.
- Aksi: Lihat Detail, Edit, Nonaktifkan (soft delete).

**Show Toko (`/admin/stores/{store}`):**

- Detail lengkap toko, daftar listing (tab atau table kecil), rating, ulasan.

**Edit Toko:** Nama, service_radius, operating_hours (JSON editor sederhana atau input terstruktur), status verifikasi.

### 9.6 Manajemen Listing

**Index (`/admin/listings`):**

- Datatables: Judul, Toko, Tipe, Harga, Status, Aksi.
- Aksi: Lihat, Hapus (soft delete).

### 9.7 Manajemen Permintaan

**Index (`/admin/requests`):**

- Datatables: Judul, Pembeli, Kategori, Status, Tanggal Expired.
- Filter: status (open, closed, expired).
- Aksi: Lihat detail.

**Show Permintaan (`/admin/requests/{request}`):**

- Detail permintaan + tabel offers yang masuk.

### 9.8 Manajemen Penawaran

**Index (`/admin/offers`):**

- Datatables: Permintaan, Toko, Harga, Status.
- Filter: status.
- Tidak ada aksi edit/hapus (read-only).

### 9.9 Manajemen Pesanan

**Index (`/admin/orders`):**

- Datatables: ID Pesanan, Pembeli, Toko, Total, Status, Tanggal.
- Filter: status.
- Aksi: Lihat detail.

**Show Pesanan (`/admin/orders/{order}`):**

- Timeline status, detail barang/jasa, informasi pembayaran, tombol “Lihat Dispute” jika ada.

### 9.10 Manajemen Dispute

**Index (`/admin/disputes`):**

- Datatables: Pesanan, Pelapor, Alasan, Status, Tanggal.
- Filter: status.
- Aksi: Lihat Detail, Selesaikan (resolve).

**Show Dispute (`/admin/disputes/{dispute}`):**

- Detail dispute + form resolution_note.
- Tombol “Selesaikan” -> isi catatan, update status resolved, kirim notifikasi ke pelapor.

### 9.11 Manajemen Ulasan

**Index (`/admin/reviews`):**

- Datatables: Pesanan, Penilai, Dinilai, Rating, Aksi.
- Aksi: Hapus (soft, hanya jika mengandung kata-kata kasar, dsb).

### 9.12 Pengaturan Sistem

**Halaman (`/admin/settings`):**

- Form: Maks Radius Default (km), Durasi Expired Permintaan (jam), Komisi (%), dll. (dapat disimpan di tabel `settings` atau file config).

---

## 10. CONTROLLERS & ACTIONS (ADMIN)

Contoh struktur `CategoryController`:

```php
class CategoryController extends Controller
{
    public function index()
    {
        $this->authorize('manage-categories');
        return view('admin.categories.index');
    }

    public function data()
    {
        $query = Category::with('parent');
        return DataTables::of($query)
            ->addColumn('action', function($row) {
                return view('admin.categories.actions', compact('row'));
            })
            ->make(true);
    }

    public function store(CategoryRequest $request) { ... }
    public function update(CategoryRequest $request, Category $category) { ... }
    public function destroy(Category $category) { ... }
}
```

Route datatables: `Route::get('categories/data', [CategoryController::class, 'data'])->name('admin.categories.data');`

---

## 11. FORM REQUESTS & VALIDASI (ADMIN)

`CategoryRequest`:

```php
public function rules(): array {
    $categoryId = $this->route('category')?->id;
    return [
        'name' => 'required|string|max:50',
        'slug' => 'required|alpha_dash|unique:categories,slug,' . $categoryId,
        'parent_id' => 'nullable|exists:categories,id|not_in:'.$categoryId,
        'icon' => 'nullable|string|max:50',
        'sort_order' => 'integer|min:0',
    ];
}
```

---

## 12. MODELS & RELATIONSHIPS (Tambah Spatie)

User model sudah mencakup `HasRoles`.  
Tambahkan accessor: `getRoleNamesAttribute()` atau langsung gunakan `$user->roles`.

---

## 13. OBSERVERS & EVENTS

Pemetaan event → listener didaftarkan di `EventServiceProvider`. Semua listener
yang memicu notifikasi berjalan lewat antrian.

| Event                    | Listener                          | Efek                                                        |
| :----------------------- | :-------------------------------- | :----------------------------------------------------------- |
| `CustomerRequestCreated` | `DispatchRequestBroadcast`        | Dispatch `BroadcastRequestJob` ke antrian                   |
| `OfferAccepted`          | `SendOfferAcceptedNotification`   | Notifikasi penyedia pemenang + tolak offer lain             |
| `OrderStatusChanged`     | `SendOrderStatusNotification`     | Notifikasi pihak terkait sesuai status baru                 |
| `ReviewSubmitted`        | `UpdateStoreRatingOnReview`       | Dispatch `RecalculateStoreRatingJob`                        |

**Observers** dipakai untuk side-effect yang selalu terjadi apa pun jalur
masuknya (API, admin panel, atau seeder):

| Observer         | Hook              | Efek                                                      |
| :--------------- | :---------------- | :--------------------------------------------------------- |
| `ReviewObserver` | `created`         | Perbarui `stores.rating_avg` & `stores.total_reviews`     |
| `OrderObserver`  | `updating`        | Isi `completed_at` saat status berubah jadi `selesai`     |

> Observer cocok untuk konsistensi data, bukan untuk pekerjaan berat.
> Pengiriman notifikasi tetap lewat event → job antrian.

---

## 14. JOBS & QUEUE

Semua job berjalan di Redis. Jalankan worker dengan:

```bash
php artisan queue:work redis --queue=high,default --tries=3
```

| Job                          | Antrian   | Fungsi                                                  |
| :--------------------------- | :-------- | :-------------------------------------------------------- |
| `BroadcastRequestJob`        | `high`    | Cari penyedia dalam radius, kirim notifikasi ke mereka  |
| `SendPushNotificationJob`    | `high`    | Satu pesan FCM ke satu perangkat                        |
| `SendWhatsAppOtpJob`         | `high`    | Kirim OTP via Twilio / Kirim WA                         |
| `RecalculateStoreRatingJob`  | `default` | Hitung ulang `rating_avg` dari seluruh ulasan toko      |

**Scheduler** (`routes/console.php`) — menutup permintaan yang kadaluarsa:

```php
Schedule::command('requests:close-expired')->everyFifteenMinutes();
```

Perintah ini memakai indeks `cr_status_expires_idx` seperti dijelaskan di
`DATABASE.md` §11.

---

## 15. NOTIFIKASI (PUSH & WHATSAPP) – Tetap

---

## 16. GEOSPASIAL & QUERY RADIUS – Tetap

---

## 17. API RESPONSE & PAGINASI (WEB & API)

- **Web:** Datatables menangani server-side processing dan paginasi otomatis.
- **API:** Paginasi standar.

---

## 18. ERROR HANDLING & LOGGING (Tetap)

Khusus web, kita bisa menggunakan `abort(403)` jika tidak punya permission, dan tampilkan halaman error custom.

---

## 19. MIGRATION & SEEDER (LENGKAP)

### 19.1 Migration: Tidak berubah dari dokumen database.

### 19.2 Seeder

**CategorySeeder:** masukkan 24 kategori dengan hirarki.

**RolesAndPermissionsSeeder:**

```php
public function run() {
    $permissions = [
        'manage-users', 'verify-users', 'manage-stores', 'verify-stores',
        'manage-categories', 'manage-listings', 'manage-requests', 'manage-offers',
        'manage-orders', 'manage-disputes', 'manage-reviews', 'manage-settings'
    ];
    foreach ($permissions as $perm) {
        Permission::create(['name' => $perm, 'guard_name' => 'web']);
    }

    $superAdmin = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
    $superAdmin->givePermissionTo(Permission::all());

    $admin = Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $admin->givePermissionTo(['manage-categories', 'verify-users', 'verify-stores', ...]);

    // Buat user super-admin
    $user = User::firstOrCreate(['phone' => '6280000000000'], ['name' => 'Super Admin', 'verification_level' => 3]);
    $user->assignRole('super-admin');
}
```

**Dummy User/Toko/Listing seeder (untuk development):** opsional.

---

## 20. TESTING (Tetap, tambahkan test untuk admin panel)

---

## 21. DEPLOYMENT (Tetap)

---

## 22. LAMPIRAN: CONTOH KODE BLADE & CONTROLLER

### 22.1 Datatables Controller (Category)

```php
public function data() {
    $query = Category::with('parent')->select('categories.*');
    return DataTables::of($query)
        ->addColumn('parent_name', function($cat) { return $cat->parent?->name ?? '-'; })
        ->addColumn('action', function($cat) {
            $editBtn = auth()->user()->can('manage-categories') ?
                '<button class="btn btn-sm btn-warning edit-btn" data-id="'.$cat->id.'" data-name="'.$cat->name.'" ...>Edit</button>' : '';
            $deleteBtn = auth()->user()->can('manage-categories') ?
                '<button class="btn btn-sm btn-danger delete-btn" data-id="'.$cat->id.'">Hapus</button>' : '';
            return $editBtn.$deleteBtn;
        })
        ->rawColumns(['action'])
        ->make(true);
}
```

### 22.2 Blade Partial (categories/index.blade.php)

```blade
@extends('layouts.admin')
@section('content')
<div class="card">
  <div class="card-header">
    <h5>Daftar Kategori</h5>
    <button class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#categoryModal">Tambah Kategori</button>
  </div>
  <div class="card-body">
    {!! $dataTable->table() !!}
  </div>
</div>
<!-- Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
  <div class="modal-dialog">
    <form id="categoryForm">
      @csrf
      <input type="hidden" name="_method" value="POST" id="method">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Tambah/Edit Kategori</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Nama</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <!-- field lainnya -->
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
  // handling modal, AJAX store/update
</script>
@endpush
```

### 22.3 Layout Admin (`layouts/admin.blade.php`)

```html
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <title>Seekitar Admin</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css"
    />
  </head>
  <body>
    <div class="d-flex">
      <!-- Sidebar -->
      <nav class="bg-dark text-white p-3" style="width:250px;">
        <h4>Seekitar</h4>
        <ul class="nav flex-column">
          @can('manage-categories')
          <li class="nav-item">
            <a
              href="{{ route('admin.categories.index') }}"
              class="nav-link text-white"
              >Kategori</a
            >
          </li>
          @endcan
          <!-- menu lain sesuai permission -->
        </ul>
      </nav>
      <!-- Main content -->
      <div class="flex-grow-1 p-4">@yield('content')</div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
  </body>
</html>
```
