@extends('web.layout')

@section('title', 'Tentang Seekitar')
@section('description', 'Seekitar adalah marketplace hyperlocal dua arah yang dikunci dalam satu kabupaten.')

@section('content')

    @include('web.partials._hero', [
        'kicker'   => 'Tentang',
        'judul'    => 'Tentang Seekitar',
        'subjudul' => 'Yang kamu butuhkan, ada di sekitar.',
    ])

    {{-- Gagasan inti --}}
    <section class="py-5">
        <div class="container" style="max-width: 860px;">
            <div class="lp-doc mb-4">
                <p class="lead fw-semibold">
                    Seekitar adalah marketplace <span style="color: var(--hijau-lokal);">hyperlocal dua arah</span>
                    yang sengaja dibatasi pada satu wilayah kabupaten — {{ config('seekitar.regency') }}.
                </p>
                <p class="mb-0">
                    Batasan itu bukan kekurangan, melainkan inti produknya: penjual dan pembeli
                    cukup dekat untuk bertemu langsung, dan ongkos kirim tidak menghapus nilai transaksi.
                </p>
            </div>
        </div>
    </section>

    {{-- Dua arah: perbandingan dengan marketplace biasa --}}
    <section class="pb-5">
        <div class="container" style="max-width: 860px;">
            <div class="text-center mb-4">
                <div class="lp-kicker mb-2">Dua arah</div>
                <h2 class="h3 fw-bold mb-2">Bukan sekadar etalase</h2>
                <p class="text-secondary mb-0 mx-auto" style="max-width: 34rem;">
                    Karena itu satu akun bisa menjadi keduanya — pemilik warung yang menjual beras
                    pagi ini bisa mencari tukang servis AC sore nanti, tanpa akun terpisah.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="lp-kartu-fitur">
                        <div class="lp-fitur-ikon lp-tone-abu mb-3" aria-hidden="true">
                            <i class="ti ti-arrow-right"></i>
                        </div>
                        <h3 class="h5 fw-bold">Marketplace biasa</h3>
                        <p class="text-secondary mb-0">
                            Satu arah: penjual memasang, pembeli mencari.
                            Kalau kebutuhanmu tidak ada di katalog, selesai — tidak ada yang tahu.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="lp-kartu-fitur" style="border-color: var(--hijau-lokal);">
                        <div class="lp-fitur-ikon lp-tone-hijau mb-3" aria-hidden="true">
                            <i class="ti ti-arrows-left-right"></i>
                        </div>
                        <h3 class="h5 fw-bold">Seekitar</h3>
                        <p class="text-secondary mb-0">
                            Dua arah: pembeli boleh <strong>memasang kebutuhan</strong>, lalu
                            penyedia terdekat yang mengirim penawaran — bukan sebaliknya saja.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Kenapa satu kabupaten: pencocokan radius dua arah --}}
    <section class="py-5 bg-light">
        <div class="container" style="max-width: 960px;">
            <div class="text-center mb-4">
                <div class="lp-kicker mb-2">Satu kabupaten</div>
                <h2 class="h3 fw-bold mb-2">Pencocokan berlaku dua arah</h2>
                <p class="text-secondary mb-0 mx-auto" style="max-width: 34rem;">
                    Warung dengan jangkauan 5 km tidak akan dibanjiri permintaan
                    dari orang 12 km jauhnya.
                </p>
            </div>

            <div class="row g-4 align-items-stretch">
                <div class="col-md-6">
                    <div class="lp-kartu-fitur text-center">
                        <div class="lp-fitur-ikon lp-tone-biru mb-3 mx-auto" aria-hidden="true">
                            <i class="ti ti-current-location"></i>
                        </div>
                        <h3 class="h6 fw-bold">Radius pilihanmu</h3>
                        <p class="text-secondary mb-0">
                            Kamu menentukan seberapa jauh kebutuhanmu disiarkan —
                            5, 10, atau 15 km dari titikmu.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="lp-kartu-fitur text-center">
                        <div class="lp-fitur-ikon lp-tone-hijau mb-3 mx-auto" aria-hidden="true">
                            <i class="ti ti-building-store"></i>
                        </div>
                        <h3 class="h6 fw-bold">Radius layanan toko</h3>
                        <p class="text-secondary mb-0">
                            Toko pun menetapkan jangkauannya. Permintaan hanya tersambung
                            bila kedua radius saling menaungi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Verifikasi bertingkat --}}
    <section class="py-5">
        <div class="container" style="max-width: 960px;">
            <div class="text-center mb-4">
                <div class="lp-kicker mb-2">Kepercayaan</div>
                <h2 class="h3 fw-bold mb-2">Verifikasi bertingkat</h2>
                <p class="text-secondary mb-0">
                    Membuka toko mensyaratkan tingkat kedua — setiap penjual punya jejak
                    identitas yang bisa dipertanggungjawabkan.
                </p>
            </div>

            <div class="row g-4">
                @foreach ([
                    ['ti-device-mobile', 'hijau', 'Nomor Terverifikasi', 'Masuk dengan kode OTP WhatsApp. Tidak ada kata sandi yang bisa bocor.'],
                    ['ti-id', 'biru', 'Identitas Terverifikasi', 'KTP dan NIK ditinjau admin maksimal 1×24 jam; berkas disimpan privat.'],
                    ['ti-building-store', 'kuning', 'Usaha Terverifikasi', 'Alamat, foto toko, dan titik lokasi dicocokkan dengan peta sebelum toko tayang.'],
                ] as $i => [$ikon, $tone, $judulLangkah, $isi])
                    <div class="col-md-4">
                        <div class="lp-kartu-fitur text-center">
                            <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                                <span class="lp-langkah-no" aria-hidden="true">{{ $i + 1 }}</span>
                                <span class="lp-fitur-ikon lp-tone-{{ $tone }}" style="width: 44px; height: 44px;" aria-hidden="true">
                                    <i class="ti {{ $ikon }}"></i>
                                </span>
                            </div>
                            <h3 class="h6 fw-bold">{{ $judulLangkah }}</h3>
                            <p class="text-secondary mb-0" style="font-size: 14px;">{{ $isi }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-secondary text-center mt-4 mb-0" style="font-size: 14px;">
                Badge verifikasi tidak diperjualbelikan dan bukan jaminan mutlak
                atas kualitas barang atau jasa.
            </p>
        </div>
    </section>

    {{-- CTA kecil --}}
    <section class="pb-5">
        <div class="container" style="max-width: 960px;">
            <div class="lp-cta text-center px-4 py-5">
                <div class="position-relative">
                    <h2 class="h4 fw-bold mb-3">Penasaran alurnya?</h2>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="{{ route('web.home') }}#cara-kerja" class="btn btn-seekitar px-4">Lihat Cara Kerja</a>
                        <a href="{{ route('web.help') }}" class="btn btn-outline-dark px-4">Pusat Bantuan</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
