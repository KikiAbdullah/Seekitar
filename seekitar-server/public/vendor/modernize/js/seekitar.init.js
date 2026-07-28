/*
 * Inisialisasi Modernize untuk Seekitar.
 *
 * MENGGANTIKAN app.init.js bawaan template.
 *
 * KENAPA TIDAK MEMAKAI app.init.js ASLI
 * -------------------------------------
 * Berkas itu menyetel `ThemeBg: "purple_theme"`, yang di template asli
 * memicu pemuatan berkas tema terpisah (style-purple.min.css). Seekitar
 * memakai SATU stylesheet yang sudah diwarnai hijau langsung di dalamnya
 * (lihat tools/dev/recolor-modernize.mjs), jadi pemilih tema tidak dipakai
 * sama sekali — menyisakannya hanya menambah kebingungan saat menelusuri
 * dari mana warna sebuah komponen berasal.
 *
 * Yang tetap dibutuhkan dari AdminSettings hanyalah ManageSidebarType:
 * mode mini-sidebar otomatis di bawah 1300px.
 */
$(function () {
  "use strict";

  $("#main-wrapper").AdminSettings({
    SidebarType: "full",       // penuh di layar lebar, otomatis mini < 1300px
    SidebarPosition: true,     // sidebar ikut tergulir bersama halaman
    HeaderPosition: true,      // header menempel di atas
  });
});
