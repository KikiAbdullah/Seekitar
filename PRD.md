# 📄 PRODUCT REQUIREMENTS DOCUMENT (PRD) – PRODUCTION READY

**Seekitar**  
**Platform Marketplace Hyperlocal Dua Arah Berbasis Geolokasi**  
_“Yang kamu butuhkan, ada di sekitar.”_

---

| **Informasi Dokumen** |                                                                           |
| :-------------------- | :------------------------------------------------------------------------ |
| **Nama Produk**       | Seekitar                                                                  |
| **Platform**          | Mobile App (Flutter) & Web App (Laravel 13 + Bootstrap 5.3.x) + Admin Dashboard |
| **Versi Dokumen**     | 2.1 (Production Ready – MySQL)                                            |
| **Tanggal**           | 27 Juli 2026                                                              |
| **Penulis**           | Tim Pengembang Seekitar                                                   |
| **Status**            | Final – Siap Implementasi                                                 |

---

## DAFTAR ISI

1. [Ringkasan Eksekutif](#1-ringkasan-eksekutif)
2. [Latar Belakang, Masalah & Tujuan](#2-latar-belakang-masalah--tujuan)
3. [Target Pengguna & Persona](#3-target-pengguna--persona)
4. [Lingkup Produk (MVP vs Rilis Berikutnya)](#4-lingkup-produk-mvp-vs-rilis-berikutnya)
5. [Fitur Inti & Kebutuhan Fungsional Rinci](#5-fitur-inti--kebutuhan-fungsional-rinci)
   - 5.1 Marketplace Katalog (Barang, Jasa, Sewa)
   - 5.2 Papan Kebutuhan (Reverse Marketplace)
   - 5.3 Akun & Manajemen Toko
   - 5.4 Manajemen Pesanan & State Machine
   - 5.5 Sistem Kepercayaan & Ulasan
   - 5.6 Komunikasi Pembeli-Penjual
6. [Alur Pengguna (User Flow) Terperinci](#6-alur-pengguna-user-flow-terperinci)
7. [Arsitektur Sistem & Teknologi](#7-arsitektur-sistem--teknologi)
8. [Model Data & Skema Database](#8-model-data--skema-database)
9. [Spesifikasi API & Layanan Backend](#9-spesifikasi-api--layanan-backend)
10. [Notifikasi & Komunikasi Sistem](#10-notifikasi--komunikasi-sistem)
11. [Keamanan & Kepatuhan Regulasi](#11-keamanan--kepatuhan-regulasi)
12. [Model Bisnis & Monetisasi](#12-model-bisnis--monetisasi)
13. [Metrik Keberhasilan & KPI](#13-metrik-keberhasilan--kpi)
14. [Roadmap & Tahapan Proyek](#14-roadmap--tahapan-proyek)
15. [Asumsi, Risiko & Dependensi](#15-asumsi-risiko--dependensi)
16. [Lampiran: Wireframe Kunci & Referensi](#16-lampiran-wireframe-kunci--referensi)

---

## 1. RINGKASAN EKSEKUTIF

**Seekitar** adalah platform _hyperlocal two-way marketplace_ yang secara geografis dikunci dalam satu wilayah kabupaten. Platform ini menggabungkan dua model transaksi:

- **Marketplace Katalog (Jelajahi)** – penjual memajang produk/jasa, pembeli mencari dan memesan.
- **Papan Kebutuhan / Reverse Marketplace (Pasang Kebutuhan)** – pembeli mengajukan kebutuhan spesifik, penyedia lokal memberikan penawaran kompetitif.

Dengan memanfaatkan teknologi geolokasi akurat (MySQL Spatial), platform ini memastikan setiap transaksi hanya melibatkan pihak-pihak yang berada dalam radius layanan. Target utama adalah UMKM, penyedia jasa informal, dan warga yang menginginkan kemudahan, kecepatan, dan kepercayaan transaksi lokal.

**Nilai Unik (Unique Value Proposition):**

- **Beli & Butuh, Semua Ada di Satu Genggaman** – Tidak perlu beralih antara aplikasi nasional dan grup WhatsApp.
- **Jangkauan Tepat, Ongkos Minim** – Hanya penjual/penyedia di sekitar Anda yang muncul.
- **Dari “Menunggu Pembeli” Menjadi “Menjemput Kebutuhan”** – Penyedia jasa bisa proaktif menawarkan solusi ke calon pelanggan yang sudah jelas lokasi dan kebutuhannya.

---

## 2. LATAR BELAKANG, MASALAH & TUJUAN

### 2.1 Masalah Mendasar

1. **Ekonomi Lokal Terfragmentasi:** Informasi tentang penyedia jasa, stok barang toko kecil, dan peluang sewa alat masih tersebar di grup Facebook, broadcast WhatsApp, dan papan pengumuman fisik.
2. **Marketplace Nasional Tidak Efisien untuk Kebutuhan Harian:** Barang berat (galon, beras karung), jasa dadakan (tukang ledeng, potong rambut panggilan), dan sewa (tenda, sound system) sulit difasilitasi karena ongkir tinggi dan ketiadaan opsi kurir instan lintas desa.
3. **Penyedia Lokal Pasif:** 80% pelaku UMKM dan penyedia jasa hanya mengandalkan pelanggan walk-in. Mereka tidak memiliki saluran digital untuk “menemukan” permintaan di sekitar.
4. **Kepercayaan Terbatas:** Transaksi langsung antar individu tanpa sistem reputasi sering menimbulkan kekhawatiran kualitas dan keamanan.

### 2.2 Tujuan Produk

| Tujuan                          | Deskripsi                                                                                   | Ukuran Keberhasilan (Target 3 Bulan)                                           |
| :------------------------------ | :------------------------------------------------------------------------------------------ | :----------------------------------------------------------------------------- |
| **Aktivasi Ekosistem Dua Arah** | Memastikan baik pembeli maupun penjual aktif menggunakan fitur permintaan dan penawaran     | 500 permintaan terpasang, >70% mendapat minimal 1 penawaran                    |
| **Akuisisi Awal**               | Mendapatkan basis pengguna yang beragam (pembeli, penjual, penyedia jasa)                   | 500 pengguna terdaftar, 150 penyedia jasa/toko terverifikasi                   |
| **Membuktikan Transaksi Lokal** | Menunjukkan bahwa transaksi COD/transfer langsung dapat terlacak dan menghasilkan ulasan    | GMV ≥ Rp 50.000.000, 200 pesanan sukses                                        |
| **Kepatuhan & Kepercayaan**     | Platform beroperasi sesuai regulasi PSE dan UU PDP, dengan sistem verifikasi yang berfungsi | 0 insiden pelanggaran data, 100% penyedia jasa di kabupaten target tervalidasi |

#### Asumsi di Balik Target 3 Bulan

Target di atas hanya bermakna bila asumsinya dinyatakan terbuka — jika salah
satu meleset, targetnya harus direvisi, bukan dipaksakan.

| Asumsi | Nilai | Dasar |
| :-- | :-- | :-- |
| Populasi kabupaten target | 1–2 juta jiwa | Rata-rata kabupaten di Jawa Timur |
| Peluncuran bertahap | 3 kecamatan (bukan 1 kabupaten penuh) | Roadmap §14, minggu 17–18 |
| Populasi terjangkau efektif | ±150.000 jiwa | 3 kecamatan × ±50.000 |
| Konversi instal → daftar | 25% | Kisaran umum aplikasi lokal baru |
| Penyedia dari hasil rekrutmen langsung | 100 dari 150 | Closed beta minggu 15–16 |
| Nilai transaksi rata-rata | Rp 250.000 | 200 pesanan × Rp250rb = GMV Rp50 juta |

**Konsekuensi angkanya:** 500 pengguna dari ±150.000 penduduk terjangkau =
**penetrasi 0,33%**. Ini konservatif dan realistis untuk produk baru tanpa
anggaran iklan besar.

Yang **tidak** konservatif adalah **150 penyedia terverifikasi**: rasio 1
penyedia per 3,3 pengguna sangat tinggi untuk marketplace. Angka ini hanya
tercapai lewat rekrutmen langsung di lapangan (mendatangi pasar, bengkel,
komunitas UMKM), bukan lewat pertumbuhan organik.

> ⚠️ **Risiko terbesar bukan jumlah pengguna, melainkan likuiditas.**
> Papan kebutuhan menjadi tidak berguna jika permintaan tidak mendapat
> penawaran. Karena itu target **Match Rate ≥75%** (§13) lebih penting
> daripada GMV — kalau harus memilih, prioritaskan kedalaman penyedia di
> sedikit kategori daripada menyebar tipis ke seluruh kategori.
>
> **Asumsi verifikasi:** dengan SLA 1×24 jam dan ±150 pengajuan dalam 3 bulan
> (≈2 per hari), satu admin paruh waktu masih memadai. Di atas 10 pengajuan
> per hari, dibutuhkan admin khusus atau OCR otomatis (Fase 2).

---

## 3. TARGET PENGGUNA & PERSONA

Platform menggunakan **Unified Account** (satu akun multi-peran). Persona dibagi berdasarkan aktivitas dominan:

### 3.1 Persona A – Pencari Kebutuhan (Pembeli/Pengguna Jasa)

- **Nama:** Budi Santoso (35 tahun), karyawan swasta.
- **Tujuan:** Mencari barang atau jasa dengan cepat, membandingkan harga lokal, dan mendapatkan layanan terpercaya tanpa harus keluar rumah.
- **Perilaku Digital:** Terbiasa dengan WhatsApp dan Facebook, kurang nyaman dengan marketplace nasional karena rumit.
- **Pain Points:** Tidak tahu kontak tukang terdekat; ragu dengan kualitas penyedia tanpa rekomendasi; tidak ingin repot tawar-menawar.

### 3.2 Persona B – Penyedia Jasa / UMKM Produk

- **Nama:** Pak Yanto (45 tahun), teknisi AC lepas.
- **Tujuan:** Mendapatkan pelanggan baru secara konsisten tanpa biaya iklan besar. Ingin sistem yang memberitahu ketika ada permintaan servis di dekatnya.
- **Perilaku Digital:** Punya smartphone Android, aktif di WhatsApp, tapi gaptek terhadap aplikasi rumit.
- **Pain Points:** Waktu luang terbuang sia-sia; pelanggan sering mencari tetapi tidak menemukan kontaknya; takut ditipu pelanggan fiktif.

> ℹ️ **Bagaimana “teknisi AC lepas” ditemukan di MVP.** Persona ini menyiratkan
> keahlian spesifik, tetapi MVP **tidak** punya field “keahlian” tersendiri.
> Penyedia ditemukan lewat tiga mekanisme yang sudah ada:
>
> 1. **Kategori toko** (`stores.category_ids`, maks 3 subkategori) — mis.
>    “Servis & Bengkel → AC”. Inilah dasar pencocokan broadcast.
> 2. **Judul & deskripsi listing** — terindeks FULLTEXT, jadi pencarian
>    “servis AC” tetap menemukannya.
> 3. **Foto hasil kerja** pada listing jasa, sebagai portofolio sederhana.
>
> Profil keahlian terstruktur (daftar sertifikat, tahun pengalaman, portofolio
> terpisah) masuk **Fase 2** bersama badge “Keahlian Terverifikasi”
> (`BRANDING-GUIDELINE.md` §4.1) — keduanya membutuhkan alur unggah dokumen dan
> peninjauan admin yang sama.

### 3.3 Persona C – Penjual Barang / Penyewa

- **Nama:** Bu Siti (40 tahun), pemilik toko kelontong.
- **Tujuan:** Meningkatkan penjualan dengan menjangkau tetangga yang mager (malas gerak). Ingin katalog sederhana yang bisa update stok sendiri.
- **Pain Points:** Ongkos kirim platform nasional mahal; fitur “sewa” tidak ada di platform lain.

---

## 4. LINGKUP PRODUK (MVP vs RILIS BERIKUTNYA)

| Modul / Fitur                          | MVP (Rilis 1.0)                                                                                  | Rencana Fase 2                                                                         |
| :------------------------------------- | :----------------------------------------------------------------------------------------------- | :------------------------------------------------------------------------------------- |
| **Pendaftaran & Profil**               | OTP WhatsApp, pengisian nama & lokasi (pinpoint), unggah KTP manual (opsional)                   | Login Google/Apple, biometrik, verifikasi KTP otomatis OCR                             |
| **Manajemen Toko**                     | Buka toko, pilih jenis (barang/jasa/sewa), atur area layanan (radius), jam operasional           | Multi-cabang, integrasi stok real-time dengan POS                                      |
| **Katalog (Jelajahi)**                 | Posting produk/jasa/sewa, foto maks 5, harga tetap, pencarian & filter radius                    | Video 15 detik, variasi (ukuran/warna), “Flash Sale” lokal, live streaming             |
| **Papan Kebutuhan (Pasang Kebutuhan)** | Pasang kebutuhan, pilih kategori, budget (opsional), terima/bandingkan penawaran, pilih pemenang | AI rekomendasi penyedia, auto-bidding, negosiasi chat langsung dalam aplikasi          |
| **Pesanan & Transaksi**                | COD, transfer langsung (nomor rekening ditampilkan), status pesanan manual                       | Escrow (in-app wallet), QRIS dinamis, integrasi kurir pihak ketiga, live tracking      |
| **Ulasan & Reputasi**                  | Rating bintang + komentar setelah pesanan selesai, hanya sekali per pesanan                      | Multi-kriteria rating (ketepatan, kualitas), lencana “Pelanggan Setia”, laporan detail |
| **Komunikasi**                         | Tombol “Hubungi via WhatsApp” dengan teks otomatis berisi detail kebutuhan                       | Chat in-app real-time, voice note, panggilan VoIP tersamar (privacy)                   |
| **Admin Dashboard**                    | Review verifikasi KTP/toko, manajemen kategori, lihat dispute, blokir pengguna                   | Dashboard analitik, sistem otomatis deteksi penipuan                                   |
| **Monetisasi**                         | Gratis 100% (fokus akuisisi)                                                                     | Paket langganan penyedia, boost listing, admin fee transaksi escrow                    |

#### Penjelasan Istilah Fase 2

Beberapa istilah di kolom kanan disebut sekali lalu tidak pernah dijelaskan.
Definisinya di sini agar tidak ditafsirkan berbeda-beda saat perencanaan Fase 2:

| Istilah | Maksud | Prasyarat |
| :-- | :-- | :-- |
| **Auto-bidding** | Penyedia menyetel aturan sekali (kategori, radius, harga dasar), lalu sistem mengirim penawaran awal otomatis saat ada permintaan cocok | `subscriptions` (fitur Pro), template harga per kategori, batas harian agar tidak jadi spam |
| **AI rekomendasi penyedia** | Mengurutkan penyedia berdasarkan riwayat kecocokan, bukan hanya jarak & rating | Data historis ≥6 bulan |
| **Verifikasi KTP otomatis (OCR)** | Membaca NIK & nama dari foto KTP, mencocokkan dengan input pengguna | Layanan OCR pihak ketiga, uji akurasi, tetap perlu tinjauan manual saat skor rendah |
| **Escrow / in-app wallet** | Dana ditahan platform sampai pesanan selesai | Izin PJP dari Bank Indonesia — proses panjang |

> ⚠️ **Auto-bidding berisiko merusak kepercayaan** bila diterapkan tanpa
> pembatas. Pembeli yang menerima lima penawaran identik dalam hitungan detik
> akan curiga penawarannya bukan dari manusia. Bila kelak diaktifkan:
> penawaran otomatis **wajib** ditandai jelas di UI, dan dibatasi jumlahnya
> per penyedia per hari.
>
> **Escrow bukan sekadar fitur teknis** — menahan dana pengguna memerlukan izin
> penyelenggara jasa pembayaran. Jangan dijadwalkan tanpa jalur perizinannya.

---

## 5. FITUR INTI & KEBUTUHAN FUNGSIONAL RINCI

### 5.1 Marketplace Katalog (Barang, Jasa, Sewa) – “Jelajahi”

**5.1.1 Pembuatan Listing**

- **Jenis Listing:**
  - `product` (barang siap jual) → wajib isi stok, harga tetap
  - `service` (jasa) → wajib isi kategori jasa, kapasitas harian (`slot`), harga bisa tetap atau “mulai dari”
  - `rental` (sewa) → wajib isi harga per hari/minggu, ketersediaan kalender, biaya antar/jemput
- **Data Listing:** Judul, deskripsi, kategori (dari taxonomy tree yang dikelola admin), foto (min 1, max 5), lokasi toko (otomatis dari profil toko), status aktif/nonaktif.
- **Aturan:** Satu toko bisa memiliki banyak listing, namun satu listing hanya untuk satu jenis (tidak campur). Listing jasa boleh menampilkan foto hasil kerja.

> ℹ️ **Arti `slot` pada listing jasa = kapasitas per hari, bukan jadwal.**
> Nilai `3` berarti penyedia sanggup menerima 3 pesanan per hari — bukan tiga
> jam tertentu. Waktu spesifik disepakati lewat WhatsApp dan dicatat di
> `offers.estimation_time`.
>
> Penjadwalan slot konkret (pilih “Senin 09:00”) memerlukan tabel
> `service_slots` dan masuk **Fase 2** — lihat `DATABASE.md` §4.10.
>
> Padanan kolomnya: `product`/`rental` memakai `stock_qty`, `service` memakai
> `slot`. Keduanya saling meniadakan dan ditegakkan CHECK constraint.

**5.1.2 Penelusuran & Filter**

- **Default tampilan:** Berdasarkan jarak terdekat dari lokasi pengguna, dengan batas radius maksimal 25 km (konfigurasi admin).

> ℹ️ **Tiga angka radius yang berbeda — jangan tertukar:**
>
> | Angka | Milik | Kolom / setelan | Arti |
> | :-- | :-- | :-- | :-- |
> | **25 km** | Sistem | `settings.max_search_radius_km` | Batas atas pencarian katalog |
> | **15 km** | Pembeli | `customer_requests.radius_km` | Jangkauan pencarian penyedia (§5.2.1) |
> | **5 km** | Penjual | `stores.service_radius_km` | Sejauh mana toko bersedia melayani (§5.3.3) |
>
> Ketiganya bukan duplikasi: 25 km membatasi *pencarian*, 15 km adalah default
> *permintaan*, 5 km adalah *kesanggupan toko*. Pencocokan broadcast menuntut
> **keduanya terpenuhi** — toko harus berada dalam radius pembeli, dan pembeli
> harus berada dalam radius layanan toko.
- **Filter tersedia:** Kategori/subkategori, jenis listing (barang/jasa/sewa), rentang harga, rating minimum.
- **Pencarian teks:** Full-text search pada judul dan deskripsi (menggunakan indeks FULLTEXT MySQL), dengan auto-suggest.
- **Tampilan Peta (opsional):** User dapat beralih ke tampilan peta untuk melihat sebaran listing di sekitar.

**5.1.3 Detail Listing & Aksi**

- Halaman detail menampilkan: slider foto, deskripsi, profil toko mini (nama, rating, verifikasi), peta jarak, dan tombol:
  - _Pesan Sekarang_ (langsung membuat pesanan dengan harga tetap)
  - _Tanya Penjual_ (buka WhatsApp dengan pre-filled text)
  - _Masukkan ke Wishlist_ (disimpan di profil)
- Untuk jasa, tombol “Pesan Sekarang” bisa mengarahkan ke form pemilihan slot waktu jika disediakan.

---

### 5.2 Papan Kebutuhan (Reverse Marketplace) – **UNGGULAN UTAMA** “Pasang Kebutuhan”

**5.2.1 Pembuatan Permintaan (Customer Request)**

- Pengguna mengisi **Form Permintaan**:
  1. Judul singkat (mis. “Butuh servis AC 1 PK”)
  2. Kategori wajib (sampai level 2, mis. Elektronik > AC) – dipilih dari taxonomy
  3. Deskripsi detail (maks 500 karakter)
  4. Foto pendukung (maks 3 foto, opsional)
  5. Lokasi spesifik (auto-fill dari GPS atau pilih di peta; wajib pinpoint)
  6. Rentang Budget (opsional): “Fixed” (harga pasti) atau “Bisa Nego” (rentang minimum-maksimum)
  7. Tanggal/jam dibutuhkan (opsional)
  8. Radius maksimal penyedia (default 15 km, bisa diubah pembeli)
- Setelah submit, sistem langsung menyimpan dan men-trigger proses “Broadcast”.

**5.2.2 Algoritma Pencocokan & Broadcast**

- Backend menjalankan **query geospasial** untuk mencari `stores` yang:
  - Memiliki `store_type` yang sesuai (jasa/barang) dan kategori layanan yang cocok dengan kategori permintaan.
  - Lokasi toko berada dalam radius permintaan pembeli (menggunakan fungsi `ST_Distance_Sphere`).
  - Toko dalam status aktif dan tidak dibekukan.
- **Prioritas Broadcast:** Penyedia yang sebelumnya pernah dipilih oleh pembeli serupa, rating tertinggi, dan jarak terdekat masuk dalam daftar penerima notifikasi.
- Notifikasi **push** dikirim ke perangkat penyedia terpilih, dan muncul di “Kebutuhan Sekitar” pada aplikasi mereka.

**5.2.3 Penawaran (Offer) oleh Penyedia**

- Penyedia yang menerima notifikasi dapat melihat detail permintaan (tanpa identitas lengkap pembeli, hanya nama depan dan rating pembeli).

> 🔒 **Data pembeli yang boleh dilihat penyedia sebelum penawaran diterima:**
>
> | Data | Sebelum diterima | Setelah diterima |
> | :-- | :-- | :-- |
> | Nama depan (`"Budi S."`) | ✅ | ✅ nama lengkap |
> | Rating sebagai pembeli | ✅ | ✅ |
> | Jarak perkiraan (“±3 km”) | ✅ | ✅ jarak tepat |
> | Lokasi | ⚠️ dibulatkan ke kelurahan | ✅ titik & alamat |
> | Nomor telepon | ❌ | ✅ |
> | Alamat lengkap | ❌ | ✅ |
>
> Ini bukan sekadar preferensi produk: menampilkan nomor telepon di papan
> kebutuhan terbuka akan membuat data kontak bisa dipanen massal — pelanggaran
> UU PDP sekaligus alasan pembeli berhenti memakai aplikasi.
>
> **Lokasi sengaja dibulatkan**, bukan disamarkan sebagian. Menampilkan
> koordinat dengan sedikit gangguan acak masih memungkinkan titik aslinya
> disimpulkan bila permintaan yang sama dilihat berkali-kali.
>
> Nama depan diturunkan dari kata pertama `users.name` + inisial kata kedua.
> API **tidak boleh** mengirim nama lengkap lalu memotongnya di klien — data
> yang terkirim ke perangkat harus sudah tersaring dari server.
- Penyedia mengisi **Form Penawaran**:
  - Harga penawaran (wajib, jika pembeli fixed maka tidak bisa diubah)
  - Estimasi waktu pengerjaan (teks bebas, mis. “Bisa datang hari ini jam 4 sore”)
  - Pesan tambahan (maks 200 karakter)
  - Opsi “Sertakan profil usaha” sudah otomatis.
- Satu penyedia hanya bisa mengirim **satu penawaran aktif** per permintaan. Jika ditolak, tidak bisa mengirim ulang kecuali ada revisi permintaan dari pembeli.

**5.2.4 Evaluasi & Pemilihan Pemenang oleh Pembeli**

- Pembeli mendapat notifikasi setiap kali ada penawaran baru.
- Pada halaman “Permintaan Saya”, pembeli melihat daftar penawaran yang terurut: **Harga Terendah** (default) atau bisa diurutkan berdasarkan Rating Tertinggi, Jarak Terdekat.

> **Parameter API pengurutan** — `GET /requests/{id}/offers?sort=cheapest`:
>
> | `sort` | Kriteria | Catatan |
> | :-- | :-- | :-- |
> | `cheapest` (default) | `price + additional_cost` ASC | **Total**, bukan harga saja — lihat `DATABASE.md` §4.6 |
> | `best_rating` | `stores.rating_avg` DESC, `total_reviews` DESC | Toko tanpa ulasan di urutan bawah |
> | `nearest` | `distance_km` ASC | Dihitung `ST_Distance_Sphere` |
> | `fastest` | `estimated_hours` ASC | Yang tidak mengisi ditaruh di akhir |
>
> Pengurutan **wajib di sisi server**, bukan di klien: daftar penawaran
> dipaginasi, dan mengurutkan hanya halaman pertama akan memberi hasil yang
> keliru.
>
> `best_rating` memakai `total_reviews` sebagai pemecah imbang — tanpa itu,
> toko dengan satu ulasan bintang 5 akan mengalahkan toko dengan lima puluh
> ulasan rata-rata 4,8.
- Setiap kartu penawaran menampilkan: nama toko/penyedia, rating, jumlah transaksi sukses, jarak, harga penawaran, estimasi, dan pesan.
- Pembeli bisa menekan tombol **“Terima Penawaran”** pada salah satu penawaran.
- Setelah penerimaan:
  - Status permintaan berubah menjadi `closed`, penawaran yang diterima menjadi `accepted`, penawaran lain otomatis `rejected`.
  - Terbentuk **pesanan (order)** baru yang mengikat kedua belah pihak, dan detail kontak (nomor telepon, alamat lengkap) dibuka untuk keduanya.

**5.2.5 Penanganan Kadaluarsa & Tidak Ada Penawaran**

- Permintaan memiliki masa aktif (default 24 jam, bisa diperpanjang manual oleh pembeli).
- Jika dalam 24 jam tidak ada penawaran, status berubah `expired`. Pembeli mendapat notifikasi dan bisa mengajukan ulang dengan jangkauan lebih luas atau budget lebih jelas.
- Admin dapat memantau permintaan tanpa penawaran untuk mengidentifikasi celah pasokan di wilayah tertentu.

---

### 5.3 Akun & Manajemen Toko

**5.3.1 Registrasi & Profil Pengguna**

- Registrasi hanya dengan **nomor HP Indonesia**, verifikasi via WhatsApp OTP (menggunakan Twilio/Kirim WA API).
- Setelah OTP, user wajib mengisi: Nama lengkap, pilih lokasi utama (desa/kecamatan) dari dropdown atau pinpoint peta.
- Upload foto profil (opsional).

**5.3.2 Level Verifikasi (KTP & Toko)**

- **Level 1 (Pengguna Biasa):** Hanya nomor HP, bisa membeli, memasang permintaan.
- **Level 2 (Pengguna Terverifikasi):** Unggah foto KTP dan selfie dengan KTP. Ditinjau manual oleh admin (maks 1x24 jam). Setelah lolos, bisa membuka toko.
- **Level 3 (Penyedia Pro):** Mengisi data usaha (NPWP opsional), foto tempat usaha, verifikasi lokasi via GPS. Lolos verifikasi mendapat lencana “Pro” dan peringkat lebih tinggi dalam broadcast.

**5.3.3 Pembukaan Toko & Pengaturan**

- Tombol “Jual/Tawarkan” (Buka Usaha/Layanan) hanya muncul jika user Level 2+.
- Form pembukaan toko:
  - Nama Toko (uniqueness per kabupaten dicek)
  - Kategori Layanan Utama (bisa pilih hingga 3 subkategori)
  - Jenis Toko: `goods`, `services`, `rental`, atau kombinasi.
  - **Pinpoint Lokasi Toko** (wajib, drag & drop pin pada peta; koordinat disimpan sebagai `POINT(lng lat)` menggunakan SRID 4326).
  - Radius Layanan (km) – misal 5 km untuk toko kelontong, 20 km untuk tukang bangunan.
  - Jam operasional per hari (Senin-Minggu, bisa set “Tutup”).
  - Nomor rekening bank/QRIS (opsional untuk menampilkan ke pembeli) – nanti digunakan jika transfer langsung.
- Setelah disimpan, toko berstatus `pending review`. Admin akan memverifikasi data dan lokasi (terutama memastikan toko benar-benar di dalam wilayah kabupaten target). Jika di luar, otomatis ditolak.

> **Cara “uniqueness per kabupaten” dan “di dalam kabupaten target” ditegakkan.**
> Keduanya membutuhkan kolom wilayah yang semula tidak ada. Kini tersedia
> `stores.regency` dan `stores.regency_code` (`DATABASE.md` §4.2):
>
> - **Uniqueness:** `UNIQUE (name, regency, deleted_at)` — nama toko boleh sama
>   di kabupaten berbeda, tetapi tidak di kabupaten yang sama.
> - **Geofencing berlapis:**
>   1. `regency_code` hasil reverse geocoding dicocokkan dengan tabel
>      `service_areas` (daftar kabupaten yang dilayani) → penolakan instan.
>   2. Bila poligon batas tersedia, diuji `ST_Contains` — reverse geocoding
>      bisa meleset di dekat perbatasan.
>   3. Admin tetap meninjau manual sebagai lapis terakhir.
>
> Penolakan otomatis **tidak** boleh menjadi satu-satunya penentu: kesalahan
> GPS di dalam bangunan bisa menggeser titik ratusan meter. Karena itu toko
> yang ditolak otomatis tetap bisa mengajukan banding lewat kanal pengaduan.

> **NPWP untuk Level 3.** Kolom `stores.npwp` (opsional, `DATABASE.md` §4.2)
> menampung nomor NPWP usaha. Karena bersifat opsional, ketiadaannya **tidak**
> boleh memblokir verifikasi Level 3 — foto tempat usaha dan verifikasi GPS
> tetap menjadi syarat utama. NPWP diperlakukan sebagai data sensitif dan tidak
> pernah ditampilkan ke pengguna lain.

---

### 5.4 Manajemen Pesanan & State Machine

Setiap pesanan memiliki tipe `order_type`: `product`, `service`, `rental`. Alur status disesuaikan.

> Nilainya **sama persis** dengan `listings.listing_type` agar bisa disalin
> langsung saat pesanan dibuat. Jangan tulis `goods` di sini — bentuk jamak
> `goods` hanya dipakai `stores.store_type` yang bertipe `SET`.

> ⚠️ **Diagram di bawah adalah alur pengalaman pengguna, bukan nilai kolom.**
> `orders.status` hanya punya **enam** nilai (`DATABASE.md` §4.7):
> `menunggu_konfirmasi`, `diproses`, `dikirim`, `selesai`, `dibatalkan`,
> `dispute`. Label seperti “Dijadwalkan” atau “Dikembalikan” adalah **tampilan
> turunan**, bukan status baru.
>
> Pemetaannya:
>
> | Label di diagram | `orders.status` | Pembeda |
> | :-- | :-- | :-- |
> | Menunggu Konfirmasi | `menunggu_konfirmasi` | — |
> | Diproses / Dijadwalkan / Dalam Pengerjaan | `diproses` | `order_type` + `scheduled_at` |
> | Dikirim / Siap Diambil | `dikirim` | `delivery_method` (`delivery` vs `pickup`) |
> | Disewa / Dikembalikan | `dikirim` → `selesai` | `order_type = 'rental'` |
> | Selesai | `selesai` | — |
> | Dibatalkan | `dibatalkan` | — |
>
> **Kenapa tidak menambah nilai ENUM saja?** Menambah `siap_diambil`,
> `dijadwalkan`, dan `dikembalikan` membuat state machine bercabang per
> `order_type`, dan setiap cabang menggandakan jumlah transisi yang harus
> divalidasi serta diuji. Label UI cukup diturunkan dari kombinasi
> `status + order_type + delivery_method` yang datanya sudah ada.
>
> Contoh: pesanan `status = 'dikirim'` ditampilkan sebagai **“Dikirim”** bila
> `delivery_method = 'delivery'`, dan **“Siap Diambil”** bila `pickup`.

**5.4.1 State Diagram Pesanan Barang**

```
[Menunggu Konfirmasi Penjual] → penjual terima → [Diproses] → penjual kirim/siap ambil → [Dikirim/Siap Diambil] → pembeli konfirmasi terima → [Selesai]
                                  → penjual tolak → [Dibatalkan]
```

- Pembeli dapat membatalkan sebelum status “Diproses”.
- Setelah “Selesai”, ulasan dapat ditulis dalam 7 hari.

**5.4.2 State Diagram Pesanan Jasa**

```
[Menunggu Konfirmasi Penyedia] → diterima → [Dijadwalkan] (isi tanggal & jam) → penyedia mulai → [Penyedia Menuju Lokasi] (opsional, jika ada tracking) → [Dalam Pengerjaan] → penyedia selesai & upload foto bukti → [Menunggu Konfirmasi Pembeli] → pembeli setuju → [Selesai]
```

- Pembeli dapat mengajukan komplain jika pengerjaan tidak sesuai; status berubah menjadi `dispute` dan admin masuk.
- Ulasan hanya bisa diberikan jika status “Selesai”.

**5.4.3 State Diagram Sewa**

- Mirip barang, tetapi ada tambahan sub-state pengembalian:  
  `... [Disewa] → [Dikembalikan] → [Selesai]` (jika ada sistem pengembalian). MVP sederhana: status “Selesai” setelah masa sewa berakhir berdasarkan konfirmasi kedua pihak.

**Pemetaan MVP untuk sewa:** “Disewa” = `dikirim` (barang sudah di tangan
penyewa), “Dikembalikan” = konfirmasi pengembalian yang langsung menutup
pesanan ke `selesai`. Tidak ada nilai ENUM `dikembalikan`.

> Sistem pengembalian bertahap (dengan pemeriksaan kondisi barang dan denda
> keterlambatan) masuk **Fase 2** — memerlukan kolom denda, bukti kondisi, dan
> alur sengketa tersendiri.

---

### 5.5 Sistem Kepercayaan & Ulasan

- **Rating & Ulasan Terkunci:** Hanya setelah pesanan `selesai`, pembeli dan penjual bisa saling menilai (rating 1-5, komentar). Tidak bisa diubah.
- **Perhitungan Rating Toko:** Rata-rata bobot dari semua rating yang diterima, ditampilkan dengan 1 desimal. Rating di bawah 3.0 akan mendapat flag dan bisa ditinjau admin.
- **Laporan/Dispute:** Di setiap pesanan, ada tombol “Laporkan Masalah”. Alasan yang bisa dipilih:
  - Barang tidak sesuai
  - Jasa tidak selesai / tidak profesional
  - Penyedia tidak responsif
  - Pembeli fiktif / tidak bayar
  - Lainnya (isi teks)
- Tim admin wajib menanggapi dispute dalam 1x24 jam melalui dashboard.

---

### 5.6 Komunikasi Pembeli-Penjual

**MVP:** Menggunakan **deep link WhatsApp** untuk memulai percakapan.

- Saat pengguna menekan “Hubungi via WhatsApp”, sistem membuat URL:  
  `https://wa.me/62XXXXXXXXXX?text=Halo%20[Nama%20Toko],%20saya%20tertarik%20dengan%20[Judul%20Listing/ID%20Permintaan]%20di%20Seekitar.%20...`
- Klik dicatat sebagai event `whatsapp_click` untuk analitik.
- Nomor telepon hanya ditampilkan setelah transaksi disepakati (untuk permintaan, setelah penawaran diterima). Sebelumnya, hanya nama toko dan informasi umum yang terlihat.

**Keamanan Tambahan:** Sistem memberikan peringatan tegas di chat agar tidak bertransaksi di luar platform jika belum ada kesepakatan, untuk mengurangi risiko penipuan.

---

## 6. ALUR PENGGUNA (USER FLOW) TERPERINCI

### 6.1 Alur “Pembeli Memasang Kebutuhan hingga Memilih Penyedia”

1. Pembeli di Beranda → Tab “Pasang Kebutuhan”.
2. Pilih kategori (list dari API `/categories?type=service`).
3. Isi form (judul, deskripsi, foto, budget, lokasi, radius). Lokasi pin bisa digeser.
4. Tekan “Pasang”. Backend menyimpan di `customer_requests`, lalu memanggil job “Broadcast” yang query toko relevan dan kirim push notifikasi.
5. Halaman “Permintaan Saya” menampilkan status `open`, jumlah penawaran `0`.
6. Penyedia menerima notifikasi, buka permintaan, dan kirim penawaran.
7. Pembeli mendapat push: “Ada penawaran baru untuk ‘Servis AC’”. Buka halaman permintaan, lihat daftar penawaran.
8. Pembeli mengurutkan/filter, lalu tekan “Terima Penawaran” pada penyedia pilihan.
9. Dialog konfirmasi: “Anda akan menerima penawaran dari [Nama Toko] seharga Rp X. Detail kontak akan ditampilkan.” Setuju.
10. Status permintaan `closed`, order otomatis terbentuk dengan status `Menunggu Konfirmasi Penyedia` (atau langsung `Dijadwalkan` jika sudah ada estimasi).
11. Pembeli dan penyedia dapat melihat detail kontak satu sama lain di halaman order.

### 6.2 Alur “Penyedia Jasa Mengirim Penawaran”

1. Notifikasi push: “Permintaan Baru: Servis Kulkas (3.2 km)”.
2. Klik → halaman detail permintaan. Tampil informasi (tanpa nama/HP pembeli lengkap).
3. Tekan “Kirim Penawaran”. Isi harga, estimasi, pesan.
4. Tekan “Kirim”. Offer tersimpan, pembeli mendapat notifikasi.
5. Jika penawaran diterima, penyedia mendapat notifikasi “Penawaran Anda diterima! Lihat detail pelanggan”.

### 6.3 Wireframe Deskriptif Halaman Kunci

- **Halaman Utama:** 3 tab bawah – Jelajahi (feed listing terdekat), Kebutuhan (feed permintaan), Transaksi (history). Header berisi search bar dan ikon notifikasi.
- **Detail Permintaan (sisi penyedia):** Slider foto, deskripsi, pin lokasi di peta kecil, budget (jika ada), tombol “Kirim Penawaran” mencolok.
- **Halaman Bandingkan Penawaran:** Tampilan list vertikal, setiap kartu: foto profil toko, nama, rating, jarak, harga besar, estimasi, tombol “Terima”. Sort by di atas.

---

## 7. ARSITEKTUR SISTEM & TEKNOLOGI

### 7.1 Diagram Komponen Tingkat Tinggi

```
[Flutter App] ↔ [REST API] ↔ [Laravel 13 Backend (API + Web + Admin)]
                                    ↕
                         [MySQL 8.0.34+ (Spatial)]
                         [Redis 7 (Cache, Job Queue)]
                         [Cloud Storage (S3/MinIO)]
                         [Push Notification (Firebase FCM)]
                         [WhatsApp API (Twilio/Kirim WA)]
```

### 7.2 Tech Stack Rinci

> 📌 Versi lengkap & matriks kompatibilitas paket ada di [`TECH_STACK.md`](TECH_STACK.md) (sumber kebenaran tunggal).

| Layer                          | Teknologi                                         | Keterangan                                                                        |
| :----------------------------- | :------------------------------------------------ | :-------------------------------------------------------------------------------- |
| **Mobile App**                 | Flutter 3.44+, Riverpod 3, Dio 5, Google Maps Flutter | Satu kode untuk Android & iOS, performa native                                    |
| **Web Public (SEO)**           | Laravel 13 + Bootstrap 5.3.x (Blade), tanpa Vite/Vue  | Halaman katalog & landing page SEO-friendly, server-side rendering                |
| **Admin Dashboard**            | Laravel 13 + Bootstrap 5.3.x (Blade)                | Panel admin di subdomain/admin, otentikasi Laravel session & gate                 |
| **Backend API**                | Laravel 13 (REST API dengan Sanctum 4)              | Semua API untuk mobile app, rate limiting, validasi, queue job broadcast          |
| **Database**                   | MySQL 8.0.34+ (dengan dukungan Spatial)               | Menyimpan semua data transaksional dan geospasial                                 |
| **Cache & Queue**              | Redis 7                                           | Menyimpan sesi, cache data kategori, antrian notifikasi broadcast (Laravel Queue) |
| **Storage**                    | AWS S3 (atau MinIO self-hosted)                   | Foto produk, KTP, bukti kerja (terenkripsi)                                       |
| **Push Notification**          | Firebase Cloud Messaging (FCM)                    | Notifikasi ke perangkat mobile                                                    |
| **WhatsApp OTP & Redirection** | Twilio / Kirim WA API                             | Kirim OTP, tracking klik                                                          |
| **CI/CD**                      | GitHub Actions / GitLab CI                        | Otomatisasi build APK, deploy backend & web                                       |
| **Monitoring**                 | Laravel Telescope (dev), Sentry (error tracking)  |                                                                                   |

### 7.3 Konfigurasi Geospasial (MySQL Spatial)

- Setiap tabel yang berisi lokasi (`stores.location`, `customer_requests.location`, `users.location`) menggunakan tipe `POINT` dengan SRID 4326 (WGS 84).
- Indeks spasial `SPATIAL INDEX` dibuat pada kolom lokasi untuk mempercepat query jarak.
- Perhitungan jarak menggunakan fungsi `ST_Distance_Sphere` yang menghasilkan jarak dalam meter.
- Query utama pencarian dalam radius (menggunakan Laravel Query Builder dengan raw query):
  ```php
  Store::whereRaw(
      "ST_Distance_Sphere(location, ST_GeomFromText(?, 4326, 'axis-order=long-lat')) <= ?",
      ["POINT({$longitude} {$latitude})", $radius_meter]
  )->where('is_active', true)->get();
  ```
- Nilai $radius_meter dikonversi dari km dengan mengalikan 1000.
- Untuk menampilkan jarak, gunakan `ST_Distance_Sphere` dan konversi ke km di aplikasi.

---

## 8. MODEL DATA & SKEMA DATABASE

> 📌 **Sumber kebenaran skema adalah [`DATABASE.md`](DATABASE.md) §4.**
> Bab ini ringkasan untuk pembaca non-teknis: hanya kolom utama, tanpa indeks,
> CHECK constraint, dan kolom operasional. Bila keduanya berbeda, DATABASE.md
> yang berlaku — dan perbedaan itu adalah bug yang harus diperbaiki di sini.

### 8.1 Skema Tabel Utama (MVP)

**`users`**
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| id | CHAR(36) | Primary Key (UUID) |
| phone | VARCHAR(15) UNIQUE | Nomor HP (62xxx) |
| name | VARCHAR(100) | |
| avatar_url | TEXT | |
| location | POINT SRID 4326 | Lokasi default user |
| verification_level | TINYINT | 1,2,3 |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**`stores`**
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| id | CHAR(36) | Primary Key (UUID) |
| user_id | CHAR(36) | FK ke users |
| name | VARCHAR(100) | Nama toko / lapak |
| store_type | SET('goods','services','rental') | Kombinasi jenis usaha |
| category_ids | JSON | Array ID kategori, mis. `[1,3,7]` |
| regency | VARCHAR(100) | Kabupaten/kota; lingkup uniqueness nama toko |
| npwp | VARCHAR(20) NULL | Opsional, pendukung verifikasi Level 3 |
| location | POINT SRID 4326 | Titik lokasi toko |
| service_radius_km | DECIMAL(5,2) | |
| operating_hours | JSON | Jam operasional per hari |
| rating_avg | DECIMAL(3,2) | |
| total_reviews | INT | |
| is_active | TINYINT(1) | |
| verification_status | VARCHAR(20) | `pending`, `verified`, `rejected` |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**`categories`**
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| id | INT AUTO_INCREMENT PK | |
| name | VARCHAR(50) | |
| slug | VARCHAR(50) | |
| parent_id | INT NULL | FK ke id sendiri |
| icon | VARCHAR(50) | Nama ikon (misal FontAwesome) |

**`listings`** (Katalog)
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| id | CHAR(36) PK | |
| store_id | CHAR(36) FK | |
| title | VARCHAR(200) | |
| description | TEXT | |
| listing_type | VARCHAR(20) | `product`, `service`, `rental` |
| price | DECIMAL(12,2) | Harga (untuk service bisa null) |
| stock_qty | INT NULL | untuk product/rental |
| slot | INT NULL | untuk service (kapasitas per slot) |
| images | JSON | Array URL gambar |
| status | VARCHAR(20) | `active`, `sold`, `hidden` |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**`customer_requests`** (Papan Kebutuhan)
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| id | CHAR(36) PK | |
| user_id | CHAR(36) FK | |
| title | VARCHAR(200) | |
| description | TEXT | |
| category_id | INT FK | |
| budget_min | DECIMAL(12,2) NULL | |
| budget_max | DECIMAL(12,2) NULL | |
| location | POINT SRID 4326 | Lokasi permintaan |
| radius_km | DECIMAL(5,2) | |
| required_date | TIMESTAMP NULL | |
| expires_at | TIMESTAMP | Default 24 jam |
| status | VARCHAR(20) | `open`, `closed`, `expired` |
| accepted_offer_id | CHAR(36) NULL | FK ke offers |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**`offers`** (Penawaran)
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| id | CHAR(36) PK | |
| request_id | CHAR(36) FK | |
| store_id | CHAR(36) FK | |
| price | DECIMAL(12,2) | |
| estimation_time | VARCHAR(100) | |
| notes | TEXT | |
| status | VARCHAR(20) | `pending`, `accepted`, `rejected` |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**`orders`**
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| id | CHAR(36) PK | |
| buyer_id | CHAR(36) FK | |
| store_id | CHAR(36) FK | |
| offer_id | CHAR(36) NULL | |
| listing_id | CHAR(36) NULL | |
| order_type | VARCHAR(20) | `product`, `service`, `rental` — sama persis dengan `listings.listing_type` |
| quantity | INT UNSIGNED | Default 1; selalu 1 untuk `service` |
| total_amount | DECIMAL(12,2) | |
| status | VARCHAR(30) | |
| payment_method | VARCHAR(30) | `cod`, `transfer` |
| notes | TEXT NULL | Catatan pembeli saat memesan |
| completed_at | TIMESTAMP NULL | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**`reviews`**
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| id | CHAR(36) PK | |
| order_id | CHAR(36) FK | **Tidak UNIQUE** — satu pesanan punya maks 2 ulasan (§5.5) |
| reviewer_id | CHAR(36) FK | Penulis ulasan |
| reviewee_id | CHAR(36) FK | Pihak yang diulas |
| store_id | CHAR(36) NULL FK | Diisi hanya saat arah `buyer_to_store` |
| direction | VARCHAR(20) | `buyer_to_store`, `store_to_buyer` |
| rating | TINYINT CHECK(1-5) | |
| comment | TEXT | |
| created_at | TIMESTAMP | Tanpa `updated_at` — ulasan tidak bisa disunting |

> ⚠️ `UNIQUE` dipasang pada **(`order_id`, `direction`)**, bukan `order_id`
> saja. Menguncinya ke satu ulasan per pesanan akan mematahkan penilaian dua
> arah yang diwajibkan §5.5.

**`disputes`**
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| id | CHAR(36) PK | |
| order_id | CHAR(36) FK | |
| reported_by | CHAR(36) FK | |
| reason | VARCHAR(100) | |
| description | TEXT | |
| status | VARCHAR(20) | `open`, `resolved` |
| resolution_note | TEXT NULL | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

## 9. SPESIFIKASI API & LAYANAN BACKEND

API menggunakan format REST JSON, autentikasi Bearer Token dengan Laravel Sanctum. Rate limiting: 60 request/menit per IP.

### 9.1 Daftar Endpoint Kunci

**Authentication**

- `POST /api/auth/request-otp` (body: phone) → Kirim OTP WhatsApp
- `POST /api/auth/verify-otp` (body: phone, otp) → Mengembalikan token akses (Personal Access Token)

**Stores**

- `POST /api/stores` – Buka toko baru (butuh level 2, middleware `verified`)
- `GET /api/stores/nearby?lat=...&lng=...&radius=...&type=...` – Cari toko sekitar dengan filter
- `GET /api/stores/:id` – Detail toko

**Listings**

- `GET /api/listings?lat=...&lng=...&radius=...&category=...&type=...&sort=...` – Pencarian katalog
- `POST /api/listings` – Tambah listing
- `GET /api/listings/:id` – Detail

**Customer Requests (Reverse)**

- `POST /api/requests` – Pasang permintaan
- `GET /api/requests?status=open&lat=...&lng=...&radius=...` – Lihat daftar permintaan untuk penyedia (Kebutuhan Sekitar)
- `GET /api/requests/:id` – Detail permintaan (dengan flag apakah user pemilik/penyedia)

**Offers**

- `POST /api/requests/:id/offers` – Kirim penawaran
- `GET /api/requests/:id/offers` – Lihat penawaran (untuk pembeli)
- `PATCH /api/offers/:id/accept` – Terima penawaran (hanya pemilik request)

**Orders**

- `POST /api/orders` – Buat order langsung (dari katalog) atau otomatis dari penerimaan offer
- `PATCH /api/orders/:id/status` – Update status (dengan validasi state)
- `GET /api/orders` – Riwayat pesanan user

**Reviews**

- `POST /api/orders/:id/review` – Beri ulasan

**Admin Endpoints** (prefix `/api/admin` dengan guard `admin` menggunakan Laravel Gates)

- `GET /api/admin/verifications/pending`
- `POST /api/admin/verifications/:user_id/approve` atau `reject`
- `GET /api/admin/disputes`

### 9.2 Contoh Request/Response “Kirim Penawaran”

**Request:**  
`POST /api/requests/req-123/offers`  
Headers: `Authorization: Bearer <token>`  
Body:

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

**Response 201:**

```json
{
  "success": true,
  "data": {
    "id": "offer-456",
    "request_id": "req-123",
    "store_id": "store-789",
    "store": { "id": "store-789", "name": "Bengkel AC Yanto", "rating_avg": 4.5 },
    "price": 150000,
    "additional_cost": 15000,
    "total_amount": 165000,
    "status": "pending",
    "expires_at": "2026-07-29T10:30:00Z",
    "created_at": "2026-07-27T10:30:00Z"
  }
}
```

`store_id` disertakan agar klien tidak perlu memanggil ulang untuk mengetahui
toko mana yang menawar — satu penyedia bisa memiliki lebih dari satu toko.
`total_amount` (`price + additional_cost`) juga dikirim server supaya
perhitungan tidak diulang di klien dan berisiko berbeda.

Format response mengikuti pembungkus `success`/`data` yang baku
(`API_DOCUMENTATION.md` §1) — contoh lama di dokumen ini sempat mengembalikan
objek telanjang, yang tidak konsisten dengan endpoint lain.

Jika user mencoba mengirim lagi sementara masih ada offer pending: **409 Conflict** `"Anda sudah mengirim penawaran pada permintaan ini."`

---

## 10. NOTIFIKASI & KOMUNIKASI SISTEM

Notifikasi bersifat kritis untuk engagement. Digunakan **Firebase Cloud Messaging** untuk push, dan untuk WhatsApp menggunakan template yang sudah disetujui.

### 10.1 Jenis Notifikasi Push (Mobile)

| Trigger                 | Penerima        | Judul & Isi                                                                                       |
| :---------------------- | :-------------- | :------------------------------------------------------------------------------------------------ |
| Permintaan baru cocok   | Penyedia        | _Permintaan Baru: [Judul]_ – “Jarak [X] km dari lokasi Anda. Kirim penawaran sekarang.”           |
| Penawaran baru diterima | Pembeli         | _Penawaran Masuk_ – “[Nama Toko] menawarkan Rp [Harga] untuk [Judul Permintaan].”                 |
| Penawaran diterima      | Penyedia        | _Penawaran Diterima!_ – “Selamat, penawaran Anda untuk [Judul] diterima. Lihat detail pelanggan.” |
| Status pesanan berubah  | Pembeli/Penjual | _Pesanan [ID] Diperbarui_ – “Status: [Status Baru].”                                              |
| Permintaan kadaluarsa   | Pembeli         | _Permintaan Kadaluarsa_ – “Tidak ada penawaran masuk. Perluas radius atau pasang ulang?”          |

### 10.2 Deep Link & Data Payload

Setiap notifikasi membawa `data` payload yang berisi `type`, `entity_id`, dan `screen` untuk navigasi langsung ke halaman terkait (deep link).

**Struktur payload:**

```json
{
  "notification": {
    "title": "Permintaan Baru: Servis Kulkas",
    "body": "Jarak 3.2 km dari lokasi Anda. Kirim penawaran sekarang."
  },
  "data": {
    "type": "request_broadcast",
    "screen": "request_detail",
    "entity_id": "uuid-permintaan",
    "distance_km": "3.2"
  }
}
```

> ⚠️ **`distance_km` wajib ikut di payload.** Teks notifikasi “Jarak [X] km”
> disusun di server saat job broadcast berjalan — jaraknya **sudah dihitung**
> di sana lewat `ST_Distance_Sphere` antara `stores.location` dan
> `customer_requests.location` (lihat `Server_Implementation_Guide.md` §14.1).
>
> Aplikasi **tidak bisa** menghitungnya sendiri saat notifikasi tiba: lokasi
> toko penerima tidak tentu tersedia di perangkat, dan menghitung ulang saat
> aplikasi masih tertutup itu mustahil.
>
> Nilai `data` pada FCM **harus berupa string** — `"3.2"`, bukan `3.2`.
> Mengirim angka menyebabkan pesan ditolak Firebase.
>
> Jarak dibulatkan **satu desimal** dan bersifat perkiraan; jarak tepat baru
> ditampilkan setelah penawaran diterima (§5.2.3).

---

## 11. KEAMANAN & KEPATUHAN REGULASI

> 📌 Implementasi teknis setiap poin di bab ini ada di
> [`Server_Implementation_Guide.md`](Server_Implementation_Guide.md) §18A,
> lengkap dengan tabel pemetaan janji → kode (§18A.7).

### 11.1 Perlindungan Data Pribadi

- **KTP & Selfie** dienkripsi saat penyimpanan (AES-256). Hanya diakses oleh admin terotorisasi, lewat URL berumur pendek yang setiap aksesnya dicatat.

> ⚠️ **Cara enkripsinya berbeda antara berkas dan teks.** Berkas gambar memakai
> **S3 SSE-KMS** (enkripsi at-rest oleh penyedia), sedangkan NIK memakai
> `Crypt::encryptString()` di kolom database. Mengenkripsi berkas 5 MB lewat
> `Crypt` membengkakkannya 33% dan memuat seluruhnya ke memori — lihat
> pengukurannya di `Server_Implementation_Guide.md` §18A.3.
>
> Kuncinya dikelola **AWS KMS**, bukan Vault — menyelaraskan dengan pilihan
> penyimpanan S3 di §7.2 dan menghindari satu komponen infrastruktur tambahan.
- **Koordinat Lokasi** hanya digunakan untuk pencarian dan tidak ditampilkan secara mentah ke publik. Pembeli tidak melihat alamat lengkap penyedia sebelum order diterima.
- **Nomor Telepon** ditampilkan bertahap: di halaman penawaran (penyedia) hanya informasi umum; setelah transaksi disepakati, nomor terbuka dua arah. Peringatan ditampilkan: “Hati-hati berkomunikasi di luar platform. Simpan bukti transaksi.”
- **Data pribadi tidak dijual**, sesuai UU PDP. Saat registrasi, pengguna wajib menyetujui kebijakan privasi yang menjelaskan pengumpulan data lokasi dan KTP.

### 11.2 Kepatuhan PSE & Permendag

- **Registrasi PSE:** Sistem didaftarkan ke Kominfo (Komdigi) sebelum publik. Menyediakan halaman pelaporan konten dan kontak resmi.

> **Timeline pendaftaran PSE — mulai pada minggu 11, bukan menjelang rilis.**
>
> | Kapan | Kegiatan |
> | :-- | :-- |
> | Minggu 11–12 | Siapkan prasyarat: akta badan usaha, NPWP, kebijakan privasi, syarat & ketentuan, kanal pengaduan aktif |
> | Minggu 13 | Ajukan pendaftaran lewat OSS |
> | Minggu 13–16 | Masa proses (umumnya beberapa minggu; bisa ada permintaan perbaikan) |
> | Minggu 17 | **Tanda daftar terbit** — syarat sebelum open beta |
> | Minggu 17–18 | Open beta / rilis publik |
>
> Prasyaratnya bukan pekerjaan sepele: kebijakan privasi dan syarat & ketentuan
> harus sudah final, dan alamat pengaduan (`pengaduan@seekitar.id`,
> `privasi@seekitar.id`) harus **sudah aktif** karena dicantumkan di formulir —
> lihat `BRANDING-GUIDELINE.md` §8.3.
>
> ⚠️ Play Store dan App Store dapat menolak aplikasi marketplace Indonesia yang
> belum terdaftar PSE. Karena itu pendaftaran berjalan **paralel** dengan
> pengembangan, bukan setelahnya. Distribusi APK untuk closed beta (minggu
> 15–16) masih boleh berjalan lebih dulu.
- **Dispute Resolution:** Fitur laporan di aplikasi langsung, tim admin memproses sesuai SLA.
- **Transparansi:** Halaman “Pusat Bantuan” berisi kebijakan pengembalian dana, syarat COD, dan prosedur komplain.

### 11.3 Keamanan Aplikasi

- Semua komunikasi via HTTPS/TLS 1.3.
- Validasi input ketat pada semua endpoint (Laravel Form Request, mencegah XSS, SQL Injection via Eloquent).
- Rate limiting pada endpoint OTP dan kirim penawaran (Laravel throttle).
- Monitoring anomali login (gagal 5x → blokir sementara, fitur Laravel Rate Limiter).

---

## 12. MODEL BISNIS & MONETISASI

**Fase 1 (6 bulan pertama):** Seluruh fitur gratis untuk memaksimalkan adopsi dan data.  
**Pasca MVP:** Monetisasi bertahap tanpa merusak kepercayaan.

| Sumber Pendapatan        | Deskripsi                                                                                                                                                    | Estimasi Harga       |
| :----------------------- | :----------------------------------------------------------------------------------------------------------------------------------------------------------- | :------------------- |
| **Paket “Penyedia Pro”** | Langganan bulanan. Fitur: Auto-reply penawaran (dengan template), lencana Pro di profil & pencarian, prioritas muncul di broadcast (bukan prioritas mutlak). | Rp 49.000/bulan      |
| **Boost Listing**        | Tampil di urutan teratas hasil pencarian kecamatan/kategori selama 24 jam.                                                                                   | Rp 9.900/hari        |
| **Admin Fee (Escrow)**   | Jika kelak menerapkan in-app wallet & escrow, biaya 1-2% dari nilai transaksi yang dibayarkan via platform.                                                  | 1.5%                 |
| **Iklan Lokal**          | Toko bisa memasang banner di feed beranda dengan segmentasi radius.                                                                                          | Mulai Rp 50.000/hari |

---

## 13. METRIK KEBERHASILAN & KPI

Selain target GMV dan DAU, detail metrik berikut dilacak via dashboard analitik (Google Analytics for Firebase / Mixpanel).

| Kategori       | Nama Metrik               | Definisi                                                     | Target 3 Bulan |
| :------------- | :------------------------ | :----------------------------------------------------------- | :------------- |
| **Likuiditas** | Match Rate                | % permintaan yang mendapat ≥1 penawaran                      | ≥ 75%          |
|                | Time to First Offer       | Waktu rata-rata dari request terbit ke penawaran pertama     | ≤ 20 menit     |
|                | Offer Acceptance Rate     | % penawaran yang akhirnya diterima pembeli                   | ≥ 40%          |
| **Aktivitas**  | Weekly Active Sellers     | Penyedia yang membuka aplikasi minimal 1x seminggu           | 150            |
|                | Requests per Active Buyer | Rata-rata permintaan yang dibuat per pembeli aktif per bulan | 3              |
| **Kualitas**   | NPS (Net Promoter Score)  | Survei in-app setelah pesanan selesai                        | ≥ 30           |
|                | Dispute Rate              | % pesanan yang berujung laporan                              | ≤ 5%           |
| **Bisnis**     | Conversion Rate (penjual) | Pengguna yang registrasi lalu buka toko                      | ≥ 25%          |
|                | Time to Verify KTP        | SLA admin menyetujui verifikasi                              | ≤ 24 jam       |

#### Rumus Perhitungan

Definisi tekstual saja menghasilkan angka berbeda-beda antar orang. Berikut
rumus bakunya:

| Metrik | Rumus |
| :-- | :-- |
| **Match Rate** | `permintaan dengan ≥1 penawaran ÷ total permintaan` |
| **Time to First Offer** | `median(offer_pertama.created_at − request.created_at)` |
| **Offer Acceptance Rate** | `penawaran accepted ÷ total penawaran` |
| **Conversion Rate (penjual)** | `pengguna punya ≥1 toko verified ÷ total pengguna` |
| **Dispute Rate** | `pesanan dengan ≥1 dispute ÷ pesanan selesai/dibatalkan` |
| **Time to Verify KTP** | `median(approved_at − ktp_submitted_at)` |

```sql
-- Match Rate (30 hari terakhir, hanya permintaan yang sudah selesai masa aktifnya)
SELECT ROUND(100 * SUM(has_offer) / COUNT(*), 1) AS match_rate_pct
FROM (
  SELECT cr.id, EXISTS(SELECT 1 FROM offers o WHERE o.request_id = cr.id) AS has_offer
  FROM customer_requests cr
  WHERE cr.created_at >= NOW() - INTERVAL 30 DAY
    AND cr.status <> 'open'          -- yang masih terbuka belum bisa dinilai
) t;
```

```sql
-- Time to First Offer: MEDIAN, bukan AVG
SELECT
  AVG(minutes) AS mean_minutes,
  MAX(CASE WHEN rn = mid THEN minutes END) AS median_minutes
FROM (
  SELECT TIMESTAMPDIFF(MINUTE, cr.created_at, MIN(o.created_at)) AS minutes,
         ROW_NUMBER() OVER (ORDER BY TIMESTAMPDIFF(MINUTE, cr.created_at, MIN(o.created_at))) AS rn,
         CEIL(COUNT(*) OVER () / 2) AS mid
  FROM customer_requests cr
  JOIN offers o ON o.request_id = cr.id
  WHERE cr.created_at >= NOW() - INTERVAL 30 DAY
  GROUP BY cr.id, cr.created_at
) t;
```

> ⚠️ **Tiga jebakan pengukuran:**
>
> 1. **Permintaan `open` harus dikecualikan** dari Match Rate. Permintaan yang
>    baru dibuat 5 menit lalu belum tentu gagal — memasukkannya membuat angka
>    terlihat buruk secara palsu.
> 2. **Time to First Offer memakai median, bukan rata-rata.** Satu permintaan
>    yang dijawab setelah 20 jam akan menarik rata-rata jauh ke atas, padahal
>    mayoritas dijawab dalam hitungan menit.
> 3. **Denominator Dispute Rate adalah pesanan yang sudah berakhir**, bukan
>    seluruh pesanan — pesanan yang masih berjalan belum berpeluang disengketakan.
>
> Semua metrik dihitung dari basis data (sumber kebenaran transaksi), sedangkan
> Firebase Analytics dipakai untuk metrik perilaku (corong, retensi) yang tidak
> tercatat di basis data.

---

## 14. ROADMAP & TAHAPAN PROYEK

Perkiraan dengan tim kecil (2-3 orang full-stack Laravel + Flutter, 1 UI/UX). Fase pengembangan 4 bulan, dilanjutkan peluncuran bertahap.

| Minggu    | Deliverable                         | Detail                                                                                                                                                                  |
| :-------- | :---------------------------------- | :---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **1-2**   | Setup Infrastruktur & Database      | Provisioning server, MySQL 8.0.34+, Redis 7, Firebase project, CI/CD (GitHub Actions + Laravel Forge), inisialisasi proyek Laravel 13 dan Flutter                       |
| **3-4**   | Backend Core: Auth, Profil, Toko    | API registrasi, OTP, CRUD profil, buka toko + endpoint geospasial toko, admin verifikasi toko                                                                           |
| **5-6**   | Frontend: Auth & Profil UI, Peta    | Flutter screen register, home skeleton, integrasi Google Maps, komponen pilih lokasi. Web Laravel landing & halaman katalog SSR (Blade)                                 |
| **7-8**   | Engine 1: Marketplace Katalog       | Backend listing, pencarian full-text, filter radius, frontend feed katalog, detail listing, pemesanan langsung                                                          |
| **9-10**  | Engine 2: Papan Kebutuhan & Bidding | Backend request, broadcast job Redis (Laravel Queue), offer API, notifikasi FCM. Frontend form pasang kebutuhan, halaman permintaan, daftar penawaran, terima penawaran |
| **11-12** | Pesanan, Review & Dispute           | State machine pesanan, integrasi halaman status di mobile, review pasca-selesai, laporan/dispute di admin (Laravel Blade)                                               |
| **13-14** | Admin Dashboard Lanjutan & Web Public | Dashboard analitik, manajemen kategori, penanganan dispute, pengaturan sistem. Web public SEO (landing, halaman listing)                                              |
| **15-16** | Testing, Closed Beta & Perbaikan    | UAT internal, perbaikan bug, closed beta 50 penyedia di 1 kecamatan. Optimasi performa query spasial MySQL                                                              |
| **17-18** | Open Beta & Monitoring              | Rilis ke Play Store/App Store (atau distribusi APK), open beta 3 kecamatan, monitoring crash, analitik KPI                                                              |
| **19-20** | **Buffer: Stabilisasi & Perbaikan** | Menindaklanjuti temuan open beta, perbaikan bug prioritas, penyetelan performa query spasial. **Tidak ada fitur baru.**                                                |
| **21+**   | Iterasi & Monetisasi                | Evaluasi metrik, aktifkan paket langganan dan boost, tambah fitur Fase 2 sesuai prioritas                                                                               |

### Kenapa Ada Buffer 2 Minggu (Minggu 19–20)

UAT internal memang sudah dijadwalkan di minggu 15–16, tetapi itu pengujian
**oleh tim sendiri** sebelum closed beta. Yang belum dialokasikan adalah waktu
menindaklanjuti temuan dari **pengguna sungguhan** di open beta.

| Fase | Siapa yang menguji | Temuan khasnya |
| :-- | :-- | :-- |
| Minggu 15–16 | Tim internal | Bug fungsional, alur yang buntu |
| Minggu 17–18 | 3 kecamatan, pengguna nyata | Perangkat beragam, sinyal lemah, cara pakai di luar dugaan |
| **Minggu 19–20** | — (perbaikan) | Menindaklanjuti keduanya |

Tanpa buffer ini, perbaikan bug open beta akan bertabrakan dengan pekerjaan
monetisasi — dan yang biasanya dikorbankan adalah perbaikan bug.

> ⚠️ **Buffer bukan waktu luang.** Aturannya tegas: **tidak ada fitur baru**
> di minggu 19–20. Kalau ternyata tidak ada bug serius, waktunya dipakai untuk
> menurunkan utang teknis atau menaikkan cakupan pengujian — bukan menarik maju
> pekerjaan Fase 2.
>
> Dua hal yang hampir pasti muncul di open beta dan butuh waktu:
> **query spasial melambat** saat data bertambah (`DATABASE.md` §11), dan
> **perilaku di sinyal lemah** yang tidak terlihat saat diuji di kantor.

### Catatan Urutan Pengerjaan

> ⚠️ **Halaman verifikasi admin tidak boleh menunggu minggu 13.**
> Minggu 3–4 sudah menghasilkan fitur “buka toko”, dan setiap toko lahir dengan
> status `pending`. Tanpa antarmuka verifikasi, **tidak ada satu pun toko yang
> bisa aktif** — seluruh pengujian alur berikutnya (listing, pencarian,
> broadcast) ikut tersumbat.
>
> Karena itu urutannya:
>
> | Minggu | Bagian admin yang dikerjakan |
> | :-- | :-- |
> | **3–4** | Verifikasi pengguna & toko (approve/reject) — minimal, seadanya |
> | **11–12** | Penanganan dispute, mengikuti modul pesanan |
> | **13–14** | Dashboard analitik, manajemen kategori, pengaturan sistem |
>
> Yang digeser ke awal hanya **halaman verifikasi**, bukan seluruh dashboard.
> Alternatif jangka pendek: perintah artisan untuk menyetujui toko, lalu
> diganti antarmuka penuh di minggu 13–14.

**Perkakas CI/CD (minggu 1–2):**

| Kebutuhan | Perkakas | Catatan |
| :-- | :-- | :-- |
| CI (uji & analisis) | **GitHub Actions** | `phpunit`, `pint`, `flutter analyze`, `flutter test` |
| Deploy backend | **Laravel Forge** (alternatif: Ploi) | Deploy otomatis dari branch `main` |
| Build APK/IPA | GitHub Actions + Fastlane | Artefak untuk closed beta |
| Distribusi beta | Firebase App Distribution | Sebelum masuk Play Store |
| Pemantauan galat | Sentry (server) + Crashlytics (mobile) | |

---

## 15. ASUMSI, RISIKO & DEPENDENSI

| Asumsi                                               | Risiko                                                    | Mitigasi                                                                                                   |
| :--------------------------------------------------- | :-------------------------------------------------------- | :--------------------------------------------------------------------------------------------------------- |
| Semua penyedia jasa memiliki smartphone dan nomor WA | Adopsi di kalangan penyedia tua rendah                    | Onboarding manual (petugas kunjungi lapangan), sediakan antarmuka sangat sederhana                         |
| Titik koordinat yang diberikan akurat                | Alamat tidak valid, radius tidak berguna                  | Validasi: jika koordinat di luar kabupaten, tolak. Cross-check reverse geocoding                           |
| Admin mampu memproses verifikasi manual dalam 24 jam | Penumpukan verifikasi menghambat pengguna buka toko       | Siapkan prioritas verifikasi, rekrut admin paruh waktu, pertimbangkan auto-approve dengan AI OCR di fase 2 |
| Regulasi PSE tidak mempersulit operasi               | Pendaftaran PSE lambat atau berbelit                      | Ajukan sejak awal proyek, gunakan badan hukum yang sudah ada                                               |
| COD dan transfer langsung mendominasi                | Sulit melacak GMV akurat karena pembayaran di luar sistem | Meminta konfirmasi manual “Sudah bayar?” dan insentif (kupon) jika melaporkan nilai transaksi              |

---

## 16. LAMPIRAN: WIREFRAME KUNCI & REFERENSI

**Status:** wireframe belum dibuat. Deskripsi di bawah adalah spesifikasi yang
menjadi acuan desainer, bukan pengganti wireframe.

| Berkas | Isi | Status |
| :-- | :-- | :-- |
| Figma – `Seekitar / Wireframe MVP` | 5 alur kunci di bawah | ⏳ Belum dibuat |
| Figma – `Seekitar / Design System` | Komponen dari Brand Guideline §3 | ⏳ Belum dibuat |
| [`assets/brand/logo-grid-construction.svg`](assets/brand/logo-grid-construction.svg) | Konstruksi logo | ✅ Tersedia |

> Tautan Figma ditambahkan ke tabel ini begitu berkasnya dibuat. Menyimpan
> tautan hanya di percakapan tim membuatnya hilang saat anggota berganti.
>
> Prioritas pembuatan mengikuti roadmap: alur **Pasang Kebutuhan** dan
> **Banding Penawaran** dibutuhkan paling awal (minggu 9–10), sedangkan
> Jelajahi dan Pesanan menyusul.

- **Halaman Utama (Jelajahi):** Header dengan search bar, chip filter kategori horizontal, lalu daftar kartu listing dengan foto, nama, jarak, rating. Float action button “Pasang Kebutuhan”.
- **Halaman Pasang Kebutuhan:** Langkah-langkah (stepper) – Kategori → Deskripsi & Foto → Budget → Lokasi & Radius → Konfirmasi.
- **Halaman Permintaan Saya:** Tabs “Aktif”, “Riwayat”. Di dalamnya list permintaan dengan status dan counter penawaran.

  Tiap kartu permintaan menampilkan:

  | Elemen | Isi | Catatan |
  | :-- | :-- | :-- |
  | Judul | `customer_requests.title`, maks 2 baris | |
  | Lencana status | `open` hijau · `closed` abu · `expired` oranye | Warna dari Brand Guideline §3.5.3 |
  | Penghitung penawaran | “3 penawaran” / “Belum ada penawaran” | **Bukan “0 penawaran”** |
  | Sisa waktu | “Berakhir 6 jam lagi” | Dari `expires_at`; merah bila <2 jam |
  | Kategori & radius | “Servis & Bengkel · 15 km” | |
  | Aksi | “Lihat Penawaran” · “Perpanjang” (bila hampir kedaluwarsa) | |

  **Keadaan kosong** ditangani berbeda, karena maknanya berbeda:

  | Keadaan | Tampilan |
  | :-- | :-- |
  | Belum pernah pasang | Ilustrasi + “Pasang kebutuhan pertama Anda” + tombol |
  | Ada, tapi belum ada penawaran | “Menunggu penawaran…” + waktu tunggu berjalan |
  | Kedaluwarsa tanpa penawaran | “Tidak ada penawaran masuk” + tombol “Pasang Ulang” dengan radius lebih luas |

  > Menampilkan “0 penawaran” terasa seperti kegagalan. “Menunggu penawaran…”
  > menyampaikan hal yang sama tanpa membuat pengguna menyerah — penting karena
  > penawaran pertama ditargetkan datang dalam 20 menit (§13), bukan seketika.
- **Halaman Banding Penawaran:** Kartu penawaran bisa di-swipe untuk menolak, atau tap untuk detail lalu “Terima”.
- **Halaman Pesanan:** Timeline status vertikal, menampilkan langkah yang sudah dilalui, tombol aksi sesuai status (misal “Konfirmasi Terima” untuk pembeli).

**Referensi Teknis:**

- MySQL Spatial Documentation: https://dev.mysql.com/doc/refman/8.0/en/spatial-types.html
- Laravel Documentation: https://laravel.com/docs/13.x
- Flutter Documentation: https://docs.flutter.dev
- Riverpod 3 Migration Guide: https://riverpod.dev/docs/3.0_migration

**Dokumen Internal Terkait:**

| Dokumen | Isi |
| :-- | :-- |
| [`TECH_STACK.md`](TECH_STACK.md) | Sumber kebenaran versi & matriks kompatibilitas |
| [`DATABASE.md`](DATABASE.md) | Skema tabel, constraint, keputusan desain |
| [`API_DOCUMENTATION.md`](API_DOCUMENTATION.md) | Kontrak REST API & konvensi global |
| [`Server_Implementation_Guide.md`](Server_Implementation_Guide.md) | Implementasi Laravel & panel admin |
| [`Mobile_Implementation_Guide.md`](Mobile_Implementation_Guide.md) | Implementasi Flutter |
| [`BRANDING-GUIDELINE.md`](BRANDING-GUIDELINE.md) | Identitas visual & verbal |

---
