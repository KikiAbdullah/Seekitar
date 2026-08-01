@extends('web.layout')

@section('title', 'Tentang Seekitar — Pasar Lokal ' . config('seekitar.regency'))
@section('meta_description', 'Seekitar adalah pasar lokal dua arah di ' . config('seekitar.regency') . ': beli dan jual barang, jasa, serta sewa di sekitar Anda. Gratis, tanpa komisi.')

@section('content')

  @include('web.partials._hero', [
    'kicker'   => 'Tentang Kami',
    'judul'    => 'Pasar lokal dua arah dalam satu kabupaten',
    'subjudul' => 'Seekitar hadir untuk menghidupkan kembali kebiasaan bertransaksi dengan tetangga sendiri.',
    'gambar'   => asset('img/web/tentang-baru.jpg'),
    'gambarAlt' => 'Ilustrasi pasar lokal Seekitar',
  ])

  {{-- ============================ GAGASAN INTI ============================ --}}
  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-8 text-center" data-aos="fade-up" data-aos-duration="900">
          <span class="eyebrow">Gagasan Inti</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-2">Bukan sekadar etalase</h2>
          <p class="fs-5 text-muted mb-0">
            Seekitar dibangun dengan satu keyakinan: uang yang berputar di komunitas sendiri
            akan membuat semua orang lebih sejahtera.
          </p>
        </div>
      </div>
      <div class="row g-4">
        <div class="col-md-6" data-aos="fade-up" data-aos-delay="100" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
              <span class="icon-soft mb-4"><i class="ti ti-map-pin"></i></span>
              <h5 class="fs-5 fw-semibold mb-2">Fokus pada satu kabupaten</h5>
              <p class="mb-0 text-muted fs-4">
                Marketplace nasional membuat penjual dari kota besar bersaing dengan tetangga sendiri.
                Seekitar membatasi ruang lingkup pada <strong>{{ config('seekitar.regency') }}</strong> agar yang tampil
                adalah orang-orang yang benar-benar bisa kamu jangkau — tetangga sekecamatan, sekabupaten.
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-6" data-aos="fade-up" data-aos-delay="250" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
              <span class="icon-soft mb-4"><i class="ti ti-building-store"></i></span>
              <h5 class="fs-5 fw-semibold mb-2">Pasar dua arah</h5>
              <p class="mb-0 text-muted fs-4">
                Di Seekitar, pembeli tidak hanya menunggu penawaran — mereka juga bisa
                <strong>mengumumkan kebutuhan</strong>. Penjual terdekat mendapat notifikasi
                dan bisa langsung mengirimkan penawaran. Pasar bekerja dua arah, seperti
                kehidupan nyata.
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-6" data-aos="fade-up" data-aos-delay="100" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
              <span class="icon-soft mb-4"><i class="ti ti-wallet"></i></span>
              <h5 class="fs-5 fw-semibold mb-2">Gratis, tanpa komisi</h5>
              <p class="mb-0 text-muted fs-4">
                Transaksi terjadi langsung antara pembeli dan penjual — tunai atau transfer.
                Seekitar <strong>tidak mengambil potongan</strong> apa pun dari penjualan.
                Kami tidak menjadi perantara uang dan tidak menyediakan dompet digital.
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-6" data-aos="fade-up" data-aos-delay="250" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
              <span class="icon-soft mb-4"><i class="ti ti-shield-check"></i></span>
              <h5 class="fs-5 fw-semibold mb-2">Dibangun atas kepercayaan</h5>
              <p class="mb-0 text-muted fs-4">
                Identitas pemilik toko diverifikasi dengan KTP asli. Ulasan dan rating
                tercatat rapi dan tidak bisa dimanipulasi. Admin siap menengahi bila ada
                masalah. Inilah fondasi pasar warga yang sehat.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ============================ NILAI KAMI ============================ --}}
  <section class="bg-light py-8 py-lg-11">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100" data-aos-duration="900">
          <span class="eyebrow">Nilai Kami</span>
          <h2 class="fs-8 fw-bolder mt-3 mb-4">Ekonomi yang berputar di sekitar kita</h2>
          <ul class="list-unstyled d-grid gap-3 mb-0">
            <li class="check-item">
              <i class="ti ti-circle-check"></i>
              <span class="text-dark">Uang yang dibelanjakan tetap tinggal di komunitas sendiri.</span>
            </li>
            <li class="check-item">
              <i class="ti ti-circle-check"></i>
              <span class="text-dark">Penjual kecil dan usaha rumahan mendapat panggung yang setara.</span>
            </li>
            <li class="check-item">
              <i class="ti ti-circle-check"></i>
              <span class="text-dark">Barang bekas yang layak dipakai kembali memperpanjang masa gunanya.</span>
            </li>
            <li class="check-item">
              <i class="ti ti-circle-check"></i>
              <span class="text-dark">Komunikasi langsung menumbuhkan rasa saling kenal antarwarga.</span>
            </li>
            <li class="check-item">
              <i class="ti ti-circle-check"></i>
              <span class="text-dark">Transparansi penuh: rating, ulasan, dan identitas diverifikasi.</span>
            </li>
          </ul>
        </div>
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="250" data-aos-duration="900">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5">
              <span class="eyebrow mb-4 d-block">Fakta Singkat</span>
              <div class="d-grid gap-4 mt-3">
                <div class="d-flex align-items-center gap-3">
                  <span class="icon-soft"><i class="ti ti-map-pin"></i></span>
                  <div>
                    <div class="fw-bold fs-5 lh-sm">{{ config('seekitar.regency') }}</div>
                    <div class="fs-3 text-muted">wilayah operasi terfokus — satu kabupaten</div>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                  <span class="icon-soft"><i class="ti ti-tags"></i></span>
                  <div>
                    <div class="fw-bold fs-5 lh-sm">Barang · Jasa · Sewa</div>
                    <div class="fs-3 text-muted">tiga jenis kebutuhan dalam satu pasar</div>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                  <span class="icon-soft"><i class="ti ti-wallet"></i></span>
                  <div>
                    <div class="fw-bold fs-5 lh-sm">Rp 0</div>
                    <div class="fs-3 text-muted">komisi untuk semua transaksi</div>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                  <span class="icon-soft"><i class="ti ti-building-store"></i></span>
                  <div>
                    <div class="fw-bold fs-5 lh-sm">Verifikasi KTP</div>
                    <div class="fs-3 text-muted">setiap penjual diverifikasi identitasnya</div>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                  <span class="icon-soft"><i class="ti ti-message-dots"></i></span>
                  <div>
                    <div class="fw-bold fs-5 lh-sm">Dua Arah</div>
                    <div class="fs-3 text-muted">pembeli dan penjual sama-sama bisa memulai</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ============================ TIM & ALAMAT ============================ --}}
  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row justify-content-center mb-5 mb-lg-7">
        <div class="col-lg-8 text-center" data-aos="fade-up" data-aos-duration="900">
          <span class="eyebrow">Kehadiran Kami</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-2">Di mana kami berada</h2>
          <p class="fs-5 text-muted mb-0">
            Seekitar beroperasi penuh di {{ config('seekitar.regency') }}. Kami adalah tim lokal
            yang paham kebutuhan masyarakat setempat.
          </p>
        </div>
      </div>
      <div class="row g-4 justify-content-center">
        <div class="col-md-6" data-aos="fade-up" data-aos-delay="100" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100 text-center">
            <div class="card-body p-4 p-lg-5">
              <span class="icon-soft d-inline-flex align-items-center justify-content-center mb-4" style="width: 64px; height: 64px; font-size: 1.75rem;">
                <i class="ti ti-map-pin"></i>
              </span>
              <h5 class="fs-5 fw-semibold mb-2">Alamat</h5>
              <p class="mb-0 text-muted fs-4">{{ config('seekitar.company.address') }}</p>
            </div>
          </div>
        </div>
        <div class="col-md-6" data-aos="fade-up" data-aos-delay="200" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100 text-center">
            <div class="card-body p-4 p-lg-5">
              <span class="icon-soft d-inline-flex align-items-center justify-content-center mb-4" style="width: 64px; height: 64px; font-size: 1.75rem;">
                <i class="ti ti-clock"></i>
              </span>
              <h5 class="fs-5 fw-semibold mb-2">Jam Operasional</h5>
              <p class="mb-0 text-muted fs-4">Senin–Jumat, 08.00–17.00 WIB<br>Laporan darurat tetap dipantau di luar jam kerja.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ============================ CTA ============================ --}}
  <section class="pb-8 pb-lg-11">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm" data-aos="fade-up" data-aos-duration="900">
            <div class="card-body text-center p-4 p-lg-8 py-8">
              <h3 class="fs-7 fw-semibold">Mari bertetangga dengan cara baru</h3>
              <p class="mb-8 text-muted">
                Mulai dari lingkaran terkecil: warga {{ config('seekitar.regency') }}.
              </p>
              <div class="d-sm-flex align-items-center justify-content-center gap-3">
                <a href="{{ route('web.listings') }}"
                  class="btn btn-primary px-5 d-block mb-3 mb-sm-0 btn-hover-shadow">Jelajahi Pasar</a>
                <a href="{{ route('web.for-sellers') }}"
                  class="btn btn-outline-primary px-5 d-block">Buka Toko Gratis</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
