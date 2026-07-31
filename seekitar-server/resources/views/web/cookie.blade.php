@extends('web.layout')

@section('title', 'Kebijakan Cookie')
@section('description', 'Bagaimana Seekitar menggunakan cookie dan teknologi penyimpanan lokal di situs web.')

@section('content')

    @include('web.partials._hero', [
        'kicker'   => 'Legal · Cookie',
        'judul'    => 'Kebijakan Cookie',
        'subjudul' => 'Transparansi penggunaan cookie dan teknologi sejenis di situs Seekitar.',
    ])

    <section class="py-5">
        <div class="container" style="max-width: 860px;">

            <nav class="lp-toc mb-4" aria-label="Daftar isi">
                @foreach ([
                    'Apa itu cookie', 'Cookie yang dipakai', 'Cookie pihak ketiga',
                    'Kontrol cookie', 'Pembaruan',
                ] as $i => $bagian)
                    <a href="#cookie-{{ $i + 1 }}"><span class="lp-no">{{ $i + 1 }}</span> {{ $bagian }}</a>
                @endforeach
            </nav>

            <div class="lp-doc sr-reveal">
                <p style="color: var(--teks-secondary); font-size: 13px;">Terakhir diperbarui: 30 Juli 2026</p>

                <div class="alert alert-info">
                    <i class="fa-regular fa-file-lines me-1" aria-hidden="true"></i>
                    Dokumen ini melengkapi
                    <a href="{{ route('web.privacy') }}">Kebijakan Privasi</a> dan menjelaskan
                    secara khusus penggunaan cookie serta teknologi penyimpanan lokal.
                </div>

                <h2 id="cookie-1" class="h5 fw-bold mt-4">1. Apa itu cookie?</h2>
                <p>
                    Cookie adalah berkas teks kecil yang disimpan peramban di perangkatmu saat
                    mengunjungi situs web. Cookie memungkinkan situs mengingat preferensi dan
                    pengaturanmu untuk kunjungan berikutnya.
                </p>
                <p>
                    Selain cookie, kami juga memakai <em>localStorage</em> — teknologi
                    penyimpanan lokal yang mirip dengan cookie tetapi tidak dikirim otomatis
                    ke server pada setiap permintaan.
                </p>

                <h2 id="cookie-2" class="h5 fw-bold mt-4">2. Cookie yang kami pakai</h2>
                <div class="table-responsive">
                    <table class="table table-bordered" style="font-size: 14px;">
                        <thead class="table-light">
                            <tr>
                                <th>Jenis</th>
                                <th>Tujuan</th>
                                <th>Masa simpan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Sesi</strong></td>
                                <td>Menjaga sesi masukmu agar tetap terhubung.</td>
                                <td>Sampai peramban ditutup</td>
                            </tr>
                            <tr>
                                <td><strong>Preferensi</strong></td>
                                <td>Menyimpan status persetujuan cookie agar banner tidak muncul berulang.</td>
                                <td>1 tahun</td>
                            </tr>
                            <tr>
                                <td><strong>Keamanan</strong></td>
                                <td>Melindungi formulir dari serangan CSRF (fungsional).</td>
                                <td>Sampai peramban ditutup</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mb-0" style="color: var(--teks-secondary); font-size: 14px;">
                    Kami hanya memakai cookie fungsional. Tidak ada cookie iklan, pelacakan
                    lintas-situs, atau analitik perilaku individu.
                </p>

                <h2 id="cookie-3" class="h5 fw-bold mt-4">3. Cookie pihak ketiga</h2>
                <p>
                    Seekitar saat ini <strong>tidak memakai</strong> layanan pihak ketiga
                    yang menempatkan cookie di perangkatmu (seperti Google Analytics, iklan
                    tersegmentasi, atau pixel media sosial).
                </p>
                <p class="mb-0">
                    Bila di masa depan kami menambahkan layanan pihak ketiga yang memakai
                    cookie, halaman ini akan diperbarui dan kamu akan mendapat pemberitahuan.
                </p>

                <h2 id="cookie-4" class="h5 fw-bold mt-4">4. Cara mengontrol cookie</h2>
                <p>Kamu dapat mengelola atau menghapus cookie kapan saja lewat pengaturan peramban:</p>
                <ul>
                    <li><strong>Google Chrome:</strong> Pengaturan → Privasi dan keamanan → Cookie</li>
                    <li><strong>Mozilla Firefox:</strong> Pengaturan → Privasi & Keamanan → Cookie dan Data Situs</li>
                    <li><strong>Safari:</strong> Pengaturan → Privasi → Cookie</li>
                    <li><strong>Microsoft Edge:</strong> Pengaturan → Cookie dan izin situs</li>
                </ul>
                <p class="mb-0">
                    Penghapusan cookie tidak memengaruhi fungsi aplikasi Seekitar di ponsel,
                    karena aplikasi tidak bergantung pada cookie peramban.
                </p>

                <h2 id="cookie-5" class="h5 fw-bold mt-4">5. Pembaruan kebijakan cookie</h2>
                <p class="mb-0">
                    Kebijakan ini dapat berubah seiring penambahan fitur atau perubahan teknologi.
                    Tanggal berlaku terbaru tercantum di bagian atas dokumen. Perubahan yang
                    bersifat material akan diberitahukan lewat situs.
                </p>
            </div>
        </div>
    </section>

@endsection