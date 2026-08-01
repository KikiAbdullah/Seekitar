@extends('web.layout')

@section('title', 'Karier — Seekitar')
@section('meta_description', 'Bergabung dengan ' . config('seekitar.company.name') . ' dan bantu menghidupkan ekonomi lokal ' . config('seekitar.regency') . '.')

@section('content')

  @include('web.partials._hero', [
    'kicker'   => 'Karier',
    'judul'    => 'Membangun pasar lokal bersama',
    'subjudul' => 'Kami selalu mencari individu bersemangat yang peduli pada pertumbuhan ekonomi komunitas.',
    'gambar'   => asset('img/web/tentang.webp'),
    'gambarAlt' => 'Ilustrasi karier di Seekitar',
  ])

  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-8 text-center" data-aos="fade-up" data-aos-duration="900">
          <span class="eyebrow">Budaya Kami</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-2">Bekerja dengan dampak nyata</h2>
          <p class="fs-4 text-muted mt-3 mb-0">
            Di Seekitar, kami percaya pada kolaborasi, inovasi, dan dampak langsung bagi masyarakat.
            Kami bukan hanya membangun platform — tapi juga membangun komunitas yang saling mendukung.
          </p>
        </div>
      </div>

      <div class="row g-4 justify-content-center">
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4 text-center">
              <span class="icon-soft d-inline-flex align-items-center justify-content-center mb-4" style="width: 72px; height: 72px; font-size: 2rem;">
                <i class="ti ti-users"></i>
              </span>
              <h5 class="fs-5 fw-semibold mb-1">Kolaborasi Kuat</h5>
              <p class="mb-0 fs-4 text-muted">Kami bekerja sebagai tim, berbagi ide, dan merayakan keberhasilan bersama.</p>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4 text-center">
              <span class="icon-soft d-inline-flex align-items-center justify-content-center mb-4" style="width: 72px; height: 72px; font-size: 2rem;">
                <i class="ti ti-bulb"></i>
              </span>
              <h5 class="fs-5 fw-semibold mb-1">Inovasi Berkelanjutan</h5>
              <p class="mb-0 fs-4 text-muted">Kami tak henti mencari cara baru meningkatkan pengalaman pengguna dan solusi lokal.</p>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4 text-center">
              <span class="icon-soft d-inline-flex align-items-center justify-content-center mb-4" style="width: 72px; height: 72px; font-size: 2rem;">
                <i class="ti ti-target"></i>
              </span>
              <h5 class="fs-5 fw-semibold mb-1">Dampak Nyata</h5>
              <p class="mb-0 fs-4 text-muted">Setiap pekerjaan kami bertujuan memberdayakan ekonomi lokal dan kesejahteraan komunitas.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="py-8 py-lg-11 bg-light">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 text-center" data-aos="fade-up" data-aos-duration="900">
          <span class="eyebrow">Bergabung</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-3">Tertarik bergabung dengan kami?</h2>
          <p class="fs-5 text-muted mb-4">
            Kami adalah tim kecil yang bertumbuh pesat dan selalu mencari talenta terbaik.
          </p>
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5">
              <span class="icon-soft d-inline-flex align-items-center justify-content-center mb-4" style="width: 64px; height: 64px; font-size: 1.75rem;">
                <i class="ti ti-mail"></i>
              </span>
              <p class="mb-3 text-muted fs-4">
                Saat ini belum ada lowongan spesifik yang dibuka, namun kami selalu terbuka untuk inisiatif.
                Jika kamu memiliki keahlian yang relevan dan ingin berkontribusi membangun pasar lokal
                bersama Seekitar, kirimkan lamaranmu ke:
              </p>
              <a href="mailto:{{ config('seekitar.contacts.complaint') }}?subject={{ rawurlencode('Lamaran — [Posisi yang diminati]') }}"
                class="btn btn-primary px-4 btn-hover-shadow mb-3">
                <i class="ti ti-mail me-1"></i> {{ config('seekitar.contacts.complaint') }}
              </a>
              <p class="mb-0 fs-3 text-muted">
                Gunakan subjek <strong>"Lamaran — [Posisi yang diminati]"</strong>
              </p>
            </div>
          </div>
          <a href="{{ route('web.about') }}" class="btn btn-outline-primary px-5 mt-4">Pelajari Lebih Lanjut Tentang Kami</a>
        </div>
      </div>
    </div>
  </section>

  {{-- ============================ CTA ============================ --}}
  <section class="pb-8 pb-lg-11">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card c2a-box border-0 shadow-sm" data-aos="fade-up" data-aos-duration="900">
            <div class="card-body text-center p-4 p-lg-8 py-8">
              <h3 class="fs-7 fw-semibold">Kenali kami lebih dekat</h3>
              <p class="mb-8 text-muted">
                Baca tentang misi kami menghidupkan ekonomi lokal satu kabupaten.
              </p>
              <a href="{{ route('web.about') }}" class="btn btn-primary px-5 btn-hover-shadow">Tentang Seekitar</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
