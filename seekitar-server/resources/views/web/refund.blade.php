@extends('web.layout')

@section('title', 'Kebijakan Pengembalian & Sengketa')
@section('description', 'Prosedur pengembalian dana dan penyelesaian sengketa transaksi di Seekitar.')

@section('content')

    @include('web.partials._hero', [
        'kicker'   => 'Legal',
        'judul'    => 'Kebijakan Pengembalian & Sengketa',
        'subjudul' => 'Mekanisme penyelesaian masalah transaksi antara pembeli dan penjual.',
    ])

    <section class="py-5">
        <div class="container" style="max-width: 860px;">

            <nav class="lp-toc mb-4" aria-label="Daftar isi">
                @foreach ([
                    'Posisi Seekitar', 'Jenis sengketa', 'Cara melapor',
                    'Proses mediasi', 'Keputusan', 'Pembatasan',
                ] as $i => $bagian)
                    <a href="#refund-{{ $i + 1 }}"><span class="lp-no">{{ $i + 1 }}</span> {{ $bagian }}</a>
                @endforeach
            </nav>

            <div class="lp-doc sr-reveal">
                <p style="color: var(--teks-secondary); font-size: 13px;">Terakhir diperbarui: 30 Juli 2026</p>

                <div class="alert alert-warning">
                    <i class="fa-regular fa-circle-xmark me-1" aria-hidden="true"></i>
                    Karena pembayaran dilakukan <strong>langsung</strong> antara pembeli
                    dan penjual (COD/transfer), Seekitar tidak menampung dana dan tidak
                    dapat mengembalikan uang secara otomatis.
                </div>

                <h2 id="refund-1" class="h5 fw-bold mt-4">1. Posisi Seekitar</h2>
                <p>
                    Seekitar adalah <strong>platform penghubung</strong>, bukan pihak dalam
                    transaksi. Pembayaran dilakukan langsung antara pembeli dan penjual —
                    tunai saat bertemu (COD) atau transfer bank.
                </p>
                <p>
                    Karena dana tidak melewati sistem Seekitar, kami tidak dapat membatalkan
                transaksi atau mengembalikan dana secara sepihak. Namun kami bertindak
                    sebagai <strong>mediator</strong> ketika terjadi sengketa.
                </p>

                <h2 id="refund-2" class="h5 fw-bold mt-4">2. Jenis sengketa yang dapat dilaporkan</h2>
                <ul>
                    <li>Barang tidak sesuai deskripsi (ukuran, warna, merek berbeda).</li>
                    <li>Barang rusak atau cacat saat diterima.</li>
                    <li>Pesanan tidak dikirim setelah pembayaran.</li>
                    <li>Jumlah barang kurang dari yang diperjanjikan.</li>
                    <li>Penjual tidak dapat dihubungi setelah pembayaran.</li>
                    <li>Pembeli tidak hadir pada lokasi dan waktu yang disepakati.</li>
                </ul>

                <h2 id="refund-3" class="h5 fw-bold mt-4">3. Cara melaporkan sengketa</h2>
                <ol>
                    <li>Buka halaman <strong>Pesanan</strong> di aplikasi.</li>
                    <li>Pilih pesanan yang bermasalah.</li>
                    <li>Klik <strong>Laporkan Masalah</strong>.</li>
                    <li>Jelaskan masalah dan lampirkan bukti (foto, tangkapan layar).</li>
                </ol>
                <p>
                    <strong>Penting:</strong> Laporan lewat aplikasi otomatis membekukan
                    status pesanan, sehingga tidak bisa diubah statusnya sampai admin
                    memutuskan. Laporan lewat email <strong>tidak</strong> membekukan pesanan.
                </p>
                <p>
                    Jika tidak bisa lewat aplikasi, kirim ke
                    <a href="mailto:{{ config('seekitar.contacts.complaint') }}">{{ config('seekitar.contacts.complaint') }}</a>
                    dengan menyertakan nomor pesanan.
                </p>

                <h2 id="refund-4" class="h5 fw-bold mt-4">4. Proses mediasi</h2>
                <ol>
                    <li><strong>Pelaporan</strong> — Laporan masuk, pesanan dibekukan. (≤ 1×24 jam)</li>
                    <li><strong>Tanggapan</strong> — Pihak lawan diberi kesempatan menanggapi dalam 2×24 jam.</li>
                    <li><strong>Pemeriksaan</strong> — Admin memeriksa bukti dari kedua pihak.</li>
                    <li><strong>Keputusan</strong> — Admin memutuskan berdasarkan bukti yang ada.</li>
                </ol>
                <p>
                    Selama proses mediasi, kedua pihak tetap dapat berkomunikasi untuk
                    mencapai kesepakatan di luar mediasi.
                </p>

                <h2 id="refund-5" class="h5 fw-bold mt-4">5. Keputusan mediasi</h2>
                <p>Keputusan yang dapat diambil admin:</p>
                <ul>
                    <li><strong>Pesanan dilanjutkan</strong> — jika barang/jasa sesuai dan tidak ditemukan pelanggaran.</li>
                    <li><strong>Pesanan dibatalkan</strong> — jika terbukti ada pelanggaran dari salah satu pihak.</li>
                    <li><strong>Pembatasan akun</strong> — sanksi bagi pihak yang terbukti melanggar.</li>
                </ul>
                <p class="mb-0" style="color: var(--teks-secondary); font-size: 14px;">
                    Keputusan admin bersifat final dan mengikat. Kedua pihak mendapat
                    pemberitahuan lewat aplikasi dan email.
                </p>

                <h2 id="refund-6" class="h5 fw-bold mt-4">6. Pembatasan tanggung jawab</h2>
                <p>
                    Seekitar tidak bertanggung jawab atas kerugian yang timbul di luar
                    transaksi yang tercatat di sistem kami. Kami juga tidak bertanggung
                    jawab atas kesepakatan di luar platform antara pembeli dan penjual.
                </p>
                <p class="mb-0">
                    Untuk sengketa yang melibatkan jumlah besar atau pelanggaran pidana,
                    kami menyarankan kedua pihak menempuh jalur hukum sesuai peraturan
                    perundang-undangan yang berlaku.
                </p>
            </div>
        </div>
    </section>

@endsection