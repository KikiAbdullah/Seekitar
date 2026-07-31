@extends('web.layout')

@section('title', 'Karier — Seekitar')
@section('meta_description', 'Bergabung dengan ' . config('seekitar.company.name') . ' dan bantu menghidupkan ekonomi lokal ' . config('seekitar.regency') . '.')

@section('content')

  @include('web.partials._hero', [
    'kicker'   => 'Karier',
    'judul'    => 'Membangun pasar lokal bersama',
    'subjudul' => 'Kami selalu mencari individu bersemangat yang peduli pada pertumbuhan ekonomi komunitas. Mari kembangkan potensi Anda bersama kami.',
  ])

  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-8 text-center" data-aos="fade-up" data-aos-duration="900">
          <span class="eyebrow">Nilai Kami</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-0">Budaya Kerja & Filosofi</h2>
          <p class="fs-4 text-muted mt-3 mb-0">Di Seekitar, kami percaya pada kolaborasi, inovasi, dan dampak nyata. Kami bukan hanya membangun platform, tapi juga komunitas yang saling mendukung.</p>
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
              <p class="mb-0 fs-4 text-muted">Kami tak henti mencari cara baru untuk meningkatkan pengalaman pengguna dan solusi lokal.</p>
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
              <p class="mb-0 fs-4 text-muted">Setiap pekerjaan kami bertujuan untuk memberdayakan ekonomi lokal dan meningkatkan kesejahteraan komunitas.</p>
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
          <h2 class="fs-9 fw-bolder mb-3">Tertarik Bergabung?</h2>
          <p class="fs-5 text-muted mb-8">
            Kami adalah tim kecil yang bertumbuh pesat dan selalu mencari talenta terbaik. Saat ini belum ada lowongan spesifik yang dibuka, namun kami selalu terbuka untuk inisiatif. Jika Anda memiliki keahlian yang relevan dan ingin berkontribusi dalam membangun pasar lokal bersama Seekitar, kirimkan lamaran Anda ke:
            <a href="mailto:{{ config('seekitar.contacts.complaint') }}?subject={{ rawurlencode('Lamaran — [Posisi yang diminati]') }}"
              class="text-primary fw-semibold">{{ config('seekitar.contacts.complaint') }}</a>
            dengan subjek <strong>"Lamaran — [Posisi yang diminati]"</strong>.
          </p>
          <a href="{{ route('web.about') }}" class="btn btn-primary px-5 btn-hover-shadow">Pelajari Lebih Lanjut Tentang Kami</a>
        </div>
      </div>
    </div>
  </section>

@endsection
