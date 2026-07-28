#!/usr/bin/env node
/**
 * Warnai ulang CSS template Modernize menjadi hijau Seekitar.
 *
 * KENAPA SKRIP, BUKAN SUNTINGAN TANGAN
 * ------------------------------------
 * `style.min.css` memuat 117 kemunculan biru `#5D87FF` yang ditulis
 * langsung (bukan lewat variabel), tersebar di ratusan aturan. Menyuntingnya
 * dengan tangan berarti: tidak bisa diulang saat template diperbarui, dan
 * satu-dua kemunculan pasti terlewat lalu muncul sebagai tombol biru nyasar
 * di halaman yang jarang dibuka.
 *
 * Dijalankan ulang kapan pun berkas vendor diperbarui:
 *   node tools/dev/recolor-modernize.mjs
 *
 * Skrip ini IDEMPOTEN — menjalankannya dua kali tidak mengubah apa pun,
 * karena warna sumber sudah tidak ada lagi setelah lintasan pertama.
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const CSS = path.join(ROOT, 'seekitar-server/public/vendor/modernize/css/style.min.css');

/*
 * Palet.
 *
 * Hijau Lokal #168A4A adalah warna identitas & aksi (BRANDING §3.5).
 * Turunan gelap/muda dihitung sekali di sini supaya seluruh state Bootstrap
 * (hover, active, subtle, emphasis) tetap serasi.
 */
const PETA = {
  // Primary: biru Modernize -> hijau Seekitar
  '#5D87FF': '#168A4A',   // primary
  '#5d87ff': '#168a4a',
  '#4570EA': '#11703C',   // primary hover / active (versi gelap)
  '#4570ea': '#11703c',
  '#ECF2FF': '#E3F3EA',   // primary-subtle (latar lembut)
  '#ecf2ff': '#e3f3ea',
  '#dfe7ff': '#d3ecdd',   // primary-bg-subtle
  '#becfff': '#b9e0ca',   // primary-border-subtle
  '#253666': '#0C5A30',   // primary-text-emphasis
  '#9eb7ff': '#7cc79b',   // primary-text-emphasis (mode gelap)
  '#131b33': '#0a2418',   // primary-bg-subtle (mode gelap)
  '#385199': '#12603a',   // primary-border-subtle (mode gelap)

  // rgb() yang ditulis terpisah dari heksa
  '93,135,255': '22,138,74',
  '93, 135, 255': '22, 138, 74',
  '69,112,234': '17,112,60',
  '69, 112, 234': '17, 112, 60',
};

if (!fs.existsSync(CSS)) {
  console.error(`Tidak ada: ${CSS}`);
  process.exit(1);
}

let css = fs.readFileSync(CSS, 'utf8');
const sebelum = css.length;
const hitung = {};

for (const [dari, ke] of Object.entries(PETA)) {
  // split/join, bukan regex: nilai heksa & rgb tidak butuh pola dan
  // penggantian harfiah lebih aman terhadap karakter khusus.
  const bagian = css.split(dari);
  if (bagian.length > 1) {
    hitung[dari] = bagian.length - 1;
    css = bagian.join(ke);
  }
}

/*
 * Path font Tabler diarahkan ke woff2 saja.
 *
 * Repo aslinya membawa eot/ttf/woff/woff2 (total 4,9 MB) untuk mendukung
 * IE8. Hanya woff2 yang disalin (640 KB); tanpa perbaikan ini browser akan
 * meminta tiga berkas yang tidak ada dan menghasilkan 404 di setiap halaman.
 */
const TI = path.join(ROOT, 'seekitar-server/public/vendor/modernize/css/icons/tabler-icons/tabler-icons.min.css');
if (fs.existsSync(TI)) {
  let ti = fs.readFileSync(TI, 'utf8');
  const asli = ti;

  /*
   * @font-face Tabler memuat DUA deklarasi src berurutan:
   *
   *   src:url("...eot");                         <- fallback IE, tanpa format()
   *   src:url("...woff2") format("woff2"), ...   <- daftar modern
   *
   * Versi pertama skrip ini hanya menulis ulang blok yang MENGANDUNG woff2,
   * sehingga baris `.eot` di atasnya lolos dan browser tetap meminta berkas
   * yang tidak disalin — 404 di setiap halaman. Diverifikasi dengan membaca
   * hasilnya, bukan diasumsikan.
   *
   * Karena itu seluruh @font-face dirakit ulang: satu src saja.
   */
  ti = ti.replace(/@font-face\{[^}]*\}/g, (blok) => {
    if (!blok.includes('tabler-icons')) return blok;

    return '@font-face{font-family:"tabler-icons";font-style:normal;'
      + 'font-weight:400;font-display:swap;'
      + 'src:url("./fonts/tabler-icons.woff2?v2.11.0") format("woff2");'
      + 'speak:none;font-variant:normal;text-transform:none;line-height:1;'
      + '-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}';
  });

  if (ti !== asli) {
    fs.writeFileSync(TI, ti);
    console.log('  tabler-icons.min.css -> hanya woff2');
  }
}

fs.writeFileSync(CSS, css);

const total = Object.values(hitung).reduce((a, b) => a + b, 0);
console.log(`\nstyle.min.css: ${total} penggantian warna`);
for (const [dari, n] of Object.entries(hitung)) {
  console.log(`  ${dari.padEnd(16)} x${n}`);
}
console.log(`ukuran ${sebelum} -> ${css.length} byte`);

// Verifikasi: tidak boleh ada sisa biru primary.
const sisa = (css.match(/#5[dD]87[fF][fF]/g) || []).length;
if (sisa > 0) {
  console.error(`\nGAGAL: masih ada ${sisa} kemunculan #5D87FF`);
  process.exit(1);
}
console.log('\nOK — tidak ada sisa biru #5D87FF');
