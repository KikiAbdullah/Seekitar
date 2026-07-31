@extends('web.layout')

@section('title', 'Pedoman Komunitas')
@section('description', 'Pedoman perilaku pengguna Seekitar — konten yang dilarang, sanksi, dan cara melaporkan pelanggaran.')

@section('content')

    @include('web.partials._hero', [
        'kicker'   => 'Pedoman',
        'judul'    => 'Pedoman Komunitas',
        'subjudul' => 'Aturan main agar Seekitar tetap aman dan nyaman untuk semua warga.',
    ])

    <section class="py-5">
        <div class="container" style="max-width: 860px;">

            <nav class="lp-toc mb-4" aria-label="Daftar isi">
                @foreach ([
                    'Prinsip dasar', 'Konten yang dilarang', 'Perilaku yang dilarang',
                    'Pelaporan', 'Sanksi', 'Banding',
                ] as $i => $bagian)
                    <a href="#pedoman-{{ $i + 1 }}"><span class="lp-no">{{ $i + 1 }}</span> {{ $bagian }}</a>
                @endforeach
            </nav>

            <div class="lp-doc sr-reveal">
                <p style="color: var(--teks-secondary); font-size: 13px;">Terakhir diperbarui: 30 Juli 2026</p>

                <div class="alert alert-warning">
                    <i class="ti ti-users me-1" aria-hidden="true"></i>
                    Pedoman ini berlaku untuk semua pengguna Seekitar. Pelanggaran dapat
                    berakibat pada peringatan, pembatasan fitur, atau pemblokiran akun.
                </div>

                <h2 id="pedoman-1" class="h5 fw-bold mt-4">1. Prinsip dasar</h2>
                <p>Setiap pengguna Seekitar diharapkan:</p>
                <ul>
                    <li>Bersikap jujur dalam setiap transaksi — baik sebagai pembeli maupun penjual.</li>
                    <li>Menghormati privasi dan data pengguna lain.</li>
                    <li>Mematuhi seluruh hukum yang berlaku di Republik Indonesia.</li>
                    <li>Melaporkan pelanggaran yang ditemukan melalui kanal yang tersedia.</li>
                </ul>

                <h2 id="pedoman-2" class="h5 fw-bold mt-4">2. Konten yang dilarang</h2>
                <p>Dilarang memasang listing, permintaan, atau konten lain yang:</p>
                <ul>
                    <li><strong>Melanggar hukum</strong> — narkotika, senjata api, obat ilegal, uang palsu, dan barang ilegal lainnya.</li>
                    <li><strong>Bersifat eksplisit</strong> — pornografi, konten seksual, atau kekerasan grafis.</li>
                    <li><strong>Menyesatkan</strong> — barang atau jasa palsu, deskripsi tidak sesuai kenyataan, harga fiktif.</li>
                    <li><strong>Melanggar kekayaan intelektual</strong> — barang bajakan, tiruan merek terkenal tanpa izin.</li>
                    <li><strong>Berbahaya</strong> — hewan langka/terlindungi, limbah beracun, bahan kimia berbahaya.</li>
                    <li><strong>Dilarang PPMSE</strong> — barang/jasa yang dilarang oleh Permendag PPMSE.</li>
                </ul>

                <h2 id="pedoman-3" class="h5 fw-bold mt-4">3. Perilaku yang dilarang</h2>
                <ul>
                    <li>Mengirim pesan spam atau promosi tidak diminta (termasuk tautan afiliasi).</li>
                    <li>Memanipulasi rating dengan membuat ulasan palsu atau memberi rating negatif tanpa dasar.</li>
                    <li>Memakai identitas orang lain atau membuat akun palsu.</li>
                    <li>Menghubungi pengguna lain di luar konteks transaksi tanpa izin.</li>
                    <li>Menawarkan transaksi di luar platform untuk menghindari mekanisme sengketa.</li>
                </ul>

                <h2 id="pedoman-4" class="h5 fw-bold mt-4">4. Cara melaporkan pelanggaran</h2>
                <p>Kamu bisa melaporkan pelanggaran melalui:</p>
                <ul>
                    <li><strong>Laporkan Masalah</strong> di halaman pesanan — untuk sengketa transaksi, ditanggapi ≤ 1×24 jam.</li>
                    <li><strong>Email konten ilegal</strong> — <a href="mailto:{{ config('seekitar.contacts.abuse') }}">{{ config('seekitar.contacts.abuse') }}</a>, ditanggapi ≤ 1×24 jam.</li>
                    <li><strong>Email pengaduan umum</strong> — <a href="mailto:{{ config('seekitar.contacts.complaint') }}">{{ config('seekitar.contacts.complaint') }}</a>, ditanggapi ≤ 2×24 jam.</li>
                </ul>
                <p class="mb-0">
                    Sertakan bukti pendukung (tangkapan layar, nomor pesanan) untuk
                    mempercepat penanganan.
                </p>

                <h2 id="pedoman-5" class="h5 fw-bold mt-4">5. Sanksi</h2>
                <p>Tingkat pelanggaran dan sanksi yang dapat dikenakan:</p>
                <div class="table-responsive">
                    <table class="table table-bordered" style="font-size: 14px;">
                        <thead class="table-light">
                            <tr>
                                <th>Pelanggaran</th>
                                <th>Contoh</th>
                                <th>Sanksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Ringan</td>
                                <td>Deskripsi barang tidak rapi, foto buram</td>
                                <td>Peringatan + perbaikan 2×24 jam</td>
                            </tr>
                            <tr>
                                <td>Sedang</td>
                                <td>Rating manipulatif, spam</td>
                                <td>Pembatasan fitur 7 hari</td>
                            </tr>
                            <tr>
                                <td>Berat</td>
                                <td>Konten ilegal, penipuan, identitas palsu</td>
                                <td>Pemblokiran akun permanen</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h2 id="pedoman-6" class="h5 fw-bold mt-4">6. Banding</h2>
                <p class="mb-0">
                    Jika kamu merasa sanksi dikenakan secara keliru, ajukan banding dalam
                    7 hari ke
                    <a href="mailto:{{ config('seekitar.contacts.complaint') }}">{{ config('seekitar.contacts.complaint') }}</a>.
                    Sertakan argumen dan bukti pendukung. Banding ditanggapi dalam 3×24 jam.
                </p>
            </div>
        </div>
    </section>

@endsection