@extends('web.layout')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor/mordenize-lp/libs/owl.carousel/dist/assets/owl.carousel.min.css') }}">
  <style>
    /* ============ KATEGORI ============ */
    .icon-circle {
      width: 68px;
      height: 68px;
      border-radius: 50%;
    }
    .icon-circle-lg {
      width: 80px;
      height: 80px;
      border-radius: 50%;
    }

    /* ============ CARA KERJA ============ */
    .step-num {
      width: 56px;
      height: 56px;
      border-radius: 1.1rem;
      background: var(--bs-primary);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 1.4rem;
      flex-shrink: 0;
    }

    /* ============ LAYANAN ============ */
    .service-thumb {
      height: 210px;
      overflow: hidden;
      border-radius: 1.25rem 1.25rem 0 0;
    }
    .service-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform .5s ease;
    }
    .service-card:hover .service-thumb img {
      transform: scale(1.06);
    }
    .service-icon {
      top: 1rem;
      left: 1rem;
      width: 48px;
      height: 48px;
      border-radius: .9rem;
      background: #fff;
      color: var(--bs-primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.35rem;
      box-shadow: 0 10px 24px -8px rgba(31, 41, 51, .35);
    }

    /* ============ DUA SISI PASAR ============ */
    .side-card .side-thumb {
      height: 230px;
      overflow: hidden;
      border-radius: 1.25rem 1.25rem 0 0;
    }
    .side-card .side-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform .5s ease;
    }
    .side-card:hover .side-thumb img {
      transform: scale(1.05);
    }

    /* ============ COUNTER STATS ============ */
    .stat-card {
      border-left: 4px solid var(--bs-primary);
      transition: all .3s ease;
    }
    .stat-card:hover {
      border-left-color: var(--bs-primary);
      background: var(--bs-primary-bg-subtle);
    }

    /* ============ FITUR DENGAN WARNA BERBEDA ============ */
    .feature-card-accent {
      border-top: 3px solid transparent;
      transition: border-color .3s ease;
    }
    .feature-card-accent:hover {
      border-top-color: var(--bs-primary);
    }
  </style>
@endpush

@push('scripts')
  <script src="{{ asset('vendor/mordenize-lp/libs/jquery/dist/jquery.min.js') }}"></script>
  <script src="{{ asset('vendor/mordenize-lp/libs/owl.carousel/dist/owl.carousel.min.js') }}"></script>
@endpush

@section('title', 'Seekitar — Pasar Lokal ' . config('seekitar.regency'))
@section('meta_description', 'Beli dan jual barang, jasa, serta sewa di sekitar Anda. Gratis, tanpa komisi, hanya di ' . config('seekitar.regency') . '.')

@section('content')

  {{-- ================================ HERO ================================ --}}
  <section class="hero-wrap position-relative overflow-hidden" id="beranda">
    <div class="container position-relative z-2">
      <div class="row align-items-center g-5 pt-13 pb-11 pt-lg-13 pb-lg-12 pt-xl-13 pb-xl-13">
        <div class="col-lg-6" data-aos="fade-right" data-aos-duration="1000">
          <span class="eyebrow d-inline-flex align-items-center">Pasar Lokal Satu Kabupaten</span>
          <h1 class="fw-bolder mt-4 mb-4 fs-11 lh-sm">
            Yang kamu butuhkan,<br>
            <span class="text-primary">ada di sekitar.</span>
          </h1>
          <p class="fs-5 text-muted mb-5">
            Temukan barang, jasa, dan usaha dari tetangga terdekatmu.
            Buka toko gratis, tanpa komisi, tanpa langganan.
          </p>
          <div class="d-sm-flex align-items-center gap-3 mb-5">
            <a href="{{ route('web.listings') }}" class="btn btn-primary btn-lg px-5 btn-hover-shadow">Jelajahi Sekarang</a>
            <a href="#fitur" class="btn btn-outline-primary btn-lg px-5 scroll-link">Lihat Keuntungan</a>
          </div>
          <div class="d-flex flex-wrap align-items-center gap-4 pt-4 border-top">
            <div class="d-flex align-items-center gap-2">
              <span class="icon-soft"><i class="ti ti-wallet"></i></span>
              <div>
                <div class="fw-bold fs-5 lh-sm">Gratis</div>
                <div class="fs-2 text-muted">tanpa biaya apa pun</div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="icon-soft"><i class="ti ti-map-pin"></i></span>
              <div>
                <div class="fw-bold fs-5 lh-sm">{{ config('seekitar.regency') }}</div>
                <div class="fs-2 text-muted">fokus satu kabupaten</div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="icon-soft"><i class="ti ti-shield-check"></i></span>
              <div>
                <div class="fw-bold fs-5 lh-sm">Terpercaya</div>
                <div class="fs-2 text-muted">toko terverifikasi</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="150">
          <div class="hero-figure ms-lg-4">
            <img src="{{ asset('img/web/hero.webp') }}" class="hero-img" alt="Pasar lokal Seekitar di sekitar Anda" fetchpriority="high" decoding="async">
            <div class="hero-badge badge-top">
              <span class="badge-icon bg-primary-subtle text-primary"><i class="ti ti-shield-check"></i></span>
              <div>
                <div class="num">{{ number_format($statistik['toko']) }}</div>
                <div class="lbl">toko terverifikasi</div>
              </div>
            </div>
            <div class="hero-badge badge-bottom">
              <span class="badge-icon bg-light-secondary text-secondary"><i class="ti ti-tags"></i></span>
              <div>
                <div class="num">{{ number_format($statistik['listing']) }}</div>
                <div class="lbl">listing aktif</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ============================ STATISTIK UTAMA ============================ --}}
  <section class="pb-8 pb-lg-11" id="statistik">
    <div class="container">
      <div class="row g-4 justify-content-center" data-aos="fade-up" data-aos-duration="1000">
        <div class="col-sm-6 col-lg-3">
          <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <span class="icon-soft"><i class="ti ti-building-store"></i></span>
              <div>
                <div class="stat-num">{{ number_format($statistik['toko']) }}</div>
                <div class="text-muted">toko terverifikasi</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <span class="icon-soft"><i class="ti ti-shopping-bag"></i></span>
              <div>
                <div class="stat-num">{{ number_format($statistik['listing']) }}</div>
                <div class="text-muted">listing aktif</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <span class="icon-soft"><i class="ti ti-category"></i></span>
              <div>
                <div class="stat-num">{{ $categories->count() }}</div>
                <div class="text-muted">kategori utama</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <span class="icon-soft"><i class="ti ti-map-pin"></i></span>
              <div>
                <div class="stat-num">1</div>
                <div class="text-muted">kabupaten, fokus penuh</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ============================ LAYANAN ============================ --}}
  <section class="pb-8 pb-lg-11" id="layanan">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-7 text-center" data-aos="fade-up" data-aos-duration="1000">
          <span class="eyebrow">Apa yang bisa kamu temukan</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-2">Satu pasar untuk semua kebutuhan</h2>
          <p class="fs-5 text-muted mb-0">Barang, jasa, dan sewa — semuanya dari warga sekitar.</p>
        </div>
      </div>
      <div class="row g-4">
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="100" data-aos-duration="1000">
          <div class="card service-card card-lift border-0 shadow-sm h-100 overflow-hidden position-relative">
            <div class="service-thumb position-relative">
              <img src="{{ asset('img/web/layanan-barang.webp') }}" alt="Jual beli barang" loading="lazy">
              <span class="service-icon position-absolute"><i class="ti ti-shopping-bag"></i></span>
            </div>
            <div class="card-body p-4">
              <h5 class="fs-5 fw-semibold mb-2">Barang</h5>
              <p class="mb-3 text-muted fs-4">Kebutuhan harian, peralatan rumah, elektronik, fashion, hingga barang bekas yang masih layak pakai.</p>
              <a href="{{ route('web.listings', ['type' => 'product']) }}" class="stretched-link fw-semibold text-primary text-decoration-none">
                Jelajahi Barang <i class="ti ti-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="250" data-aos-duration="1000">
          <div class="card service-card card-lift border-0 shadow-sm h-100 overflow-hidden position-relative">
            <div class="service-thumb position-relative">
              <img src="{{ asset('img/web/layanan-jasa.webp') }}" alt="Jasa rumah dan bengkel" loading="lazy">
              <span class="service-icon position-absolute"><i class="ti ti-tool"></i></span>
            </div>
            <div class="card-body p-4">
              <h5 class="fs-5 fw-semibold mb-2">Jasa</h5>
              <p class="mb-3 text-muted fs-4">Tukang bangunan, servis AC, kebersihan, bengkel motor, les privat, fotografer, dan banyak lagi.</p>
              <a href="{{ route('web.listings', ['type' => 'service']) }}" class="stretched-link fw-semibold text-primary text-decoration-none">
                Jelajahi Jasa <i class="ti ti-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="400" data-aos-duration="1000">
          <div class="card service-card card-lift border-0 shadow-sm h-100 overflow-hidden position-relative">
            <div class="service-thumb position-relative">
              <img src="{{ asset('img/web/layanan-sewa.webp') }}" alt="Sewa tenda dan peralatan" loading="lazy">
              <span class="service-icon position-absolute"><i class="ti ti-calendar-month"></i></span>
            </div>
            <div class="card-body p-4">
              <h5 class="fs-5 fw-semibold mb-2">Sewa</h5>
              <p class="mb-3 text-muted fs-4">Tenda hajatan, kursi, sound system, kendaraan, dan perlengkapan acara dari penyedia terdekat.</p>
              <a href="{{ route('web.listings', ['type' => 'rental']) }}" class="stretched-link fw-semibold text-primary text-decoration-none">
                Jelajahi Sewa <i class="ti ti-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ============================ KATEGORI ============================ --}}
  <section class="bg-light py-8 py-lg-11" id="kategori">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-8 col-xxl-6 text-center" data-aos="fade-up" data-aos-duration="1000">
          <span class="eyebrow">Jelajahi berdasarkan kategori</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-2">Semua kebutuhan ada kategorinya</h2>
          <p class="fs-5 text-muted mb-0">
            {{ $categories->count() }} kategori utama dengan {{ $categories->sum('children_count') }} subkategori siap kamu jelajahi.
          </p>
        </div>
      </div>
      <div class="row g-4">
        @forelse ($categories as $kategori)
          <div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="100" data-aos-duration="1000">
            <div class="card card-category card-lift h-100 border-0 shadow-sm position-relative">
              <div class="card-body text-center p-4">
                <span class="icon-circle d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary mb-4">
                  <i class="d-block {{ $kategori->tablerIcon() }} fs-5"></i>
                </span>
                <h5 class="fs-5 fw-semibold mb-1">{{ $kategori->name }}</h5>
                <p class="mb-0 fs-3 text-muted">
                  {{ $kategori->children_count }} subkategori
                </p>
                <a href="{{ route('web.listings', ['category' => $kategori->id]) }}"
                  class="stretched-link" aria-label="Lihat listing kategori {{ $kategori->name }}"></a>
              </div>
            </div>
          </div>
        @empty
          <div class="col-12 text-center">
            <div class="card border-0 shadow-sm">
              <div class="card-body p-5">
                <span class="icon-soft d-inline-flex align-items-center justify-content-center mb-3">
                  <i class="ti ti-category"></i>
                </span>
                <p class="mb-0 text-muted">Kategori sedang disiapkan. Kembali lagi nanti!</p>
              </div>
            </div>
          </div>
        @endforelse
      </div>
    </div>
  </section>

  {{-- ============================ CARA KERJA ============================ --}}
  <section class="py-8 py-lg-11" id="cara-kerja">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-7 text-center" data-aos="fade-up" data-aos-duration="1000">
          <span class="eyebrow">Bagaimana cara kerja</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-0">Mulai hanya dalam tiga langkah</h2>
        </div>
      </div>
      <div class="row g-4 align-items-stretch">
        @foreach ([
          ['Buka toko gratis', 'Daftar dengan nomor HP, lengkapi profil toko, dan serahkan dokumen untuk verifikasi identitas. Admin meninjau maksimal 1×24 jam.'],
          ['Pasang listing', 'Unggah barang, jasa, atau sewa dengan foto dan harga. Tentukan sendiri radius jangkauan pelayananmu.'],
          ['Transaksi & rating', 'Terima pesanan dari warga terdekat, selesaikan transaksi langsung, dan bangun reputasi dari rating pembeli.'],
        ] as [$judul, $isi])
          <div class="col-md-4" data-aos="fade-up" data-aos-delay="100" data-aos-duration="1000">
            <div class="card card-lift border-0 shadow-sm h-100">
              <div class="card-body p-4">
                <span class="step-num mb-4">{{ $loop->iteration }}</span>
                <h5 class="fs-5 fw-semibold mb-2">{{ $judul }}</h5>
                <p class="mb-0 text-muted fs-4">{{ $isi }}</p>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ============================ DUA SISI PASAR ============================ --}}
  <section class="bg-light py-8 py-lg-11" id="komunitas">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-7 text-center" data-aos="fade-up" data-aos-duration="1000">
          <span class="eyebrow">Dua sisi, satu pasar</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-0">Dibangun untuk kamu semua</h2>
        </div>
      </div>
      <div class="row g-4 g-lg-5 align-items-stretch">
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100" data-aos-duration="1000">
          <div class="card side-card card-lift border-0 shadow-sm h-100 overflow-hidden">
            <div class="side-thumb">
              <img src="{{ asset('img/web/pencari.webp') }}" alt="Untuk pembeli dan pencari kebutuhan" loading="lazy">
            </div>
            <div class="card-body p-4 p-lg-5">
              <h4 class="fs-6 fw-bold mb-3">Untuk Pembeli</h4>
              <ul class="list-unstyled mb-4 d-grid gap-2">
                <li class="check-item">
                  <i class="ti ti-circle-check"></i>
                  <span class="text-dark">Cari barang, jasa, dan sewa dari toko terverifikasi di sekitarmu.</span>
                </li>
                <li class="check-item">
                  <i class="ti ti-circle-check"></i>
                  <span class="text-dark">Bandingkan harga dan penjual terdekat tanpa perlu keluar rumah.</span>
                </li>
                <li class="check-item">
                  <i class="ti ti-circle-check"></i>
                  <span class="text-dark">Umumkan kebutuhan dan terima penawaran dari penyedia terdekat.</span>
                </li>
                <li class="check-item">
                  <i class="ti ti-circle-check"></i>
                  <span class="text-dark">Bayar langsung ke penjual — tunai atau transfer, tanpa biaya tambahan.</span>
                </li>
              </ul>
              <a href="{{ route('web.listings') }}" class="btn btn-outline-primary px-4">Jelajahi Pasar</a>
            </div>
          </div>
        </div>
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="250" data-aos-duration="1000">
          <div class="card side-card card-lift border-0 shadow-sm h-100 overflow-hidden">
            <div class="side-thumb">
              <img src="{{ asset('img/web/penyedia.webp') }}" alt="Untuk penjual dan penyedia jasa" loading="lazy">
            </div>
            <div class="card-body p-4 p-lg-5">
              <h4 class="fs-6 fw-bold mb-3">Untuk Penjual</h4>
              <ul class="list-unstyled mb-4 d-grid gap-2">
                <li class="check-item">
                  <i class="ti ti-circle-check"></i>
                  <span class="text-dark">Buka toko gratis selamanya — tanpa komisi dan tanpa biaya langganan.</span>
                </li>
                <li class="check-item">
                  <i class="ti ti-circle-check"></i>
                  <span class="text-dark">Dapat notifikasi saat warga sekitar mencari produk atau jasamu.</span>
                </li>
                <li class="check-item">
                  <i class="ti ti-circle-check"></i>
                  <span class="text-dark">Badge verifikasi membangun kepercayaan pembeli terhadap tokomu.</span>
                </li>
                <li class="check-item">
                  <i class="ti ti-circle-check"></i>
                  <span class="text-dark">Pembayaran diterima 100% — tidak ada potongan dari Seekitar.</span>
                </li>
              </ul>
              <a href="{{ route('web.for-sellers') }}" class="btn btn-primary px-4 btn-hover-shadow">Buka Toko Gratis</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ============================ FITUR UNGGULAN ============================ --}}
  <section class="py-8 py-lg-11" id="fitur">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-6 text-center" data-aos="fade-up" data-aos-duration="1000">
          <span class="eyebrow">Kenapa Seekitar</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-0">Keuntungan yang benar-benar kamu rasakan</h2>
        </div>
      </div>
      <div class="row g-4">
        @foreach ([
          ['ti ti-wallet', 'Gratis Selamanya', 'Tidak ada biaya pendaftaran, langganan, atau komisi. Pembayaran langsung antara kamu dan penjual — tanpa potongan.', 'bg-primary-subtle text-primary'],
          ['ti ti-map', 'Pelanggan Terdekat', 'Tokomu tampil ke warga yang benar-benar di sekitar — dalam radius yang kamu tentukan sendiri.', 'bg-success-subtle text-success'],
          ['ti ti-shield-check', 'Verifikasi Terpercaya', 'Badge terverifikasi meningkatkan kepercayaan pembeli. Admin meninjau identitas setiap penjual.', 'bg-info-subtle text-info'],
          ['ti ti-bell', 'Notifikasi Kebutuhan', 'Dapatkan pemberitahuan saat warga mencari barang atau jasa yang kamu tawarkan.', 'bg-warning-subtle text-warning'],
          ['ti ti-message-dots', 'Transaksi Terpantau', 'Riwayat pesanan, rating, dan ulasan tercatat rapi. Admin siap menengahi bila ada masalah.', 'bg-danger-subtle text-danger'],
          ['ti ti-star', 'Rating & Reputasi', 'Semakin baik pelayananmu, semakin tinggi ratingmu — dan semakin sering tokomu muncul di pencarian.', 'bg-primary-subtle text-primary'],
        ] as [$ikon, $judul, $isi, $warna])
          <div class="col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="100" data-aos-duration="1000">
            <div class="card feature-card-accent card-lift border-0 shadow-sm h-100">
              <div class="card-body p-4">
                <span class="feature-icon {{ $warna }} mb-4 d-flex">
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

  {{-- ============================ TESTIMONI ============================ --}}
  <section class="bg-light py-8 py-lg-11" id="testimoni">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-8 text-center" data-aos="fade-up" data-aos-duration="1000">
          <span class="eyebrow">Testimoni</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-0">Kata mereka yang sudah bergabung</h2>
        </div>
      </div>
      <div class="review-slider" data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
        <div class="owl-carousel owl-theme">
          @forelse ($testimoni as $t)
            <div class="item">
              <div class="card card-lift border-0 shadow-sm h-100">
                <div class="card-body p-4">
                  <div class="d-flex justify-content-between mb-4">
                    <div class="d-flex align-items-center">
                      <span class="me-3 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; font-weight: 700;">{{ $t['inisial'] }}</span>
                      <div>
                        <h6 class="fs-4 mb-1 fw-semibold">{{ $t['nama'] }}</h6>
                        <p class="mb-0 text-muted fs-3">{{ $t['toko'] }}</p>
                      </div>
                    </div>
                    <div>
                      <ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
                        @for ($i = 0; $i < 5; $i++)
                          <li @if ($i >= $t['rating']) style="opacity: .25;" @endif>
                            <i class="ti ti-star-filled text-warning"></i>
                          </li>
                        @endfor
                      </ul>
                    </div>
                  </div>
                  <p class="fs-4 mb-0 text-dark">"{{ $t['kata'] }}"</p>
                </div>
              </div>
            </div>
          @empty
            <div class="item">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4 text-center">
                  <p class="mb-0 text-muted">Ulasan asli akan segera tampil di sini.</p>
                </div>
              </div>
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </section>

  {{-- ============================ CTA ============================ --}}
  <section class="pb-8 pb-lg-11">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card c2a-box border-0 shadow-sm" data-aos="fade-up" data-aos-duration="1000">
            <div class="card-body text-center p-4 p-lg-8 py-8">
              <h3 class="fs-7 fw-semibold">Siap mulai?</h3>
              <p class="mb-8 text-muted">
                Bergabung dengan {{ number_format($statistik['toko']) }} penjual lain di {{ config('seekitar.regency') }}. Gratis, tanpa komitmen.
              </p>
              <div class="d-sm-flex align-items-center justify-content-center gap-3 mb-4">
                <a href="{{ route('web.for-sellers') }}"
                  class="btn btn-primary px-5 d-block mb-3 mb-sm-0 btn-hover-shadow">Buka Toko Gratis</a>
                <a href="{{ route('web.pricing') }}" class="btn btn-outline-secondary px-5 d-block">Lihat Detail Biaya</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
