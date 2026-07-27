#!/usr/bin/env node
/**
 * Penjaga konsistensi STRUKTUR PROYEK di dokumentasi.
 *
 * Memastikan folder/berkas kunci tetap tercantum di §4 Server Guide dan
 * §3 Mobile Guide, dan nilai Enum tetap cocok dengan skema di DATABASE.md.
 *
 * Jalankan:  node tools/dev/check-structure.mjs
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const read = f => fs.readFileSync(path.join(ROOT, f), 'utf8');

/** Ambil satu bab berdasarkan heading `## <n>. ` sampai heading berikutnya. */
function section(text, startRe, endRe) {
  const lines = text.split('\n');
  const start = lines.findIndex(l => startRe.test(l));
  if (start === -1) return '';
  const rest = lines.slice(start + 1);
  const end = rest.findIndex(l => endRe.test(l));
  return (end === -1 ? rest : rest.slice(0, end)).join('\n');
}

/**
 * Ambil HANYA blok pohon direktori dari sebuah bab.
 *
 * Penting: tanpa ini, checker ikut mencocokkan prosa seperti
 * "Kenapa `app/Services/` Diperlukan" sehingga folder yang benar-benar
 * dihapus dari pohon tetap dianggap ada (false negative).
 */
function directoryTrees(sectionText) {
  const blocks = [...sectionText.matchAll(/```\n([\s\S]*?)```/g)].map(m => m[1]);
  return blocks
    .filter(b => /[├└]──/.test(b)) // hanya blok yang benar-benar pohon direktori
    .join('\n');
}

let problems = 0;
const fail = (msg) => { console.log(`  ❌ ${msg}`); problems++; };
const ok = (msg) => console.log(`  ✅ ${msg}`);

// ---------------------------------------------------------------- Server §4
console.log('Server_Implementation_Guide.md §4 — Struktur Proyek');
const sig = read('Server_Implementation_Guide.md');
const sig4 = directoryTrees(section(sig, /^## 4\. STRUKTUR PROYEK/, /^## 5\./));

const SERVER_REQUIRED = [
  ['app/Services/', ['Services/', 'BroadcastService', 'GeolocationService', 'NotificationService']],
  ['app/Enums/', ['Enums/', 'OrderStatus.php', 'RequestStatus.php', 'OfferStatus.php', 'StoreType.php', 'VerificationLevel.php']],
  ['app/DataTables/', ['DataTables/']],
  ['database/seeders/', ['seeders/', 'CategorySeeder.php', 'RolesAndPermissionsSeeder.php']],
  ['app/Jobs/', ['Jobs/', 'BroadcastRequestJob.php']],
  ['app/Listeners/', ['Listeners/', 'SendOfferAcceptedNotification.php']],
];

for (const [label, needles] of SERVER_REQUIRED) {
  const missing = needles.filter(n => !sig4.includes(n));
  if (missing.length) fail(`${label} — hilang: ${missing.join(', ')}`);
  else ok(label);
}

// ---------------------------------------------------------------- Mobile §3
console.log('\nMobile_Implementation_Guide.md §3 — Struktur Proyek');
const mig = read('Mobile_Implementation_Guide.md');
const mig3raw = section(mig, /^## 3\. STRUKTUR PROYEK/, /^## 4\. STATE MANAGEMENT/);
const mig3 = directoryTrees(mig3raw);

const MOBILE_REQUIRED = [
  ['core/services/', ['services/', 'location_service.dart', 'notification_service.dart', 'analytics_service.dart']],
  ['presentation/providers/', ['providers/', 'auth_provider.dart']],
  ['route_constants.dart', ['route_constants.dart']],

];

for (const [label, needles] of MOBILE_REQUIRED) {
  const missing = needles.filter(n => !mig3.includes(n));
  if (missing.length) fail(`${label} — hilang: ${missing.join(', ')}`);
  else ok(label);
}

// Penjelasan tiga lokasi widget ada di prosa (bukan pohon direktori).
const WIDGET_DOC = ['core/widgets/', 'presentation/widgets/', 'Tiga Lokasi Widget'];
const missingWidgetDoc = WIDGET_DOC.filter(n => !mig3raw.includes(n));
if (missingWidgetDoc.length) fail(`penjelasan 3 lokasi widget — hilang: ${missingWidgetDoc.join(', ')}`);
else ok('penjelasan 3 lokasi widget');

// ------------------------------------------------- Enum vs skema DATABASE.md
console.log('\nEnum harus cocok dengan ENUM di DATABASE.md');
const db = read('DATABASE.md');

const ENUMS = {
  'orders.status': ['menunggu_konfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan', 'dispute'],
  'customer_requests.status': ['open', 'closed', 'expired'],
  'offers.status': ['pending', 'accepted', 'rejected'],
  'listings.status': ['active', 'sold', 'hidden'],
  'listings.listing_type': ['product', 'service', 'rental'],
  'stores.store_type': ['goods', 'services', 'rental'],
  'orders.payment_method': ['cod', 'transfer'],
  'disputes.status': ['open', 'resolved'],
};

for (const [col, values] of Object.entries(ENUMS)) {
  const notInDb = values.filter(v => !db.includes(`'${v}'`));
  const notInSig = values.filter(v => !sig.includes(`'${v}'`) && !sig.includes(`\`${v}\``));
  if (notInDb.length) fail(`${col} — nilai tidak ada di DATABASE.md: ${notInDb.join(', ')}`);
  else if (notInSig.length) fail(`${col} — belum tercermin di Server Guide: ${notInSig.join(', ')}`);
  else ok(col);
}

// Jebakan klasik: store_type jamak vs listing_type/order_type tunggal.
// Dicek berdasarkan MAKNA, bukan kalimat persis, supaya penulisan ulang
// tidak dianggap regresi selama peringatannya tetap ada.
const warnsPlural =
  /StoreType.*(jamak|plural)/s.test(sig) &&
  /(ListingType|OrderType).*(tunggal|singular)/s.test(sig);
if (!warnsPlural) {
  fail('Peringatan beda bentuk jamak/tunggal (StoreType vs ListingType/OrderType) hilang dari Server Guide');
} else {
  ok('catatan jamak vs tunggal masih ada');
}

console.log(
  problems === 0
    ? '\n✅ Struktur proyek konsisten.'
    : `\n❌ ${problems} masalah struktur ditemukan.`
);
if (problems) process.exitCode = 1;
