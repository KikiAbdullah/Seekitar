@extends('web.layout')

@section('title', 'Seekitar')
@section('description', 'Cari barang, jasa, dan sewaan dari warga di sekitar ' . config('seekitar.regency') . '. Pasang kebutuhan, terima penawaran, transaksi langsung.')

@push('head')
    {{-- Data terstruktur: membantu mesin pencari menampilkan cuplikan kaya.

         Array disusun di blok kode terlebih dahulu, BUKAN langsung di dalam
         @json(): Blade memotong argumen direktif pada kurung penutup
         pertama, sehingga array multi-baris menghasilkan PHP yang tidak
         bisa di-parse. --}}
    @php
        $jsonLd = [
            '@context'    => 'https://schema.org',
            '@type'       => 'WebSite',
            'name'        => 'Seekitar',
            'url'         => route('web.home'),
            'description' => 'Marketplace hyperlocal dua arah di '.config('seekitar.regency'),
            'inLanguage'  => 'id-ID',
        ];
    @endphp
    <script type="application/ld+json">@json($jsonLd)</script>


    {{--
        Gaya landing page (mengacu template Modernize "frontend-landingpage"
        yang di-recolor Seekitar). Dekorasi latar murni CSS; konten visual
        memakai ilustrasi WebP lokal (< 100 KB per berkas) di public/img/web/
        — halaman tetap ratusan KB sehingga cepat di koneksi lambat.
        Prefiks .lp- menjaganya tidak menabrak gaya halaman lain.
    --}}
@endpush

@section('content')

    {{-- ================================ HERO ================================ --}}
    {{-- Ilustrasi memenuhi SELURUH latar hero (bukan kolom terpisah): teks
         menumpang di atasnya, dilindungi scrim putih yang memudar ke kanan
         supaya keterbacaan tidak bergantung pada sibuk-tidaknya gambar.
         Gradasi hijau .lp-hero tetap di belakang sebagai warna cadangan
         selama gambar dimuat. --}}
    <section class="lp-hero lp-hero-full pt-5 pb-4 pb-lg-5">
        <img src="{{ asset('img/web/hero.webp') }}"
             alt="Ilustrasi warga memakai Seekitar: barang, jasa, dan sewa dari toko sekitar dalam satu genggaman"
             class="lp-hero-bg" width="1100" height="733"
             fetchpriority="high" decoding="async">
        <span class="lp-hero-scrim" aria-hidden="true"></span>

        <div class="container position-relative py-4 py-lg-5">
            <div class="row">
                <div class="col-lg-7 col-xl-6 text-center text-lg-start">
                    <span class="lp-pill mb-3">Marketplace hyperlocal · {{ config('seekitar.regency') }}</span>

                    <h1 class="display-5 fw-bold mb-3 lh-sm">
                        Yang kamu butuhkan,<br class="d-none d-lg-block">
                        ada di sekitar.
                    </h1>

                    <p class="lead text-secondary mb-4" style="max-width: 34rem;">
                        Seekitar menghubungkan warga {{ config('seekitar.regency') }} dengan
                        penjual, penyedia jasa, dan penyewaan terdekat — tanpa perantara.
                    </p>

                    <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-2 mb-4">
                        <a href="#unduh" class="btn btn-seekitar btn-lg px-4">
                            <i class="ti ti-download me-1" aria-hidden="true"></i> Mulai Sekarang
                        </a>
                        <a href="#cara-kerja" class="btn btn-outline-dark btn-lg px-4">
                            Lihat Cara Kerja
                        </a>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start gap-3 mb-5">
                        <span class="lp-avatar-stack" aria-hidden="true">
                            <span style="background: var(--hijau-lokal);">W</span>
                            <span style="background: #B45309;">S</span>
                            <span style="background: #2563EB;">R</span>
                            <span style="background: #4B5563;">D</span>
                        </span>
                        <span class="text-secondary" style="max-width: 22rem;">
                            Dibuat khusus untuk warga dan pelaku usaha {{ config('seekitar.regency') }}.
                        </span>
                    </div>

                    <div class="lp-iconbar pb-lg-2 justify-content-lg-start">
                        <span><i class="ti ti-package" aria-hidden="true"></i>Barang</span>
                        <span><i class="ti ti-tools" aria-hidden="true"></i>Jasa</span>
                        <span><i class="ti ti-key" aria-hidden="true"></i>Sewa</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================ KEUNGGULAN ============================ --}}
    <section id="keunggulan" class="py-5">
        <div class="container py-lg-3">
            <div class="text-center mb-5">
                <div class="fw-bold text-uppercase mb-2" style="color: var(--hijau-lokal); font-size: 13px; letter-spacing: .08em;">
                    Kenapa Seekitar
                </div>
                <h2 class="h3 fw-bold mb-2">Aman dari hulu ke hilir</h2>
                <p class="text-secondary mb-0 mx-auto" style="max-width: 36rem;">
                    Setiap sisi transaksi dirancang agar warga berani bertransaksi
                    dengan orang yang belum dikenal.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="lp-kartu-fitur">
                        <div class="lp-fitur-ikon lp-tone-hijau mb-3" aria-hidden="true">
                            <i class="ti ti-shield-check"></i>
                        </div>
                        <h3 class="h5 fw-bold">Verifikasi Berlapis</h3>
                        <p class="text-secondary mb-0">
                            Nomor HP, KTP, dan NIK ditinjau admin satu per satu.
                            Penjual terverifikasi memakai tanda khusus.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="lp-kartu-fitur">
                        <div class="lp-fitur-ikon lp-tone-kuning mb-3" aria-hidden="true">
                            <i class="ti ti-star"></i>
                        </div>
                        <h3 class="h5 fw-bold">Rating Dua Arah</h3>
                        <p class="text-secondary mb-0">
                            Pembeli dan toko saling menilai 1–5 bintang, jadi
                            reputasi baik terjaga di kedua sisi.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="lp-kartu-fitur">
                        <div class="lp-fitur-ikon lp-tone-biru mb-3" aria-hidden="true">
                            <i class="ti ti-map-pin"></i>
                        </div>
                        <h3 class="h5 fw-bold">Hyperlocal</h3>
                        <p class="text-secondary mb-0">
                            Semua dicari dalam radius kilometer dari lokasimu —
                            dekat berarti cepat, murah, dan bisa dicek langsung.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="lp-kartu-fitur">
                        <div class="lp-fitur-ikon lp-tone-merah mb-3" aria-hidden="true">
                            <i class="ti ti-gavel"></i>
                        </div>
                        <h3 class="h5 fw-bold">Ada Penengah</h3>
                        <p class="text-secondary mb-0">
                            Sengketa ditinjau tim admin dengan batas respons yang
                            jelas — kamu tidak dibiarkan sendirian.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ========================= TIGA LAYANAN ========================= --}}
    <section id="layanan" class="py-5 bg-light">
        <div class="container py-lg-3">
            <div class="text-center mb-5">
                <div class="lp-kicker mb-2">Apa Saja Ada</div>
                <h2 class="h3 fw-bold mb-2">Barang, jasa, dan sewaan</h2>
                <p class="text-secondary mb-0 mx-auto" style="max-width: 36rem;">
                    Bukan katalog kota sebelah — semua ini benar-benar ada
                    di sekitar tempat tinggalmu.
                </p>
            </div>

            <div class="row g-4">
                @foreach ([
                    ['img/web/layanan-barang.webp', 'Ilustrasi etalase warung berisi sembako, sayur segar, dan camilan', 'ti ti-package', 'hijau', 'Barang',
                     'Sembako, sayur kebun tetangga, kue rumahan, sampai barang bekas layak pakai — semua etalase warga sekitar.'],
                    ['img/web/layanan-jasa.webp', 'Ilustrasi tukang memperbaiki pipa air dengan sepeda motor dan kotak perkakas', 'ti ti-tools', 'biru', 'Jasa',
                     'Tukang pipa, servis motor, jahit pakaian, sampai guru les — keahlian warga sekitar yang sudah terverifikasi.'],
                    ['img/web/layanan-sewa.webp', 'Ilustrasi tenda lipat, tumpukan kursi plastik, dan pengeras suara untuk disewa', 'ti ti-key', 'kuning', 'Sewa',
                     'Tenda hajatan, kursi, sound system, sampai alat pertanian — pakai sebentar, bayar seperlunya.'],
                ] as [$berkas, $alt, $ikon, $tone, $judulLayanan, $isi])
                    <div class="col-md-4">
                        <div class="lp-kartu-layanan">
                            <img src="{{ asset($berkas) }}" alt="{{ $alt }}"
                                 width="900" height="600" loading="lazy" decoding="async">
                            <div class="p-4">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="lp-fitur-ikon lp-tone-{{ $tone }}"
                                          style="width: 40px; height: 40px; font-size: 20px;" aria-hidden="true">
                                        <i class="ti {{ $ikon }}"></i>
                                    </span>
                                    <h3 class="h5 fw-bold mb-0">{{ $judulLayanan }}</h3>
                                </div>
                                <p class="text-secondary mb-0">{{ $isi }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================ CARA KERJA ============================ --}}
    <section id="cara-kerja" class="py-5">
        <div class="container py-lg-3">
            <div class="text-center mb-5">
                <div class="fw-bold text-uppercase mb-2" style="color: var(--hijau-lokal); font-size: 13px; letter-spacing: .08em;">
                    Cara Kerja
                </div>
                <h2 class="h3 fw-bold mb-2">Dua arah, satu aplikasi</h2>
                <p class="text-secondary mb-0">Untuk pencari kebutuhan maupun pemilik usaha.</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="h-100 bg-white border rounded-4 p-4">
                        <img src="{{ asset('img/web/pencari.webp') }}"
                             alt="Ilustrasi pencari duduk santai membandingkan tiga penawaran yang masuk di ponselnya"
                             class="lp-foto-kerja mb-4" width="900" height="600"
                             loading="lazy" decoding="async">
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <span class="lp-fitur-ikon lp-tone-biru" style="width: 44px; height: 44px;" aria-hidden="true">
                                <i class="ti ti-shopping-bag"></i>
                            </span>
                            <h3 class="h5 fw-bold mb-0">Untuk Pencari</h3>
                        </div>
                        @foreach ([
                            ['Jelajahi atau pasang kebutuhan', 'Lihat barang dan jasa sekitar — atau tulis kebutuhanmu dan biarkan disiarkan ke toko terdekat.'],
                            ['Bandingkan penawaran', 'Penawaran masuk lengkap dengan harga, ongkos, estimasi, dan rating toko. Pilih yang paling pas.'],
                            ['Transaksi & beri ulasan', 'COD atau transfer, ambil sendiri atau diantar. Selesai, saling beri bintang.'],
                        ] as $i => [$judul, $isi])
                            <div class="d-flex gap-3 {{ $i < 2 ? 'mb-3' : '' }}">
                                <span class="lp-langkah-no" aria-hidden="true">{{ $i + 1 }}</span>
                                <div>
                                    <div class="fw-semibold">{{ $judul }}</div>
                                    <div class="text-secondary" style="font-size: 14px;">{{ $isi }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="h-100 bg-white border rounded-4 p-4">
                        <img src="{{ asset('img/web/penyedia.webp') }}"
                             alt="Ilustrasi pemilik warung menerima penawaran masuk dari ponselnya di depan tokonya"
                             class="lp-foto-kerja mb-4" width="900" height="600"
                             loading="lazy" decoding="async">
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <span class="lp-fitur-ikon lp-tone-hijau" style="width: 44px; height: 44px;" aria-hidden="true">
                                <i class="ti ti-building-store"></i>
                            </span>
                            <h3 class="h5 fw-bold mb-0">Untuk Penyedia</h3>
                        </div>
                        @foreach ([
                            ['Daftar & verifikasi', 'Unggah KTP, isi profil toko dengan foto dan titik lokasi — ditinjau admin maksimal 1×24 jam.'],
                            ['Terima siaran kebutuhan', 'Permintaan warga dalam radiusmu masuk otomatis. Tidak perlu menunggu pembeli datang.'],
                            ['Kirim penawaran & layani', 'Tawar dengan transparan. Pesanan, pengantaran, dan pembayaran terpantau dalam satu aplikasi.'],
                        ] as $i => [$judul, $isi])
                            <div class="d-flex gap-3 {{ $i < 2 ? 'mb-3' : '' }}">
                                <span class="lp-langkah-no" aria-hidden="true">{{ $i + 1 }}</span>
                                <div>
                                    <div class="fw-semibold">{{ $judul }}</div>
                                    <div class="text-secondary" style="font-size: 14px;">{{ $isi }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ========================= PITA STATISTIK ========================= --}}
    <section class="lp-stats py-5">
        <div class="container">
            <div class="row text-center g-4">
                <div class="col-4">
                    <div class="lp-angka">{{ number_format($statistik['toko']) }}</div>
                    <div class="lp-label">Toko Terverifikasi</div>
                </div>
                <div class="col-4">
                    <div class="lp-angka">{{ number_format($statistik['listing']) }}</div>
                    <div class="lp-label">Listing Aktif</div>
                </div>
                <div class="col-4">
                    <div class="lp-angka">{{ number_format($categories->count()) }}</div>
                    <div class="lp-label">Kategori Kebutuhan</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Kategori dari basis data: daftar yang berbeda dari isi aplikasi
         justru merusak kepercayaan. --}}
    @if ($categories->isNotEmpty())
        <section id="kategori" class="py-5">
            <div class="container">
                <div class="text-center mb-4">
                    <h2 class="h3 fw-bold mb-2">Jelajahi kategori</h2>
                    <p class="text-secondary mb-0">Semua kebutuhan sehari-hari, dari yang dijual sampai yang disewakan.</p>
                </div>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    @foreach ($categories as $category)
                        <span class="badge rounded-pill text-bg-light border py-2 px-3 fs-6">
                            {{ $category->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ================================ CTA ================================ --}}
    <section id="unduh" class="pb-5">
        <div class="container">
            <div class="lp-cta text-center px-4 py-5">
                <div class="position-relative">
                    <h2 class="h3 fw-bold mb-3">Mulai dari sekarang</h2>
                    <p class="text-secondary mb-4 mx-auto" style="max-width: 32rem;">
                        Aplikasi Seekitar sedang dalam tahap pengembangan.
                        Ada pertanyaan atau ingin jadi penyedia pertama? Kami siap membantu.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="{{ route('web.contact') }}" class="btn btn-seekitar btn-lg px-4">
                            Hubungi Kami
                        </a>
                        <a href="{{ route('web.help') }}" class="btn btn-outline-dark btn-lg px-4">
                            Pusat Bantuan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
