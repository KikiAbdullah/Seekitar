# 📘 API DOCUMENTATION – SEEKITAR

**Versi:** 2.1 (Production‑Ready)  
**Tanggal Publikasi:** 27 Juli 2026  
**Backend:** Laravel 13 · PHP 8.3+ · Sanctum 4

## BASE URL PER ENVIRONMENT

| Environment                | Base URL                                 | Keterangan                                   |
| :------------------------- | :--------------------------------------- | :-------------------------------------------- |
| **Production**             | `https://api.seekitar.id/api/v1`         | Rilis publik                                  |
| **Staging**                | `https://staging-api.seekitar.id/api/v1` | UAT & closed beta                             |
| **Development**            | `http://localhost:8000/api/v1`           | `php artisan serve` di mesin lokal            |
| **Dev (emulator Android)** | `http://10.0.2.2:8000/api/v1`            | `localhost` tidak terjangkau dari emulator    |
| **Dev (perangkat fisik)**  | `http://<IP-LAN>:8000/api/v1`            | Jalankan `artisan serve --host=0.0.0.0`       |

> ⚠️ Emulator Android memetakan mesin host ke `10.0.2.2`; simulator iOS bisa
> memakai `localhost` langsung. Simpan base URL lewat `--dart-define` atau file
> konfigurasi environment — **jangan di-hardcode** di dalam kode.

```bash
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
```

Semua environment memakai skema versi yang sama (`/api/v1`), jadi perpindahan
environment cukup dengan mengganti host.

> 📌 Versi backend & matriks kompatibilitas paket ada di [`TECH_STACK.md`](TECH_STACK.md) (sumber kebenaran tunggal).

## DAFTAR ISI

1. [Autentikasi & Keamanan](#1-autentikasi--keamanan)
2. [Users](#2-users) — termasuk Logout (2.6) & FCM Token (2.7)
3. [Stores](#3-stores)
4. [Listings](#4-listings) — termasuk Upload Gambar (4.0) & Wishlist (4.5)
5. [Customer Requests](#5-customer-requests)
6. [Offers](#6-offers)
7. [Orders](#7-orders) — termasuk Diagram State (7.2)
8. [Reviews](#8-reviews)
9. [Disputes](#9-disputes)
10. [Admin Endpoints](#10-admin-endpoints) — Resource (10.4) & Settings (10.5)
11. [Status Kode & Error Handling](#11-status-kode--error-handling)
12. [Konvensi Global](#12-konvensi-global) — Tanggal, Paginasi, Satuan

---

## 1. AUTENTIKASI & KEAMANAN

Semua endpoint kecuali yang dinyatakan “Public” memerlukan header:

```
Authorization: Bearer <personal_access_token>
```

Token didapat dari proses OTP WhatsApp.

### Rate Limiting

- Umum: 60 request/menit per IP.
- `auth/request-otp`: 3 request/menit per nomor.
- `auth/verify-otp`: 5 request/menit per nomor.
- `offers`: 30 request/menit per penyedia.

**Setiap response** (sukses maupun gagal) menyertakan header kuota:

| Header | Contoh | Arti |
| :-- | :-- | :-- |
| `X-RateLimit-Limit` | `60` | Kuota maksimum pada jendela waktu ini |
| `X-RateLimit-Remaining` | `57` | Sisa kuota |
| `X-RateLimit-Reset` | `1785312000` | Unix timestamp saat kuota dipulihkan |
| `Retry-After` | `42` | **Hanya saat 429.** Detik sampai boleh mencoba lagi |

```http
HTTP/1.1 429 Too Many Requests
X-RateLimit-Limit: 3
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1785312060
Retry-After: 42
```

```json
{
  "success": false,
  "message": "Terlalu banyak permintaan OTP. Coba lagi dalam 42 detik.",
  "errors": null
}
```

> Untuk klien mobile: baca `Retry-After` dan tampilkan hitung mundur, jangan
> mencoba ulang otomatis. Endpoint OTP dibatasi **per nomor telepon**, bukan
> per IP, sehingga ganti jaringan tidak mengatur ulang kuota.

### Format Response Standar

```json
{
  "success": true,
  "data": { ... },
  "message": "OK"
}
```

Jika error:

```json
{
  "success": false,
  "message": "Deskripsi error",
  "errors": { "field": ["error detail"] } // opsional
}
```

---

## 2. USERS

### 2.1 Request OTP

```http
POST /auth/request-otp
```

**Public** – Mengirim OTP WhatsApp ke nomor telepon.

**Body:**

```json
{
  "phone": "6281234567890"
}
```

**Response 200:**

```json
{
  "success": true,
  "message": "OTP telah dikirim ke WhatsApp Anda."
}
```

**Error:**

- `422` – Validasi gagal (format nomor salah)
- `429` – Terlalu banyak percobaan

### 2.2 Verify OTP

```http
POST /auth/verify-otp
```

**Public** – Verifikasi OTP dan dapatkan token akses.

**Body:**

```json
{
  "phone": "6281234567890",
  "otp": "123456"
}
```

**Response 200:**

```json
{
  "success": true,
  "data": {
    "token": "1|abcdef...",
    "token_type": "Bearer",
    "expires_in": 2592000,
    "is_new_user": true,
    "user": {
      "id": "uuid",
      "phone": "6281234567890",
      "name": "Budi Santoso",
      "avatar_url": null,
      "verification_level": 1,
      "location": null
    }
  }
}
```

| Field | Keterangan |
| :-- | :-- |
| `token_type` | Selalu `Bearer`. Kirim sebagai `Authorization: Bearer <token>`. |
| `expires_in` | Umur token dalam **detik** (30 hari). Klien menyimpan waktu kedaluwarsa, bukan menghitung sendiri. |
| `is_new_user` | `true` jika akun baru dibuat — klien mengarahkan ke layar lengkapi profil. |

> **Tidak ada `refresh_token`.** Sanctum memakai token berumur panjang tanpa
> mekanisme refresh. Saat token kedaluwarsa atau ditolak `401`, klien harus
> mengulang alur OTP. Menambahkan refresh token berarti mengganti Sanctum
> dengan Passport/OAuth2 — di luar cakupan MVP.
>
> `location: null` pada pengguna baru itu wajar; lokasi diisi pada langkah
> berikutnya lewat `PATCH /auth/profile`.

**Error:**

- `401` – OTP salah atau kadaluarsa
- `422` – Validasi
- `423` – Akun dibekukan admin (lihat §11)

### 2.3 Get My Profile

```http
GET /auth/me
```

**Auth required** – Mendapatkan data profil pengguna yang sedang login.

**Response 200:**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": "uuid",
      "phone": "6281234567890",
      "name": "Budi Santoso",
      "avatar_url": "https://...",
      "verification_level": 2,
      "location": { "type": "Point", "coordinates": [106.845, -6.211] }
    }
  }
}
```

### 2.4 Update Profile

```http
PATCH /auth/profile
```

**Auth required** – Memperbarui nama, avatar, dan lokasi default.

**Body (multipart/form-data):**
| Field | Tipe | Aturan validasi |
|-------|------|-----------------|
| `name` | string | `sometimes\|string\|min:3\|max:100` |
| `avatar` | file | `sometimes\|image\|mimes:jpeg,jpg,png\|max:2048\|dimensions:min_width=200,min_height=200` |
| `latitude` | numeric | `required_with:longitude\|numeric\|between:-90,90` |
| `longitude` | numeric | `required_with:latitude\|numeric\|between:-180,180` |

Semua field opsional (`PATCH` = pembaruan sebagian), tetapi `latitude` dan
`longitude` **wajib berpasangan** — mengirim salah satu saja menghasilkan 422.

- `max:2048` dalam satuan **kilobyte** (2 MB).
- `dimensions:min_width=200,min_height=200` mencegah avatar pecah saat
  ditampilkan pada layar kepadatan tinggi.

**Response 200:** (data user yang sudah diperbarui)

**Error:**

- `422` – Validasi gagal, mis. avatar terlalu kecil:

```json
{
  "success": false,
  "message": "Data yang dikirim tidak valid.",
  "errors": {
    "avatar": ["Dimensi gambar minimal 200x200 piksel."]
  }
}
```

### 2.5 Verification – Upload KTP

```http
POST /auth/verification/ktp
```

**Auth required** (verification_level < 2)

**Body (multipart/form-data):**
| Field | Tipe | Aturan validasi |
|-------|------|-----------------|
| `ktp_image` | file | `required\|image\|mimes:jpeg,jpg,png\|max:5120\|dimensions:min_width=600,min_height=400` |
| `selfie_image` | file | `required\|image\|mimes:jpeg,jpg,png\|max:5120\|dimensions:min_width=600,min_height=600` |

Keduanya **wajib dikirim bersamaan** — verifikasi tidak bisa diproses sebagian.
Batas 5 MB (`max:5120`) mengakomodasi foto kamera ponsel tanpa kompresi berat,
sedangkan batas dimensi minimum memastikan NIK masih terbaca admin.

**Response 200:**

```json
{
  "success": true,
  "message": "Verifikasi KTP sedang ditinjau oleh admin.",
  "data": {
    "submitted_at": "2026-07-27T10:00:00Z",
    "estimated_review_hours": 24
  }
}
```

Verification_level akan dinaikkan ke 2 setelah disetujui admin.

**Error:**

- `409` – Pengajuan sebelumnya masih berstatus `pending`
- `422` – Validasi file gagal

> 🔒 **Penanganan data sensitif.** `ktp_image` dan `selfie_image` adalah data
> pribadi menurut UU PDP. Berkas disimpan di bucket privat dan **tidak pernah**
> dikembalikan lewat endpoint publik mana pun — termasuk `GET /auth/me`. Hanya
> admin terverifikasi yang bisa mengaksesnya melalui URL pre-signed berumur
> pendek.

### 2.6 Logout

```http
POST /auth/logout
```

**Auth required** – Mencabut token yang sedang dipakai.

**Response 200:**

```json
{
  "success": true,
  "message": "Berhasil keluar."
}
```

Hanya token pada request ini yang dicabut; sesi di perangkat lain tetap aktif.
Untuk keluar dari semua perangkat, kirim `{"all_devices": true}`.

> Klien **wajib** juga menghapus `fcm_token` perangkat (§2.7) saat logout,
> supaya notifikasi tidak terkirim ke pengguna yang sudah keluar.

### 2.7 Registrasi FCM Token

```http
POST /auth/fcm-token
```

**Auth required** – Mendaftarkan perangkat untuk push notification.

**Body:**

```json
{
  "fcm_token": "dGhpcyBpcyBhIGZha2UgdG9rZW4...",
  "device_id": "a1b2c3d4-e5f6",
  "platform": "android"
}
```

| Field | Aturan | Keterangan |
| :-- | :-- | :-- |
| `fcm_token` | `required\|string\|max:255` | Token dari Firebase SDK |
| `device_id` | `required\|string\|max:100` | Pengenal perangkat, agar satu perangkat tidak menumpuk token |
| `platform` | `required\|in:android,ios` | |

Kirim ulang setiap kali Firebase memperbarui token (`onTokenRefresh`) dan
setelah login berhasil. Server melakukan *upsert* berdasarkan `device_id`.

**Response 200:**

```json
{
  "success": true,
  "message": "Token perangkat terdaftar."
}
```

```http
DELETE /auth/fcm-token
```

Menghapus token perangkat saat logout. Body: `{"device_id": "a1b2c3d4-e5f6"}`.

---

## 3. STORES

### 3.1 Create Store

```http
POST /stores
```

**Auth required** (verification_level ≥ 2)

**Body:**

```json
{
  "name": "Bengkel AC Yanto",
  "store_type": ["services"],
  "category_ids": [5, 6],
  "latitude": -6.2,
  "longitude": 106.85,
  "service_radius_km": 10.0,
  "operating_hours": {
    "senin":  { "open": "08:00", "close": "17:00" },
    "selasa": { "open": "08:00", "close": "17:00" },
    "rabu":   { "open": "08:00", "close": "17:00" },
    "kamis":  { "open": "08:00", "close": "17:00" },
    "jumat":  { "open": "08:00", "close": "11:30" },
    "sabtu":  { "open": "09:00", "close": "15:00" },
    "minggu": null
  },
  "bank_account": "BCA 1234567890 a/n Yanto" // opsional
}
```

#### Aturan `store_type`

**Selalu array**, meski hanya satu nilai. Nilai sah: `goods`, `services`,
`rental` — bisa dikombinasikan, mis. `["goods", "services"]`.

| Lapisan | Bentuk | Contoh |
| :-- | :-- | :-- |
| Request & response API | **array of string** | `["goods","services"]` |
| Kolom MySQL (`SET`) | string dipisah koma | `'goods,services'` |

Konversi ditangani model Laravel; klien **tidak pernah** melihat bentuk
comma-separated:

```php
protected function storeType(): Attribute
{
    return Attribute::make(
        get: fn ($v) => $v ? explode(',', $v) : [],
        set: fn ($v) => implode(',', (array) $v),
    );
}
```

> ⚠️ **Hati-hati `services` vs `service`.**
>
> | Field | Bentuk | Nilai | Alasan |
> | :-- | :-- | :-- | :-- |
> | `store_type` | **jamak** | `goods`, `services`, `rental` | Array — satu toko bisa beberapa jenis |
> | `listing_type` | tunggal | `product`, `service`, `rental` | Satu nilai per listing |
> | `order_type` | tunggal | `product`, `service`, `rental` | Disalin dari `listing_type` |
>
> Perbedaan ini **disengaja**, bukan kelalaian: `store_type` menggambarkan
> kumpulan, dua lainnya menggambarkan satu benda. `listing_type` dan
> `order_type` sengaja **identik** supaya nilainya bisa disalin langsung saat
> pesanan dibuat. Lihat `DATABASE.md` §4.2.

#### Aturan `operating_hours`

Objek dengan **tujuh kunci wajib**: `senin` … `minggu` (huruf kecil).
Nilainya berupa objek `{open, close}` format `HH:MM` 24 jam, atau **`null`**
jika tutup pada hari itu.

| Aturan | Keterangan |
| :-- | :-- |
| Kunci lengkap | Tujuh hari harus ada; hari yang hilang → 422 |
| `close` > `open` | Jam tutup harus setelah jam buka |
| Lewat tengah malam | Belum didukung MVP. `22:00`–`02:00` ditolak |
| Zona waktu | Selalu **WIB (UTC+7)**, jam dinding lokal toko |

**Response 201:**

```json
{
  "success": true,
  "data": {
    "store": {
      "id": "uuid",
      "name": "Bengkel AC Yanto",
      "store_type": ["services"],
      "category_ids": [5, 6],
      "service_radius_km": 10.0,
      "verification_status": "pending",
      "rating_avg": 0,
      "total_reviews": 0
    }
  }
}
```

Toko berstatus `pending` **tidak muncul** di pencarian sampai admin
menyetujuinya. Jika ditolak, alasannya tersedia di `rejected_reason`.

### 3.2 Get Nearby Stores

```http
GET /stores/nearby?lat=-6.200&lng=106.850&radius=10&type=services&category=5
```

**Public** – Pencarian toko dalam radius (km).

| Parameter | Wajib | Default | Keterangan |
| :-- | :-- | :-- | :-- |
| `lat` | ✅ | – | `-90`…`90` |
| `lng` | ✅ | – | `-180`…`180` |
| `radius` | – | `10` | Km, maks `25` |
| `type` | – | – | `goods`, `services`, `rental` (**jamak**, sama seperti `store_type`) |
| `category` | – | – | `category_id` |
| `search` | – | – | Teks pencarian nama toko |
| `page` | – | `1` | |
| `per_page` | – | `15` | Maks `50` |

**Response 200:**

```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "name": "Bengkel AC Yanto",
      "store_type": ["services"],
      "rating_avg": 4.5,
      "total_reviews": 28,
      "distance_km": 2.3
    }
  ],
  "meta": { "current_page": 1, "per_page": 15, "total": 42, "last_page": 3 }
}
```

> `store_type` dikembalikan sebagai **array**, konsisten dengan format saat
> membuat toko. Versi awal dokumen ini sempat menampilkannya sebagai string
> (`"services"`) — itu keliru dan sudah diperbaiki.

Hanya toko dengan `verification_status = verified` dan `is_active = true` yang
muncul. Hasil diurutkan dari yang terdekat.

### 3.3 Get Store Detail

```http
GET /stores/{store_id}
```

**Public** – Detail toko + daftar listings (maks 5 terbaru).

---

## 4. LISTINGS

### 4.0 Upload Gambar

```http
POST /uploads/images
```

**Auth required** – Mengunggah gambar **sebelum** membuat listing/permintaan.

Memisahkan unggahan dari penyimpanan data membuat pengguna bisa mengunggah foto
sambil masih mengisi formulir, dan kegagalan jaringan pada satu foto tidak
membatalkan seluruh isian.

**Body (multipart/form-data):**

| Field | Aturan | Keterangan |
| :-- | :-- | :-- |
| `file` | `required\|image\|mimes:jpeg,jpg,png,webp\|max:5120` | Satu berkas per request |
| `purpose` | `required\|in:listing,request,payment_proof` | Menentukan folder & masa simpan |

**Response 201:**

```json
{
  "success": true,
  "data": {
    "url": "https://cdn.seekitar.id/tmp/listing/9f8e7d.jpg",
    "expires_at": "2026-07-27T12:00:00Z"
  }
}
```

> ⚠️ URL bersifat **sementara (2 jam)**. Berkas menjadi permanen begitu URL-nya
> dipakai saat membuat listing/permintaan. Yang tidak terpakai dibersihkan
> otomatis oleh penjadwal.

### 4.1 Create Listing

```http
POST /listings
```

**Auth required** (pemilik toko, store verified)

**Body (application/json):** — kirim URL hasil §4.0, bukan berkas mentah.

| Field | Aturan validasi |
|-------|-----------------|
| `store_id` | `required\|uuid\|exists:stores,id` |
| `title` | `required\|string\|min:5\|max:200` |
| `description` | `required\|string\|max:5000` |
| `listing_type` | `required\|in:product,service,rental` (**tunggal**) |
| `price` | `nullable\|numeric\|min:0` — **wajib** jika `product`/`rental` |
| `stock_qty` | `required_if:listing_type,product,rental\|prohibited_unless:listing_type,product,rental\|integer\|min:0` |
| `slot` | `required_if:listing_type,service\|prohibited_unless:listing_type,service\|integer\|min:1` |
| `images` | `required\|array\|min:1\|max:5` |
| `images.*` | `url\|max:500` |

Aturan `price`, `stock_qty`, dan `slot` mencerminkan CHECK constraint di
database (lihat `DATABASE.md` §4.4) — jasa tidak boleh punya stok, dan produk
tidak boleh punya slot.

**Response 201:** (data listing)

**Error 422** – Contoh saat produk dikirim tanpa harga:

```json
{
  "success": false,
  "message": "Data yang dikirim tidak valid.",
  "errors": {
    "price": ["Harga wajib diisi untuk listing tipe product."]
  }
}
```

### 4.2 Search Listings

```http
GET /listings?lat=-6.200&lng=106.850&radius=15&category=5&type=product&sort=nearest&keyword=beras
```

**Public** – Pencarian katalog.

| Parameter | Wajib | Default | Keterangan |
| :-- | :-- | :-- | :-- |
| `lat` | ✅ | – | Wajib untuk menghitung `distance_km` |
| `lng` | ✅ | – | |
| `radius` | – | `25` | Km, maks `25` (batas admin, PRD §5.1) |
| `category` | – | – | `category_id` |
| `type` | – | – | `product`, `service`, `rental` (**tunggal**) |
| `keyword` | – | – | Pencarian FULLTEXT pada judul & deskripsi |
| `min_price`, `max_price` | – | – | `max_price` ≥ `min_price` |
| `min_rating` | – | – | `1`…`5`, difilter dari `stores.rating_avg` |
| `sort` | – | `nearest` | `nearest`, `cheapest`, `newest` |
| `page` | – | `1` | |
| `per_page` | – | `15` | Maks `50` |

> ⚠️ **`lat` & `lng` selalu wajib** — bukan hanya untuk `sort=nearest`.
> Seekitar bersifat *hyperlocal*: tanpa koordinat, filter radius tidak bisa
> dijalankan dan hasilnya tidak bermakna. Permintaan tanpa keduanya
> menghasilkan `422`, bukan menampilkan seluruh katalog.
>
> `sort=cheapest` mengabaikan listing ber-`price: null` (jasa nego) dengan
> menempatkannya di akhir, karena harga NULL tidak bisa dibandingkan.

**Response 200:**

```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "title": "Beras Organik 5kg",
      "price": 65000,
      "store": { "id": "...", "name": "...", "rating_avg": 4.8 },
      "distance_km": 1.2,
      "images": [...]
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 120
  }
}
```

### 4.3 Get Listing Detail

```http
GET /listings/{listing_id}
```

**Public** – Detail listing lengkap dengan informasi toko.

### 4.4 Update / Delete Listing

- `PUT /listings/{listing_id}` (pemilik toko)
- `DELETE /listings/{listing_id}` (soft delete)

### 4.5 Wishlist / Favorit

Mendukung aksi *"Masukkan ke Wishlist"* pada PRD §5.1.

```http
POST   /listings/{listing_id}/favorite
DELETE /listings/{listing_id}/favorite
```

**Auth required** – Menambah/menghapus listing dari wishlist pribadi.

`POST` bersifat **idempoten**: memfavoritkan listing yang sudah difavoritkan
tetap mengembalikan `200`, bukan `409`. Ini menyederhanakan tombol *toggle* di
klien yang mungkin mengirim ulang karena jaringan tidak stabil.

```http
GET /favorites?page=1&per_page=15
```

**Auth required** – Daftar listing yang difavoritkan, terbaru lebih dulu.

**Response 200:**

```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "title": "Beras Organik 5kg",
      "price": 65000,
      "is_available": true,
      "store": { "id": "uuid", "name": "Toko Tani Makmur" },
      "favorited_at": "2026-07-26T08:00:00Z"
    }
  ],
  "meta": { "current_page": 1, "per_page": 15, "total": 8, "last_page": 1 }
}
```

> Listing yang dihapus penjual **tetap muncul** dengan
> `is_available: false` alih-alih hilang diam-diam, supaya pengguna paham
> mengapa barang incarannya tidak bisa dipesan lagi.

Detail listing (§4.3) menyertakan `is_favorited` bagi pengguna terautentikasi.

---

## 5. CUSTOMER REQUESTS

### 5.1 Create Request

```http
POST /requests
```

**Auth required**

**Body:**

```json
{
  "title": "Butuh servis AC 1 PK",
  "description": "AC tidak dingin, perlu dicek freon",
  "category_id": 5,
  "budget_min": 100000,
  "budget_max": 200000,
  "latitude": -6.21,
  "longitude": 106.84,
  "radius_km": 10,
  "images": ["https://cdn.seekitar.id/tmp/request/9f8e7d.jpg"], // opsional, maks 3
  "required_date": "2026-07-28T03:00:00Z" // opsional
}
```

`radius_km` default **15** jika tidak dikirim. `budget_max` tidak boleh lebih
kecil dari `budget_min`. `images` berisi URL hasil `POST /uploads/images`
dengan `purpose=request` (maks 3 foto).

**Response 201:**

```json
{
  "success": true,
  "data": {
    "request": {
      "id": "uuid",
      "status": "open",
      "expires_at": "2026-07-28T03:00:00Z",
      "extension_count": 0
    }
  }
}
```

### 5.1a Perpanjang Masa Aktif Permintaan

```http
POST /requests/{request_id}/extend
```

**Auth required** (pemilik permintaan) – Memperpanjang `expires_at` 24 jam.

**Error 422** – Sudah mencapai batas 2 kali perpanjangan, atau permintaan
sudah `closed`/`expired`.

### 5.2 Get Open Requests (Penyedia)

```http
GET /requests?status=open&lat=-6.200&lng=106.850&radius=10&category=5
```

**Auth required** (penyedia toko) – Menampilkan permintaan terdekat yang cocok dengan kategori toko.

| Parameter | Wajib | Default | Keterangan |
| :-- | :-- | :-- | :-- |
| `status` | – | `open` | `open`, `closed`, `expired` |
| `lat`, `lng` | ✅ | – | Titik acuan penyedia |
| `radius` | – | `15` | Km |
| `category` | – | – | Default: seluruh kategori toko penyedia |
| `sort` | – | `newest` | `newest`, `nearest`, `expiring` |
| `page`, `per_page` | – | `1`, `15` | Lihat §12.2 |

`sort=expiring` mengurutkan berdasarkan `expires_at` terdekat — berguna bagi
penyedia yang ingin menyambar permintaan yang hampir kedaluwarsa.

**Response 200:** (daftar request, tanpa data pribadi pembeli)

> 🔒 Lokasi pembeli dibulatkan ke tingkat kelurahan dan **nomor telepon tidak
> disertakan** sampai penawaran diterima. Ini mencegah penyedia memanen data
> kontak dari papan kebutuhan.

### 5.3 Get My Requests (Pembeli)

```http
GET /requests/mine?status=open&page=1&per_page=15
```

**Auth required** – Daftar permintaan sendiri. Mendukung paginasi (§12.2).

### 5.4 Get Request Detail

```http
GET /requests/{request_id}
```

**Auth required** – Detail permintaan (jika pembeli, lihat penawaran; jika penyedia, lihat info terbatas).

---

## 6. OFFERS

### 6.1 Create Offer

```http
POST /requests/{request_id}/offers
```

**Auth required** (penyedia toko)

**Body:**

```json
{
  "price": 150000,
  "additional_cost": 15000,
  "additional_cost_note": "Ongkos antar 3 km",
  "estimation_time": "Bisa datang siang ini jam 2",
  "estimated_hours": 4,
  "notes": "Garansi 1 minggu"
}
```

`price` adalah nilai pekerjaan/barang **saja**; ongkos antar atau biaya
material masuk ke `additional_cost` (default `0`). Yang mengikat sebagai
`orders.total_amount` adalah **jumlah keduanya**.

> ⚠️ Memasukkan ongkos antar ke `price` membuat penyedia yang jujur kalah pada
> pengurutan "termurah" — karena itu pengurutan memakai
> `price + additional_cost`, bukan `price` saja.

`estimation_time` adalah teks yang dibaca pembeli; `estimated_hours` adalah
bentuk numeriknya untuk pengurutan "tercepat". Keduanya sebaiknya dikirim
bersamaan agar hasil sortir konsisten dengan yang ditampilkan.

**Response 201:**

```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "status": "pending",
    "expires_at": "2026-07-29T03:00:00Z"
  }
}
```

`expires_at` diisi server: 48 jam, atau mengikuti `expires_at` permintaan jika
permintaan tersebut berakhir lebih dulu.

#### Validasi harga terhadap budget

Jika permintaan mencantumkan budget, harga penawaran **divalidasi terhadap
rentang tersebut**:

| Kondisi | Hasil |
| :-- | :-- |
| `budget_min` & `budget_max` diisi | `price` harus berada di antaranya (inklusif) |
| Hanya `budget_max` | `price` ≤ `budget_max` |
| Hanya `budget_min` | `price` ≥ `budget_min` |
| Keduanya `null` | Bebas — pembeli tidak menetapkan anggaran |

```json
{
  "success": false,
  "message": "Harga penawaran di luar anggaran pembeli.",
  "errors": {
    "price": ["Harga harus antara Rp100.000 dan Rp200.000."]
  }
}
```

> Aturan ini disengaja **ketat** (menolak, bukan sekadar memperingatkan) agar
> pembeli tidak dibanjiri penawaran yang jelas di luar kemampuannya. Penyedia
> yang ingin menawar di luar rentang dapat menghubungi pembeli lewat WhatsApp
> setelah penawaran wajar diterima.

**Error:**

- `409` – Sudah mengirim penawaran untuk permintaan ini
- `403` – Tidak sesuai kategori/toko tidak aktif/belum terverifikasi
- `422` – Permintaan sudah `closed`/`expired`, atau harga di luar budget

### 6.2 Get Offers for a Request

```http
GET /requests/{request_id}/offers
```

**Auth required** (hanya pembeli pemilik request) – Lihat semua penawaran yang masuk.

**Response 200:**

```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "store": {
        "id": "...",
        "name": "Bengkel AC Yanto",
        "rating_avg": 4.5,
        "distance_km": 3.2
      },
      "price": 150000,
      "estimation_time": "...",
      "notes": "...",
      "created_at": "..."
    }
  ]
}
```

### 6.3 Accept Offer

```http
PATCH /offers/{offer_id}/accept
```

**Auth required** (hanya pembeli pemilik request)

**Response 200:**

```json
{
  "success": true,
  "data": {
    "order": {
      "id": "uuid",
      "status": "menunggu_konfirmasi"
    }
  }
}
```

**Efek:**

- Status permintaan → `closed`
- Offer lain otomatis `rejected`
- Order baru terbentuk, detail kontak pembeli & penyedia dibuka.

---

## 7. ORDERS

### 7.1 Create Direct Order (dari listing)

```http
POST /orders
```

**Auth required** (pembeli)

**Body:**

```json
{
  "listing_id": "uuid",
  "quantity": 1, // untuk product
  "payment_method": "cod",
  "delivery_method": "delivery",
  "shipping_address": "Jl. Melati No. 12, RT 03/RW 05, Sukolilo",
  "notes": "Pesan tambahan"
}
```

#### Sumber pesanan: `listing_id` **XOR** `offer_id`

Setiap pesanan lahir dari **tepat satu** sumber. Keduanya tidak boleh dikirim
bersamaan, dan tidak boleh kosong dua-duanya:

| Alur | Cara membuat | `listing_id` | `offer_id` |
| :-- | :-- | :-- | :-- |
| Beli dari katalog | `POST /orders` (endpoint ini) | ✅ wajib | ✗ dilarang |
| Menang lelang kebutuhan | `PATCH /offers/{id}/accept` — **otomatis** | ✗ dilarang | ✅ diisi server |

> ⚠️ Pesanan dari penawaran **tidak dibuat lewat endpoint ini**. Order
> terbentuk otomatis sebagai efek samping `PATCH /offers/{id}/accept` (§6.3),
> di dalam satu transaksi bersama penutupan permintaan. Mengirim `offer_id`
> ke `POST /orders` selalu menghasilkan `422`.

```php
// Aturan validasi
'listing_id' => ['required_without:offer_id', 'prohibits:offer_id', 'uuid', 'exists:listings,id'],
```

`shipping_address` **wajib** jika `delivery_method` = `delivery`, dan diabaikan
jika `pickup`.

**Response 201:**

```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "order_number": "SKT-20260727-0001",
    "status": "menunggu_konfirmasi",
    "delivery_method": "delivery",
    "total_amount": 150000
  },
  "message": "Pesanan dibuat."
}
```

> `order_number` adalah referensi yang ditampilkan ke pengguna dan dipakai saat
> menghubungi penjual via WhatsApp. `id` (UUID) tetap dipakai untuk semua
> pemanggilan API berikutnya.

### 7.1a Upload Bukti Transfer

```http
POST /orders/{order_id}/payment-proof
```

**Auth required** (pembeli) – Hanya untuk `payment_method` = `transfer`.

**Body (multipart/form-data):**

| Field | Tipe | Keterangan |
|-------|------|------------|
| `payment_proof` | file | Foto bukti transfer (jpg/png, maks 5 MB) |

Setelah diunggah, penjual harus mengonfirmasi penerimaan dana secara manual.
Platform **tidak** memverifikasi mutasi bank, sehingga status pesanan tidak
berubah otomatis.

### 7.2 Update Order Status

```http
PATCH /orders/{order_id}/status
```

**Auth required** – Transisi divalidasi state machine; hanya pihak berwenang.

**Body:**

```json
{
  "status": "diproses"
}
```

Saat membatalkan, sertakan alasannya:

```json
{
  "status": "dibatalkan",
  "cancel_reason": "Stok habis"
}
```

#### Diagram State

```
                    ┌─────────────────────┐
                    │ menunggu_konfirmasi │  (status awal)
                    └──────────┬──────────┘
              penjual terima   │   penjual/pembeli batalkan
                   ┌───────────┴───────────┐
                   ▼                       ▼
             ┌──────────┐            ┌────────────┐
             │ diproses │            │ dibatalkan │ ● final
             └────┬─────┘            └────────────┘
    penjual kirim │
                  ▼
            ┌──────────┐
            │ dikirim  │
            └────┬─────┘
  pembeli terima │
                 ▼
            ┌──────────┐
            │ selesai  │ ● final
            └──────────┘

  Dari diproses / dikirim, kedua pihak dapat memicu ─────▶ ┌─────────┐
  sengketa lewat POST /orders/{id}/disputes                │ dispute │
                                                            └─────────┘
```

#### Transisi Sah & Siapa yang Berwenang

| Dari | Ke | Pelaku | Catatan |
| :-- | :-- | :-- | :-- |
| `menunggu_konfirmasi` | `diproses` | **Penjual** | Menerima pesanan |
| `menunggu_konfirmasi` | `dibatalkan` | Penjual / Pembeli | Pembeli bebas batal di tahap ini |
| `diproses` | `dikirim` | **Penjual** | Dikirim atau siap diambil |
| `diproses` | `dibatalkan` | **Penjual** | Pembeli **tidak** bisa batal (PRD §5.4.1) |
| `dikirim` | `selesai` | **Pembeli** | Konfirmasi barang/jasa diterima |
| `diproses` / `dikirim` | `dispute` | Penjual / Pembeli | Lewat §9.1, bukan endpoint ini |

Transisi selain tabel di atas menghasilkan `409 Conflict`. `selesai` dan
`dibatalkan` bersifat **final** — tidak ada jalan kembali.

**Efek samping otomatis:**

- → `selesai` : `completed_at` diisi; ulasan terbuka selama **7 hari** (PRD §5.4.1).
- → `dibatalkan` : `cancelled_at`, `cancelled_by`, `cancel_reason` diisi.
- Setiap transisi memicu notifikasi FCM ke pihak lawan.

**Response 200:**

```json
{
  "success": true,
  "data": {
    "order": {
      "id": "uuid",
      "order_number": "SKT-20260727-0001",
      "status": "diproses",
      "updated_at": "2026-07-27T10:05:00Z"
    }
  }
}
```

**Error 409** – Transisi tidak sah:

```json
{
  "success": false,
  "message": "Pesanan tidak dapat diubah dari 'selesai' ke 'diproses'.",
  "errors": null
}
```

**Error 403** – Pihak yang tidak berwenang, mis. pembeli mencoba menandai
`dikirim`.

> ⚠️ **Alur jasa belum sepenuhnya terwakili.** PRD §5.4.2 menyebut status
> `Dijadwalkan`, `Dalam Pengerjaan`, dan `Menunggu Konfirmasi Pembeli` yang
> **tidak ada** di ENUM `orders.status`. Sampai keputusan diambil (lihat
> `Server_Implementation_Guide.md` §4), ketiganya dipetakan ke `diproses`.

### 7.3 Get Order Detail

```http
GET /orders/{order_id}
```

**Auth required** – Hanya pembeli/penjual terkait.

### 7.4 Get My Orders

```http
GET /orders?as=buyer&status=selesai
```

**Auth required** – Riwayat pesanan sebagai pembeli atau penjual.

---

## 8. REVIEWS

### 8.1 Create Review

```http
POST /orders/{order_id}/review
```

**Auth required** – Prasyarat diperiksa berurutan:

| # | Syarat | Gagal → |
| :-- | :-- | :-- |
| 1 | Pemanggil adalah pembeli **atau** pemilik toko pada pesanan itu | `403` |
| 2 | `status` pesanan **`selesai`** | `409` |
| 3 | Belum lewat **7 hari** sejak `completed_at` (PRD §5.4.1) | `422` |
| 4 | Pihak ini belum pernah mengulas pesanan tersebut | `409` |

Ulasan **tidak dapat diubah atau dihapus** setelah dikirim (PRD §5.5).

Ulasan bersifat **dua arah** (PRD §5.5): pembeli menilai toko, penjual menilai
pembeli. `direction` **tidak dikirim klien** — server menyimpulkannya dari
identitas pemanggil:

| Pemanggil        | `direction` tersimpan | Memengaruhi rating toko? |
| :--------------- | :-------------------- | :------------------------ |
| Pembeli order    | `buyer_to_store`      | ✅ Ya                     |
| Pemilik toko     | `store_to_buyer`      | ❌ Tidak                  |

**Body:**

```json
{
  "rating": 5,
  "comment": "Bagus banget, cepat dan rapi."
}
```

**Response 201:**

```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "order_id": "uuid",
    "direction": "buyer_to_store",
    "store_id": "uuid",
    "rating": 5,
    "comment": "Bagus banget, cepat dan rapi.",
    "created_at": "2026-07-27T10:00:00Z"
  },
  "message": "Ulasan tersimpan."
}
```

**Error 409** – Pihak yang sama sudah pernah mengulas pesanan ini. Setiap pihak
hanya boleh satu kali; ulasan tidak bisa diubah setelah dikirim.

### 8.2 Get Reviews for Store

```http
GET /stores/{store_id}/reviews
```

**Public** – Ulasan terbaru untuk toko tertentu.

Hanya mengembalikan ulasan `buyer_to_store`. Penilaian penjual terhadap pembeli
tidak pernah tampil di profil publik toko.

---

## 9. DISPUTES

### 9.1 Create Dispute

```http
POST /orders/{order_id}/disputes
```

**Auth required** – Pembeli atau penjual bisa melaporkan.

**Body:**

```json
{
  "reason": "barang_tidak_sesuai",
  "description": "Barang yang datang berbeda warna"
}
```

`reason` enum: `barang_tidak_sesuai`, `jasa_tidak_profesional`, `penyedia_tidak_responsif`, `pembeli_fiktif`, `lainnya`.

`description` **wajib** jika `reason` = `lainnya` (ditegakkan CHECK constraint
di database).

**Prasyarat:** pesanan berstatus `diproses` atau `dikirim`. Pesanan yang sudah
`selesai` atau `dibatalkan` tidak bisa disengketakan (`409`).

**Efek samping — status pesanan ikut berubah:**

```
orders.status  →  'dispute'      (dikunci sampai admin menyelesaikan)
disputes.status →  'open'
```

Selama berstatus `dispute`, **semua transisi status pesanan diblokir**. Hanya
admin yang dapat membukanya lewat `PATCH /admin/disputes/{id}/resolve`, yang
mengembalikan pesanan ke status sebelumnya atau langsung ke `dibatalkan`.

Admin wajib menanggapi dalam **1×24 jam** (PRD §5.5).

**Response 201:**

```json
{
  "success": true,
  "data": {
    "dispute": {
      "id": "uuid",
      "order_id": "uuid",
      "reason": "barang_tidak_sesuai",
      "status": "open",
      "created_at": "2026-07-27T10:00:00Z"
    },
    "order_status": "dispute"
  },
  "message": "Laporan diterima. Admin akan meninjau dalam 1x24 jam."
}
```

### 9.2 Get Disputes (Admin)

`GET /api/admin/disputes` – Lihat seksi Admin.

---

## 10. ADMIN ENDPOINTS

Prefix: `/admin` dengan guard `admin` (menggunakan Laravel Gates atau Sanctum ability).

### 10.1 Verifications

```http
GET /admin/verifications/pending
```

**Admin only** – Daftar user/stores yang menunggu verifikasi.

```http
POST /admin/verifications/users/{user_id}/approve
POST /admin/verifications/users/{user_id}/reject
POST /admin/verifications/stores/{store_id}/approve
POST /admin/verifications/stores/{store_id}/reject
```

### 10.2 Disputes

```http
GET /admin/disputes
```

Filter: `status=open`

```http
PATCH /admin/disputes/{dispute_id}/resolve
```

Body: `{"resolution_note": "..."}`

### 10.3 Categories Management

```http
GET /admin/categories
POST /admin/categories
PUT /admin/categories/{id}
DELETE /admin/categories/{id}
```

> ⚠️ `DELETE` gagal dengan `409` jika kategori masih dipakai — baik oleh
> `customer_requests` (dijaga FK `RESTRICT`) maupun oleh `stores.category_ids`
> yang berbentuk JSON dan **tidak** terlindungi FK, sehingga diperiksa aplikasi.
> Kategori induk yang masih punya anak juga ditolak (lihat `DATABASE.md` §4.3).

### 10.4 Manajemen Resource

Seluruh resource memakai pola yang sama, dengan paginasi §12.2 dan guard
`role:admin`.

| Resource | Endpoint | Aksi tersedia |
| :-- | :-- | :-- |
| Users | `GET /admin/users`<br>`GET /admin/users/{id}` | Filter: `verification_level`, `is_blocked`, `search` |
| | `PATCH /admin/users/{id}/block`<br>`PATCH /admin/users/{id}/unblock` | Blokir pengguna (PRD §4) |
| Stores | `GET /admin/stores`<br>`GET /admin/stores/{id}` | Filter: `verification_status`, `is_active` |
| | `PATCH /admin/stores/{id}/deactivate` | Nonaktifkan toko bermasalah |
| Listings | `GET /admin/listings`<br>`DELETE /admin/listings/{id}` | Filter: `status`, `store_id`, `type` |
| Requests | `GET /admin/requests` | Filter: `status`, `category_id` |
| Offers | `GET /admin/offers` | Filter: `status`, `request_id` |
| Orders | `GET /admin/orders`<br>`GET /admin/orders/{id}` | Filter: `status`, `payment_method`, rentang tanggal |
| Reviews | `GET /admin/reviews`<br>`DELETE /admin/reviews/{id}` | Filter: `rating`, `flagged` |

**Memblokir pengguna:**

```http
PATCH /admin/users/{user_id}/block
```

Body: `{"reason": "Terindikasi penipuan berulang"}`

Semua token pengguna dicabut seketika. Permintaan berikutnya dari pengguna itu
mendapat **`423 Locked`** (§11). Toko miliknya otomatis dinonaktifkan dan
listing-nya hilang dari pencarian, tetapi pesanan yang sedang berjalan tetap
utuh agar pihak lawan tidak dirugikan.

> ⚠️ Ulasan yang dihapus admin memicu perhitungan ulang `rating_avg` dan
> `total_reviews` toko terkait.

### 10.5 Pengaturan Sistem

```http
GET  /admin/settings
POST /admin/settings
```

**Hanya `super-admin`** — admin biasa mendapat `403`.

```json
{
  "max_search_radius_km": 25,
  "default_request_radius_km": 15,
  "request_expiry_hours": 24,
  "offer_expiry_hours": 48,
  "max_request_extensions": 2,
  "review_window_days": 7,
  "ktp_review_sla_hours": 24,
  "dispute_sla_hours": 24
}
```

Nilai-nilai ini mengendalikan perilaku yang di dokumen lain tampil sebagai
angka tetap. Mengubahnya berlaku untuk data **baru**; misalnya menurunkan
`request_expiry_hours` tidak memperpendek permintaan yang sudah berjalan.

**Response 200:** objek pengaturan setelah diperbarui.

**Error 422** – Nilai di luar batas aman, mis. `max_search_radius_km > 50`.

---

## 11. STATUS KODE & ERROR HANDLING

| Kode | Penjelasan                                     | Kapan dipakai |
| ---- | ---------------------------------------------- | :------------ |
| 200  | Sukses                                         | |
| 201  | Data berhasil dibuat                           | |
| 204  | Sukses tanpa konten (contoh: delete)           | |
| 400  | Bad Request (parameter tidak valid)            | JSON rusak, tipe salah |
| 401  | Unauthorized (token tidak ada/kadaluarsa)      | Klien harus mengulang alur OTP |
| 403  | Forbidden (tidak memiliki izin)                | Terautentikasi, tapi bukan haknya |
| 404  | Data tidak ditemukan                           | |
| 409  | Conflict (duplikasi, transisi status tidak sah) | Lihat contoh di bawah |
| 422  | Validasi gagal (form request)                  | Selalu menyertakan `errors` |
| 423  | Locked — akun dibekukan admin                  | Lihat contoh di bawah |
| 429  | Terlalu banyak request                         | Sertai `Retry-After` |
| 500  | Server error                                   | Jangan bocorkan detail internal |

Setiap error (4xx/5xx) memiliki body:

```json
{
  "success": false,
  "message": "Pesan error",
  "errors": { ... }
}
```

### 401 vs 403 vs 423

Ketiganya sering tertukar. Bedanya:

| Kode | Arti | Tindakan klien |
| :-- | :-- | :-- |
| `401` | Belum/tidak lagi terautentikasi | Arahkan ke layar login |
| `403` | Terautentikasi, tapi tidak berhak | Tampilkan pesan, **jangan** logout |
| `423` | Akun dibekukan | Logout paksa + tampilkan alasan & kontak |

### Contoh Body 409 Conflict

Duplikasi penawaran:

```json
{
  "success": false,
  "message": "Anda sudah mengirim penawaran untuk permintaan ini.",
  "errors": null
}
```

Transisi status tidak sah:

```json
{
  "success": false,
  "message": "Pesanan tidak dapat diubah dari 'selesai' ke 'diproses'.",
  "errors": null
}
```

Ulasan ganda:

```json
{
  "success": false,
  "message": "Anda sudah memberi ulasan untuk pesanan ini.",
  "errors": null
}
```

### Contoh Body 422 Validation Error

`errors` memuat **semua** field yang gagal sekaligus — bukan satu per satu.
Klien menandai seluruh field bermasalah dalam satu kali render:

```json
{
  "success": false,
  "message": "Data yang dikirim tidak valid.",
  "errors": {
    "phone": ["Format nomor tidak valid."],
    "otp": ["Kode OTP harus 6 digit."]
  }
}
```

Satu field bisa punya lebih dari satu pesan:

```json
{
  "success": false,
  "message": "Data yang dikirim tidak valid.",
  "errors": {
    "price": [
      "Harga wajib diisi untuk listing tipe product.",
      "Harga tidak boleh kurang dari 0."
    ],
    "images": ["Minimal 1 foto wajib diunggah."]
  }
}
```

Field bersarang memakai notasi titik, sesuai aturan Laravel:

```json
{
  "errors": {
    "operating_hours.senin.close": ["Jam tutup harus setelah jam buka."],
    "images.2": ["Berkas ketiga bukan gambar yang valid."]
  }
}
```

> ⚠️ **Kunci `errors` selalu berupa objek berisi array**, bahkan untuk satu
> pesan. Klien yang mengasumsikan string akan gagal mem-parsing — lihat
> `mapDioException` di `Mobile_Implementation_Guide.md` §5.3.
>
> Indeks array dimulai dari **0** (`images.2` = berkas ketiga). Saat
> menampilkannya ke pengguna, tambahkan 1 agar tidak membingungkan.

### Contoh Body 423 Locked

```json
{
  "success": false,
  "message": "Akun Anda dibekukan karena terindikasi penipuan berulang. Hubungi support@seekitar.id.",
  "errors": {
    "blocked_at": "2026-07-25T09:00:00Z",
    "reason": "Terindikasi penipuan berulang"
  }
}
```

Semua token dicabut saat pemblokiran, jadi `423` hanya muncul pada percobaan
login berikutnya (`POST /auth/verify-otp`). Klien harus membedakannya dari
`401`: `423` **tidak bisa** dipulihkan dengan login ulang.

`errors` berisi detail validasi (jika 422) atau null.

---

## 12. KONVENSI GLOBAL

### 12.1 Format Tanggal & Waktu

Semua timestamp — dikirim maupun diterima — memakai **ISO 8601 dalam UTC**
dengan akhiran `Z`:

```
2026-07-28T03:00:00Z        ✅ benar
2026-07-28T10:00:00+07:00   ❌ jangan dipakai lagi
2026-07-28 10:00:00         ❌ ambigu, tanpa zona waktu
```

Server selalu menyimpan dan mengembalikan UTC. **Konversi ke WIB dilakukan di
klien**, sehingga pengguna yang bepergian ke zona waktu lain tetap melihat
waktu yang konsisten. Satu-satunya pengecualian adalah `operating_hours` pada
toko, yang berupa jam dinding lokal (`"08:00"`) tanpa tanggal — lihat §3.1.

```dart
// Flutter: parse UTC lalu tampilkan waktu lokal
final expiresAt = DateTime.parse(json['expires_at']).toLocal();
```

### 12.2 Paginasi

Semua endpoint yang mengembalikan daftar memakai paginasi yang sama:

| Parameter | Default | Maks | Keterangan |
| :-- | :-- | :-- | :-- |
| `page` | `1` | – | Nomor halaman, mulai dari 1 |
| `per_page` | `15` | `50` | Melebihi 50 akan dibatasi jadi 50 |

Respons menyertakan objek `meta`:

```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 120,
    "last_page": 8
  }
}
```

Berlaku untuk: `GET /stores/nearby`, `/listings`, `/requests`,
`/requests/mine`, `/requests/{id}/offers`, `/orders`, `/stores/{id}/reviews`,
`/favorites`, dan seluruh endpoint daftar di `/admin`.

### 12.3 Satuan & Tipe

| Konsep | Satuan | Contoh |
| :-- | :-- | :-- |
| Jarak | kilometer, desimal | `distance_km: 2.3` |
| Radius | kilometer, integer/desimal | `radius_km: 15` |
| Uang | Rupiah, **tanpa** desimal | `price: 150000` |
| Durasi token | detik | `expires_in: 2592000` |
| Ukuran berkas | kilobyte (aturan validasi) | `max:5120` = 5 MB |
| Koordinat | derajat desimal, WGS 84 | `-6.211`, `106.845` |

Koordinat dalam response berbentuk GeoJSON `[longitude, latitude]` — perhatikan
urutannya terbalik dari kebiasaan menulis "lat, lng".

---

## 13. KOLEKSI API (POSTMAN / OPENAPI)

Dokumen ini adalah acuan kontrak; untuk mencoba endpoint secara langsung
tersedia koleksi yang dapat diimpor.

| Berkas | Format | Status |
| :-- | :-- | :-- |
| `docs/api/seekitar.postman_collection.json` | Postman v2.1 | ⏳ Dibuat saat endpoint pertama selesai |
| `docs/api/openapi.yaml` | OpenAPI 3.1 | ⏳ Dibangkitkan dari kode |

### Membangkitkan, bukan menulis manual

Koleksi yang ditulis tangan akan cepat basi begitu endpoint berubah. Hasilkan
dari kode agar selalu sinkron:

```bash
composer require --dev dedoc/scramble
php artisan scramble:export      # -> openapi.yaml dari Form Request & Resource
```

OpenAPI dapat diimpor langsung ke Postman, Insomnia, maupun Bruno, sehingga
cukup satu berkas untuk semua perkakas.

### Variabel Koleksi

Jangan menyematkan URL atau token di dalam setiap request:

| Variabel | Nilai contoh | Keterangan |
| :-- | :-- | :-- |
| `{{base_url}}` | `http://10.0.2.2:8000/api/v1` | Ganti per environment (§12) |
| `{{token}}` | — | Diisi otomatis oleh skrip di bawah |

```js
// Tab "Tests" pada request POST /auth/verify-otp
const data = pm.response.json().data;
if (data?.token) {
    pm.collectionVariables.set("token", data.token);
}
```

Dengan skrip itu, seluruh request lain cukup memakai
`Authorization: Bearer {{token}}` tanpa menyalin token secara manual.

> ⚠️ **Jangan commit koleksi yang masih memuat token asli.** Simpan token di
> *collection variable* bertipe `secret`, dan gunakan environment terpisah
> untuk produksi.
>
> Endpoint yang tidak bisa diuji lewat Postman: unggah berkas multipart lebih
> mudah dicoba dari aplikasi, dan alur OTP memerlukan WhatsApp sungguhan —
> pakai nomor uji di staging (`Mobile_Implementation_Guide.md` §17.2).
