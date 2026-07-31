@extends('web.layout')

@section('title', 'Untuk Penjual & Penyedia Jasa — Seekitar')
@section('meta_description', 'Buka toko online gratis di Seekitar. Jangkau pelanggan terdekat tanpa biaya langganan.')

@section('content')

  @include('web.partials._hero', [
    'kicker'   => 'Gratis · Tanpa komisi',
    'judul'    => 'Buka toko, jangkau tetangga',
    'subjudul' => 'Daftarkan usaha dan dapatkan pelanggan baru dari warga sekitar ' . config('seekitar.regency') . ' — gratis, tanpa potongan apa pun.',
    'gambar'   => asset('img/web/penyedia.webp'),
    'gambarAlt' => 'Ilustrasi penjual dan penyedia jasa di pasar lokal Seekitar',
  ])

  {{-- ============================ ANGKA ============================ --}}
  <section class="pb-8 pb-lg-11">
    <div class="container">
      <div class="row g-4 justify-content-center" data-aos="fade-up" data-aos-duration="900">
        <div class="col-sm-6 col-lg-4">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <span class="icon-soft"><i class="ti ti-building-store"></i></span>
              <div>
                <div class="stat-num">{{ number_format($stats['toko']) }}</div>
                <div class="text-muted">toko terverifikasi</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-4">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <span class="icon-soft"><i class="ti ti-tags"></i></span>
              <div>
                <div class="stat-num">{{ number_format($stats['listing']) }}</div>
                <div class="text-muted">listing aktif</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-4">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <span class="icon-soft"><i class="ti ti-map-pin"></i></span>
              <div>
                <div class="stat-num">{{ config('seekitar.regency') }}</div>
                <div class="text-muted">wilayah operasi</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ============================ KEUNTUNGAN ============================ --}}
  <section class="pb-8 pb-lg-11" id="benefit">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-7 text-center" data-aos="fade-up" data-aos-duration="900">
          <span class="eyebrow">Keuntungan</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-0">Kenapa buka toko di Seekitar?</h2>
        </div>
      </div>
      <div class="row g-4">
        @foreach ([
          ['ti ti-wallet', 'Gratis Selamanya', 'Tidak ada biaya pendaftaran, biaya langganan, atau komisi. Pembayaran langsung antara kamu dan pembeli — tanpa potongan apa pun.'],
          ['ti ti-map', 'Pelanggan Terdekat', 'Tokomu tampil ke warga yang benar-benar di sekitar — dalam radius yang kamu tentukan sendiri. Tidak ada persaingan dengan toko dari kota lain.'],
          ['ti ti-shield-check', 'Verifikasi Terpercaya', 'Badge terverifikasi meningkatkan kepercayaan pembeli. Admin meninjau identitasmu, jadi pembeli tahu kamu nyata.'],
          ['ti ti-bell', 'Siaran Kebutuhan', 'Kami kirimkan pemberitahuan saat warga mencari barang/jasa yang kamu tawarkan. Kamu tidak perlu menunggu pembeli datang.'],
          ['ti ti-message-dots', 'Transaksi Terpantau', 'Riwayat pesanan, rating, dan ulasan tercatat rapi. Bila ada masalah, admin siap menengahi.'],
          ['ti ti-star', 'Rating & Reputasi', 'Semakin baik pelayananmu, semakin tinggi ratingmu — dan semakin sering tokumu muncul di pencarian.'],
          ['ti ti-gem', 'Fitur Premium (Opsional)', 'Boost listing agar tokumu tampil di atas, atau langganan Pro untuk prioritas siaran. Fitur berbagai — pakai yang gratis saja juga bisa.'],
        ] as [$ikon, $judul, $isi])
          <div class="col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="100" data-aos-duration="900">
            <div class="card card-lift border-0 shadow-sm h-100">
              <div class="card-body p-4">
                <span class="feature-icon bg-primary-subtle text-primary mb-4 d-flex">
                  <i class="d-block {{ $ikon }}"></i>
                </span>
                <h5 class="fs-5 fw-semibold mb-2">{{ $judul }}</h5>
                <p class="mb-0 text-muted fs-4">{{ $isi }}</p>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ============================ CARA KERJA ============================ --}}
  <section class="bg-light py-8 py-lg-11" id="cara-kerja">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-7 text-center" data-aos="fade-up" data-aos-duration="900">
          <span class="eyebrow">Langkah Mudah</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-0">Buka toko dalam 4 langkah</h2>
        </div>
      </div>
      <div class="row g-4 align-items-stretch">
        @foreach ([
          ['ti ti-download', 'Unduh & Daftar', 'Unduh aplikasi Seekitar, daftar dengan nomor WhatsApp — hanya perlu 1 menit.'],
          ['ti ti-id', 'Verifikasi Identitas', 'Unggah foto KTP dan swafoto. Admin tinjau maksimal 1×24 jam.'],
          ['ti ti-building-store', 'Atur Toko', 'Pasang foto toko, tentukan radius layanan, dan atur jam buka.'],
          ['ti ti-clipboard', 'Listing Barang', 'Upload barang atau jasa yang ingin kamu jual atau sewakan. Selesai!'],
        ] as [$ikon, $judul, $isi])
          <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100" data-aos-duration="900">
            <div class="card card-lift border-0 shadow-sm h-100">
              <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                <span class="icon-soft mb-4" style="width: 60px; height: 60px; font-size: 1.5rem;">
                  <i class="{{ $ikon }}"></i>
                </span>
                <div class="fs-3 text-muted fw-bold mb-1">Langkah {{ $loop->iteration }}</div>
                <h5 class="fs-5 fw-semibold mb-2">{{ $judul }}</h5>
                <p class="mb-0 text-muted fs-4">{{ $isi }}</p>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ============================ TESTIMONI ============================ --}}
  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-7 text-center" data-aos="fade-up" data-aos-duration="900">
          <span class="eyebrow">Testimoni</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-0">Kata mereka yang sudah bergabung</h2>
        </div>
      </div>
      <div class="row g-4">
        @foreach ([
          ['W', 'Warung Sembako Ibu Wati', 'Pinggir Jl. Raya Bangil', 'Awalnya ragu jualan online, tapi karena pembeli semua warga sini, jadi percaya. Sekarang rata-rata dapat 5 pesanan per hari.'],
          ['S', 'Servis AC Barokah', 'Perumahan Bumi Asih', 'Dulu cari pelanggan dari mulut ke mulut. Sekarang tinggal nunggu notifikasi dari aplikasi. Efisien banget.'],
          ['R', 'Sewa Tenda Rizki', 'Ds. Kedungrejo', 'Modalnya barang yang sudah ada, nggak perlu biaya iklan. Orang hajatan pada tahu saya dari Seekitar.'],
        ] as [$inisial, $nama, $lokasi, $kata])
          <div class="col-md-4" data-aos="fade-up" data-aos-delay="150" data-aos-duration="900">
            <div class="card card-lift border-0 shadow-sm h-100">
              <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                  <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                    style="width: 44px; height: 44px; font-weight: 700;">{{ $inisial }}</span>
                  <div>
                    <div class="fw-semibold">{{ $nama }}</div>
                    <div class="fs-3 text-muted">{{ $lokasi }}</div>
                  </div>
                </div>
                <p class="mb-0 text-muted fs-4 fst-italic">"{{ $kata }}"</p>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ================================ CTA ================================ --}}
  <section class="pb-8 pb-lg-11">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card c2a-box border-0 shadow-sm" data-aos="fade-up" data-aos-duration="900">
            <div class="card-body text-center p-4 p-lg-8 py-8">
              <h3 class="fs-7 fw-semibold">Siap mulai?</h3>
              <p class="mb-8 text-muted">
                Bergabung dengan penjual lain di {{ config('seekitar.regency') }}.
                Gratis, tanpa komitmen.
              </p>
              <div class="d-sm-flex align-items-center justify-content-center gap-3 mb-4">
                <a href="{{ route('web.help') }}"
                  class="btn btn-primary px-5 d-block mb-3 mb-sm-0 btn-hover-shadow">Buka Toko Gratis</a>
                <a href="{{ route('web.pricing') }}"
                  class="btn btn-outline-secondary px-5 d-block">Lihat Detail Biaya</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
