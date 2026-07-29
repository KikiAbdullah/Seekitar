#!/usr/bin/env node
/**
 * Penjaga konsistensi terminologi & penamaan.
 *
 * Sumber kebenaran: TECH_STACK.md §6 (Glosarium Lintas Lapisan).
 *
 * Jalankan:  node tools/dev/check-terms.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');

const DOCS = [
  'PRD.md', 'DATABASE.md', 'API_DOCUMENTATION.md',
  'Server_Implementation_Guide.md', 'Mobile_Implementation_Guide.md',
  'BRANDING-GUIDELINE.md', 'TECH_STACK.md',
];
const src = Object.fromEntries(DOCS.map(f => [f, read(f)]));
const all = Object.values(src).join('\n');

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);

console.log('Glosarium terpusat');
const glossary = src['TECH_STACK.md'];
['GLOSARIUM LINTAS LAPISAN', 'customer_requests', 'Jangan pakai']
  .forEach(n => { if (!glossary.includes(n)) fail(`TECH_STACK.md §6 kurang: ${n}`); });
if (glossary.includes('GLOSARIUM LINTAS LAPISAN')) ok('glosarium ada di TECH_STACK.md §6 (#190-#194)');

['BRANDING-GUIDELINE.md', 'DATABASE.md'].forEach(f => {
  if (!src[f].includes('TECH_STACK.md')) fail(`${f} tidak merujuk ke glosarium`);
});
ok('dokumen lain merujuk ke glosarium');

console.log('\nNama entitas konsisten (#195)');
// Nama tabel dipilih customer_requests; user_requests tidak boleh muncul.
// Glosarium memuat istilah ini di kolom penjelasan, jadi periksa per-baris
// dan abaikan baris yang membahas keputusan penamaannya.
const stray = DOCS.filter(f =>
  src[f].split('\n').some(l =>
    /\buser_requests\b/.test(l) &&
    !/diusulkan|Tidak diubah|ambigu|~~/.test(l)
  )
);
if (stray.length) fail(`'user_requests' muncul di: ${stray.join(', ')} — nama tabelnya customer_requests`);
else ok("nama tabel 'customer_requests' konsisten di semua dokumen");

if (!glossary.includes('Kenapa nama tabel tetap `customer_requests`')) {
  fail('alasan mempertahankan customer_requests belum terdokumentasi (#195)');
} else ok('alasan penamaan terdokumentasi (#195)');

console.log('\nSufiks _level vs _status (#196)');
if (!glossary.includes('Sufiks `_level` vs `_status`')) {
  fail('aturan sufiks _level/_status belum terdokumentasi');
} else ok('aturan sufiks terdokumentasi');
if (/users\.verification_status|stores\.verification_level/.test(all)) {
  fail('sufiks tertukar: users memakai _level, stores memakai _status');
} else ok('users.verification_level & stores.verification_status tidak tertukar');

console.log('\nIstilah UI penyedia vs pembeli (#197)');
if (!glossary.includes('Pasang Kebutuhan') || !glossary.includes('Kebutuhan Sekitar')) {
  fail('pembedaan Pasang Kebutuhan / Kebutuhan Sekitar belum di glosarium');
} else ok('Pasang Kebutuhan (pembeli) vs Kebutuhan Sekitar (penyedia)');

console.log('\nNilai tipe: jamak vs tunggal (#198)');
const db = src['DATABASE.md'];

// listing_type dan order_type WAJIB identik — nilainya disalin saat order dibuat.
const listingEnum = db.match(/ENUM\('product','service','rental'\)/g) || [];
if (listingEnum.length < 2) {
  fail(`listing_type & order_type harus memakai ENUM('product','service','rental') yang sama — ditemukan ${listingEnum.length} dari 2`);
} else ok('listing_type & order_type identik (dapat disalin langsung)');

const goodsHits = db.split('\n').filter(l =>
  /ENUM\('goods','service','rental'\)/.test(l) && !l.trim().startsWith('>')
);
if (goodsHits.length) {
  fail("order_type masih memakai 'goods' — tidak cocok dengan listing_type 'product'");
} else ok("order_type tidak lagi memakai 'goods'");

// store_type sengaja jamak karena bertipe SET.
if (!/SET\('goods','services','rental'\)/.test(db)) {
  fail('store_type bukan lagi SET jamak — cek apakah disengaja');
} else ok('store_type tetap SET jamak (menampung kombinasi)');

if (!db.includes('Kenapa `store_type` jamak')) {
  fail('alasan beda bentuk jamak/tunggal belum terdokumentasi (#198)');
} else ok('alasan jamak vs tunggal terdokumentasi (#198)');

console.log('\nIstilah yang sudah dihentikan');
// Hanya tandai bila dipakai sebagai NAMA ENTITAS, bukan kata biasa.
const BANNED = [
  [/\bmerchants?\b/i, 'merchant', 'pakai store/toko'],
  [/`bids?`|\bBid\b(?! )/, 'bid', 'pakai offer/penawaran'],
];
let banned = 0;
for (const [re, term, hint] of BANNED) {
  const hits = DOCS.filter(f =>
    src[f].split('\n').some(l =>
      re.test(l) &&
      !l.includes('~~') &&          // baris glosarium "Jangan pakai"
      !l.includes('Jangan pakai')
    )
  );
  if (hits.length) { fail(`istilah '${term}' muncul di ${hits.join(', ')} — ${hint}`); banned++; }
}
if (!banned) ok('tidak ada istilah yang sudah dihentikan');

console.log(problems === 0
  ? '\n✅ Terminologi konsisten.'
  : `\n❌ ${problems} masalah ditemukan.`);
if (problems) process.exitCode = 1;
