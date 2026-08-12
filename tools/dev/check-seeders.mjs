#!/usr/bin/env node
/**
 * Penjaga MIGRASI & SEEDER.
 *
 * KENAPA CHECKER INI ADA
 * ----------------------
 * Seeder produksi menentukan apakah aplikasi bisa dipakai sama sekali:
 * `customer_requests.category_id` NOT NULL + FK RESTRICT, jadi tanpa kategori
 * tidak ada permintaan yang bisa dibuat. Sementara isinya (jumlah kategori,
 * daftar permission, kunci settings) hanya tertulis di dokumen — mudah
 * menyimpang tanpa ada yang sadar.
 *
 * Checker ini membandingkan seeder dengan angka yang dijanjikan dokumen, dan
 * memverifikasi syarat idempoten yang diwajibkan Server_Implementation_Guide
 * §19.2.
 *
 * Jalankan:  node tools/dev/check-seeders.mjs
 */
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');
const exists = f => fs.existsSync(path.join(ROOT, f));
const SEEDERS = 'seekitar-server/database/seeders';

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);

// ───────────────────────────────────────────── 1. Berkas wajib ada
console.log('Berkas seeder');
const REQUIRED = ['DatabaseSeeder', 'CategorySeeder', 'RolesAndPermissionsSeeder',
                  'SettingSeeder', 'DummyDataSeeder', 'AdminUserSeeder'];
for (const name of REQUIRED) {
  if (!fs.existsSync(path.join(ROOT, SEEDERS, `${name}.php`))) fail(`${name}.php tidak ada`);
}
if (problems === 0) ok(`${REQUIRED.length} seeder tersedia`);

// ───────────────────────────────────────────── 2. Kategori: tepat 24
console.log('\nCategorySeeder');
const catSrc = read(`${SEEDERS}/CategorySeeder.php`);

// Hitung dari struktur TAXONOMY, bukan dari komentar.
const taxonomy = catSrc.match(/private const TAXONOMY = \[([\s\S]*?)\n    \];/);
if (!taxonomy) {
  fail('konstanta TAXONOMY tidak ditemukan');
} else {
  const body = taxonomy[1];
  // Induk: baris yang membuka grup dengan 4 elemen (slug, nama, ikon, [anak]).
  const parents = (body.match(/^\s{8}\['[a-z0-9-]+', '[^']+', '[^']+', \[$/gm) || []).length;
  // Anak: baris array tiga elemen di dalam grup.
  const children = (body.match(/^\s{12}\['[a-z0-9-]+', '[^']+', '[^']+'\],$/gm) || []).length;
  const total = parents + children;

  if (total !== 24) fail(`kategori berjumlah ${total} (induk ${parents} + anak ${children}) — dokumen menjanjikan 24`);
  else ok(`24 kategori (${parents} induk + ${children} anak)`);

  // PRD §5.1 membatasi taksonomi dua level.
  if (/^\s{16}\[/m.test(body)) fail('ada kategori level 3 — PRD §5.1 membatasi dua level');
  else ok('kedalaman maksimal dua level');

  // Slug unik: UNIQUE(slug) di skema akan menolak duplikat saat deploy.
  const slugs = [...body.matchAll(/\['([a-z0-9-]+)',/g)].map(m => m[1]);
  const dupes = slugs.filter((s, i) => slugs.indexOf(s) !== i);
  if (dupes.length) fail(`slug ganda: ${[...new Set(dupes)].join(', ')}`);
  else ok(`${slugs.length} slug unik`);

  // Kolom slug & name VARCHAR(50).
  const tooLong = slugs.filter(s => s.length > 50);
  if (tooLong.length) fail(`slug melebihi VARCHAR(50): ${tooLong.join(', ')}`);
}

// ───────────────────────────────────────────── 3. Permission: 17, sesuai §6.2
console.log('\nRolesAndPermissionsSeeder');
const roleSrc = read(`${SEEDERS}/RolesAndPermissionsSeeder.php`);
const sig = read('Server_Implementation_Guide.md');

const permsInSeeder = [...roleSrc.matchAll(/^\s{8}'([a-z-]+)',$/gm)].map(m => m[1])
  .filter(p => p.startsWith('manage-') || p.startsWith('verify-'));

if (permsInSeeder.length !== 17) {
  fail(`permission berjumlah ${permsInSeeder.length}, dokumen §6.2 menyebut 17`);
} else ok('17 permission');

// Tiap permission harus benar-benar tercantum di Server Guide §6.2.
const missingInDoc = permsInSeeder.filter(p => !sig.includes(`\`${p}\``));
if (missingInDoc.length) fail(`permission tidak ada di §6.2: ${missingInDoc.join(', ')}`);
else ok('semua permission cocok dengan §6.2');

for (const role of ['super-admin', 'admin', 'user']) {
  if (!roleSrc.includes(`'${role}'`)) fail(`role '${role}' tidak dibuat`);
}
if (['super-admin', 'admin', 'user'].every(r => roleSrc.includes(`'${r}'`))) ok('3 role dibuat');

// Nomor super-admin tidak boleh ditanam di kode.
if (/firstOrCreate\(\s*\['phone' => '62\d+'\]/.test(roleSrc)) {
  fail('nomor super-admin ditanam di kode — pakai config/env');
} else ok('nomor super-admin dari konfigurasi');

// ───────────────────────────────────────────── 4. Settings: 8 kunci
console.log('\nSettingSeeder');
const setSrc = read(`${SEEDERS}/SettingSeeder.php`);
const KEYS = [
  'max_search_radius_km', 'default_request_radius_km', 'request_expiry_hours',
  'offer_expiry_hours', 'max_request_extensions', 'review_window_days',
  'ktp_review_sla_hours', 'dispute_sla_hours',
];
const missingKeys = KEYS.filter(k => !setSrc.includes(`'${k}'`));
if (missingKeys.length) fail(`kunci settings hilang: ${missingKeys.join(', ')}`);
else ok(`${KEYS.length} kunci settings lengkap`);

// Nilai default harus sama dengan tabel di §9.12.
const DEFAULTS = { max_search_radius_km: 25, default_request_radius_km: 15, request_expiry_hours: 24, offer_expiry_hours: 48, max_request_extensions: 2, review_window_days: 7 };
for (const [key, want] of Object.entries(DEFAULTS)) {
  const m = setSrc.match(new RegExp(`'${key}' => \\[\\s*(\\d+)`));
  if (m && Number(m[1]) !== want) fail(`${key} = ${m[1]}, dokumen §9.12 menyebut ${want}`);
}
ok('nilai default cocok dengan §9.12');

// SettingSeeder TIDAK boleh menimpa value yang sudah disunting admin.
if (/updateOrCreate\(\s*\['key'/.test(setSrc)) {
  fail('SettingSeeder memakai updateOrCreate — akan mengembalikan nilai admin ke default tiap deploy');
} else ok('nilai yang sudah disunting admin tidak ditimpa');

// ───────────────────────────────────────────── 5. Idempoten
console.log('\nSyarat idempoten (§19.2)');

/** Buang komentar agar contoh "yang salah" di docblock tidak ikut tertangkap. */
function stripComments(php) {
  return php
    .replace(/\/\*[\s\S]*?\*\//g, '')   // blok /* ... */ termasuk docblock
    .split('\n')
    .map(l => l.replace(/\/\/.*$/, ''))
    .join('\n');
}

let bareCreate = 0;
for (const name of ['CategorySeeder', 'RolesAndPermissionsSeeder', 'SettingSeeder']) {
  const code = stripComments(read(`${SEEDERS}/${name}.php`));
  // Pola berbahaya: Model::create( tanpa firstOr/updateOr di depannya.
  const bare = [...code.matchAll(/(?<!first|update)(?<![A-Za-z])([A-Z]\w+)::create\(/g)].map(m => m[1]);
  if (bare.length) { fail(`${name} memakai ${bare[0]}::create() — deploy kedua akan gagal unique`); bareCreate++; }
}
if (bareCreate === 0) ok('seeder produksi memakai firstOrCreate/updateOrCreate');

// ───────────────────────────────────────────── 5b. Akun contoh per peran
console.log('\nAdminUserSeeder');
if (exists(`${SEEDERS}/AdminUserSeeder.php`)) {
  const src = read(`${SEEDERS}/AdminUserSeeder.php`);

  // Akun ini memakai kata sandi yang tertulis di repositori.
  if (!/environment\('local', 'testing'\)/.test(src)) {
    fail('AdminUserSeeder tanpa penjagaan environment — kata sandi di repo bisa aktif di produksi');
  } else ok('dibatasi local/testing');

  // assignRole() menumpuk peran tiap kali seeder dijalankan ulang.
  if (/->assignRole\(/.test(src)) {
    fail('memakai assignRole() — peran menumpuk saat seeder dijalankan ulang; pakai syncRoles()');
  } else ok('memakai syncRoles (idempoten)');

  const roles = [...src.matchAll(/'role'\s*=>\s*'([a-z-]+)'/g)].map(m => m[1]);
  const expected = ['admin', 'user'];
  const missing = expected.filter(r => !roles.includes(r));
  if (missing.length) fail(`peran tanpa akun contoh: ${missing.join(', ')}`);
  else ok(`${roles.length} akun contoh (${roles.join(', ')})`);

  // Tanpa akun yang seharusnya DITOLAK, pembatasan peran tidak teruji.
  if (!/'panel'\s*=>\s*false/.test(src)) {
    fail("tidak ada akun kontrol ('panel' => false) untuk menguji penolakan");
  } else ok('menyertakan akun kontrol yang harus ditolak');

  // Bentrok nomor dengan akun pemilik akan menimpa perannya lewat syncRoles.
  const mainPhone = read('seekitar-server/config/seekitar.php')
    .match(/'super_admin_phone'\s*=>\s*env\([^,]+,\s*'(\d+)'\)/)?.[1];
  if (mainPhone && src.includes(`'${mainPhone}'`)) {
    fail(`nomor ${mainPhone} bentrok dengan akun super-admin utama`);
  } else ok('nomor tidak bentrok dengan akun pemilik');
}

// ───────────────────────────────────────────── 6. DatabaseSeeder
console.log('\nDatabaseSeeder');
const dbSrc = read(`${SEEDERS}/DatabaseSeeder.php`);

// Urutan wajib: role dulu, karena super-admin langsung diberi role.
const iRole = dbSrc.indexOf('RolesAndPermissionsSeeder');
const iCat = dbSrc.indexOf('CategorySeeder');
const iSet = dbSrc.indexOf('SettingSeeder');
if (iRole === -1 || iCat === -1 || iSet === -1) fail('DatabaseSeeder tidak memanggil ketiga seeder produksi');
else if (!(iRole < iCat && iCat < iSet)) fail('urutan seeder salah — role harus lebih dulu (§19.2)');
else ok('urutan: Roles → Category → Setting');

// Akun contoh harus dibuat SETELAH perannya ada.
const iAdminUser = dbSrc.indexOf('AdminUserSeeder');
if (iAdminUser !== -1 && iRole > iAdminUser) {
  fail('AdminUserSeeder dipanggil sebelum RolesAndPermissionsSeeder — assignRole akan gagal');
} else if (iAdminUser !== -1) ok('akun contoh dibuat setelah peran');

// Data contoh tidak boleh ikut di produksi.
if (dbSrc.includes('DummyDataSeeder') && !/environment\('local', 'testing'\)/.test(dbSrc)) {
  fail('DummyDataSeeder dipanggil tanpa penjagaan environment');
} else ok('data contoh dibatasi local/testing');

// Kolom email tidak ada di skema Seekitar (identitas memakai nomor telepon).
if (/'email'/.test(dbSrc)) fail("DatabaseSeeder menyebut kolom 'email' yang tidak ada di skema");
else ok('tidak memakai kolom email');

// ───────────────────────────────────────────── 7. Migrasi permission
console.log('\nMigrasi tabel permission');
const migDir = path.join(ROOT, 'seekitar-server/database/migrations');
const permMig = fs.readdirSync(migDir).find(f => f.includes('permission_tables'));
if (!permMig) {
  fail('migrasi tabel permission tidak ada — assignRole() akan gagal');
} else {
  const src = fs.readFileSync(path.join(migDir, permMig), 'utf8');
  // users.id adalah UUID, jadi kolom morph WAJIB uuid — bukan bigInteger.
  if (/unsignedBigInteger\(\$morphKey\)|unsignedBigInteger\(\$columnNames\['model_morph_key'\]\)/.test(src)) {
    fail('kolom morph memakai unsignedBigInteger, padahal users.id berupa UUID');
  } else ok('kolom morph bertipe uuid');

  const cfg = read('seekitar-server/config/permission.php');
  if (!/'model_morph_key' => 'model_uuid'/.test(cfg)) {
    fail("config/permission.php: model_morph_key harus 'model_uuid' untuk primary key UUID");
  } else ok("config: model_morph_key = 'model_uuid'");
}

// User model butuh trait HasRoles, kalau tidak assignRole() tidak ada.
const userSrc = read('seekitar-server/app/Models/User.php');
if (!/use .*\bHasRoles\b/.test(userSrc)) fail('User model tidak memakai trait HasRoles');
else ok('User memakai HasRoles');

// ───────────────────────────────────────────── N. Jalankan seeder sungguhan
console.log('\nMenjalankan seluruh seeder (SQLite in-memory)');

/*
 * Pemeriksaan statis TIDAK bisa membuktikan seeder berjalan. Kesalahan yang
 * paling sering terjadi di lapisan ini — relasi salah nama, kolom wajib tak
 * terisi, UNIQUE tertabrak di baris ke-300, factory yang menyentuh basis data
 * di definition() — semuanya baru muncul saat benar-benar dieksekusi.
 *
 * Kode keluar diabaikan: pembungkus php-wasm SELALU mengembalikan 0, apa pun
 * exit() dari skrip PHP-nya. Keputusan diambil dari isi keluaran.
 */
let seedOut = '';
try {
  seedOut = execFileSync(path.join(ROOT, 'tools/dev/php'),
    [path.join(ROOT, 'tools/dev/run-seeders.php')],
    { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'], timeout: 600000 });
} catch (e) {
  seedOut = (e.stdout?.toString() ?? '') + (e.stderr?.toString() ?? '');
}

const seedCocok = seedOut.match(/(\d+) masalah/);
const seedMasalah = seedCocok ? Number(seedCocok[1]) : -1;

if (seedMasalah === 0) {
  const jumlahTabel = seedOut.split('\n').filter(l => /^ {2}\w+ +\d+/.test(l)).length;
  ok(`seeder berjalan penuh — ${jumlahTabel} tabel terisi`);
} else if (seedMasalah < 0) {
  // Tidak ada baris ringkasan sama sekali = skripnya mati di tengah jalan.
  fail('seeder tidak selesai:\n     ' + (seedOut.trim().split('\n').slice(-6).join('\n     ') || '(tanpa keluaran)'));
} else {
  const rincian = seedOut.split('\n')
    .filter(l => /GAGAL|KOSONG/.test(l))
    .slice(0, 8);
  fail(`seeder bermasalah (${seedMasalah}):\n     ` + rincian.join('\n     '));
}

console.log(problems === 0
  ? '\n✅ Migrasi & seeder konsisten dengan dokumen.'
  : `\n❌ ${problems} masalah ditemukan.`);
if (problems) process.exitCode = 1;
