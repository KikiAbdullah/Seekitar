/*
 * Ganti ikon Tabler (`ti ti-*`) ke Font Awesome (`fa-solid fa-*`).
 *
 * Berjalan tanpa dependensi npm — cukup Node bawaan. Mengganti semua
 * kemunculan `ti ti-<nama>` di seluruh sumber seekitar-server (di luar
 * vendor/node_modules) dengan pasangan Font Awesome dari tabel pemetaan.
 *
 * Jalankan:
 *     node swap-tabler-to-fontawesome.mjs
 *
 * Pemetaan nama Tabler -> Font Awesome diambil per ikon yang benar-benar
 * dipakai (inventaris `ti ti-*`), dan nama FA diverifikasi ada di
 * public/vendor/fontawesome/css/all.min.css (Font Awesome Free 6.7.2).
 */
import { readdir, readFile, writeFile } from 'node:fs/promises';
import { dirname, extname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const root = resolve(here, '..', '..');
const server = join(root, 'seekitar-server');

const SKIP_DIR = new Set(['vendor', 'node_modules', '.git', 'storage', 'bootstrap']);
const TEXT_EXT = new Set(['.blade.php', '.php', '.js', '.mjs', '.cjs', '.json', '.css', '.svg', '.html', '.htm', '.md', '.txt']);

const MAP = {
  ad: 'rectangle-ad',
  'alert-circle': 'circle-exclamation',
  'alert-triangle': 'triangle-exclamation',
  'arrow-left': 'arrow-left',
  'arrow-right': 'arrow-right',
  'arrows-left-right': 'arrows-left-right',
  ban: 'ban',
  'bell-ringing': 'bell',
  box: 'box',
  broadcast: 'tower-broadcast',
  building: 'building',
  'building-store': 'store',
  cash: 'money-bill',
  check: 'check',
  'chevron-up': 'chevron-up',
  'circle-check': 'circle-check',
  'circle-check-filled': 'circle-check',
  'circle-x': 'circle-xmark',
  'clipboard-list': 'clipboard-list',
  clock: 'clock',
  'clock-hour-4': 'clock',
  cookie: 'cookie',
  crown: 'crown',
  'currency-dollar': 'dollar-sign',
  'current-location': 'location-crosshairs',
  'device-floppy': 'floppy-disk',
  'device-mobile': 'mobile-screen',
  'device-mobile-check': 'mobile-screen',
  'discount-2': 'tags',
  dots: 'ellipsis',
  download: 'download',
  eye: 'eye',
  'eye-off': 'eye-slash',
  flag: 'flag',
  gavel: 'gavel',
  'heart-filled': 'heart',
  history: 'clock-rotate-left',
  id: 'id-card',
  'info-circle': 'circle-info',
  key: 'key',
  'layout-dashboard': 'gauge-high',
  lock: 'lock',
  'lock-open': 'lock-open',
  mail: 'envelope',
  'map-2': 'map',
  'map-pin': 'location-dot',
  'map-pin-off': 'location-dot',
  'map-search': 'magnifying-glass-location',
  'menu-2': 'bars',
  news: 'newspaper',
  package: 'box-open',
  pencil: 'pen',
  phone: 'phone',
  'photo-off': 'image',
  plus: 'plus',
  'radar-2': 'satellite-dish',
  'receipt-refund': 'receipt',
  refresh: 'arrows-rotate',
  search: 'magnifying-glass',
  settings: 'gear',
  shield: 'shield-halved',
  'shield-check': 'square-check',
  'shield-lock': 'shield-halved',
  'shopping-bag': 'bag-shopping',
  'shopping-cart': 'cart-shopping',
  star: 'star',
  tag: 'tag',
  tools: 'screwdriver-wrench',
  trash: 'trash',
  'truck-delivery': 'truck-fast',
  user: 'user',
  'user-circle': 'circle-user',
  users: 'users',
  'user-x': 'user-xmark',
  x: 'xmark',
};

async function walk(dir, acc) {
  for (const entry of await readdir(dir, { withFileTypes: true })) {
    if (entry.isDirectory()) {
      if (!SKIP_DIR.has(entry.name)) await walk(join(dir, entry.name), acc);
    } else if (TEXT_EXT.has(extname(entry.name).toLowerCase())) {
      acc.push(join(dir, entry.name));
    }
  }
}

const files = [];
await walk(server, files);

const re = /\bti ti-([a-z0-9-]+)\b/g;
const total = new Map();
const unused = new Set(Object.keys(MAP));

for (const file of files) {
  const text = await readFile(file, 'utf8');
  let out = text;
  let m;
  re.lastIndex = 0;
  let changed = false;
  while ((m = re.exec(text))) {
    const tabler = m[1];
    const fa = MAP[tabler];
    if (!fa) {
      console.error('TANPA PEMETAAN:', tabler, '->', file);
      continue;
    }
    out = out.replace(m[0], `fa-solid fa-${fa}`);
    total.set(tabler, (total.get(tabler) || 0) + 1);
    unused.delete(tabler);
    changed = true;
    re.lastIndex = m.index + m[0].length;
  }
  if (changed) {
    await writeFile(file, out);
    console.log('  diganti:', file.replace(root + '\\', ''));
  }
}

console.log('\nRINGKASAN penggantian (ikon -> jumlah):');
for (const [name, count] of [...total.entries()].sort((a, b) => b[1] - a[1])) {
  console.log('  ', name.padEnd(22), count);
}

if (unused.size) {
  console.log('\nPemetaan tak terpakai (boleh dirapikan):', [...unused].sort().join(', '));
}
console.log('\nTotal ikon berbeda diganti:', total.size);
