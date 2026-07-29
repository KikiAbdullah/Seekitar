#!/usr/bin/env node
/**
 * Penjaga "MySQL-only".
 *
 * KENAPA CHECKER INI ADA
 * ----------------------
 * Skema Seekitar bergantung pada fitur yang HANYA ada di MySQL: POINT SRID
 * 4326, SPATIAL INDEX, tipe SET, dan CHECK constraint. Selama masih ada
 * jalur SQLite, test bisa hijau tanpa membuktikan apa pun tentang perilaku
 * produksi. Checker ini memastikan jalur itu tidak diam-diam kembali.
 *
 * Sumber pemeriksaan bukan pembacaan berkas biasa, melainkan DDL MySQL yang
 * benar-benar dihasilkan Laravel (`tools/dev/ddl`, memakai pretend()).
 *
 * Jalankan:  node tools/dev/check-mysql.mjs
 */
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);

// ─────────────────────────────────────────── 1. Tidak ada sisa SQLite
console.log('Tidak ada jalur SQLite yang tersisa');

const MUST_BE_CLEAN = [
  'seekitar-server/config/database.php',
  'seekitar-server/config/queue.php',
  'seekitar-server/phpunit.xml',
  'seekitar-server/.env.example',
  'seekitar-server/app/Support/SpatialSchema.php',
  'seekitar-server/app/Models/Concerns/HasLocation.php',
];

for (const f of MUST_BE_CLEAN) {
  const src = read(f);
  // Penyebutan dalam komentar penjelas boleh; yang dilarang adalah nilai
  // konfigurasi/kode yang benar-benar mengaktifkan SQLite.
  const offending = src.split('\n').filter(l => {
    const t = l.trim();
    if (t.startsWith('//') || t.startsWith('*') || t.startsWith('#') || t.startsWith('<!--')) return false;
    return /'sqlite'|"sqlite"|value="sqlite"|DB_CONNECTION=sqlite|:memory:|database\.sqlite/i.test(t);
  });
  if (offending.length) fail(`${f} masih mengaktifkan SQLite: ${offending[0].trim().slice(0, 80)}`);
}
if (problems === 0) ok('config, phpunit, dan lapisan spasial bebas SQLite');

const dbConfig = read('seekitar-server/config/database.php');
for (const driver of ['sqlite', 'pgsql', 'sqlsrv', 'mariadb']) {
  if (new RegExp(`'${driver}' => \\[`).test(dbConfig)) {
    fail(`koneksi '${driver}' muncul lagi di config/database.php — proyek ini MySQL-only`);
  }
}
if (!/'default' => env\('DB_CONNECTION', 'mysql'\)/.test(dbConfig)) {
  fail("config/database.php: default connection harus 'mysql'");
} else ok('hanya koneksi mysql yang terdaftar');

// ─────────────────────────────────────────── 2. Urutan sumbu SRID 4326
console.log('\nUrutan sumbu SRID 4326 (penyebab ERROR 3617)');

// MySQL membaca WKT SRID 4326 sebagai (latitude longitude). Bujur Indonesia
// 95–141° BT berada di luar rentang lintang ±90, jadi setiap ST_GeomFromText
// tanpa 'axis-order=long-lat' pasti gagal.
const phpSources = [
  'seekitar-server/app/Models/Concerns/HasLocation.php',
  'seekitar-server/app/Support/SpatialSchema.php',
];
for (const f of phpSources) {
  const src = read(f);
  // Bentuk yang sah ada tiga: argumen ke-3 berupa placeholder `?`, konstanta
  // self::AXIS lewat sprintf `'%s'`, atau literal 'axis-order=...'. Yang
  // dicari adalah ST_GeomFromText(..., 4326) yang BERHENTI di situ.
  const calls = [...src.matchAll(/ST_GeomFromText\(([^)]*)\)/gi)];
  const bare = calls.filter(([, args]) => /4326\s*$/.test(args.trim()));
  if (bare.length) {
    fail(`${f}: ST_GeomFromText berhenti di 4326 tanpa argumen axis-order → ${bare[0][0].slice(0, 70)}`);
  }
  if (!src.includes('axis-order=long-lat') && calls.length) {
    fail(`${f}: tidak menyebut axis-order=long-lat sama sekali`);
  }
}
// Konstanta AXIS harus bernilai persis 'axis-order=long-lat'.
// Mengecek keberadaan string saja tidak cukup: mengganti nilainya menjadi
// 'srid-defined' (default MySQL yang justru memicu ERROR 3617) tetap lolos
// karena kalimat penjelasnya masih menyebut long-lat.
const hasLocSrc = read(phpSources[0]);
const axisConst = hasLocSrc.match(/const AXIS\s*=\s*'([^']*)'/);
if (!axisConst) {
  fail('HasLocation tidak lagi mendefinisikan konstanta AXIS');
} else if (axisConst[1] !== 'axis-order=long-lat') {
  fail(`HasLocation::AXIS bernilai '${axisConst[1]}', harus 'axis-order=long-lat' (lihat DATABASE.md §11)`);
} else {
  ok("HasLocation::AXIS = 'axis-order=long-lat'");
}

// Semua pemakaian axis-order di kode wajib long-lat, bukan varian lain.
for (const f of phpSources) {
  const wrong = [...read(f).matchAll(/axis-order=(?!long-lat)([a-z-]+)/g)].map(m => m[1]);
  if (wrong.length) fail(`${f}: memakai axis-order=${wrong[0]} — harus long-lat`);
}

if (read(phpSources[1]).includes('axis-order=long-lat')) ok('SpatialSchema memakai axis-order=long-lat');

// Contoh SQL di dokumen ikut diperiksa: menambah placeholder ketiga tanpa
// menambah nilai binding-nya menghasilkan snippet yang gagal saat disalin.
const DOCS_WITH_SQL = ['DATABASE.md', 'Server_Implementation_Guide.md', 'PRD.md'];
let docIssues = 0;
for (const f of DOCS_WITH_SQL) {
  const lines = read(f).split('\n');
  lines.forEach((line, i) => {
    if (!/ST_GeomFromText\(\?,\s*4326/.test(line)) return;

    // Dua bentuk sah:
    //   a) literal langsung di SQL  — ST_GeomFromText(?, 4326, 'axis-order=long-lat')
    //   b) placeholder ketiga       — ST_GeomFromText(?, 4326, ?)  + nilai di binding
    const literal = /ST_GeomFromText\(\?,\s*4326,\s*'axis-order=long-lat'\)/.test(line);
    const placeholder = /ST_GeomFromText\(\?,\s*4326,\s*\?\)/.test(line);

    if (!literal && !placeholder) {
      fail(`${f}:${i + 1} ST_GeomFromText tanpa argumen axis-order`);
      docIssues++;
      return;
    }
    if (!placeholder) return;   // bentuk (a) sudah lengkap di baris itu sendiri

    // Bentuk (b): nilainya harus benar-benar ada di array binding.
    const near = lines.slice(i, i + 8).join('\n');
    if (!/self::AXIS|'axis-order=long-lat'/.test(near)) {
      fail(`${f}:${i + 1} placeholder axis-order tidak punya nilai binding`);
      docIssues++;
    }
  });
}
if (docIssues === 0) ok('contoh SQL di dokumen mengikat axis-order dengan benar');

// self::AXIS hanya sah bila konstantanya benar-benar didefinisikan.
for (const f of DOCS_WITH_SQL) {
  const src = read(f);
  if (src.includes('self::AXIS') && !src.includes("const AXIS = 'axis-order=long-lat'")) {
    fail(`${f}: memakai self::AXIS tetapi konstantanya tidak pernah didefinisikan`);
  }
}

// ─────────────────────────────────────────── 3. DDL nyata dari Laravel
console.log('\nDDL MySQL yang benar-benar dihasilkan Laravel');

let ddl = '';
try {
  ddl = execFileSync(path.join(ROOT, 'tools/dev/ddl'), { encoding: 'utf8', maxBuffer: 32 * 1024 * 1024 });
} catch (e) {
  console.log('  ⚠️  tidak bisa menghasilkan DDL (butuh ./tools/dev/setup). Bagian ini dilewati.');
  console.log(problems === 0 ? '\n✅ Pemeriksaan statis lolos.' : `\n❌ ${problems} masalah.`);
  if (problems) process.exitCode = 1;
  process.exit();
}

const lower = ddl.toLowerCase();

// Kolom spasial wajib SRID 4326 — tanpa itu SPATIAL INDEX ditolak MySQL.
// Nama tabel ikut ditangkap: `location` muncul di beberapa tabel dengan
// nullability berbeda, jadi mencocokkan nama kolom saja membuat baris yang
// salah yang terbaca.
const points = [...ddl.matchAll(/ALTER TABLE `(\w+)` ADD COLUMN `(\w+)` POINT (NOT NULL|NULL) SRID (\d+)/gi)]
  .map(([, table, col, nullability, srid]) => ({ table, col, nullability, srid }));

// Sejak 2.3 hanya TIGA kolom POINT tersisa: users.location,
// customer_requests.location, orders.shipping_location. Kolom stores.
// location DIBUBARKAN — toko dicari lewat kotak pembatas latitude/
// longitude (DECIMAL) karena lebih murah dan bebas jebakan urutan sumbu;
// tiga tabel di atas tetap POINT karena dipakai fungsi jarak langsung.
if (points.length < 3) fail(`kolom POINT terlalu sedikit: ${points.length}, harusnya 3 (users, customer_requests, orders)`);
for (const p of points) {
  if (p.srid !== '4326') fail(`${p.table}.${p.col} memakai SRID ${p.srid}, harus 4326`);
}
if (points.length >= 3 && points.every(p => p.srid === '4326')) {
  ok(`${points.length} kolom POINT semuanya SRID 4326`);
}

// Toko 2.3: koordinat toko adalah dua kolom DECIMAL berskala 8 digit
// (≈ 1,1 mm), keduanya NOT NULL — pengganti POINT yang dibubarkan.
// Spasi setelah koma bervariasi antarversi klien (decimal(11, 8) vs (11,8)).
if (!/`latitude` decimal\(11,\s?8\) NOT NULL/i.test(ddl)) fail('stores.latitude bukan DECIMAL(11,8) NOT NULL');
else if (!/`longitude` decimal\(12,\s?8\) NOT NULL/i.test(ddl)) fail('stores.longitude bukan DECIMAL(12,8) NOT NULL');
else ok('stores.latitude/longitude DECIMAL berskala 8, NOT NULL');

// SPATIAL INDEX hanya sah pada kolom NOT NULL. Setelah stores pindah ke
// DECIMAL (2.3), satu-satunya yang tersisa adalah cr_location_spatial di
// customer_requests — satu-satunya tabel yang memfilter jarak di SQL.
const spatialIdx = [...ddl.matchAll(/ALTER TABLE `(\w+)` ADD SPATIAL INDEX `(\w+)` \(`(\w+)`\)/gi)]
  .map(([, table, idx, col]) => ({ table, idx, col }));

if (spatialIdx.length < 1) fail(`SPATIAL INDEX hilang: customer_requests.location butuh cr_location_spatial`);
else ok(`${spatialIdx.length} SPATIAL INDEX dibuat`);

for (const { table, idx, col } of spatialIdx) {
  // Dicocokkan per (tabel, kolom): nama `location` dipakai di beberapa tabel
  // dengan nullability berbeda.
  const decl = points.find(p => p.table === table && p.col === col);
  if (!decl) { fail(`SPATIAL INDEX ${idx} menunjuk kolom ${table}.${col} yang tidak dideklarasikan`); continue; }
  if (decl.nullability.toUpperCase() !== 'NOT NULL') {
    fail(`SPATIAL INDEX ${idx} pada kolom nullable ${table}.${col} — MySQL menolaknya`);
  }
}

// Tipe asli MySQL yang tidak punya padanan di engine lain.
if (!/`store_type` set\(/i.test(ddl)) fail('store_type bukan tipe SET MySQL');
else ok('store_type memakai SET asli');

const enumCols = (lower.match(/ enum\(/g) || []).length;
if (enumCols < 10) fail(`kolom ENUM hanya ${enumCols}, harusnya ≥10 (status/tipe pakai ENUM, bukan VARCHAR)`);
else ok(`${enumCols} kolom memakai ENUM asli`);

// CHECK constraint yang didokumentasikan DATABASE.md harus benar-benar dibuat.
const REQUIRED_CHECKS = [
  'listings_price_required_chk',
  'listings_qty_slot_chk',
  'offers_expiry_chk',
  'orders_shipping_chk',
  'reviews_store_direction_chk',
  'stores_fulfilment_chk',
];
const missingChecks = REQUIRED_CHECKS.filter(c => !ddl.includes(c));
if (missingChecks.length) fail(`CHECK constraint hilang: ${missingChecks.join(', ')}`);
else ok(`${REQUIRED_CHECKS.length} CHECK constraint ditegakkan engine`);

// InnoDB: MyISAM tidak mendukung FK, transaksi, maupun CHECK.
const tables = (lower.match(/^create table/gm) || []).length;
const innodb = (lower.match(/engine = innodb/g) || []).length;
if (tables !== innodb) fail(`${tables} tabel tetapi hanya ${innodb} InnoDB`);
else ok(`${tables} tabel semuanya InnoDB`);

// ENUM di DDL harus sama persis dengan enum PHP-nya.
console.log('\nNilai ENUM di skema = nilai enum PHP');
const ENUM_MAP = {
  'OrderStatus': 'menunggu_konfirmasi',
  'ListingType': 'product',
  'DisputeReason': 'pembeli_fiktif',
  'ReviewDirection': 'buyer_to_store',
};
for (const [cls, sample] of Object.entries(ENUM_MAP)) {
  const src = read(`seekitar-server/app/Enums/${cls}.php`);
  const values = [...src.matchAll(/=\s*'([a-z_]+)'/g)].map(m => m[1]);
  const missing = values.filter(v => !ddl.includes(`'${v}'`));
  if (missing.length) fail(`${cls}: nilai tidak muncul di DDL → ${missing.join(', ')}`);
  else if (!ddl.includes(`'${sample}'`)) fail(`${cls}: contoh nilai '${sample}' tidak ada di DDL`);
  else ok(`${cls} (${values.length} nilai)`);
}

console.log(problems === 0
  ? '\n✅ Skema MySQL-only konsisten.'
  : `\n❌ ${problems} masalah ditemukan.`);
if (problems) process.exitCode = 1;
