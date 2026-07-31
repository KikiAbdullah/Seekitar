@extends('web.layout')

@section('title', 'Pusat Bantuan — Seekitar')
@section('meta_description', 'Jawaban atas pertanyaan umum seputar akun, pembayaran, permintaan & penawaran, serta verifikasi dan ulasan di Seekitar.')

@section('content')

  @include('web.partials._hero', [
    'kicker'    => 'Pusat Bantuan',
    'judul'     => 'Pertanyaan yang sering diajukan',
    'subjudul'  => 'Belum menemukan jawaban? Hubungi kanal pengaduan kami — setiap laporan ditinjau tim manusia.',
    'gambar'    => asset('img/web/bantuan.webp'),
    'gambarAlt' => 'Ilustrasi pusat bantuan Seekitar',
  ])

  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-7 text-center" data-aos="fade-up" data-aos-duration="900">
          <span class="eyebrow">FAQ</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-0">Pertanyaan umum</h2>
        </div>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-9" data-aos="fade-up" data-aos-delay="150" data-aos-duration="900">
          <div class="d-grid gap-4">

            {{-- Akun & Keamanan --}}
            <div>
              <h5 class="fw-semibold mb-3"><i class="ti ti-user-check me-2 text-primary"></i>Akun &amp; Keamanan</h5>
              <div class="accordion d-grid gap-3" id="faq-akun">
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#akun-1" aria-expanded="false" aria-controls="akun-1">
                      Bagaimana cara mendaftar di Seekitar?
                    </button>
                  </h2>
                  <div id="akun-1" class="accordion-collapse collapse" data-bs-parent="#faq-akun">
                    <div class="accordion-body fs-4 text-muted">
                      Cukup masukkan nomor HP, lalu verifikasi dengan kode OTP yang dikirim lewat WhatsApp.
                      Nama dan alamat bisa dilengkapi nanti di profil.
                    </div>
                  </div>
                </div>
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#akun-2" aria-expanded="false" aria-controls="akun-2">
                      Saya lupa kata sandi. Bagaimana cara mengatur ulang?
                    </button>
                  </h2>
                  <div id="akun-2" class="accordion-collapse collapse" data-bs-parent="#faq-akun">
                    <div class="accordion-body fs-4 text-muted">
                      Gunakan menu "Lupa kata sandi" di aplikasi — tautan pengaturan ulang dikirim ke
                      nomor HP terdaftar lewat OTP. Setelah 5 kali gagal, akun terkunci sementara
                      selama 30 menit demi keamanan.
                    </div>
                  </div>
                </div>
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#akun-3" aria-expanded="false" aria-controls="akun-3">
                      Bagaimana Seekitar melindungi data saya?
                    </button>
                  </h2>
                  <div id="akun-3" class="accordion-collapse collapse" data-bs-parent="#faq-akun">
                    <div class="accordion-body fs-4 text-muted">
                      Data sensitif seperti NIK dan foto KTP dienkripsi dan hanya bisa diakses oleh
                      admin yang berwenang. Detail lengkapnya di halaman Kebijakan Verifikasi dan
                      Kebijakan Privasi.
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {{-- Pembayaran --}}
            <div>
              <h5 class="fw-semibold mb-3"><i class="ti ti-wallet me-2 text-primary"></i>Pembayaran</h5>
              <div class="accordion d-grid gap-3" id="faq-bayar">
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#bayar-1" aria-expanded="false" aria-controls="bayar-1">
                      Apakah Seekitar memungut komisi dari transaksi?
                    </button>
                  </h2>
                  <div id="bayar-1" class="accordion-collapse collapse" data-bs-parent="#faq-bayar">
                    <div class="accordion-body fs-4 text-muted">
                      Tidak. Di fase ini semua layanan gratis. Pembayaran terjadi langsung antara
                      pembeli dan penjual (COD atau transfer), tanpa lewat platform.
                    </div>
                  </div>
                </div>
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#bayar-2" aria-expanded="false" aria-controls="bayar-2">
                      Metode pembayaran apa saja yang didukung?
                    </button>
                  </h2>
                  <div id="bayar-2" class="accordion-collapse collapse" data-bs-parent="#faq-bayar">
                    <div class="accordion-body fs-4 text-muted">
                      Tergantung kesepakatan dengan penjual: bayar di tempat (COD) atau transfer ke
                      rekening penjual. Detail biaya di masa depan dijelaskan di halaman Biaya &amp; Harga.
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {{-- Permintaan & Penawaran --}}
            <div>
              <h5 class="fw-semibold mb-3"><i class="ti ti-message-dots me-2 text-primary"></i>Permintaan &amp; Penawaran</h5>
              <div class="accordion d-grid gap-3" id="faq-minta">
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#minta-1" aria-expanded="false" aria-controls="minta-1">
                      Apa itu permintaan (customer request)?
                    </button>
                  </h2>
                  <div id="minta-1" class="accordion-collapse collapse" data-bs-parent="#faq-minta">
                    <div class="accordion-body fs-4 text-muted">
                      Pembeli bisa mengumumkan kebutuhan (misal "cari tukang cat dinding"). Penyedia
                      dalam radius akan mendapat notifikasi dan mengirim penawaran. Permintaan
                      otomatis ditutup setelah 24 jam.
                    </div>
                  </div>
                </div>
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#minta-2" aria-expanded="false" aria-controls="minta-2">
                      Bagaimana cara menerima penawaran terbaik?
                    </button>
                  </h2>
                  <div id="minta-2" class="accordion-collapse collapse" data-bs-parent="#faq-minta">
                    <div class="accordion-body fs-4 text-muted">
                      Bandingkan penawaran dari tiap penyedia (harga, estimasi, reputasi), lalu
                      terima yang paling cocok. Begitu penawaran diterima, pesanan dibuat otomatis
                      dan penyedia lain diberi tahu.
                    </div>
                  </div>
                </div>
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#minta-3" aria-expanded="false" aria-controls="minta-3">
                      Bisakah saya memperpanjang permintaan yang sudah kedaluwarsa?
                    </button>
                  </h2>
                  <div id="minta-3" class="accordion-collapse collapse" data-bs-parent="#faq-minta">
                    <div class="accordion-body fs-4 text-muted">
                      Ya, selama statusnya masih terbuka kamu bisa memperpanjang masa berlaku
                      24 jam lagi lewat halaman permintaan.
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {{-- Verifikasi & Ulasan --}}
            <div>
              <h5 class="fw-semibold mb-3"><i class="ti ti-star me-2 text-primary"></i>Verifikasi &amp; Ulasan</h5>
              <div class="accordion d-grid gap-3" id="faq-verif">
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#verif-1" aria-expanded="false" aria-controls="verif-1">
                      Kenapa saya harus verifikasi untuk membuka toko?
                    </button>
                  </h2>
                  <div id="verif-1" class="accordion-collapse collapse" data-bs-parent="#faq-verif">
                    <div class="accordion-body fs-4 text-muted">
                      Verifikasi identitas menjaga pasar tetap aman: pembeli tahu penjualnya nyata.
                      Prosesnya satu kali dan biasanya selesai dalam 1×24 jam.
                    </div>
                  </div>
                </div>
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#verif-2" aria-expanded="false" aria-controls="verif-2">
                      Bagaimana cara memberikan ulasan?
                    </button>
                  </h2>
                  <div id="verif-2" class="accordion-collapse collapse" data-bs-parent="#faq-verif">
                    <div class="accordion-body fs-4 text-muted">
                      Setelah pesanan selesai, beri rating dan komentar lewat halaman pesanan.
                      Ulasan tidak bisa disunting setelah dikirim, dan ratingnya ikut menghitung
                      reputasi toko.
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="pb-8 pb-lg-11">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm" data-aos="fade-up" data-aos-duration="900">
            <div class="card-body text-center p-4 p-lg-8 py-8">
              <h3 class="fs-7 fw-semibold">Masih ada pertanyaan?</h3>
              <p class="mb-8 text-muted">
                Tim kami siap membantu lewat kanal pengaduan resmi.
              </p>
              <div class="d-sm-flex align-items-center justify-content-center gap-3">
                <a href="{{ route('web.contact') }}"
                  class="btn btn-primary px-5 d-block mb-3 mb-sm-0 btn-hover-shadow">Hubungi Kami</a>
                <a href="{{ route('web.about') }}"
                  class="btn btn-outline-primary px-5 d-block">Tentang Seekitar</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
