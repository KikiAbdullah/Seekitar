@extends('web.layout')

@section('title', 'Pusat Keamanan')
@section('description', 'Tips dan panduan menjaga keamanan akun dan transaksi di Seekitar.')

@section('content')

    @include('web.partials._hero', [
        'kicker'    => 'Keamanan',
        'judul'     => 'Pusat Keamanan',
        'subjudul'  => 'Panduan menjaga keamanan akun dan bertransaksi dengan aman.',
        'gambar'    => 'img/web/kontak.webp',
        'gambarAlt' => 'Ilustrasi perisai dengan centang di tengah, dikelilingi ikon kunci dan notifikasi keamanan',
    ])

    <section class="py-5">
        <div class="container" style="max-width: 860px;">

            <nav class="lp-toc mb-4" aria-label="Daftar isi">
                @foreach ([
                    'Keamanan akun', 'OTP & kata sandi', 'Transaksi aman',
                    'Penipuan umum', 'Laporkan masalah',
                ] as $i => $bagian)
                    <a href="#keamanan-{{ $i + 1 }}"><span class="lp-no">{{ $i + 1 }}</span> {{ $bagian }}</a>
                @endforeach
            </nav>

            <div class="lp-doc sr-reveal">

                <h2 id="keamanan-1" class="h5 fw-bold mt-4">1. Menjaga keamanan akun</h2>
                <ul>
                    <li><strong>Jangan bagikan kode OTP</strong> — kepada siapa pun, termasuk yang mengaku dari Seekitar.</li>
                    <li><strong>Gunakan nomor yang aktif</strong> — pemulihan akun hanya lewat WhatsApp.</li>
                    <li><strong>Logout dari perangkat bersama</strong> — setelah selesai menggunakan ponsel orang lain.</li>
                    <li><strong>Aktifkan notifikasi</strong> — agar tahu setiap ada aktivitas di akunmu.</li>
                </ul>

                <h2 id="keamanan-2" class="h5 fw-bold mt-4">2. OTP dan kata sandi</h2>
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

                <h2 id="keamanan-3" class="h5 fw-bold mt-4">3. Tips transaksi aman</h2>
                <div class="row g-3 mb-3">
                    @foreach ([
                        ['fa-regular fa-map', 'Temu di tempat umum', 'Pilih lokasi ramai untuk bertemu — depan masjid, pusat desa, atau area perkantoran.'],
                        ['fa-regular fa-clipboard', 'Cek barang sebelum bayar', 'Buka kemasan, periksa kondisi, pastikan sesuai deskripsi.'],
                        ['fa-regular fa-money-bill-1', 'Siapkan uang pas', 'COD dengan uang pas meminimalkan risiko kesalahan kembalian.'],
                        ['fa-regular fa-comment', 'Gunakan fitur Laporkan', 'Jika ada masalah, jangan selesaikan sendiri — laporkan lewat aplikasi.'],
                    ] as [$ikon, $judul, $isi])
                        <div class="col-md-6">
                            <div class="d-flex gap-2">
                                <span class="lp-fitur-ikon lp-tone-hijau flex-shrink-0" style="width: 36px; height: 36px; font-size: 18px;">
                                    <i class="{{ $ikon }}"></i>
                                </span>
                                <div>
                                    <div class="fw-semibold" style="font-size: 14px;">{{ $judul }}</div>
                                    <div style="color: var(--teks-secondary); font-size: 13px;">{{ $isi }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <h2 id="keamanan-4" class="h5 fw-bold mt-4">4. Modus penipuan yang umum</h2>
                <p>Waspada terhadap modus berikut:</p>
                <ul>
                    <li><strong>Minta OTP</strong> — "Saya dari Seekitar, perlu kode OTP untuk verifikasi." <strong>Ini penipuan.</strong></li>
                    <li><strong>Transfer di muka</strong> — Penjual minta transfer penuh sebelum barang dikirim, lalu menghilang.</li>
                    <li><strong>Harga terlalu murah</strong> — Barang baru harga setengah harga pasar — biasanya palsu atau tidak ada.</li>
                    <li><strong>Link palsu</strong> — Tautan mengaku dari Seekitar yang meminta data pribadi.</li>
                </ul>

                <h2 id="keamanan-5" class="h5 fw-bold mt-4">5. Melaporkan masalah keamanan</h2>
                <p>Jika mengalami atau mencurigai masalah keamanan:</p>
                <ul>
                    <li>Sengketa transaksi: laporkan lewat aplikasi (Pesanan → Laporkan Masalah).</li>
                    <li>Akun diretas: hubungi <a href="mailto:{{ config('seekitar.contacts.complaint') }}">{{ config('seekitar.contacts.complaint') }}</a>.</li>
                    <li>Celah keamanan sistem: laporkan ke <a href="mailto:{{ config('seekitar.contacts.security') }}">{{ config('seekitar.contacts.security') }}</a>.</li>
                </ul>
                <p class="mb-0">
                    Semua laporan ditanggapi sesuai tenggat yang tercantum di
                    <a href="{{ route('web.contact') }}">halaman Kontak</a>.
                </p>
            </div>
        </div>
    </section>

@endsection