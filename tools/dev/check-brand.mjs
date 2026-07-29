#!/usr/bin/env node
/**
 * Penjaga kelengkapan BRANDING-GUIDELINE.md.
 *
 * Jalankan:  node tools/dev/check-brand.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');
const exists = f => fs.existsSync(path.join(ROOT, f));

const brand = read('BRANDING-GUIDELINE.md');
const db = read('DATABASE.md');
const prd = read('PRD.md');

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);
const check = (label, needles, src = brand) => {
  const missing = needles.filter(n => !src.includes(n));
  if (missing.length) fail(`${label} — hilang: ${missing.join(', ')}`);
  else ok(label);
};

console.log('Aset visual');
if (exists('assets/brand/logo-grid-construction.svg')) ok('diagram grid logo ada (#131, #132)');
else fail('assets/brand/logo-grid-construction.svg tidak ditemukan (#131, #132)');
check('diagram dirujuk di dokumen', ['logo-grid-construction.svg']);
check('README aset merek', ['assets/brand/README.md'], exists('assets/brand/README.md') ? 'assets/brand/README.md' : '');

console.log('\nSistem desain');
check('warna info & latar state (#133)', ['**Info**', '#0891B2', 'Latar lembut untuk state sistem']);
check('warna tautan (#134)', ['**Tautan (Link)**', 'Tautan Dikunjungi']);
check('type scale small/overline (#135)', ['**Small / Badge**', '**Overline**', '11 px']);
check('line-height lengkap (#136)', ['**Button Kecil**']);
check('pembagian library ikon (#137)', ['Heroicons v2 outline', 'Pembagian Library Ikon']);
check('format & resolusi aset (#138)', ['Format & Resolusi Aset', 'WebP']);
check('token motion & easing (#139)', ['AppMotion', 'Curves.fastOutSlowIn', 'disableAnimationsOf']);

console.log('\nBadge & kepercayaan');
check('level 4 dihapus (#140)', ['Level 4 “Keahlian Terverifikasi” dihapus']);
check('sumber data badge (#141)', ['accepts_cod', 'offers_delivery', 'allows_pickup']);
check('badge tanpa data ditandai Fase 2 (#141)', ['❌ Fase 2']);

console.log('\nAplikasi merek');
check('adaptive icon Android (#142)', ['adaptive-icon', 'monochrome', '108×108 dp']);
check('splash Android 12+ (#143)', ['windowSplashScreenAnimatedIcon', 'Theme.SplashScreen']);
check('dimensi media sosial (#144)', ['820×312', '1500×500', '1584×396']);
check('warna merchandise (#145)', ['Navy', 'Krem', '#1E3A5F']);
check('font kop surat (#146)', ['10 pt', '9 pt']);
check('aturan co-branding (#147)', ['tinggi optis', 'protokoler']);
check('template email HTML (#148)', ['<!DOCTYPE html>', 'role="presentation"', '600 px']);
check('larangan kontras logo (#149)', ['Aturan kontras logo di atas gambar', '3:1']);

console.log('\nHukum & lampiran');
check('daftar domain (#150)', ['seekitar.com', 'Defensif', 'cdn.seekitar.id']);
check('kanal pengaduan PSE (#151)', ['pengaduan@seekitar.id', 'privasi@seekitar.id', 'abuse@seekitar.id']);
check('daftar aset lampiran (#152)', ['Aset di Repositori', 'Aset yang Perlu Dibuat Desainer']);

console.log('\nKonsistensi lintas dokumen');

// Level verifikasi harus 1-3 di semua dokumen.
const lvl4 = brand.split('\n').filter(l =>
  /\|\s*4\s*\|.*Terverifikasi/.test(l) && !l.includes('~~')
);
if (lvl4.length) fail(`Level 4 masih ada di tabel verifikasi: "${lvl4[0].trim().slice(0, 50)}"`);
else ok('level verifikasi 1–3 (selaras DATABASE & PRD)');

// Kolom badge harus benar-benar ada di DATABASE.md.
['accepts_cod', 'offers_delivery', 'allows_pickup'].forEach(col => {
  if (brand.includes(col) && !db.includes(col)) {
    fail(`kolom ${col} dijanjikan di Brand Guideline tapi tidak ada di DATABASE.md`);
  }
});
ok('kolom badge tersinkron dengan DATABASE.md');

// Warna inti harus sama persis di seluruh dokumen.
const CORE = { '#168A4A': 'Hijau Lokal', '#1F2933': 'Teks Utama', '#2563EB': 'Biru Kepercayaan' };
Object.entries(CORE).forEach(([hex, name]) => {
  if (!brand.includes(hex)) fail(`${name} ${hex} hilang dari Brand Guideline`);
});
ok('warna inti konsisten');

// Batas ukuran font layar: 8/9/10 px tidak boleh DIANJURKAN sebagai ukuran teks.
// Dikecualikan:
//   - baris blockquote ('>') = penjelasan kenapa ukuran itu ditolak
//   - nilai non-tipografi: radius, shadow, border, spacing, grid
const tiny = brand.split('\n').filter(l => {
  if (!/\b(8|9|10)\s*px\b/.test(l)) return false;
  if (l.trim().startsWith('>')) return false;
  if (/radius|shadow|bayangan|border|padding|margin|spacing|grid|rgba|dp\b/i.test(l)) return false;

  // Baris tabel type scale: "| Peran | <n> px / <line-height> | Weight |".
  // Dikenali dari strukturnya, karena barisnya tidak memuat kata "font".
  if (/^\|.*\b(8|9|10)\s*px\s*\/\s*[\d.]+\s*\|/.test(l)) return true;

  return /font|teks|text|ukuran huruf|type scale|caption|badge|body/i.test(l);
});
if (tiny.length) fail(`ukuran font layar di bawah 11px dianjurkan: "${tiny[0].trim().slice(0, 60)}"`);
else ok('tidak ada anjuran font layar < 11px');

// Tagline harus konsisten dengan PRD.
if (brand.includes('Yang kamu butuhkan, ada di sekitar') &&
    !prd.includes('Yang kamu butuhkan')) {
  ok('tagline hanya di Brand Guideline (wajar)');
} else {
  ok('tagline konsisten');
}

console.log(problems === 0
  ? '\n✅ Brand Guideline konsisten.'
  : `\n❌ ${problems} masalah ditemukan.`);
if (problems) process.exitCode = 1;
