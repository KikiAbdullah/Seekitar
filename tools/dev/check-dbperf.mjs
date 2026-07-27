#!/usr/bin/env node
/**
 * Penjaga performa & indeks database (TODO_BUG bagian L).
 *
 * Jalankan:  node tools/dev/check-dbperf.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');

const db = read('DATABASE.md');
const sig = read('Server_Implementation_Guide.md');

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);
const check = (label, needles, src = db) => {
  const missing = needles.filter(n => !src.includes(n));
  if (missing.length) fail(`${label} — hilang: ${missing.join(', ')}`);
  else ok(label);
};

console.log('Indeks komposit');
check('urutan kolom dijelaskan (#219)', ['kesamaan sebelum rentang', 'Aturan urutan kolom']);
check('cr_category_status_idx (#220)', ['cr_category_status_idx']);
check('offers_request_status_idx (#221)', ['offers_request_status_idx']);
check('orders_buyer_status_idx (#222)', ['orders_buyer_status_idx', 'orders_store_status_idx']);
check('reviews_store_direction_idx', ['reviews_store_direction_idx']);
check('panduan EXPLAIN', ['EXPLAIN', 'Using index condition', 'ANALYZE TABLE']);

console.log('\nFulltext');
check('penolakan parser ngram (#223)', ['Jangan Pakai Parser `ngram`', 'CJK']);
check('innodb_ft_min_token_size', ['innodb_ft_min_token_size=2']);
check('sanitasi input BOOLEAN MODE', ['IN BOOLEAN MODE', 'preg_replace']);

console.log('\nSpasial');
check('syarat SRID & NOT NULL (#224)', ['SRID 4326', 'ST_GEOMETRY_COLUMNS', 'SRS_ID']);
check('pola dua tahap (#225)', ['MBRContains', 'ST_Distance_Sphere']);
check('penolakan ST_Buffer (#225)', ['ST_Buffer', 'geographic spatial reference systems']);
// Argumen ketiga (axis-order) wajib ada — tanpa itu query gagal di MySQL
// dengan ERROR 3617 untuk setiap koordinat Indonesia (DATABASE.md §11).
// Nilainya diikat sebagai parameter, jadi yang dicek bentuk placeholder-nya;
// check-mysql.mjs yang memastikan nilai binding-nya benar.
check('scopeNearby dua tahap', ['MBRContains(ST_GeomFromText(?, 4326, ?), location)'], sig);

console.log('\nPemantauan');
check('slow query log', ['slow_query_log', 'long_query_time']);
check('ringkasan indeks', ['Ringkasan Indeks & Pola Query']);

console.log('\nAnti-pola yang tidak boleh muncul kembali');

// ngram tidak boleh dianjurkan (hanya boleh disebut dalam konteks penolakan).
const ngramHits = db.split('\n').filter(l =>
  /WITH PARSER ngram/.test(l) && !/Jangan|keliru|CJK|bila|jika/i.test(l)
);
if (ngramHits.length) fail(`parser ngram masih dianjurkan: "${ngramHits[0].trim().slice(0, 60)}"`);
else ok('parser ngram tidak dianjurkan');

// ST_Buffer tidak boleh dianjurkan sebagai solusi.
const bufferHits = db.split('\n').filter(l =>
  /ST_Buffer/.test(l) && !/keliru|tidak mendukung|ERROR|hanya bekerja|SRID 0/i.test(l)
);
if (bufferHits.length) fail(`ST_Buffer dianjurkan padahal tidak didukung SRID 4326: "${bufferHits[0].trim().slice(0, 60)}"`);
else ok('ST_Buffer tidak dianjurkan');

// scopeNearby tidak boleh kembali ke satu tahap.
const nearbySection = sig.slice(sig.indexOf('trait HasLocation'), sig.indexOf('trait HasLocation') + 1800);
if (nearbySection.includes('scopeNearby') && !nearbySection.includes('MBRContains')) {
  fail('scopeNearby kembali memakai ST_Distance_Sphere tanpa pra-filter indeks (#225)');
} else ok('scopeNearby memakai pra-filter indeks');

// Indeks harus konsisten dengan kolom yang ada.
const REQUIRED_COLS = [['reviews', 'direction'], ['orders', 'buyer_id'], ['offers', 'request_id']];
REQUIRED_COLS.forEach(([tbl, col]) => {
  if (!db.includes(`\`${col}\``)) fail(`kolom ${tbl}.${col} dirujuk indeks tapi tidak ada di skema`);
});
ok('kolom yang diindeks ada di skema');

console.log(problems === 0
  ? '\n✅ Performa & indeks database konsisten.'
  : `\n❌ ${problems} masalah ditemukan.`);
if (problems) process.exitCode = 1;
