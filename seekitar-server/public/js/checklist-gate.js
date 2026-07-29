/*
 * Gerbang checklist SOP pada antrian verifikasi (pengguna & toko berbagi
 * pola yang sama): tombol Setuju terkunci sampai SELURUH konfirmasi
 * pemeriksaan dicentang — menyetujui berkas tanpa memeriksa tidak boleh
 * terjadi "tanpa sengaja".
 *
 * Satu listener delegasi di document, bukan handler per baris: modal
 * dirender satu per antrian, dan jumlahnya bisa dua digit per halaman.
 */
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('change', function (event) {
        const checklist = event.target.closest('[data-checklist]');
        if (!checklist) return;

        const modal  = checklist.closest('.modal');
        const tombol = modal ? modal.querySelector('[data-tombol-verifikasi]') : null;
        if (!tombol) return;

        const centang = checklist.querySelectorAll('input[type="checkbox"]');
        let lengkap = true;
        centang.forEach(function (cb) { if (!cb.checked) lengkap = false; });

        tombol.disabled = !lengkap;
        tombol.title = lengkap ? '' : 'Centang seluruh konfirmasi pemeriksaan dulu';
    });
});
