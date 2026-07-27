# 📄 Seekitar – Server Implementation Guide

**Versi:** 2.0 (Ultra‑Detailed · Production‑Ready)  
**Tanggal:** 27 Juli 2026  
**Target:** Laravel 13 + PHP 8.3.30 + MySQL 8.0 + Bootstrap 5 + Yajra Datatables + Spatie Permission

---

## DAFTAR ISI
    
1. [Ikhtisar & Prinsip](#1-ikhtisar--prinsip)
2. [Tech Stack & Versi](#2-tech-stack--versi)
3. [Instalasi & Konfigurasi Library Tambahan](#3-instalasi--konfigurasi-library-tambahan)
4. [Struktur Proyek](#4-struktur-proyek)
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
- **Web Admin Panel** menggunakan Laravel Blade + Bootstrap 5 + Yajra Datatables untuk pengelolaan internal (verifikasi, manajemen data, dispute).
- **Web Public** halaman katalog, landing page (SEO-friendly).

Prinsip desain:

- **Thin controllers, fat models** – logika bisnis di model/service.
- **Validasi ketat** – FormRequest + custom rule.
- **Database-first integrity** – FK, constraint, spatial index.
- **UUID sebagai primary key** – mencegah enumerasi.
- **Observers** untuk side‑effect (rating, notifikasi).
- **Job antrian** untuk broadcast permintaan ke penyedia.
- **Soft delete** untuk users, stores, listings.
- **Responsive UI** – Bootstrap 5 dengan komponen siap pakai.

---

## 2. TECH STACK & VERSI

| Komponen          | Teknologi                          |
| ----------------- | ---------------------------------- |
| Bahasa            | PHP 8.3.30                         |
| Framework         | Laravel 13                         |
| Database          | MySQL 8.0                          |
| Cache & Queue     | Redis 7                            |
| Storage           | AWS S3 / MinIO                     |
| Push Notification | Firebase Cloud Messaging           |
| WhatsApp          | Twilio / Kirim WA API              |
| Admin UI          | Bootstrap 5, Yajra Datatables 11.x |
| Permission        | Spatie Laravel Permission 6.x      |
| CI/CD             | GitHub Actions                     |

---

## 3. INSTALASI & KONFIGURASI LIBRARY TAMBAHAN

### 3.1 Yajra Datatables

```bash
composer require yajra/laravel-datatables:^11.0
```

**Konfigurasi:** Tidak ada file konfig khusus. Langsung gunakan facade `DataTables`.

### 3.2 Spatie Laravel Permission

```bash
composer require spatie/laravel-permission:^6.0
```

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

Kita gunakan Laravel Breeze (opsional) atau langsung kompilasi Bootstrap 5 via Vite (tidak dianjurkan karena Anda minta tanpa Vite).  
**Alternatif:** Gunakan Bootstrap 5 CDN di layout Blade utama.

**Layout Admin (`resources/views/layouts/admin.blade.php`)** akan menyertakan CSS & JS Bootstrap 5, Datatables, dan Font Awesome.

---

## 4. STRUKTUR PROYEK (Tambahan Admin)

```
app/
├── ...
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
│   └── Requests/
│       └── Admin/
│           ├── CategoryRequest.php
│           ├── UserRequest.php
│           ├── StoreRequest.php
│           ├── SettingRequest.php
│           └── ...
├── DataTables/
│   ├── UsersDataTable.php
│   ├── StoresDataTable.php
│   ├── ListingsDataTable.php
│   ├── RequestsDataTable.php
│   ├── OffersDataTable.php
│   ├── OrdersDataTable.php
│   ├── DisputesDataTable.php
│   └── ReviewsDataTable.php
```

---

## 5. MIDDLEWARE & PIPELINE (Tambahan Web)

**Web Routes Middleware:**

- `auth` – memastikan user login.
- `role:admin` – hanya user dengan role admin yang bisa akses admin panel. Kita daftarkan di `Kernel.php` atau gunakan middleware Spatie.

---

## 6. AUTENTIKASI & OTORISASI

### 6.1 Sanctum & Token (API)

Seperti sebelumnya.

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

Layout admin menggunakan sidebar Bootstrap 5.  
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

## 13. OBSERVERS & EVENTS (Sama seperti sebelumnya)

Tidak ada perubahan.

---

## 14. JOBS & QUEUE (Tetap)

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
