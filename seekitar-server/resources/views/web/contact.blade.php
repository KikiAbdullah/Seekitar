@extends('web.layout')

@section('title', 'Kontak & Pengaduan')
@section('description', 'Kanal pengaduan resmi Seekitar beserta tenggat tanggapannya.')

@section('content')

    @include('web.partials._hero', [
        'kicker'    => 'Kontak & Pengaduan',
        'judul'     => 'Kontak & Pengaduan',
        'subjudul'  => 'Setiap kanal punya tenggat tanggapan yang mengikat. Sebutkan nomor pesanan bila laporanmu terkait transaksi.',
        'gambar'    => 'img/web/kontak.webp',
        'gambarAlt' => 'Ilustrasi kotak surat dengan amplop masuk, dikelilingi lencana pelaporan, keamanan, privasi, dan tenggat waktu',
    ])

    <section class="py-5">
        <div class="container" style="max-width: 960px;">

            {{-- Empat kanal email --}}
            <div class="row g-4 mb-4">
                @foreach ([
                    ['fa-regular fa-envelope', 'hijau', 'Pengaduan umum', 'complaint', '2×24 jam', 'Laporkan masalah terkait transaksi, akun, atau fitur.'],
                    ['fa-regular fa-flag', 'merah', 'Pelaporan konten ilegal', 'abuse', '1×24 jam', 'Laporkan barang/jasa terlarang, penipuan, atau penyalahgunaan.'],
                    ['fa-regular fa-circle-stop', 'biru', 'Data pribadi (UU PDP)', 'privacy', '3×24 jam', 'Akses, koreksi, atau penghapusan data pribadi.'],
                    ['fa-regular fa-circle-check', 'kuning', 'Celah keamanan', 'security', '1×24 jam', 'Laporkan kerentanan atau insiden keamanan sistem.'],
                ] as $i => [$ikon, $tone, $keperluan, $kunci, $tenggat, $deskripsi])
                    <div class="col-md-6 sr-reveal sr-reveal-delay-{{ $i + 1 }}">
                        <div class="lp-kanal">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                <span class="lp-fitur-ikon lp-tone-{{ $tone }}" aria-hidden="true">
                                    <i class="{{ $ikon }}"></i>
                                </span>
                                <span class="badge text-bg-light border" style="font-size: 12px;">≤ {{ $tenggat }}</span>
                            </div>
                            <h2 class="h6 fw-bold mb-1">{{ $keperluan }}</h2>
                            <p class="mb-2" style="color: var(--teks-secondary); font-size: 13px;">{{ $deskripsi }}</p>
                            <a href="mailto:{{ config('seekitar.contacts.' . $kunci) }}"
                               class="stretched-link text-decoration-none fw-semibold"
                               style="color: var(--hijau-lokal); word-break: break-all;">
                                {{ config('seekitar.contacts.' . $kunci) }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Sengketa transaksi --}}
            <div class="lp-kanal mb-4 sr-reveal" style="border-color: var(--kuning); background: #FFFBEB;">
                <div class="d-flex gap-3">
                    <span class="lp-fitur-ikon lp-tone-kuning flex-shrink-0" aria-hidden="true">
                        <i class="fa-regular fa-file-lines"></i>
                    </span>
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h2 class="h6 fw-bold mb-0">Sengketa transaksi</h2>
                            <span class="badge text-bg-light border" style="font-size: 12px;">≤ 1×24 jam</span>
                        </div>
                        <p class="mb-0" style="font-size: 14px;">
                            <strong>Laporkan lewat aplikasi: buka pesanan → Laporkan Masalah.</strong>
                            Sebaiknya bukan lewat email — laporan dari aplikasi otomatis membekukan
                            pesanan, sehingga statusnya tidak bisa berubah sampai admin memutuskan.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Penyelenggara --}}
            <div class="lp-kanal sr-reveal">
                <div class="d-flex gap-3">
                    <span class="lp-fitur-ikon lp-tone-abu flex-shrink-0" aria-hidden="true">
                        <i class="fa-regular fa-building"></i>
                    </span>
                    <div>
                        <h2 class="h6 fw-bold mb-1">Penyelenggara</h2>
                        <p class="mb-0" style="font-size: 14px;">
                            {{ config('seekitar.company.name') }}<br>
                            {{ config('seekitar.company.address') }}
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </section>

@endsection