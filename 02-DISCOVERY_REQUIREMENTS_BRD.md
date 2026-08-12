# 📋 02. DISCOVERY & REQUIREMENTS (BRD) — SEEKITAR

> **Fungsi halaman:** menjembatani keinginan klien dengan eksekusi teknis — spesifikasi
> kebutuhan yang menjadi bahan baku sprint/task. Disusun selaras dengan `PRD.md` (sumber
> kebenaran produk) dan `01-PROJECT_CHARTER_MASTER_PLAN.md` (scope, versi & kebutuhan).

| **Informasi Dokumen** | Nilai |
| :--------------------- | :---- |
| **Produk** | Seekitar — Marketplace Hyperlocal Dua Arah (Kabupaten Pasuruan, kode BPS 3514) |
| **Versi Dokumen** | 2.3 (versi bersama dokumen) |
| **Tanggal** | 29 Juli 2026 |
| **Status** | Final — Dasar Pengembangan |
| **Dokumen terkait** | `01-PROJECT_CHARTER_MASTER_PLAN.md` · `PRD.md` · `TECH_STACK.md` · `BRANDING-GUIDELINE.md` |

---

## A. USER PERSONA (SIAPA YANG PAKAI?)

> Persona adalah **orang fiktif representatif**, bukan keinginan tim. Setiap keputusan desain
> & dev diuji dengan pertanyaan: *“Apakah keputusan ini membantu Budi / Yanto / Siti?”*
> Sumber: `PRD.md` §3 (target pengguna & persona).

### A.1 Persona 1 — Pembeli / Pengguna Jasa (Umum)

| Atribut | Isi |
| :------ | :-- |
| **🧑‍💼 Nama & Profil** | **Budi Santoso, 35 tahun** — karyawan swasta, menetap di Kab. Pasuruan |
| **Pekerjaan/Jabatan** | Karyawan swasta (kantor), bisa dari rumah/mencari kebutuhan harian |
| **Tingkat Digital** | Terbiasa WhatsApp & Facebook; **kurang nyaman** dengan marketplace nasional yang rumit |
| **Pemicu memakai aplikasi** | Butuh barang berat/jasa mendadak di rumah tanpa tahu kontak penjual terdekat |
| **Pain Points** | ① Tidak tahu kontak tukang/penyedia terdekat (tukang ledeng, AC, dll.); ② Ragu kualitas penyedia tanpa referensi/reputasi; ③ Enggan tawar-menawar & repot; ④ Takut ditipu transaksi langsung antarpribadi |
| **Goals** | ① Menemukan barang/jasa terdekat **dengan cepat**; ② Membandingkan harga & reputasi lokal; ③ Mendapat layanan terpercaya tanpa keluar rumah; ④ Memasang kebutuhan lebih spesifik dan memilih penawar terbaik |
| **Konteks utama** | Pagi: cek penawaran baru untuk permintaannya di “Permintaan Saya”; sore: browsing “Jelajahi” listing terdekat; malam: selesaikan pembayaran COD/transfer |
| **Tidak untuk persona ini** | Flow yang butuh login Google/Apple, mekanisme escrow, atau chatbot AI — terlalu tinggi untuk MVP |

### A.2 Persona 2 — Penyedia Jasa (Sisi Pasok Utama)

| Atribut | Isi |
| :------ | :-- |
| **🛠️ Nama & Profil** | **Pak Yanto, 45 tahun** — teknisi AC lepas, bergantung pada pesanan musiman |
| **Pekerjaan/Jabatan** | Teknisi AC / teknisi perbaikan rumah tangga (penyedia jasa informal) |
| **Tingkat Digital** | Punya smartphone Android, aktif di WhatsApp, **gaptek** terhadap aplikasi rumit |
| **Pemicu memakai aplikasi** | Ingin pelanggan baru konsisten tanpa biaya iklan besar; ingin "dibisiki" saat ada permintaan servis di dekatnya |
| **Pain Points** | ① Waktu luang terbuang sia-sia; ② Pelanggan mencari tapi tidak menemukan kontaknya; ③ Takut ditipu pelanggan fiktif; ④ Bosan iklan / gaya promosi yang rumit |
| **Goals** | ① Menerima notifikasi **“Permintaan Baru”** di radius layanannya; ② Mengirim penawaran dalam 2–3 ketukan; ③ Menampilkan reputasi & riwayat transaksi sukses; ④ Menerima pesanan yang bisa dikerjakan kapan dia mau (estimasi waktu bebas) |
| **Konteks utama** | Terima push notifikasi saat memasang AC pelanggan; buka detail permintaan (tanpa HP pembeli dulu — data terlindungi); kirim penawaran harga+estimasi; setelah diterima, lihat kontak pembeli & kerjakan; minta ulasan |
| **Prasyarat teknis MVP** | Perlu tingkat **Level 2 (verifikasi KTP)** untuk buka toko; dikelompokkan lewat kategori toko (mis. “Servis & Bengkel → AC”) karena MVP tak punya field keahlian terpisah (`PRD.md` §3.2) |
| **Tidak untuk persona ini** | UI rumit, dashboard analitik, penjadwalan slot per-jam |

### A.3 Persona 3 — Penjual Barang / Penyewa (Sisi Pasok Dagang)

| Atribut | Isi |
| :------ | :-- |
| **🏪 Nama & Profil** | **Bu Siti, 40 tahun** — pemilik toko kelontong sekaligus merental alat (tenda, sound, dll.) |
| **Pekerjaan/Jabatan** | Pemilik usaha mikro (toko kelontong + sewa) |
| **Tingkat Digital** | Aktif WA; belum pernah jualan online selain grup |
| **Pemicu memakai aplikasi** | Jangkauan toko fisik cuma tetangga; ingin pelanggan “mager” tetap bisa beli dari rumah |
| **Pain Points** | ① Ongkos kirim platform nasional mahal untuk barangnya; ② Platform lain **tidak punya fitur sewa**; ③ Update stok/ketersediaan sendiri terasa rumit |
| **Goals** | ① Katalog sederhana yang bisa di-update stok sendiri; ② Menampilkan radius layanan (mis. 5 km) & jam operasional; ③ Menerima pesanan langsung (COD) dari “Jelajahi”; ④ Menyewakan barang dengan kalender ketersediaan sederhana |
| **Konteks utama** | Pagi: ganti stok galon/beras di listing; siang: terima order COD dari tetangga; sore: tandai sewa tenda “disewa—dikembalikan—selesai”; malam: balas penawaran & lihat tagihan |
| **Tidak untuk persona ini** | Multi-cabang, POS, variasi warna/ukuran, video listing (Fase 2) |

> **Ringkasan untuk tim desain:** mayoritas audiens adalah **pengguna WhatsApp
> aktif tapi non-geek** → setiap alur harus: maksimal 3 langkah inti, bahasa Indonesia
> sederhana, huruf besar & kontras baik, tanpa meminta akun baru yang rumit, dan nomor penting
> selalu tampak (WA).

---

## B. USER STORY MAPPING (PRIORITAS FITUR)

> Setiap cerita ditulis dengan format **“Sebagai [peran], saya bisa [aksi] agar [manfaat].”**
> Prioritas memakai **MoSCoW**:
>
> - **Must** = wajib ada di MVP (tanpa ini produk cacat) → masuk **Rilis 1.0**
> - **Should** = sebaiknya ada; bisa menyusul dalam gelombang rilis jika waktu menipis
> - **Could** = bagus jika ada, tidak menjanjikan
> - **Won't** = **tidak dikerjakan dalam proyek/fase ini** (lihat OUT pada charter §C.2)
>
> Estimasi jam adalah **kasar** untuk sprint planning (baseline 2.000 man-hours pada charter §F).
> Estimasi dihitung sebagai upaya dev+QA (tanpa desain/PM), sekali kerjaan.

### B.1 Ringkasan Prioritas per Epic

| Epic | #Cerita | Must | Should | Could | Won't | Estimasi total (jam) |
| :---- | :-: | :-: | :-: | :-: | :-: | :-: |
| E1 Autentikasi & Profil | 10 | 9 | 1 | 0 | 0 | ±150 |
| E2 Verifikasi Identitas & Toko | 8 | 7 | 1 | 0 | 0 | ±130 |
| E3 Toko & Katalog (Jelajahi) | 10 | 9 | 1 | 0 | 0 | ±200 |
| E4 Papan Kebutuhan & Penawaran | 12 | 11 | 1 | 0 | 0 | ±240 |
| E5 Pesanan & State Machine | 10 | 9 | 1 | 0 | 0 | ±180 |
| E6 Ulasan, Reputasi & Dispute | 6 | 6 | 0 | 0 | 0 | ±100 |
| E7 Komunikasi WhatsApp | 4 | 3 | 1 | 0 | 0 | ±40 |
| E8 Notifikasi (FCM + Gateway) | 5 | 4 | 1 | 0 | 0 | ±70 |
| E9 Admin Dashboard | 10 | 9 | 1 | 0 | 0 | ±180 |
| E10 Web SEO & Legal | 7 | 6 | 1 | 0 | 0 | ±110 |
| E11 Monetisasi Fase 2 (data model) | 5 | 2 | 2 | 1 | 0 | ±60 |
| E12 Infrastruktur & Kualitas | 5 | 5 | 0 | 0 | 0 | ±90 |
| E13 (Fase 2) Chat in-app, escrow, OCR, dll. | 5 | 0 | 0 | 0 | 5 | — *(Won't)* |
| **Total (cerita MVP)** | **≈ 92** | **80** | **11** | **1** | — | **±1.550 jam** |

> Estimasi ±1.550 jam untuk cerita + buffer 450 jam (≈22%) = 2.000 jam baseline charter §F.
> **Aturan prioritas:** cerita **Must** yang belum selesai menimpa semua cerita Should/Could.

### B.2 Cerita Detail per Epic

#### E1 — Autentikasi & Profil

| # | Epic | User Story | Prioritas | Est (jam) |
| :-: | :--- | :--------- | :-------: | :-: |
| 1.1 | E1 | Sebagai pengguna baru, saya bisa mendaftar dengan nomor HP + **OTP WhatsApp** agar akun saya terbukti milik saya tanpa kata sandi | **Must** | 14 |
| 1.2 | E1 | Sebagai pengguna, saya bisa menerima OTP ke email/log (mode dev) bila gateway WhatsApp sedang down, agar registrasi tidak macet | **Must** | 6 |
| 1.3 | E1 | Sebagai pengguna, saya bisa memverifikasi OTP dan langsung menerima **JWT** agar dapat memakai API | **Must** | 12 |
| 1.4 | E1 | Sebagai pengguna, saya bisa membuat/memperbarui profil (nama, avatar, lokasi pinpoint, alamat pengiriman) | **Must** | 16 |
| 1.5 | E1 | Sebagai pengguna, saya bisa login kembali saat token kedaluwarsa dengan **refresh token otomatis** (Dio interceptor) | **Must** | 10 |
| 1.6 | E1 | Sebagai pengguna, saya bisa logout dan mem-blacklist JWT agar sesi tidak bisa dipakai lagi | **Must** | 6 |
| 1.7 | E1 | Sebagai pengguna, saya bisa **ganti nomor HP** lewat OTP dua langkah agar akun tetap aman | **Must** | 18 |
| 1.8 | E1 | Sebagai pengguna, saya tahu level verifikasi saya (1/2/3) dan akses fitur yang dibuka tiap level | **Must** | 12 |
| 1.9 | E1 | Sebagai pengguna, saya bisa menyetujui kebijakan privasi & syarat pada pendaftaran (UU PDP) | **Must** | 6 |
| 1.10 | E1 | Sebagai pengguna, saya bisa memakai biometrik/login Google agar lebih cepat | Should | 20 *(bisa digeser)* |

#### E2 — Verifikasi Identitas & Toko

| # | Epic | User Story | Prioritas | Est (jam) |
| :-: | :--- | :--------- | :-------: | :-: |
| 2.1 | E2 | Sebagai pengguna, saya bisa **unggah KTP + selfie** dan menunggu tinjauan admin (SLA 1×24 jam) | **Must** | 16 |
| 2.2 | E2 | Sebagai admin, saya bisa menyetujui/menolak verifikasi dalam **satu penilaian** (wajah, KTP, alamat, titik domisili) dan menulis alasan | **Must** | 20 |
| 2.3 | E2 | Sebagai pengguna, saya bisa mengirim banding bila verifikasi ditolak | **Must** | 8 |
| 2.4 | E2 | Sebagai pengguna, saya bisa **buka toko** (nama unik per kabupaten, jenis goods/services/rental, kategori, radius, jam operasional, rekening) | **Must** | 24 |
| 2.5 | E2 | Sebagai admin, saya bisa memverifikasi toko (data + lokasi GPS + geofencing 3514) dengan alur approve/reject/block + alasan | **Must** | 22 |
| 2.6 | E2 | Sebagai sistem, berkas KTP dienkripsi & cuma bisa diakses admin lewat URL berumur pendek yang dicatat | **Must** | 16 |
| 2.7 | E2 | Sebagai pengguna Level 3, saya bisa menambah NPWP opsional & foto tempat usaha untuk badge Pro | Should | 14 |
| 2.8 | E2 | Sebagai sistem, stempel verifikasi dicatat (siapa-menandai-kapan) & tak bisa diubah bebas | **Must** | 10 |

#### E3 — Toko & Katalog (Jelajahi)

| # | Epic | User Story | Prioritas | Est (jam) |
| :-: | :--- | :--------- | :-------: | :-: |
| 3.1 | E3 | Sebagai penjual Level 2+, saya bisa membuat listing `product` (stok, harga tetap) | **Must** | 16 |
| 3.2 | E3 | Sebagai penyedia jasa, saya bisa membuat listing `service` (slot/hari, harga tetap atau “mulai dari”) | **Must** | 14 |
| 3.3 | E3 | Sebagai penjual, saya bisa membuat listing `rental` (harga per hari/minggu, ketersediaan) | **Must** | 16 |
| 3.4 | E3 | Sebagai penjual, saya bisa mengunggah foto (maks 5) & mengompres sebelum kirim | **Must** | 10 |
| 3.5 | E3 | Sebagai penjual, saya bisa nonaktifkan/tandai terjual listing | **Must** | 6 |
| 3.6 | E3 | Sebagai pembeli, saya bisa **mencari** listing berdasarkan teks (FULLTEXT) + filter kategori/jenis/harga/rating | **Must** | 24 |
| 3.7 | E3 | Sebagai pembeli, saya bisa melihat listing **terdekat** dalam radius maks 25 km (geolokasi) | **Must** | 20 |
| 3.8 | E3 | Sebagai pembeli, saya bisa melihat detail listing: galeri, profil toko mini, rating, peta jarak, tombol Pesan/Tanya/Simpan | **Must** | 18 |
| 3.9 | E3 | Sebagai pembeli, saya bisa memasukkan listing ke **wishlist** | **Must** | 6 |
| 3.10 | E3 | Sebagai pembeli, saya bisa beralih ke tampilan peta untuk melihat sebaran listing | Should | 16 |

#### E4 — Papan Kebutuhan (Reverse Marketplace) & Penawaran

| # | Epic | User Story | Prioritas | Est (jam) |
| :-: | :--- | :--------- | :-------: | :-: |
| 4.1 | E4 | Sebagai pembeli, saya bisa **memasang kebutuhan** (judul, deskripsi, kategori level-2, foto ≤3, budget fixed/nego, lokasi, radius default 15 km) | **Must** | 18 |
| 4.2 | E4 | Sebagai sistem, saya melakukan **broadcast geospasial** (MBRContains + ST_Distance_Sphere) ke penyedia cocok | **Must** | 24 |
| 4.3 | E4 | Sebagai penyedia, saya mendapat notifikasi **“Permintaan Baru (+jarak km)”** dengan payload jarak terhitung server | **Must** | 10 |
| 4.4 | E4 | Sebagai penyedia, saya melihat detail permintaan **tanpa data pribadi pembeli** (nama depan, lokasi dibulatkan, tanpa HP) | **Must** | 14 |
| 4.5 | E4 | Sebagai penyedia, saya bisa **mengirim penawaran** (harga, biaya tambahan opsional, estimasi, pesan) — maks 1 aktif per permintaan | **Must** | 16 |
| 4.6 | E4 | Sebagai pembeli, saya bisa mengurutkan penawaran (cheapest/best_rating/nearest/fastest) — sorting **di server** | **Must** | 12 |
| 4.7 | E4 | Sebagai pembeli, saya bisa **menerima penawaran**; status jadi accepted/closed & order terbentuk | **Must** | 16 |
| 4.8 | E4 | Sebagai pembeli, saya bisa memperpanjang permintaan yang mendekati kadaluarsa (24 jam) | **Must** | 6 |
| 4.9 | E4 | Sebagai sistem, permintaan tanpa penawaran menjadi `expired` + notifikasi | **Must** | 8 |
| 4.10 | E4 | Sebagai pembeli, saya melihat “Permintaan Saya”; sebagai penyedia “Kebutuhan Sekitar” | **Must** | 20 |
| 4.11 | E4 | Sebagai admin, saya bisa memantau permintaan tanpa penawaran untuk gap pasokan | Should | 12 |
| 4.12 | E4 | Sebagai penyedia, saya bisa mengirim ulang penawaran bila pembeli mengubah/memperbarui permintaan (revisi) | **Must** | 8 |

#### E5 — Pesanan & State Machine

| # | Epic | User Story | Prioritas | Est (jam) |
| :-: | :--- | :--------- | :-------: | :-: |
| 5.1 | E5 | Sebagai pembeli, saya bisa **pesan langsung** dari listing (quantity, catatan, COD/transfer, metode ambil/antar) | **Must** | 18 |
| 5.2 | E5 | Sebagai penjual, saya bisa menerima/menolak pesanan (menunggu konfirmasi) | **Must** | 8 |
| 5.3 | E5 | Sebagai penjual, saya bisa mengubah status diproses / dikirim (label UI diturunkan dari status+tipe+metode) | **Must** | 16 |
| 5.4 | E5 | Sebagai pembeli, saya bisa konfirmasi terima → `selesai` | **Must** | 8 |
| 5.5 | E5 | Sebagai pembeli, saya bisa membatalkan sebelum `diproses` | **Must** | 6 |
| 5.6 | E5 | Sebagai sistem, saya menegakkan transisi status yang sah (state machine; transisi ilegal ditolak 409/422) | **Must** | 20 |
| 5.7 | E5 | Sebagai pembeli/penjual, saya bisa melaporkan masalah → status `dispute` dan admin menindak ≤1×24 jam | **Must** | 14 |
| 5.8 | E5 | Sebagai sistem, saya menghitung & mengirim `total_amount` (harga+biaya tambahan) dari server | **Must** | 8 |
| 5.9 | E5 | Sebagai pengguna, saya bisa melihat riwayat & detail order (termasuk akses kontak lawan saat pesanan disepakati) | **Must** | 16 |
| 5.10 | E5 | Sebagai pembeli jasa, saya bisa mengatur jadwal pengerjaan (catat estimasi) | Should | 10 |

#### E6 — Ulasan, Reputasi & Dispute

| # | Epic | User Story | Prioritas | Est (jam) |
| :-: | :--- | :--------- | :-------: | :-: |
| 6.1 | E6 | Sebagai pembeli/penjual, saya bisa memberi rating+ulasan hanya setelah `selesai` (maks 1× per arah per order) | **Must** | 14 |
| 6.2 | E6 | Sebagai sistem, ulasan **tidak dapat disunting** & dihitung ke rating toko (1 desimal) | **Must** | 8 |
| 6.3 | E6 | Sebagai sistem, toko dengan rating <3.0 diflag untuk tinjauan admin | **Must** | 6 |
| 6.4 | E6 | Sebagai admin, saya bisa menindak dispute dan menulis resolusi | **Must** | 12 |
| 6.5 | E6 | Sebagai pembeli/penjual, saya bisa mengajukan dispute dengan alasan yang tersedia | **Must** | 10 |
| 6.6 | E6 | Sebagai admin, saya bisa memblokir pengguna/toko (bagian dispute & moderasi) | **Must** | 10 |

#### E7 — Komunikasi WhatsApp

| # | Epic | User Story | Prioritas | Est (jam) |
| :-: | :--- | :--------- | :-------: | :-: |
| 7.1 | E7 | Sebagai pembeli, saya bisa **“Hubungi via WhatsApp”** dengan teks pre-filled (deep link `wa.me`) | **Must** | 8 |
| 7.2 | E7 | Sebagai sistem, saya mencatat klik `whatsapp_click` untuk analitik | **Must** | 6 |
| 7.3 | E7 | Sebagai sistem, nomor telepon hanya terbuka setelah transaksi disepakati (tanpa pembocoran di papan kebutuhan) | **Must** | 12 |
| 7.4 | E7 | Sebagai pembeli, saya mendapat peringatan untuk tidak bertransaksi di luar platform sebelum kesepakatan | Should | 3 |

#### E8 — Notifikasi (FCM + WhatsApp Gateway)

| # | Epic | User Story | Prioritas | Est (jam) |
| :-: | :--- | :--------- | :-------: | :-: |
| 8.1 | E8 | Sebagai sistem, saya mengirim push FCM dengan payload `type/screen/entity_id/distance_km` (string) + deep link | **Must** | 18 |
| 8.2 | E8 | Sebagai pengguna, saya bisa menerima notifikasi saat latar belakang (firebase_messaging + local notifications saat foreground) | **Must** | 14 |
| 8.3 | E8 | Sebagai admin, saya bisa melihat **WhatsApp Gateway** status online/offline + scan QR + logout + uji kirim | **Must** | 16 |
| 8.4 | E8 | Sebagai sistem, OTP terkirim ≤30 detik lewat gateway Baileys (HTTP/Redis fast-path) | **Must** | 12 |
| 8.5 | E8 | Sebagai sistem, saya mendeteksi gateway offline & fallback driver (log/email) agar alur registrasi tak tersendat | Should | 10 |

#### E9 — Admin Dashboard

| # | Epic | User Story | Prioritas | Est (jam) |
| :-: | :--- | :--------- | :-------: | :-: |
| 9.1 | E9 | Sebagai admin, saya bisa login dengan email+sandi (sesi Laravel, role & permission Spatie) | **Must** | 12 |
| 9.2 | E9 | Sebagai admin, saya bisa menyetujui/menolak verifikasi KTP (daftar antrean, detail berkas) | **Must** | 20 |
| 9.3 | E9 | Sebagai admin, saya bisa menyetujui/menolak toko | **Must** | 12 |
| 9.4 | E9 | Sebagai admin, saya bisa mengelola kategori (taxonomy tree) | **Must** | 10 |
| 9.5 | E9 | Sebagai admin, saya bisa memoderasi listing (aktif/sembunyikan) | **Must** | 10 |
| 9.6 | E9 | Sebagai admin, saya bisa menindak dispute & memblokir user/toko | **Must** | 14 |
| 9.7 | E9 | Sebagai admin, saya bisa mengelola pengaturan sistem (radius maks, kontak PSE, monetisasi) | **Must** | 12 |
| 9.8 | E9 | Sebagai admin, saya bisa kelola halaman web (blog, kebijakan) | **Must** | 10 |
| 9.9 | E9 | Sebagai admin, saya bisa lihat metrik CP (pengguna, penyedia, permintaan, penawaran, GMV, match rate) | Should | 18 |
| 9.10 | E9 | Sebagai super-admin, saya bisa kelola akun & permission staf | **Must** | 12 |

#### E10 — Web SEO & Legal

| # | Epic | User Story | Prioritas | Est (jam) |
| :-: | :--- | :--------- | :-------: | :-: |
| 10.1 | E10 | Sebagai pengunjung, saya bisa membuka halaman publik (Beranda, Cari, Tentang, Untuk Penjual, Bantuan, Blog, Kontak) yang SEO-friendly & responsif | **Must** | 20 |
| 10.2 | E10 | Sebagai publik, saya bisa melihat Kebijakan Privasi (UU PDP) & Pusat Keamanan | **Must** | 12 |
| 10.3 | E10 | Sebagai publik, saya bisa mengirim pesan via formulir kontak/pengaduan (masuk antrean admin) | **Must** | 10 |
| 10.4 | E10 | Sebagai publik, saya bisa melihat katalog publik (listing terbaik) | **Must** | 14 |
| 10.5 | E10 | Sebagai admin, saya bisa mengelola blog | **Must** | 10 |
| 10.6 | E10 | Sebagai sistem, saya menyajikan Open Graph/kanonikal & akselerasi font agar SEO optimal | Should | 8 |
| 10.7 | E10 | Sebagai sistem, saya menampilkan nomor PSE & kanal resmi di footer sebelum rilis | **Must** | 4 |

#### E11 — Monetisasi (data model & API, aktivasi bertahap)

| # | Epic | User Story | Prioritas | Est (jam) |
| :-: | :--- | :--------- | :-------: | :-: |
| 11.1 | E11 | Sebagai sistem, saya menyimpan langganan Pro (Rp30rb/bulan) & boost (Rp7,5rb/7 hari) | **Must** (model) | 16 |
| 11.2 | E11 | Sebagai penyedia, saya bisa upgrade Pro & melihat badge di profil | Could (aktivasi fase 2) | 14 |
| 11.3 | E11 | Sebagai sistem, saya menyimpan transaksi iklan banner (Rp50rb/hari) | Should (model) | 10 |
| 11.4 | E11 | Sebagai sistem, saya punya toggle service fee (Rp1.500, default off) | Should | 8 |
| 11.5 | E11 | Sebagai admin, saya bisa melihat pendapatan/pengaturan harga monetisasi | **Must** | 12 |

#### E12 — Infrastruktur & Kualitas

| # | Epic | User Story | Prioritas | Est (jam) |
| :-: | :--- | :--------- | :-------: | :-: |
| 12.1 | E12 | Sebagai pengembang, saya bisa deploy ke staging & prod (CI/CD) tanpa langkah manual yang rawan error | **Must** | 20 |
| 12.2 | E12 | Sebagai pengembang, saya bisa memantau error dengan Sentry & uptime jadi `healthz`/`api/status` | **Must** | 12 |
| 12.3 | E12 | Sebagai sistem, saya menerapkan rate limiting & proteksi input di semua endpoint API | **Must** | 10 |
| 12.4 | E12 | Sebagai pengembang, saya punya test otomatis (unit + feature) yang berjalan di CI | **Must** | 30 |
| 12.5 | E12 | Sebagai sistem, saya membackup database & storage sesuai kebijakan | **Must** | 12 |

#### E13 — Fase 2 (Won't di proyek ini)

| # | Epic | User Story | Prioritas |
| :-: | :--- | :--------- | :-------: |
| 13.1 | E13 | Sebagai pembeli, saya bisa chat real-time in-app (bukan deep-link WA) | Won't |
| 13.2 | E13 | Sebagai pembeli, saya bayar via escrow/wallet (butuh izin PJP BI) | Won't |
| 13.3 | E13 | Sebagai pengguna, verifikasi KTP otomatis OCR | Won't |
| 13.4 | E13 | Sebagai penyedia, saya diurutkan AI rekomendasi & auto-bidding | Won't |
| 13.5 | E13 | Sebagai pengguna, saya masuk dengan Google/Apple/biometrik | Won't |

> **Catatan pemotongan cerdas:** bila waktu menipis, urutan pengorbanan yang disarankan PM
> (tanya PIC saat demo setiap 2 minggu): 1) fitur **Should** → gelombang berikutnya; 2) demi
> menjaga **Must E4 (papan kebutuhan)** dan **E1 (OTP)** tetap utuh — keduanya adalah pembeda produk.

---

## C. DEFINISI "SELESAI" (DEFINITION OF DONE)

> Berlaku **seragam untuk semua cerita/PR** — dev, desain, dan QA memakai checklist yang sama.
> Sebuah cerita **belum dianggap selesai** selama satu pun poin belum terpenuhi.

### C.1 Checklist DoD (Dev)

- [ ] **① Kode di-review oleh Tech Lead** — minimal 1 reviewer selain penulis; tidak ada komentar terbuka yang menghalangi.
- [ ] **② Tidak ada error** di console (client), log backend (local/staging), maupun Sentry baru dari perubahan ini.
- [ ] **③ Sudah di-deploy ke server Staging** dan diverifikasi berjalan (url staging + akun staging aktif).
- [ ] **④ Sudah di-test QA (positif & negatif)** — skenario utama positif jalan, skenario negatif (validasi, izin, konflik status) ditolak dengan benar.
- [ ] **⑤ Sudah di-approve PM** — ditampilkan/dikonfirmasi ke PIC klien lewat screenshot/demo; persetujuan tertulis di Log Keputusan (§J charter).
- [ ] **⑥ Tidak merusak kontrak API/DB/versi** — bila skema/endpoint berubah, `API_DOCUMENTATION.md`/`DATABASE.md` diupdate & versi bersama naik.
- [ ] **⑦ Kepatuhan UU PDP** — tidak ada data pribadi/KTP ditampilkan tanpa mode “terbuka bertahap”; akses ke berkas privat dicatat.

### C.2 DoD Khusus Desain (UI/UX) & QA

| Aspek | Standar |
| :---- | :------ |
| **Desain** | Kontras huruf ≥ WCAG AA; bahasa menghadap pengguna Indonesia (terminologi §I.2 charter); empati persona masuk (Budi/Yanto/Siti); aset sesuai `BRANDING-GUIDELINE.md` |
| **QA fungsional** | Uji alur kritis: OTP→profil→buka toko→listing→pasang kebutuhan→penawaran→order→selesai→ulasan; hasil dicatat (test case + hasil) |
| **QA regresi** | Suite otomatis minimal untuk kontrak API inti jalan di CI (`php artisan test`) |
| **Kinerja** | Latensi API lokal ≤ 1 dtk p50; OTP sampai **≤ 30 dtk**; FCM tiba dalam hitungan menit |
| **Keamanan** | Pada cerita yang menyentuh autentikasi/data: cek izin (role/permission), tidak ada secret di repo, input tervalidasi |

### C.3 Kriteria "Done" untuk Milestone (Gate Demo)

Satu milestone dinyatakan selesai bila: **semua cerita Must** pada epic-nya berstatus DoD ✅,
tidak ada bug kritikal terbuka, dan hasil demo PIC dicatat di Log Keputusan.

---

## D. LINK DESAIN & FLOWCHART

> Kolom ini diisi pada kickoff/stage F1 (Desain). Sebelum asset final tersedia, tautan boleh
> kosong (`[DIISI]`) — tapi keputusan desain **tidak boleh menunggu**: wireframe low-fi sudah
> harus muncul di Laporan Mingguan mulai minggu M4.

| Asset | Tautan | Akses | Catatan |
| :---- | :----- | :---- | :------ |
| **🎨 Figma — Design System & UI Mobile** | `[DIISI: link Figma]` | `[DIISI]` | Toko komponen (button, card, form), warna hero `#168A4A`, 5 tab bawah, layar MVP |
| **🎨 Figma — Admin Dashboard (web)** | `[DIISI: link Figma]` | `[DIISI]` | Template Modernize + DataTables; wajib mencakup QR gateway |
| **🗺️ Flow (alur) — Draw.io / Miro** | `[DIISI: link Miro]` | `[DIISI]` | Alur wajib: registrasi-OTP, pasang kebutuhan→penawaran→order, state machine order, dispute |
| **🗄️ ERD — Database** | `[DIISI: link ERD / dbdiagram]` | `[DIISI]` | Sumber skema: `DATABASE.md` §4; khusus **POINT SRID 4326** & **SPATIAL INDEX** |
| **📱 Prototipe (mobile)** | `[DIISI: link prototipe]` | `[DIISI]` | Prototipe tapable untuk uji persona Budi/Yanto/Siti |
| **🧭 Referensi alur teknis** | `seekitar_mobile/FLOWS.md` · `Server_Implementation_Guide.md` §14 (broadcast, state machine) | internal | Dipakai tim, bukan klien |

> **Saran pengelolaan:** hak akses (editor vs viewer) dibatasi per peran (lihat RACI charter
> §D.2). Tautan yang berubah-ubah dirilis ulang lewat Laporan Mingguan, bukan di sini —
> dokumen ini bersifat statis.

---

## LAMPIRAN — DAFTAR PERIKSA KESEPAKATAN BRD

| Uji | Hasil |
| :--- | :----: |
| Semua cerita **Must** ⊆ scope IN (charter §C.1)? | ☐ ya · ☐ tidak → **CR wajib** |
| Tidak ada cerita Won't yang masuk MVP? | ☐ ya · ☐ tidak → **CR wajib** |
| Estimasi total (≈1.550) ≤ baseline budget available (2.000)? | ☐ |
| Persona jumlah & profilenya disetujui oleh PIC? | ☐ |
| DoD dipakai sebagai dasar acceptance di setiap milestone? | ☐ |
| Tanggal setuju: `[DIISI]` — ditandatangani PIC klien `[DIISI]` & PM `[DIISI]` | ☐ |