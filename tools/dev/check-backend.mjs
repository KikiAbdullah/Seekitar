#!/usr/bin/env node
/**
 * Penjaga kelengkapan Server_Implementation_Guide.md.
 *
 * Jalankan:  node tools/dev/check-backend.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');

const sig = read('Server_Implementation_Guide.md');
const api = read('API_DOCUMENTATION.md');

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);
const check = (label, needles, src = sig) => {
  const missing = needles.filter(n => !src.includes(n));
  if (missing.length) fail(`${label} — hilang: ${missing.join(', ')}`);
  else ok(label);
};

console.log('Konfigurasi & otorisasi');
check('middleware di bootstrap/app.php (#71)', ['bootstrap/app.php', '$middleware->alias([']);
check('rate limiter per nomor (#71)', ["RateLimiter::for('otp'"]);
check('guard sanctum vs web dijelaskan (#72)', ['guard_name', "'defaults' => ['guard' => 'web']"]);
check('Gate::before super-admin (#73)', ['Gate::before', "hasRole('super-admin')"]);
check('daftar Policy (#73)', ['ListingPolicy', 'OrderPolicy', 'ReviewPolicy']);

console.log('\nRouting');
check('route admin ber-middleware auth (#74)', ["Route::middleware(['auth', 'role:admin'])"]);
check('route AJAX datatables (#75)', ["categories/data", "users/data", "orders/data"]);
check('route aksi admin (#82, #83)', ['toggle-status', 'requests/{request}/extend']);
check('api routes konkret', ["Route::middleware('auth:sanctum')", 'profile.complete']);

console.log('\nHalaman admin');
check('chart dashboard (#76)', ['chartData', 'Chart.js', 'chart.umd.min.js']);
check('anti-siklus kategori (#77)', ['NotADescendant', 'turunannya sendiri']);
// Resolusi final #78: kolom level dihapus — persetujuan men-stempel
// verified*_by/at (tulis-sekali, baris dikunci); penolakan membuka ulang antrian.
check('approve/reject user (#78)', ['verifyUser', 'rejectUser', 'lockForUpdate', 'tulis-sekali']);
check('reject toko (#79)', ['rejectStore', 'rejected_reason']);
// Resolusi final #80: filter level hidup di API (whereVerificationLevel);
// panel menampilkan ikon centang KTP + filter status blokir via UsersDataTable.
check('filter datatables (#80)', ['UsersDataTable', 'verification_level', 'is_blocked']);
check('editor operating_hours (#81)', ['operating_hours[{{ $day }}]', 'date_format:H:i']);
check('resolve dispute ubah order (#84)', ['ResolveDisputeRequest', 'membuka kunci']);
check('tabel settings (#85)', ["Schema::create('settings'", 'SettingService']);

console.log('\nController, request, observer');
check('create/edit controller (#86)', ['public function create()', 'public function edit(Category $category)']);
check('FormRequest untuk semua aksi (#87)', ['ResolveDisputeRequest', 'RejectVerificationRequest']);
check('validasi ikon (#88)', ['ValidFontAwesomeIcon']);
check('daftar observer (#89)', ['StoreObserver', 'ListingObserver', 'OrderObserver', 'ReviewObserver']);
check('BroadcastRequestJob lengkap (#90)', ['matchingStores', 'public function failed']);
check('FCM notification (#91)', ['RequestBroadcastNotification', 'kreait/laravel-firebase']);
check('WhatsApp gateway (#92)', ['WhatsAppGateway', 'KirimWaGateway']);
check('scope geospasial (#93)', ['scopeNearby', 'scopeWithDistance', 'HasLocation']);
check('trait response (#94)', ['trait ApiResponse', 'trait WebResponse']);

console.log('\nSeeder, testing, deployment');
check('manage-settings di seeder (#95)', ['manage-settings']);
check('DatabaseSeeder (#96)', ['class DatabaseSeeder', '$this->call([']);
check('daftar test (#97)', ['AcceptOfferTest', 'OrderStateMachineTest', 'Feature Test']);
check('env variables (#98)', ['FIREBASE_PROJECT_ID', 'KIRIMWA_TOKEN', 'SANCTUM_STATEFUL_DOMAINS']);
check('dataTable dikirim ke view (#99)', ["$dataTable->render('admin.categories.index')"]);
check('logika modal edit (#100)', ['function openModal', "_method"]);
check('Telescope (#101)', ['laravel/telescope', 'Telescope::filter']);
check('scheduler cron (#102)', ['schedule:run', 'withoutOverlapping']);
check('supervisor worker (#103)', ['supervisor', 'queue:work redis', 'numprocs']);

console.log('\nKonsistensi lintas dokumen');

// Laravel 11+ tidak lagi punya Kernel.php.
const kernelHits = sig.split('\n').filter(l =>
  /Kernel\.php/.test(l) && !l.includes('sudah tidak ada')
);
if (kernelHits.length) fail(`masih merujuk Kernel.php (dihapus di Laravel 11): "${kernelHits[0].trim().slice(0, 70)}"`);
else ok('tidak ada rujukan Kernel.php yang menyesatkan');

// Nama route admin tidak boleh dobel prefix.
if (/->name\('admin\./.test(sig.slice(sig.indexOf('### Admin Routes'), sig.indexOf('### API Routes')))) {
  fail("nama route admin dobel prefix (->name('admin.…) di grup yang sudah name('admin.'))");
} else {
  ok('nama route admin tidak dobel prefix');
}

// verification_level 0 tidak boleh muncul sebagai anjuran.
if (/verification_level`? *= *0|verification_level.*jadi 0/.test(sig)) {
  fail('menyarankan verification_level = 0, padahal rentangnya 1–3');
} else {
  ok('verification_level tetap 1–3');
}

// Status order yang dipakai di guide harus sama dengan API.
['selesai', 'dibatalkan', 'dispute'].forEach(s => {
  if (!sig.includes(s)) fail(`status '${s}' tidak disebut di Server Guide`);
});
ok('status order konsisten dengan API');

// Endpoint kunci di API harus punya padanan route di guide.
[['/uploads/images', 'uploads/images'], ['/favorites', 'favorites'],
 ['/auth/fcm-token', 'auth/fcm-token'], ['/auth/logout', 'auth/logout']].forEach(([apiPath, routeFrag]) => {
  if (api.includes(apiPath) && !sig.includes(routeFrag)) {
    fail(`endpoint ${apiPath} ada di API docs tapi tidak ada route-nya di Server Guide`);
  }
});
ok('endpoint API punya padanan route di Server Guide');

console.log(problems === 0
  ? '\n✅ Server Implementation Guide konsisten.'
  : `\n❌ ${problems} masalah ditemukan.`);
if (problems) process.exitCode = 1;
