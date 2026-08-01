@extends('web.layout')

@section('title', 'Pusat Keamanan — Seekitar')
@section('meta_description', 'Tips dan panduan menjaga keamanan akun dan bertransaksi dengan aman di Seekitar.')

@section('content')

  @include('web.partials._hero', [
    'kicker'    => 'Keamanan',
    'judul'     => 'Pusat Keamanan',
    'subjudul'  => 'Panduan lengkap menjaga keamanan akun dan bertransaksi dengan aman di Seekitar.',
    'gambar'    => asset('img/web/keamanan-baru.jpg'),
    'gambarAlt' => 'Ilustrasi keamanan akun dan transaksi Seekitar',
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
                    'Keamanan akun', 'OTP & kata sandi', 'Transaksi aman',
                    'Penipuan umum', 'Laporkan masalah',
                  ] as $i => $bagian)
                    <a class="toc-link" href="#keamanan-{{ $i + 1 }}">
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

            <div class="alert alert-success d-flex align-items-start gap-3 mb-4">
              <i class="ti ti-shield-check fs-5 mt-1"></i>
              <div>
                <strong>Keamanan adalah prioritas kami.</strong> Seekitar memakai enkripsi, OTP,
                dan verifikasi identitas untuk melindungi setiap pengguna. Tapi kamu juga
                punya peran penting — baca panduan ini baik-baik.
              </div>
            </div>

            <h2 id="keamanan-1">1. Menjaga keamanan akun</h2>
            <ul>
              <li><strong>Jangan bagikan kode OTP</strong> — kepada siapa pun, termasuk yang mengaku dari Seekitar. Kami <strong>tidak pernah</strong> meminta kode OTP-mu.</li>
              <li><strong>Gunakan nomor yang selalu aktif</strong> — pemulihan akun hanya bisa dilakukan lewat WhatsApp ke nomor terdaftar.</li>
              <li><strong>Logout dari perangkat bersama</strong> — setelah selesai menggunakan ponsel teman atau keluarga.</li>
              <li><strong>Aktifkan notifikasi</strong> — agar kamu tahu setiap ada aktivitas di akunmu (pesanan baru, penawaran, dll).</li>
              <li><strong>Jangan berikan akunmu ke orang lain</strong> — kamu bertanggung jawab atas semua aktivitas dari akunmu.</li>
            </ul>

            <h2 id="keamanan-2">2. OTP dan kata sandi</h2>
            <p>
              Seekitar <strong>tidak memakai kata sandi</strong>. Masuk cukup dengan
              kode OTP 6 digit yang dikirim ke WhatsApp kamu. Ini lebih aman karena:
            </p>
            <ul>
              <li>Tidak ada kata sandi yang bisa dicuri atau ditebak.</li>
              <li>Kode OTP hanya berlaku <strong>5 menit</strong> dan hanya bisa dipakai <strong>sekali</strong>.</li>
              <li>Setelah <strong>5 kali</strong> percobaan gagal, akun dikunci sementara selama <strong>30 menit</strong>.</li>
            </ul>
            <div class="alert alert-warning d-flex align-items-start gap-3">
              <i class="ti ti-alert-triangle fs-5 mt-1"></i>
              <div>
                <strong>Waspada penipuan!</strong> Jika ada yang menghubungimu — lewat telepon, SMS,
                WhatsApp, atau chat — dan meminta kode OTP dengan alasan apa pun,
                <strong>itu penipuan</strong>. Jangan berikan. Segera laporkan ke kami.
              </div>
            </div>

            <h2 id="keamanan-3">3. Tips transaksi aman</h2>
            <div class="row g-3 mb-3">
              @foreach ([
                ['ti ti-map-pin', 'Temu di tempat umum', 'Pilih lokasi ramai untuk bertemu — depan masjid, pusat desa, area perkantoran, atau minimarket.'],
                ['ti ti-clipboard', 'Cek barang sebelum bayar', 'Buka kemasan, periksa kondisi fisik, dan pastikan barang sesuai deskripsi di listing.'],
                ['ti ti-cash', 'Siapkan uang pas', 'COD dengan uang pas meminimalkan risiko kesalahan kembalian dan mempercepat transaksi.'],
                ['ti ti-users', 'Ajak teman', 'Jika merasa ragu, ajak teman atau keluarga saat bertemu penjual untuk pertama kalinya.'],
                ['ti ti-message-dots', 'Gunakan chat aplikasi', 'Semua komunikasi awal sebaiknya lewat chat di aplikasi, bukan WhatsApp pribadi.'],
                ['ti ti-camera', 'Dokumentasikan', 'Foto barang dan bukti pembayaran sebagai cadangan jika terjadi sengketa.'],
              ] as [$ikon, $judul, $isi])
                <div class="col-md-6">
                  <div class="d-flex align-items-start gap-3">
                    <span class="icon-soft" style="width: 40px; height: 40px; font-size: 1.05rem;">
                      <i class="{{ $ikon }}"></i>
                    </span>
                    <div>
                      <div class="fw-semibold">{{ $judul }}</div>
                      <div class="text-muted fs-4">{{ $isi }}</div>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>

            <h2 id="keamanan-4">4. Modus penipuan yang umum</h2>
            <p>Kenali modus-modus berikut agar kamu tidak menjadi korban:</p>

            <div class="table-responsive">
              <table>
                <thead>
                  <tr>
                    <th>Modus</th>
                    <th>Ciri-ciri</th>
                    <th>Yang harus dilakukan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><strong>Minta OTP</strong></td>
                    <td>"Saya dari Seekitar, perlu kode OTP untuk verifikasi."</td>
                    <td><span class="text-danger fw-semibold">Jangan berikan!</span> Kami tidak pernah meminta OTP.</td>
                  </tr>
                  <tr>
                    <td><strong>Transfer di muka</strong></td>
                    <td>Penjual minta transfer penuh sebelum barang dikirim, lalu menghilang.</td>
                    <td>Pilih COD. Jika transfer, minta foto barang dan KTP penjual.</td>
                  </tr>
                  <tr>
                    <td><strong>Harga terlalu murah</strong></td>
                    <td>Barang baru harga setengah pasaran — biasanya palsu atau tidak ada.</td>
                    <td>Bandingkan harga pasar. Jika terlalu murah, curigai.</td>
                  </tr>
                  <tr>
                    <td><strong>Tautan palsu</strong></td>
                    <td>Tautan mengaku dari Seekitar yang meminta data pribadi.</td>
                    <td>Jangan klik. Domain resmi hanya seekitar.id dan seekitar.co.id.</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <h2 id="keamanan-5">5. Melaporkan masalah keamanan</h2>
            <p>Jika mengalami atau mencurigai masalah keamanan:</p>
            <div class="row g-3">
              <div class="col-md-4">
                <div class="card border shadow-sm h-100">
                  <div class="card-body p-3 text-center">
                    <i class="ti ti-clipboard-list fs-6 text-primary mb-2 d-block"></i>
                    <strong class="d-block mb-1">Sengketa Transaksi</strong>
                    <small class="text-muted">Laporkan lewat aplikasi: Pesanan → Laporkan Masalah</small>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card border shadow-sm h-100">
                  <div class="card-body p-3 text-center">
                    <i class="ti ti-user-x fs-6 text-primary mb-2 d-block"></i>
                    <strong class="d-block mb-1">Akun Diretas</strong>
                    <small class="text-muted">Hubungi <a href="mailto:{{ config('seekitar.contacts.complaint') }}" class="text-primary fw-semibold">{{ config('seekitar.contacts.complaint') }}</a></small>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card border shadow-sm h-100">
                  <div class="card-body p-3 text-center">
                    <i class="ti ti-shield-lock fs-6 text-primary mb-2 d-block"></i>
                    <strong class="d-block mb-1">Celah Keamanan</strong>
                    <small class="text-muted">Laporkan ke <a href="mailto:{{ config('seekitar.contacts.security') }}" class="text-primary fw-semibold">{{ config('seekitar.contacts.security') }}</a></small>
                  </div>
                </div>
              </div>
            </div>
            <p class="mt-4">
              Semua laporan ditanggapi sesuai tenggat yang tercantum di
              <a href="{{ route('web.contact') }}" class="text-primary fw-semibold">halaman Kontak</a>.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ============================ CTA ============================ --}}
  <section class="pb-8 pb-lg-11">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm" data-aos="fade-up" data-aos-duration="900">
            <div class="card-body text-center p-4 p-lg-8 py-8">
              <h3 class="fs-7 fw-semibold">Keamanan adalah tanggung jawab bersama</h3>
              <p class="mb-8 text-muted">
                Laporkan aktivitas mencurigakan. Satu laporan bisa melindungi banyak orang.
              </p>
              <a href="{{ route('web.contact') }}" class="btn btn-primary px-5 btn-hover-shadow">Laporkan Sekarang</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
