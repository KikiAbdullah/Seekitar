# 📋 01. PROJECT CHARTER & MASTER PLAN — SEEKITAR

> **Dokumen fondasi proyek.** Semua keputusan besar (scope, budget, jadwal, approval)
> harus merujuk ke dokumen ini. Bersifat **statis** (bukan data aplikasi) — perubahan
> hanya boleh terjadi lewat **Change Request (CR)** yang disetujui kedua pihak (lihat §J).

| **Informasi Dokumen**    | Nilai |
| :----------------------- | :---- |
| **Nama Produk**          | Seekitar — «Yang kamu butuhkan, ada di sekitar.» (Platform Marketplace Hyperlocal Dua Arah Berbasis Geolokasi) |
| **Klien**                | PT Seekitar Digital Nusantara (Bangil, Kabupaten Pasuruan, Jawa Timur) |
| **Platform**             | Mobile App (Flutter, Android/iOS) + REST API & Web (Laravel 13 + Bootstrap 5.3) + Admin Dashboard + WhatsApp Gateway |
| **Versi Dokumen**        | 2.3 (selaras dengan versi bersama dokumen PRD/DATABASE/API/Guide) |
| **Tanggal**              | 29 Juli 2026 (perbarui hanya saat nomor versi berubah) |
| **Status**               | Final — Dasar Kontrak & Eksekusi |
| **Penulis**              | Tim Pengembang Seekitar |
| **Dokumen terkait**      | `PRD.md`, `DATABASE.md`, `API_DOCUMENTATION.md`, `TECH_STACK.md`, `Server_Implementation_Guide.md`, `Mobile_Implementation_Guide.md`, `BRANDING-GUIDELINE.md` |

> ⚠️ **Aturan emas dokumen ini:** angka, batas, dan SLA yang tertulis di sini tidak boleh
> bertentangan dengan PRD (§2.2, §5, §11). Bila ada perbedaan, `PRD.md` adalah sumber
> kebenaran — dan perbedaan itu adalah bug yang harus diperbaiki.

---

## DAFTAR ISI

1. [A. Ringkasan Eksekutif](#a-ringkasan-eksekutif)
2. [B. Tujuan Bisnis (SMART Goals)](#b-tujuan-bisnis-smart-goals)
3. [C. Ruang Lingkup (Scope) — IN vs OUT](#c-ruang-lingkup-scope--in-vs-out)
4. [D. Tim Inti & Kontak](#d-tim-inti--kontak)
5. [E. Aturan Main Komunikasi (Communication Charter)](#e-aturan-main-komunikasi-communication-charter)
6. [F. Budget & Manpower Tracker](#f-budget--manpower-tracker)
7. [G. Master Plan & Roadmap](#g-master-plan--roadmap)
8. [H. Manajemen Risiko Utama](#h-manajemen-risiko-utama)
9. [I. Kontrol Dokumen & Referensi](#i-kontrol-dokumen--referensi)
10. [J. Change Control & Log Keputusan](#j-change-control--log-keputusan)

> Notasi yang dipakai di seluruh dokumen:
> - `[DIISI: …]` — data yang harus diisi/dilengkapi oleh pemilik dokumen (belum tersedia saat penulisan).
> - 🟢 = aman · 🟡 = perlu perhatian · 🔴 = kritis (dipakai pada tracker & risiko).

---

## A. RINGKASAN EKSEKUTIF

### A.1 Ringkasan (3–4 Kalimat Wajib)

> **PT Seekitar Digital Nusantara** menghadapi masalah **ekonomi lokal yang terfragmentasi**:
> informasi penyedia jasa, stok toko kecil, dan peluang sewa tersebar di grup WhatsApp dan
> papan pengumuman fisik, sementara marketplace nasional tidak efisien untuk kebutuhan harian
> (ongkir mahal, tanpa opsi kurir lintas desa). Sebagai solusi, kami membangun **Seekitar**,
> sebuah **platform marketplace hyperlocal dua arah berbasis geolokasi** — aplikasi mobile
> (Flutter) + backend (Laravel 13) + panel admin — yang menghubungkan pembeli dan penyedia
> **hanya dalam radius layanan di Kabupaten Pasuruan**, menggabungkan katalog (barang/jasa/sewa)
> dengan **papan kebutuhan (reverse marketplace)** agar penyedia lokal proaktif menjemput
> permintaan. Setelah rilis, klien mendapatkan ekosistem transaksi lokal yang terpercaya,
> terlacak, dan patuh PSE/UU PDP — dengan basis pengguna awal 500 pengguna, 150 penyedia
> terverifikasi, dan target GMV ≥ Rp50 juta dalam 3 bulan pertama.

### A.2 Detail Pendukung

| Aspek | Isi |
| :---- | :-- |
| **Klien** | PT Seekitar Digital Nusantara — alamat: Bangil, Kabupaten Pasuruan, Jawa Timur (kontak kanal pengaduan: lihat §D.3) |
| **Masalah bisnis yang dipecahkan** | ① Ekonomi lokal terfragmentasi (info tersebar di grup Facebook/WhatsApp/papan fisik); ② Marketplace nasional tak efisien untuk barang berat & jasa dadakan (galon, beras karung, tukang ledeng, sewa tenda/sound); ③ ±80% pelaku UMKM & penyedia jasa hanya mengandalkan pelanggan *walk-in* dan tidak punya saluran digital menemukan permintaan; ④ Transaksi langsung antarpribadi tanpa sistem reputasi → kekhawatiran kualitas & keamanan |
| **Solusi yang ditawarkan** | Platform 3-lapisan: **Mobile App** (Flutter, Android/iOS), **REST API + Web SEO** (Laravel 13 + Bootstrap 5.3), **Admin Dashboard** (Blade), diperkuat **WhatsApp Gateway** (OTP & kontak) dan **Firebase FCM** (push). Model **unified account** — satu akun untuk pembeli, penjual, dan penyedia jasa |
| **Nilai tambah setelah proyek selesai** | ① “Beli & Butuh, semua ada di satu genggaman” — tidak perlu pindah-pindah platform; ② Jangkauan tepat & ongkos minim — hanya penyedia dalam radius yang muncul (geolokasi MySQL Spatial, radius maks. 25 km); ③ “Dari menunggu pembeli menjadi menjemput kebutuhan” — reverse marketplace; ④ Reputasi dua arah yang membangun kepercayaan; ⑤ Kepatuhan regulasi (PSE Kominfo, UU PDP) sebagai pembeda kepercayaan |

### A.3 Nilai Unik (Unique Value Proposition)

1. **Beli & Butuh, Semua Ada di Satu Genggaman** — tidak perlu beralih antara aplikasi nasional dan grup WhatsApp.
2. **Jangkauan Tepat, Ongkos Minim** — hanya penjual/penyedia di sekitar Anda yang muncul.
3. **Dari “Menunggu Pembeli” Menjadi “Menjemput Kebutuhan”** — penyedia jasa proaktif menawarkan solusi ke calon pelanggan yang sudah jelas lokasi & kebutuhannya.

---

## B. TUJUAN BISNIS (SMART GOALS)

Semua tujuan proyek diukur **selama 3 bulan setelah peluncuran publik (open beta)**, kecuali
dinyatakan lain. Target ini hanya bermakna bila asumsi dasarnya terbuka — jika salah satu
asumsi meleset, target **direvisi bersama**, bukan dipaksakan retroaktif.

### B.1 Asumsi Dasar Target 3 Bulan (dari PRD §2.2)

| Asumsi | Nilai | Catatan |
| :----- | :---- | :------ |
| Populasi kabupaten target | 1–2 juta jiwa | Rata-rata kabupaten di Jawa Timur |
| Peluncuran bertahap | 3 kecamatan (bukan 1 kabupaten penuh) | Roadmap fase F (minggu 17–18) |
| Populasi terjangkau efektif | ±150.000 jiwa | 3 kecamatan × ±50.000 jiwa |
| Konversi instal → daftar | 25% | Kisaran umum aplikasi lokal baru |
| Nilai transaksi rata-rata | Rp 250.000 | 200 pesanan × Rp250rb = GMV Rp50 juta |
| Penetrasi yang ditargetkan | 0,33% (500 dari ±150.000) | Konservatif & realistis tanpa anggaran iklan besar |

### B.2 Goals

#### Goal 1 — Aktivasi Ekosistem Dua Arah
**Ekosistem “pasang kebutuhan → terima penawaran” berfungsi nyata, bukan sekadar katalog.**

| Kriteria | Rincian |
| :------- | :------ |
| **S**pesifik | 500 permintaan terpasang (Papan Kebutuhan); >70% di antaranya menerima minimal 1 penawaran (Match Rate). Prioritaskan kedalaman penyedia di sedikit kategori daripada menyebar tipis. |
| **M**easurable | Jumlah permintaan & match rate dari database (`customer_requests` + `offers`), dilaporkan mingguan di dashboard admin. |
| **A**chievable | Di atas populasi terjangkau ±150.000 jiwa; dengan rekrutmen langsung 100 penyedia (closed beta). |
| **R**elevant | Membuktikan nilai unik utama (reverse marketplace) — pembeda utama vs marketplace lain. |
| **T**ime-bound | 100% tercapai ≤ 3 bulan setelah open beta (minggu 17–18). |

#### Goal 2 — Akuisisi Awal Pengguna Beragam
**Basis pengguna heterogen: pembeli, penjual barang, dan penyedia jasa.**

| Kriteria | Rincian |
| :------- | :------ |
| **S**pesifik | 500 pengguna terdaftar; 150 penyedia jasa/toko terverifikasi (Level 2+ / toko `verified`). |
| **M**easurable | Hitungan `users` & `stores` (status `verified`) di dashboard; rasio pelaporan 1 penyedia : 3,3 pengguna. |
| **A**chievable | Hanya lewat **rekrutmen langsung di lapangan** (pasar, bengkel, komunitas UMKM) — bukan pertumbuhan organik; SLA verifikasi 1×24 jam memadai untuk ±2 pengajuan/hari. |
| **R**elevant | Jumlah penyedia adalah faktor kunci likuiditas marketplace. |
| **T**ime-bound | 100% tercapai ≤ 3 bulan setelah open beta. |

#### Goal 3 — Membuktikan Transaksi Lokal Berjalan
**Transaksi COD/transfer langsung terlacak dan berujung ulasan.**

| Kriteria | Rincian |
| :------- | :------ |
| **S**pesifik | GMV ≥ Rp50.000.000 dengan 200 pesanan sukses (`orders.status = selesai`) + pertukaran ulasan. |
| **M**easurable | GMV & jumlah order sukses dari dashboard; rata-rata nilai order ≥ Rp250rb. |
| **A**chievable | 200 pesanan × Rp250rb = Rp50 juta — dihitung dari asumsi konversi & rekrutmen penyedia. |
| **R**elevant | Membuktikan alur transaksi & state machine bekerja end-to-end (pesan → konfirmasi → selesai → ulasan). |
| **T**ime-bound | ≤ 3 bulan setelah open beta; angka dievaluasi ulang pada minggu ke-6. |

#### Goal 4 — Kepatuhan Regulasi & Kepercayaan
**Platform beroperasi sah dan aman secara regulasi.**

| Kriteria | Rincian |
| :------- | :------ |
| **S**pesifik | Tanda daftar **PSE Kominfo** terbit; kanal pengaduan aktif; verifikasi identitas/penyedia berfungsi. |
| **M**easurable | 0 insiden pelanggaran data; 100% penyedia di 3 kecamatan target tervalidasi; SLA tanggapan terkunci: pengaduan ≤2×24 jam, abuse ≤1×24 jam, privasi/UU PDP ≤3×24 jam, keamanan ≤1×24 jam. |
| **A**chievable | Timeline PSE mulai minggu 11 (lihat §G), tanda daftar target minggu 17 sebagai syarat open beta. |
| **R**elevant | Syarat wajib rilis publik (potensi tolak Play Store/App Store bila tidak patuh); pembeda kepercayaan. |
| **T**ime-bound | Tanda daftar PSE ≤ minggu 17; kepatuhan berjalan berkelanjutan. |

#### Goal 5 — Kualitas Teknis Platform (Enabler)
**Platform stabil & aman sehingga target bisnis di atas bisa direalisasikan.**

| Kriteria | Rincian |
| :------- | :------ |
| **S**pesifik | Ketersediaan (uptime) API ≥ 99,5%; SLO pemrosesan background job ≤ 1 menit; OTP terkirim ≤ 30 detik. |
| **M**easurable | Monitoring (Sentry + health check `/healthz`, `/api/status`), metrik uptime, ukur latensi OTP gateway. |
| **A**chievable | Infrastruktur Redis + MySQL + Laravel Queue yang sudah terpasang; WhatsApp Gateway Baileys dengan fallback HTTP. |
| **R**elevant | OTP & notifikasi adalah jalur kritis aktivasi pengguna. |
| **T**ime-bound | Dipertahankan mulai closed beta (minggu 15) hingga 3 bulan setelah rilis. |

---

## C. RUANG LINGKUP (SCOPE) — IN vs OUT

Batas pekerjaan **hitam-putih**. Fitur di luar kolom **OUT** tidak dikerjakan dalam proyek ini
dan menjadi senjata utama melawan *scope creep*. Permintaan penambahan apa pun hanya diproses
via **Change Request** (§J) — termasuk penilaian dampak jadwal & biaya.

### C.1 IN SCOPE (WAJIB dikerjakan sesuai kontrak)

| # | Domain | Cakupan konkret |
| :-: | :----- | :-------------- |
| 1 | **Akun & Autentikasi** | Daftar via nomor HP Indonesia + **OTP WhatsApp**; JWT Bearer (30 hari) + refresh; profil (nama, lokasi pinpoint, foto, alamat pengiriman); ubah nomor HP dua langkah OTP; logika `verification_level` 1–3 (turunan baca-saja) |
| 2 | **Verifikasi Identitas** | Unggah KTP & selfie (opsional di MVP), peninjauan admin 1×24 jam dalam **satu penilaian** (wajah, KTP, alamat, titik domisili); verifikasi toko (data usaha, foto tempat, GPS); status/jejak approve-reject-block (stempel `*_at`/`*_by`, alasan) |
| 3 | **Marketplace Katalog (Jelajahi)** | Listing `product`/`service`/`rental` (foto maks 5, harga, stok/slot, status aktif/sold/hidden); pencarian FULLTEXT + filter (kategori, jenis, harga, rating) + **radius geolokasi maks. 25 km** (setting admin); tampilan peta opsional; wishlist |
| 4 | **Papan Kebutuhan (Reverse Marketplace)** | Pasang permintaan (judul, deskripsi, kategori level-2, foto maks 3, budget fixed/nego, radius default 15 km, 24 jam masa aktif); broadcast geospasial ke penyedia (MySQL Spatial `ST_Distance_Sphere` + `MBRContains`); penawaran 1× per penyedia (harga, estimasi, pesan); pembeli sort `cheapest`/`best_rating`/`nearest`/`fastest`; pilih pemenang → order terbentuk; **privasi data sebelum penawaran diterima** (nama depan, lokasi dibulatkan, tanpa nomor HP) |
| 5 | **Manajemen Toko** | Buka toko (Level 2+); jenis `goods`/`services`/`rental`; hingga 3 subkategori; radius layanan (default 5 km); jam operasional; rekening bank; uniqueness nama per kabupaten; geofencing wilayah target (kode BPS 3514) |
| 6 | **Pesanan & State Machine** | Order langsung (katalog) & dari penawaran; tipe product/service/rental; status terkunci 6 nilai: `menunggu_konfirmasi`→`diproses`→`dikirim`→`selesai`, `dibatalkan`, `dispute`; pembayaran **COD & transfer langsung** (nomor rekening ditampilkan); catatan pembeli; SLO dispute admin 1×24 jam |
| 7 | **Ulasan & Reputasi** | Rating 1–5 + komentar; wajib setelah `selesai` (2 arah: `buyer_to_store` & `store_to_buyer`); maks 1× per arah per order; tidak bisa diubah; rating di bawah 3.0 diflag |
| 8 | **Komunikasi (MVP)** | **Deep link WhatsApp** (pre-filled text, klik dicatat `whatsapp_click`); nomor telepon terbuka dua arah hanya setelah transaksi disepakati |
| 9 | **Admin Dashboard** | Verifikasi KTP/toko; manajemen kategori; moderasi listing; dispute & keputusan blokir; **WhatsApp Gateway** (scan QR, status online/offline, logout, uji kirim); pengaturan sistem (radius maks, kontak PSE, monetisasi) |
| 10 | **Web SEO (Public)** | Halaman landing, katalog publik, blog, kontak & pengaduan, Kebijakan Privasi (UU PDP), Keamanan, Bantuan — server-side rendered |
| 11 | **Notifikasi & Gateway** | Firebase FCM (payload berisi `distance_km`, nilai **string**); WhatsApp gateway (Baileys sidecar) untuk OTP; deep link navigasi per tipe notifikasi |
| 12 | **Monetisasi Fase 2 (data model & API, aktivasi bertahap)** | Struktur `subscriptions` (Pro Rp30rb/bln), Boost listing (Rp7,5rb/7 hari), banner lokal (Rp50rb/hari), service fee Rp1.500 (toggle admin, default off) — data model & API jadi, aktivasi iklan bertahap |
| 13 | **Keamanan & Kepatuhan** | Enkripsi berkas KTP (S3 SSE-KMS), NIK `Crypt::encryptString`; URL verifikasi berumur pendek + log akses; rate limiting 60 req/menit/IP; kunci akun setelah 5× OTP gagal (30 menit); kanal pelaporan PSE aktif; UU PDP: akses/koreksi/hapus data |
| 14 | **Infrastruktur & Quality** | MySQL 8.0.34+ (Spatial, SRID 4326), Redis 7, Laravel Queue (`database`/`redis`), storage local/private yang benar, uji otomatis (unit + feature), dokumentasi konsisten |

### C.2 OUT OF SCOPE (TIDAK dikerjakan pada proyek/fase ini)

| # | Domain | Alasan dikeluarkan | Ganti/kelak (Fase 2+) |
| :-: | :----- | :----------------- | :--------------------- |
| 1 | **Chat in-app real-time** | MVP memakai deep-link WhatsApp | Chat in-app, voice note, panggilan VoIP |
| 2 | **Escrow / in-app wallet** | Butuh **izin PJP Bank Indonesia** — proses panjang, bukan sekadar teknis | Wallet & escrow setelah izin |
| 3 | **Pembayaran QRIS dinamis / gerbang pembayaran** | Bergantung izin & integrasi pihak ketiga | Mikrotransaksi online |
| 4 | **Kurir pihak ketiga & live tracking** | Di luar model COD/transfer lokal | Integrasi kurir + tracking |
| 5 | **Verifikasi KTP otomatis (OCR)** | Akurasi belum terjamin; tetap perlu tinjauan manual | OCR bila skor tinggi + peninjauan manual |
| 6 | **AI rekomendasi penyedia / auto-bidding** | Butuh data historis ≥6 bulan; auto-bidding berisiko merusak kepercayaan | Setelah data & pembatasan anti-spam siap |
| 7 | **Login Google/Apple, biometrik** | Percepatan MVP; akun OTP sudah mencukupi | Fase 2 |
| 8 | **Multi-cabang toko, integrasi POS** | Kompleksitas tinggi | Fase 2 |
| 9 | **Video listing, variasi produk, live streaming, flash sale** | Fokus CPI MVP | Fase 2 |
| 10 | **Penjadwalan slot konkret (`service_slots`)** | MVP: `slot` = kapasitas harian; waktu disepakati via WhatsApp | Fase 2 |
| 11 | **Sistem pengembalian sewa bertahap (denda kondisi)** | Butuh kolom denda, bukti kondisi, alur sengketa | Fase 2 |
| 12 | **Migrasi data dari sistem/platform lain** | Tidak ada data lama yang dibawa (greenfield) | — |
| 13 | **Ekspansi ke luar Kabupaten Pasuruan** | Dikunci satu kabupaten (geofencing BPS 3514) | Fase berikutnya |
| 14 | **Testing perangkat iOS / publikasi App Store** | Lingkungan kerja Windows; publikasi menyusul | Tahap rilis iOS |

### C.3 Batasan & Asumsi Lingkup Penting

1. **Regency tunggal**: wilayah operasi dikunci ke Kabupaten Pasuruan (`regency_code = 3514`). Toko di luar wilayah otomatis ditolak (dengan jalur banding kanal pengaduan).
2. **Radius tiga tingkat — jangan tertukar**: 25 km (batas pencarian), 15 km (default permintaan pembeli), 5 km (radius layanan toko). Broadcast menuntut **keduanya terpenuhi**: toko dalam radius pembeli **dan** pembeli dalam radius toko.
3. **State machine terkunci 6 status** — label UI seperti “Siap Diambil”/“Dijadwalkan” adalah tampilan turunan, bukan status baru.
4. **`verification_level` adalah turunan, bukan kolom** — tidak boleh ditulis ke `users`.
5. **Semua foto KTP/identitas hidup di disk `local` privat**, tidak pernah di disk `public`.
6. **OTP via WhatsApp** memakai gateway Baileys; **BUKAN API resmi** — risiko nomor diblokir, gunakan nomor khusus gateway. (Driver OTP via `log`/`email` tersedia untuk dev.)
7. Perubahan scope = **CR wajib disetujui PIC klien + PM** (§J). Pengerjaan di luar CR dianggap pro-bono/negosiasi ulang — **tidak boleh** menelan man-hours milestone.

---

## D. TIM INTI & KONTAK

### D.1 Tim Internal

> Isi kolom **Nama** & **Kontak** dengan data aktual, lalu hapus tanda `[DIISI]`. Sebarkan
> daftar ini di rapat kickoff dan simpan versi tertandatangani sebagai lampiran kontrak.

| 🧑‍💼/🖼️ | Nama Lengkap | Jabatan | Tanggung Jawab Spesifik | Kontak (WhatsApp) | Kontak (Email) |
| :--: | :----------- | :------ | :---------------------- | :---------------: | :------------- |
| 👨‍💼 | `[DIISI: Nama PM]` | **Project Manager (PM)** | Satu pintu komunikasi dengan klien; jadwal & budget; pengelola CR; laporan mingguan | `[DIISI: 62xxxx]` | `[DIISI: email]` |
| 🛠️ | `[DIISI]` | **Tech Lead / Backend** | Penentu arsitektur teknis; API `/api/v1`; Laravel 13, MySQL Spatial, Redis, otorisasi & keamanan | `[DIISI]` | `[DIISI]` |
| 📱 | `[DIISI]` | **Mobile Developer (Flutter)** | Aplikasi Android/iOS; state `ChangeNotifier`/provider; Dio client; FCM; peta & geolokasi | `[DIISI]` | `[DIISI]` |
| 🖥️ | `[DIISI]` | **Frontend Web & Admin** | Web SEO (Blade/Bootstrap 5.3), panel admin (DataTables 13, SweetAlert2), dashboard WhatsApp Gateway | `[DIISI]` | `[DIISI]` |
| 🤖 | `[DIISI]` | **WhatsApp Gateway / Integrasi** | Node.js sidecar Baileys; sesi & QR; alur OTP; Redis fast-path; keandalan pengiriman | `[DIISI]` | `[DIISI]` |
| 🎨 | `[DIISI]` | **UI/UX Designer** | Wireframe & prototipe (selaras `BRANDING-GUIDELINE.md`); design system; aset visual | `[DIISI]` | `[DIISI]` |
| ✅ | `[DIISI]` | **QA** | Uji unit & fitur; uji end-to-end alur OTP→order→ulasan; checklist kepatuhan; koordinator closed beta | `[DIISI]` | `[DIISI]` |
| ☁️ | `[DIISI]` | **DevOps / Infra** | Deploy, sertifikat, backup, monitoring (Sentry), CI/CD, PSE hosting compliance | `[DIISI]` | `[DIISI]` |

> **Catatan kapasitas:** bila peran DevOps/Integrasi diisi anggota yang merangkap, rinci
> alokasi % waktu di **§F** agar estimasi man-hours jujur.

### D.2 RACI Tugas Kunci

| Keputusan / Hasil | PM | Tech Lead | Backend | Mobile | Web/Admin | QA | Klien (PIC) |
| :---------------- | :-: | :-------: | :-----: | :----: | :-------: | :-: | :---------: |
| Scope kontrak baru (CR) | A | C | C | C | C | C | **A/D** |
| Kontrak API `/api/v1` | C | A | R | C | C | C | I |
| Desain UI/UX final | C | I | I | C | C | C | **A** |
| Jadwal & milestone | R | C | C | C | C | C | A |
| Penerimaan hasil (acceptance) | A | C | C | C | C | C | **R/A** |
| Closed beta / demo | C | C | I | I | I | R | A |

**Legenda:** R = Responsible (mengerjakan) · A = Accountable (bertanggung jawab final) · C = Consulted · D = Decides · I = Informed.

### D.3 Kontak Resmi Perusahaan (untuk footer, halaman legal & formulir PSE)

| Kanal | Alamat | SLA tanggapan wajib |
| :---- | :----- | :------------------ |
| Pengaduan umum | `pengaduan@seekitar.id` | ≤ 2×24 jam |
| Konten ilegal / abuse | `abuse@seekitar.id` | ≤ 1×24 jam |
| Hak subjek data (UU PDP) | `privasi@seekitar.id` | ≤ 3×24 jam |
| Celah keamanan | `security@seekitar.id` | ≤ 1×24 jam |
| WhatsApp | `6281234567890` | Jam operasional |
| Badan usaha | **PT Seekitar Digital Nusantara** — Bangil, Kab. Pasuruan, Jawa Timur | — |

---

## E. ATURAN MAIN KOMUNIKASI (COMMUNICATION CHARTER)

### E.1 Jadwal & Respons

| Hal | Ketentuan |
| :--- | :-------- |
| **Jam kerja tim** | Senin–Jumat, 09.00–17.00 WIB (istirahat 12.00–13.00) |
| **Response time (jam kerja)** | Pertanyaan umum **≤ 2 jam**; prioritas tinggi (bug blokir/urgensi) **≤ 1 jam** via WhatsApp grup |
| **Di luar jam kerja** | Hanya untuk **emergency** (server down, kebocoran data, modul produksi tidak berfungsi). Eskalasi: hubungi **PM** dulu; bila tak terjawab ≤ 30 menit, lanjut ke Tech Lead. Non-urgent menunggu jam kerja |
| **PIC Klien** | **1 nama resmi** `[DIISI: Nama PIC + jabatan]`. Hanya PIC yang berwenang memberikan **persetujuan resmi**. Perubahan/usulan staf klien lain **tidak diproses** tanpa diteruskan & disetujui PIC |
| **Laporan mingguan (progress report)** | Setiap **Jumat 16.00 WIB** — email + ringkasan: progres vs jadwal, blocker, risiko, perubahan scope, metrik (lihat §B), man-hours (§F) |
| **Demo rutin** | **Setiap 2 minggu sekali** (kecuali minggu laporan) — kanal: screen share / APK-build terbaru / panel admin |
| **Kanal komunikasi harian** | WhatsApp grup proyek (internal), WhatsApp dengan PIC (resmi eksternal). Email resmi untuk laporan & dokumen kontraktual |
| **Kickoff** | Meeting kickoff wajib dihadiri seluruh tim + PIC klien; agenda: penetapan dokumen ini, jadwal, dan kanal |

### E.2 Aturan Persetujuan & Dokumentasi

1. Persetujuan hard-copy/digital resmi hanya dari **PIC klien** (dan PM untuk sisi internal). Persetujuan lisan **harus** dikuatkan email dalam 1 hari kerja.
2. Setiap keputusan tercatat di **Log Keputusan** (§J.2) — siapa, kapan, memutuskan apa.
3. Semua dokumen produk memakai **versi bersama 2.3** — perubahan kontrak API/skema = mayor, fitur baru = minor, perbaikan penulisan = tanpa naik versi (§I).
4. Aset & deskripsi fitur yang tampil di UI berpedoman ke `BRANDING-GUIDELINE.md` (bahasa Indonesia menghadap pengguna; istilah teknis mengikuti `TECH_STACK.md` §6).
5. **Sekali disepakati, dokumen ini statis.** Setiap pelanggaran itu sendiri adalah CR.

---

## F. BUDGET & MANPOWER TRACKER

> Angka berikut adalah **contoh isian** yang harus disempurnakan dengan nilai kontrak
> sebenarnya (`[DIISI]`). Rumus & metodologi di bawah adalah yang berlaku — angka boleh berubah,
> metodenya tidak.

### F.1 Angka Kunci

| Indikator | Nilai | Status | Catatan |
| :-------- | :---- | :----: | :------ |
| **Total Budget Proyek** | `[DIISI: Rp xxx.000.000]` *(contoh: Rp 280.000.000)* | 🟢 | Sesuai kontrak; tidak termasuk biaya berulang (server, PSE, iklan) |
| **Total Estimasi Man-Hours (alokasi terjual)** | **2.000 jam** | 🟢 | Dari breakdown §F.2 |
| **Man-Hours Terpakai (s.d. minggu ini)** | `0` (perbarui mingguan) | 🟢 | Diisi QA/PM tiap Jumat |
| **Sisa Man-Hours** | `2.000 − terpakai` | 🟢 | Status: ≥25% tersisa = 🟢 · 10–25% = 🟡 · <10% = 🔴 |

> ⚠️ **Aturan baku:** *“Jika man-hours habis sebelum fitur selesai, dilakukan **negosiasi
> tambahan biaya** — bukan menambah jam gratis. Fitur belum selesai dan man-hours tersisa < 10%
> → wajib rapat eskalasi dengan PIC klien sebelum akhir minggu yang sama.”*

### F.2 Breakdown Estimasi Man-Hours (Contoh 2.000 jam)

| Workstream | Alokasi (jam) | % | Pemilik | Sisa akhir M1 (contoh) |
| :--------- | :-----------: | :-: | :------ | :--------------------- |
| Backend API & geolokasi | 520 | 26% | Tech Lead/Backend | — |
| Mobile App (Flutter) | 520 | 26% | Mobile Dev | — |
| Web SEO & Admin Dashboard | 280 | 14% | Frontend Web | — |
| WhatsApp Gateway & integrasi | 120 | 6% | Integrasi | — |
| UI/UX Design | 200 | 10% | UI/UX | — |
| QA & closed beta | 240 | 12% | QA | — |
| PM & laporan | 80 | 4% | PM | — |
| DevOps/Infra & deployment | 40 | 2% | DevOps | — |
| **Total** | **2.000** | **100%** | — | → diisi §F.3 |

### F.3 Tracker Mingguan (contoh format)

| Minggu | Terpakai (jam) | Kumulatif (%) | Burn rate | Sisa | Status | Catatan / Blocker |
| :----: | -------------: | :-----------: | :-------: | :--: | :----: | :---------------- |
| M1 (3–7 Agu) | 320 | 16% | 320 | 1.680 | 🟢 | Environment & setup CI selesai |
| M2 | 340 | 33% | 330 | 1.340 | 🟢 | Kontrak API KD-1 disetujui PIC |
| … | — | — | — | — | — | — |

Format mingguan per status: 🟢 on track / 🟡 risiko (isi mitigasi) / 🔴 off track (isi eskalasi + rencana remedial).

### F.4 Catatan Finansial

- **Biaya berulang (di luar budget proyek):** hosting MySQL/Redis, domain, S3/minio, FCM, SMS cadangan, gateway WhatsApp (nomor khusus). Rinci `[DIISI]` di lampiran kontrak.
- **Pembayaran milestone** mengikuti §G (gate). Tidak ada milestone yang ditagih tanpa acceptance tertulis PIC klien.
- Setiap **CR** (§J) menyertakan estimasi jam & dampak budget; CR disetujui secara tertulis sebelum dikerjakan.

---

## G. MASTER PLAN & ROADMAP

> Sumber jadwal bisnis: PRD §14 (roadmap 18 minggu). Milestone teknis disusun agar setiap gate
> bisa diuji & di-acceptance PIC.

### G.1 Fase & Milestone

| Fase | Minggu | Deliverable kunci | Gate / Acceptance |
| :--- | :----: | :---------------- | :---------------- |
| **F0 — Inisiasi & Setup** | 1–2 | Charter disetujui; lingkungan dev (Laragon PHP 8.3, MySQL 8, Redis); repo & CI; spesifikasi kontrak API KD-1 | Kickoff + charter ditandatangani |
| **F1 — Desain** | 3–4 | Wireframe & prototipe UI/UX; skema DB final; set variabel lingkungan; rencana test | Look & feel disetujui PIC (demo 1) |
| **F2 — Backend Core** | 5–8 | Autentikasi OTP + JWT; profil & verifikasi; toko; listings; geolokasi (Spatial); `customer_requests` + offers + broadcast; state machine order; ulasan/dispute | Demo 2: alur “pasang kebutuhan → terima penawaran → order” jalan end-to-end |
| **F3 — Mobile App** | 7–11 | Semua layar MVP Flutter; FCM; peta & lokasi; semua integrasi API; offline banner | APK alpha jalan di perangkat riil (Motorola/emulator) |
| **F4 — Web SEO & Admin** | 8–11 | Landing, katalog publik, blog, halaman legal (privasi/keamanan/help/kontak); panel admin lengkap + dashboard WhatsApp Gateway | Admin dapat memoderasi & memutus verifikasi/dispute |
| **F5 — QA & Closed Beta Prep** | 11–14 | Uji unit+fitur; uji end-to-end; performa; keamanan; **mulai pendaftaran PSE (minggu 11–13 ajukan, 13–16 proses)**; checklist Go/No-Go | QA sign-off; bug kritis = 0 open |
| **F6 — Closed Beta** | 15–16 | Rekrutmen langsung 100 penyedia; uji coba lapangan di 3 kecamatan; monitoring metrik §B; settlement umpan balik | Match rate & transaksi riil tercatat |
| **F7 — Open Beta / Rilis** | 17–18 | **Tanda daftar PSE terbit (syarat rilis);** peluncuran bertahap 3 kecamatan; penanganan support & pengaduan; target penetrasi 0,33% dalam 3 bulan | Go-live + pengukuran 3 bulan (§B) |

### G.2 Prinsip Penjadwalan

- **Jalur kritis:** Backend Core (F2) → integrasi Mobile (F3) → QA (F5). Keterlambatan F2 menggeser seluruh jadwal.
- **PSE tidak boleh ditunda**: mulai prasyarat minggu 11 (akta, NPWP, kebijakan privasi final, kanal pengaduan aktif). Android/iOS store menolak aplikasi legal tidak lengkap.
- Setiap minggu Jumat: laporan §E; setiap 2 minggu: demo PIC.
- Rollover pekerjaan antar-minggu harus disetujui PM; dampak ke milestone ditulis eksplisit di laporan.

---

## H. MANAJEMEN RISIKO UTAMA

| # | Risiko | Prob. | Dampak | Mitigasi | Pemilik |
| :-: | :----- | :----: | :----: | :------- | :------ |
| R1 | **Likuiditas rendah** — permintaan tak dapat penawaran → ekosistem mati | Tinggi | Kritis | Prioritas kedalaman penyedia di sedikit kategori; rekrutmen langsung; target Match Rate ≥75% diukur tiap minggu; penyesuaian radius/budget | PM + QA |
| R2 | **Nomor gateway WhatsApp diblokir** (Baileys bukan API resmi) | Tinggi | Sedang | Nomor khusus gateway; fallback driver `log`/`email`; monitor status `/api/status`; SOP recovery & re-scan | Integrasi |
| R3 | **PSE / izin terlambat** (tanda daftar tak terbit saat jadwal) | Sedang | Kritis | Mulai minggu 11; checklist prasyarat; kanal email sudah aktif sebelum formulir | PM |
| R4 | **Dua proses gateway berebut memakai sesi yang sama** → offline terus-menerus | Sedang | Sedang | Hanya satu instans (PM2/README troubleshoot); validasi saat deploy; log `prekey bundle` | Integrasi |
| R5 | **Kebocoran/penyalahgunaan data pribadi (KTP, lokasi)** | Rendah | Kritis | Enkripsi by-design (SSE-KMS, NIK encrypted); akses berjejak; pola «terbuka bertahap» nomor & lokasi; tes keamanan di F5 | Tech Lead |
| R6 | **Scope creep** (fitur masuk di luar kontrak) | Sedang | Sedang | Charter §C + CR §J; demo tidak pernah otomatis = komitmen fitur baru | PM |
| R7 | **Konversi rendah** (<25% instal→daftar) | Sedang | Sedang | Alur OTP cepat; dukungan non-tech (persona Pak Yanto); SOP dukungan jam kerja; perbaiki pain point dari umpan balik beta | Mobile + PM |
| R8 | **Dependency MySQL Spatial / PHP 8.3** (env lokal PHP 7.4) | Rendah | Sedang | Standarisasi lingkungan (Laragon `php-8.3`), cek konsistensi `TECH_STACK.md`, CI build di tiap push | Tech Lead |

---

## I. KONTROL DOKUMEN & REFERENSI

### I.1 Riwayat & Versi

| Versi | Tanggal | Perubahan | Oleh |
| :---- | :------ | :-------- | :--- |
| 1.0 | `[DIISI]` | Draf awal charter | PM |
| 2.3 | 29 Jul 2026 | Selaras dengan versi dokumen produk (PRD 2.3) | Tim |

**Aturan versi bersama** (`TECH_STACK.md` §6A): perubahan kontrak API/skema → **mayor**;
penambahan fitur/endpoint → **minor**; perbaikan penulisan → tanpa naik versi. Perbarui tanggal
hanya saat nomor versi berubah.

### I.2 Peta Referensi

| Dokumen | Sumber kebenaran untuk |
| :------ | :--------------------- |
| `PRD.md` | Produk, fitur, target bisnis, model monetisasi |
| `DATABASE.md` | Skema, kolom, constraint, SPATIAL |
| `API_DOCUMENTATION.md` | Kontrak endpoint `/api/v1`, format respons |
| `TECH_STACK.md` | Versi semua paket, konfigurasi env |
| `Server_Implementation_Guide.md` / `Mobile_Implementation_Guide.md` | Panduan implementasi |
| `BRANDING-GUIDELINE.md` | Materi merek, terminologi UI, halaman legal |
| `CONTRIBUTING.md` | Standar kontribusi kode (perintah QA dsb.) |

---

## J. CHANGE CONTROL & LOG KEPUTUSAN

### J.1 Alur Change Request (CR)

```
Usulan CR (siapa pun, tertulis)
   → PM: registrasi di Log (nomor CR-tanggal)
   → Analisa dampak: scope | jadwal | budget (jam & rupiah)
   → Review teknis oleh Tech Lead
   → Persetujuan resmi: PIC klien + PM (tertulis/email ≤ 5 hari kerja)
   → Naikkan versi dokumen (bila kontrak terpengaruh), rilis ke semua pihak
   → Dikerjakan dalam milestone berikutnya yang tersedia (tidak pernah nyerobot pekerjaan di milestone berjalan)
```

Ketentuan:
- CR tanpa analisa dampak tidak dijadwalkan/mengubah jadwal.
- Fitur di kolom **OUT (C.2)** yang diminta klien = otomatis **CR besar** (tulis estimasi penuh).
- Setiap CR baru mengubah baseline §F (budget) & §G (jadwal) hanya setelah persetujuan; sebelum itu baseline tetap.

### J.2 Log Keputusan (Decision Log)

| # | Tanggal | Keputusan | Alasan | Pemutus | Status |
| :-: | :------ | :-------- | :----- | :------ | :----: |
| D1 | 29 Jul 2026 | Versi bersama dokumen = 2.3 | Konsistensi seluruh panduan | Tim | ✅ |
| D2 | 29 Jul 2026 | Target 3 bulan = 500 pengguna / 150 penyedia / GMV Rp50jt | Lihat §B.1 asumsi | Tim + `[PIC]` | ✅ |
| D3 | `[DIISI]` | CR-… | … | … | ⏳ |
| … | … | … | … | … | … |

---

## LAMPIRAN — DAFTAR PERIKSA PERSETUJUAN

| Pihak | Nama & Tanda Tangan | Tanggal |
| :---- | :------------------ | :-----: |
| **Klien (PIC)** — PT Seekitar Digital Nusantara | `[DIISI]` | `[DIISI]` |
| **Project Manager (Tim)** | `[DIISI]` | `[DIISI]` |

> 📌 Setelah ditandatangani, dokumen ini menjadi lampiran kontrak. Perubahan apa pun hanya
> melalui **CR (§J)**. Simpan versi di lokasi berbagi repositori proyek (root `Seekitar/`)
> bersama PRD & dokumen lainnya.