#!/usr/bin/env node
/**
 * Penjaga implementasi keamanan.
 *
 * Prinsipnya: setiap janji keamanan di PRD §11 harus punya padanan
 * implementasi di Server_Implementation_Guide.md §18A.
 *
 * Jalankan:  node tools/dev/check-security.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');

const sig = read('Server_Implementation_Guide.md');
const prd = read('PRD.md');
const db = read('DATABASE.md');
const mig = read('Mobile_Implementation_Guide.md');

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);
const check = (label, needles, src = sig) => {
  const missing = needles.filter(n => !src.includes(n));
  if (missing.length) fail(`${label} — hilang: ${missing.join(', ')}`);
  else ok(label);
};

console.log('Bab keamanan');
check('bab 18A ada', ['## 18A. KEAMANAN APLIKASI', '18A.7 Ringkasan Pemetaan']);
check('PRD merujuk implementasi', ['§18A'], prd);

console.log('\nKontrol keamanan');
check('UUID route pattern (#199)', ['Route::pattern', '[0-9a-fA-F]{8}-']);
check('CORS terkonfigurasi (#200)', ['config/cors.php', 'CORS_ALLOWED_ORIGINS', 'exposed_headers']);
// Implementasi aktual: berkas di disk `local` privat + dialirkan lewat route
// berizin; NIK satu-satunya kolom terenkripsi. SSE-KMS dicadangkan untuk produksi.
check('enkripsi KTP & NIK (#201, #206)', ["'nik' => 'encrypted'", 'disk privat', 'no-store']);
check('proteksi XSS (#202)', ['rawColumns', 'Content-Security-Policy', '@json']);
check('rate limit login admin (#203)', ["RateLimiter::for('admin-login'", 'throttle:admin-login']);
check('validasi nomor HP (#207)', ['phone:ID', 'normalizePhone', 'propaganistas/laravel-phone']);

console.log('\nSudah ditangani di bagian F');
check('interceptor 401/423 (#205)', ['status == 401 || status == 423'], mig);
check('refresh token dijelaskan (#204)', ['Tidak ada mekanisme refresh token'], mig);

console.log('\nSkema pendukung');
check('kolom nik & nik_hash', ['`nik_hash`', 'users_nik_hash_unique'], db);
check('env keamanan', ['AWS_KMS_KEY_ID', 'CORS_ALLOWED_ORIGINS']);

console.log('\nAnti-pola yang tidak boleh muncul kembali');

// CORS wildcard + credentials adalah kombinasi terlarang.
const corsWildcard = sig.split('\n').filter(l =>
  /'allowed_origins'\s*=>\s*\[\s*'\*'/.test(l) && !l.includes('JANGAN')
);
if (corsWildcard.length) fail("config CORS memakai allowed_origins => ['*'] (#200)");
else ok('CORS tidak memakai wildcard');

// Rekomendasi keliru: {!! !!} untuk atribut. Harus ada koreksinya.
if (!sig.includes('`{!! !!}` justru **mematikan**') && !sig.includes('mematikan** escaping')) {
  fail('koreksi soal {!! !!} yang mematikan escaping belum terdokumentasi (#202)');
} else ok('bahaya {!! !!} dijelaskan');

// Crypt tidak boleh dianjurkan untuk berkas gambar.
const cryptFile = sig.split('\n').filter(l =>
  /Crypt::encryptString/.test(l) &&
  /file|berkas|gambar|image/i.test(l) &&
  !/TIDAK cocok|jangan/i.test(l)
);
if (cryptFile.length) fail(`Crypt::encryptString dianjurkan untuk berkas: "${cryptFile[0].trim().slice(0, 60)}"`);
else ok('Crypt hanya untuk teks pendek, bukan berkas');

// Nomor telepon harus dinormalkan di server, bukan hanya di klien.
if (!sig.includes('prepareForValidation')) {
  fail('normalisasi nomor telepon tidak dilakukan di FormRequest (#207)');
} else ok('normalisasi telepon di sisi server');

// PRD tidak boleh lagi menyebut Vault (infrastruktur yang tidak dipakai).
const vault = prd.split('\n').filter(l => /\bVault\b/.test(l) && !/bukan Vault/.test(l));
if (vault.length) fail(`PRD masih menyebut Vault sebagai pengelola kunci: "${vault[0].trim().slice(0, 60)}"`);
else ok('pengelolaan kunci konsisten (KMS, bukan Vault)');

console.log(problems === 0
  ? '\n✅ Implementasi keamanan konsisten.'
  : `\n❌ ${problems} masalah ditemukan.`);
if (problems) process.exitCode = 1;
