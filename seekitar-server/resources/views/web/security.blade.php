@extends('web.layout')

@section('title', 'Pusat Keamanan — Seekitar')
@section('meta_description', 'Tips dan panduan menjaga keamanan akun dan transaksi di Seekitar.')

@section('content')

  @include('web.partials._hero', [
    'kicker'    => 'Keamanan',
    'judul'     => 'Pusat Keamanan',
    'subjudul'  => 'Panduan menjaga keamanan akun dan bertransaksi dengan aman.',
    'gambar'    => asset('img/web/kontak.webp'),
    'gambarAlt' => 'Ilustrasi perisai dengan centang di tengah, dikelilingi ikon kunci dan notifikasi keamanan',
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
            <h2 id="keamanan-1">1. Menjaga keamanan akun</h2>
            <ul>
              <li><strong>Jangan bagikan kode OTP</strong> — kepada siapa pun, termasuk yang mengaku dari Seekitar.</li>
              <li><strong>Gunakan nomor yang aktif</strong> — pemulihan akun hanya lewat WhatsApp.</li>
              <li><strong>Logout dari perangkat bersama</strong> — setelah selesai menggunakan ponsel orang lain.</li>
              <li><strong>Aktifkan notifikasi</strong> — agar tahu setiap ada aktivitas di akunmu.</li>
            </ul>

            <h2 id="keamanan-2">2. OTP dan kata sandi</h2>
            <p>
              Seekitar <strong>tidak memakai kata sandi</strong>. Masuk cukup dengan
              kode OTP 6 digit yang dikirim ke WhatsApp. Kode berlaku 5 menit dan
              hanya bisa dipakai sekali.
            </p>
            <p>Yang perlu kamu tahu:</p>
            <ul>
              <li>Kami <strong>tidak pernah</strong> meminta kode OTP — baik lewat telepon, SMS, maupun WhatsApp.</li>
              <li>Jika ada yang meminta kode OTP, itu adalah upaya penipuan. Laporkan segera.</li>
              <li>Setelah 5 kali percobaan gagal, akun dikunci sementara selama 30 menit.</li>
            </ul>

            <h2 id="keamanan-3">3. Tips transaksi aman</h2>
            <div class="row g-3 mb-3">
              @foreach ([
                ['ti ti-map-pin', 'Temu di tempat umum', 'Pilih lokasi ramai untuk bertemu — depan masjid, pusat desa, atau area perkantoran.'],
                ['ti ti-clipboard', 'Cek barang sebelum bayar', 'Buka kemasan, periksa kondisi, pastikan sesuai deskripsi.'],
                ['ti ti-cash', 'Siapkan uang pas', 'COD dengan uang pas meminimalkan risiko kesalahan kembalian.'],
                ['ti ti-message-dots', 'Gunakan fitur Laporkan', 'Jika ada masalah, jangan selesaikan sendiri — laporkan lewat aplikasi.'],
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
            <p>Waspada terhadap modus berikut:</p>
            <ul>
              <li><strong>Minta OTP</strong> — "Saya dari Seekitar, perlu kode OTP untuk verifikasi." <strong>Ini penipuan.</strong></li>
              <li><strong>Transfer di muka</strong> — Penjual minta transfer penuh sebelum barang dikirim, lalu menghilang.</li>
              <li><strong>Harga terlalu murah</strong> — Barang baru harga setengah harga pasar — biasanya palsu atau tidak ada.</li>
              <li><strong>Link palsu</strong> — Tautan mengaku dari Seekitar yang meminta data pribadi.</li>
            </ul>

            <h2 id="keamanan-5">5. Melaporkan masalah keamanan</h2>
            <p>Jika mengalami atau mencurigai masalah keamanan:</p>
            <ul>
              <li>Sengketa transaksi: laporkan lewat aplikasi (Pesanan → Laporkan Masalah).</li>
              <li>Akun diretas: hubungi <a href="mailto:{{ config('seekitar.contacts.complaint') }}" class="text-primary fw-semibold">{{ config('seekitar.contacts.complaint') }}</a>.</li>
              <li>Celah keamanan sistem: laporkan ke <a href="mailto:{{ config('seekitar.contacts.security') }}" class="text-primary fw-semibold">{{ config('seekitar.contacts.security') }}</a>.</li>
            </ul>
            <p>
              Semua laporan ditanggapi sesuai tenggat yang tercantum di
              <a href="{{ route('web.contact') }}" class="text-primary fw-semibold">halaman Kontak</a>.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
