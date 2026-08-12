# 📋 03. SPRINT & TASK MANAGEMENT — SEEKITAR

> **Fungsi halaman:** menghubungkan Notion dengan alat task management utama
> (**Jira / Linear**). Halaman ini **BUKAN tempat membuat task baru** — semua task
> dikelola & di-update di board. Notion hanya **menampilkan ringkasan & tautan** agar
> stakeholder (klien, PM, eskalasi) punya satu titik pandang tanpa masuk Jira/Linear.
>
> Rujukan: `01-PROJECT_CHARTER_MASTER_PLAN.md` (§E komunikasi, §F budget, §G roadmap) dan
> `02-DISCOVERY_REQUIREMENTS_BRD.md` (user stories & estimasi).

| **Informasi Dokumen** | Nilai |
| :--------------------- | :---- |
| **Produk** | Seekitar — Marketplace Hyperlocal Dua Arah |
| **Versi Dokumen** | 2.3 (versi bersama) |
| **Tanggal** | 12 Agustus 2026 |
| **Status** | Aktif — dikoreksi setiap Sprint Planning |
| **Alat task management** | `[DIISI: Jira / Linear]` |
| **Pemilik halaman** | PM |

---

## 1. LINK MENUJU BOARD (JIRA / LINEAR)

> ➤ **Di Notion:** gunakan blok **`/embed`** (pratinjau langsung) atau **`/bookmark`**
> (kartu tautan) untuk meletakkan board di bawah. Setiap kali board dipindahkan/berganti repositori,
> perbarui tautan di sini — dokumen lain cukup merujuk ke bagian ini.

```
[DIISI: https://your-project.atlassian.net/jira/software/... ]   ← ganti lalu pakai /embed
[DIISI: https://linear.app/... ]                                  ← ganti lalu pakai /bookmark
```

| Board | Tautan | Scope project | Akses |
| :---- | :----- | :------------ | :---- |
| **Backlog (Product Backlog)** | `[DIISI]` | Semua user story dari BRD §B (epic E1–E12) + task teknis | Semua tim |
| **Sprint Board (aktif)** | `[DIISI]` | Task sprint berjalan | Semua tim |
| **Bug / QA Board** | `[DIISI: atau project kedua]` | Defect hasil QA & staging | Dev + QA |
| **Roadmap (timeline view)** | `[DIISI]` | Epic vs fase G (roadmap) | PM + Tech Lead (view) |

### Aturan pemakaian board (wajib)

1. **Task hanya dibuat di Jira/Linear** — di Notion cukup link ini. Task cadangan yang terlanjur
   ditulis di Notion harus segera di-transfer dan dicoret.
2. **Satu task = satu unit kerja yang bisa dicek DoD** (BRD §C). Task besar dipecah sampai
   bisa selesai dalam ≤½ sprint.
3. **Status baku**: `Backlog → To Do → In Progress → Code Review → Testing (QA) → Done`,
   plus `Blocked` (wajib ditulis alasan eskalasi ke PM ≤1 hari kerja).
4. **Estimasi memakai Story Point (SP)** — skala Fibonacci: `0, 0.5, 1, 2, 3, 5, 8, 13`.
   Task teknikal murni juga diestimasi SP agar chart velocity konsisten.
5. **Label wajib**: `epic:E#`, `prioritas:Must/Should/Could`, `area:backend/mobile/web-admin/infra`,
   `doD:done-checked` (otomatis saat status Testing selesai).
6. **Kecepatan prioritas** mengikuti MoSCoW BRD §B: cerita **Must** selalu menimpa **Should**/**Could**
   saat kapasitas menipis.

---

## 2. INFORMASI SPRINT SAAT INI

> Cadence: **2 minggu/sprint**, selaras demo rutin PIC (charter §E). Pemetaan fase roadmap
> (charter §G) ke sprint dihitung mundur dari Go-Live (F7, minggu 17–18).

### 2.1 Sprint Aktif

| Kolom | Nilai |
| :---- | :---- |
| **Nomor Sprint** | **Sprint 1** *(dari target 9 sprint; sesuaikan saat berjalan)* |
| **Fase roadmap** | F0–F1 (Inisiasi & Desain) |
| **Tanggal mulai** | Senin, **3 Agustus 2026** |
| **Tanggal selesai** | Jumat, **14 Agustus 2026** |
| **Hari kerja efektif** | 10 hari (kurangi libur & meeting: ±8 hari pengerjaan) |
| **Goal sprint (satu kalimat)** | Kontrak API KD-1 disetujui, environment dev standar (PHP 8.3/MySQL 8/Redis) siap, wireframe UI/UX F1 selesai & disetujui PIC |
| **Sprint Backlog** | `[DIISI: link filter sprint-1 di board]` |
| **Hasil (Sprint Review)** | `[DIISI: isi setelah review]` |

### 2.2 Kalender Sprint (Template 2 Minggu)

| Hari | Aktivitas |
| :--- | :-------- |
| Senin (Sprint start) | Sprint Planning (seleksi backlog, set goal); update Notion §2 |
| Kamis | Refinement singkat (estimasi cerita 1–2 sprint ke depan) |
| Jumat minggu 1 | Progress check + **Laporan Mingguan ke PIC** (charter §E) |
| Kamis minggu 2 | QA window — semua task sprint di-test |
| Jumat minggu 2 | **Sprint Review (demo PIC)** + Retrospective; update Velocity §3 & Sprint info §2.1 |

### 2.3 Daftar Sprint & Target (Roadmap → Sprint)

| Sprint | Fase | Mulai | Selesai | Fokus / target utama |
| :----- | :--- | :----: | :-----: | :------------------- |
| S1 | F0–F1 | 3 Agu 2026 | 14 Agu 2026 | Kontrak API KD-1; env standar; wireframe |
| S2 | F1–F2 | 17 Agu 2026 | 28 Agu 2026 | Skema DB final; autentikasi OTP; profil |
| S3 | F2 | 31 Agu 2026 | 11 Sep 2026 | Toko & verifikasi; listings; geolokasi |
| S4 | F2 | 14 Sep 2026 | 25 Sep 2026 | Papan kebutuhan + broadcast + penawaran |
| S5 | F2–F3 | 28 Sep 2026 | 9 Okt 2026 | State machine order; mobile MVP screen |
| S6 | F3–F4 | 12 Okt 2026 | 23 Okt 2026 | Mobile integrasi API; web SEO & legal; admin |
| S7 | F5 | 26 Okt 2026 | 6 Nov 2026 | QA & hardening; mulai pendaftaran PSE |
| S8 | F6 | 9 Nov 2026 | 20 Nov 2026 | Closed beta & rekrutmen 100 penyedia |
| S9 | F7 | 23 Nov 2026 | 4 Des 2026 | Open beta bertahap 3 kecamatan; rilis |

> **Catatan:** tanggal di atas adalah contoh baseline dari charter §G (18 minggu). Nilai resmi
> yang mengikat adalah **hasil Sprint Planning** — mintalah PM menyetujui revisi tanggal tiap
> kali bergeser, lalu perbarui tabel §2.3 & Laporan Mingguan.

---

## 3. RATA-RATA VELOCITY TIM

> **Velocity** = total SP yang **berhasil diverifikasi DoD (di-status Done)** pada suatu sprint,
> bukan SP yang “dikerjakan”. Dipakai untuk menghitung kapasitas sprint berikutnya
> (last-3 average / moving average).

### 3.1 Skala & Konversi Acuan (BRD §B ↔ SP)

Perkiraan kasar agar estimasi konsisten antar-dev (BRD memakai jam, Jira memakai SP):

| SP | Jam usaha (±) | Contoh |
| :-: | :----------- | :----- |
| 0.5 | ±1–2 | Fix typo, teks pre-filled |
| 1 | ±3–4 | Endpoint query sederhana + validasi |
| 2 | ±8 | CRUD resource kecil + test dasar |
| 3 | ±12–16 | Fitur inti + izin & validasi negatif |
| 5 | ±20–24 | Fitur kompleks (broadcast geospasial) |
| 8 | ±32–40 | Epic pecahan (state machine penuh) |
| 13 | — | **Harus dipecah** dulu |

> Total BRD ±1.550 jam ≈ **±390 SP** (dengan 1 SP ≈ 4 jam) — dipakai sebagai patokan
> kurva sisa kapasitas (charter §F).

### 3.2 Ringkasan Velocity

| Kolom | Nilai |
| :---- | :---- |
| **Sprint cadence** | 2 minggu |
| **Kapasitas teoretis** (±) | `[DIISI: SP — dari survei tim, mis. 80]` |
| **Velocity rata-rata (last 3 sprint)** | `[DIISI: mis. 62 SP/sprint — segera isi setelah S1]` |
| **Sprint pertama (target awal)** | 60–70 SP (belum ada data historis; pakai estimasi) |
| **Formula sisa** | `Sisa SP BRD (≈390) ÷ velocity = sisa sprint → bandingkan dgn jadwal §2.3` |

### 3.3 Tabel Pelacakan Velocity

| Sprint | SP Commit | SP Selesai (Done-DoD) | Velocity (rata 3) | Catatan |
| :----- | :-------: | :-------------------: | :---------------: | :------ |
| S1 | `[DIISI]` | `0` | — | Sprint sedang berjalan → masih `0` |
| S2 | `[DIISI]` | `[DIISI]` | — | |
| S3 | `[DIISI]` | `[DIISI]` | `[..]` | Baru bisa dihitung setelah S3 |
| … | | | | |

> **Baca grafik:** bila `SP Selesai` konsisten < commit 2× berturut-turut → potong kapasitas
> commit di sprint berikutnya (1 sprint diperlambat). Bila <70% MoSCoW **Must** berisiko,
> aktifkan urutan pengorbanan BRD §B.1 dan naikkan ke Laporan Mingguan PIC.

### 3.4 Burndown & Goal Check

- **Burndown chart**: dipantau PM tiap hari kerja di board (link §1). Task `Blocked` > 1 hari
  wajib dinyatakan di stand-up.
- **Eskalasi**: bila burndown menyimpang >15% dari garis ideal di 2 hari berturut-turut, PM
  melakukan re-plan internal sebelum demo.
- Data & template grafik velocity dapat disimpan di Notion sub-halaman `[DIISI: link subpage]`
  sebagai lampiran halaman ini.

---

## 4. STAND-UP & KETENTUAN PENUTUP

| Hal | Ketentuan |
| :--- | :-------- |
| **Daily stand-up** | Singkat (≤15 mnt), sinkron isi board: kemarin → hari ini → blocker |
| **Kanal stand-up** | `[DIISI: WhatsApp grup / Slack / meet]` |
| **Satu tujuan halaman ini** | Hanya *pintu masuk* ke Jira/Linear — tidak ada task yang dibuat/diperbarui di sini |
| **Perubahan di halaman ini** | PM yang memegang; stakeholder lain minta lewat PM (charter §E) |
| **Penyelarasan DoD** | Sekali task berstatus `Done` = seluruh checklist BRD §C tercentang. Kalau ada keraguan, QA berhak pull back ke `In Progress` |

---

## LAMPIRAN — CEK SINKRON (Tiap Sprint Review)

| Uji | Hasil |
| :--- | :----: |
| Link board §1 masih valid & terbuka? | ☐ |
| Sprint info §2.1 mencerminkan sprint terakhir (nomor, tanggal)? | ☐ |
| Semua `Done` = DoD checklist BRD §C? | ☐ |
| Velocity §3.3 ter-update setelah demo? | ☐ |
| Laporan mingguan charter §E mengutip angka dari halaman ini? | ☐ |
| Sprint berikutnya sudah masuk kalender §2.3? | ☐ |