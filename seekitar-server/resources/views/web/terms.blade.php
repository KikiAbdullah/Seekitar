@extends('web.layout')

@section('title', 'Syarat & Ketentuan')
@section('description', 'Ketentuan penggunaan layanan Seekitar.')

@section('content')
<div class="container py-5" style="max-width: 800px">
    <h1 class="h2 fw-bold mb-2">Syarat &amp; Ketentuan</h1>
    <p class="text-secondary mb-4">Berlaku sejak kamu membuat akun Seekitar.</p>

    <h2 class="h5 fw-bold mt-4">1. Peran Seekitar</h2>
    <p>
        Seekitar adalah <strong>platform penghubung</strong>. Kami
        mempertemukan pembeli dengan penjual atau penyedia jasa di sekitarnya,
        tetapi <strong>bukan pihak dalam transaksi</strong>. Kesepakatan harga,
        kualitas, dan penyerahan barang atau jasa adalah tanggung jawab kedua
        pihak yang bertransaksi.
    </p>

    <h2 class="h5 fw-bold mt-4">2. Akun</h2>
    <ul>
        <li>Satu nomor telepon untuk satu akun.</li>
        <li>Kamu bertanggung jawab atas semua aktivitas dari akunmu.</li>
        <li>Jangan bagikan kode OTP kepada siapa pun — termasuk kepada pihak yang mengaku dari Seekitar. <strong>Kami tidak pernah meminta kode OTP.</strong></li>
        <li>Membuka toko mensyaratkan verifikasi identitas (KTP).</li>
    </ul>

    <h2 class="h5 fw-bold mt-4">3. Pembayaran</h2>
    <p>
        Pembayaran dilakukan <strong>langsung antara pembeli dan penjual</strong>,
        secara tunai saat bertemu (COD) atau transfer bank. Seekitar tidak
        menampung dana dan tidak menyediakan rekening bersama.
    </p>
    <div class="alert alert-warning">
        Karena dana tidak melewati Seekitar, kami <strong>tidak dapat
        mengembalikan uang</strong> secara otomatis. Bila terjadi masalah,
        laporkan lewat fitur Laporkan Masalah agar kami dapat menengahi.
    </div>

    <h2 class="h5 fw-bold mt-4">4. Yang dilarang</h2>
    <ul>
        <li>Menjual barang atau jasa yang melanggar hukum Indonesia.</li>
        <li>Memasang permintaan atau penawaran palsu.</li>
        <li>Memakai identitas orang lain.</li>
        <li>Memanipulasi rating dengan ulasan palsu.</li>
        <li>Memakai data pengguna lain di luar keperluan transaksi.</li>
    </ul>
    <p>Pelanggaran dapat berujung pada pemblokiran akun tanpa pemberitahuan.</p>

    <h2 class="h5 fw-bold mt-4">5. Ulasan</h2>
    <p>
        Ulasan bersifat dua arah — pembeli menilai penjual, penjual menilai
        pembeli. Ulasan hanya bisa ditulis dalam <strong>7 hari</strong>
        setelah pesanan selesai, dan <strong>tidak dapat diubah</strong>
        setelah dikirim.
    </p>

    <h2 class="h5 fw-bold mt-4">6. Penyelesaian sengketa</h2>
    <p>
        Laporan sengketa ditanggapi dalam <strong>1×24 jam</strong>. Selama
        sengketa berjalan, status pesanan dibekukan. Keputusan admin
        didasarkan pada bukti yang diberikan kedua pihak.
    </p>

    <h2 class="h5 fw-bold mt-4">7. Perubahan ketentuan</h2>
    <p class="mb-0">
        Ketentuan ini dapat berubah. Perubahan yang bersifat material akan
        diberitahukan lewat aplikasi sebelum berlaku.
    </p>
</div>
@endsection
