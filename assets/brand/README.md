# Aset Merek Seekitar

Sumber kebenaran aturannya ada di [`BRANDING-GUIDELINE.md`](../../BRANDING-GUIDELINE.md).
Folder ini menyimpan aset yang versinya dikendalikan bersama kode.

## Tersedia

| Berkas | Isi | Dirujuk di |
| :-- | :-- | :-- |
| `logo-grid-construction.svg` | Diagram konstruksi & grid logo | Brand Guideline §3.1, §3.2 |

## Belum tersedia

Daftar lengkap aset yang perlu dibuat desainer ada di **Brand Guideline §9.2**,
termasuk konvensi penamaan dan format ekspor.

## Aturan

- **Nama berkas:** huruf kecil, tanda hubung, tanpa tanggal/versi di nama.
  `logo-primary.svg` ✅ — `Logo Primary FINAL v2.svg` ❌
- **Format:** vektor (SVG) untuk logo, ikon, dan ilustrasi. PNG hanya untuk
  hal yang memang tidak bisa vektor (app icon, mockup, logo di email).
- **Berkas besar** (PSD, AI, mockup beresolusi tinggi) jangan di-commit
  langsung — pakai Git LFS atau catat tautan drive tim di berkas ini.
- **Diagram spesifikasi ≠ artwork final.** `logo-grid-construction.svg`
  menggambarkan proporsi secara skematis; artwork logo sesungguhnya dikerjakan
  desainer dan disimpan sebagai `logo-primary.ai`/`.svg`.

## Memeriksa hasil render SVG

Sandbox tidak punya renderer SVG bawaan. Untuk memeriksa hasil visualnya:

```bash
npm install sharp
node -e "require('sharp')('logo-grid-construction.svg',{density:120}).png().toFile('/tmp/preview.png')"
```

Perhatikan teks yang meluap: renderer memakai font pengganti bila
Plus Jakarta Sans tidak terpasang, dan lebarnya bisa berbeda dari perkiraan.
