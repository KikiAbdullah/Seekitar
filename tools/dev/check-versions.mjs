#!/usr/bin/env node
/**
 * Penjaga konsistensi versi antar dokumen.
 *
 * Mencegah kembalinya inkonsistensi tech stack (mis. "Laravel 11" muncul lagi,
 * atau constraint paket yang tidak kompatibel dengan Laravel 13).
 *
 * Jalankan:  node tools/dev/check-versions.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const DOCS = [
  'PRD.md',
  'DATABASE.md',
  'API_DOCUMENTATION.md',
  'Server_Implementation_Guide.md',
  'Mobile_Implementation_Guide.md',
  'TECH_STACK.md',
];

/**
 * Pola terlarang. `allow` = substring yang menandakan baris itu sengaja
 * membahas versi lama (blok "sebelum/sesudah", catatan migrasi, dll).
 */
const RULES = [
  {
    id: 'laravel-version',
    re: /Laravel\s+1[0-2]\b/i,
    msg: 'Menyebut Laravel 10-12; proyek ini memakai Laravel 13.',
    allow: ['mentok', 'tidak akan ter-install', 'dokumen lama', 'sudah **tidak berlaku**',
            'mengunci Laravel 11', 'diseragamkan', 'hanya mendukung sampai',
            // Rujukan historis: menjelaskan SEJAK KAPAN sebuah perilaku berubah,
            // bukan menyatakan proyek ini memakai versi tersebut.
            'sudah tidak ada sejak', 'Laravel 11+', 'seperti Laravel 10'],
  },
  {
    id: 'php-patch-pin',
    re: /PHP\s+8\.3\.\d+/i,
    msg: 'Versi patch PHP terlalu spesifik; pakai "PHP 8.3+".',
    allow: ['jangan', 'Menyebut versi patch'],
  },
  {
    id: 'php-too-old',
    re: /PHP\s+8\.[0-2]\b/i,
    msg: 'Menyebut PHP di bawah 8.3; minimum proyek adalah PHP 8.3.',
    allow: [],
  },
  {
    id: 'yajra-constraint',
    re: /laravel-datatables(-oracle)?:?\s*\^?1[12]\b/i,
    msg: 'Yajra Datatables harus ^13.0 untuk Laravel 13.',
    allow: ['~~', 'mengunci Laravel 11'],
  },
  {
    id: 'yajra-package-name',
    re: /composer require yajra\/laravel-datatables:/i,
    msg: 'Nama paket Composer adalah yajra/laravel-datatables-oracle.',
    allow: [],
  },
  {
    id: 'spatie-constraint',
    re: /laravel-permission:?\s*\^?[67]\b/i,
    msg: 'Spatie Permission harus ^8.0 untuk Laravel 13.',
    allow: ['~~', 'mentok', 'hanya mendukung sampai', 'gagal resolusi'],
  },
  {
    id: 'flutter-version',
    re: /Flutter\s+3\.(19|[0-3]?[0-9])\+/i,
    msg: 'Flutter minimum proyek adalah 3.44+.',
    allow: [],
    test: (line) => {
      const m = line.match(/Flutter\s+3\.(\d+)\+/i);
      return m ? Number(m[1]) < 44 : false;
    },
  },
  {
    id: 'go-router-old',
    re: /go_router.*\^1[5-9]\.|go_router.*\^2\d\./i,
    msg: 'go_router harus ^14.8.0 (selaras pubspec.yaml).',
    allow: ['keliru', 'Catatan asal'],
  },
];

let problems = 0;

/**
 * Baris "contoh salah" yang memang disengaja ditandai lewat komentar penanda
 * tepat di atasnya (mis. "// ❌ Riverpod 2 (tidak lagi berlaku)"), atau lewat
 * kalimat yang jelas-jelas menjelaskan versi lama.
 */
const CONTEXT_MARKERS = [
  '❌', '~~', '(lama)', 'tidak lagi berlaku', 'Riverpod 2.x (lama)',
  'sebelumnya', 'Before:', 'Constraint lama',
];

function isIntentionalExample(lines, i) {
  // Periksa 2 baris di atas: penanda blok "sebelum/salah".
  for (let k = Math.max(0, i - 2); k < i; k++) {
    if (CONTEXT_MARKERS.some(m => lines[k].includes(m))) return true;
  }
  return false;
}

for (const doc of DOCS) {
  const file = path.join(ROOT, doc);
  if (!fs.existsSync(file)) continue;
  const lines = fs.readFileSync(file, 'utf8').split('\n');

  lines.forEach((line, i) => {
    for (const rule of RULES) {
      const hit = rule.test ? rule.test(line) : rule.re.test(line);
      if (!hit) continue;
      if (rule.allow.some(a => line.includes(a))) continue;
      if (isIntentionalExample(lines, i)) continue;
      console.log(`${doc}:${i + 1}  [${rule.id}] ${rule.msg}`);
      console.log(`   ${line.trim().slice(0, 110)}`);
      problems++;
    }
  });
}

if (problems === 0) {
  console.log('✅ Konsisten: tidak ada versi usang di dokumen.');
} else {
  console.log(`\n❌ ${problems} masalah konsistensi versi ditemukan.`);
  process.exitCode = 1;
}
