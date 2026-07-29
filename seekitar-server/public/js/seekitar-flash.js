/*
 * Satu wajah umpan balik panel admin — semuanya lewat SweetAlert2:
 *
 *   1. FLASH halaman: layout menitipkan window.seekitarFlash =
 *      { sukses, gagal, validasi[] }, berkas ini yang merendernya.
 *      Sukses = toast ringan yang hilang sendiri; gagal/validasi = modal
 *      yang menghentikan perhatian — dua perilaku itu memang butuh dua
 *      komponen berbeda, bukan satu <div class="alert"> statis.
 *   2. KONFIRMASI tindakan berisiko: tombol submit bertanda
 *      data-seekitar-confirm="pesan" — pengganti confirm() bawaan yang
 *      kasar dan tidak berbahasa brand. Warna tombol ikut gaya tombolnya:
 *      btn-danger merah, sisanya hijau Seekitar.
 *
 * Semua pesan dari server diperlakukan sebagai TEKS, bukan HTML — pesan
 * validasi bisa memuat input pengguna, dan innerHTML mentah adalah XSS.
 */
(function () {
    'use strict';

    function jadiTeksPolos(pesan) {
        const wadah = document.createElement('div');
        wadah.textContent = String(pesan);
        return wadah.innerHTML;
    }

    // ── 1. Flash server ───────────────────────────────────────────────
    const data = window.seekitarFlash || {};

    const toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
    });

    if (data.sukses) {
        toast.fire({ icon: 'success', titleText: data.sukses });
    }

    if (data.gagal) {
        Swal.fire({
            icon: 'error',
            titleText: 'Gagal',
            text: data.gagal,
            confirmButtonText: 'Mengerti',
            confirmButtonColor: '#DC3545',
        });
    }

    if (Array.isArray(data.validasi) && data.validasi.length > 0) {
        const daftar = '<ul style="text-align:left;margin:0;padding-left:1.25rem">'
            + data.validasi.map(jadiTeksPolos).map(function (m) { return '<li>' + m + '</li>'; }).join('')
            + '</ul>';

        Swal.fire({
            icon: 'warning',
            titleText: 'Periksa kembali isian',
            html: daftar,
            confirmButtonText: 'Perbaiki',
            confirmButtonColor: '#168A4A',
        });
    }

    // ── 2. Konfirmasi tindakan berisiko ───────────────────────────────
    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        // Penanda boleh di tombol yang diklik maupun di form-nya — dua-dua
        // pola dipakai di panel (aksi baris vs aksi halaman detail).
        const pemicu = form.querySelector('[data-seekitar-confirm]');
        if (!pemicu || form.dataset.swalDisetujui === '1') return;

        event.preventDefault();

        const berbahaya = pemicu.classList.contains('btn-danger')
            || pemicu.classList.contains('btn-outline-danger');

        Swal.fire({
            icon: 'warning',
            titleText: 'Yakin?',
            text: pemicu.getAttribute('data-seekitar-confirm'),
            showCancelButton: true,
            confirmButtonText: 'Ya, lanjutkan',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            confirmButtonColor: berbahaya ? '#DC3545' : '#168A4A',
        }).then(function (hasil) {
            if (!hasil.isConfirmed) return;
            form.dataset.swalDisetujui = '1';
            // requestSubmit (bukan submit()): validasi HTML5 tetap berjalan
            // dan event submit terpancar ulang dengan penanda lolos terpasang.
            form.requestSubmit();
        });
    }, true);
})();
