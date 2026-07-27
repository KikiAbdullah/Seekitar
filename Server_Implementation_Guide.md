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
   - 5.1 Registrasi Middleware (`bootstrap/app.php`)
   - 5.3 Rate Limiter Kustom
   - 5.4 `EnsureProfileComplete`
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
    - 15.1 Push Notification (FCM)
    - 15.2 WhatsApp OTP
16. [Geospasial & Query Radius](#16-geospasial--query-radius)
17. [API Response & Paginasi (Web & API)](#17-api-response--paginasi-web--api)
18. [Error Handling & Logging](#18-error-handling--logging)
19. [Migration & Seeder (Lengkap)](#19-migration--seeder-lengkap)
20. [Testing](#20-testing)
21. [Deployment](#21-deployment)
    - 21.1 Environment Variables
    - 21.2 Perintah Deploy
    - 21.3 Queue Worker (Supervisor)
    - 21.4 Scheduler (Cron)
    - 21.5 Tooling Pengembangan
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
| `OrderType`          | `product`, `service`, `rental`                                                | `orders.order_type`           |
| `RequestStatus`      | `open`, `closed`, `expired`                                                  | `customer_requests.status`    |
| `OfferStatus`        | `pending`, `accepted`, `rejected`                                            | `offers.status`               |
| `ListingStatus`      | `active`, `sold`, `hidden`                                                   | `listings.status`             |
| `ListingType`        | `product`, `service`, `rental`                                               | `listings.listing_type`       |
| `StoreType`          | `goods`, `services`, `rental`                                                | `stores.store_type` (SET)     |
| `VerificationStatus` | `pending`, `verified`, `rejected`                                            | `stores.verification_status`  |
| `PaymentMethod`      | `cod`, `transfer`                                                            | `orders.payment_method`       |
| `DisputeStatus`      | `open`, `resolved`                                                           | `disputes.status`             |
| `VerificationLevel`  | `1`, `2`, `3` (int)                                                          | `users.verification_level`    |

> ⚠️ **Perhatikan bedanya:** `StoreType` memakai bentuk **jamak**
> (`goods`, `services`) karena kolomnya bertipe SET dan menampung kombinasi —
> "toko ini menjual barang dan jasa". Sementara `ListingType` dan `OrderType`
> memakai bentuk **tunggal** (`product`, `service`) karena masing-masing hanya
> satu nilai.
>
> `ListingType` dan `OrderType` **wajib identik** — nilainya disalin langsung
> saat pesanan dibuat dari listing. Jangan "merapikan" salah satunya tanpa
> mengubah yang lain beserta migrasinya. Lihat `DATABASE.md` §4.2 dan §4.7.

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

## 5. MIDDLEWARE & PIPELINE

> ⚠️ **`app/Http/Kernel.php` sudah tidak ada sejak Laravel 11.** Semua
> registrasi middleware kini terpusat di `bootstrap/app.php`. Panduan lama yang
> menyuruh mendaftarkan alias di Kernel tidak berlaku untuk Laravel 13.

### 5.1 Registrasi Middleware (`bootstrap/app.php`)

```php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',
        api:      __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health:   '/up',
        then: function () {
            // Route admin dipisah agar prefix & middleware-nya tidak
            // tercampur dengan web publik SEO.
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias dari Spatie Permission — inilah yang membuat 'role:admin'
        // dan 'permission:manage-users' bisa dipakai di route.
        $middleware->alias([
            'role'              => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'        => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission'=> \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'store.owner'       => \App\Http\Middleware\EnsureStoreOwner::class,
            'profile.complete'  => \App\Http\Middleware\EnsureProfileComplete::class,
        ]);

        // Cookie-based auth hanya untuk domain di SANCTUM_STATEFUL_DOMAINS.
        $middleware->statefulApi();

        // Throttle bawaan untuk seluruh grup api.
        $middleware->api(append: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
        );
    })->create();
```

### 5.2 Daftar Middleware

| Alias | Dipakai di | Fungsi |
| :-- | :-- | :-- |
| `auth` | web admin | Sesi login Laravel |
| `auth:sanctum` | `/api/*` | Bearer token |
| `role:admin` | web admin | Spatie — batasi ke admin |
| `permission:manage-users` | per-route admin | Spatie — izin granular |
| `store.owner` | API penjual | Memastikan pemanggil pemilik toko terkait |
| `profile.complete` | API | Menegakkan pengisian `users.location` |
| `throttle:otp` | `/auth/request-otp` | 3 request/menit **per nomor** |

### 5.3 Rate Limiter Kustom

Batas OTP dihitung **per nomor telepon**, bukan per IP — kalau per IP, satu
orang bisa memanen OTP dengan berganti jaringan, dan sebaliknya pengguna satu
WiFi kantor saling memblokir.

```php
// AppServiceProvider::boot()
RateLimiter::for('otp', fn (Request $request) => [
    Limit::perMinute(3)->by('otp:'.$request->input('phone')),
    Limit::perDay(10)->by('otp-daily:'.$request->input('phone')),
]);

RateLimiter::for('offers', fn (Request $request) =>
    Limit::perMinute(30)->by('offers:'.$request->user()?->id)
);

RateLimiter::for('api', fn (Request $request) =>
    Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
);
```

### 5.4 `EnsureProfileComplete`

`users.location` sengaja NULL-able (lihat `DATABASE.md` §4.1), sehingga
kewajiban mengisi lokasi ditegakkan middleware — bukan constraint database.

```php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    if ($user && ($user->location === null || $user->name === null)) {
        return response()->json([
            'success' => false,
            'message' => 'Lengkapi profil (nama & lokasi) terlebih dahulu.',
            'errors'  => ['profile' => ['incomplete']],
        ], 403);
    }

    return $next($request);
}
```

Middleware ini **tidak** dipasang pada `PATCH /auth/profile` sendiri — kalau
dipasang, pengguna baru terkunci dan tidak akan pernah bisa melengkapi profil.

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

#### ⚠️ Guard: sumber kebingungan utama

Spatie menyimpan `guard_name` **di setiap baris** role dan permission. Sebuah
permission bermilik `guard_name = 'web'` **tidak terlihat** oleh pengguna yang
diautentikasi lewat guard `sanctum`, meskipun namanya sama persis. Ini penyebab
`can()` mendadak mengembalikan `false` di API padahal berfungsi di web admin.

Seekitar sengaja memakai pembagian berikut:

| Kanal | Guard | Otorisasi memakai |
| :-- | :-- | :-- |
| Web admin | `web` | **Spatie** role & permission (`role:admin`, `can('manage-users')`) |
| API mobile | `sanctum` | **Token ability** (`ability:store-owner`) + Policy |

Artinya seluruh role/permission Spatie cukup dibuat untuk guard `web` saja —
persis seperti seeder di §19.2. Pengguna biasa di aplikasi mobile tidak
membutuhkan baris permission sama sekali; haknya ditentukan kepemilikan data
(lewat Policy) dan ability token.

```php
// config/permission.php
'models' => [
    'permission' => Spatie\Permission\Models\Permission::class,
    'role'       => Spatie\Permission\Models\Role::class,
],

// Guard default saat membuat role/permission tanpa menyebut guard.
'defaults' => ['guard' => 'web'],
```

> Jika suatu saat admin perlu mengakses API lewat token, **jangan** mengganti
> guard default. Duplikasikan permission untuk guard `sanctum`:
> ```php
> Permission::findOrCreate('manage-users', 'sanctum');
> ```
> Menukar default ke `sanctum` akan mematikan seluruh otorisasi web admin.

### 6.3 Gates & Policies

Policies untuk API (StorePolicy, ListingPolicy, dll.) tetap sama.  
Untuk admin web, kita bisa menggunakan `Gate::allow` berdasarkan permission. Di controller, gunakan `$this->authorize('manage-users')` atau cek dengan `if (auth()->user()->can('manage-users'))`.

#### `Gate::before` untuk super-admin

Tanpa ini, setiap Policy harus mengulang pengecualian untuk super-admin.
Satu pintu lebih aman daripada belasan pemeriksaan yang bisa terlupa:

```php
// AppServiceProvider::boot()
Gate::before(function (User $user, string $ability) {
    // Mengembalikan true = izinkan semua; null = lanjutkan ke Policy.
    // JANGAN kembalikan false — itu memblokir Policy lain ikut memutuskan.
    return $user->hasRole('super-admin') ? true : null;
});
```

> ⚠️ Perhatikan `null`, bukan `false`. Mengembalikan `false` membuat seluruh
> Policy dilewati dan semua orang selain super-admin ditolak.

#### Policy untuk data milik pengguna

```php
class ListingPolicy
{
    public function update(User $user, Listing $listing): bool
    {
        return $user->id === $listing->store->user_id;
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $this->update($user, $listing)
            || $user->can('manage-listings');   // admin boleh menghapus
    }
}
```

| Policy | Aturan inti |
| :-- | :-- |
| `StorePolicy` | Hanya pemilik yang boleh `update`; admin boleh `deactivate` |
| `ListingPolicy` | Pemilik toko; admin boleh `delete` (konten bermasalah) |
| `OfferPolicy` | Pemilik toko boleh membuat; hanya pemilik request boleh `accept` |
| `OrderPolicy` | Pembeli **atau** pemilik toko terkait; transisi status dicek `OrderStateMachine` |
| `ReviewPolicy` | Hanya pihak pada pesanan `selesai` dan dalam jendela 7 hari |

---

## 7. ROUTING LENGKAP

### Admin Routes (`routes/admin.php`)

Route admin dipisah dari `web.php` (didaftarkan lewat `then:` di
`bootstrap/app.php`, lihat §5.1) supaya web publik SEO tidak ikut terbebani
middleware admin.

Karena prefix `admin` dan name `admin.` sudah disetel saat pendaftaran grup,
di dalam berkas ini **tidak perlu** mengulanginya.

```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // --- Endpoint AJAX Datatables ---------------------------------------
    // WAJIB didaftarkan SEBELUM Route::resource, kalau tidak 'data' akan
    // tertangkap sebagai {category} pada route show/edit.
    Route::get('categories/data', [CategoryController::class, 'data'])->name('categories.data');
    Route::get('users/data',      [UserController::class, 'data'])->name('users.data');
    Route::get('stores/data',     [StoreController::class, 'data'])->name('stores.data');
    Route::get('listings/data',   [ListingController::class, 'data'])->name('listings.data');
    Route::get('requests/data',   [CustomerRequestController::class, 'data'])->name('requests.data');
    Route::get('offers/data',     [OfferController::class, 'data'])->name('offers.data');
    Route::get('orders/data',     [OrderController::class, 'data'])->name('orders.data');
    Route::get('disputes/data',   [DisputeController::class, 'data'])->name('disputes.data');
    Route::get('reviews/data',    [ReviewController::class, 'data'])->name('reviews.data');

    // Data untuk grafik dashboard (§9.1)
    Route::get('dashboard/chart', [DashboardController::class, 'chartData'])->name('dashboard.chart');

    // Categories
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Users
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Verifikasi massal
    Route::post('verifications/users/{user}/approve', [VerificationController::class, 'approveUser'])->name('verify.user.approve');
    Route::post('verifications/users/{user}/reject', [VerificationController::class, 'rejectUser'])->name('verify.user.reject');
    Route::post('verifications/stores/{store}/approve', [VerificationController::class, 'approveStore'])->name('verify.store.approve');
    Route::post('verifications/stores/{store}/reject', [VerificationController::class, 'rejectStore'])->name('verify.store.reject');

    // Stores
    Route::resource('stores', StoreController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::patch('stores/{store}/deactivate', [StoreController::class, 'deactivate'])->name('stores.deactivate');

    // Blokir pengguna (mencabut semua token, lihat API §10.4)
    Route::patch('users/{user}/block',   [UserController::class, 'block'])->name('users.block');
    Route::patch('users/{user}/unblock', [UserController::class, 'unblock'])->name('users.unblock');

    // Listings
    Route::resource('listings', ListingController::class)->only(['index', 'show', 'destroy']);
    Route::patch('listings/{listing}/toggle-status', [ListingController::class, 'toggleStatus'])
        ->name('listings.toggle-status');

    // Customer Requests
    Route::resource('requests', CustomerRequestController::class)->only(['index', 'show']);
    Route::patch('requests/{request}/extend', [CustomerRequestController::class, 'extend'])
        ->name('requests.extend');

    // Offers
    Route::resource('offers', OfferController::class)->only(['index']);

    // Orders
    Route::resource('orders', OrderController::class)->only(['index', 'show']);

    // Disputes
    Route::get('disputes', [DisputeController::class, 'index'])->name('disputes.index');
    Route::get('disputes/{dispute}', [DisputeController::class, 'show'])->name('disputes.show');
    Route::patch('disputes/{dispute}/resolve', [DisputeController::class, 'resolve'])->name('disputes.resolve');

    // Reviews
    Route::resource('reviews', ReviewController::class)->only(['index', 'destroy']);

    // Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
});
```

### API Routes (`routes/api.php`)

Kontrak lengkapnya ada di [`API_DOCUMENTATION.md`](API_DOCUMENTATION.md).
Yang penting diperhatikan di sisi routing adalah **penempatan middleware**:

```php
Route::prefix('v1')->group(function () {

    // --- Publik --------------------------------------------------------
    Route::post('auth/request-otp', [AuthController::class, 'requestOtp'])
        ->middleware('throttle:otp');                     // per nomor, bukan IP
    Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:6,1');

    Route::get('stores/nearby',   [StoreController::class, 'nearby']);
    Route::get('stores/{store}',  [StoreController::class, 'show']);
    Route::get('listings',        [ListingController::class, 'index']);
    Route::get('listings/{listing}', [ListingController::class, 'show']);
    Route::get('categories',      [CategoryController::class, 'index']);

    // --- Perlu login ---------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout',     [AuthController::class, 'logout']);
        Route::get('auth/me',          [AuthController::class, 'me']);
        Route::patch('auth/profile',   [AuthController::class, 'updateProfile']);
        Route::post('auth/verification/ktp', [VerificationController::class, 'submitKtp']);
        Route::post('auth/fcm-token',  [DeviceController::class, 'store']);
        Route::delete('auth/fcm-token',[DeviceController::class, 'destroy']);

        // Butuh profil lengkap (nama + lokasi) sebelum bertransaksi.
        Route::middleware('profile.complete')->group(function () {
            Route::post('uploads/images', [UploadController::class, 'store']);

            Route::post('requests',              [CustomerRequestController::class, 'store']);
            Route::get('requests',               [CustomerRequestController::class, 'index']);
            Route::get('requests/mine',          [CustomerRequestController::class, 'mine']);
            Route::post('requests/{request}/extend', [CustomerRequestController::class, 'extend']);

            Route::post('orders',                [OrderController::class, 'store']);
            Route::patch('orders/{order}/status',[OrderController::class, 'updateStatus']);
            Route::post('orders/{order}/review', [ReviewController::class, 'store']);
            Route::post('orders/{order}/disputes', [DisputeController::class, 'store']);

            Route::post('listings/{listing}/favorite',   [FavoriteController::class, 'store']);
            Route::delete('listings/{listing}/favorite', [FavoriteController::class, 'destroy']);
            Route::get('favorites',                      [FavoriteController::class, 'index']);

            // --- Khusus pemilik toko (ability token) --------------------
            Route::middleware('ability:store-owner')->group(function () {
                Route::post('stores',   [StoreController::class, 'store']);
                Route::apiResource('listings', ListingController::class)
                    ->only(['store', 'update', 'destroy']);
                Route::post('requests/{request}/offers', [OfferController::class, 'store'])
                    ->middleware('throttle:offers');
            });
        });
    });
});
```

> ⚠️ **Urutan `requests/mine` sebelum `requests/{request}`.** Jika terbalik,
> kata `mine` akan ditangkap sebagai `{request}` dan menghasilkan 404. Masalah
> yang sama berlaku untuk seluruh route `*/data` di admin.

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
- **Chart:** Grafik permintaan baru per hari dalam 7 hari terakhir (Chart.js).
- **Tabel ringkas:** 5 permintaan terbaru, 5 penawaran terbaru.

**Controller** — data grafik disajikan lewat endpoint terpisah supaya halaman
tidak menunggu agregasi selesai:

```php
public function chartData(): JsonResponse
{
    $this->authorize('viewAny', CustomerRequest::class);

    // Rentang 7 hari termasuk hari ini.
    $from = now()->subDays(6)->startOfDay();

    $rows = CustomerRequest::query()
        ->where('created_at', '>=', $from)
        ->selectRaw('DATE(created_at) AS d, COUNT(*) AS total')
        ->groupBy('d')
        ->pluck('total', 'd');

    // Isi hari kosong dengan 0 — tanpa ini grafik "melompat".
    $labels = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'));

    return response()->json([
        'labels' => $labels->map(fn ($d) => Carbon::parse($d)->translatedFormat('D, d M')),
        'data'   => $labels->map(fn ($d) => (int) ($rows[$d] ?? 0)),
    ]);
}
```

**Blade + Chart.js:**

```blade
<canvas id="requestChart" height="80"></canvas>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
fetch("{{ route('admin.dashboard.chart') }}")
  .then(r => r.json())
  .then(({ labels, data }) => {
    new Chart(document.getElementById('requestChart'), {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Permintaan Baru',
          data,
          borderColor: '#0d6efd',
          backgroundColor: 'rgba(13,110,253,.1)',
          fill: true,
          tension: .3,
        }],
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
      },
    });
  });
</script>
@endpush
```

> Query di atas memakai `GROUP BY DATE(created_at)` yang **tidak** bisa
> memanfaatkan indeks. Untuk 7 hari data masih murah, tetapi jika dashboard
> nanti menampilkan rentang setahun, simpan agregat harian di tabel terpisah
> lewat scheduler.

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

#### ⚠️ Mencegah loop hierarki

`not_in:{id}` saja **tidak cukup**. Aturan itu hanya mencegah kategori menjadi
induk dirinya sendiri (A → A), tapi tidak mencegah siklus tak langsung:

```
A (induk B)  →  jadikan induknya D
B (induk C)
C (induk D)
D            →  A jadi keturunan D, sekaligus D jadi keturunan A
```

Hasilnya cabang A–B–C–D terlepas dari pohon dan **menghilang** dari semua
query rekursif — tanpa error apa pun. Karena itu perlu validasi keturunan:

```php
// app/Rules/NotADescendant.php
class NotADescendant implements ValidationRule
{
    public function __construct(private ?int $categoryId) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->categoryId || ! $value) {
            return;                       // kategori baru tidak mungkin punya anak
        }

        if ((int) $value === $this->categoryId) {
            $fail('Kategori tidak boleh menjadi induk dirinya sendiri.');
            return;
        }

        // Telusuri ke atas dari calon induk; jika bertemu diri sendiri, itu siklus.
        $ancestorId = $value;
        $guard = 0;

        while ($ancestorId && $guard++ < 10) {
            if ((int) $ancestorId === $this->categoryId) {
                $fail('Kategori tidak boleh dipindahkan ke dalam turunannya sendiri.');
                return;
            }
            $ancestorId = Category::whereKey($ancestorId)->value('parent_id');
        }
    }
}
```

`$guard` membatasi penelusuran agar tidak berputar selamanya seandainya sudah
terlanjur ada siklus di data lama.

> Hierarki kategori dibatasi **2 level** (`DATABASE.md` §3). Tambahkan juga
> validasi bahwa calon induk adalah kategori teratas:
> ```php
> 'parent_id' => [... , Rule::exists('categories', 'id')->whereNull('parent_id')],
> ```

### 9.3 Verifikasi Pengguna & Toko

**Index Verifikasi Pengguna (`/admin/verifications/users`):**

- Tabel pengguna yang `verification_level` = 1 (menunggu verifikasi KTP).
- Kolom: Nama, No HP, Tanggal Daftar, Aksi.
- Aksi: Tombol “Approve” (hijau) & “Reject” (merah) → konfirmasi, lalu kirim notifikasi ke pengguna.

**Index Verifikasi Toko (`/admin/verifications/stores`):**

- Tabel toko dengan `verification_status` = ‘pending’.
- Kolom: Nama Toko, Pemilik, Tanggal Daftar, Aksi.
- Aksi: Approve/Reject.

#### Logika Approve & Reject

> ⚠️ **`verification_level` tidak pernah bernilai 0.** Nilainya hanya `1`, `2`,
> atau `3` (`DATABASE.md` §4.1). Menolak pengajuan KTP berarti pengguna
> **tetap di Level 1**, bukan diturunkan ke 0 — Level 1 sudah berarti "hanya
> nomor HP terverifikasi", yang persis menggambarkan kondisinya.

```php
public function approveUser(User $user): RedirectResponse
{
    $this->authorize('verify-users');

    DB::transaction(function () use ($user) {
        $user->update([
            'verification_level'  => VerificationLevel::Verified,   // 1 -> 2
            'ktp_rejected_reason' => null,
        ]);

        // Berkas KTP tidak lagi diperlukan setelah disetujui (UU PDP).
        $user->notify(new VerificationApproved());
    });

    return back()->with('success', "Verifikasi {$user->name} disetujui.");
}

public function rejectUser(RejectVerificationRequest $request, User $user): RedirectResponse
{
    $this->authorize('verify-users');

    $user->update([
        // TETAP Level 1 — tidak ada level 0.
        'verification_level'  => VerificationLevel::Basic,
        'ktp_rejected_reason' => $request->validated('reason'),
        'ktp_image'           => null,   // minta unggah ulang
        'selfie_image'        => null,
        'ktp_submitted_at'    => null,
    ]);

    $user->notify(new VerificationRejected($request->validated('reason')));

    return back()->with('success', 'Pengajuan ditolak, pengguna diberi tahu.');
}
```

**Penolakan toko** — beri alasan, dan pastikan toko tidak muncul di pencarian:

```php
public function rejectStore(RejectVerificationRequest $request, Store $store): RedirectResponse
{
    $this->authorize('verify-stores');

    DB::transaction(function () use ($request, $store) {
        $store->update([
            'verification_status' => VerificationStatus::Rejected,
            'rejected_reason'     => $request->validated('reason'),
            'is_active'           => false,   // hilang dari pencarian
        ]);

        $store->owner->notify(new StoreVerificationRejected($store, $request->validated('reason')));
    });

    return back()->with('success', 'Toko ditolak.');
}

public function approveStore(Store $store): RedirectResponse
{
    $this->authorize('verify-stores');

    $store->update([
        'verification_status' => VerificationStatus::Verified,
        'verified_at'         => now(),
        'rejected_reason'     => null,
        'is_active'           => true,
    ]);

    $store->owner->notify(new StoreVerificationApproved($store));

    return back()->with('success', 'Toko disetujui.');
}
```

`reason` wajib diisi saat menolak (`RejectVerificationRequest`:
`'reason' => 'required|string|min:10|max:500'`) — penolakan tanpa alasan
membuat pengguna mengajukan ulang berkas yang sama.

### 9.4 Manajemen Pengguna

**Index (`/admin/users`):**

- Datatables: Nama, Telepon, Level Verifikasi, Role, Status, Aksi.
- Filter: **Level Verifikasi** (select), Role (select), Status blokir (select).
- Aksi: Edit (role, verification_level), Blokir/Buka blokir, Delete (soft delete, hanya jika tidak punya order aktif).

**Filter Datatables server-side** — Yajra tidak otomatis membaca filter kustom;
kirim lewat `data` di JS lalu tangkap di controller:

```php
public function data(Request $request)
{
    $query = User::query()->with('roles');

    $query->when($request->filled('verification_level'),
        fn ($q) => $q->where('verification_level', $request->integer('verification_level')));

    $query->when($request->filled('role'),
        fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $request->string('role'))));

    $query->when($request->filled('is_blocked'),
        fn ($q) => $q->where('is_blocked', $request->boolean('is_blocked')));

    return DataTables::of($query)
        ->addColumn('level_label', fn ($u) => VerificationLevel::from($u->verification_level)->label())
        ->addColumn('role', fn ($u) => $u->roles->pluck('name')->join(', ') ?: '-')
        ->addColumn('action', fn ($u) => view('admin.users.actions', ['user' => $u]))
        ->rawColumns(['action'])
        ->make(true);
}
```

```js
const table = $('#usersTable').DataTable({
  processing: true,
  serverSide: true,
  ajax: {
    url: "{{ route('admin.users.data') }}",
    data: (d) => {
      d.verification_level = $('#filterLevel').val();
      d.role = $('#filterRole').val();
      d.is_blocked = $('#filterBlocked').val();
    },
  },
  columns: [
    { data: 'name' },
    { data: 'phone' },
    { data: 'level_label' },
    { data: 'role' },
    { data: 'action', orderable: false, searchable: false },
  ],
});

// Muat ulang tabel setiap filter berubah.
$('#filterLevel, #filterRole, #filterBlocked').on('change', () => table.ajax.reload());
```

Pola `data:` + `ajax.reload()` yang sama dipakai untuk seluruh Datatables lain
yang punya filter (toko, listing, permintaan, pesanan, dispute).

**Edit Pengguna (modal atau halaman terpisah):**

- Form: Nama, Phone (readonly), Verification Level (dropdown), Role (dropdown, jika admin yang login adalah super-admin).

### 9.5 Manajemen Toko

**Index (`/admin/stores`):**

- Datatables: Nama, Pemilik, Tipe, Rating, Status Verifikasi, Aksi.
- Aksi: Lihat Detail, Edit, Nonaktifkan (soft delete).

**Show Toko (`/admin/stores/{store}`):**

- Detail lengkap toko, daftar listing (tab atau table kecil), rating, ulasan.

**Edit Toko:** Nama, service_radius, operating_hours, status verifikasi.

#### Editor `operating_hours`

**Jangan** memakai textarea JSON mentah — satu koma salah membuat jam
operasional toko rusak tanpa pesan yang berguna. Pakai form terstruktur:
tujuh baris, masing-masing dengan checkbox "Buka" dan dua input `time`.

```blade
@php $days = ['senin','selasa','rabu','kamis','jumat','sabtu','minggu']; @endphp

@foreach ($days as $day)
  @php $h = old("operating_hours.$day", $store->operating_hours[$day] ?? null); @endphp
  <div class="row align-items-center mb-2">
    <div class="col-3 text-capitalize">{{ $day }}</div>
    <div class="col-2 form-check form-switch">
      <input type="checkbox" class="form-check-input day-toggle"
             name="operating_hours[{{ $day }}][is_open]" value="1"
             data-day="{{ $day }}" @checked($h)>
    </div>
    <div class="col-3">
      <input type="time" class="form-control" name="operating_hours[{{ $day }}][open]"
             value="{{ $h['open'] ?? '08:00' }}" @disabled(! $h)>
    </div>
    <div class="col-3">
      <input type="time" class="form-control" name="operating_hours[{{ $day }}][close]"
             value="{{ $h['close'] ?? '17:00' }}" @disabled(! $h)>
    </div>
  </div>
@endforeach
```

Controller mengubahnya menjadi struktur yang disimpan database — hari yang
tidak dicentang bernilai `null`, bukan dihilangkan:

```php
$hours = collect($request->validated('operating_hours'))
    ->map(fn ($v) => ($v['is_open'] ?? false)
        ? ['open' => $v['open'], 'close' => $v['close']]
        : null)
    ->all();

$store->update(['operating_hours' => $hours]);
```

Validasinya menegakkan aturan di `API_DOCUMENTATION.md` §3.1 — tujuh kunci
wajib ada, dan jam tutup harus setelah jam buka:

```php
'operating_hours'          => ['required', 'array', 'size:7'],
'operating_hours.*.open'   => ['required_with:operating_hours.*.is_open', 'date_format:H:i'],
'operating_hours.*.close'  => ['required_with:operating_hours.*.is_open', 'date_format:H:i', 'after:operating_hours.*.open'],
```

### 9.6 Manajemen Listing

**Index (`/admin/listings`):**

- Datatables: Judul, Toko, Tipe, Harga, Status, Aksi.
- Filter: status (active/sold/hidden), tipe, toko.
- Aksi: Lihat, **Aktifkan/Sembunyikan**, Hapus (soft delete).

Menyembunyikan listing lebih proporsional daripada menghapusnya — konten yang
melanggar bisa ditinjau ulang, dan penjual tidak kehilangan datanya:

```php
public function toggleStatus(Listing $listing): RedirectResponse
{
    $this->authorize('manage-listings');

    // 'sold' diatur penjual, bukan admin — admin hanya menyembunyikan/menampilkan.
    if ($listing->status === ListingStatus::Sold) {
        return back()->withErrors('Listing berstatus terjual tidak dapat diubah admin.');
    }

    $listing->update([
        'status' => $listing->status === ListingStatus::Active
            ? ListingStatus::Hidden
            : ListingStatus::Active,
    ]);

    return back()->with('success', 'Status listing diperbarui.');
}
```

### 9.7 Manajemen Permintaan

**Index (`/admin/requests`):**

- Datatables: Judul, Pembeli, Kategori, Status, Tanggal Expired.
- Filter: status (open, closed, expired).
- Aksi: Lihat detail.

**Show Permintaan (`/admin/requests/{request}`):**

- Detail permintaan + tabel offers yang masuk.
- Aksi **Perpanjang** (opsional): mendorong `expires_at` maju 24 jam.

Berguna saat permintaan kedaluwarsa akibat gangguan sistem, bukan karena
pembeli membiarkannya. Admin **tidak** dibatasi kuota 2 kali seperti pembeli
(`DATABASE.md` §4.5), tetapi setiap perpanjangan tetap tercatat:

```php
public function extend(CustomerRequest $request): RedirectResponse
{
    $this->authorize('manage-requests');

    if ($request->status === RequestStatus::Closed) {
        return back()->withErrors('Permintaan yang sudah ditutup tidak dapat diperpanjang.');
    }

    $request->update([
        'status'          => RequestStatus::Open,   // hidupkan lagi jika expired
        'expires_at'      => now()->addHours(24),
        'extended_at'     => now(),
        'extension_count' => $request->extension_count + 1,
    ]);

    activity()->performedOn($request)->log('Diperpanjang admin');

    return back()->with('success', 'Permintaan diperpanjang 24 jam.');
}
```

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
- Tombol “Selesaikan” → isi catatan, pilih keputusan, kirim notifikasi ke **kedua** pihak.

#### Menyelesaikan dispute juga membuka kunci pesanan

Saat dispute dibuat, `orders.status` menjadi `dispute` dan **semua transisi
status pesanan terkunci** (`API_DOCUMENTATION.md` §9.1). Kalau admin hanya
menandai dispute `resolved` tanpa menyentuh pesanan, pesanan itu **terkunci
selamanya** — tidak bisa diselesaikan maupun dibatalkan.

Karena itu form resolusi wajib memuat keputusan akhir pesanan:

| Keputusan admin | `orders.status` menjadi | Kapan dipakai |
| :-- | :-- | :-- |
| Lanjutkan transaksi | `selesai` | Laporan tidak terbukti; barang/jasa sudah diterima |
| Batalkan transaksi | `dibatalkan` | Laporan terbukti; transaksi dibatalkan |

```php
public function resolve(ResolveDisputeRequest $request, Dispute $dispute): RedirectResponse
{
    $this->authorize('manage-disputes');

    DB::transaction(function () use ($request, $dispute) {
        $order    = $dispute->order()->lockForUpdate()->first();
        $decision = $request->validated('decision');   // 'selesai' | 'dibatalkan'

        $dispute->update([
            'status'          => DisputeStatus::Resolved,
            'resolution_note' => $request->validated('resolution_note'),
            'resolved_at'     => now(),
        ]);

        $order->update(array_filter([
            'status'        => OrderStatus::from($decision),
            'completed_at'  => $decision === 'selesai'    ? now() : null,
            'cancelled_at'  => $decision === 'dibatalkan' ? now() : null,
            'cancelled_by'  => $decision === 'dibatalkan' ? auth()->id() : null,
            'cancel_reason' => $decision === 'dibatalkan' ? 'Dibatalkan admin melalui dispute' : null,
        ], fn ($v) => $v !== null));

        // Kedua pihak diberi tahu, bukan hanya pelapor.
        Notification::send(
            [$order->buyer, $order->store->owner],
            new DisputeResolved($dispute, $decision)
        );
    });

    return back()->with('success', 'Dispute diselesaikan.');
}
```

> ⚠️ Menyelesaikan dispute ke `selesai` **membuka jendela ulasan 7 hari**
> terhitung dari `completed_at` yang baru diisi. Ini disengaja: pihak yang
> dirugikan tetap berhak memberi penilaian.

### 9.11 Manajemen Ulasan

**Index (`/admin/reviews`):**

- Datatables: Pesanan, Penilai, Dinilai, Rating, Aksi.
- Aksi: Hapus (soft, hanya jika mengandung kata-kata kasar, dsb).

### 9.12 Pengaturan Sistem

**Halaman (`/admin/settings`):** hanya `super-admin` (lihat `API_DOCUMENTATION.md` §10.5).

Nilai yang di dokumen lain tampak sebagai angka tetap sebenarnya dikendalikan
dari sini: radius maksimum pencarian, masa berlaku permintaan & penawaran,
batas perpanjangan, jendela ulasan, dan SLA admin.

#### Tabel `settings`

Belum ada di `DATABASE.md` §4 karena bukan tabel domain — ini penyimpanan
key-value untuk konfigurasi runtime.

```php
Schema::create('settings', function (Blueprint $table) {
    $table->string('key', 100)->primary();
    $table->text('value')->nullable();
    $table->string('type', 20)->default('string'); // string|integer|boolean|json
    $table->string('group', 50)->default('general');
    $table->string('label');                        // teks yang tampil di form admin
    $table->timestamps();
});
```

| `key` | Tipe | Default | Dipakai di |
| :-- | :-- | :-- | :-- |
| `max_search_radius_km` | integer | `25` | Pencarian listing (PRD §5.1) |
| `default_request_radius_km` | integer | `15` | `customer_requests.radius_km` |
| `request_expiry_hours` | integer | `24` | `expires_at` permintaan |
| `offer_expiry_hours` | integer | `48` | `expires_at` penawaran |
| `max_request_extensions` | integer | `2` | Batas perpanjangan pembeli |
| `review_window_days` | integer | `7` | Jendela ulasan (PRD §5.4.1) |
| `ktp_review_sla_hours` | integer | `24` | SLA verifikasi |
| `dispute_sla_hours` | integer | `24` | SLA dispute (PRD §5.5) |

#### Service dengan cache

Pengaturan dibaca hampir di setiap request, jadi wajib di-cache — dan cache
**harus** dibersihkan saat nilainya berubah:

```php
class SettingService
{
    private const CACHE_KEY = 'settings:all';

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () =>
            Setting::all()->mapWithKeys(fn ($s) => [
                $s->key => match ($s->type) {
                    'integer' => (int) $s->value,
                    'boolean' => filter_var($s->value, FILTER_VALIDATE_BOOL),
                    'json'    => json_decode($s->value, true),
                    default   => $s->value,
                },
            ])->all()
        );
    }

    public function set(array $values): void
    {
        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                Setting::where('key', $key)->update(['value' => $value]);
            }
        });

        Cache::forget(self::CACHE_KEY);   // WAJIB, kalau tidak nilai lama bertahan
    }
}
```

> ⚠️ Perubahan pengaturan berlaku untuk data **baru** saja. Menurunkan
> `request_expiry_hours` tidak memperpendek permintaan yang sudah berjalan,
> karena `expires_at` sudah terhitung saat permintaan dibuat.

---

## 10. CONTROLLERS & ACTIONS (ADMIN)

Struktur lengkap `CategoryController` — perhatikan bahwa **setiap** aksi
tulis memakai FormRequest, dan `index()` mengirim `$dataTable` ke view:

```php
class CategoryController extends Controller
{
    public function __construct(private CategoryService $categories)
    {
        // Satu baris ini menggantikan authorize() berulang di tiap method.
        $this->middleware('permission:manage-categories');
    }

    public function index(CategoriesDataTable $dataTable)
    {
        // Blade memanggil $dataTable->table() & ->scripts(), jadi objeknya
        // WAJIB dikirim — tanpa ini view melempar "Undefined variable".
        return $dataTable->render('admin.categories.index');
    }

    public function create()
    {
        return view('admin.categories.form', [
            'category' => new Category(),
            'parents'  => Category::whereNull('parent_id')->orderBy('name')->get(),
        ]);
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', [
            'category' => $category,
            // Kategori tidak boleh menjadi induk dirinya sendiri.
            'parents'  => Category::whereNull('parent_id')
                ->whereKeyNot($category->id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->validated());

        return to_route('admin.categories.index')
            ->with('success', 'Kategori ditambahkan.');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, $request->validated());

        return to_route('admin.categories.index')
            ->with('success', 'Kategori diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // Penghapusan punya banyak prasyarat (anak, request, JSON store) —
        // logikanya di service, bukan di controller.
        $this->categories->delete($category);

        return back()->with('success', 'Kategori dihapus.');
    }
}
```

#### `create()`/`edit()` — kapan diperlukan?

Keduanya **hanya** dibutuhkan jika memakai halaman terpisah. Bila form berupa
modal (seperti §22.2), keduanya tidak perlu dan route-nya dipangkas:

```php
Route::resource('categories', CategoryController::class)
    ->except(['show', 'create', 'edit']);
```

Untuk kategori, halaman terpisah lebih disarankan — form-nya memuat pemilihan
induk yang butuh validasi hierarki (§9.2), dan menampilkan error validasi di
dalam modal jauh lebih merepotkan.

#### Aturan FormRequest

**Semua** aksi tulis (`store`, `update`, dan aksi kustom seperti `resolve`,
`reject`, `extend`) wajib memakai FormRequest — jangan pernah memvalidasi
dengan `$request->validate()` di dalam controller.

| Controller | FormRequest |
| :-- | :-- |
| `CategoryController@store/update` | `CategoryRequest` |
| `UserController@update` | `UserRequest` |
| `UserController@block` | `BlockUserRequest` |
| `StoreController@update` | `StoreRequest` |
| `VerificationController@rejectUser/rejectStore` | `RejectVerificationRequest` |
| `DisputeController@resolve` | `ResolveDisputeRequest` |
| `SettingController@update` | `SettingRequest` |

Alasannya: aturan validasi jadi bisa diuji terpisah, otorisasi tambahan bisa
ditaruh di `authorize()`, dan pesan error terkumpul di satu tempat.

---

## 11. FORM REQUESTS & VALIDASI (ADMIN)

`CategoryRequest`:

```php
class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-categories');
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name' => ['required', 'string', 'min:3', 'max:50'],

            'slug' => [
                'required', 'alpha_dash', 'max:50',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],

            'parent_id' => [
                'nullable',
                // Hanya kategori level teratas yang boleh jadi induk (maks 2 level).
                Rule::exists('categories', 'id')->whereNull('parent_id'),
                new NotADescendant($categoryId),   // cegah siklus, lihat §9.2
            ],

            'icon'       => ['nullable', 'string', 'max:50', new ValidFontAwesomeIcon()],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Slug dibuat otomatis jika kosong, supaya admin tidak perlu mengetiknya.
        $this->merge([
            'slug' => $this->slug ?: Str::slug($this->name ?? ''),
        ]);
    }
}
```

> `Rule::unique(...)->ignore()` menggantikan penggabungan string
> `'unique:categories,slug,'.$id`. Bentuk string akan rusak jika `$id` bernilai
> `null` (menghasilkan `unique:categories,slug,`) — yang justru terjadi pada
> aksi *create*.

#### Validasi ikon FontAwesome

Daftar kelas FontAwesome ada ribuan, jadi `in:` tidak praktis. Yang dibutuhkan
adalah pembatasan **format** plus daftar putih yang bisa dirawat:

```php
class ValidFontAwesomeIcon implements ValidationRule
{
    /** Ikon yang disediakan untuk kategori Seekitar. */
    private const ALLOWED = [
        'fa-utensils', 'fa-store', 'fa-screwdriver-wrench', 'fa-bolt',
        'fa-truck', 'fa-house', 'fa-shirt', 'fa-mobile-screen',
        'fa-motorcycle', 'fa-leaf', 'fa-graduation-cap', 'fa-scissors',
        'fa-paint-roller', 'fa-camera', 'fa-heart-pulse', 'fa-basket-shopping',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^fa-[a-z0-9-]+$/', (string) $value)) {
            $fail('Format ikon tidak valid. Contoh: fa-store');
            return;
        }

        if (! in_array($value, self::ALLOWED, true)) {
            $fail('Ikon tidak tersedia. Pilih dari daftar ikon yang disediakan.');
        }
    }
}
```

Di form admin, tampilkan sebagai **grid ikon yang bisa diklik**, bukan input
teks bebas — admin tidak perlu menghafal nama kelas, dan validasi di atas
menjadi jaring pengaman terakhir saja.

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

| Observer           | Hook                 | Efek                                                          |
| :----------------- | :------------------- | :-------------------------------------------------------------- |
| `ReviewObserver`   | `created`, `deleted` | Hitung ulang `rating_avg` & `total_reviews` (hanya `buyer_to_store`) |
| `OrderObserver`    | `creating`           | Buat `order_number` (`SKT-YYYYMMDD-NNNN`)                      |
| `OrderObserver`    | `updating`           | Isi `completed_at` / `cancelled_at`; blokir transisi tidak sah |
| `StoreObserver`    | `deleted`, `restored`| Soft delete listing terkait; pulihkan saat restore             |
| `ListingObserver`  | `saving`             | Normalkan `images` (buang duplikat, batasi 5)                  |
| `CustomerRequestObserver` | `created`     | Set `expires_at` dari `SettingService`                         |

**`OrderObserver` — nomor pesanan & penjaga transisi:**

```php
class OrderObserver
{
    public function __construct(private OrderStateMachine $states) {}

    public function creating(Order $order): void
    {
        $order->order_number ??= $this->generateNumber();
    }

    public function updating(Order $order): void
    {
        if (! $order->isDirty('status')) {
            return;
        }

        // Jaring pengaman terakhir: transisi tidak sah ditolak walau
        // datangnya dari seeder, tinker, atau admin panel.
        $this->states->assertCanTransition(
            $order->getOriginal('status'),
            $order->status,
        );

        match ($order->status) {
            OrderStatus::Selesai    => $order->completed_at ??= now(),
            OrderStatus::Dibatalkan => $order->cancelled_at ??= now(),
            default                 => null,
        };
    }

    private function generateNumber(): string
    {
        $date = now()->format('Ymd');
        $seq  = Redis::incr("order_seq:$date");
        Redis::expire("order_seq:$date", 172800);

        return sprintf('SKT-%s-%04d', $date, $seq);
    }
}
```

**`ReviewObserver` — perhatikan filter arah:**

```php
public function created(Review $review): void
{
    // Penilaian penjual->pembeli TIDAK memengaruhi rating toko.
    if ($review->direction !== ReviewDirection::BuyerToStore) {
        return;
    }

    RecalculateStoreRatingJob::dispatch($review->store_id);
}

public function deleted(Review $review): void
{
    // Admin menghapus ulasan bermasalah -> rating harus dihitung ulang.
    if ($review->direction === ReviewDirection::BuyerToStore) {
        RecalculateStoreRatingJob::dispatch($review->store_id);
    }
}
```

**Registrasi** (Laravel 11+ memakai atribut, bukan `EventServiceProvider`):

```php
#[ObservedBy([OrderObserver::class])]
class Order extends Model { /* ... */ }
```

> Observer cocok untuk konsistensi data, bukan untuk pekerjaan berat.
> Pengiriman notifikasi tetap lewat event → job antrian.
>
> ⚠️ Observer **tidak terpanggil** pada operasi massal seperti
> `Order::where(...)->update([...])`, karena query builder melewati model.
> Untuk pembaruan massal (mis. scheduler menutup permintaan kedaluwarsa), efek
> sampingnya harus ditulis eksplisit.

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

### 14.1 `BroadcastRequestJob` — implementasi lengkap

Inti mesin kedua Seekitar: menyebar permintaan pembeli ke penyedia yang relevan.

```php
class BroadcastRequestJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 120;

    public function __construct(public string $customerRequestId) {}

    public function handle(BroadcastService $broadcast): void
    {
        $request = CustomerRequest::with('category')->find($this->customerRequestId);

        // Permintaan bisa sudah ditutup/kedaluwarsa sebelum job dieksekusi.
        if (! $request || $request->status !== RequestStatus::Open) {
            Log::info('Broadcast dilewati', ['request' => $this->customerRequestId]);
            return;
        }

        $stores = $broadcast->matchingStores($request);

        if ($stores->isEmpty()) {
            Log::warning('Tidak ada penyedia cocok', [
                'request'   => $request->id,
                'category'  => $request->category_id,
                'radius_km' => $request->radius_km,
            ]);
            return;
        }

        // Satu job kecil per penerima: satu FCM gagal tidak menggagalkan sisanya.
        $stores->each(fn (Store $store) =>
            SendPushNotificationJob::dispatch(
                storeId: $store->id,
                notification: new RequestBroadcastNotification($request),
            )
        );

        Log::info('Permintaan disebar', [
            'request'    => $request->id,
            'recipients' => $stores->count(),
        ]);
    }

    /** Dipanggil setelah percobaan terakhir gagal. */
    public function failed(?Throwable $e): void
    {
        Log::error('BroadcastRequestJob gagal total', [
            'request' => $this->customerRequestId,
            'error'   => $e?->getMessage(),
        ]);
    }
}
```

**`BroadcastService::matchingStores()`** — kriteria pencocokan dari PRD §5.2.2:

```php
public function matchingStores(CustomerRequest $request): Collection
{
    [$lng, $lat] = [$request->longitude, $request->latitude];

    return Store::query()
        ->where('is_active', true)
        ->where('verification_status', VerificationStatus::Verified)
        // Toko tidak boleh menawar pada permintaannya sendiri.
        ->where('user_id', '!=', $request->user_id)
        // Kategori toko memuat kategori permintaan (JSON, bukan FK).
        ->whereRaw('JSON_CONTAINS(category_ids, ?)', [(string) $request->category_id])
        // Dua arah: toko dalam radius pembeli, DAN pembeli dalam radius layanan toko.
        ->nearby($lat, $lng, $request->radius_km)
        ->whereRaw(
            'ST_Distance_Sphere(location, ST_GeomFromText(?, 4326)) <= service_radius_km * 1000',
            ["POINT($lng $lat)"]
        )
        // Prioritas: rating tertinggi, lalu terdekat.
        ->orderByDesc('rating_avg')
        ->orderBy('distance_km')
        ->limit(50)          // batasi ledakan notifikasi
        ->get();
}
```

> ⚠️ Pencocokan sengaja **dua arah**. Toko kelontong beradius 5 km tidak akan
> menerima permintaan dari pembeli 12 km jauhnya, meski pembeli menyetel radius
> 15 km. Tanpa syarat kedua, penyedia dibanjiri permintaan di luar jangkauan.

**Scheduler** (`routes/console.php`):

```php
use Illuminate\Support\Facades\Schedule;

// Menutup permintaan & penawaran kedaluwarsa.
Schedule::command('requests:close-expired')
    ->everyFifteenMinutes()
    ->withoutOverlapping()          // cegah tumpang tindih jika eksekusi lambat
    ->onOneServer();                // aman saat multi-server

// Membersihkan berkas unggahan sementara yang tidak jadi dipakai.
Schedule::command('uploads:prune')->hourly();

// Menghitung ulang rating (jaring pengaman bila ada observer terlewat).
Schedule::command('stores:recalculate-ratings')->dailyAt('03:00');
```

Perintah ini memakai indeks `cr_status_expires_idx` seperti dijelaskan di
`DATABASE.md` §11.

---

## 15. NOTIFIKASI (PUSH & WHATSAPP)

### 15.1 Push Notification (FCM)

Firebase menghentikan API legacy, jadi pakai **HTTP v1** yang berbasis service
account:

```bash
composer require kreait/laravel-firebase
```

```env
FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json
FIREBASE_PROJECT_ID=seekitar-prod
```

Notification memakai channel kustom agar `toFcm()` bisa dites terpisah:

```php
class RequestBroadcastNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CustomerRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['fcm', 'database'];
    }

    public function toFcm(object $notifiable): CloudMessage
    {
        return CloudMessage::new()
            ->withNotification([
                'title' => 'Ada kebutuhan baru di sekitar Anda',
                'body'  => Str::limit($this->request->title, 80),
            ])
            // data payload dipakai app untuk deep-link (Mobile Guide §12)
            ->withData([
                'screen'    => 'request_detail',
                'entity_id' => $this->request->id,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return ['request_id' => $this->request->id, 'title' => $this->request->title];
    }
}
```

**Token tidak valid harus dibersihkan.** Jika diabaikan, antrian terus mencoba
mengirim ke perangkat yang sudah menghapus aplikasi:

```php
try {
    $messaging->send($message->withChangedTarget('token', $device->fcm_token));
} catch (NotFound|InvalidMessage $e) {
    $device->delete();      // token kedaluwarsa/dicabut
}
```

### 15.2 WhatsApp OTP

Tidak ada paket resmi Laravel untuk provider lokal, jadi bungkus HTTP client
sendiri di balik satu interface — supaya provider bisa diganti tanpa menyentuh
kode pemanggil.

```php
interface WhatsAppGateway
{
    public function sendOtp(string $phone, string $code): void;
}
```

| Provider | Cocok untuk | Catatan |
| :-- | :-- | :-- |
| Twilio (`twilio/sdk`) | Produksi lintas negara | Perlu template WhatsApp Business terdaftar |
| Kirim WA / Wablas | Pasar Indonesia, biaya lebih murah | REST sederhana, cukup `Http::post()` |
| `LogWhatsAppGateway` | Development | OTP ditulis ke `storage/logs` |

```php
class KirimWaGateway implements WhatsAppGateway
{
    public function sendOtp(string $phone, string $code): void
    {
        $response = Http::withToken(config('services.kirimwa.token'))
            ->timeout(10)
            ->retry(2, 500)
            ->post(config('services.kirimwa.url').'/send', [
                'phone'   => $phone,
                'message' => "Kode OTP Seekitar Anda: {$code}. Berlaku 5 menit. "
                           . "Jangan bagikan kode ini kepada siapa pun.",
            ]);

        if ($response->failed()) {
            throw new OtpDeliveryException($response->body());
        }
    }
}
```

Binding di `AppServiceProvider` — di lokal, OTP tidak benar-benar dikirim:

```php
$this->app->bind(WhatsAppGateway::class, fn () =>
    app()->isProduction() ? new KirimWaGateway() : new LogWhatsAppGateway()
);
```

> 🔒 OTP disimpan di Redis dengan TTL 5 menit dan **di-hash**, bukan plaintext:
> `Redis::setex("otp:$phone", 300, Hash::make($code))`. Batasi juga percobaan
> verifikasi (5×) agar tidak bisa ditebak paksa.

---

## 16. GEOSPASIAL & QUERY RADIUS

Semua query radius memakai `GeolocationService` (§4) yang dipanggil lewat
**Eloquent scope**, sehingga raw SQL tidak tersebar ke banyak controller.

```php
// app/Models/Concerns/HasLocation.php
trait HasLocation
{
    /** Batasi hasil pada radius (km) dari sebuah titik. */
    public function scopeNearby(Builder $q, float $lat, float $lng, float $radiusKm): Builder
    {
        return $q->whereRaw(
            'ST_Distance_Sphere(location, ST_GeomFromText(?, 4326)) <= ?',
            ["POINT($lng $lat)", $radiusKm * 1000]
        );
    }

    /** Tambahkan kolom distance_km agar bisa diurutkan & ditampilkan. */
    public function scopeWithDistance(Builder $q, float $lat, float $lng): Builder
    {
        return $q->select('*')->selectRaw(
            'ST_Distance_Sphere(location, ST_GeomFromText(?, 4326)) / 1000 AS distance_km',
            ["POINT($lng $lat)"]
        );
    }

    public function scopeOrderByDistance(Builder $q, string $dir = 'asc'): Builder
    {
        return $q->orderBy('distance_km', $dir);
    }
}
```

Dipakai di `Store` dan `CustomerRequest` (keduanya punya kolom `location`):

```php
class Store extends Model
{
    use HasLocation, SoftDeletes;
}

// Pemakaian di controller:
$stores = Store::query()
    ->withDistance($lat, $lng)
    ->nearby($lat, $lng, $radiusKm)
    ->where('is_active', true)
    ->orderByDistance()
    ->paginate($perPage);
```

> ⚠️ **Urutan `POINT(longitude latitude)`** — terbalik dari kebiasaan menulis
> "lat, lng". Menukarnya tidak menimbulkan error, hanya hasil yang salah diam-diam.
> Karena itu semua penulisan POINT dipusatkan di trait ini.
>
> ⚠️ `withDistance()` harus dipanggil **sebelum** `orderByDistance()`, karena
> alias `distance_km` belum ada sebelum kolomnya dipilih.

**Menyimpan koordinat** (kolom POINT tidak bisa diisi string biasa):

```php
$store->location = DB::raw("ST_GeomFromText('POINT($lng $lat)', 4326)");
```

---

## 17. API RESPONSE & PAGINASI (WEB & API)

- **Web:** Datatables menangani server-side processing dan paginasi otomatis.
- **API:** Paginasi standar (`page`, `per_page`; lihat `API_DOCUMENTATION.md` §12.2).

### 17.1 Trait `ApiResponse`

Format response di `API_DOCUMENTATION.md` §1 hanya konsisten jika dibungkus
satu helper — jangan menyusun array `['success' => ...]` manual di controller.

```php
trait ApiResponse
{
    protected function ok(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json(array_filter([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ], fn ($v) => $v !== null), $status);
    }

    protected function created(mixed $data, string $message = 'Data berhasil dibuat'): JsonResponse
    {
        return $this->ok($data, $message, 201);
    }

    protected function fail(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    /** Paginator -> struktur data + meta yang seragam. */
    protected function paginated(LengthAwarePaginator $p, ?string $resource = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $resource ? $resource::collection($p->items()) : $p->items(),
            'meta'    => [
                'current_page' => $p->currentPage(),
                'per_page'     => $p->perPage(),
                'total'        => $p->total(),
                'last_page'    => $p->lastPage(),
            ],
        ]);
    }
}
```

### 17.2 Response Web (Admin)

Admin panel memakai redirect + flash message, bukan JSON. Seragamkan dengan
helper kecil supaya nama kunci flash tidak berbeda-beda antar controller:

```php
trait WebResponse
{
    protected function redirectSuccess(string $route, string $message, array $params = []): RedirectResponse
    {
        return to_route($route, $params)->with('success', $message);
    }

    protected function backError(string $message): RedirectResponse
    {
        return back()->withInput()->with('error', $message);
    }
}
```

Layout admin menampilkannya di satu tempat:

```blade
@if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if (session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif
```

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

> Daftar permission di atas **sudah** memuat `manage-settings` (12 permission,
> sesuai §6.2). Pastikan keduanya tetap sinkron saat menambah permission baru.

**`DatabaseSeeder`** — satu pintu, dan urutannya penting karena
`RolesAndPermissionsSeeder` membuat user super-admin yang butuh role:

```php
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,  // 1. role & permission dulu
            CategorySeeder::class,             // 2. 24 kategori (wajib produksi)
            SettingSeeder::class,              // 3. nilai default tabel settings
        ]);

        // Data contoh HANYA untuk pengembangan.
        if (app()->environment('local', 'testing')) {
            $this->call(DummyDataSeeder::class);
        }
    }
}
```

```bash
php artisan db:seed                          # semua
php artisan db:seed --class=CategorySeeder   # satu saja
```

> ⚠️ Seeder produksi (`RolesAndPermissionsSeeder`, `CategorySeeder`,
> `SettingSeeder`) wajib **idempoten** — pakai `firstOrCreate`/`updateOrCreate`,
> bukan `create`. Menjalankan ulang saat deploy tidak boleh menggandakan data
> atau melempar error unique.

---

## 20. TESTING

Target minimum sebelum rilis: **seluruh endpoint API punya feature test**, dan
setiap Service punya unit test.

```bash
php artisan test                      # semua
php artisan test --filter=OfferTest   # satu berkas
php artisan test --parallel           # lebih cepat di CI
```

### 20.1 Feature Test (API)

| Berkas | Yang diuji |
| :-- | :-- |
| `AuthTest` | OTP terkirim, rate limit per nomor, OTP salah/kedaluwarsa, token terbit, logout mencabut token |
| `ProfileTest` | Update profil, validasi avatar, `EnsureProfileComplete` memblokir profil kosong |
| `VerificationTest` | Unggah KTP, approve menaikkan level ke 2, reject **tetap** level 1 |
| `StoreTest` | Buat toko butuh level 2, pencarian radius, toko `pending` tidak muncul |
| `ListingTest` | CHECK harga/stok/slot per tipe, maks 5 gambar, hanya pemilik boleh ubah |
| `CustomerRequestTest` | Buat permintaan memicu broadcast, batas 2 perpanjangan, auto-expire |
| `OfferTest` | Satu toko satu penawaran, harga di luar budget ditolak, kedaluwarsa mengikuti request |
| `AcceptOfferTest` | Offer lain ter-reject, request `closed`, order terbentuk — **dalam satu transaksi** |
| `OrderTest` | `listing_id` XOR `offer_id`, transisi status sah/tidak sah, `order_number` unik |
| `ReviewTest` | Dua arah, satu per arah, jendela 7 hari, hanya `buyer_to_store` mengubah rating |
| `DisputeTest` | Order terkunci saat dispute, resolve membuka kunci |
| `AdminTest` | Non-admin ditolak, blokir mencabut token, super-admin lolos `Gate::before` |

Contoh yang menguji aturan paling rawan — balapan saat menerima penawaran:

```php
it('hanya menghasilkan satu order meski accept dipanggil dua kali', function () {
    $request = CustomerRequest::factory()->has(Offer::factory()->count(3))->create();
    $offer   = $request->offers->first();

    $this->actingAs($request->user)
        ->patchJson("/api/v1/offers/{$offer->id}/accept")
        ->assertOk();

    // Percobaan kedua harus ditolak, bukan membuat order kedua.
    $this->actingAs($request->user)
        ->patchJson("/api/v1/offers/{$offer->id}/accept")
        ->assertStatus(409);

    expect(Order::where('offer_id', $offer->id)->count())->toBe(1);
    expect($request->fresh()->status)->toBe(RequestStatus::Closed);
    expect($request->offers()->where('status', OfferStatus::Rejected)->count())->toBe(2);
});
```

### 20.2 Unit Test (Service)

| Berkas | Fokus |
| :-- | :-- |
| `OrderStateMachineTest` | Matriks transisi lengkap, termasuk yang ditolak |
| `GeolocationServiceTest` | Urutan `POINT(lng lat)` benar, konversi km↔meter |
| `BroadcastServiceTest` | Pencocokan dua arah, toko sendiri dikecualikan, batas 50 |
| `OtpServiceTest` | OTP di-hash, TTL 5 menit, batas 5 percobaan |
| `SettingServiceTest` | Cache dibersihkan setelah `set()` |

### 20.3 Konfigurasi

```xml
<!-- phpunit.xml -->
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="CACHE_STORE" value="array"/>
```

> ⚠️ **SQLite tidak punya fungsi spasial** (`ST_Distance_Sphere`). Test yang
> menyentuh query radius harus dijalankan terhadap MySQL 8, atau `nearby()`
> di-mock. Jangan sampai lolos karena "tidak diuji" — inilah alasan
> `GeolocationServiceTest` memakai koneksi MySQL terpisah di CI.

---

## 21. DEPLOYMENT

### 21.1 Environment Variables

```env
# --- Aplikasi -------------------------------------------------------------
APP_NAME=Seekitar
APP_ENV=production
APP_KEY=                       # php artisan key:generate
APP_DEBUG=false                # WAJIB false di produksi
APP_URL=https://api.seekitar.id
APP_TIMEZONE=UTC               # simpan UTC, konversi di klien

# --- Database -------------------------------------------------------------
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=seekitar
DB_USERNAME=seekitar
DB_PASSWORD=

# --- Redis (cache, session, queue) ---------------------------------------
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=
REDIS_PORT=6379
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# --- Sanctum & Session ----------------------------------------------------
SANCTUM_STATEFUL_DOMAINS=admin.seekitar.id
SESSION_DOMAIN=.seekitar.id
SESSION_SECURE_COOKIE=true

# --- Penyimpanan berkas ---------------------------------------------------
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=seekitar-media
AWS_URL=https://cdn.seekitar.id
AWS_BUCKET_PRIVATE=seekitar-ktp     # KTP & selfie, TIDAK publik

# --- Firebase (FCM) -------------------------------------------------------
FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json
FIREBASE_PROJECT_ID=seekitar-prod

# --- WhatsApp OTP ---------------------------------------------------------
WHATSAPP_DRIVER=kirimwa        # kirimwa | twilio | log
KIRIMWA_URL=https://api.kirimwa.id/v1
KIRIMWA_TOKEN=
TWILIO_SID=
TWILIO_TOKEN=
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886

# --- Monitoring -----------------------------------------------------------
SENTRY_LARAVEL_DSN=
LOG_CHANNEL=stack
LOG_LEVEL=warning              # 'debug' membanjiri disk di produksi
```

### 21.2 Perintah Deploy

```bash
php artisan down --render="errors::503"
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force            # idempoten, aman diulang
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart              # worker memuat ulang kode baru
php artisan up
```

> ⚠️ `queue:restart` **wajib** setelah deploy. Tanpa itu, worker lama terus
> berjalan dengan kode versi sebelumnya sampai proses dimatikan manual.
>
> ⚠️ Jangan pakai `config:cache` bila ada `env()` di luar berkas `config/` —
> nilainya akan menjadi `null` setelah cache dibuat.

### 21.3 Queue Worker (Supervisor)

Antrian `high` diproses lebih dulu agar OTP dan broadcast tidak tertahan di
belakang pekerjaan berat.

```ini
; /etc/supervisor/conf.d/seekitar-worker.conf
[program:seekitar-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/seekitar/artisan queue:work redis --queue=high,default --tries=3 --max-time=3600 --sleep=1
directory=/var/www/seekitar
autostart=true
autorestart=true
stopwaitsecs=3600
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/seekitar/storage/logs/worker.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start seekitar-worker:*
```

`--max-time=3600` mendaur ulang worker tiap jam untuk mencegah kebocoran
memori. `stopwaitsecs` dibuat besar agar job yang sedang berjalan tidak
terpotong saat restart.

### 21.4 Scheduler (Cron)

Laravel hanya butuh **satu** entri cron; sisanya diatur di `routes/console.php`:

```cron
* * * * * cd /var/www/seekitar && php artisan schedule:run >> /dev/null 2>&1
```

Verifikasi jadwal yang terdaftar:

```bash
php artisan schedule:list
```

### 21.5 Tooling Pengembangan

Hanya untuk lokal — **jangan** dipasang di produksi:

```bash
composer require --dev laravel/telescope barryvdh/laravel-debugbar
php artisan telescope:install && php artisan migrate
```

```php
// AppServiceProvider::register()
if ($this->app->environment('local')) {
    $this->app->register(TelescopeServiceProvider::class);
}
```

Telescope sangat membantu untuk mengintip **job antrian yang gagal** dan query
lambat — dua hal yang paling sering bermasalah di sistem berbasis broadcast
seperti ini. Batasi perekamannya agar tidak membengkak:

```php
Telescope::filter(fn (IncomingEntry $entry) =>
    $entry->isReportableException() || $entry->isFailedJob() || $entry->isSlowQuery()
);
```

---

## 22. LAMPIRAN: CONTOH KODE BLADE & CONTROLLER

### 22.1 Datatables Controller (Category)

Yajra menyediakan dua pendekatan. Yang dipakai di §22.2 adalah **kelas
DataTable** (`app/DataTables/`), karena Blade-nya memanggil `$dataTable->table()`
dan `$dataTable->scripts()`.

```php
// app/DataTables/CategoriesDataTable.php
class CategoriesDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('parent_name', fn (Category $c) => $c->parent?->name ?? '-')
            // Kirim data lewat view, bukan rangkaian string HTML — atribut
            // yang mengandung kutip (mis. nama "Bengkel \"Jaya\"") akan
            // merusak markup jika digabung manual.
            ->addColumn('action', fn (Category $c) => view('admin.categories.actions', ['category' => $c]))
            ->rawColumns(['action'])
            ->setRowId('id');
    }

    public function query(Category $model): QueryBuilder
    {
        return $model->newQuery()->with('parent')->select('categories.*');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('categoriesTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
            ->parameters(['language' => ['url' => asset('js/datatables-id.json')]]);
    }

    protected function getColumns(): array
    {
        return [
            Column::make('name')->title('Nama'),
            Column::make('slug')->title('Slug'),
            Column::make('parent_name')->title('Induk')->orderable(false),
            Column::make('icon')->title('Ikon'),
            Column::computed('action')->title('Aksi')
                ->exportable(false)->printable(false)->width(120)->addClass('text-center'),
        ];
    }
}
```

**Controller wajib mengirim objeknya ke view** — Blade di §22.2 memanggil
`$dataTable->table()`, jadi tanpa ini muncul `Undefined variable $dataTable`:

```php
public function index(CategoriesDataTable $dataTable)
{
    // render() sekaligus menyuntikkan $dataTable ke view.
    return $dataTable->render('admin.categories.index');
}
```

> Alternatifnya memakai facade `DataTables::of()` di method `data()` seperti
> §10 — tapi kalau memilih itu, Blade-nya **tidak boleh** memanggil
> `$dataTable->table()`; tabel `<table>` ditulis manual dan inisialisasi
> DataTables dilakukan di JavaScript. Jangan mencampur kedua pendekatan.

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
  // handling modal, AJAX store/update — lihat 22.2a
</script>
@endpush
```

### 22.2a Logika Modal Create vs Edit

Satu form dipakai untuk dua mode. Yang membedakan hanyalah URL tujuan dan
method — dan **method spoofing** (`_method=PUT`) wajib, karena form HTML hanya
mengenal GET/POST.

```js
const modal   = new bootstrap.Modal('#categoryModal');
const form    = document.getElementById('categoryForm');
const titleEl = document.querySelector('#categoryModal .modal-title');

/** Bersihkan sisa state sebelumnya, lalu isi sesuai mode. */
function openModal(mode, category = null) {
  form.reset();
  form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

  if (mode === 'edit') {
    titleEl.textContent = 'Edit Kategori';
    form.action = `/admin/categories/${category.id}`;
    form.querySelector('#method').value = 'PUT';   // spoofing
    form.name.value       = category.name;
    form.slug.value       = category.slug;
    form.parent_id.value  = category.parent_id ?? '';
    form.icon.value       = category.icon ?? '';
    form.sort_order.value = category.sort_order ?? 0;
  } else {
    titleEl.textContent = 'Tambah Kategori';
    form.action = '/admin/categories';
    form.querySelector('#method').value = 'POST';
  }

  modal.show();
}

document.getElementById('btnAddCategory')
  .addEventListener('click', () => openModal('create'));

// Tombol Edit dibuat ulang setiap tabel dimuat, jadi pakai event delegation —
// listener langsung akan hilang setelah paginasi/pencarian.
document.querySelector('#categoriesTable').addEventListener('click', (e) => {
  const btn = e.target.closest('.edit-btn');
  if (btn) openModal('edit', JSON.parse(btn.dataset.category));
});

form.addEventListener('submit', async (e) => {
  e.preventDefault();

  const res = await fetch(form.action, {
    method: 'POST',                       // method asli tetap POST
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    body: new FormData(form),
  });

  if (res.status === 422) {
    const { errors } = await res.json();
    Object.entries(errors).forEach(([field, messages]) => {
      const input = form.querySelector(`[name="${field}"]`);
      if (!input) return;
      input.classList.add('is-invalid');
      input.insertAdjacentHTML('afterend',
        `<div class="invalid-feedback">${messages[0]}</div>`);
    });
    return;                               // modal tetap terbuka
  }

  if (res.ok) {
    modal.hide();
    window.LaravelDataTables.categoriesTable.ajax.reload(null, false); // pertahankan halaman
  }
});
```

Tombol Edit membawa datanya sebagai satu atribut JSON, sehingga aman terhadap
tanda kutip di nama kategori:

```blade
{{-- resources/views/admin/categories/actions.blade.php --}}
@can('manage-categories')
  <button class="btn btn-sm btn-warning edit-btn"
          data-category='@json($category->only(["id","name","slug","parent_id","icon","sort_order"]))'>
    Edit
  </button>
  <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline"
        onsubmit="return confirm('Hapus kategori {{ $category->name }}?')">
    @csrf @method('DELETE')
    <button class="btn btn-sm btn-danger">Hapus</button>
  </form>
@endcan
```

> ⚠️ `ajax.reload(null, false)` — argumen kedua `false` menjaga posisi halaman.
> Tanpa itu, admin selalu terlempar ke halaman 1 setiap menyimpan.

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
