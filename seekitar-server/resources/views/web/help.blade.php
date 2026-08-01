@extends('web.layout')

@section('title', 'Pusat Bantuan — Seekitar')
@section('meta_description', 'Jawaban atas pertanyaan umum seputar akun, pembayaran, permintaan & penawaran, serta verifikasi dan ulasan di Seekitar.')

@section('content')

  @include('web.partials._hero', [
    'kicker'    => 'Pusat Bantuan',
    'judul'     => 'Pertanyaan yang sering diajukan',
    'subjudul'  => 'Belum menemukan jawaban? Hubungi kanal pengaduan kami — setiap laporan ditinjau oleh tim manusia.',
    'gambar'    => asset('img/web/bantuan-baru.jpg'),
    'gambarAlt' => 'Ilustrasi pusat bantuan Seekitar',
  ])

  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-7 text-center" data-aos="fade-up" data-aos-duration="900">
          <span class="eyebrow">FAQ</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-2">Pertanyaan umum</h2>
          <p class="fs-5 text-muted mb-0">Pilih kategori di bawah untuk melihat jawabannya.</p>
        </div>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-9" data-aos="fade-up" data-aos-delay="150" data-aos-duration="900">
          <div class="d-grid gap-4">

            {{-- Akun & Keamanan --}}
            <div class="card border-0 shadow-sm">
              <div class="card-body p-4 p-lg-5">
                <h5 class="fw-semibold mb-4 d-flex align-items-center gap-2">
                  <span class="icon-soft"><i class="ti ti-user-check"></i></span> Akun & Keamanan
                </h5>
                <div class="accordion d-grid gap-3" id="faq-akun">
                  <div class="accordion-item border rounded-3">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#akun-1" aria-expanded="false" aria-controls="akun-1">
                        Bagaimana cara mendaftar di Seekitar?
                      </button>
                    </h2>
                    <div id="akun-1" class="accordion-collapse collapse" data-bs-parent="#faq-akun">
                      <div class="accordion-body fs-4 text-muted">
                        Sangat mudah! Unduh aplikasi Seekitar, masukkan nomor HP kamu, lalu verifikasi
                        dengan kode OTP 6 digit yang dikirim lewat WhatsApp. Setelah masuk, kamu bisa
                        langsung mencari barang dan jasa. Untuk membuka toko, lengkapi profil dan lakukan
                        verifikasi identitas.
                      </div>
                    </div>
                  </div>
                  <div class="accordion-item border rounded-3">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#akun-2" aria-expanded="false" aria-controls="akun-2">
                        Saya lupa kata sandi. Bagaimana cara mengatur ulang?
                      </button>
                    </h2>
                    <div id="akun-2" class="accordion-collapse collapse" data-bs-parent="#faq-akun">
                      <div class="accordion-body fs-4 text-muted">
                        Seekitar tidak memakai kata sandi — masuk cukup dengan kode OTP yang dikirim ke
                        WhatsApp kamu. Jadi tidak ada kata sandi yang perlu diingat atau diatur ulang.
                        Setelah 5 kali percobaan OTP gagal, akun akan terkunci sementara selama 30 menit
                        demi keamanan.
                      </div>
                    </div>
                  </div>
                  <div class="accordion-item border rounded-3">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#akun-3" aria-expanded="false" aria-controls="akun-3">
                        Bagaimana Seekitar melindungi data saya?
                      </button>
                    </h2>
                    <div id="akun-3" class="accordion-collapse collapse" data-bs-parent="#faq-akun">
                      <div class="accordion-body fs-4 text-muted">
                        Data sensitif seperti NIK dan foto KTP dienkripsi dan hanya bisa diakses oleh
                        admin yang berwenang. NIK disimpan dalam bentuk hash (terenkripsi), bukan teks
                        biasa. Seluruh lalu lintas data menggunakan HTTPS. Detail lengkapnya bisa kamu baca
                        di halaman <a href="{{ route('web.privacy') }}" class="text-primary fw-semibold">Kebijakan Privasi</a>
                        dan <a href="{{ route('web.verification') }}" class="text-primary fw-semibold">Kebijakan Verifikasi</a>.
                      </div>
                    </div>
                  </div>
                  <div class="accordion-item border rounded-3">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#akun-4" aria-expanded="false" aria-controls="akun-4">
                        Apakah saya bisa pakai akun yang sama di beberapa perangkat?
                      </button>
                    </h2>
                    <div id="akun-4" class="accordion-collapse collapse" data-bs-parent="#faq-akun">
                      <div class="accordion-body fs-4 text-muted">
                        Ya, kamu bisa login di beberapa perangkat. Namun untuk keamanan, kami sarankan
                        logout dari perangkat yang tidak kamu pakai lagi, terutama perangkat bersama.
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {{-- Pembayaran --}}
            <div class="card border-0 shadow-sm">
              <div class="card-body p-4 p-lg-5">
                <h5 class="fw-semibold mb-4 d-flex align-items-center gap-2">
                  <span class="icon-soft"><i class="ti ti-wallet"></i></span> Pembayaran
                </h5>
                <div class="accordion d-grid gap-3" id="faq-bayar">
                  <div class="accordion-item border rounded-3">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#bayar-1" aria-expanded="false" aria-controls="bayar-1">
                        Apakah Seekitar memungut komisi dari transaksi?
                      </button>
                    </h2>
                    <div id="bayar-1" class="accordion-collapse collapse" data-bs-parent="#faq-bayar">
                      <div class="accordion-body fs-4 text-muted">
                        <strong>Tidak sama sekali.</strong> Seekitar 100% gratis. Pembayaran terjadi
                        langsung antara pembeli dan penjual (COD atau transfer bank), tanpa melewati
                        platform. Kami tidak memotong sepeser pun dari nilai transaksi. Detail lengkap
                        ada di <a href="{{ route('web.pricing') }}" class="text-primary fw-semibold">halaman Biaya & Harga</a>.
                      </div>
                    </div>
                  </div>
                  <div class="accordion-item border rounded-3">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#bayar-2" aria-expanded="false" aria-controls="bayar-2">
                        Metode pembayaran apa saja yang bisa saya pakai?
                      </button>
                    </h2>
                    <div id="bayar-2" class="accordion-collapse collapse" data-bs-parent="#faq-bayar">
                      <div class="accordion-body fs-4 text-muted">
                        Pembayaran dilakukan langsung antara kamu dan penjual/pembeli. Metode yang
                        umum dipakai:
                        <ul class="mt-2">
                          <li><strong>COD (Bayar di Tempat)</strong> — bayar tunai saat bertemu dan menerima barang.</li>
                          <li><strong>Transfer Bank</strong> — transfer langsung ke rekening penjual.</li>
                        </ul>
                        Kamu dan penjual bebas menyepakati metode apa pun yang nyaman untuk kedua pihak.
                      </div>
                    </div>
                  </div>
                  <div class="accordion-item border rounded-3">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#bayar-3" aria-expanded="false" aria-controls="bayar-3">
                        Apakah Seekitar menyediakan rekening bersama (escrow)?
                      </button>
                    </h2>
                    <div id="bayar-3" class="accordion-collapse collapse" data-bs-parent="#faq-bayar">
                      <div class="accordion-body fs-4 text-muted">
                        Saat ini Seekitar <strong>tidak</strong> menyediakan rekening bersama atau dompet
                        digital. Semua pembayaran terjadi langsung antara pembeli dan penjual. Karena itu
                        kami sangat menyarankan transaksi COD untuk keamanan maksimal.
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {{-- Permintaan & Penawaran --}}
            <div class="card border-0 shadow-sm">
              <div class="card-body p-4 p-lg-5">
                <h5 class="fw-semibold mb-4 d-flex align-items-center gap-2">
                  <span class="icon-soft"><i class="ti ti-message-dots"></i></span> Permintaan & Penawaran
                </h5>
                <div class="accordion d-grid gap-3" id="faq-minta">
                  <div class="accordion-item border rounded-3">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#minta-1" aria-expanded="false" aria-controls="minta-1">
                        Apa itu permintaan (customer request)?
                      </button>
                    </h2>
                    <div id="minta-1" class="accordion-collapse collapse" data-bs-parent="#faq-minta">
                      <div class="accordion-body fs-4 text-muted">
                        Permintaan adalah cara pembeli mengumumkan kebutuhannya. Misalnya "cari tukang cat
                        dinding" atau "butuh sewa tenda 4 unit". Penyedia jasa dalam radius yang ditentukan
                        akan mendapat notifikasi dan bisa mengirimkan penawaran mereka. Permintaan otomatis
                        ditutup setelah 24 jam jika tidak ada penawaran yang diterima.
                      </div>
                    </div>
                  </div>
                  <div class="accordion-item border rounded-3">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#minta-2" aria-expanded="false" aria-controls="minta-2">
                        Bagaimana cara memilih penawaran terbaik?
                      </button>
                    </h2>
                    <div id="minta-2" class="accordion-collapse collapse" data-bs-parent="#faq-minta">
                      <div class="accordion-body fs-4 text-muted">
                        Kamu bisa membandingkan penawaran dari setiap penyedia berdasarkan:
                        <ul class="mt-2">
                          <li><strong>Harga</strong> — bandingkan harga yang ditawarkan.</li>
                          <li><strong>Estimasi pengerjaan</strong> — kapan penyedia bisa mulai dan selesai.</li>
                          <li><strong>Rating dan ulasan</strong> — lihat reputasi penyedia dari pelanggan sebelumnya.</li>
                        </ul>
                        Begitu kamu menerima satu penawaran, pesanan akan dibuat otomatis dan
                        penyedia lain akan diberi tahu.
                      </div>
                    </div>
                  </div>
                  <div class="accordion-item border rounded-3">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#minta-3" aria-expanded="false" aria-controls="minta-3">
                        Bisakah saya memperpanjang permintaan yang sudah kedaluwarsa?
                      </button>
                    </h2>
                    <div id="minta-3" class="accordion-collapse collapse" data-bs-parent="#faq-minta">
                      <div class="accordion-body fs-4 text-muted">
                        Ya, selama statusnya masih terbuka, kamu bisa memperpanjang masa berlaku 24 jam
                        lagi langsung dari halaman permintaan. Setelah kedaluwarsa, kamu perlu membuat
                        permintaan baru.
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {{-- Verifikasi & Ulasan --}}
            <div class="card border-0 shadow-sm">
              <div class="card-body p-4 p-lg-5">
                <h5 class="fw-semibold mb-4 d-flex align-items-center gap-2">
                  <span class="icon-soft"><i class="ti ti-star"></i></span> Verifikasi & Ulasan
                </h5>
                <div class="accordion d-grid gap-3" id="faq-verif">
                  <div class="accordion-item border rounded-3">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#verif-1" aria-expanded="false" aria-controls="verif-1">
                        Kenapa saya harus verifikasi identitas untuk membuka toko?
                      </button>
                    </h2>
                    <div id="verif-1" class="accordion-collapse collapse" data-bs-parent="#faq-verif">
                      <div class="accordion-body fs-4 text-muted">
                        Verifikasi identitas menciptakan lingkungan transaksi yang aman untuk semua.
                        Pembeli tahu penjualnya orang nyata, dan pelanggar bisa diidentifikasi. Prosesnya
                        satu kali saja, gratis, dan biasanya selesai dalam 1×24 jam. Detail lengkapnya
                        ada di <a href="{{ route('web.verification') }}" class="text-primary fw-semibold">Kebijakan Verifikasi</a>.
                      </div>
                    </div>
                  </div>
                  <div class="accordion-item border rounded-3">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#verif-2" aria-expanded="false" aria-controls="verif-2">
                        Bagaimana cara memberikan ulasan?
                      </button>
                    </h2>
                    <div id="verif-2" class="accordion-collapse collapse" data-bs-parent="#faq-verif">
                      <div class="accordion-body fs-4 text-muted">
                        Setelah pesanan selesai, kamu bisa memberikan rating bintang 1-5 dan menulis
                        komentar lewat halaman pesanan. Batas waktu pemberian ulasan adalah 7 hari
                        setelah pesanan selesai. Ulasan tidak bisa disunting setelah dikirim, jadi
                        pastikan sudah benar sebelum submit. Rating yang kamu berikan akan ikut
                        menghitung reputasi toko.
                      </div>
                    </div>
                  </div>
                  <div class="accordion-item border rounded-3">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#verif-3" aria-expanded="false" aria-controls="verif-3">
                        Apa yang terjadi jika verifikasi saya ditolak?
                      </button>
                    </h2>
                    <div id="verif-3" class="accordion-collapse collapse" data-bs-parent="#faq-verif">
                      <div class="accordion-body fs-4 text-muted">
                        Kamu akan mendapat notifikasi dengan alasan penolakan (misalnya foto buram,
                        data tidak cocok). Kamu bisa mengajukan ulang kapan saja setelah memperbaiki
                        berkas. Pengajuan ulang gratis dan tidak dibatasi jumlahnya.
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {{-- Transaksi & Pengiriman --}}
            <div class="card border-0 shadow-sm">
              <div class="card-body p-4 p-lg-5">
                <h5 class="fw-semibold mb-4 d-flex align-items-center gap-2">
                  <span class="icon-soft"><i class="ti ti-truck"></i></span> Transaksi & Pengiriman
                </h5>
                <div class="accordion d-grid gap-3" id="faq-trans">
                  <div class="accordion-item border rounded-3">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#trans-1" aria-expanded="false" aria-controls="trans-1">
                        Bagaimana cara bertransaksi dengan aman?
                      </button>
                    </h2>
                    <div id="trans-1" class="accordion-collapse collapse" data-bs-parent="#faq-trans">
                      <div class="accordion-body fs-4 text-muted">
                        <ul>
                          <li>Pilih COD dan bertemu di tempat umum yang ramai.</li>
                          <li>Periksa kondisi barang sebelum membayar.</li>
                          <li>Gunakan fitur chat di aplikasi — jangan lanjutkan komunikasi ke WhatsApp sebelum pesanan dibuat.</li>
                          <li>Jika ada masalah, segera laporkan lewat tombol "Laporkan Masalah" di halaman pesanan.</li>
                        </ul>
                        Tips selengkapnya ada di <a href="{{ route('web.security') }}" class="text-primary fw-semibold">Pusat Keamanan</a>.
                      </div>
                    </div>
                  </div>
                  <div class="accordion-item border rounded-3">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                        data-bs-target="#trans-2" aria-expanded="false" aria-controls="trans-2">
                        Berapa lama pesanan saya diproses?
                      </button>
                    </h2>
                    <div id="trans-2" class="accordion-collapse collapse" data-bs-parent="#faq-trans">
                      <div class="accordion-body fs-4 text-muted">
                        Tergantung kesepakatan dengan penjual. Setiap penjual bisa mencantumkan estimasi
                        waktu pengerjaan atau pengiriman di listing-nya. Kamu juga bisa menanyakan langsung
                        lewat chat di aplikasi.
                      </div>
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

  {{-- ============================ KANAL BANTUAN ============================ --}}
  <section class="bg-light py-8 py-lg-11">
    <div class="container">
      <div class="row justify-content-center mb-5 mb-lg-7">
        <div class="col-lg-7 text-center" data-aos="fade-up" data-aos-duration="900">
          <span class="eyebrow">Butuh Bantuan Langsung?</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-2">Kanal bantuan kami</h2>
          <p class="fs-5 text-muted mb-0">Pilih kanal yang sesuai dengan kebutuhanmu.</p>
        </div>
      </div>
      <div class="row g-4 justify-content-center">
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100 text-center">
            <div class="card-body p-4">
              <span class="icon-soft d-inline-flex align-items-center justify-content-center mb-4" style="width: 64px; height: 64px; font-size: 1.75rem;">
                <i class="ti ti-headset"></i>
              </span>
              <h5 class="fs-5 fw-semibold mb-2">Pengaduan Umum</h5>
              <p class="mb-3 text-muted fs-4">Masalah transaksi, penjual, atau keluhan layanan.</p>
              <span class="badge bg-primary-subtle text-primary mb-3">≤ 2×24 jam</span>
              <div>
                <a href="mailto:{{ config('seekitar.contacts.complaint') }}" class="fw-semibold text-primary text-decoration-none fs-4">
                  {{ config('seekitar.contacts.complaint') }}
                </a>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100 text-center">
            <div class="card-body p-4">
              <span class="icon-soft d-inline-flex align-items-center justify-content-center mb-4" style="width: 64px; height: 64px; font-size: 1.75rem;">
                <i class="ti ti-brand-whatsapp"></i>
              </span>
              <h5 class="fs-5 fw-semibold mb-2">WhatsApp</h5>
              <p class="mb-3 text-muted fs-4">Alternatif cepat untuk pertanyaan umum.</p>
              <span class="badge bg-primary-subtle text-primary mb-3">Jam kerja</span>
              <div>
                <a href="https://wa.me/{{ config('seekitar.contacts.whatsapp') }}"
                  class="fw-semibold text-primary text-decoration-none fs-4" target="_blank" rel="noopener">
                  +62 {{ ltrim(config('seekitar.contacts.whatsapp'), '62') }}
                </a>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100 text-center">
            <div class="card-body p-4">
              <span class="icon-soft d-inline-flex align-items-center justify-content-center mb-4" style="width: 64px; height: 64px; font-size: 1.75rem;">
                <i class="ti ti-flag"></i>
              </span>
              <h5 class="fs-5 fw-semibold mb-2">Konten Ilegal</h5>
              <p class="mb-3 text-muted fs-4">Penipuan, konten terlarang, atau pelanggaran hukum.</p>
              <span class="badge bg-danger-subtle text-danger mb-3">≤ 1×24 jam</span>
              <div>
                <a href="mailto:{{ config('seekitar.contacts.abuse') }}" class="fw-semibold text-primary text-decoration-none fs-4">
                  {{ config('seekitar.contacts.abuse') }}
                </a>
              </div>
            </div>
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
              <h3 class="fs-7 fw-semibold">Masih ada pertanyaan?</h3>
              <p class="mb-8 text-muted">
                Tim kami siap membantu lewat kanal pengaduan resmi. Setiap laporan ditinjau oleh manusia, bukan bot.
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
