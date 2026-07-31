@extends('web.layout')

@section('title', 'Biaya & Harga')
@section('description', 'Rincian biaya penggunaan Seekitar — gratis untuk pembeli dan penjual.')

@section('content')

    @include('web.partials._hero', [
        'kicker'   => 'Informasi Biaya',
        'judul'    => 'Biaya & Harga',
        'subjudul' => 'Transparansi biaya penggunaan platform Seekitar sesuai PPMSE.',
    ])

    <section class="py-5">
        <div class="container" style="max-width: 860px;">

            <div class="lp-doc sr-reveal">
                <div class="alert alert-success">
                    <i class="ti ti-circle-check me-1" aria-hidden="true"></i>
                    Seekitar <strong>gratis</strong> untuk semua pengguna — baik pembeli
                    maupun penjual. Tidak ada biaya tersembunyi.
                </div>

                <h2 class="h5 fw-bold mt-4">Biaya untuk pembeli</h2>
                <div class="table-responsive">
                    <table class="table table-bordered" style="font-size: 14px;">
                        <thead class="table-light">
                            <tr><th>Layanan</th><th>Biaya</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Membuat akun</td><td><span class="text-success fw-bold">Gratis</span></td></tr>
                            <tr><td>Mencari barang/jasa</td><td><span class="text-success fw-bold">Gratis</span></td></tr>
                            <tr><td>Memasang kebutuhan</td><td><span class="text-success fw-bold">Gratis</span></td></tr>
                            <tr><td>Menerima penawaran</td><td><span class="text-success fw-bold">Gratis</span></td></tr>
                            <tr><td>Melaporkan masalah</td><td><span class="text-success fw-bold">Gratis</span></td></tr>
                        </tbody>
                    </table>
                </div>

                <h2 class="h5 fw-bold mt-4">Biaya untuk penjual / penyedia</h2>
                <div class="alert alert-info mb-3">
                    <i class="ti ti-info-circle me-1" aria-hidden="true"></i>
                    Saat ini seluruh layanan <strong>gratis 100%</strong>.
                    Fitur premium di bawah belum aktif — akan diumumkan sebelum berlaku.
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered" style="font-size: 14px;">
                        <thead class="table-light">
                            <tr><th>Layanan</th><th>Sekarang</th><th>Kedepan</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Pendaftaran toko</td>
                                <td><span class="text-success fw-bold">Gratis</span></td>
                                <td><span class="text-success fw-bold">Gratis</span></td>
                            </tr>
                            <tr>
                                <td>Verifikasi identitas (KTP)</td>
                                <td><span class="text-success fw-bold">Gratis</span></td>
                                <td><span class="text-success fw-bold">Gratis</span></td>
                            </tr>
                            <tr>
                                <td>Memasang listing barang/jasa</td>
                                <td><span class="text-success fw-bold">Gratis</span></td>
                                <td><span class="text-success fw-bold">Gratis</span></td>
                            </tr>
                            <tr>
                                <td>Menerima siaran kebutuhan</td>
                                <td><span class="text-success fw-bold">Gratis</span></td>
                                <td><span class="text-success fw-bold">Gratis</span></td>
                            </tr>
                            <tr>
                                <td>Komisi per transaksi</td>
                                <td><span class="text-success fw-bold">Gratis</span></td>
                                <td><span class="text-success fw-bold">Gratis</span></td>
                            </tr>
                            <tr>
                                <td><strong>Boost Listing</strong> — tampil di atas pencarian 7 hari</td>
                                <td><span class="text-success fw-bold">Gratis</span></td>
                                <td><span class="text-muted">Rp 7.500</span></td>
                            </tr>
                            <tr>
                                <td><strong>Pro Bulanan</strong> — prioritas siaran + statistik</td>
                                <td><span class="text-success fw-bold">Gratis</span></td>
                                <td><span class="text-muted">Rp 30.000/bln</span></td>
                            </tr>
                            <tr>
                                <td><strong>Iklan Banner</strong> — pasang iklan di feed beranda</td>
                                <td><span class="text-success fw-bold">Gratis</span></td>
                                <td><span class="text-muted">Rp 50.000/hari</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h2 class="h5 fw-bold mt-4">Biaya flat per transaksi</h2>
                <p>
                    Seekitar <strong>tidak memotong persentase</strong> dari nilai transaksi.
                    Pembayaran dilakukan langsung antara kamu dan penjual — tanpa dompet
                    digital. Jika diaktifkan, biaya flat kecil (mulai Rp 1.500) dikenakan
                    per pesanan selesai, bukan persentase.
                </p>

                <div class="alert alert-warning mt-4">
                    <i class="ti ti-info-circle me-1" aria-hidden="true"></i>
                    Satu-satunya biaya yang pasti kamu keluarkan adalah <strong>biaya
                    transfer bank</strong> jika pembayaran lewat transfer — biaya tersebut
                    milik bank, bukan Seekitar.
                </div>

                <h2 class="h5 fw-bold mt-4">Pembayaran langsung antar pengguna</h2>
                <p>
                    Semua pembayaran dilakukan langsung antara pembeli dan penjual
                    (tunai, transfer, atau COD). Kamu tidak perlu mengisi saldo dompet
                    atau menunggu pencairan dana.
                </p>

                <h2 class="h5 fw-bold mt-4">Fitur premium</h2>
                <p>
                    Fitur berbagai di atas bersifat opsional. Kamu bisa menggunakan
                    Seekitar sepenuhnya gratis tanpa pernah membayar apa pun.
                </p>
                <p class="mb-0">
                    Setiap perubahan biaya akan diberitahukan <strong>minimal 30 hari</strong>
                    sebelum berlaku. Fitur dasar — membuat toko, listing, menerima penawaran —
                    akan tetap gratis selamanya.
                </p>
            </div>
        </div>
    </section>

@endsection