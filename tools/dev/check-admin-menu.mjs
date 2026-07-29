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

  /*
   * Ukuran huruf terkecil di sidebar: fs-1 = .625rem = 10px.
   *
   * Ini di bawah batas 11px (BRANDING-GUIDELINE §4; usulan 8–10px pernah ditolak), tetapi
   * pemeriksaan font yang sudah ada TIDAK menangkapnya: ia mencari deklarasi
   * `font-size:Npx`, sedangkan di sini ukurannya datang dari KELAS utilitas.
   * Terbukti lewat getComputedStyle di Chromium — tiga elemen sidebar
   * (dua lencana + kode BPS) merender 10px dan lolos semua checker.
   */
  const fs1 = [...bersih.matchAll(/class="[^"]*\bfs-1\b[^"]*"/g)].map(m => m[0].slice(0, 60));
  if (fs1.length) {
    fail(`sidebar memakai .fs-1 (= 10px, di bawah batas 11px BRANDING §4): ${fs1.join(', ')}`);
  } else {
    ok('sidebar tidak memakai .fs-1 (10px)');
  }

  /*
   * Kartu wilayah di kaki sidebar memakai pola .sidebar-ad milik template.
   * Kelas itu bukan hiasan: template menyembunyikannya otomatis saat
   * mini-sidebar lewat aturan `[data-sidebartype=mini-sidebar] .sidebar-ad
   * {display:none}`. Tanpa kelasnya, kartu tetap tampil di sidebar selebar
   * 87px dan teksnya terpotong.
   */
  if (!/class="[^"]*\bsidebar-ad\b/.test(bersih)) {
    fail('kartu kaki sidebar tidak memakai .sidebar-ad — tidak akan tersembunyi saat mini-sidebar');
  } else {
    ok('kartu kaki sidebar memakai pola .sidebar-ad template');
  }
}

/*
 * Backdrop gelap saat sidebar terbuka di layar kecil.
 *
 * Template menyediakan `<div class="dark-transparent sidebartoggler">` di luar
 * .page-wrapper, dan app.min.js memasang penutup pada SETIAP .sidebartoggler.
 * Tanpa elemen ini, di ponsel sidebar menutupi konten tanpa peredupan dan
 * satu-satunya cara menutupnya adalah menemukan tombol X — mengetuk di luar
 * tidak melakukan apa pun. Diverifikasi di Chromium 390px: sebelum perbaikan
 * `.dark-transparent` tidak ada sama sekali.
 */
{
  const layout = read('seekitar-server/resources/views/admin/layout.blade.php');
  const adaBackdrop = /class="dark-transparent[^"]*sidebartoggler/.test(layout);

  if (!adaBackdrop) {
    fail('layout tanpa <div class="dark-transparent sidebartoggler"> — sidebar ponsel tanpa peredupan & tidak bisa ditutup dari luar');
  } else {
    ok('backdrop sidebar ponsel ada & terhubung ke sidebartoggler');
  }
}

/*
 * Dua cacat tata letak sidebar yang diukur di peramban, bukan dibaca.
 *
 * 1. Lencana menabrak panah .has-arrow. Panah digambar ::after yang
 *    diposisikan absolut, jadi tidak menempati ruang layout dan lencana di
 *    ujung baris menimpanya. Terukur: panah x 213–220, lencana 208,6–235.
 *
 * 2. Kartu kaki sidebar jatuh di bawah lipatan. Template memberi
 *    .scroll-sidebar tinggi calc(100vh - 80px) sementara .brand-logo sudah
 *    memakai 70px — hanya 10px tersisa untuk kartu setinggi 83px.
 *    Perbaikannya flex, bukan angka calc() baru yang akan salah lagi.
 */
{
  const adminCss = read('seekitar-server/public/css/admin.css')
    .replace(/\/\*[\s\S]*?\*\//g, '');

  if (!/\.sidebar-link\.has-arrow\s*>\s*\.hide-menu:last-child\s*\{[^}]*margin-right/.test(adminCss)) {
    fail('lencana pada menu bersubmenu tidak diberi ruang — akan menabrak panah .has-arrow');
  } else {
    ok('lencana sidebar tidak menabrak panah submenu');
  }

  const flexKolom = /\.left-sidebar\s*>\s*div\s*\{[^}]*flex-direction:\s*column/.test(adminCss);
  const gulirFleks = /\.left-sidebar\s+\.scroll-sidebar\s*\{[^}]*min-height:\s*0/.test(adminCss);

  if (!flexKolom || !gulirFleks) {
    fail('kaki sidebar tidak memakai tata letak flex — kartu wilayah jatuh di bawah lipatan (template menyisakan 10px untuk kartu 83px)');
  } else {
    ok('kaki sidebar memakai flex — kartu wilayah selalu terlihat');
  }

  /*
   * Ikon submenu tidak boleh tertinggal di 7px.
   *
   * Template mengunci `.first-level .sidebar-link .ti` ke font-size 7px, dan
   * itu wajar di sana: SEMUA 98 ikon submenu template adalah `ti-circle`,
   * sekadar titik penanda daftar. Seekitar memakai ikon bermakna, yang pada
   * 7px menyusut jadi bintik tak terbedakan. Diukur di Chromium: 7×7 px
   * berbanding 21×21 px milik menu induk.
   */
  const ikonSub = adminCss.match(/\.first-level\s+\.sidebar-link\s+\.ti\s*\{[^}]*\}/s)?.[0] ?? '';
  const ukuran = parseFloat(ikonSub.match(/font-size:\s*(\d+(?:\.\d+)?)px/)?.[1] ?? '0');

  if (ukuran < 12) {
    fail('ikon submenu tidak diperbesar dari 7px bawaan template — ikon bermakna menyusut jadi bintik');
  } else {
    ok(`ikon submenu terbaca (${ukuran}px, bukan 7px bawaan template)`);
  }

  /*
   * Submenu aktif tidak boleh dibedakan HANYA lewat warna.
   *
   * Template menandainya dengan mengubah teks jadi hijau saja, latar dipaksa
   * transparan. Terukur: hijau #168A4A di atas putih = 4,40:1 — di bawah
   * ambang WCAG AA 4.5:1; dan bedanya dengan butir non-aktif hanya 2,81:1
   * pada bobot huruf yang sama, sehingga tidak terlihat oleh pengguna buta
   * warna merah-hijau (WCAG 1.4.1).
   */
  const aktifSub = adminCss.match(/\.first-level[^{]*\.sidebar-link\.active\s*\{[^}]*\}/s)?.[0] ?? '';
  const adaLatar = /background-color:\s*var\(--bs-primary-bg-subtle\)/.test(aktifSub);
  const adaBobot = /font-weight:\s*[6-9]\d\d/.test(aktifSub);

  if (!adaLatar || !adaBobot) {
    fail('submenu aktif hanya dibedakan warna — butuh latar + bobot huruf (WCAG 1.4.1, kontras template 4,40:1 < 4.5:1)');
  } else {
    ok('submenu aktif punya penanda non-warna (latar + bobot)');
  }
}

// ─────────────────────────────────── 5d. Dasbor
console.log('\nDasbor (pola index2 template)');

{
  const DASH = 'seekitar-server/resources/views/admin/dashboard.blade.php';
  const dash = read(DASH);

  /*
   * `align-items-strech` — ejaan yang salah, kurang huruf t.
   *
   * JUJUR SOAL DAMPAKNYA: ini BUKAN bug visual. Diuji langsung di Chromium
   * dengan dua kolom flex berdampingan — `align-items:normal` menghasilkan
   * tinggi yang IDENTIK dengan `align-items:stretch` (200px vs 200px), karena
   * `normal` memang berperilaku sebagai `stretch` pada flex container. Kelas
   * yang salah eja itu sekadar tidak ada di CSS, jadi tidak berefek apa pun.
   *
   * Tetap ditolak karena menyesatkan: pembaca berikutnya mengira tinggi kartu
   * disamakan oleh kelas itu, lalu menghapusnya saat merapikan — dan baru
   * saat itu tata letaknya benar-benar berubah.
   */
  const salahEja = (dash.match(/align-items-strech/g) || []).length;
  if (salahEja) {
    fail(`${salahEja} kemunculan "align-items-strech" (salah eja, kelasnya tidak ada di CSS) — tulis align-items-stretch`);
  } else {
    ok('tidak ada kelas Bootstrap yang salah eja');
  }

  /*
   * Tabel ringkas dasbor punya 4 kolom dan sel yang panjang (judul permintaan,
   * nama toko, rupiah). Pada col-lg-6 = 489px, kolom terakhir "Status"
   * terpotong di 1440px, 1280px, dan 992px sekaligus — terukur meluber sampai
   * 262px. Template menaruh tabel selebar ini di kolom lebar (col-lg-8),
   * bukan dua tabel bersebelahan.
   */
  const tabelSempit = [...dash.matchAll(/<div class="col-lg-6 d-flex[^"]*">\s*<div class="card w-100">[\s\S]{0,900}?<table/g)].length;
  if (tabelSempit) {
    fail(`${tabelSempit} tabel ringkas di kolom col-lg-6 — kolom terakhir terpotong (terukur meluber s.d. 262px di 992px)`);
  } else {
    ok('tabel ringkas dasbor tidak dijepit di kolom setengah lebar');
  }

  /*
   * Setiap variabel yang dipakai dasbor harus benar-benar dikirim controller.
   * `$sorotan` sempat dipakai di Blade sebelum controllernya menyediakan —
   * render harness menangkapnya, tetapi hanya karena fixture-nya diperbarui.
   * Pemeriksaan ini membandingkan langsung ke sumbernya.
   */
  const ctrl = read('seekitar-server/app/Http/Controllers/Admin/DashboardController.php');
  const dikirim = new Set(
    [...ctrl.matchAll(/'(\w+)'\s*=>\s*\$this->/g)].map(m => m[1]),
  );

  // Variabel tingkat atas yang dirujuk view, tanpa yang berasal dari @foreach
  // atau helper global.
  const LOKAL = new Set(['card', 'item', 'role', 'offer', 'hari', 'label', 'loop']);
  const dipakai = new Set(
    [...dash.matchAll(/\$(\w+)\s*\[/g)].map(m => m[1])
      .concat([...dash.matchAll(/\{\{\s*\\?\$(\w+)\b/g)].map(m => m[1]))
      .filter(v => !LOKAL.has(v) && v !== 'errors'),
  );

  const takDikirim = [...dipakai].filter(v => !dikirim.has(v));
  if (takDikirim.length) {
    fail(`dasbor memakai variabel yang tidak dikirim DashboardController: ${takDikirim.join(', ')}`);
  } else {
    ok(`${dikirim.size} variabel dasbor semuanya disediakan controller`);
  }

  /*
   * Dasbor harus tetap RINGKAS.
   *
   * Diukur di Chromium pada 1440x900: versi sebelum pemadatan butuh 2818px
   * (3,13 layar penuh) untuk isi yang sama. Penyebab terbesarnya blok yang
   * mengulang informasi tempat lain — kartu "Peran & Wilayah" mengulang peran
   * dari dropdown header DAN wilayah dari kaki sidebar — plus dua tabel yang
   * ditumpuk ke bawah, bukan berdampingan.
   *
   * Pemeriksaan ini tidak bisa mengukur piksel tanpa peramban, jadi yang
   * dijaga adalah keputusan strukturalnya: tabel berdampingan, dan tidak ada
   * lagi kartu yang mengulang peran/wilayah.
   */
  const tabelBerdampingan = (dash.match(/col-xl-6 d-flex align-items-stretch/g) || []).length;
  if (tabelBerdampingan < 2) {
    fail('tabel ringkas tidak berdampingan (col-xl-6) — dasbor memanjang tanpa perlu');
  } else {
    ok('dua tabel ringkas berdampingan di layar lebar');
  }

  if (/Peran\s*&amp;\s*Wilayah|Hak akses akun Anda/.test(dash)) {
    fail('kartu "Peran & Wilayah" mengulang isi dropdown header + kaki sidebar — hapus, bukan tampilkan tiga kali');
  } else {
    ok('dasbor tidak mengulang peran/wilayah yang sudah ada di header & sidebar');
  }

  /*
   * Tinggi grafik tidak boleh dipatok piksel lewat atribut style.
   *
   * `height: 300px` memakan 39% tinggi layar laptop 1366x768 untuk satu blok,
   * sementara di monitor besar justru terlihat kerdil. Dipindah ke .admin-grafik
   * yang memakai clamp() terhadap tinggi viewport.
   */
  if (/style="[^"]*height:\s*\d+px/.test(dash)) {
    fail('tinggi grafik dipatok piksel lewat atribut style — pakai .admin-grafik (clamp terhadap viewport)');
  } else {
    ok('tinggi grafik responsif terhadap tinggi layar');
  }

  /*
   * Sel judul tabel ringkas WAJIB punya patokan lebar.
   *
   * `text-truncate` di dalam <td> tidak cukup: sel tabel melebar mengikuti isi
   * terpanjang sehingga text-overflow tidak pernah aktif. Terukur — sel
   * pertama tabel penawaran menolak menyusut di bawah 265px dan membuat tabel
   * meluber 13px di 1366px serta 175px di ponsel.
   */
  const adminCssDash = read('seekitar-server/public/css/admin.css')
    .replace(/\/\*[\s\S]*?\*\//g, '');
  const punyaKelas = /class="[^"]*\badmin-ringkas\b/.test(dash);
  const punyaAturan = /\.admin-ringkas\s+td:first-child[^{]*\{[^}]*max-width:\s*0/s.test(adminCssDash);

  if (!punyaKelas || !punyaAturan) {
    fail('tabel ringkas tanpa .admin-ringkas + max-width:0 pada sel pertama — kolom terakhir meluber (terukur 175px di 390px)');
  } else {
    ok('sel judul tabel ringkas bisa menyusut — tidak ada kolom yang meluber');
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
// presbiopia (BRANDING-GUIDELINE.md §4; usulan 8–10px pernah ditolak).
const sumberGaya = [...blades, path.join(ROOT, 'seekitar-server/public/css/admin.css')];
const fontKecil = [];
for (const p of sumberGaya) {
  if (!fs.existsSync(p)) continue;

  /*
   * Komentar dibuang dulu. Catatan yang MENJELASKAN kenapa sebuah ukuran
   * ditolak sering menyebut angkanya — misalnya "template mengunci ikon ke
   * font-size: 7px" — dan tanpa pembuangan ini checker melaporkan
   * penjelasannya sendiri sebagai pelanggaran. Persis jebakan yang sama
   * dengan prosa di komentar CSS pada pemeriksaan fokus isian.
   */
  const src = fs.readFileSync(p, 'utf8')
    .replace(/\/\*[\s\S]*?\*\//g, '')        // komentar CSS & PHP blok
    .replace(/\{\{--[\s\S]*?--\}\}/g, '');   // komentar Blade

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

// Ikon Tabler harus Netral ATAU Bermakna bagi pembaca layar:
//   - dekoratif ⇒ aria-hidden="true" supaya glyph-nya tidak dibacakan acak;
//   - bermakna  ⇒ role="img" + aria-label (mis. centang "KTP terverifikasi"
//     di _cek_terverifikasi — menyembunyikannya justru menghilangkan arti).
// Tag tanpa keduanya tidak punya perlakuan aksesibilitas sama sekali.
//
// Panel memakai Tabler (`ti ti-*`) sejak beralih ke template Modernize —
// FontAwesome tidak lagi dimuat sama sekali, jadi kelas `fa-*` yang tersisa
// akan tampil sebagai kotak kosong.
const ikonTanpaAria = [];
for (const p of blades) {
  const src = fs.readFileSync(p, 'utf8');
  for (const m of src.matchAll(/<i class="ti [^"]*"(?![^>]*aria-hidden)[^>]*>/g)) {
    // role="img" + aria-label adalah alternatif aksesibel yang SAH.
    if (/role="img"/.test(m[0]) && /aria-label=/.test(m[0])) continue;
    ikonTanpaAria.push(`${path.basename(p)}: ${m[0].slice(0, 50)}`);
  }
}
if (ikonTanpaAria.length) {
  fail(`ikon tanpa perlakuan aksesibilitas (aria-hidden, atau role+aria-label): ${ikonTanpaAria.join(', ')}`);
} else {
  ok('semua ikon dekoratif aria-hidden; ikon bermakna punya aria-label');
}

/*
 * Tidak boleh ada sisa FontAwesome.
 *
 * Template Modernize memakai Tabler, dan FontAwesome sudah TIDAK dimuat sama
 * sekali. Kelas `fa-*` yang tertinggal tidak memunculkan error apa pun — ia
 * hanya tampil sebagai ruang kosong, dan itu baru terlihat oleh mata manusia.
 */
const sisaFa = [];
for (const p of blades) {
  const src = fs.readFileSync(p, 'utf8').replace(/\{\{--[\s\S]*?--\}\}/g, '');
  if (/\bfa-solid\b|\bfa-regular\b|"fa-[a-z]/.test(src)) {
    sisaFa.push(path.basename(path.dirname(p)) + '/' + path.basename(p));
  }
}
if (sisaFa.length) {
  fail(`kelas FontAwesome tersisa (ikon akan kosong): ${sisaFa.join(', ')}`);
} else {
  ok('tidak ada sisa FontAwesome — semua ikon Tabler');
}

// Template Modernize sudah memuat Bootstrap 5.3.3 di styles.min.css.
// Memuat CSS Bootstrap lagi menggandakan ~200 KB dan membuat aturan yang
// belakangan menang secara acak tergantung urutan berkas.
const layoutBlades = [
  path.join(viewDir, 'layout.blade.php'),
  path.join(viewDir, 'auth/login.blade.php'),
];
const bootstrapGanda = layoutBlades.filter(f =>
  fs.existsSync(f) && /<link[^>]+bootstrap@[^>]+\.css/.test(fs.readFileSync(f, 'utf8')));
if (bootstrapGanda.length) {
  fail(`CSS Bootstrap dimuat terpisah padahal sudah ada di styles.min.css: ${bootstrapGanda.map(f => path.basename(f)).join(', ')}`);
} else {
  ok('Bootstrap tidak dimuat ganda');
}

// Aset template harus benar-benar ada di repositori.
const V = 'seekitar-server/public/vendor/modernize';
let asetHilang = 0;
for (const aset of [
  `${V}/css/style.min.css`,
  `${V}/css/icons/tabler-icons/tabler-icons.min.css`,
  `${V}/css/icons/tabler-icons/fonts/tabler-icons.woff2`,
  `${V}/js/app.min.js`,
  `${V}/js/seekitar.init.js`,
  `${V}/js/sidebarmenu.js`,
  `${V}/js/custom.js`,
  `${V}/SUMBER.md`,   // provenance & catatan lisensi
]) {
  if (!exists(aset)) { fail(`aset template hilang: ${aset}`); asetHilang++; }
}
if (!asetHilang) ok('aset template Modernize lengkap');

/*
 * Warna template WAJIB sudah hijau.
 *
 * Biru bawaan #5D87FF ditulis langsung di 117 tempat di dalam style.min.css —
 * variabel CSS tidak menjangkaunya. Kalau berkas vendor diperbarui tanpa
 * menjalankan ulang tools/dev/recolor-modernize.mjs, panel diam-diam kembali
 * biru di ratusan komponen.
 */
const vendorCss = path.join(ROOT, `${V}/css/style.min.css`);
if (fs.existsSync(vendorCss)) {
  const isi = fs.readFileSync(vendorCss, 'utf8');
  const biru = (isi.match(/#5[dD]87[fF][fF]/g) || []).length;

  if (biru > 0) {
    fail(`style.min.css masih memuat ${biru} biru #5D87FF — jalankan: node tools/dev/recolor-modernize.mjs`);
  } else if (!isi.includes('#168A4A') && !isi.includes('#168a4a')) {
    fail('style.min.css tidak memuat hijau Seekitar #168A4A — pewarnaan belum dijalankan');
  } else {
    ok('CSS template sudah diwarnai hijau Seekitar');
  }
}

/*
 * Font Tabler: hanya woff2 yang disalin, jadi CSS-nya tidak boleh lagi
 * meminta eot/ttf/woff — tiga permintaan 404 di setiap halaman.
 */
const tiCss = path.join(ROOT, `${V}/css/icons/tabler-icons/tabler-icons.min.css`);
if (fs.existsSync(tiCss)) {
  const isi = fs.readFileSync(tiCss, 'utf8');
  const mati = ['\\.eot', '\\.ttf', '\\.woff\\?', '\\.woff"']
    .filter(ext => new RegExp(`tabler-icons${ext}`).test(isi));

  if (mati.length) {
    fail(`tabler-icons.min.css masih merujuk font yang tidak disalin (404): ${mati.join(', ')}`);
  } else {
    ok('font Tabler hanya woff2 — tidak ada rujukan 404');
  }
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
 * Perbaikannya BUKAN {!! !!} (mematikan escaping), melainkan
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

// ── Select2 ────────────────────────────────────────────────────────────
/*
 * Setiap <select> panel wajib memakai Select2 agar bisa dicari, dan
 * pemasangannya harus lewat kelas `.js-select2` — bukan selektor `select`
 * global, yang akan ikut membungkus pemilih "Tampilkan N entri" milik
 * Datatables dan membuatnya hilang setiap tabel digambar ulang.
 */
const selectTanpaKelas = [];
for (const p of blades) {
  /*
   * Ekspresi Blade dibuang lebih dulu.
   *
   * Versi pertama memakai `<select\b[^>]*>` dan langsung salah: tag seperti
   *   <select id="setting-{{ $s->key }}" ... class="form-select js-select2">
   * membuat regex berhenti pada `>` MILIK `{{ }}`, sehingga atribut class
   * yang berada sesudahnya tak pernah terbaca dan berkas yang sudah benar
   * dilaporkan melanggar. Bug di checker sendiri, ketahuan saat mengujinya.
   */
  const src = fs.readFileSync(p, 'utf8')
    .replace(/\{\{--[\s\S]*?--\}\}/g, '')
    .replace(/\{\{[\s\S]*?\}\}/g, 'X')
    .replace(/@\w+\([^)]*\)/g, 'X');

  for (const m of src.matchAll(/<select\b[^>]*?>/g)) {
    if (!/js-select2/.test(m[0])) {
      selectTanpaKelas.push(`${path.basename(path.dirname(p))}/${path.basename(p)}`);
    }
  }
}
if (selectTanpaKelas.length) {
  fail(`<select> tanpa kelas js-select2: ${[...new Set(selectTanpaKelas)].join(', ')}`);
} else {
  ok('semua <select> memakai Select2');
}

/*
 * Filter tabel WAJIB memakai jQuery .on('change'), BUKAN addEventListener.
 *
 * Select2 mengganti nilai lewat `$el.trigger('change')` jQuery, dan event
 * sintetis itu tidak menyentuh listener native. Diverifikasi di jsdom:
 * addEventListener terpanggil 0 kali, jQuery .on 1 kali. Memakai yang salah
 * membuat SELURUH filter berhenti bekerja tanpa satu pun pesan error.
 */
const partialSrc = fs.existsSync(path.join(viewDir, 'partials/table-page.blade.php'))
  ? fs.readFileSync(path.join(viewDir, 'partials/table-page.blade.php'), 'utf8')
  : '';
if (/addEventListener\('change'/.test(partialSrc.replace(/\{\{--[\s\S]*?--\}\}/g, ''))) {
  fail("filter memakai addEventListener('change') — Select2 memicu event jQuery, filter akan mati diam-diam");
} else if (!/\$\('\[data-dt-filter/.test(partialSrc)) {
  fail('filter tabel tidak terpasang listener change apa pun');
} else {
  ok("filter memakai jQuery .on('change') — kompatibel dengan Select2");
}

// Berkas Select2 & temanya harus dimuat layout.
for (const [aset, label] of [
  ['select2@4.1.0/dist/js/select2.full.min.js', 'Select2 (varian full, agar i18n bisa didaftarkan)'],
  ['select2@4.1.0/dist/js/i18n/id.js', 'terjemahan Indonesia Select2'],
  ['select2-bootstrap-5-theme', 'tema Bootstrap 5 Select2'],
]) {
  if (!layoutSrc.includes(aset)) fail(`layout tidak memuat ${label}`);
}
ok('aset Select2 lengkap di layout');

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


/*
 * Blade admin harus bersih dari komentar naratif.
 *
 * View adalah lapisan presentasi; penjelasan alasan, riwayat perbaikan, dan
 * catatan investigasi tidak dibaca siapa pun di sana dan membuat berkasnya
 * sulit dipindai. Alasan teknis tempatnya di controller, service, atau
 * Server_Implementation_Guide.md.
 *
 * DUA pengecualian:
 *   1. Dokumentasi PARAMETER di kepala partial — itu kontrak bagi
 *      pemanggilnya (id/nama kontainer turunan, variabel wajib, dsb.).
 *   2. Catatan di KEPALA berkas (sebelum cuplikan HTML pertama) yang
 *      menjelaskan kenapa berkasnya dirakit dengan cara tertentu — dipindah
 *      ke controller berarti memisahkannya dari markup yang justru ia
 *      jelaskan; editor berkas inilah pembacanya, setiap kali berkas dibuka.
 *
 * Komentar singkat menjelang baris yang rumit dibiarkan, selama pendek —
 * yang dilarang tetap riwayat/penjelasan berlarut-larut.
 */
const PARTIAL_KEPALA_BERDOKUMEN = /^\{\{--[\s\S]*?\$[a-zA-Z]/;

const naratif = [];
for (const p of blades) {
  const src = fs.readFileSync(p, 'utf8');
  for (const m of src.matchAll(/\{\{--([\s\S]*?)--\}\}/g)) {
    const isi = m[1].trim();
    const baris = isi.split('\n').length;
    const penanda = /KENAPA|Kenapa|Sebabnya|Diverifikasi|Versi sebelumnya|⚠️|TODO_BUG|jebakan|Akibatnya/.test(isi);

    // Kepala berkas yang mendokumentasikan parameter = kontrak pemanggil;
    // sebutan variabel ($index, $user, …) adalah ciri khasnya.
    const diKepala = src.slice(0, m.index).trim() === '';
    if (diKepala && PARTIAL_KEPALA_BERDOKUMEN.test(m[0])) continue;

    if (baris > 2 || penanda) {
      naratif.push(`${path.basename(path.dirname(p))}/${path.basename(p)}`);
      break;
    }
  }
}
if (naratif.length) {
  fail(`komentar naratif di Blade (pindahkan ke controller/dokumen): ${[...new Set(naratif)].join(', ')}`);
} else {
  ok('Blade admin bersih dari komentar naratif');
}

// Kartu judul + remah roti memakai pola Modernize di setiap halaman.
const tanpaHeader = [];
for (const p of blades) {
  const src = fs.readFileSync(p, 'utf8');
  if (!/@extends\('admin\.layout'\)/.test(src)) continue;
  if (/admin\.partials\.table-page/.test(src)) continue;   // sudah dari partial
  /*
   * Pencocokan harfiah `card bg-light-primary` terlalu ketat: kartu sambutan
   * dasbor memakai `card w-100 bg-light-primary` mengikuti pola index2, dan
   * urutan kelas Bootstrap tidak bermakna. Yang benar-benar diuji adalah
   * ADANYA kartu bernada primary sebagai kepala halaman, bukan urutan kata.
   */
  if (!/class="[^"]*\bcard\b[^"]*\bbg-light-primary\b/.test(src)) {
    tanpaHeader.push(path.basename(path.dirname(p)) + '/' + path.basename(p));
  }
}
if (tanpaHeader.length) {
  fail(`halaman tanpa kartu judul Modernize: ${tanpaHeader.join(', ')}`);
} else {
  ok('semua halaman memakai kartu judul + remah roti Modernize');
}


/*
 * Setiap partial yang MEMAKAI variabel lencana harus terdaftar di
 * View::composer. Kalau tidak, variabelnya tidak pernah terisi dan seluruh
 * lencana hilang tanpa error — `$x ?? 0` membuatnya gagal secara diam-diam.
 */
const composerSrc = read('seekitar-server/app/Providers/AppServiceProvider.php');
const butuhComposer = [];
for (const p of blades) {
  const src = fs.readFileSync(p, 'utf8');
  if (!/laporanLewatSla|pendingVerifikasi/.test(src)) continue;

  const nama = 'admin.' + path.relative(viewDir, p)
    .replace(/\.blade\.php$/, '')
    .replace(/\//g, '.');

  if (!composerSrc.includes(`'${nama}'`)) butuhComposer.push(nama);
}
if (butuhComposer.length) {
  fail(`view memakai variabel lencana tetapi tidak terdaftar di View::composer: ${butuhComposer.join(', ')}`);
} else {
  ok('semua view berlencana terdaftar di View::composer');
}

// ─────────────────────────────────── 5c. Halaman masuk
console.log('\nHalaman masuk (pola authentication-login template)');

const LOGIN = 'seekitar-server/resources/views/admin/auth/login.blade.php';

if (!exists(LOGIN)) {
  fail(`${LOGIN} tidak ada`);
} else {
  const login = read(LOGIN);

  /*
   * Struktur dua kolom dari package/html/main/authentication-login.html.
   *
   * Kelasnya BUKAN hiasan: `.radial-gradient` dan `.z-index-5` didefinisikan
   * di style.min.css, dan `col-xl-7` + `col-xl-5` yang berpasangan itulah yang
   * membuat ilustrasi dan formulir berdampingan. Mengganti salah satunya
   * membuat halaman kembali menjadi kartu tengah — tanpa error apa pun.
   */
  const WAJIB = [
    ['radial-gradient', 'latar gradasi'],
    ['z-index-5', 'lapisan konten di atas gradasi'],
    ['col-xl-7 col-xxl-8', 'kolom ilustrasi'],
    ['col-xl-5 col-xxl-4', 'kolom formulir'],
    ['authentication-login', 'panel formulir'],
    ['min-vh-100', 'tinggi penuh layar'],
    ['form-check-input primary', 'kotak centang bertema'],
    ['btn btn-primary w-100 py-8', 'tombol masuk'],
  ];

  const hilang = WAJIB.filter(([kelas]) => !login.includes(kelas));
  if (hilang.length) {
    fail(`halaman masuk kehilangan penanda template: ${hilang.map(([k, ket]) => `${k} (${ket})`).join(', ')}`);
  } else {
    ok(`halaman masuk memakai pola template (${WAJIB.length}/${WAJIB.length} penanda)`);
  }

  /*
   * Ilustrasi harus benar-benar ada DAN sudah hijau.
   *
   * Warna di SVG ditulis sebagai atribut fill, jadi CSS tidak menjangkaunya.
   * Kalau berkasnya disalin ulang dari template tanpa menjalankan
   * recolor-modernize.mjs, ilustrasinya kembali ungu di samping formulir hijau
   * dan tidak ada satu pun pemeriksaan lain yang mengeluh.
   */
  const ART = 'seekitar-server/public/vendor/modernize/images/backgrounds/login-security.svg';
  if (!login.includes('login-security.svg')) {
    fail('halaman masuk tidak menampilkan ilustrasi login-security.svg');
  } else if (!exists(ART)) {
    fail(`ilustrasi dirujuk tetapi berkasnya tidak ada: ${ART}`);
  } else {
    const svg = read(ART);
    const ungu = ['#8d95ff', '#757bff', '#ccd2ff', '#e1e5ff'].filter(w => svg.includes(w));

    if (ungu.length) {
      fail(`ilustrasi masuk masih ungu bawaan template (${ungu.join(', ')}) — jalankan: node tools/dev/recolor-modernize.mjs`);
    } else if (!svg.includes('#3FA46E')) {
      fail('ilustrasi masuk tidak memuat hijau Seekitar — pewarnaan belum dijalankan');
    } else {
      ok('ilustrasi masuk sudah diwarnai hijau');
    }
  }

  /*
   * `@keyframes gradient` TIDAK ADA di style.min.css maupun di berkas tema
   * mana pun di repositori template — sudah diperiksa langsung di style.css
   * yang belum diminifikasi. Animasi yang menunjuk nama tak dikenal diabaikan
   * browser diam-diam, jadi gradasinya membeku di satu warna. Definisinya ada
   * di admin.css; kalau hilang, latarnya diam lagi tanpa peringatan apa pun.
   */
  const adminCss = read('seekitar-server/public/css/admin.css');
  const vendorPunyaKeyframe = read(`${V}/css/style.min.css`).includes('@keyframes gradient');

  if (!vendorPunyaKeyframe && !/@keyframes\s+gradient\b/.test(adminCss)) {
    fail('.radial-gradient memanggil animasi "gradient" yang tidak didefinisikan di mana pun — latar akan diam');
  } else {
    ok('animasi gradasi latar terdefinisi');
  }

  /*
   * Elemen template yang TIDAK boleh ikut tersalin.
   *
   * Tombol "Sign in with Google/FB" menuntut OAuth yang tidak dipasang
   * (composer.json tidak memuat Socialite), dan "Create an account" tidak
   * berlaku: akun admin dibuat lewat seeder, bukan pendaftaran mandiri.
   * Tautan mati di halaman masuk membuat admin mengira panelnya rusak.
   */
  const PALSU = [
    ['google-icon', 'tombol Google (OAuth tidak dipasang)'],
    ['facebook-icon', 'tombol Facebook (OAuth tidak dipasang)'],
    ['Create an account', 'tautan daftar (akun admin dibuat seeder)'],
    ['Buat akun', 'tautan daftar (akun admin dibuat seeder)'],
  ];
  const adaPalsu = PALSU.filter(([t]) => login.includes(t));
  if (adaPalsu.length) {
    fail(`halaman masuk memuat elemen tanpa backend: ${adaPalsu.map(([, k]) => k).join(', ')}`);
  } else {
    ok('tidak ada tombol/tautan tanpa backend di halaman masuk');
  }

  /*
   * "Lupa Kata Sandi" hanya boleh tampil kalau route-nya benar-benar ada.
   * Template menyediakan tautannya; menyalinnya tanpa route membuat Blade
   * melempar RouteNotFoundException dan halaman masuk mati total — panel
   * tidak bisa diakses sama sekali.
   */
  const adaRouteLupa = /name\('password\.request'\)|name\('password\.email'\)/.test(read('seekitar-server/routes/admin.php'));
  const adaTautanLupa = /Lupa [Kk]ata [Ss]andi|Forgot Password/.test(login);

  if (adaTautanLupa && !adaRouteLupa) {
    fail('tautan "Lupa Kata Sandi" ada tetapi route password.request belum dibuat — halaman masuk akan melempar RouteNotFoundException');
  } else {
    ok(adaTautanLupa
      ? 'tautan lupa kata sandi punya route'
      : 'tidak ada tautan lupa kata sandi (route-nya memang belum ada)');
  }

  // Panel memakai satu stylesheet; halaman masuk tidak boleh memuat CDN
  // Bootstrap/jQuery yang tidak dipakainya (formulirnya HTML murni).
  const cdnTakTerpakai = ['code.jquery.com', 'bootstrap@5'].filter(c => login.includes(c));
  if (cdnTakTerpakai.length) {
    fail(`halaman masuk memuat skrip yang tidak dipakainya: ${cdnTakTerpakai.join(', ')}`);
  } else {
    ok('halaman masuk tidak memuat skrip yang tidak dipakai');
  }
}

/*
 * Fokus isian form: hijau, dan benar-benar terlihat.
 *
 * Dua cacat template yang HANYA muncul saat halaman dirender sungguhan —
 * keduanya ditemukan lewat getComputedStyle di Chromium, bukan dengan membaca
 * berkas:
 *
 *   - `.form-control:focus` memakai border #aec3ff (biru). Nilai itu tidak
 *     ada di peta recolor-modernize.mjs, jadi ia selamat dari pewarnaan dan
 *     membuat setiap kotak isian berkedip biru di panel hijau.
 *   - `:focus{outline:0;box-shadow:none!important}` yang berlaku global
 *     membunuh cincin fokus milik template sendiri — pengguna keyboard
 *     kehilangan satu-satunya penanda posisi (WCAG 2.4.7).
 *
 * Perbaikannya di admin.css. Kalau berkas itu ditata ulang dan aturannya
 * hilang, tidak ada error apa pun: fokus hanya diam-diam menjadi tak terlihat.
 */
{
  /*
   * Komentar CSS dibuang DULU, dan itu bukan kerapian belaka.
   *
   * Versi pertama pemeriksaan ini mencocokkan pola langsung ke berkas mentah
   * dan lulus karena mengenai PROSA di dalam komentar yang menjelaskan aturan
   * — bukan aturannya. Terbukti saat regresi "hapus !important" disuntikkan:
   * checker tetap hijau. Jebakan yang sama pernah terjadi di
   * check-datatables.php, waktu kata "withCount" di komentar terbaca sebagai
   * kode.
   */
  const adminCss = read('seekitar-server/public/css/admin.css')
    .replace(/\/\*[\s\S]*?\*\//g, '');

  const aturanFokus = adminCss.match(/\.form-control:focus[^{]*\{[^}]*\}/s)?.[0] ?? '';
  const adaFokusHijau = /box-shadow:[^;}]*!important/.test(aturanFokus);
  const adaBorderHijau = /border-color:\s*var\(--seekitar-green\)/.test(aturanFokus);

  if (!adaBorderHijau) {
    fail('.form-control:focus tidak dipaksa hijau — template memakai border biru #aec3ff yang lolos pewarnaan');
  } else if (!adaFokusHijau) {
    fail('cincin fokus tanpa !important — aturan global :focus{box-shadow:none!important} template akan menang dan fokus jadi tak terlihat (WCAG 2.4.7)');
  } else {
    ok('fokus isian hijau & terlihat (mengalahkan :focus{box-shadow:none!important} template)');
  }

  // Isian bermasalah harus TETAP merah saat difokus; kalau ikut hijau, penanda
  // galatnya hilang justru pada saat pengguna sedang membetulkannya.
  if (!/\.form-control\.is-invalid:focus/.test(adminCss)) {
    fail('.form-control.is-invalid:focus tidak diatur — isian bermasalah berubah hijau saat difokus');
  } else {
    ok('isian bermasalah tetap merah saat difokus');
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
