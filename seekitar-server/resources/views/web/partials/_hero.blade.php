{{--
  Hero mini untuk halaman statis.

  Parameter:
    - $kicker    : pill di atas judul (opsional)
    - $judul     : judul halaman (wajib)
    - $subjudul  : deskripsi pendek (opsional)
    - $gambar    : URL gambar (opsional) — mode berfoto bila diisi
    - $gambarAlt : teks alt gambar
    - $crumb     : label breadcrumb (opsional; default = judul)
--}}
<section class="page-hero">
  <div class="container">
    <div class="row align-items-center pt-13 pb-11 pt-lg-13 pb-lg-12">
      <div class="{{ $gambar ?? false ? 'col-lg-7' : 'col-lg-9' }}" data-aos="fade-right" data-aos-duration="900">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-3">
            <li class="breadcrumb-item"><a class="text-hover-primary" href="{{ route('web.home') }}">Beranda</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $crumb ?? $judul }}</li>
          </ol>
        </nav>
        @if (isset($kicker) && $kicker)
          <span class="badge rounded-pill bg-primary-subtle text-primary mb-3 px-3 py-2">{{ $kicker }}</span>
        @endif
        <h1 class="fw-bolder mb-3 fs-9 lh-sm">{{ $judul }}</h1>
        @if (isset($subjudul) && $subjudul)
          <p class="fs-5 text-muted mb-0">{{ $subjudul }}</p>
        @endif
      </div>
      @if ($gambar ?? false)
        <div class="col-lg-5" data-aos="fade-left" data-aos-delay="150" data-aos-duration="900">
          <img src="{{ $gambar }}" alt="{{ $gambarAlt ?? $judul }}" class="hero-img" fetchpriority="high" decoding="async"
            onerror="this.onerror=null; this.src='https://placehold.co/1100x733/E7F6EC/168A4A?text=Seekitar'">
        </div>
      @endif
    </div>
  </div>
</section>
