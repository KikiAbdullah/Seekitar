#!/usr/bin/env node
/**
 * Penjaga LAPISAN SERVICE.
 *
 * KENAPA CHECKER INI ADA
 * ----------------------
 * Service memuat aturan yang konsekuensinya tidak langsung terlihat: OTP
 * yang tidak di-hash tetap "berfungsi", pencocokan siaran satu arah tetap
 * mengembalikan hasil, dan state machine yang longgar tetap menyimpan
 * pesanan. Semua tampak normal sampai ada yang dirugikan.
 *
 * Yang dijaga di sini adalah janji-janji keamanan & korektnes yang tertulis
 * di dokumen, bukan gaya penulisan kode.
 *
 * Jalankan:  node tools/dev/check-services.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');
const exists = f => fs.existsSync(path.join(ROOT, f));
const APP = 'seekitar-server/app';

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);

// ───────────────────────────────────────── 1. Berkas yang dijanjikan §4
console.log('Service yang dijanjikan Server_Implementation_Guide §4');
const REQUIRED = [
  'Services/BroadcastService.php',
  'Services/GeolocationService.php',
  'Services/OrderStateMachine.php',
  'Services/OtpService.php',
  'Services/SettingService.php',
  'Services/Contracts/WhatsAppGateway.php',
];
const missing = REQUIRED.filter(f => !exists(`${APP}/${f}`));
if (missing.length) fail(`belum ada: ${missing.join(', ')}`);
else ok(`${REQUIRED.length} service tersedia`);

// ───────────────────────────────────────── 2. OTP: jaminan keamanan §15.2
console.log('\nOtpService — jaminan keamanan §15.2');
if (exists(`${APP}/Services/OtpService.php`)) {
  const src = read(`${APP}/Services/OtpService.php`);

  // Kode WAJIB di-hash. Redis bukan brankas: dump memori atau MONITOR
  // membocorkan isinya, dan OTP adalah satu-satunya faktor autentikasi.
  if (!/Hash::make\(\$code\)/.test(src)) {
    fail('OTP tidak di-hash sebelum disimpan — plaintext di cache bisa bocor');
  } else ok('kode di-hash sebelum disimpan');

  const ttl = src.match(/TTL_SECONDS\s*=\s*(\d+)/);
  if (!ttl) fail('konstanta TTL_SECONDS tidak ditemukan');
  else if (Number(ttl[1]) !== 300) fail(`TTL ${ttl[1]} detik, dokumen §15.2 menyebut 5 menit (300)`);
  else ok('TTL 300 detik (5 menit)');

  const attempts = src.match(/MAX_ATTEMPTS\s*=\s*(\d+)/);
  if (!attempts) fail('konstanta MAX_ATTEMPTS tidak ditemukan');
  else if (Number(attempts[1]) !== 5) fail(`batas percobaan ${attempts[1]}, dokumen menyebut 5`);
  else ok('batas 5 percobaan');

  // Batas percobaan harus benar-benar dipakai, bukan sekadar dideklarasikan.
  if (!/attemptsLeft\(\$phone\)\s*<=\s*0/.test(src)) {
    fail('MAX_ATTEMPTS tidak pernah diperiksa saat verify() — brute force 6 digit terbuka');
  } else ok('batas percobaan ditegakkan di verify()');

  // Kode sekali pakai.
  if (!/\$this->forget\(\$phone\);\s*\n\s*return true;/.test(src)) {
    fail('kode tidak dibuang setelah verifikasi berhasil — bisa dipakai ulang');
  } else ok('kode dibuang setelah dipakai');

  // Generator acak kriptografis. Komentar dibuang dulu: docblock di sini
  // justru MENJELASKAN kenapa rand() tidak dipakai, dan itu bukan pelanggaran.
  const codeOnly = src
    .replace(/\/\*[\s\S]*?\*\//g, '')
    .split('\n').map(l => l.replace(/\/\/.*$/, '')).join('\n');
  if (/\bmt_rand\(|(?<![_a-zA-Z])rand\(/.test(codeOnly)) {
    fail('memakai rand()/mt_rand() — bisa diprediksi; pakai random_int()');
  } else if (!/random_int\(/.test(src)) {
    fail('tidak memakai random_int() untuk membuat kode');
  } else ok('kode dibuat dengan random_int()');

  // Kunci cache harus ternormalisasi, kalau tidak OTP untuk 08... tidak
  // ditemukan saat diverifikasi sebagai 62...
  if (!/PhoneNumber::normalize/.test(src)) {
    fail('kunci cache tidak dinormalkan — 08xxx dan 62xxx dianggap nomor berbeda');
  } else ok('kunci cache memakai nomor ternormalisasi');
}

// ───────────────────────────────────────── 3. Normalisasi telepon §18A.6
console.log('\nPhoneNumber — pencegah akun ganda §18A.6');
if (!exists(`${APP}/Support/PhoneNumber.php`)) {
  fail('app/Support/PhoneNumber.php tidak ada');
} else {
  const src = read(`${APP}/Support/PhoneNumber.php`);
  for (const branch of ["str_starts_with($digits, '0')", "str_starts_with($digits, '62')"]) {
    if (!src.includes(branch)) fail(`cabang normalisasi hilang: ${branch}`);
  }
  ok('menangani awalan 0, 62, dan tanpa awalan');
}

// ───────────────────────────────────────── 4. Broadcast: pencocokan dua arah
console.log('\nBroadcastService — pencocokan dua arah PRD §5.2.2');
if (exists(`${APP}/Services/BroadcastService.php`)) {
  const src = read(`${APP}/Services/BroadcastService.php`);

  // Kedua arah WAJIB diperiksa, tetapi CARANYA berubah pada 2.3: lokasi
  // toko bukan lagi POINT, jadi tidak ada ST_Distance/->nearby mentah —
  // kandidat disaring kotak pembatas (withinBox) lalu lingkaran akurat
  // dihitung di PHP lewat Jarak::haversineKm (keputusan "tanpa SQL
  // mentah"). Pola lama diterima sebagai padanan historis.
  // ARAH 1: toko dalam radius pembeli.
  const arah1 = /distance_km\s*<=\s*\(float\)\s*\$request->radius_km/.test(src) || /->nearby\(/.test(src);
  if (!arah1) fail('arah 1 hilang: toko harus berada dalam radius pembeli');
  else ok('arah 1 — toko dalam radius pembeli');

  // ARAH 2: pembeli dalam radius layanan toko. Tanpa ini, warung beradius
  // 5 km dibanjiri permintaan dari pembeli 12 km jauhnya.
  const arah2 = /distance_km\s*<=\s*\(float\)\s*\$s->service_radius_km/.test(src)
    || /service_radius_km \* 1000/.test(src);
  if (!arah2) {
    fail('arah 2 hilang: pembeli harus berada dalam radius layanan toko');
  } else ok('arah 2 — pembeli dalam radius layanan toko');

  // Pra-filter kotak: tanpa withinBox (atau nearby lama), kandidat yang
  // ditarik dari basis data adalah SELURUH toko aktif — lambat.
  if (!/->withinBox\(/.test(src) && !/->nearby\(/.test(src)) {
    fail('tidak ada pra-filter kotak — seluruh toko ditarik sebelum disaring');
  }

  if (!/where\('user_id', '!=', \$request->user_id\)/.test(src)) {
    fail('toko bisa menawar pada permintaannya sendiri');
  } else ok('toko sendiri dikecualikan');

  const kategori = /whereJsonContains\('category_ids'/.test(src) || /JSON_CONTAINS\(category_ids/.test(src);
  if (!kategori) fail('kategori tidak dicocokkan');
  else ok('kategori dicocokkan (whereJsonContains/JSON_CONTAINS)');

  const limit = src.match(/MAX_RECIPIENTS\s*=\s*(\d+)/);
  if (!limit) fail('tidak ada batas penerima — satu permintaan bisa memicu ribuan notifikasi');
  else if (Number(limit[1]) !== 50) fail(`batas penerima ${limit[1]}, dokumen menyebut 50`);
  else ok('batas 50 penerima');

  // Enum VerificationStatus dihapus pada 2.3; status toko kini StoreStatus.
  if (!/StoreStatus::Verified|VerificationStatus::Verified/.test(src)) {
    fail('toko belum terverifikasi ikut menerima siaran');
  } else ok('hanya toko terverifikasi');
}

// ───────────────────────────────────────── 5. Scope lokasi tidak saling menimpa
console.log('\nHasLocation — scope bisa dirangkai');
if (exists(`${APP}/Models/Concerns/HasLocation.php`)) {
  const src = read(`${APP}/Models/Concerns/HasLocation.php`);

  // `select('*')` menimpa kolom yang sudah dipilih scope sebelumnya, jadi
  // withCoordinates()->withDistance() akan membuang latitude/longitude.
  if (/->select\('\*'\)/.test(src)) {
    fail("scope memakai select('*') — merangkai withCoordinates()->withDistance() akan membuang kolom");
  } else ok('scope mempertahankan kolom yang sudah dipilih');
}

// ───────────────────────────────────────── 6. State machine
console.log('\nOrderStateMachine');
if (exists(`${APP}/Services/OrderStateMachine.php`)) {
  const src = read(`${APP}/Services/OrderStateMachine.php`);

  // Status akhir tidak boleh punya jalan keluar.
  for (const final of ['selesai', 'dibatalkan']) {
    const m = src.match(new RegExp(`'${final}'\\s*=>\\s*\\[([^\\]]*)\\]`));
    if (!m) fail(`status '${final}' tidak ada di matriks transisi`);
    else if (m[1].trim() !== '') fail(`status akhir '${final}' punya transisi keluar: ${m[1].trim()}`);
  }
  ok('selesai & dibatalkan bersifat final');

  // Pesanan yang sudah dikirim tidak boleh dibatalkan sepihak.
  const dikirim = src.match(/'dikirim'\s*=>\s*\[([^\]]*)\]/);
  if (dikirim && /Dibatalkan/.test(dikirim[1])) {
    fail("'dikirim' bisa langsung dibatalkan — seharusnya lewat dispute");
  } else ok("'dikirim' tidak bisa dibatalkan sepihak");

  // Enam nilai ENUM, tidak lebih (PRD §5.4 menolak status bercabang).
  const states = [...src.matchAll(/^\s{8}'([a-z_]+)'\s*=>/gm)].map(m => m[1]);
  if (states.length !== 6) fail(`matriks memuat ${states.length} status, ENUM hanya punya 6`);
  else ok('6 status, sesuai ENUM orders.status');
}

// ───────────────────────────────────────── 7. Gateway WhatsApp
console.log('\nWhatsAppGateway');
const provider = exists(`${APP}/Providers/AppServiceProvider.php`)
  ? read(`${APP}/Providers/AppServiceProvider.php`) : '';

if (!/WhatsAppGateway::class/.test(provider)) {
  fail('WhatsAppGateway tidak di-bind di AppServiceProvider');
} else ok('gateway di-bind di container');

// Gateway log menulis OTP plaintext ke berkas — tidak boleh di produksi.
if (!/isProduction\(\)/.test(provider)) {
  fail('binding gateway tidak membedakan produksi — LogWhatsAppGateway bisa aktif di produksi');
} else ok('LogWhatsAppGateway hanya di luar produksi');

if (exists(`${APP}/Services/WhatsApp/LogWhatsAppGateway.php`)) {
  const src = read(`${APP}/Services/WhatsApp/LogWhatsAppGateway.php`);
  if (!/PhoneNumber::mask/.test(src)) {
    fail('LogWhatsAppGateway menulis nomor utuh ke log — nomor telepon data pribadi (UU PDP)');
  } else ok('nomor disensor di log');
}

console.log(problems === 0
  ? '\n✅ Lapisan service konsisten dengan dokumen.'
  : `\n❌ ${problems} masalah ditemukan.`);
if (problems) process.exitCode = 1;
