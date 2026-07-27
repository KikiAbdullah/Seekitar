#!/usr/bin/env node
/**
 * Penjaga kelengkapan panduan deployment & operasional (TODO_BUG bagian K).
 *
 * Jalankan:  node tools/dev/check-deploy.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');

const sig = read('Server_Implementation_Guide.md');
const mig = read('Mobile_Implementation_Guide.md');
const prd = read('PRD.md');

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);
const check = (label, needles, src = sig) => {
  const missing = needles.filter(n => !src.includes(n));
  if (missing.length) fail(`${label} — hilang: ${missing.join(', ')}`);
  else ok(label);
};

console.log('Infrastruktur server');
check('Supervisor worker + scheduler (#208)', ['[program:seekitar-worker]', '[program:seekitar-schedule]']);
check('penyiapan Redis (#209)', ['21.2a Penyiapan Redis', 'maxmemory-policy', 'REDIS_QUEUE_DB']);
check('S3 / MinIO (#210)', ['21.2b Penyimpanan Objek', 'AWS_USE_PATH_STYLE_ENDPOINT', 'minio/minio']);
check('SSL/TLS (#211)', ['21.2c SSL/TLS', 'certbot', 'Strict-Transport-Security']);
check('backup basis data (#212)', ['21.2d Backup', 'mysqldump', '--single-transaction']);
check('Sentry (#213)', ['sentry/sentry-laravel', 'SENTRY_TRACES_SAMPLE_RATE', 'dontReport']);
check('Laravel Pulse (#214)', ['laravel/pulse', 'viewPulse', 'pulse:trim']);

console.log('\nBuild mobile');
check('keystore Android (#215)', ['key.properties', 'signingConfigs', 'keytool -genkey'], mig);
check('build iOS (#216)', ['18.2 Penandatanganan iOS', 'fastlane', 'NSLocationWhenInUseUsageDescription'], mig);
check('environment Flutter (#217)', ['--dart-define-from-file'], mig);
check('CI build_runner sebelum build', ['dart run build_runner build'], mig);

console.log('\nRoadmap');
check('buffer stabilisasi (#218)', ['Buffer: Stabilisasi', 'Tidak ada fitur baru'], prd);

console.log('\nAnti-pola operasional');

// Kredensial penandatanganan tidak boleh masuk repositori.
if (!mig.includes('key.properties') || !/\*\.jks|\*\.keystore/.test(mig)) {
  fail('key.properties / *.jks belum dinyatakan git-ignored (#215)');
} else ok('keystore dinyatakan tidak boleh di-commit');

// Redis: antrian tidak boleh memakai kebijakan evict.
if (!sig.includes('noeviction')) {
  fail('database antrian Redis tidak dilindungi dari eviction (#209)');
} else ok('antrian Redis memakai noeviction');

// Cron dan schedule:work tidak boleh dijalankan bersamaan.
if (!sig.includes('cron ATAU `schedule:work`')) {
  fail('peringatan cron vs schedule:work ganda belum ada (#208)');
} else ok('peringatan scheduler ganda ada');

// Sentry tidak boleh mengirim data pribadi.
if (!sig.includes("'send_default_pii' => false")) {
  fail('Sentry belum dikonfigurasi menolak PII (#213)');
} else ok('Sentry menyaring data pribadi');

// Backup harus diuji, bukan sekadar dijadwalkan.
if (!sig.includes('Backup yang tidak pernah diuji')) {
  fail('kewajiban uji pemulihan backup belum ditegaskan (#212)');
} else ok('uji pemulihan backup ditegaskan');

// Cloudflare Flexible membocorkan token.
if (!sig.includes('Full (strict)')) {
  fail('peringatan mode Cloudflare Flexible belum ada (#211)');
} else ok('mode TLS Cloudflare ditegaskan');

console.log(problems === 0
  ? '\n✅ Panduan deployment konsisten.'
  : `\n❌ ${problems} masalah ditemukan.`);
if (problems) process.exitCode = 1;
