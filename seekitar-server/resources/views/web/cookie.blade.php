@extends('web.layout')

@section('title', 'Kebijakan Cookie — Seekitar')
@section('meta_description', 'Bagaimana Seekitar menggunakan cookie dan teknologi penyimpanan lokal di situs web.')

@section('content')

  @include('web.partials._hero', [
    'kicker'   => 'Legal · Cookie',
    'judul'    => 'Kebijakan Cookie',
    'subjudul' => 'Transparansi penggunaan cookie dan teknologi sejenis di situs Seekitar.',
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
                    'Apa itu cookie', 'Cookie yang dipakai', 'Cookie pihak ketiga',
                    'Kontrol cookie', 'Pembaruan',
                  ] as $i => $bagian)
                    <a class="toc-link" href="#cookie-{{ $i + 1 }}">
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

            <div class="alert alert-info d-flex align-items-start gap-2">
              <i class="ti ti-file-text mt-1" aria-hidden="true"></i>
              <div>
                Dokumen ini melengkapi
                <a href="{{ route('web.privacy') }}" class="text-primary fw-semibold">Kebijakan Privasi</a>
                dan menjelaskan secara khusus penggunaan cookie serta teknologi penyimpanan lokal.
              </div>
            </div>

            <h2 id="cookie-1">1. Apa itu cookie?</h2>
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

            <h2 id="cookie-2">2. Cookie yang kami pakai</h2>
            <div class="table-responsive">
              <table>
                <thead>
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
            <p class="text-muted">
              Kami hanya memakai cookie fungsional. Tidak ada cookie iklan, pelacakan
              lintas-situs, atau analitik perilaku individu.
            </p>

            <h2 id="cookie-3">3. Cookie pihak ketiga</h2>
            <p>
              Seekitar saat ini <strong>tidak memakai</strong> layanan pihak ketiga
              yang menempatkan cookie di perangkatmu (seperti Google Analytics, iklan
              tersegmentasi, atau pixel media sosial).
            </p>
            <p>
              Bila di masa depan kami menambahkan layanan pihak ketiga yang memakai
              cookie, halaman ini akan diperbarui dan kamu akan mendapat pemberitahuan.
            </p>

            <h2 id="cookie-4">4. Cara mengontrol cookie</h2>
            <p>Kamu dapat mengelola atau menghapus cookie kapan saja lewat pengaturan peramban:</p>
            <ul>
              <li><strong>Google Chrome:</strong> Pengaturan → Privasi dan keamanan → Cookie</li>
              <li><strong>Mozilla Firefox:</strong> Pengaturan → Privasi &amp; Keamanan → Cookie dan Data Situs</li>
              <li><strong>Safari:</strong> Pengaturan → Privasi → Cookie</li>
              <li><strong>Microsoft Edge:</strong> Pengaturan → Cookie dan izin situs</li>
            </ul>
            <p>
              Penghapusan cookie tidak memengaruhi fungsi aplikasi Seekitar di ponsel,
              karena aplikasi tidak bergantung pada cookie peramban.
            </p>

            <h2 id="cookie-5">5. Pembaruan kebijakan cookie</h2>
            <p>
              Kebijakan ini dapat berubah seiring penambahan fitur atau perubahan teknologi.
              Tanggal berlaku terbaru tercantum di bagian atas dokumen. Perubahan yang
              bersifat material akan diberitahukan lewat situs.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
