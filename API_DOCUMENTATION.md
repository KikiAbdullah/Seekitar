# 📘 API DOCUMENTATION – SEEKITAR

**Versi:** 1.0 (Production‑Ready)  
**Base URL:** `https://api.seekitar.id/api/v1`  
**Tanggal Publikasi:** 27 Juli 2026

## DAFTAR ISI

1. [Autentikasi & Keamanan](#1-autentikasi--keamanan)
2. [Users](#2-users)
3. [Stores](#3-stores)
4. [Listings](#4-listings)
5. [Customer Requests](#5-customer-requests)
6. [Offers](#6-offers)
7. [Orders](#7-orders)
8. [Reviews](#8-reviews)
9. [Disputes](#9-disputes)
10. [Admin Endpoints](#10-admin-endpoints)
11. [Status Kode & Error Handling](#11-status-kode--error-handling)

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

**Error:**

- `401` – OTP salah atau kadaluarsa
- `422` – Validasi

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
| Field | Tipe | Keterangan |
|-------|------|------------|
| name | string | Nama lengkap |
| avatar | file (image) | Opsional, maks 2 MB |
| latitude | numeric | |
| longitude | numeric | |

**Response 200:** (data user yang sudah diperbarui)

### 2.5 Verification – Upload KTP

```http
POST /auth/verification/ktp
```

**Auth required** (verification_level < 2)

**Body (multipart/form-data):**
| Field | Tipe | Keterangan |
|-------|------|------------|
| ktp_image | file | Foto KTP |
| selfie_image | file | Selfie dengan KTP |

**Response 200:**

```json
{
  "success": true,
  "message": "Verifikasi KTP sedang ditinjau oleh admin."
}
```

Verification_level akan dinaikkan ke 2 setelah disetujui admin.

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
    "senin": { "open": "08:00", "close": "17:00" },
    "selasa": { "open": "08:00", "close": "17:00" }
  },
  "bank_account": "BCA 1234567890 a/n Yanto" // opsional
}
```

**Response 201:**

```json
{
  "success": true,
  "data": {
    "store": {
      "id": "uuid",
      "name": "Bengkel AC Yanto",
      "verification_status": "pending",
      ...
    }
  }
}
```

### 3.2 Get Nearby Stores

```http
GET /stores/nearby?lat=-6.200&lng=106.850&radius=10&type=services&category=5
```

**Public** – Pencarian toko dalam radius (km). Parameter:

- `lat`, `lng` (required)
- `radius` (default 10)
- `type` (goods, services, rental)
- `category` (category_id)
- `search` (teks)

**Response 200:**

```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "name": "Bengkel AC Yanto",
      "store_type": "services",
      "rating_avg": 4.5,
      "distance_km": 2.3,
      ...
    }
  ]
}
```

### 3.3 Get Store Detail

```http
GET /stores/{store_id}
```

**Public** – Detail toko + daftar listings (maks 5 terbaru).

---

## 4. LISTINGS

### 4.1 Create Listing

```http
POST /listings
```

**Auth required** (pemilik toko, store verified)

**Body (multipart/form-data):**
| Field | Tipe | Keterangan |
|-------|------|------------|
| store_id | uuid | |
| title | string | |
| description | string | |
| listing_type | string | product / service / rental |
| price | numeric | (nullable untuk service) |
| stock_qty | integer | (untuk product/rental) |
| slot | integer | (untuk service) |
| images[] | file | Maks 5 gambar |

**Response 201:** (data listing)

### 4.2 Search Listings

```http
GET /listings?lat=-6.200&lng=106.850&radius=15&category=5&type=product&sort=nearest&keyword=beras
```

**Public** – Pencarian katalog. Parameter:

- `lat`, `lng` (required)
- `radius` (default 25 km)
- `category`
- `type`
- `keyword`
- `min_price`, `max_price`
- `min_rating`
- `sort` = nearest / cheapest / newest

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
  "required_date": "2026-07-28T10:00:00+07:00" // opsional
}
```

**Response 201:**

```json
{
  "success": true,
  "data": {
    "request": {
      "id": "uuid",
      "status": "open",
      "expires_at": "2026-07-28T10:00:00+07:00"
    }
  }
}
```

### 5.2 Get Open Requests (Penyedia)

```http
GET /requests?status=open&lat=-6.200&lng=106.850&radius=10&category=5
```

**Auth required** (penyedia toko) – Menampilkan permintaan terdekat yang cocok dengan kategori toko.

**Response 200:** (daftar request, tanpa data pribadi pembeli)

### 5.3 Get My Requests (Pembeli)

```http
GET /requests/mine?status=open
```

**Auth required** – Daftar permintaan sendiri.

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
  "estimation_time": "Bisa datang siang ini jam 2",
  "notes": "Garansi 1 minggu"
}
```

**Response 201:** (data offer)  
**Error:**

- `409` – Sudah mengirim penawaran untuk permintaan ini
- `403` – Tidak sesuai kategori/toko tidak aktif

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
  "notes": "Pesan tambahan"
}
```

**Response 201:** (data order)

### 7.2 Update Order Status

```http
PATCH /orders/{order_id}/status
```

**Auth required** – Sesuai state machine. Hanya pihak yang berwenang (penjual/pembeli).

**Body:**

```json
{
  "status": "diproses"
}
```

**Response 200:**

```json
{
  "success": true,
  "data": {
    "order": { ... }
  }
}
```

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

**Auth required** – Hanya setelah order `selesai` dan belum pernah diulas.

**Body:**

```json
{
  "rating": 5,
  "comment": "Bagus banget, cepat dan rapi."
}
```

**Response 201:** (data review)

### 8.2 Get Reviews for Store

```http
GET /stores/{store_id}/reviews
```

**Public** – Ulasan terbaru untuk toko tertentu.

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

**Response 201:** (data dispute)

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

---

## 11. STATUS KODE & ERROR HANDLING

| Kode | Penjelasan                                     |
| ---- | ---------------------------------------------- |
| 200  | Sukses                                         |
| 201  | Data berhasil dibuat                           |
| 204  | Sukses tanpa konten (contoh: delete)           |
| 400  | Bad Request (parameter tidak valid)            |
| 401  | Unauthorized (token tidak ada/kadaluarsa)      |
| 403  | Forbidden (tidak memiliki izin)                |
| 404  | Data tidak ditemukan                           |
| 409  | Conflict (duplikasi offer, status tidak valid) |
| 422  | Validasi gagal (form request)                  |
| 429  | Terlalu banyak request                         |
| 500  | Server error                                   |

Setiap error (4xx/5xx) memiliki body:

```json
{
  "success": false,
  "message": "Pesan error",
  "errors": { ... }
}
```

`errors` berisi detail validasi (jika 422) atau null.
