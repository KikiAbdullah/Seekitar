@extends('web.layout')

@section('title', 'Kontak & Pengaduan')
@section('description', 'Kanal pengaduan resmi Seekitar beserta tenggat tanggapannya.')

@section('content')

    @include('web.partials._hero', [
        'kicker'   => 'Kontak & Pengaduan',
        'judul'    => 'Kontak & Pengaduan',
        'subjudul' => 'Setiap kanal punya tenggat tanggapan yang mengikat. Sebutkan nomor pesanan bila laporanmu terkait transaksi.',
    ])

    <section class="py-5">
        <div class="container" style="max-width: 960px;">

            {{-- Empat kanal email. Seluruh kartu bisa diklik (stretched-link):
                 target sentuh lebih besar dari sekadar teks alamatnya. --}}
            <div class="row g-4 mb-4">
                @foreach ([
                    ['ti ti-mail', 'hijau', 'Pengaduan umum', 'complaint', '2×24 jam'],
                    ['ti ti-flag', 'merah', 'Pelaporan konten ilegal', 'abuse', '1×24 jam'],
                    ['ti ti-lock', 'biru', 'Data pribadi (UU PDP)', 'privacy', '3×24 jam'],
                    ['ti ti-shield', 'kuning', 'Celah keamanan', 'security', '1×24 jam'],
                ] as [$ikon, $tone, $keperluan, $kunci, $tenggat])
                    <div class="col-md-6">
                        <div class="lp-kanal">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                <span class="lp-fitur-ikon lp-tone-{{ $tone }}" aria-hidden="true">
                                    <i class="{{ $ikon }}"></i>
                                </span>
                                <span class="badge text-bg-light border">≤ {{ $tenggat }}</span>
                            </div>
                            <h2 class="h6 fw-bold mb-1">{{ $keperluan }}</h2>
                            <a href="mailto:{{ config('seekitar.contacts.' . $kunci) }}"
                               class="stretched-link text-decoration-none fw-semibold"
                               style="color: var(--hijau-lokal); word-break: break-all;">
                                {{ config('seekitar.contacts.' . $kunci) }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Sengketa transaksi: BUKAN email — lewat aplikasi agar pesanan
                 langsung membeku. Ditampilkan menonjol karena paling sering
                 keliru dikirim ke email. --}}
            <div class="lp-kanal mb-4" style="border-color: var(--kuning); background: #FFFBEB;">
                <div class="d-flex gap-3">
                    <span class="lp-fitur-ikon lp-tone-kuning flex-shrink-0" aria-hidden="true">
                        <i class="ti ti-gavel"></i>
                    </span>
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h2 class="h6 fw-bold mb-0">Sengketa transaksi</h2>
                            <span class="badge text-bg-light border">≤ 1×24 jam</span>
                        </div>
                        <p class="mb-0">
                            <strong>Laporkan lewat aplikasi: buka pesanan → Laporkan Masalah.</strong>
                            Sebaiknya bukan lewat email — laporan dari aplikasi otomatis membekukan
                            pesanan, sehingga statusnya tidak bisa berubah sampai admin memutuskan.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Penyelenggara (wajib tercantum untuk PSE). --}}
            <div class="lp-kanal">
                <div class="d-flex gap-3">
                    <span class="lp-fitur-ikon lp-tone-abu flex-shrink-0" aria-hidden="true">
                        <i class="ti ti-building"></i>
                    </span>
                    <div>
                        <h2 class="h6 fw-bold mb-1">Penyelenggara</h2>
                        <p class="mb-0">
                            {{ config('seekitar.company.name') }}<br>
                            {{ config('seekitar.company.address') }}
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </section>

@endsection
