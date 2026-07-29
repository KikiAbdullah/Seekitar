#!/usr/bin/env node
/**
 * Penjaga fitur PETA TOKO (Server_Implementation_Guide.md §9.2).
 *
 * KENAPA CHECKER TERPISAH
 * -----------------------
 * Peta punya kelas kegagalan yang tidak dimiliki halaman lain di panel, dan
 * semuanya GAGAL DIAM-DIAM:
 *
 *   - Wadah peta tanpa tinggi CSS → Leaflet menggambar ke elemen setinggi 0px.
 *     Tidak ada error; petanya sekadar "tidak muncul".
 *   - Atribusi OpenStreetMap dihapus → pelanggaran lisensi ubin, tanpa
 *     peringatan apa pun dari peramban.
 *   - Nama toko dirangkai ke HTML popup → XSS. Sudah dibuktikan nyata di
 *     Chromium: `<img src=x onerror=...>` benar-benar dieksekusi.
 *   - Kecamatan hilang dari Wilayah::KECAMATAN → wilayahnya tidak pernah
 *     muncul di peta, dan orang menyimpulkan "belum ada toko di sana".
 *
 * Jalankan:  node tools/dev/check-peta.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '..');
const read = f => fs.readFileSync(path.join(ROOT, '..', f), 'utf8');
const exists = f => fs.existsSync(path.join(ROOT, '..', f));

let problems = 0;
const fail = m => { console.log(`  ❌ ${m}`); problems++; };
const ok = m => console.log(`  ✅ ${m}`);

const VIEW = 'seekitar-server/resources/views/admin/maps/stores.blade.php';
const CTRL = 'seekitar-server/app/Http/Controllers/Admin/StoreMapController.php';
const SEED = 'seekitar-server/database/seeders/StoreMapSeeder.php';
const WIL  = 'seekitar-server/database/factories/Support/Wilayah.php';
const CSS  = 'seekitar-server/public/css/admin.css';
const LEAF = 'seekitar-server/public/vendor/leaflet';

// ───────────────────────────────────────────── 1. Kelengkapan kecamatan
console.log('Wilayah Kabupaten Pasuruan');

/*
 * Ke-24 kecamatan menurut kode Kemendagri 35.14.01–35.14.24.
 *
 * Daftar ini ditulis ulang di sini DENGAN SENGAJA: kalau ia dibaca dari
 * Wilayah.php, checker akan selalu lulus apa pun isinya — termasuk saat
 * sebuah kecamatan terhapus. Sumber kebenarannya harus di luar berkas yang
 * sedang diuji.
 *
 * Versi pertama Wilayah.php hanya memuat 19 nama, dan salah satunya
 * ("Bangil Kota") bukan kecamatan sama sekali.
 */
const RESMI = [
  'Bangil', 'Beji', 'Gempol', 'Gondangwetan', 'Grati', 'Kejayan',
  'Kraton', 'Lekok', 'Lumbang', 'Nguling', 'Pandaan', 'Pasrepan',
  'Pohjentrek', 'Prigen', 'Purwodadi', 'Purwosari', 'Puspo', 'Rejoso',
  'Rembang', 'Sukorejo', 'Tosari', 'Tutur', 'Winongan', 'Wonorejo',
];

/** Batas resmi kabupaten (BPK Jatim): 112°33'55"–113°30'37" BT, 7°32'34"–8°30'20" LS. */
const BATAS = {
  lng: [112 + 33 / 60 + 55 / 3600, 113 + 30 / 60 + 37 / 3600],
  lat: [-(8 + 30 / 60 + 20 / 3600), -(7 + 32 / 60 + 34 / 3600)],
};

if (!exists(WIL)) {
  fail(`${WIL} tidak ada`);
} else {
  const src = read(WIL);
  const i = src.indexOf('public const KECAMATAN');
  const blok = src.slice(i, src.indexOf('];', i));

  /*
   * KEDUA angka dicocokkan dengan `-?`, bukan hanya yang pertama.
   *
   * Versi pertama menulis `(-?[\d.]+)\s*,\s*([\d.]+)` — mengharuskan tanda
   * minus di angka pertama. Akibatnya pasangan yang TERTUKAR
   * (`[112.8203, -7.5966]`) tidak cocok sama sekali, kecamatannya hilang dari
   * hasil parse, dan pemeriksaan "urutan tertukar" di bawah tidak pernah
   * melihatnya. Regresi yang disuntikkan justru dilaporkan sebagai
   * "kecamatan hilang" — pesan yang menyesatkan.
   */
  const titik = [...blok.matchAll(/'([A-Za-z ]+)'\s*=>\s*\[\s*(-?[\d.]+)\s*,\s*(-?[\d.]+)/g)]
    .map(m => ({ nama: m[1], lat: parseFloat(m[2]), lng: parseFloat(m[3]) }));

  const nama = titik.map(t => t.nama);
  const hilang = RESMI.filter(k => !nama.includes(k));
  const karangan = nama.filter(k => !RESMI.includes(k));

  if (hilang.length) {
    fail(`kecamatan hilang dari Wilayah::KECAMATAN: ${hilang.join(', ')} — wilayahnya tidak akan pernah muncul di peta`);
  } else if (karangan.length) {
    fail(`nama bukan kecamatan resmi Kab. Pasuruan: ${karangan.join(', ')}`);
  } else {
    ok(`${titik.length} kecamatan lengkap & sesuai kode Kemendagri 35.14.*`);
  }

  const luar = titik.filter(t =>
    t.lat < BATAS.lat[0] || t.lat > BATAS.lat[1] ||
    t.lng < BATAS.lng[0] || t.lng > BATAS.lng[1]);

  if (luar.length) {
    fail(`titik di LUAR batas kabupaten: ${luar.map(t => `${t.nama} (${t.lat}, ${t.lng})`).join('; ')}`);
  } else {
    ok('semua titik kecamatan berada di dalam batas Kabupaten Pasuruan');
  }

  /*
   * Lintang Indonesia SELALU negatif (selatan khatulistiwa) dan bujurnya
   * selalu positif. Koordinat yang tertukar lolos semua pemeriksaan sintaks
   * dan hanya terlihat sebagai toko yang tiba-tiba berada di Samudra Hindia.
   */
  const tertukar = titik.filter(t => t.lat > 0 || t.lng < 0);
  if (tertukar.length) {
    fail(`urutan lat/lng tertukar: ${tertukar.map(t => t.nama).join(', ')}`);
  } else {
    ok('urutan [lat, lng] konsisten — tidak ada yang tertukar');
  }
}

// ───────────────────────────────────────────── 2. Aset Leaflet
console.log('\nAset Leaflet');

for (const f of ['leaflet.js', 'leaflet.css', 'images/marker-icon.png', 'LICENSE', 'SUMBER.md']) {
  if (!exists(`${LEAF}/${f}`)) fail(`aset Leaflet hilang: ${LEAF}/${f}`);
}
if (exists(`${LEAF}/leaflet.js`) && exists(`${LEAF}/leaflet.css`) && exists(`${LEAF}/LICENSE`)) {
  ok('Leaflet di-host sendiri, lengkap dengan lisensi & provenance');
}

if (exists(VIEW)) {
  const view = read(VIEW);

  // CDN untuk Leaflet ditolak: CSS-nya memuat images/ lewat jalur relatif,
  // jadi CDN yang berubah struktur membuat semua penanda hilang tanpa error.
  if (/cdn[^"']*leaflet/i.test(view) || /unpkg[^"']*leaflet/i.test(view)) {
    fail('Leaflet dimuat dari CDN — pakai aset lokal (lihat vendor/leaflet/SUMBER.md)');
  } else if (!view.includes("asset('vendor/leaflet/leaflet.js')")) {
    fail('view peta tidak memuat vendor/leaflet/leaflet.js');
  } else {
    ok('view peta memakai Leaflet lokal');
  }

  // CSS Leaflet WAJIB: tanpa itu ubin peta bertumpuk kacau di pojok kiri atas.
  if (!view.includes("asset('vendor/leaflet/leaflet.css')")) {
    fail('leaflet.css tidak dimuat — ubin peta akan bertumpuk kacau');
  } else {
    ok('leaflet.css dimuat');
  }
}

// ───────────────────────────────────────────── 3. Lisensi ubin OSM
console.log('\nKepatuhan lisensi ubin');

if (exists(VIEW)) {
  const view = read(VIEW);
  const pakaiOsm = /tile\.openstreetmap\.org/.test(view);
  const adaAtribusi = /attribution\s*:/.test(view) && /OpenStreetMap/.test(view);

  if (pakaiOsm && !adaAtribusi) {
    fail('memakai ubin OpenStreetMap tanpa atribusi — melanggar Tile Usage Policy OSM');
  } else if (pakaiOsm) {
    ok('atribusi © OpenStreetMap contributors terpasang');
  } else {
    ok('tidak memakai ubin OSM (atribusi tidak wajib)');
  }
}

// ───────────────────────────────────────────── 4. Tinggi wadah peta
console.log('\nTata letak peta');

if (exists(CSS)) {
  const css = read(CSS).replace(/\/\*[\s\S]*?\*\//g, '');
  const aturan = css.match(/\.admin-peta\s*\{[^}]*\}/s)?.[0] ?? '';

  /*
   * Leaflet menggambar ke dalam div berposisi absolut, jadi wadahnya HARUS
   * punya tinggi eksplisit. Tanpa itu tingginya nol dan petanya tidak terlihat
   * sama sekali — tanpa satu pun pesan error di konsol.
   */
  if (!/height\s*:/.test(aturan)) {
    fail('.admin-peta tanpa tinggi CSS — Leaflet menggambar ke elemen setinggi 0px (peta tidak muncul)');
  } else {
    ok('wadah peta punya tinggi eksplisit');
  }
}

// ───────────────────────────────────────────── 5. Popup aman dari XSS
console.log('\nKeamanan popup');

if (exists(VIEW)) {
  const view = read(VIEW);

  /*
   * Nama & alamat toko diisi PEMILIK TOKO — data tak tepercaya.
   *
   * Merangkainya ke string HTML lalu memberikannya ke bindPopup() membuatnya
   * dieksekusi sebagai markup. Diverifikasi nyata di Chromium: payload
   * `<img src=x onerror=...>` benar-benar berjalan lewat pola itu, dan tidak
   * berjalan setelah diganti textContent.
   *
   * Leaflet TIDAK punya pelolos HTML bawaan — `L.Util.escapeHtml` tidak ada
   * (sudah dicari di leaflet.js: 0 kemunculan).
   */
  const skrip = view.slice(view.indexOf('<script'));
  const rangkaiHtml = /bindPopup\(\s*[`'"]/.test(skrip)
    || /bindPopup\([^)]*\$\{/.test(skrip)
    || /\.innerHTML\s*=/.test(skrip);

  if (rangkaiHtml) {
    fail('popup peta merangkai HTML dari data toko — XSS (terbukti dieksekusi di Chromium)');
  } else if (!/textContent/.test(skrip)) {
    fail('popup peta tidak memakai textContent — pastikan data toko tidak masuk sebagai HTML');
  } else {
    ok('popup memakai textContent — nama/alamat toko tidak bisa menyuntik HTML');
  }
}

// ───────────────────────────────────────────── 6. Endpoint & izin
console.log('\nController & data');

if (exists(CTRL)) {
  const ctrl = read(CTRL);

  /*
   * 2.3: koordinat toko adalah kolom DECIMAL(11,8)/(12,8) NOT NULL, bukan
   * POINT lagi — tidak ada WKB biner dan tidak ada baris tanpa titik
   * (engine menolak NULL). GeoJSON wajib dibangun dari keduanya lewat
   * accessor coordinates(); pola lama (withCoordinates + whereNotNull)
   * tetap diterima sebagai padanan historis.
   */
  const geojsonBaru = /'latitude'/.test(ctrl) && /coordinates\(\)/.test(ctrl);
  if (!geojsonBaru && !/withCoordinates\(\)/.test(ctrl)) {
    fail('controller peta tidak membangun GeoJSON dari kolom latitude/longitude');
  } else {
    ok('GeoJSON dibangun dari kolom latitude/longitude (bukan WKB biner)');
  }

  // Null yang lolos ke Leaflet melempar "Invalid LatLng" dan mematikan
  // SELURUH peta, bukan satu titik — kolom NOT NULL atau filter lama
  // adalah dua cara sah mencegahnya.
  if (!/coordinates\(\)/.test(ctrl) && !/whereNotNull\(\s*'location'\s*\)/.test(ctrl)) {
    fail("titik tanpa koordinat bisa lolos — satu null mematikan seluruh peta");
  } else {
    ok('titik peta terjamin punya koordinat (kolom NOT NULL)');
  }
}

const routes = exists('seekitar-server/routes/admin.php')
  ? read('seekitar-server/routes/admin.php') : '';

if (routes) {
  const blokPeta = routes.slice(routes.indexOf('maps/stores') - 400, routes.indexOf('maps/stores') + 300);

  if (!/permission:manage-stores/.test(blokPeta)) {
    fail('route peta tidak dijaga permission:manage-stores — siapa pun bisa menarik koordinat semua toko');
  } else {
    ok('route peta & endpoint GeoJSON dijaga manage-stores');
  }
}

// ───────────────────────────────────────────── 7. Seeder 50 toko
console.log('\nSeeder 50 toko');

if (!exists(SEED)) {
  fail(`${SEED} tidak ada`);
} else {
  const seed = read(SEED);
  const jumlah = parseInt(seed.match(/JUMLAH\s*=\s*(\d+)/)?.[1] ?? '0', 10);

  if (jumlah !== 50) {
    fail(`StoreMapSeeder::JUMLAH = ${jumlah}, seharusnya 50`);
  } else {
    ok('seeder menghasilkan 50 toko');
  }

  // Tiap kecamatan WAJIB kebagian minimal satu toko; kalau tidak, peta bolong
  // dan orang menyimpulkan wilayah itu belum terlayani.
  if (jumlah < RESMI.length) {
    fail(`jumlah toko (${jumlah}) < jumlah kecamatan (${RESMI.length}) — sebagian wilayah pasti kosong`);
  } else {
    ok(`${jumlah} toko cukup untuk menutup ${RESMI.length} kecamatan`);
  }

  // Deterministik: tanpa seed tetap, tangkapan layar peta tidak bisa
  // dibandingkan antar-hari saat menelusuri regresi tampilan.
  if (!/mt_srand\(/.test(seed)) {
    fail('seeder tidak deterministik (tanpa mt_srand) — titik berubah setiap kali di-seed');
  } else {
    ok('seeder deterministik — titik sama setiap kali dijalankan');
  }

  // Penjagaan environment: seeder ini membuat data contoh.
  if (!/environment\('local', 'testing'\)/.test(seed)) {
    fail('StoreMapSeeder tanpa penjagaan environment — data contoh bisa masuk produksi');
  } else {
    ok('seeder dijaga agar hanya jalan di local/testing');
  }

  // Pergeseran titik wajib: 50 toko di koordinat kantor kecamatan akan
  // bertumpuk jadi satu penanda dan kabupaten tampak nyaris kosong.
  if (!/mt_rand\(-\d+,\s*\d+\)\s*\/\s*10000/.test(seed)) {
    fail('titik toko tidak digeser dari pusat kecamatan — penanda akan bertumpuk');
  } else {
    ok('titik toko digeser acak — tidak bertumpuk di satu koordinat');
  }

  /*
   * Geseran acak HARUS dijepit ke batas kabupaten.
   *
   * Bukan kehati-hatian teoretis: Gempol berpusat di lintang -7,5497
   * sementara batas utara kabupaten -7,5428 — selisihnya cuma 0,0069°.
   * Geseran +0,010° melempar toko 343 m ke luar wilayah, dan di peta ia
   * mengambang di Kabupaten Sidoarjo. Ditemukan dengan menjalankan seeder
   * terhadap Wilayah::KECAMATAN sungguhan lewat refleksi.
   */
  if (!/private function jepit\(/.test(seed) || !/BATAS_LAT/.test(seed)) {
    fail('geseran titik tidak dijepit ke batas kabupaten — toko di kecamatan tepi (mis. Gempol) bisa jatuh di luar wilayah');
  } else {
    ok('geseran titik dijepit ke batas kabupaten');
  }
}

console.log(problems === 0
  ? '\n✅ Peta toko konsisten: wilayah, aset, lisensi, keamanan, dan data.'
  : `\n❌ ${problems} masalah ditemukan.`);
if (problems) process.exitCode = 1;
