// Alat kecil: terapkan pasangan berkas NN.old → NN.new di satu direktori
// ke sebuah dokumen. Anchor literal — gagal keras (exit 1) bila teks sumber
// sudah bergeser, supaya dokumen tidak sunyi-sunyi menyimpang dari kode.
// Pakai: node tools/dev/apply-swaps.mjs <dokumen.md> <dir-pasangan>
import fs from 'node:fs';
import path from 'node:path';

const [target, dir] = process.argv.slice(2);
if (!target || !dir) {
  console.error('pakai: node tools/dev/apply-swaps.mjs <dokumen.md> <dir-pasangan>');
  process.exit(2);
}

let t = fs.readFileSync(target, 'utf8');
const olds = fs.readdirSync(dir).filter(f => f.endsWith('.old')).sort();
if (olds.length === 0) {
  console.error(`tidak ada berkas .old di ${dir}`);
  process.exit(2);
}

for (const f of olds) {
  const lama = fs.readFileSync(path.join(dir, f), 'utf8').replace(/\n+$/, '');
  const baru = fs.readFileSync(path.join(dir, f.replace(/\.old$/, '.new')), 'utf8').replace(/\n+$/, '');
  if (!t.includes(lama)) {
    console.error(`GAGAL anchor: ${f}`);
    process.exit(1);
  }
  // Fungsi pengganti: teks PHP penuh '$…' — bentuk string mentah akan
  // salah menafsirkan pola $&, $', $$ dst.
  t = t.replace(lama, () => baru);
  console.log(`OK: ${f}`);
}

fs.writeFileSync(target, t);
console.log(`Selesai ✔ (${olds.length} blok)`);
