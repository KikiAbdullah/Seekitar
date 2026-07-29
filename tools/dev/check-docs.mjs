#!/usr/bin/env node
/**
 * Penjaga UI/UX, queue, testing, notifikasi, event, response, geospasial,
 * dan format dokumen (TODO_BUG bagian M–T).
 *
 * Jalankan:  node tools/dev/check-docs.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');
const exists = f => fs.existsSync(path.join(ROOT, f));

const sig = read('Server_Implementation_Guide.md');
const mig = read('Mobile_Implementation_Guide.md');
const api = read('API_DOCUMENTATION.md');
const db = read('DATABASE.md');
const brand = read('BRANDING-GUIDELINE.md');
const prd = read('PRD.md');
const tech = read('TECH_STACK.md');

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);
const check = (label, needles, src) => {
  const missing = needles.filter(n => !src.includes(n));
  if (missing.length) fail(`${label} — hilang: ${missing.join(', ')}`);
  else ok(label);
};

console.log('M. UI/UX');
check('menu admin per-permission (#226)', ['@canany', '@can('], sig);
check('breadcrumb (#227)', ["@section('breadcrumb')", 'aria-current'], sig);
check('kolom datatable orderable (#228)', ['orderable(false)'], sig);
check('shimmer (#229)', ['Shimmer.fromColors'], mig);
check('empty state (#230)', ['EmptyState'], mig);
check('spesifikasi FAB (#231)', ['Floating Action Button (FAB)', '56×56 dp'], brand);
check('badge bottom nav (#232)', ['Badge Notifikasi pada', '9+'], brand);

console.log('\nN. Queue & Job');
check('BroadcastRequestJob (#233)', ['matchingStores', 'JSON_CONTAINS'], sig);
check('job rating (#234)', ['RecalculateStoreRatingJob'], sig);
check('command expired (#235)', ['CloseExpiredRequests', 'requests:close-expired'], sig);
check('retry policy (#236)', ['$tries', '$backoff'], sig);

console.log('\nO. Testing');
check('test admin (#237)', ['AdminTest'], sig);
check('test geospasial (#238)', ['GeolocationServiceTest'], sig);
check('test Sanctum (#239)', ['AuthTest'], sig);
check('widget test (#240)', ['testWidgets', 'overrideWithValue'], mig);
check('integration test (#241)', ['integration_test', 'IntegrationTestWidgetsFlutterBinding'], mig);

console.log('\nP. Notifikasi');
check('payload FCM (#242)', ['withNotification', 'entity_id'], sig);
check('template OTP (#243)', ['Kode OTP Seekitar'], sig);
check('multi-perangkat (#244)', ['sendMulticast', 'invalidTokens'], sig);
check('pelacakan pengiriman (#245)', ['logDelivery', 'notification_opened'], sig);
check('navigasi dari notifikasi (#246)', ['onMessageOpenedApp', 'getInitialMessage'], mig);

console.log('\nQ. Event & Listener');
check('daftar event (#247)', ['CustomerRequestCreated', 'OfferAccepted', 'OrderStatusChanged'], sig);
check('daftar listener (#248)', ['DispatchRequestBroadcast', 'SendOfferAcceptedNotification'], sig);

console.log('\nR. API Response');
check('meta paginasi (#249)', ['last_page', 'current_page'], api);
check('contoh 422 lengkap (#250)', ['Kode OTP harus 6 digit', 'operating_hours.senin.close'], api);
check('trait response (#251)', ['trait ApiResponse', 'trait WebResponse'], sig);

console.log('\nS. Geospasial');
check('urutan POINT (#252)', ['POINT(longitude latitude)'], db);
check('reverse geocoding (#253)', ['GeocodingService', 'reverse('], sig);
check('konversi km ke meter (#254)', ['* 1000'], db);
check('kolom address (#255)', ['`address`'], db);

console.log('\nT. Format & Dokumentasi');
check('kebijakan versi (#256, #257)', ['VERSI & TANGGAL DOKUMEN', 'bukan salah ketik'], tech);
if (exists('README.md')) ok('README.md ada (#258)');
else fail('README.md tidak ada (#258)');
if (exists('CONTRIBUTING.md')) ok('CONTRIBUTING.md ada (#258)');
else fail('CONTRIBUTING.md tidak ada (#258)');
if (exists('seekitar_mobile/analysis_options.yaml')) {
  const lint = read('seekitar_mobile/analysis_options.yaml');
  check('aturan linting (#259)', ['use_build_context_synchronously', 'unawaited_futures', '*.g.dart'], lint);
} else fail('analysis_options.yaml tidak ada (#259)');
check('koleksi API (#260)', ['KOLEKSI API', 'openapi'], api);

console.log('\nKonsistensi versi dokumen');
const VER = '2.2';
const docs = {
  'PRD.md': prd, 'DATABASE.md': db, 'API_DOCUMENTATION.md': api,
  'Server_Implementation_Guide.md': sig, 'Mobile_Implementation_Guide.md': mig,
  'BRANDING-GUIDELINE.md': brand,
};
const wrong = Object.entries(docs).filter(([, s]) => {
  const m = s.match(/\*\*Versi(?: Dokumen)?:?\*\*\s*\|?\s*([\d.]+)/);
  return !m || m[1] !== VER;
}).map(([f]) => f);
if (wrong.length) fail(`versi dokumen tidak seragam (harus ${VER}): ${wrong.join(', ')}`);
else ok(`enam dokumen seragam di versi ${VER}`);

console.log(problems === 0
  ? '\n✅ Dokumentasi M–T konsisten.'
  : `\n❌ ${problems} masalah ditemukan.`);
if (problems) process.exitCode = 1;
