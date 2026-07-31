#!/usr/bin/env node
/**
 * Warnai ulang CSS template Mordenize menjadi hijau Seekitar.
 *
 * KENAPA SKRIP, BUKAN SUNTINGAN TANGAN
 * ------------------------------------
 * `style-green.min.css` memuat 113 kemunculan teal `#0a7ea4` (primary) dan
 * 15 kemunculan lime `#ccda4e` (secondary) yang ditulis langsung (bukan
 * lewat variabel), tersebar di ratusan aturan. Menyuntingnya dengan tangan
 * berarti: tidak bisa diulang saat template diperbarui, dan satu-dua
 * kemunculan pasti terlewat lalu muncul sebagai tombol teal/biru nyasar di
 * halaman yang jarang dibuka.
 *
 * Nama varian "green" bawaan template MENYESATKAN: `--bs-primary`-nya teal
 * `#0a7ea4` (terbaca biru di layar) dan `--bs-secondary`-nya lime `#ccda4e`.
 * Template `vendor/modernize` yang digantikan adalah acuan hasil recolor yang
 * benar — 30 warna turunan teal/lime di berkas baru berkorespondensi 1:1
 * dengan 30 warna hijau/biru di berkas lama (jumlah literal identik, 2651).
 * Nilai sasaran diambil dari sana; dua pengecualian yang sengaja berubah
 * menjadi hijau (bukan biru peninggalan) dijelaskan di PETA.
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
const CSS = path.join(ROOT, 'seekitar-server/public/vendor/mordenize/css/style-green.min.css');

/*
 * Palet.
 *
 * Hijau Lokal #168A4A adalah warna identitas & aksi (BRANDING §3.5).
 * Turunan gelap/muda dihitung sekali di sini supaya seluruh state Bootstrap
 * (hover, active, subtle, emphasis) tetap serasi.
 */
const PETA = {
  // Primary: teal template -> hijau Seekitar
  '#0a7ea4': '#168A4A',   // primary / link (113x)
  '#086583': '#11703C',   // link-hover, btn-hover-border, btn-active-bg
  '#096b8b': '#11703C',   // btn-hover-bg
  '#085f7b': '#0C5A30',   // btn-active-border
  '#043242': '#0C5A30',   // primary-text-emphasis (terang)
  '#cee5ed': '#d3ecdd',   // primary-bg-subtle (terang)
  '#9dcbdb': '#b9e0ca',   // primary-border-subtle (terang)
  '#021921': '#0a2418',   // primary-bg-subtle (mode gelap)
  '#064c62': '#12603a',   // primary-border-subtle (mode gelap)
  '#6cb2c8': '#7cc79b',   // link & primary-text-emphasis (mode gelap)
  '#89c1d3': '#7cc79b',   // link-hover (mode gelap)

  /*
   * Fokus kotak isian: #85bfd2. Berkas lama memakai #aec3ff — BIRU yang
   * lolos dari recolor dan sudah dicatat sebagai cacat di admin.css
   * ("setiap kotak isian berkedip biru"). Daripada mewarisi biru itu,
   * diperbaiki di sumbernya jadi hijau; admin.css tetap menimpanya untuk
   * form-control/form-select/form-check-input, aturan ini menutup sisanya
   * (border fokus accordion).
   */
  '#85bfd2': '#b9e0ca',
  // Thumb form-range saat ditekan — hijau, bukan biru peninggalan.
  '#b6d8e4': '#b9e0ca',

  // Primary: sisa biru template lama yang ikut tersalin di berkas baru
  '#5D87FF': '#168A4A',
  '#5d87ff': '#168a4a',
  '#ECF2FF': '#E3F3EA',   // light-primary (latar lembut)
  '#ecf2ff': '#e3f3ea',

  // Secondary: lime template -> biru muda, mengikuti template lama
  '#ccda4e': '#49BEFF',   // secondary
  '#d4e069': '#3ea2d9',   // btn-secondary hover-bg
  '#d6e171': '#3a98cc',   // btn-secondary active-bg
  '#d1de60': '#3a98cc',   // btn-secondary hover/active border
  '#f5f8dc': '#dbf2ff',   // secondary-bg-subtle (terang)
  '#ebf0b8': '#b6e5ff',   // secondary-border-subtle (terang)
  '#52571f': '#1d4c66',   // secondary-text-emphasis (terang)
  '#e0e995': '#92d8ff',   // secondary-text-emphasis (mode gelap)
  '#292c10': '#0f2633',   // secondary-bg-subtle (mode gelap)
  '#7a832f': '#2c7299',   // secondary-border-subtle (mode gelap)

  // Tabel kontekstual — nuansa tabel primary/secondary (meniru template lama)
  '#b9ced5': '#c9d0e6',   // table-primary: border & active
  '#c4dae1': '#d4dbf2',   // table-primary: striped
  '#bfd4db': '#ced6ec',   // table-primary: hover
  '#e9ecd1': '#d0e6f2',   // table-secondary: striped
  '#e3e5cc': '#cbe0ec',   // table-secondary: hover
  '#dddfc6': '#c5dae6',   // table-secondary: border & active

  // rgb() yang ditulis terpisah dari heksa
  '10,126,164': '22,138,74',
  '10, 126, 164': '22, 138, 74',
  '8,101,131': '17,112,60',
  '8, 101, 131': '17, 112, 60',
  '47,145,178': '22,138,74',   // btn-primary focus-shadow-rgb
  '108,178,200': '124,199,155', // link-color-rgb (mode gelap)
  '137,193,211': '124,199,155', // link-hover-rgb (mode gelap)
  '93,135,255': '22,138,74',   // sisa rgb biru template lama
  '93, 135, 255': '22, 138, 74',
  '204,218,78': '73,190,255',
  '204, 218, 78': '73, 190, 255',
  '173,185,66': '100,200,255', // btn-secondary focus-shadow-rgb
  '214,225,113': '58,152,204',
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
 * IE8. Semua format memang ada di vendor, tetapi browser modern hanya
 * memakai woff2; @font-face ini dipangkas supaya server tidak menyajikan
 * 4,9 MB padahal 640 KB cukup.
 */
const TI = path.join(ROOT, 'seekitar-server/public/vendor/mordenize/css/tabler-icons/tabler-icons.min.css');
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

/*
 * Ilustrasi halaman masuk.
 *
 * `login-security.svg` menggambar celana dan jendela peramban dengan biru-ungu
 * yang serasi dengan tema asli template, bukan dengan hijau Seekitar. Warnanya
 * ditulis sebagai atribut fill di dalam SVG, jadi CSS tidak bisa
 * menjangkaunya — satu-satunya cara adalah mengganti nilainya di berkas.
 *
 * Dilakukan lewat skrip yang sama, dan bukan dengan tangan, supaya menyalin
 * ulang aset dari repositori template tetap menghasilkan gambar hijau.
 */
const SVG = path.join(ROOT, 'seekitar-server/public/vendor/mordenize/images/backgrounds/login-security.svg');

const PETA_SVG = {
  '#8d95ff': '#3FA46E',   // celana — ungu ke hijau sedang
  '#757bff': '#2E8F5C',   // bayangan celana
  '#ccd2ff': '#BFE3CE',   // bidang jendela peramban
  '#e1e5ff': '#DFF1E7',   // latar avatar & kolom sandi
  '#112544': '#1F2933',   // garis luar -> Tinta Malam (BRANDING §3.5)
};

if (fs.existsSync(SVG)) {
  let svg = fs.readFileSync(SVG, 'utf8');
  const asli = svg;
  let nSvg = 0;

  for (const [dari, ke] of Object.entries(PETA_SVG)) {
    const bagian = svg.split(dari);
    if (bagian.length > 1) {
      nSvg += bagian.length - 1;
      svg = bagian.join(ke);
    }
  }

  if (svg !== asli) {
    fs.writeFileSync(SVG, svg);
    console.log(`  login-security.svg -> ${nSvg} penggantian warna`);
  }
}

fs.writeFileSync(CSS, css);

const total = Object.values(hitung).reduce((a, b) => a + b, 0);
console.log(`\nstyle-green.min.css: ${total} penggantian warna`);
for (const [dari, n] of Object.entries(hitung)) {
  console.log(`  ${dari.padEnd(16)} x${n}`);
}
console.log(`ukuran ${sebelum} -> ${css.length} byte`);

// Verifikasi: tidak boleh ada sisa teal primary, lime secondary, atau biru legacy.
const sisa = ['#0a7ea4', '#ccda4e', '#5d87ff', '#5D87FF']
  .map((c) => ({ warna: c, n: css.split(c).length - 1 }))
  .filter((x) => x.n > 0);
if (sisa.length > 0) {
  console.error('\nGAGAL, masih ada warna sumber:');
  for (const { warna, n } of sisa) console.error(`  ${warna} x${n}`);
  process.exit(1);
}
console.log('\nOK — tidak ada sisa teal #0a7ea4, lime #ccda4e, atau biru #5D87FF');
