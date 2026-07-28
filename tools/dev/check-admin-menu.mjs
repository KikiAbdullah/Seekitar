#!/usr/bin/env node
/**
 * Penjaga PANEL ADMIN: menu, izin, dan halaman.
 *
 * KENAPA CHECKER INI ADA
 * ----------------------
 * Ketidakcocokan antara `@can` di sidebar dan `permission:` di route tidak
 * menimbulkan error di mana pun. Ia muncul sebagai salah satu dari dua hal
 * yang sama-sama sulit dilacak:
 *
 *   - menu tampil, lalu diklik dan menghasilkan 403 (menu berbohong), atau
 *   - menu tersembunyi padahal admin sebenarnya berhak (fitur hilang diam-diam).
 *
 * Keduanya baru ketahuan dari keluhan pengguna. Checker ini membandingkan
 * keduanya langsung dari sumber kebenarannya masing-masing: `route:list` untuk
 * middleware, dan berkas sidebar untuk @can.
 *
 * Jalankan:  node tools/dev/check-admin-menu.mjs
 */
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');
const exists = f => fs.existsSync(path.join(ROOT, f));

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);

const SIDEBAR = 'seekitar-server/resources/views/admin/partials/sidebar.blade.php';

// 12 permission dari Server_Implementation_Guide.md §6.2 — sumber kebenarannya
// adalah seeder, bukan daftar yang ditulis ulang di sini.
const PERMISSIONS = [...read('seekitar-server/database/seeders/RolesAndPermissionsSeeder.php')
  .matchAll(/^\s*'(manage-[a-z]+|verify-[a-z]+)',$/gm)].map(m => m[1]);

// ─────────────────────────────────── 1. Sidebar ada & memakai @can
console.log('Sidebar admin');

if (!exists(SIDEBAR)) {
  fail(`${SIDEBAR} tidak ada — menu tidak bisa disaring per izin`);
} else {
  const sidebar = read(SIDEBAR);

  // Komentar Blade dibuang: catatan yang MENJELASKAN @can bukan pemakaian.
  const bersih = sidebar.replace(/\{\{--[\s\S]*?--\}\}/g, '');

  const canDipakai = new Set([
    ...[...bersih.matchAll(/@can\('([a-z-]+)'\)/g)].map(m => m[1]),
    ...[...bersih.matchAll(/@canany\(\[([^\]]+)\]\)/g)]
      .flatMap(m => [...m[1].matchAll(/'([a-z-]+)'/g)].map(x => x[1])),
  ]);

  if (canDipakai.size === 0) {
    fail('sidebar tidak memakai @can sama sekali — semua menu tampil ke semua admin');
  } else {
    ok(`sidebar memakai ${canDipakai.size} permission berbeda di @can/@canany`);
  }

  // Setiap permission yang punya halaman WAJIB muncul di menu; kalau tidak,
  // izinnya diberikan tapi tidak membuka apa pun.
  const tanpaMenu = PERMISSIONS.filter(p => !canDipakai.has(p));
  if (tanpaMenu.length) {
    fail(`permission tanpa butir menu: ${tanpaMenu.join(', ')} — izin diberikan tapi tidak membuka apa pun`);
  } else {
    ok(`${PERMISSIONS.length} permission semuanya punya butir menu`);
  }

  // Tautan mentah adalah bug menunggu terjadi: URL berubah, tautan mati diam.
  const hardcoded = [...bersih.matchAll(/href="\/admin\/[^"]*"/g)].map(m => m[0]);
  if (hardcoded.length) {
    fail(`URL ditulis mentah, bukan route(): ${hardcoded.join(', ')}`);
  } else {
    ok('semua tautan memakai route()');
  }

  // Induk dropdown harus @canany — dengan @can, menu "Verifikasi" hilang bagi
  // admin yang hanya punya salah satu dari dua izinnya.
  if (!/@canany\(\['verify-users', 'verify-stores'\]\)/.test(bersih)) {
    fail("induk menu Verifikasi tidak memakai @canany(['verify-users', 'verify-stores'])");
  } else {
    ok('induk dropdown Verifikasi memakai @canany');
  }
}

// ─────────────────────────────────── 2. @can sidebar ⇄ middleware route
console.log('\nIzin menu ⇄ izin route');

let routes = null;
try {
  routes = JSON.parse(execFileSync(path.join(ROOT, 'tools/dev/artisan'), ['route:list', '--json'], {
    encoding: 'utf8', maxBuffer: 32 * 1024 * 1024, stdio: ['ignore', 'pipe', 'ignore'],
  }));
} catch {
  console.log('  ⚠️  route:list gagal (jalankan ./tools/dev/setup). Bagian ini dilewati.');
}

if (routes && exists(SIDEBAR)) {
  const sidebar = read(SIDEBAR).replace(/\{\{--[\s\S]*?--\}\}/g, '');

  /*
   * Pasangan (nama route yang ditaut menu) → (permission pembungkus @can-nya).
   *
   * Diambil dengan memindai berkas berurutan: setiap @can/@canany membuka
   * konteks, dan route() yang muncul sesudahnya berada di dalamnya sampai
   * @endcan/@endcanany. Pendekatan ini cukup karena sidebar hanya bersarang
   * satu tingkat — dan kalau suatu saat lebih dalam, pemeriksaan di bawah
   * akan melaporkan ketidakcocokan alih-alih lolos diam-diam.
   */
  const tautan = [];
  const tumpukan = [];

  for (const baris of sidebar.split('\n')) {
    const can = baris.match(/@can\('([a-z-]+)'\)/);
    const canany = baris.match(/@canany\(\[([^\]]+)\]\)/);
    if (can) tumpukan.push([can[1]]);
    else if (canany) tumpukan.push([...canany[1].matchAll(/'([a-z-]+)'/g)].map(m => m[1]));

    for (const m of baris.matchAll(/route\('(admin\.[a-z.*]+)'\)/g)) {
      tautan.push({ route: m[1], izin: tumpukan.length ? tumpukan.at(-1) : [] });
    }

    if (/@endcan\b|@endcanany\b/.test(baris)) tumpukan.pop();
  }

  const byName = new Map(routes.filter(r => r.name).map(r => [r.name, r]));
  let cocok = 0;

  for (const { route: nama, izin } of tautan) {
    const r = byName.get(nama);

    if (!r) {
      fail(`menu menunjuk route '${nama}' yang tidak terdaftar — tautan mati / 500 saat render`);
      continue;
    }

    // Permission yang benar-benar dituntut route, dari middleware-nya.
    const dituntut = r.middleware
      .filter(m => /PermissionMiddleware/.test(m))
      .flatMap(m => (m.split(':')[1] ?? '').split('|'))
      .filter(Boolean);

    if (dituntut.length === 0) {
      // Dasbor & halaman akun sendiri memang tanpa permission.
      if (izin.length > 0) {
        fail(`'${nama}' disembunyikan @can(${izin.join('|')}) tetapi route-nya tidak menuntut izin apa pun`);
      }
      continue;
    }

    // Menu tidak boleh tampil lebih longgar daripada route-nya: setiap izin
    // yang membuka menu harus benar-benar diterima route.
    const terlaluLonggar = izin.filter(p => !dituntut.includes(p));
    if (izin.length === 0) {
      fail(`'${nama}' butuh ${dituntut.join('|')} tetapi menunya tidak dibungkus @can — akan tampil lalu ditolak 403`);
    } else if (terlaluLonggar.length) {
      fail(`'${nama}': menu memakai ${terlaluLonggar.join(', ')} tetapi route menuntut ${dituntut.join('|')}`);
    } else {
      cocok++;
    }
  }

  if (cocok) ok(`${cocok} tautan menu cocok dengan permission route-nya`);

  // ── Endpoint data Datatables harus dijaga izin yang sama dengan halamannya.
  // Tanpa itu, admin tanpa izin tetap bisa memanggil JSON-nya langsung dan
  // menarik seluruh tabel meski menunya tersembunyi.
  const bocor = routes
    .filter(r => /^admin\/[a-z]+\/data$/.test(r.uri))
    .filter(r => !r.middleware.some(m => /PermissionMiddleware/.test(m)));

  if (bocor.length) {
    fail(`endpoint data tanpa permission: ${bocor.map(r => r.uri).join(', ')} — bisa ditarik langsung`);
  } else {
    ok('semua endpoint data Datatables dijaga permission');
  }

  // ── Seluruh route admin (selain login/logout) wajib di balik auth + role.
  const tanpaAuth = routes
    .filter(r => r.uri.startsWith('admin'))
    .filter(r => !['admin/login', 'admin/logout'].includes(r.uri))
    .filter(r => !r.middleware.some(m => /Authenticate\b/.test(m) && !/RedirectIfAuthenticated/.test(m)));

  if (tanpaAuth.length) {
    fail(`route admin tanpa auth: ${tanpaAuth.map(r => r.uri).join(', ')}`);
  } else {
    ok('semua route admin di balik auth');
  }

  const tanpaRole = routes
    .filter(r => r.uri.startsWith('admin'))
    .filter(r => !['admin/login', 'admin/logout'].includes(r.uri))
    .filter(r => !r.middleware.some(m => /RoleMiddleware:admin\|super-admin/.test(m)));

  if (tanpaRole.length) {
    fail(`route admin tanpa role:admin|super-admin: ${tanpaRole.map(r => r.uri).join(', ')}`);
  } else {
    ok('semua route admin menuntut peran admin/super-admin');
  }

  // ── Halaman akun sendiri TIDAK boleh menuntut permission: admin tanpa
  // `manage-users` pun harus bisa mengganti kata sandinya sendiri.
  for (const nama of ['admin.profile.edit', 'admin.password.edit']) {
    const r = byName.get(nama);
    if (!r) { fail(`route '${nama}' tidak ada — admin tidak bisa mengelola akunnya sendiri`); continue; }
    if (r.middleware.some(m => /PermissionMiddleware/.test(m))) {
      fail(`'${nama}' menuntut permission — admin tanpa izin data tidak bisa mengurus akunnya sendiri`);
    }
  }
  ok('halaman profil & kata sandi bebas dari permission');
}

// ─────────────────────────────────── 3. Setiap halaman menu punya view
console.log('\nView halaman admin');

const VIEW_WAJIB = [
  'dashboard', 'layout', 'partials/sidebar',
  'auth/login', 'profile/edit', 'profile/password',
  'users/index', 'users/edit', 'categories/index', 'categories/form',
  'stores/index', 'listings/index', 'listings/show',
  'requests/index', 'requests/show', 'offers/index',
  'orders/index', 'orders/show', 'reviews/index',
  'disputes/index', 'disputes/show', 'settings/index',
  'verifications/users', 'verifications/stores',
];

const hilang = VIEW_WAJIB.filter(v => !exists(`seekitar-server/resources/views/admin/${v}.blade.php`));
if (hilang.length) fail(`view admin hilang: ${hilang.join(', ')}`);
else ok(`${VIEW_WAJIB.length} view admin lengkap`);

// ─────────────────────────────────── 4. Render nyata per peran
console.log('\nRender halaman sebagai admin & super-admin');

// Kode keluar TIDAK bisa dipakai: pembungkus php-wasm selalu mengembalikan 0
// apa pun exit() dari skrip PHP-nya. Keputusan diambil dari isi keluaran.
let out = '';
try {
  out = execFileSync(path.join(ROOT, 'tools/dev/php'),
    [path.join(ROOT, 'tools/dev/render-admin.php')],
    { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] });
} catch (e) {
  out = (e.stdout?.toString() ?? '') + (e.stderr?.toString() ?? '');
}

const gagalRender = Number(out.match(/(\d+) gagal\b/)?.[1] ?? -1);
const masalahMenu = Number(out.match(/(\d+) masalah/)?.[1] ?? -1);

if (gagalRender === 0 && masalahMenu === 0) {
  ok(out.trim().split('\n').filter(Boolean).slice(-2).join(' · '));
} else {
  const rincian = out.split('\n').filter(l => /^(GAGAL RENDER|MENU (HILANG|BOCOR))/.test(l));
  fail('render admin bermasalah:\n     ' + (rincian.join('\n     ') || out.trim() || 'tidak ada keluaran'));
}

// ─────────────────────────────────── 5. Aturan Blade panel admin
console.log('\nKebersihan Blade admin');

const viewDir = path.join(ROOT, 'seekitar-server/resources/views/admin');
const blades = [];
(function walk(dir) {
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, e.name);
    e.isDirectory() ? walk(p) : e.name.endsWith('.blade.php') && blades.push(p);
  }
})(viewDir);

// Direktif Blade DI DALAM komentar JavaScript tetap dikompilasi. `// @js(...)`
// yang ditulis sebagai penjelasan membuat Blade memanggil Js::from() tanpa
// argumen dan halaman mati saat render — jebakan yang sama dengan @php di
// dalam komentar {{-- --}}, dan sudah pernah terjadi di dashboard.blade.php.
const jsKomentar = [];
for (const p of blades) {
  const src = fs.readFileSync(p, 'utf8')
    .replace(/\{\{--[\s\S]*?--\}\}/g, '')
    /*
     * Blok @php...@endphp ikut dibuang, dan ini BUKAN kelalaian.
     *
     * Diverifikasi langsung lewat Blade::compileString(): isi blok @php
     * diteruskan apa adanya sebagai PHP, sehingga `// lihat @json()` di
     * dalamnya tetap sekadar komentar. Di luar blok itu — misalnya dalam
     * <script> — direktif yang sama benar-benar dikompilasi dan menghasilkan
     * `json_encode(, 15, 512)` yang tidak bisa di-parse.
     *
     * Tanpa pengecualian ini, checker melaporkan komentar penjelas di
     * _datatable.blade.php sebagai pelanggaran (false positive).
     */
    .replace(/@php[\s\S]*?@endphp/g, '');

  for (const baris of src.split('\n')) {
    // Hanya baris di dalam komentar JavaScript (// atau * pada blok /* */).
    if (!/^\s*(\/\/|\*)\s/.test(baris)) continue;

    // (a) direktif @js/@json/@can/… dengan satu-at (bukan @@).
    if (/[^@]@(js|json|can|php|include)\b/.test(baris)) {
      jsKomentar.push(`${path.basename(p)}: ${baris.trim().slice(0, 70)}`);
      continue;
    }

    /*
     * (b) sintaks kurung-kurawal-ganda Blade.
     *
     * Versi pertama pemeriksaan ini hanya mencari direktif @, dan langsung
     * kecolongan: komentar berisi kurung-kurawal-ganda kosong di
     * categories/index.blade.php tetap dikompilasi menjadi e() tanpa argumen
     * dan mematikan halaman. Ditemukan render harness, bukan oleh checker ini.
     */
    if (/\{\{.*\}\}/.test(baris)) {
      jsKomentar.push(`${path.basename(p)}: ${baris.trim().slice(0, 70)}`);
    }
  }
}
if (jsKomentar.length) {
  fail(`direktif Blade di dalam komentar JS (pakai @@ untuk meloloskannya):\n     ${jsKomentar.join('\n     ')}`);
} else {
  ok('tidak ada direktif Blade yang tak sengaja aktif di komentar JS');
}

// Ukuran huruf di bawah 11px ditolak: target pengguna 40+ yang umumnya sudah
// presbiopia (BRANDING-GUIDELINE.md §4, penolakan TODO_BUG #135).
const sumberGaya = [...blades, path.join(ROOT, 'seekitar-server/public/css/admin.css')];
const fontKecil = [];
for (const p of sumberGaya) {
  if (!fs.existsSync(p)) continue;
  const src = fs.readFileSync(p, 'utf8');
  for (const m of src.matchAll(/font-size:\s*(\d+(?:\.\d+)?)px/g)) {
    if (parseFloat(m[1]) < 11) fontKecil.push(`${path.basename(p)}: ${m[0]}`);
  }
}
if (fontKecil.length) {
  fail(`font di bawah batas 11px (BRANDING §4): ${fontKecil.join(', ')}`);
} else {
  ok('tidak ada font di bawah 11px');
}

/*
 * number_format() tanpa argumen pemisah memakai format Inggris: 4812 menjadi
 * "4,812", yang dibaca orang Indonesia sebagai bilangan desimal. Kesalahan ini
 * tidak menimbulkan error dan lolos semua pemeriksaan sintaks — ia hanya salah
 * dibaca. Sudah pernah terjadi di dashboard.blade.php.
 */
const angkaInggris = [];
for (const p of [...blades, ...fs.readdirSync(path.join(ROOT, 'seekitar-server/app/DataTables'))
  .filter(f => f.endsWith('.php'))
  .map(f => path.join(ROOT, 'seekitar-server/app/DataTables', f))]) {
  const src = fs.readFileSync(p, 'utf8').replace(/\{\{--[\s\S]*?--\}\}/g, '');

  /*
   * Argumen diambil dengan menghitung kurung, BUKAN dengan `[^)]*`.
   *
   * Versi pertama checker ini memakai regex sederhana dan langsung salah:
   * `number_format((int) $x, 0, ',', '.')` terpotong pada `)` milik cast
   * `(int)`, sehingga pemanggilan yang SUDAH benar dilaporkan sebagai
   * pelanggaran. Bug di checker sendiri, ditemukan saat mengujinya.
   */
  let i = -1;
  while ((i = src.indexOf('number_format(', i + 1)) !== -1) {
    let depth = 0, j = i + 'number_format'.length;
    for (; j < src.length; j++) {
      if (src[j] === '(') depth++;
      else if (src[j] === ')' && --depth === 0) break;
    }
    const argumen = src.slice(i + 'number_format('.length, j);

    // Wajib menyebut pemisah Indonesia secara eksplisit: (…, 0, ',', '.').
    if (!/',\s*'\.'/.test(argumen)) {
      angkaInggris.push(`${path.basename(p)}: number_format(${argumen.replace(/\s+/g, ' ').slice(0, 45)})`);
    }
  }
}
if (angkaInggris.length) {
  fail(`number_format tanpa pemisah Indonesia (pakai App\\Support\\Angka):\n     ${angkaInggris.join('\n     ')}`);
} else {
  ok('semua angka memakai format Indonesia (4.812, bukan 4,812)');
}

/*
 * Berkas bahasa Datatables WAJIB di-host sendiri.
 *
 * Riwayatnya: seluruh tabel admin memuat
 * `cdn.datatables.net/plug-ins/2.1.8/i18n/id.json` dan selalu gagal dengan
 * "i18n file loading error". Sebabnya 2.1.8 adalah versi CORE Datatables,
 * sedangkan repo Plugins punya penomoran sendiri dan tidak pernah punya tag
 * itu — URL-nya 404.
 *
 * Menaikkan nomor versinya bukan perbaikan: nomor itu akan basi lagi pada
 * rilis berikutnya. Karena itu yang ditegakkan di sini adalah TIDAK ADA
 * rujukan i18n ke CDN sama sekali.
 */
const I18N_LOKAL = 'seekitar-server/public/vendor/datatables/id.json';

if (!exists(I18N_LOKAL)) {
  fail(`${I18N_LOKAL} tidak ada — tabel admin akan memakai bahasa Inggris atau gagal memuat i18n`);
} else {
  let bahasa = null;
  try {
    bahasa = JSON.parse(read(I18N_LOKAL));
  } catch (e) {
    fail(`${I18N_LOKAL} bukan JSON yang sah: ${e.message} — Datatables akan melaporkan i18n error`);
  }

  if (bahasa) {
    // Kunci yang benar-benar dibaca Datatables 2.x dan tampil di layar.
    const WAJIB = ['emptyTable', 'info', 'lengthMenu', 'processing', 'search', 'zeroRecords'];
    const kurang = WAJIB.filter(k => typeof bahasa[k] !== 'string');
    if (kurang.length) fail(`berkas i18n kurang kunci: ${kurang.join(', ')}`);
    else ok(`berkas i18n Indonesia lengkap (${WAJIB.length} kunci inti)`);
  }
}

const i18nCdn = [];
for (const p of blades) {
  const src = fs.readFileSync(p, 'utf8').replace(/\{\{--[\s\S]*?--\}\}/g, '');
  // Komentar JS penjelas ikut dibuang: catatan yang MENERANGKAN kenapa CDN
  // ditinggalkan justru harus menyebut URL-nya.
  const tanpaKomentar = src.replace(/^\s*(\/\/|\*).*$/gm, '');
  if (/cdn\.datatables\.net\/plug-ins/.test(tanpaKomentar)) {
    i18nCdn.push(path.basename(p));
  }
}
if (i18nCdn.length) {
  fail(`i18n Datatables masih dari CDN di: ${i18nCdn.join(', ')} — jalur plug-ins memakai penomoran berbeda dan mudah 404`);
} else {
  ok('i18n Datatables di-host sendiri, bukan dari CDN');
}

// Konfigurasi bahasa disetel SEKALI di layout; lima tabel yang masing-masing
// menulis ulang URL-nya adalah lima tempat yang bisa menyimpang.
const layoutSrc = read('seekitar-server/resources/views/admin/layout.blade.php');
if (!/dataTable\.defaults[\s\S]{0,200}vendor\/datatables\/id\.json/.test(layoutSrc)) {
  fail('layout admin tidak menyetel $.fn.dataTable.defaults.language — tiap tabel harus mengulangnya sendiri');
} else {
  ok('bahasa Datatables disetel sekali di layout');
}

// Ikon FontAwesome wajib punya aria-hidden: pembaca layar tidak boleh
// membacakan glyph dekoratif sebagai teks acak.
const ikonTanpaAria = [];
for (const p of blades) {
  const src = fs.readFileSync(p, 'utf8');
  for (const m of src.matchAll(/<i class="fa-[^"]*"(?![^>]*aria-hidden)[^>]*>/g)) {
    ikonTanpaAria.push(`${path.basename(p)}: ${m[0].slice(0, 50)}`);
  }
}
if (ikonTanpaAria.length) {
  fail(`ikon tanpa aria-hidden: ${ikonTanpaAria.join(', ')}`);
} else {
  ok('semua ikon dekoratif memakai aria-hidden');
}

// ─────────────────────────────────── 5b. Pola tabel: pilih baris, bukan kolom aksi
console.log('\nPola tabel: baris terpilih, bukan kolom aksi');

/*
 * Tombol aksi TIDAK boleh kembali menjadi kolom tabel.
 *
 * HTML-nya tetap dikirim server di field `action` (dan tetap dipakai), tetapi
 * `action` tidak boleh muncul di daftar `columns` view mana pun — kalau
 * muncul, kolom tombol kembali dan pola pilih-baris jadi setengah jadi.
 */
const kolomAksi = [];
for (const p of blades) {
  const src = fs.readFileSync(p, 'utf8').replace(/\{\{--[\s\S]*?--\}\}/g, '');
  if (/'data'\s*=>\s*'action'/.test(src) || /\{\s*data:\s*'action'/.test(src)) {
    kolomAksi.push(path.basename(p));
  }
}
if (kolomAksi.length) {
  fail(`'action' masih didaftarkan sebagai kolom di: ${kolomAksi.join(', ')} — aksi seharusnya muncul di bilah sebelah judul`);
} else {
  ok("'action' tidak lagi menjadi kolom tabel");
}

// Setiap partial aksi WAJIB dibungkus @can. Bilah aksi hanyalah tempat
// menampilkan; tanpa @can, admin tanpa izin ikut melihat tombolnya.
const aksiTanpaCan = [];
for (const p of blades.filter(x => path.basename(x) === '_actions.blade.php')) {
  const src = fs.readFileSync(p, 'utf8').replace(/\{\{--[\s\S]*?--\}\}/g, '');
  if (!/@can(any)?\(/.test(src)) {
    aksiTanpaCan.push(path.relative(viewDir, p));
  }
}
if (aksiTanpaCan.length) {
  fail(`partial aksi tanpa @can: ${aksiTanpaCan.join(', ')} — tombol tampil ke admin yang tidak berhak`);
} else {
  ok('semua partial aksi dibungkus @can');
}

/*
 * HTML tidak boleh dioper antar-view sebagai nilai variabel.
 *
 * `@include(..., ['filter' => view('x')])` lalu menampilkannya dengan
 * `{{ $filter }}` membuat SELURUH filter tampil sebagai teks mentah
 * (`&lt;select&gt;…`). Penyebabnya: @include me-render sub-view menjadi
 * string lebih dulu, dan string biasa memang di-escape `{{ }}` — objek View
 * yang Htmlable tidak pernah sampai ke sana.
 *
 * Halaman tetap "berhasil dirender", jadi render harness pun tidak
 * mengeluhkannya. Hanya pemeriksaan ini yang menangkapnya.
 *
 * Perbaikannya BUKAN {!! !!} (mematikan escaping — TODO_BUG #202), melainkan
 * mengoper NAMA view lalu @includeIf.
 */
const viewSebagaiVariabel = [];
for (const p of blades) {
  const src = fs.readFileSync(p, 'utf8').replace(/\{\{--[\s\S]*?--\}\}/g, '');
  for (const m of src.matchAll(/'(\w+)'\s*=>\s*view\(/g)) {
    viewSebagaiVariabel.push(`${path.basename(path.dirname(p))}/${path.basename(p)}: '${m[1]}' => view(...)`);
  }
}
if (viewSebagaiVariabel.length) {
  fail(`objek view dioper sebagai variabel — akan tampil sebagai teks ter-escape:\n     ${viewSebagaiVariabel.join('\n     ')}\n     Pakai nama view + @includeIf.`);
} else {
  ok('tidak ada objek view yang dioper sebagai variabel');
}

// Bilah aksi harus kosong sebelum ada baris dipilih — tanpa teks petunjuk.
const petunjukTersisa = blades.filter(p =>
  /Pilih satu baris|admin-rowactions-hint/.test(
    fs.readFileSync(p, 'utf8').replace(/\{\{--[\s\S]*?--\}\}/g, '')));
if (petunjukTersisa.length) {
  fail(`teks petunjuk masih ada di: ${petunjukTersisa.map(p => path.basename(p)).join(', ')}`);
} else {
  ok('bilah aksi kosong sebelum baris dipilih');
}

// Nama/judul entitas tidak lagi ditempel di sebelah tombol aksi.
const namaDiAksi = blades
  .filter(p => path.basename(p) === '_actions.blade.php')
  .filter(p => /text-muted small ms-1/.test(fs.readFileSync(p, 'utf8')));
if (namaDiAksi.length) {
  fail(`nama entitas masih ditempel di bilah aksi: ${namaDiAksi.map(p => path.basename(path.dirname(p))).join(', ')}`);
} else {
  ok('bilah aksi hanya berisi tombol');
}

// Tabel yang memakai partial table-page tidak boleh lagi merakit DataTable
// sendiri — dua implementasi berarti perilaku pemilihan bisa menyimpang.
const daftarTabel = [
  'users/index', 'stores/index', 'listings/index', 'orders/index',
  'requests/index', 'offers/index', 'reviews/index', 'disputes/index',
];
const tidakPakaiPartial = daftarTabel.filter(v => {
  const f = path.join(viewDir, `${v}.blade.php`);
  return !fs.existsSync(f) || !fs.readFileSync(f, 'utf8').includes('admin.partials.table-page');
});
if (tidakPakaiPartial.length) {
  fail(`tidak memakai partial table-page: ${tidakPakaiPartial.join(', ')}`);
} else {
  ok(`${daftarTabel.length} halaman tabel memakai partial bersama`);
}

// Pemilihan harus TUNGGAL: partial wajib membersihkan pilihan lama sebelum
// menandai yang baru.
const partialTabel = path.join(viewDir, 'partials/table-page.blade.php');
if (!fs.existsSync(partialTabel)) {
  fail('partial table-page tidak ada');
} else {
  const src = fs.readFileSync(partialTabel, 'utf8');
  if (!/removeClass\('table-active'\)/.test(src)) {
    fail('partial table-page tidak pernah membersihkan baris terpilih — pemilihan bisa jadi ganda');
  } else {
    ok('pemilihan baris dijaga tetap tunggal');
  }
}

// ─────────────────────────────────── 6. Query DataTables
console.log('\nQuery & relasi DataTables');

/*
 * Dua kelas bug yang lolos semua pemeriksaan statis dan hanya muncul di
 * browser, keduanya pernah terjadi:
 *
 *   - with('user') pada model yang relasinya owner()
 *       → "Call to undefined relationship [user] on model [Store]"
 *   - withCount() ditimpa select()
 *       → "Requested unknown parameter 'offers_count'"
 *
 * Keduanya butuh memuat Laravel sungguhan, jadi dijalankan lewat skrip PHP.
 * Kode keluar diabaikan: pembungkus php-wasm selalu mengembalikan 0.
 */
for (const [skrip, judul] of [
  ['tools/dev/check-relations.php', 'relasi eager-load'],
  ['tools/dev/check-datatables.php', 'kolom & urutan query DataTables'],
]) {
  let keluaran = '';
  try {
    keluaran = execFileSync(path.join(ROOT, 'tools/dev/php'), [path.join(ROOT, skrip)],
      { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] });
  } catch (e) {
    keluaran = (e.stdout?.toString() ?? '') + (e.stderr?.toString() ?? '');
  }

  const jumlah = Number(keluaran.match(/(\d+) masalah/)?.[1] ?? -1);

  if (jumlah === 0) {
    ok(judul);
  } else {
    const rincian = keluaran.split('\n').filter(l => /GAGAL|tidak punya|Yang ada/.test(l));
    fail(`${judul}:\n     ${rincian.join('\n     ') || keluaran.trim() || 'tidak ada keluaran'}`);
  }
}

console.log(problems === 0
  ? '\n✅ Panel admin konsisten: menu, izin, route, render, dan query.'
  : `\n❌ ${problems} masalah ditemukan.`);
if (problems) process.exitCode = 1;
