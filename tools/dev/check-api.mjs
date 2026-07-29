#!/usr/bin/env node
/**
 * Penjaga kelengkapan & konsistensi API_DOCUMENTATION.md.
 *
 * Jalankan:  node tools/dev/check-api.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');

const api = read('API_DOCUMENTATION.md');
const db = read('DATABASE.md');

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);

const check = (label, needles, src = api) => {
  const missing = needles.filter(n => !src.includes(n));
  if (missing.length) fail(`${label} — hilang: ${missing.join(', ')}`);
  else ok(label);
};

console.log('Endpoint yang wajib terdokumentasi');
check('logout (#68)', ['POST /auth/logout']);
check('registrasi FCM token (#69)', ['POST /auth/fcm-token', 'DELETE /auth/fcm-token']);
check('upload gambar terpisah (#52)', ['POST /uploads/images']);
check('wishlist / favorit (#70)', ['/favorite', 'GET /favorites']);
// Admin API memang diringkas: panel web yang mengelola stores, listings,
// requests, offers, orders, dan reviews — itu resolusi akhirnya.
// Yang bertahan di API: daftar pengguna + blokirnya.
check('admin resource di API (#64)', ['GET /admin/users']);
check('admin settings (#65)', ['/admin/settings', 'super-admin']);
check('blokir pengguna', ['/block']);
check('perpanjang request', ['/extend']);
check('upload bukti transfer', ['payment-proof']);

console.log('\nDetail kontrak yang wajib dijelaskan');
check('rate limit header (#45)', ['X-RateLimit-Remaining', 'X-RateLimit-Reset', 'Retry-After']);
check('token metadata (#46)', ['expires_in', 'token_type']);
check('validasi avatar (#47)', ['dimensions:min_width=200', 'max:2048']);
check('validasi KTP & selfie (#48)', ['ktp_image', 'selfie_image', 'max:5120']);
check('operating_hours lengkap (#50)', ['"minggu"', '"jumat"']);
check('lat/lng wajib untuk pencarian (#53)', ['selalu wajib']);
check('images pada request (#54)', ['purpose=request']);
check('paginasi (#56)', ['per_page', 'last_page', 'KONVENSI GLOBAL']);
check('validasi harga vs budget (#57)', ['di luar anggaran pembeli']);
check('offer lain auto-reject (#58)', ['Offer lain otomatis `rejected`']);
check('listing_id XOR offer_id (#59)', ['XOR', 'prohibits:offer_id']);
check('shipping_address (#60)', ['shipping_address']);
check('state machine (#61)', ['Diagram State', 'Transisi Sah']);
check('prasyarat review (#62)', ['Prasyarat diperiksa berurutan']);
check('efek dispute ke order (#63)', ["orders.status  →  'dispute'"]);
check('contoh body 409 (#66)', ['Contoh Body 409 Conflict']);
check('kode 423 Locked (#67)', ['423', 'Locked']);

console.log('\nKonsistensi lintas dokumen');

// #49/#51: store_type selalu array di API, dan tidak lagi string.
if (/"store_type":\s*"(goods|services|rental)"/.test(api)) {
  fail('store_type masih dikembalikan sebagai string — harus array (#49)');
} else {
  ok('store_type konsisten array (#49)');
}
check('peringatan services vs service (#51)', ['`services` vs `service`']);

// #55: tanggal ISO 8601 UTC, offset +07:00 tidak boleh dipakai lagi di contoh.
const badDates = api.split('\n').filter(l =>
  /\+07:00/.test(l) && !l.includes('jangan dipakai') && !l.includes('❌')
);
if (badDates.length) {
  fail(`masih ada tanggal ber-offset +07:00 (#55): ${badDates.length} baris, mis. "${badDates[0].trim().slice(0, 60)}"`);
} else {
  ok('semua contoh tanggal memakai UTC "Z" (#55)');
}

// Alasan dispute harus identik dengan DATABASE.md
const reasons = ['barang_tidak_sesuai', 'jasa_tidak_profesional', 'penyedia_tidak_responsif', 'pembeli_fiktif'];
const drift = reasons.filter(r => !api.includes(r) || !db.includes(r));
if (drift.length) fail(`alasan dispute tidak sinkron dengan DATABASE.md: ${drift.join(', ')}`);
else ok('alasan dispute sinkron API ↔ DATABASE');

// Status order di API harus memakai nilai ENUM yang sah
const statuses = ['menunggu_konfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan', 'dispute'];
const missingStatus = statuses.filter(s => !api.includes(s));
if (missingStatus.length) fail(`status order belum lengkap di API: ${missingStatus.join(', ')}`);
else ok('6 status order konsisten dengan ENUM database');

console.log(problems === 0
  ? '\n✅ API documentation konsisten.'
  : `\n❌ ${problems} masalah API documentation ditemukan.`);
if (problems) process.exitCode = 1;
