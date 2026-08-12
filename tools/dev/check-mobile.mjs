#!/usr/bin/env node
/**
 * Penjaga kelengkapan Mobile_Implementation_Guide.md.
 *
 * Selaras dengan implementasi saat ini: state management `provider`
 * (ChangeNotifier), peta OpenStreetMap (`flutter_osm_plugin`), FCM + notifikasi
 * lokal, tanpa codegen (`build_runner`/`freezed`), tanpa analytics, tanpa
 * `app_links`/deep link kustom.
 *
 * Jalankan:  node tools/dev/check-mobile.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8').replace(/\r\n/g, '\n');

const mig = read('Mobile_Implementation_Guide.md');
const api = read('API_DOCUMENTATION.md');

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);
const check = (label, needles, src = mig) => {
  const missing = needles.filter(n => !src.includes(n));
  if (missing.length) fail(`${label} — hilang: ${missing.join(', ')}`);
  else ok(label);
};

console.log('State management & data layer');
check('provider global AppState (#104)', ['ChangeNotifier', 'ChangeNotifierProvider.value', 'AppState']);
check('error handling layar (#105)', ['_friendlyError']);
check('paginasi nearby stores (#106)', ['loadMore', '_isLoadingMore']);
check('interceptor 401/423 (#107)', ['statusCode == 401', '423']);
check('model tanpa freezed (#108)', ['fromJson', 'snake_case']);
check('mapping DioException (#109)', ['_friendlyError']);

console.log('\nPresentation & navigasi');
check('login satu layar OTP (#110)', ['_otpSent', '_countdown', 'requestOtp']);
check('loading state submit (#111)', ['_isSubmitting', 'CircularProgressIndicator']);
check('checkout sebagai layar (#112)', ['/checkout', 'CheckoutScreen']);
check('router statis appRouter (#113)', ['appRouter', 'StatefulShellRoute']);
check('MainShell (#114)', ['StatefulNavigationShell', 'goBranch']);
check('search debounce (#115)', ['Timer(const Duration(milliseconds: 350)', '_debounce?.cancel()']);
check('filter chips kustom (#116)', ['GestureDetector', '_chip(']);
check('peta OpenStreetMap (#117)', ['flutter_osm_plugin', 'location_picker_screen.dart']);
check('TabBar requests (#118)', ['TabController', 'Terdekat', 'Saya']);
check('sorting penawaran (#119)', ['urutkan', 'menerima']);

console.log('\nIntegrasi platform');
check('FCM izin Android (#120)', ['requestNotificationsPermission', 'Android 13']);
check('FCM foreground (#121)', ['FirebaseMessaging.onMessage.listen', 'flutter_local_notifications']);
check('WhatsApp deep link (#122)', ['launchUrl', 'wa.me']);
check('font Plus Jakarta Sans (#123)', ['fontFamily', 'Plus Jakarta Sans']);
check('skeleton kustom (#124)', ['_skeleton()', 'shimmer']);
check('guard loadMore (#125)', ['!_hasMore || _isLoadingMore']);
check('penyimpanan token terpusat (#126)', ['jwt_token', 'DioClient']);

console.log('\nBab baru');
check('environment dart-define (#127)', ['--dart-define', 'String.fromEnvironment']);
check('error handling global (#128)', ['FlutterError.onError', 'runZonedGuarded']);
check('tanpa analytics (#129)', ['FirebaseAnalytics']);
check('deep link via FCM (#130)', ['app_links', 'onMessageOpenedApp', 'getInitialMessage()']);

console.log('\nKonsistensi lintas dokumen');

// Implementasi memakai provider — pola Riverpod (Ref/ProviderScope/@Riverpod)
// tidak boleh muncul sebagai pola aktif (kalimat negasi seperti "Tidak ada
// `ProviderScope`/`ref`" di §4 diizinkan).
const refHits = mig.split('\n').filter((l, i, arr) => {
  if (!/@Riverpod\(|ProviderScope|goRouter\(Ref|Dio dio\(Ref/.test(l)) return false;
  // Kalimat negasi ("Tidak ada", "bukan Riverpod") boleh di baris yang sama
  // atau beberapa baris di atas (judul "Kenapa bukan Riverpod?").
  const ctx = arr.slice(Math.max(0, i - 3), i + 1).join(' ');
  return !/(Tidak ada|bukan|❌|diizinkan)/.test(ctx);
});
if (refHits.length) fail(`pola Riverpod masih dipakai sebagai pola aktif: "${refHits[0].trim().slice(0, 60)}"`);
else ok('memakai provider/ChangeNotifier (bukan Riverpod)');

// freezed sudah dibuang dari daftar library, jadi tidak boleh dipakai di contoh.
const freezedHits = mig.split('\n').filter(l =>
  /^\s*@freezed/.test(l) || /\bwith _\$\w+\b/.test(l)
);
if (freezedHits.length) fail(`@freezed masih dipakai padahal dibuang dari daftar library (§2): ${freezedHits.length} baris`);
else ok('tidak memakai @freezed (konsisten dengan §2)');

// Base URL harus sama dengan API docs.
['10.0.2.2:8000', 'staging-api.seekitar.id', 'api.seekitar.id'].forEach(u => {
  if (api.includes(u) && !mig.includes(u)) fail(`base URL ${u} ada di API docs tapi tidak di Mobile Guide`);
});
ok('base URL selaras dengan API docs');

console.log(problems === 0
  ? '\n✅ Mobile Implementation Guide konsisten.'
  : `\n❌ ${problems} masalah ditemukan.`);
if (problems) process.exitCode = 1;
