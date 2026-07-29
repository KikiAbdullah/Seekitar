{{--
    Kepala halaman dalam situs publik: versi pendek dari hero landing —
    bentuk yang sama (gradasi, blob, pill, pola titik) tetapi hanya setinggi
    judul + remah. Dipakai bersama oleh halaman tentang/bantuan/kontak/legal
    agar susunannya identik satu sama lain.

    $kicker     teks pill di atas judul
    $judul      judul halaman (h1) — juga dipakai sebagai remah aktif
    $subjudul   (opsional) kalimat penjelas di bawah judul
    $gambar     (opsional) ilustrasi di sisi kanan judul pada layar lg ke atas;
                tanpa parameter ini hero tetap satu kolom penuh
    $gambarAlt  (opsional, wajib bila $gambar diisi) teks alternatif ilustrasi
--}}
<section class="lp-hero lp-hero-mini">
    <span class="lp-blob lp-blob-hijau" aria-hidden="true"></span>
    <span class="lp-blob lp-blob-kuning" aria-hidden="true"></span>
    <span class="lp-dots lp-dots-atas" aria-hidden="true"></span>

    <div class="container position-relative">
        <div class="row align-items-center g-4">
            <div @class(['col-lg-8' => isset($gambar)])>
                <nav aria-label="Remah roti" class="mb-3">
                    <ol class="breadcrumb mb-0" style="font-size: 14px;">
                        <li class="breadcrumb-item">
                            <a href="{{ route('web.home') }}" class="text-decoration-none text-secondary">Beranda</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $judul }}</li>
                    </ol>
                </nav>

                <span class="lp-pill mb-3">{{ $kicker }}</span>
                <h1 class="h2 fw-bold mb-2">{{ $judul }}</h1>
                @isset($subjudul)
                    <p class="lead text-secondary mb-0" style="max-width: 42rem;">{{ $subjudul }}</p>
                @endisset
            </div>

            @isset($gambar)
                {{-- Hanya tampil mulai lg: pada layar kecil ilustrasi ini
                     murni hiasan — ia mendorong konten halaman ke luar
                     pandang pertama. --}}
                <div class="col-lg-4 d-none d-lg-block">
                    <img src="{{ asset($gambar) }}" alt="{{ $gambarAlt ?? '' }}"
                         class="lp-hero-mini-img" width="900" height="600"
                         loading="lazy" decoding="async">
                </div>
            @endisset
        </div>
    </div>
</section>
