#!/usr/bin/env node
/**
 * Penjaga SINKRONISASI SKEMA: migrasi Laravel ⇄ DATABASE.md ⇄ model Eloquent.
 *
 * KENAPA CHECKER INI ADA
 * ----------------------
 * Checker lain memverifikasi bahwa kolom yang DIMINTA audit sudah ada. Tidak
 * satu pun membandingkan skema nyata (migrasi) dengan skema tertulis
 * (DATABASE.md). Akibatnya semua checker hijau padahal ada 6 penyimpangan:
 *
 *   - `orders.notes` & `orders.quantity` ada di migrasi, hilang dari dokumen
 *   - `orders.shipping_location` ada di dokumen, tidak pernah dimigrasikan
 *   - `users.is_blocked/blocked_reason/blocked_at` tak terdokumentasi
 *   - `stores.bank_account` tak terdokumentasi (padahal dipakai alur transfer)
 *   - tabel `settings` dimigrasikan tanpa bab di DATABASE.md
 *   - `reviews` punya `updated_at` padahal API menyebut ulasan tak bisa diubah
 *
 * Drift semacam ini tidak terlihat sampai ada yang menulis kode berdasarkan
 * dokumen yang salah.
 *
 * Jalankan:  node tools/dev/check-schema-drift.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');

const db = read('DATABASE.md');
const MIG_DIR = path.join(ROOT, 'seekitar-server/database/migrations');
const MODEL_DIR = path.join(ROOT, 'seekitar-server/app/Models');

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);

/**
 * Kolom yang tercantum di bab `### 4.x <tabel>` DATABASE.md.
 *
 * Dua gaya penulisan dipakai di dokumen dan keduanya harus dibaca:
 *   - tabel markdown  → `| \`nama\` | TIPE | keterangan |`
 *   - blok CREATE TABLE → `  nama TIPE ...`
 */
function docColumns(tbl) {
  const re = new RegExp(`### 4\\.[0-9a-z]+ \`${tbl}\`[\\s\\S]*?(?=\\n### |\\n## )`);
  const m = db.match(re);
  if (!m) return null;
  const body = m[0];
  const cols = new Set();

  for (const line of body.split('\n')) {
    const t = line.trim();
    // Baris tabel markdown. Baris ENUM/nilai (kolom kedua bukan tipe SQL)
    // ikut tersaring karena tidak diawali pola `| \`x\` | TIPE`.
    const md = t.match(/^\|\s*`([a-z_0-9]+)`\s*\|\s*([A-Za-z(]+)/);
    if (md && /^(CHAR|VARCHAR|TEXT|TINYINT|SMALLINT|INT|BIGINT|DECIMAL|FLOAT|DOUBLE|BOOLEAN|DATE|DATETIME|TIMESTAMP|JSON|ENUM|SET|POINT|GEOMETRY|BLOB|BINARY)/i.test(md[2])) {
      cols.add(md[1]);
    }
  }

  // Blok CREATE TABLE di dalam bab yang sama.
  const create = body.match(/CREATE TABLE\s+\w+\s*\(([\s\S]*?)\n\);/);
  if (create) {
    for (const line of create[1].split('\n')) {
      const t = line.trim().replace(/^`|`$/g, '');
      const cm = t.match(/^`?([a-z_0-9]+)`?\s+([A-Za-z]+)/);
      const isColumn = cm && /^(CHAR|VARCHAR|TEXT|TINYINT|SMALLINT|INT|BIGINT|DECIMAL|FLOAT|DOUBLE|BOOLEAN|DATE|DATETIME|TIMESTAMP|JSON|ENUM|SET|POINT|GEOMETRY|BLOB|BINARY)/i.test(cm[2]);
      // Urutan penting: uji "nama TIPE" LEBIH DULU. Kalau baris konstraint
      // disaring duluan, kolom bernama `key` ikut terbuang karena namanya
      // bertabrakan dengan kata kunci KEY — persis bug yang sempat terjadi.
      if (isColumn) { cols.add(cm[1]); continue; }
      if (/^(PRIMARY|FOREIGN|UNIQUE|INDEX|KEY|CONSTRAINT|CHECK|SPATIAL|FULLTEXT)\b/i.test(t)) continue;
    }
  }
  return [...cols];
}

/** Kolom yang benar-benar dibuat oleh file migrasi. */
const COLRE = /\$table->(?:foreignUuid|foreignId|uuid|string|text|longText|mediumText|decimal|integer|unsignedInteger|bigInteger|unsignedBigInteger|tinyInteger|unsignedTinyInteger|smallInteger|unsignedSmallInteger|boolean|json|jsonb|timestamp|date|dateTime|char|float|double|enum|set|binary|increments)\(\s*'([a-z_0-9]+)'/g;

function migrationTables() {
  const out = [];
  for (const f of fs.readdirSync(MIG_DIR).filter(x => x.startsWith('2026_07_27_'))) {
    const src = fs.readFileSync(path.join(MIG_DIR, f), 'utf8');
    const tm = src.match(/Schema::(?:create|table)\('([a-z_]+)'/);
    if (!tm) continue;
    const cols = new Set();
    let m;
    while ((m = COLRE.exec(src)) !== null) cols.add(m[1]);
    if (/\$table->timestamps\(\)/.test(src)) { cols.add('created_at'); cols.add('updated_at'); }
    if (/\$table->softDeletes\(\)/.test(src)) cols.add('deleted_at');
    if (/\$table->rememberToken\(\)/.test(src)) cols.add('remember_token');
    // Kolom spasial ditambahkan lewat raw SQL di SpatialSchema.
    for (const sm of src.matchAll(/addLocationColumn\(\s*'[a-z_]+'[^)]*?(?:column:\s*'([a-z_0-9]+)')?\s*\)/g)) {
      cols.add(sm[1] ?? 'location');
    }
    out.push({ file: f, table: tm[1], cols: [...cols] });
  }
  return out;
}

console.log('Migrasi ⇄ DATABASE.md');
const migs = migrationTables();
if (migs.length === 0) fail('tidak ada migrasi 2026_07_27_* terbaca');

for (const { file, table, cols } of migs) {
  const doc = docColumns(table);
  if (doc === null) {
    fail(`tabel \`${table}\` (${file}) tidak punya bab di DATABASE.md §4`);
    continue;
  }
  const onlyMig = cols.filter(c => !doc.includes(c));
  const onlyDoc = doc.filter(c => !cols.includes(c));
  if (onlyMig.length) fail(`${table}: ada di migrasi, TIDAK terdokumentasi → ${onlyMig.join(', ')}`);
  if (onlyDoc.length) fail(`${table}: terdokumentasi, TIDAK ada di migrasi → ${onlyDoc.join(', ')}`);
  if (!onlyMig.length && !onlyDoc.length) ok(`${table} (${cols.length} kolom)`);
}

console.log('\nUlasan bersifat permanen');
// API menyatakan ulasan tidak bisa diubah; kalau tabelnya punya updated_at,
// dokumen dan skema saling bertentangan.
const reviewMig = migs.find(m => m.table === 'reviews');
if (reviewMig?.cols.includes('updated_at')) {
  fail('tabel reviews punya `updated_at`, padahal API_DOCUMENTATION.md §8 menyatakan ulasan tidak bisa diubah');
} else ok('reviews tanpa updated_at');

const reviewModel = fs.existsSync(path.join(MODEL_DIR, 'Review.php'))
  ? fs.readFileSync(path.join(MODEL_DIR, 'Review.php'), 'utf8') : '';
if (!/const UPDATED_AT\s*=\s*null/.test(reviewModel)) {
  fail('Review model harus menyetel `const UPDATED_AT = null`, kalau tidak Eloquent menulis kolom yang tidak ada');
} else ok('Review::UPDATED_AT = null');

console.log('\nKolom migrasi wajib ada di $fillable model');
// Kolom yang diisi lewat mass-assignment tapi lupa didaftarkan akan diam-diam
// terbuang oleh Eloquent — gagal tanpa error.
const MODEL_OF = {
  stores: 'Store.php', orders: 'Order.php', listings: 'Listing.php',
  offers: 'Offer.php', reviews: 'Review.php', disputes: 'Dispute.php',
  customer_requests: 'CustomerRequest.php', user_devices: 'UserDevice.php',
};
/**
 * Kolom yang memang TIDAK boleh mass-assignable.
 *
 * Ini bukan celah: membiarkan `rating_avg` diisi dari request berarti toko
 * bisa mengarang rating sendiri. Nilainya hanya boleh dihitung ulang oleh
 * ReviewObserver/RecalculateStoreRatingJob dari tabel `reviews`. Begitu pula
 * kolom POINT — diisi lewat raw SQL (`ST_GeomFromText`), bukan Eloquent.
 */
const NOT_FILLABLE = new Set([
  'id', 'created_at', 'updated_at', 'deleted_at', 'remember_token',
  'latitude', 'longitude', 'location', 'shipping_location',
  // Turunan, dihitung sistem — bukan input pengguna.
  'rating_avg', 'total_reviews',
]);

for (const [table, modelFile] of Object.entries(MODEL_OF)) {
  const p = path.join(MODEL_DIR, modelFile);
  if (!fs.existsSync(p)) { fail(`model ${modelFile} tidak ada`); continue; }
  const src = fs.readFileSync(p, 'utf8');
  const fm = src.match(/protected \$fillable = \[([\s\S]*?)\];/);
  if (!fm) { fail(`${modelFile} tanpa $fillable`); continue; }
  const fillable = [...fm[1].matchAll(/'([a-z_0-9]+)'/g)].map(x => x[1]);
  const mig = migs.find(m => m.table === table);
  if (!mig) continue;
  const missing = mig.cols.filter(c =>
    !NOT_FILLABLE.has(c) && !c.endsWith('_latitude') && !c.endsWith('_longitude') &&
    !fillable.includes(c)
  );
  if (missing.length) fail(`${modelFile} $fillable kurang: ${missing.join(', ')}`);
  else ok(modelFile);
}

console.log(problems === 0
  ? '\n✅ Skema migrasi, dokumen, dan model sinkron.'
  : `\n❌ ${problems} penyimpangan skema ditemukan.`);
if (problems) process.exitCode = 1;
