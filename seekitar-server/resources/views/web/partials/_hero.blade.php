{{--
    Kepala halaman dalam situs publik. Dua bentuk, bahasa visual yang sama
    dengan beranda:

    - DENGAN $gambar (tentang/bantuan/kontak): ilustrasi memenuhi SELURUH
      latar hero, teks menumpang di atas scrim — persis pola hero beranda,
      hanya lebih pendek. Gambar ikut tampil di layar kecil (selubung
      scrim merata menjaga keterbacaan), ia adalah LCP halaman ini jadi
      dimuat penuh semangat, bukan lazy.
    - TANPA $gambar (halaman legal): gradasi + blob + pola titik murni CSS
      — tidak ada gambar yang perlu diunduh.

    $kicker    teks pill di atas judul
    $judul     judul halaman (h1) — juga dipakai sebagai remah aktif
    $subjudul  (opsional) kalimat penjelas di bawah judul
    $gambar    (opsional) berkas ilustrasi latar hero
    $gambarAlt (opsional, wajib bila $gambar diisi) teks alternatif ilustrasi
--}}
<section @class(['lp-hero', 'lp-hero-mini', 'lp-hero-berfoto' => isset($gambar)])>
    @isset($gambar)
        <img src="{{ asset($gambar) }}" alt="{{ $gambarAlt ?? '' }}"
             class="lp-hero-bg" fetchpriority="high" decoding="async">
        <span class="lp-hero-scrim" aria-hidden="true"></span>
    @else
        <span class="lp-blob lp-blob-hijau" aria-hidden="true"></span>
        <span class="lp-blob lp-blob-kuning" aria-hidden="true"></span>
        <span class="lp-dots lp-dots-atas" aria-hidden="true"></span>
    @endisset

    <div class="container position-relative">
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
</section>
