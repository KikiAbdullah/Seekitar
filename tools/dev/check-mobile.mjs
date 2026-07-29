#!/usr/bin/env node
/**
 * Penjaga kelengkapan Mobile_Implementation_Guide.md.
 *
 * Jalankan:  node tools/dev/check-mobile.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');

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
check('provider global dio/storage (#104)', ['@Riverpod(keepAlive: true)', 'Dio dio(Ref ref)']);
check('error handling AuthNotifier (#105)', ['UnauthorizedException', 'NetworkException']);
check('paginasi nearby stores (#106)', ['loadMore', '_isLoadingMore']);
check('interceptor 401/423 (#107)', ['onError(DioException', 'status == 401']);
check('model tanpa freezed (#108)', ['@JsonSerializable()', 'field_rename: snake']);
check('mapping DioException (#109)', ['mapDioException', 'ValidationException']);

console.log('\nPresentation & navigasi');
check('navigasi ke OTP (#110)', ['context.push(Routes.otp']);
check('loading state submit (#111)', ['_isSubmitting', 'CircularProgressIndicator']);
check('order bottom sheet (#112)', ['showModalBottomSheet', 'OrderFormSheet']);
check('router sebagai provider (#113)', ['GoRouter goRouter(Ref ref)', 'StatefulShellRoute']);
check('MainShell (#114)', ['StatefulNavigationShell', 'shell.goBranch']);
check('search debounce (#115)', ['Timer(const Duration(milliseconds: 500)', '_debounce?.cancel()']);
check('filter chips kategori (#116)', ['CategoryChips', 'FilterChip']);
check('google maps picker (#117)', ['GoogleMap(', 'onCameraIdle']);
check('TabBar requests (#118)', ['TabController', 'TabBarView']);
check('sorting offers (#119)', ['OfferSort', 'sortedOffers']);

console.log('\nIntegrasi platform');
check('FCM permission iOS (#120)', ['requestPermission(', 'getAPNSToken']);
check('FCM foreground (#121)', ['FirebaseMessaging.onMessage.listen', 'flutter_local_notifications']);
check('fallback WhatsApp (#122)', ['_showFallback', 'Salin Nomor']);
check('google_fonts (#123)', ['GoogleFonts.plusJakartaSansTextTheme', 'allowRuntimeFetching']);
check('shimmer (#124)', ['Shimmer.fromColors', 'ListingCardSkeleton']);
check('guard loadMore (#125)', ['!_hasMore || _isLoadingMore']);
check('error handling storage (#126)', ['on PlatformException', 'StorageService']);

console.log('\nBab baru');
check('environment dart-define (#127)', ['--dart-define-from-file', 'String.fromEnvironment']);
check('error handling global (#128)', ['FlutterError.onError', 'ErrorWidget.builder', 'runZonedGuarded']);
check('analytics (#129)', ['AnalyticsService', 'FirebaseAnalyticsObserver']);
check('deep link (#130)', ['DeepLinkService', 'autoVerify="true"']);

console.log('\nKonsistensi lintas dokumen');

// Riverpod 3: subclass Ref hasil codegen sudah dihapus.
const refHits = mig.split('\n').filter((l, i, arr) => {
  if (!/\b(?!Widget)[A-Z][A-Za-z]*Ref\s+ref\b/.test(l)) return false;
  // Blok "contoh salah" ditandai komentar di 2 baris sebelumnya.
  return !arr.slice(Math.max(0, i - 2), i).some(p => p.includes('❌') || p.includes('Riverpod 2'));
});
if (refHits.length) fail(`subclass Ref codegen masih dipakai: "${refHits[0].trim().slice(0, 60)}"`);
else ok('memakai Ref langsung (Riverpod 3)');

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

// Rute yang dipanggil harus terdaftar di kelas Routes.
const routesBlock = mig.slice(mig.indexOf('abstract final class Routes'), mig.indexOf('abstract final class Routes') + 1400);
['completeProfile', 'orderDetailOf', 'requestDetailOf', 'listingDetailOf', 'splash'].forEach(r => {
  if (mig.includes(`Routes.${r}`) && !routesBlock.includes(r)) {
    fail(`Routes.${r} dipakai tapi tidak dideklarasikan di kelas Routes`);
  }
});
ok('semua Routes.* yang dipakai sudah dideklarasikan');

console.log(problems === 0
  ? '\n✅ Mobile Implementation Guide konsisten.'
  : `\n❌ ${problems} masalah ditemukan.`);
if (problems) process.exitCode = 1;
