#!/usr/bin/env node
/**
 * Penjaga konsistensi PRD.md.
 *
 * Fokusnya: PRD tidak boleh menjanjikan hal yang tidak ada di skema/API,
 * dan angka-angkanya harus selaras dengan dokumen lain.
 *
 * Jalankan:  node tools/dev/check-prd.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');

const prd = read('PRD.md');
const db = read('DATABASE.md');
const api = read('API_DOCUMENTATION.md');
const sig = read('Server_Implementation_Guide.md');

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);
const check = (label, needles, src = prd) => {
  const missing = needles.filter(n => !src.includes(n));
  if (missing.length) fail(`${label} — hilang: ${missing.join(', ')}`);
  else ok(label);
};

console.log('Asumsi & definisi');
check('asumsi target 3 bulan (#153)', ['Asumsi di Balik Target 3 Bulan', 'penetrasi 0,33%']);
check('cara menemukan keahlian penyedia (#154)', ['teknisi AC lepas” ditemukan']);
check('istilah Fase 2 dijelaskan (#155)', ['Penjelasan Istilah Fase 2', 'Auto-bidding']);
check('arti slot = kapasitas harian (#156)', ['kapasitas per hari, bukan jadwal']);
check('tiga angka radius dibedakan (#157)', ['Tiga angka radius yang berbeda']);

console.log('\nSkema yang dijanjikan PRD harus ada di DATABASE');
const PROMISED = [
  ['#158 slot waktu jasa', 'service_slots'],
  ['#164 npwp', 'npwp'],
  ['#165 regency', 'regency'],
  ['#171 SLA dispute', 'response_deadline'],
  ['#177 biaya tambahan offer', 'additional_cost'],
  ['#183 langganan', 'subscriptions'],
];
for (const [label, col] of PROMISED) {
  if (!db.includes(col)) fail(`${label} — kolom/tabel '${col}' tidak ada di DATABASE.md`);
  else ok(label);
}

console.log('\nStatus pesanan & alur');
check('pemetaan label UI ke ENUM (#167-169)', ['bukan nilai kolom', 'Siap Diambil']);
check('sewa: Dikembalikan bukan ENUM (#169)', ['Tidak ada nilai ENUM `dikembalikan`']);

// Label UI tidak boleh diperlakukan sebagai nilai ENUM baru.
const VALID_STATUS = ['menunggu_konfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan', 'dispute'];
const bogus = ['siap_diambil', 'dijadwalkan', 'dikembalikan', 'penyedia_mulai', 'dimulai']
  .filter(v => new RegExp(`\`${v}\``).test(db));
if (bogus.length) fail(`nilai ENUM tidak sah muncul di DATABASE.md: ${bogus.join(', ')}`);
else ok('ENUM orders.status tetap 6 nilai');
VALID_STATUS.forEach(v => { if (!db.includes(v)) fail(`status '${v}' hilang dari DATABASE.md`); });

console.log('\nPrivasi & kontrak API');
check('data pembeli disaring (#162)', ['dibulatkan ke kelurahan', 'Nomor telepon']);
check('parameter sorting offer (#163)', ['best_rating', 'Pengurutan **wajib di sisi server**']);
check('geofencing kabupaten (#166)', ['service_areas', 'ST_Contains']);
check('distance_km di payload notifikasi (#174, #181)', ['distance_km', 'harus berupa string']);
check('store_id di response offer (#180)', ['store-789']);

// #177: total = price + additional_cost harus konsisten di PRD & API.
if (prd.includes('additional_cost') && !api.includes('additional_cost')) {
  fail('additional_cost ada di PRD tapi belum di API_DOCUMENTATION.md');
} else ok('additional_cost tersinkron PRD ↔ API');

console.log('\nOperasional');
check('timeline PSE (#182)', ['Timeline pendaftaran PSE', 'minggu 11']);
check('rumus KPI (#184, #185)', ['Rumus Perhitungan', 'match_rate_pct', 'median']);
check('perkakas CI/CD (#186)', ['Laravel Forge', 'Fastlane']);
check('urutan admin verifikasi (#187)', ['tidak boleh menunggu minggu 13']);
check('lampiran wireframe (#189)', ['Belum dibuat', 'Dokumen Internal Terkait']);

console.log('\nKonsistensi lintas dokumen');

// Versi di tautan referensi harus mengikuti stack sebenarnya.
if (/laravel\.com\/docs\/1[0-2]\./.test(prd)) {
  fail('tautan dokumentasi Laravel masih menunjuk versi lama (proyek memakai 13.x)');
} else ok('tautan dokumentasi Laravel 13.x');

// PRD §8 tidak boleh lagi menyebut comma-separated untuk category_ids.
if (/category_ids.*comma-separated/i.test(prd)) {
  fail('PRD masih menyebut category_ids comma-separated (#176) — seharusnya JSON');
} else ok('category_ids konsisten JSON (#176)');

// Default radius tidak boleh tertukar antar dokumen.
const radiusOk =
  db.includes('DECIMAL(5,2) DEFAULT 5.00') &&
  db.includes('DECIMAL(5,2) DEFAULT 15.00') &&
  prd.includes('25 km');
if (!radiusOk) fail('default radius (5/15/25 km) tidak konsisten PRD ↔ DATABASE');
else ok('tiga default radius konsisten');

// Broadcast dua arah harus terdokumentasi di Server Guide: toko dalam radius
// pembeli (radius_km) DAN pembeli dalam radius layanan toko (service_radius_km).
if (!/service_radius_km/.test(sig) || !/radius_km/.test(sig)) {
  fail('pencocokan broadcast dua arah hilang dari Server_Implementation_Guide.md (#160, #161)');
} else ok('pencocokan broadcast dua arah (#160, #161)');

// Level verifikasi tetap 1-3 di PRD.
if (/Level 4/.test(prd)) fail('PRD menyebut Level 4 — MVP hanya 1–3');
else ok('level verifikasi 1–3');

console.log(problems === 0
  ? '\n✅ PRD konsisten.'
  : `\n❌ ${problems} masalah ditemukan.`);
if (problems) process.exitCode = 1;
