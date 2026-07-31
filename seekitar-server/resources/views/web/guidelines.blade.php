@extends('web.layout')

@section('title', 'Pedoman Komunitas — Seekitar')
@section('meta_description', 'Pedoman perilaku pengguna Seekitar — konten yang dilarang, sanksi, dan cara melaporkan pelanggaran.')

@section('content')

  @include('web.partials._hero', [
    'kicker'   => 'Pedoman',
    'judul'    => 'Pedoman Komunitas',
    'subjudul' => 'Aturan main agar Seekitar tetap aman dan nyaman untuk semua warga.',
  ])

  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row g-5">
        <div class="col-lg-4">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
              <div class="doc-toc" data-aos="fade-up" data-aos-duration="900">
                <span class="eyebrow">Daftar Isi</span>
                <nav class="d-grid gap-1 mt-4" aria-label="Daftar isi">
                  @foreach ([
                    'Prinsip dasar', 'Konten yang dilarang', 'Perilaku yang dilarang',
                    'Pelaporan', 'Sanksi', 'Banding',
                  ] as $i => $bagian)
                    <a class="toc-link" href="#pedoman-{{ $i + 1 }}">
                      <i class="ti ti-chevron-right"></i>{{ $bagian }}
                    </a>
                  @endforeach
                </nav>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-8">
          <div class="doc-content" data-aos="fade-up" data-aos-delay="150" data-aos-duration="900">
            <p class="fs-4 text-muted mb-4">Terakhir diperbarui: 30 Juli 2026</p>

            <div class="alert alert-warning d-flex align-items-start gap-2">
              <i class="ti ti-address-book mt-1" aria-hidden="true"></i>
              <div>
                Pedoman ini berlaku untuk semua pengguna Seekitar. Pelanggaran dapat
                berakibat pada peringatan, pembatasan fitur, atau pemblokiran akun.
              </div>
            </div>

            <h2 id="pedoman-1">1. Prinsip dasar</h2>
            <p>Setiap pengguna Seekitar diharapkan:</p>
            <ul>
              <li>Bersikap jujur dalam setiap transaksi — baik sebagai pembeli maupun penjual.</li>
              <li>Menghormati privasi dan data pengguna lain.</li>
              <li>Mematuhi seluruh hukum yang berlaku di Republik Indonesia.</li>
              <li>Melaporkan pelanggaran yang ditemukan melalui kanal yang tersedia.</li>
            </ul>

            <h2 id="pedoman-2">2. Konten yang dilarang</h2>
            <p>Dilarang memasang listing, permintaan, atau konten lain yang:</p>
            <ul>
              <li><strong>Melanggar hukum</strong> — narkotika, senjata api, obat ilegal, uang palsu, dan barang ilegal lainnya.</li>
              <li><strong>Bersifat eksplisit</strong> — pornografi, konten seksual, atau kekerasan grafis.</li>
              <li><strong>Menyesatkan</strong> — barang atau jasa palsu, deskripsi tidak sesuai kenyataan, harga fiktif.</li>
              <li><strong>Melanggar kekayaan intelektual</strong> — barang bajakan, tiruan merek terkenal tanpa izin.</li>
              <li><strong>Berbahaya</strong> — hewan langka/terlindungi, limbah beracun, bahan kimia berbahaya.</li>
              <li><strong>Dilarang PPMSE</strong> — barang/jasa yang dilarang oleh Permendag PPMSE.</li>
            </ul>

            <h2 id="pedoman-3">3. Perilaku yang dilarang</h2>
            <ul>
              <li>Mengirim pesan spam atau promosi tidak diminta (termasuk tautan afiliasi).</li>
              <li>Memanipulasi rating dengan membuat ulasan palsu atau memberi rating negatif tanpa dasar.</li>
              <li>Memakai identitas orang lain atau membuat akun palsu.</li>
              <li>Menghubungi pengguna lain di luar konteks transaksi tanpa izin.</li>
              <li>Menawarkan transaksi di luar platform untuk menghindari mekanisme sengketa.</li>
            </ul>

            <h2 id="pedoman-4">4. Cara melaporkan pelanggaran</h2>
            <p>Kamu bisa melaporkan pelanggaran melalui:</p>
            <ul>
              <li><strong>Laporkan Masalah</strong> di halaman pesanan — untuk sengketa transaksi, ditanggapi ≤ 1×24 jam.</li>
              <li><strong>Email konten ilegal</strong> — <a href="mailto:{{ config('seekitar.contacts.abuse') }}" class="text-primary fw-semibold">{{ config('seekitar.contacts.abuse') }}</a>, ditanggapi ≤ 1×24 jam.</li>
              <li><strong>Email pengaduan umum</strong> — <a href="mailto:{{ config('seekitar.contacts.complaint') }}" class="text-primary fw-semibold">{{ config('seekitar.contacts.complaint') }}</a>, ditanggapi ≤ 2×24 jam.</li>
            </ul>
            <p>
              Sertakan bukti pendukung (tangkapan layar, nomor pesanan) untuk
              mempercepat penanganan.
            </p>

            <h2 id="pedoman-5">5. Sanksi</h2>
            <p>Tingkat pelanggaran dan sanksi yang dapat dikenakan:</p>
            <div class="table-responsive">
              <table>
                <thead>
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

            <h2 id="pedoman-6">6. Banding</h2>
            <p>
              Jika kamu merasa sanksi dikenakan secara keliru, ajukan banding dalam
              7 hari ke
              <a href="mailto:{{ config('seekitar.contacts.complaint') }}" class="text-primary fw-semibold">{{ config('seekitar.contacts.complaint') }}</a>.
              Sertakan argumen dan bukti pendukung. Banding ditanggapi dalam 3×24 jam.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
