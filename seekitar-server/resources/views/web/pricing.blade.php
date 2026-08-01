@extends('web.layout')

@section('title', 'Biaya & Harga — Seekitar')
@section('meta_description', 'Rincian biaya penggunaan Seekitar — 100% gratis untuk pembeli dan penjual.')

@section('content')

  @include('web.partials._hero', [
    'kicker'   => 'Informasi Biaya',
    'judul'    => 'Biaya & Harga',
    'subjudul' => 'Transparansi penuh: semua layanan Seekitar saat ini GRATIS tanpa pengecualian.',
    'gambar'   => asset('img/web/harga-baru.jpg'),
    'gambarAlt' => 'Ilustrasi biaya gratis Seekitar',
  ])

  {{-- ============================ BANNER GRATIS ============================ --}}
  <section class="pb-6 pb-lg-8">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8" data-aos="fade-up" data-aos-duration="900">
          <div class="card border-0 shadow-lg bg-primary text-white overflow-hidden position-relative">
            <div class="position-absolute top-0 end-0 opacity-10" style="font-size: 12rem; line-height: 1;">
              <i class="ti ti-wallet"></i>
            </div>
            <div class="card-body p-4 p-lg-6 position-relative z-1 text-center">
              <span class="badge bg-white text-primary mb-3 px-3 py-2 fs-3 rounded-pill">
                <i class="ti ti-circle-check me-1"></i> 100% Gratis
              </span>
              <h2 class="fs-6 fw-bolder mb-3 text-white">Tidak ada biaya. Sama sekali.</h2>
              <p class="fs-5 mb-0 text-white opacity-90">
                Seekitar gratis untuk semua — pembeli maupun penjual. Tidak ada biaya pendaftaran,
                tidak ada biaya langganan, tidak ada komisi transaksi, dan tidak ada biaya tersembunyi.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ============================ BIAYA PEMBELI ============================ --}}
  <section class="pb-6 pb-lg-8">
    <div class="container">
      <div class="row justify-content-center mb-5 mb-lg-7">
        <div class="col-lg-8 text-center" data-aos="fade-up" data-aos-duration="900">
          <span class="eyebrow">Untuk Pembeli</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-2">Sebagai pembeli, kamu tidak dikenakan biaya apa pun</h2>
          <p class="fs-5 text-muted mb-0">Semua fitur pencarian, pemasangan kebutuhan, dan komunikasi 100% gratis.</p>
        </div>
      </div>

      <div class="row g-4">
        @foreach ([
          ['ti ti-user-plus', 'Membuat Akun', 'Daftar dengan nomor WhatsApp — gratis, tanpa kartu kredit.'],
          ['ti ti-search', 'Mencari Barang & Jasa', 'Jelajahi ribuan listing dari toko terverifikasi tanpa batas.'],
          ['ti ti-message-dots', 'Memasang Kebutuhan', 'Umumkan kebutuhanmu dan terima penawaran dari penyedia terdekat — gratis.'],
          ['ti ti-clipboard-check', 'Menerima Penawaran', 'Bandingkan dan pilih penawaran terbaik tanpa biaya tambahan.'],
          ['ti ti-phone', 'Menghubungi Penjual', 'Chat dan telepon langsung dengan penjual — kami tidak memotong komunikasi.'],
          ['ti ti-headset', 'Melaporkan Masalah', 'Layanan mediasi sengketa tersedia gratis untuk semua pengguna.'],
        ] as [$ikon, $judul, $isi])
          <div class="col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="100" data-aos-duration="900">
            <div class="card card-lift border-0 shadow-sm h-100">
              <div class="card-body p-4">
                <span class="icon-soft mb-4">
                  <i class="{{ $ikon }}"></i>
                </span>
                <h5 class="fs-5 fw-semibold mb-2">{{ $judul }}</h5>
                <p class="mb-0 text-muted fs-4">{{ $isi }}</p>
                <span class="badge bg-success-subtle text-success mt-3">Gratis</span>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ============================ BIAYA PENJUAL ============================ --}}
  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row justify-content-center mb-5 mb-lg-7">
        <div class="col-lg-8 text-center" data-aos="fade-up" data-aos-duration="900">
          <span class="eyebrow">Untuk Penjual</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-2">Buka toko dan mulai jualan — gratis selamanya</h2>
          <p class="fs-5 text-muted mb-0">Semua layanan inti untuk penjual tidak dipungut biaya. Tidak ada komisi per transaksi.</p>
        </div>
      </div>

      <div class="row g-4">
        @foreach ([
          ['ti ti-building-store', 'Pendaftaran Toko', 'Daftarkan usahamu — gratis tanpa biaya pendaftaran.'],
          ['ti ti-id', 'Verifikasi Identitas', 'Verifikasi KTP untuk badge terpercaya — gratis selamanya.'],
          ['ti ti-clipboard', 'Memasang Listing', 'Unggah barang, jasa, atau sewa sebanyak yang kamu mau — tanpa batas.'],
          ['ti ti-bell', 'Terima Siaran Kebutuhan', 'Dapatkan notifikasi saat warga mencari produk/layananmu — gratis.'],
          ['ti ti-wallet', 'Komisi Transaksi', 'Tidak ada potongan. Pembayaran 100% langsung dari pembeli ke kamu.'],
          ['ti ti-message-dots', 'Chat dengan Pembeli', 'Berkomunikasi langsung tanpa biaya per pesan.'],
        ] as [$ikon, $judul, $isi])
          <div class="col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="100" data-aos-duration="900">
            <div class="card card-lift border-0 shadow-sm h-100">
              <div class="card-body p-4">
                <span class="feature-icon bg-primary-subtle text-primary mb-4 d-flex">
                  <i class="d-block {{ $ikon }}"></i>
                </span>
                <h5 class="fs-5 fw-semibold mb-2">{{ $judul }}</h5>
                <p class="mb-0 text-muted fs-4">{{ $isi }}</p>
                <span class="badge bg-success-subtle text-success mt-3">Gratis</span>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ============================ RINGKASAN TABEL ============================ --}}
  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-9" data-aos="fade-up" data-aos-duration="900">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5">
              <h3 class="fs-6 fw-bold mb-4 text-center">Ringkasan Biaya Layanan</h3>
              <div class="table-responsive">
                <table class="table table-borderless align-middle mb-0">
                  <thead class="bg-primary-subtle rounded">
                    <tr>
                      <th class="px-3 py-3 rounded-start">Layanan</th>
                      <th class="px-3 py-3 text-center">Untuk Pembeli</th>
                      <th class="px-3 py-3 text-center rounded-end">Untuk Penjual</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ([
                      ['Membuat akun', 'Gratis', 'Gratis'],
                      ['Verifikasi identitas (KTP)', '—', 'Gratis'],
                      ['Mencari / menjelajah listing', 'Gratis', '—'],
                      ['Memasang listing barang / jasa', '—', 'Gratis'],
                      ['Memasang permintaan kebutuhan', 'Gratis', '—'],
                      ['Menerima siaran kebutuhan', '—', 'Gratis'],
                      ['Komisi per transaksi', 'Gratis', 'Gratis'],
                      ['Chat dengan pengguna lain', 'Gratis', 'Gratis'],
                      ['Layanan mediasi sengketa', 'Gratis', 'Gratis'],
                      ['Melaporkan masalah', 'Gratis', 'Gratis'],
                    ] as $baris)
                      <tr class="{{ $loop->last ? '' : 'border-bottom' }}">
                        <td class="px-3 py-3 fw-semibold">{{ $baris[0] }}</td>
                        <td class="px-3 py-3 text-center">
                          <span class="badge bg-success-subtle text-success">{{ $baris[1] }}</span>
                        </td>
                        <td class="px-3 py-3 text-center">
                          <span class="badge bg-success-subtle text-success">{{ $baris[2] }}</span>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ============================ PEMBAYARAN LANGSUNG ============================ --}}
  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row justify-content-center mb-5 mb-lg-7">
        <div class="col-lg-7 text-center" data-aos="fade-up" data-aos-duration="900">
          <span class="eyebrow">Cara Pembayaran</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-0">Pembayaran langsung antar pengguna</h2>
        </div>
      </div>

      <div class="row g-4 justify-content-center">
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="100" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100 text-center">
            <div class="card-body p-4 p-lg-5">
              <span class="icon-soft d-inline-flex align-items-center justify-content-center mb-4" style="width: 72px; height: 72px; font-size: 2rem;">
                <i class="ti ti-cash"></i>
              </span>
              <h5 class="fs-5 fw-semibold mb-2">COD (Bayar di Tempat)</h5>
              <p class="mb-0 text-muted fs-4">Bertemu langsung dengan penjual dan bayar tunai setelah cek barang.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="200" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100 text-center">
            <div class="card-body p-4 p-lg-5">
              <span class="icon-soft d-inline-flex align-items-center justify-content-center mb-4" style="width: 72px; height: 72px; font-size: 2rem;">
                <i class="ti ti-building-bank"></i>
              </span>
              <h5 class="fs-5 fw-semibold mb-2">Transfer Bank</h5>
              <p class="mb-0 text-muted fs-4">Transfer langsung ke rekening penjual. Biaya transfer bank ditanggung pengirim.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="300" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100 text-center">
            <div class="card-body p-4 p-lg-5">
              <span class="icon-soft d-inline-flex align-items-center justify-content-center mb-4" style="width: 72px; height: 72px; font-size: 2rem;">
                <i class="ti ti-shield-check"></i>
              </span>
              <h5 class="fs-5 fw-semibold mb-2">Tanpa Dompet Digital</h5>
              <p class="mb-0 text-muted fs-4">Tidak perlu isi saldo. Tidak perlu menunggu pencairan. Uang langsung ke penjual.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="row justify-content-center mt-5">
        <div class="col-lg-8" data-aos="fade-up" data-aos-delay="150" data-aos-duration="900">
          <div class="alert alert-warning d-flex align-items-start gap-3 mb-0">
            <i class="ti ti-info-circle fs-5 mt-1"></i>
            <div>
              <strong>Satu-satunya biaya yang mungkin kamu keluarkan:</strong> biaya transfer antar bank
              (jika kamu memilih metode transfer). Biaya ini milik bank dan bukan dari Seekitar.
              Seekitar tidak memotong atau membebankan biaya apa pun.
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
          <div class="card c2a-box border-0 shadow-sm" data-aos="fade-up" data-aos-duration="900">
            <div class="card-body text-center p-4 p-lg-8 py-8">
              <h3 class="fs-7 fw-semibold">Gratis. Tanpa syarat tersembunyi.</h3>
              <p class="mb-8 text-muted">
                Mulai jualan atau belanja di {{ config('seekitar.regency') }} sekarang juga — tanpa biaya sepeser pun.
              </p>
              <div class="d-sm-flex align-items-center justify-content-center gap-3">
                <a href="{{ route('web.for-sellers') }}"
                  class="btn btn-primary px-5 d-block mb-3 mb-sm-0 btn-hover-shadow">Buka Toko Gratis</a>
                <a href="{{ route('web.listings') }}"
                  class="btn btn-outline-primary px-5 d-block">Jelajahi Pasar</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
