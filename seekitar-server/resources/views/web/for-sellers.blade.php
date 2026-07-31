@extends('web.layout')

@section('title', 'Untuk Penjual & Penyedia Jasa')
@section('description', 'Buka toko online gratis di Seekitar. Jangkau pelanggan terdekat tanpa biaya langganan.')

@section('content')

    {{-- ================================ HERO ================================ --}}
    <section class="lp-hero lp-hero-full pt-5 pb-4 pb-lg-5">
        <img src="{{ asset('img/web/penjual-hero.webp') }}"
             alt="Ilustrasi pemilik toko tersenyum di depan etalase digital dengan notifikasi masuk"
             class="lp-hero-bg" width="1100" height="733"
             fetchpriority="high" decoding="async">
        <span class="lp-hero-scrim" aria-hidden="true"></span>

        <div class="container position-relative py-4 py-lg-5">
            <div class="row">
                <div class="col-lg-7 col-xl-6 text-center text-lg-start">
                    <span class="lp-pill mb-3">Gratis · Tanpa komisi</span>
                    <h1 class="display-5 fw-bold mb-3 lh-sm" style="animation: fade-up .6s ease-out .1s both;">
                        Buka toko,<br class="d-none d-lg-block">
                        jangkau tetangga.
                    </h1>
                    <p class="lead mb-4" style="color: var(--teks-secondary); max-width: 34rem; animation: fade-up .6s ease-out .2s both;">
                        Daftarkan usaha dan dapatkan pelanggan baru dari warga sekitar
                        {{ config('seekitar.regency') }} — gratis, tanpa potongan apa pun.
                    </p>
                    <div style="animation: fade-up .6s ease-out .3s both;">
                        <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-2 mb-4">
                            <a href="{{ route('web.home') }}#unduh" class="btn btn-seekitar btn-lg px-4">
                                <i class="ti ti-building-store me-1" aria-hidden="true"></i> Buka Toko Gratis
                            </a>
                            <a href="#benefit" class="btn btn-outline-dark btn-lg px-4">
                                Lihat Keuntungan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================ ANGKA ============================ --}}
    <section class="lp-stats py-5">
        <div class="container">
            <div class="row text-center g-4">
                <div class="col-4">
                    <div class="lp-angka">{{ number_format($stats['toko']) }}</div>
                    <div class="lp-label">Toko Terverifikasi</div>
                </div>
                <div class="col-4">
                    <div class="lp-angka">{{ number_format($stats['listing']) }}</div>
                    <div class="lp-label">Listing Aktif</div>
                </div>
                <div class="col-4">
                    <div class="lp-angka">{{ config('seekitar.regency') }}</div>
                    <div class="lp-label">Wilayah Operasi</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================ BENEFIT ============================ --}}
    <section id="benefit" class="py-5">
        <div class="container py-lg-3">
            <div class="text-center mb-5 sr-reveal">
                <div class="lp-kicker mb-2">Keuntungan</div>
                <h2 class="h3 fw-bold mb-2">Kenapa buka toko di Seekitar?</h2>
                <div class="lp-divider"></div>
            </div>

            <div class="row g-4">
                @foreach ([
                    ['ti ti-currency-dollar', 'hijau', 'Gratis Selamanya',
                     'Tidak ada biaya pendaftaran, biaya langganan, atau komisi. Pembayaran langsung antara kamu dan pembeli — tanpa potongan apa pun.'],
                    ['ti ti-radar-2', 'biru', 'Pelanggan Terdekat',
                     'Tokomu tampil ke warga yang benar-benar di sekitar — dalam radius yang kamu tentukan sendiri. Tidak ada persaingan dengan toko dari kota lain.'],
                    ['ti ti-shield-check', 'kuning', 'Verifikasi Terpercaya',
                     'Badge terverifikasi meningkatkan kepercayaan pembeli. Admin meninjau identitasmu, jadi pembeli tahu kamu nyata.'],
                    ['ti ti-broadcast', 'hijau', 'Siaran Kebutuhan',
                     'Kami kirimkan pemberitahuan saat warga mencari barang/jasa yang kamu tawarkan. Kamu tidak perlu menunggu pembeli datang.'],
                    ['ti ti-phone', 'biru', 'Transaksi Terpantau',
                     'Riwayat pesanan, rating, dan ulasan tercatat rapi. Bila ada masalah, admin siap menengahi.'],
                    ['ti ti-star', 'kuning', 'Rating & Reputasi',
                     'Semakin baik pelayananmu, semakin tinggi ratingmu — dan semakin sering tokumu muncul di pencarian.'],
                    ['ti ti-crown', 'hijau', 'Fitur Premium (Opsional)',
                     'Boost listing agar tokumu tampil di atas, atau langganan Pro untuk prioritas siaran. Fitur berbagai — pakai yang gratis saja juga bisa.'],
                ] as [$ikon, $tone, $judul, $isi])
                    <div class="col-md-6 col-lg-4 sr-reveal">
                        <div class="lp-kartu-fitur">
                            <div class="lp-fitur-ikon lp-tone-{{ $tone }} mb-3" aria-hidden="true">
                                <i class="{{ $ikon }}"></i>
                            </div>
                            <h3 class="h5 fw-bold">{{ $judul }}</h3>
                            <p class="mb-0" style="color: var(--teks-secondary);">{{ $isi }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================ CARA KERJA ============================ --}}
    <section id="cara-kerja" class="py-5 bg-light">
        <div class="container py-lg-3">
            <div class="text-center mb-5 sr-reveal">
                <div class="lp-kicker mb-2">Langkah Mudah</div>
                <h2 class="h3 fw-bold mb-2">Buka toko dalam 4 langkah</h2>
                <div class="lp-divider"></div>
            </div>

            <div class="row g-4">
                @foreach ([
                    ['ti ti-download', 'hijau', '1', 'Unduh & Daftar', 'Unduh aplikasi Seekitar, daftar dengan nomor WhatsApp — hanya perlu 1 menit.'],
                    ['ti ti-id', 'biru', '2', 'Verifikasi Identitas', 'Unggah foto KTP dan swafoto. Admin tinjau maksimal 1×24 jam.'],
                    ['ti ti-building-store', 'kuning', '3', 'Atur Toko', 'Pasang foto toko, tentukan radius layanan, dan atur jam buka.'],
                    ['ti ti-package', 'hijau', '4', 'Listing Barang', 'Upload barang atau jasa yang ingin kamu jual atau sewakan. Selesai!'],
                ] as [$ikon, $tone, $no, $judul, $isi])
                    <div class="col-md-6 col-lg-3 sr-reveal">
                        <div class="lp-kartu-fitur text-center">
                            <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                                <span class="lp-langkah-no" aria-hidden="true">{{ $no }}</span>
                                <span class="lp-fitur-ikon lp-tone-{{ $tone }}"
                                      style="width: 44px; height: 44px;" aria-hidden="true">
                                    <i class="{{ $ikon }}"></i>
                                </span>
                            </div>
                            <h3 class="h6 fw-bold">{{ $judul }}</h3>
                            <p class="mb-0" style="color: var(--teks-secondary); font-size: 14px;">{{ $isi }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================ TESTIMONIAL ============================ --}}
    <section class="py-5">
        <div class="container py-lg-3">
            <div class="text-center mb-5 sr-reveal">
                <div class="lp-kicker mb-2">Testimonial</div>
                <h2 class="h3 fw-bold mb-2">Kata mereka yang sudah bergabung</h2>
                <div class="lp-divider"></div>
            </div>
            <div class="row g-4">
                @foreach ([
                    ['W', 'var(--hijau-lokal)', 'Warung Sembako Ibu Wati', 'Pinggir Jl. Raya Bangil',
                     'Awalnya ragu jualan online, tapi karena pembeli semua warga sini, jadi percaya. Sekarang rata-rata dapat 5 pesanan per hari.'],
                    ['S', '#B45309', 'Servis AC Barokah', 'Perumahan Bumi Asih',
                     'Dulu cari pelanggan dari mulut ke mulut. Sekarang tinggal nunggu notifikasi dari aplikasi. Efisien banget.'],
                    ['R', '#2563EB', 'Sewa Tenda Rizki', 'Ds. Kedungrejo',
                     'Modalnya barang yang sudah ada, nggak perlu biaya iklan. Orang hajatan pada tahu saya dari Seekitar.'],
                ] as [$inisial, $warna, $nama, $lokasi, $kata])
                    <div class="col-md-4 sr-reveal">
                        <div class="lp-kartu-fitur d-flex flex-column">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span style="width: 44px; height: 44px; border-radius: 50%; background: {{ $warna }}; color: #fff; display: grid; place-items: center; font-weight: 700; font-size: 18px;">{{ $inisial }}</span>
                                <div>
                                    <div class="fw-semibold" style="font-size: 14px;">{{ $nama }}</div>
                                    <div style="color: var(--teks-secondary); font-size: 12px;">{{ $lokasi }}</div>
                                </div>
                            </div>
                            <p class="mb-0" style="color: var(--teks-secondary); font-size: 14px; font-style: italic;">"{{ $kata }}"</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================================ CTA ================================ --}}
    <section class="pb-5">
        <div class="container">
            <div class="lp-cta text-center px-4 py-5 sr-reveal">
                <div class="position-relative">
                    <h2 class="h3 fw-bold mb-3">Siap mulai?</h2>
                    <p class="mb-4 mx-auto" style="color: var(--teks-secondary); max-width: 32rem;">
                        Bergabung dengan puluhan penjual lain di {{ config('seekitar.regency') }}.
                        Gratis, tanpa komitmen.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="{{ route('web.home') }}#unduh" class="btn btn-seekitar btn-lg px-4">
                            <i class="ti ti-building-store me-1" aria-hidden="true"></i> Buka Toko Gratis
                        </a>
                        <a href="{{ route('web.pricing') }}" class="btn btn-outline-dark btn-lg px-4">
                            Lihat Detail Biaya
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection