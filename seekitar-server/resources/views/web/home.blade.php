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
@endpush

@section('content')

    {{-- Hero (BRANDING §6.3) --}}
    <section class="hero py-5">
        <div class="container py-lg-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <h1 class="display-5 fw-bold mb-3">Yang kamu butuhkan, ada di sekitar.</h1>
                    <p class="lead mb-4">
                        Seekitar menghubungkan warga {{ config('seekitar.regency') }} dengan
                        penjual, penyedia jasa, dan penyewaan terdekat — tanpa perantara.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="#unduh" class="btn btn-seekitar btn-lg">Download App</a>
                        <a href="{{ route('web.help') }}" class="btn btn-outline-dark btn-lg">Pelajari Dulu</a>
                    </div>
                </div>
                <div class="col-lg-5 text-center">
                    <div class="bg-white rounded-4 shadow-sm p-4 d-inline-block">
                        <div class="brand-dot mx-auto mb-3" style="width:72px;height:72px;font-size:32px">S</div>
                        <p class="mb-0 fw-semibold">Satu aplikasi,<br>dua arah kebutuhan.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Tiga fitur inti --}}
    <section class="py-5">
        <div class="container">
            <h2 class="h3 fw-bold text-center mb-5">Bagaimana Seekitar bekerja</h2>
            <div class="row g-4">
                @foreach ([
                    ['Jelajahi', 'Lihat barang dan jasa dari toko di radius sekitarmu, lengkap dengan jarak dan rating.'],
                    ['Pasang Kebutuhan', 'Belum ketemu? Tulis kebutuhanmu, dan penyedia terdekat yang akan menghubungi.'],
                    ['Tawarkan', 'Punya usaha? Terima permintaan warga sekitar dan kirim penawaran langsung.'],
                ] as [$judul, $isi])
                    <div class="col-md-4">
                        <div class="h-100 p-4 border rounded-4">
                            <div class="fitur-ikon mb-3" aria-hidden="true">◎</div>
                            <h3 class="h5 fw-bold">{{ $judul }}</h3>
                            <p class="mb-0 text-secondary">{{ $isi }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Kategori diambil dari basis data: daftar yang berbeda dari isi
         aplikasi justru merusak kepercayaan. --}}
    @if ($categories->isNotEmpty())
        <section class="py-5 bg-light">
            <div class="container">
                <h2 class="h3 fw-bold text-center mb-4">Kategori</h2>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    @foreach ($categories as $category)
                        <span class="badge rounded-pill text-bg-light border py-2 px-3">
                            {{ $category->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section id="unduh" class="py-5">
        <div class="container text-center">
            <h2 class="h3 fw-bold mb-3">Mulai dari sekarang</h2>
            <p class="text-secondary mb-4">
                Aplikasi Seekitar sedang dalam tahap pengembangan.
                Tinggalkan pertanyaan lewat kanal kontak kami.
            </p>
            <a href="{{ route('web.contact') }}" class="btn btn-seekitar btn-lg">Hubungi Kami</a>
        </div>
    </section>

@endsection
