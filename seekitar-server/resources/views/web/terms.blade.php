@extends('web.layout')

@section('title', 'Syarat & Ketentuan — Seekitar')
@section('meta_description', 'Ketentuan penggunaan layanan Seekitar.')

@section('content')

  @include('web.partials._hero', [
    'kicker'   => 'Legal',
    'judul'    => 'Syarat & Ketentuan',
    'subjudul' => 'Berlaku sejak kamu membuat akun Seekitar.',
    'gambar'   => asset('img/web/legal-baru.jpg'),
    'gambarAlt' => 'Ilustrasi syarat dan ketentuan Seekitar',
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
                    'Peran Seekitar', 'Akun', 'Pembayaran', 'Yang dilarang',
                    'Ulasan', 'Sengketa', 'Perubahan ketentuan',
                  ] as $i => $bagian)
                    <a class="toc-link" href="#ketentuan-{{ $i + 1 }}">
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
              <i class="ti ti-help mt-1" aria-hidden="true"></i>
              <div>
                Dengan membuat akun atau memakai layanan Seekitar, kamu menyetujui
                seluruh ketentuan di bawah ini.
              </div>
            </div>

            <h2 id="ketentuan-1">1. Peran Seekitar</h2>
            <p>
              Seekitar adalah <strong>platform penghubung</strong>. Kami
              mempertemukan pembeli dengan penjual atau penyedia jasa di sekitarnya,
              tetapi <strong>bukan pihak dalam transaksi</strong>. Kesepakatan harga,
              kualitas, dan penyerahan barang atau jasa adalah tanggung jawab kedua
              pihak yang bertransaksi.
            </p>

            <h2 id="ketentuan-2">2. Akun</h2>
            <ul>
              <li>Satu nomor telepon untuk satu akun.</li>
              <li>Kamu bertanggung jawab atas semua aktivitas dari akunmu.</li>
              <li>Jangan bagikan kode OTP kepada siapa pun — termasuk kepada pihak yang mengaku dari Seekitar. <strong>Kami tidak pernah meminta kode OTP.</strong></li>
              <li>Membuka toko mensyaratkan verifikasi identitas (KTP).</li>
            </ul>

            <h2 id="ketentuan-3">3. Pembayaran</h2>
            <p>
              Pembayaran dilakukan <strong>langsung antara pembeli dan penjual</strong>,
              secara tunai saat bertemu (COD) atau transfer bank. Seekitar tidak
              menampung dana dan tidak menyediakan rekening bersama.
            </p>
            <div class="alert alert-warning d-flex align-items-start gap-2">
              <i class="ti ti-circle-x mt-1" aria-hidden="true"></i>
              <div>
                Karena dana tidak melewati Seekitar, kami <strong>tidak dapat
                mengembalikan uang</strong> secara otomatis. Bila terjadi masalah,
                laporkan lewat fitur Laporkan Masalah agar kami dapat menengahi.
              </div>
            </div>

            <h2 id="ketentuan-4">4. Yang dilarang</h2>
            <ul>
              <li>Menjual barang atau jasa yang melanggar hukum Indonesia.</li>
              <li>Memasang permintaan atau penawaran palsu.</li>
              <li>Memakai identitas orang lain.</li>
              <li>Memanipulasi rating dengan ulasan palsu.</li>
              <li>Memakai data pengguna lain di luar keperluan transaksi.</li>
            </ul>
            <p>Pelanggaran dapat berujung pada pemblokiran akun tanpa pemberitahuan.</p>

            <h2 id="ketentuan-5">5. Ulasan</h2>
            <p>
              Ulasan bersifat dua arah — pembeli menilai penjual, penjual menilai
              pembeli. Ulasan hanya bisa ditulis dalam <strong>7 hari</strong>
              setelah pesanan selesai, dan <strong>tidak dapat diubah</strong>
              setelah dikirim.
            </p>

            <h2 id="ketentuan-6">6. Penyelesaian sengketa</h2>
            <p>
              Laporan sengketa ditanggapi dalam <strong>1×24 jam</strong>. Selama
              sengketa berjalan, status pesanan dibekukan. Keputusan admin
              didasarkan pada bukti yang diberikan kedua pihak.
            </p>

            <h2 id="ketentuan-7">7. Perubahan ketentuan</h2>
            <p>
              Ketentuan ini dapat berubah. Perubahan yang bersifat material akan
              diberitahukan lewat aplikasi sebelum berlaku.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
