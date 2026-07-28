#!/usr/bin/env node
/**
 * Penjaga LAPISAN HTTP: route, controller, resource, middleware, policy.
 *
 * KENAPA CHECKER INI ADA
 * ----------------------
 * Endpoint yang hilang tidak menimbulkan error di mana pun — ia hanya
 * membalas 404, dan baru ketahuan saat klien mobile memanggilnya. Begitu pula
 * resource yang tanpa sengaja membocorkan NIK: responsnya tetap 200.
 *
 * Sumber kebenaran endpoint adalah API_DOCUMENTATION.md; sumber kebenaran
 * route adalah `artisan route:list`, bukan pembacaan berkas.
 *
 * Jalankan:  node tools/dev/check-http.mjs
 */
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');
const exists = f => fs.existsSync(path.join(ROOT, f));

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);

// ─────────────────────────────────── 1. Setiap endpoint dokumen punya route
console.log('Endpoint dokumen ⇄ route terdaftar');

const documented = new Set(
  [...read('API_DOCUMENTATION.md').matchAll(/^(GET|POST|PUT|PATCH|DELETE) (\/[a-z0-9/{}_-]+)/gm)]
    .map(([, method, uri]) => `${method} ${uri.replace(/\{[a-z_]+\}/g, '{X}')}`)
);

let routes = null;
try {
  routes = JSON.parse(execFileSync(path.join(ROOT, 'tools/dev/artisan'), ['route:list', '--json'], {
    encoding: 'utf8', maxBuffer: 32 * 1024 * 1024, stdio: ['ignore', 'pipe', 'ignore'],
  }));
} catch {
  console.log('  ⚠️  route:list gagal (jalankan ./tools/dev/setup). Bagian ini dilewati.');
}

if (routes) {
  const implemented = new Set();
  for (const r of routes.filter(x => x.uri.startsWith('api/v1/'))) {
    const uri = '/' + r.uri.replace('api/v1/', '').replace(/\{[a-zA-Z]+\}/g, '{X}');
    for (const m of r.method.split('|')) {
      if (m !== 'HEAD') implemented.add(`${m} ${uri}`);
    }
  }

  const missing = [...documented].filter(e => !implemented.has(e)).sort();
  if (missing.length) fail(`endpoint terdokumentasi tanpa route: ${missing.join(', ')}`);
  else ok(`${documented.size} endpoint terdokumentasi semuanya punya route`);

  // Semua route API wajib di balik autentikasi, KECUALI alur OTP yang
  // memang dipakai sebelum pengguna punya token.
  const PUBLIC_OK = ['api/v1/auth/request-otp', 'api/v1/auth/verify-otp'];
  const unguarded = routes
    .filter(r => r.uri.startsWith('api/v1/'))
    .filter(r => !PUBLIC_OK.includes(r.uri))
    .filter(r => !r.middleware.some(m => m.includes('Authenticate:sanctum')));

  if (unguarded.length) fail(`route API tanpa auth:sanctum: ${unguarded.map(r => r.uri).join(', ')}`);
  else ok('semua route API non-publik memakai auth:sanctum');

  // Endpoint OTP harus dibatasi PER NOMOR, bukan hanya throttle umum.
  for (const uri of PUBLIC_OK) {
    const r = routes.find(x => x.uri === uri);
    if (!r) { fail(`route ${uri} tidak ada`); continue; }
    if (!r.middleware.some(m => /ThrottleRequests:otp/.test(m))) {
      fail(`${uri} tanpa throttle khusus OTP — bisa dibanjiri percobaan`);
    }
  }
  ok('endpoint OTP memakai throttle khusus');

  // Aksi transaksional butuh profil lengkap (nama + lokasi).
  const MUST_GUARD = ['api/v1/orders', 'api/v1/requests', 'api/v1/stores'];
  for (const uri of MUST_GUARD) {
    const r = routes.find(x => x.uri === uri && x.method.includes('POST'));
    if (!r) { fail(`route POST ${uri} tidak ada`); continue; }
    if (!r.middleware.some(m => m.includes('EnsureProfileComplete'))) {
      fail(`POST ${uri} tanpa profile.complete`);
    }
  }
  ok('aksi transaksional memakai profile.complete');

  // PATCH /auth/profile TIDAK boleh memakai profile.complete — kalau
  // dipasang, pengguna baru terkunci dan tak bisa melengkapi profilnya.
  const profile = routes.find(x => x.uri === 'api/v1/auth/profile');
  if (profile?.middleware.some(m => m.includes('EnsureProfileComplete'))) {
    fail('PATCH /auth/profile memakai profile.complete — pengguna baru akan terkunci');
  } else ok('PATCH /auth/profile bebas dari profile.complete');

  // Nama route admin tidak boleh dobel prefix.
  const doubled = routes.filter(r => r.name?.startsWith('admin.admin.'));
  if (doubled.length) fail(`nama route dobel prefix: ${doubled.map(r => r.name).join(', ')}`);
  else ok('nama route admin tidak dobel prefix');
}

// ─────────────────────────────────── 2. Resource tidak membocorkan data
console.log('\nResource tidak membocorkan data pribadi');
const SENSITIVE = ['nik', 'nik_hash', 'blocked_reason', 'remember_token'];
const resourceDir = path.join(ROOT, 'seekitar-server/app/Http/Resources');
if (!fs.existsSync(resourceDir)) fail('app/Http/Resources tidak ada');
else {
  let leaked = 0;
  for (const f of fs.readdirSync(resourceDir).filter(x => x.endsWith('.php'))) {
    const src = fs.readFileSync(path.join(resourceDir, f), 'utf8');
    for (const key of SENSITIVE) {
      if (new RegExp(`'${key}'\\s*=>`).test(src)) { fail(`${f} membocorkan '${key}'`); leaked++; }
    }
  }
  if (!leaked) ok(`${SENSITIVE.length} kolom sensitif tidak muncul di resource mana pun`);
}

// ─────────────────────────────────── 3. Amplop respons seragam
console.log('\nAmplop respons & penanganan error');
if (!exists('seekitar-server/app/Http/Concerns/ApiResponse.php')) {
  fail('trait ApiResponse tidak ada');
} else {
  const src = read('seekitar-server/app/Http/Concerns/ApiResponse.php');
  for (const m of ['function ok', 'function created', 'function fail', 'function paginated']) {
    if (!src.includes(m)) fail(`ApiResponse kurang ${m}()`);
  }
  // per_page WAJIB dibatasi; tanpa itu ?per_page=100000 menarik satu tabel.
  if (!/min\(/.test(src)) fail('ApiResponse::perPage() tidak membatasi nilai maksimum');
  else ok('ApiResponse lengkap & per_page dibatasi');
}

const bootstrap = read('seekitar-server/bootstrap/app.php');
for (const alias of ['profile.complete', 'store.owner', 'permission', 'role']) {
  if (!bootstrap.includes(`'${alias}'`)) fail(`alias middleware '${alias}' belum didaftarkan`);
}
ok('alias middleware terdaftar');

if (!/ValidationException/.test(bootstrap)) {
  fail('error API tidak dipetakan ke amplop seragam');
} else ok('error API memakai amplop { success, message, errors }');

// ─────────────────────────────────── 4. Blade admin: XSS
console.log('\nBlade admin');
const viewDir = path.join(ROOT, 'seekitar-server/resources/views/admin');
if (!fs.existsSync(viewDir)) fail('resources/views/admin tidak ada');
else {
  const blades = [];
  (function walk(dir) {
    for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
      const p = path.join(dir, e.name);
      e.isDirectory() ? walk(p) : e.name.endsWith('.blade.php') && blades.push(p);
    }
  })(viewDir);

  if (blades.length < 5) fail(`hanya ${blades.length} view admin — panel belum lengkap`);
  else ok(`${blades.length} view admin`);

  // {!! !!} MEMATIKAN escaping Blade — itu sumber XSS, bukan solusinya.
  // Komentar Blade {{-- --}} dibuang dulu: catatan yang MELARANG sintaks itu
  // justru harus menyebutkannya, dan itu bukan pelanggaran.
  const stripBladeComments = s => s.replace(/\{\{--[\s\S]*?--\}\}/g, '');
  const unescaped = blades.filter(p => /\{!!/.test(stripBladeComments(fs.readFileSync(p, 'utf8'))));
  if (unescaped.length) {
    fail(`{!! !!} dipakai di: ${unescaped.map(p => path.basename(p)).join(', ')} — mematikan escaping`);
  } else ok('tidak ada {!! !!} — escaping Blade aktif di semua view');

  // Setiap form POST butuh @csrf.
  const noCsrf = blades.filter(p => {
    const s = fs.readFileSync(p, 'utf8');
    return /method="POST"/i.test(s) && !s.includes('@csrf');
  });
  if (noCsrf.length) fail(`form tanpa @csrf: ${noCsrf.map(p => path.basename(p)).join(', ')}`);
  else ok('semua form POST memakai @csrf');
}

console.log(problems === 0
  ? '\n✅ Lapisan HTTP konsisten dengan dokumen.'
  : `\n❌ ${problems} masalah ditemukan.`);
if (problems) process.exitCode = 1;
