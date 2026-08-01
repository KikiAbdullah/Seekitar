// Seekitar — pengganti app.init.js bawaan template Modernize.
//
// app.init.js bawaan menyetel `ThemeBg: "purple_theme"`, pemicu stylesheet
// tema terpisah. Seekitar memakai SATU stylesheet yang sudah diwarnai hijau
// (lihat tools/dev/recolor-modernize.mjs), jadi cukup memanggil plugin
// AdminSettings agar mode mini-sidebar responsif tetap bekerja.
$(function () {
  "use strict";
  $("#main-wrapper").AdminSettings({
    Theme: false,
    SidebarType: "full",
    SidebarPosition: false,
    HeaderPosition: true,
    BoxedLayout: false,
  });
});
