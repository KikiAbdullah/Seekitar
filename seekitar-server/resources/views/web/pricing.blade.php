@extends('web.layout')

@section('title', 'Biaya & Harga — Seekitar')
@section('meta_description', 'Rincian biaya penggunaan Seekitar — gratis untuk pembeli dan penjual.')

@section('content')

  @include('web.partials._hero', [
    'kicker'   => 'Informasi Biaya',
    'judul'    => 'Biaya & Harga',
    'subjudul' => 'Transparansi biaya penggunaan platform Seekitar sesuai PPMSE.',
  ])

  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-9">
          <div class="doc-content" data-aos="fade-up" data-aos-duration="900">
            <div class="alert alert-success d-flex align-items-start gap-2">
              <i class="ti ti-circle-check mt-1" aria-hidden="true"></i>
              <div>
                Seekitar <strong>gratis</strong> untuk semua pengguna — baik pembeli
                maupun penjual. Tidak ada biaya tersembunyi.
              </div>
            </div>

            <h2>Biaya untuk pembeli</h2>
            <div class="table-responsive">
              <table>
                <thead>
                  <tr>
                    <th>Layanan</th>
                    <th>Biaya</th>
                  </tr>
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

            <h2>Biaya untuk penjual / penyedia</h2>
            <div class="alert alert-info d-flex align-items-start gap-2">
              <i class="ti ti-help mt-1" aria-hidden="true"></i>
              <div>
                Saat ini seluruh layanan <strong>gratis 100%</strong>.
                Fitur premium di bawah belum aktif — akan diumumkan sebelum berlaku.
              </div>
            </div>
            <div class="table-responsive">
              <table>
                <thead>
                  <tr>
                    <th>Layanan</th>
                    <th>Sekarang</th>
                    <th>Kedepan</th>
                  </tr>
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
                    <td><strong>Boost Listing</strong> — tampil di atas pencarian {{ config('seekitar.monetization.boost_listing_duration') }} hari</td>
                    <td><span class="text-success fw-bold">Gratis</span></td>
                    <td><span class="text-muted">{{ number_format(config('seekitar.monetization.boost_listing_price'), 0, ',', '.') }}</span></td>
                  </tr>
                  <tr>
                    <td><strong>Pro Bulanan</strong> — prioritas siaran + statistik</td>
                    <td><span class="text-success fw-bold">Gratis</span></td>
                    <td><span class="text-muted">{{ number_format(config('seekitar.monetization.pro_monthly_price'), 0, ',', '.') }}/bln</span></td>
                  </tr>
                  <tr>
                    <td><strong>Iklan Banner</strong> — pasang iklan di feed beranda</td>
                    <td><span class="text-success fw-bold">Gratis</span></td>
                    <td><span class="text-muted">{{ number_format(config('seekitar.monetization.banner_price_per_day'), 0, ',', '.') }}/hari</span></td>
                  </tr>
                </tbody>
              </table>
            </div>

            <h2>Biaya flat per transaksi</h2>
            <p>
              Seekitar <strong>tidak memotong persentase</strong> dari nilai transaksi.
              Pembayaran dilakukan langsung antara kamu dan penjual — tanpa dompet
              digital. Jika diaktifkan, biaya flat kecil (mulai
              {{ number_format(config('seekitar.monetization.service_fee_amount'), 0, ',', '.') }})
              dikenakan per pesanan selesai, bukan persentase.
            </p>

            <div class="alert alert-warning d-flex align-items-start gap-2">
              <i class="ti ti-help mt-1" aria-hidden="true"></i>
              <div>
                Satu-satunya biaya yang pasti kamu keluarkan adalah <strong>biaya
                transfer bank</strong> jika pembayaran lewat transfer — biaya tersebut
                milik bank, bukan Seekitar.
              </div>
            </div>

            <h2>Pembayaran langsung antar pengguna</h2>
            <p>
              Semua pembayaran dilakukan langsung antara pembeli dan penjual
              (tunai, transfer, atau COD). Kamu tidak perlu mengisi saldo dompet
              atau menunggu pencairan dana.
            </p>

            <h2>Fitur premium</h2>
            <p>
              Fitur berbagai di atas bersifat opsional. Kamu bisa menggunakan
              Seekitar sepenuhnya gratis tanpa pernah membayar apa pun.
            </p>
            <p>
              Setiap perubahan biaya akan diberitahukan <strong>minimal 30 hari</strong>
              sebelum berlaku. Fitur dasar — membuat toko, listing, menerima penawaran —
              akan tetap gratis selamanya.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
