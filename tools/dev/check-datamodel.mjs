#!/usr/bin/env node
/**
 * Penjaga konsistensi MODEL DATA.
 *
 * Memastikan perbaikan skema tidak diam-diam hilang, dan kontrak API tetap
 * sejalan dengan DATABASE.md.
 *
 * Jalankan:  node tools/dev/check-datamodel.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');

const db = read('DATABASE.md');
const api = read('API_DOCUMENTATION.md');
const prd = read('PRD.md');

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);

/** Ambil blok tabel `### 4.x \`nama\`` sampai heading berikutnya. */
function table(name) {
  const re = new RegExp(`### 4\\.\\d+ \`${name}\`[\\s\\S]*?(?=\\n### |\\n## )`);
  const m = db.match(re);
  return m ? m[0] : '';
}

/**
 * Cek keberadaan BARIS DEFINISI KOLOM, bukan sekadar penyebutan.
 *
 * Tanpa ini, menghapus baris kolom `store_id` tetap lolos karena namanya masih
 * muncul di baris FOREIGN KEY / INDEX pada bab yang sama.
 */
function hasColumnRow(sectionText, column) {
  const bare = column.replace(/`/g, '');
  return sectionText
    .split('\n')
    .some(l => new RegExp(`^\\|\\s*\`${bare}\`\\s*\\|`).test(l.trim()));
}

console.log('Kolom baru wajib ada di DATABASE.md');
const COLUMNS = [
  ['users', ['`ktp_image`', '`selfie_image`']],
  ['stores', ['`rejected_reason`']],
  ['listings', ['listings_price_required_chk', 'listings_qty_slot_chk']],
  ['customer_requests', ['`extended_at`', '`images`']],
  ['offers', ['`expires_at`', '`estimated_hours`']],
  ['orders', ['`order_number`', '`shipping_address`', '`cancelled_at`', '`cancelled_by`', '`payment_proof_url`']],
  ['reviews', ['`store_id`', '`direction`']],
  ['disputes', ['barang_tidak_sesuai', 'pembeli_fiktif']],
];

for (const [tbl, needles] of COLUMNS) {
  const body = table(tbl);
  if (!body) { fail(`tabel ${tbl} tidak ditemukan`); continue; }
  const missing = needles.filter(n =>
    // Nama berbacktick dicek sebagai baris kolom; sisanya (nama constraint)
    // cukup dicek sebagai substring.
    n.startsWith('`') ? !hasColumnRow(body, n) : !body.includes(n)
  );
  if (missing.length) fail(`${tbl} — hilang: ${missing.join(', ')}`);
  else ok(tbl);
}

console.log('\nKeputusan desain yang harus tetap terdokumentasi');
const DECISIONS = [
  ['reviews dua arah (PRD §5.5)', db, ['buyer_to_store', 'store_to_buyer']],
  ['UNIQUE per arah, bukan per order', db, ['reviews_order_direction_unique']],
  ['rating dihitung dari store_id', db, ["where('store_id'"]],
  ['SET store_type dipertahankan', db, ['SET tetap dipertahankan']],
  ['radius toko 5 km ≠ radius request 15 km', db, ['jangan diubah ke 15']],
  ['users.location tetap NULL-able', db, ['bukan NOT NULL']],
  ['categories parent_id RESTRICT', db, ['`parent_id` diubah dari `SET NULL` ke `RESTRICT`']],
  ['bab keputusan yang ditolak', db, ['USULAN YANG DITOLAK & DIKOREKSI']],
];

for (const [label, src, needles] of DECISIONS) {
  const missing = needles.filter(n => !src.includes(n));
  if (missing.length) fail(`${label} — hilang: ${missing.join(', ')}`);
  else ok(label);
}

console.log('\nKontrak API sejalan dengan skema');
const API_SYNC = [
  ['review direction', ['direction', 'buyer_to_store']],
  ['order_number di response', ['order_number', 'SKT-2026']],
  ['shipping_address di create order', ['shipping_address']],
  ['upload bukti transfer', ['payment-proof']],
  ['perpanjang request', ['/extend']],
  ['offer expires_at', ['expires_at']],
];

for (const [label, needles] of API_SYNC) {
  const missing = needles.filter(n => !api.includes(n));
  if (missing.length) fail(`${label} — hilang di API docs: ${missing.join(', ')}`);
  else ok(label);
}

console.log('\nKonsistensi nilai lintas dokumen');

// verification_level hanya 1-3, tidak boleh muncul level 4.
// Baris di tabel keputusan justru MENOLAK level 4, jadi dikecualikan.
const levelHits = db.split('\n').filter(l =>
  /verification_level[^\n]*\b4\b/.test(l) && !l.includes('❌ Ditolak')
);
if (levelHits.length || /Level 4/.test(prd)) {
  fail(`verification_level level 4 muncul — MVP hanya 1–3: ${levelHits[0]?.trim().slice(0, 60) ?? 'di PRD'}`);
} else {
  ok('verification_level tetap 1–3');
}

// Alasan dispute di DATABASE harus sama persis dengan API.
const REASONS = ['barang_tidak_sesuai', 'jasa_tidak_profesional', 'penyedia_tidak_responsif', 'pembeli_fiktif', 'lainnya'];
const missingReason = REASONS.filter(r => !db.includes(r) || !api.includes(r));
if (missingReason.length) fail(`alasan dispute tidak sinkron: ${missingReason.join(', ')}`);
else ok('5 alasan dispute sinkron DATABASE ↔ API');

// Default radius tidak boleh tertukar.
if (!db.includes('DECIMAL(5,2) DEFAULT 5.00')) fail('stores.service_radius_km bukan lagi DEFAULT 5.00');
else ok('stores.service_radius_km DEFAULT 5.00');
if (!db.includes('DECIMAL(5,2) DEFAULT 15.00')) fail('customer_requests.radius_km bukan lagi DEFAULT 15.00');
else ok('customer_requests.radius_km DEFAULT 15.00');

console.log(problems === 0
  ? '\n✅ Model data konsisten.'
  : `\n❌ ${problems} masalah model data ditemukan.`);
if (problems) process.exitCode = 1;
