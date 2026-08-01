@extends('web.layout')

@section('title', 'Status Layanan — Seekitar')
@section('meta_description', 'Pemantauan status layanan Seekitar: API, situs web, notifikasi, dan database.')

@section('content')

  @include('web.partials._hero', [
    'kicker'   => 'Status Layanan',
    'judul'    => 'Pemantauan status layanan',
    'subjudul' => 'Halaman ini menyajikan status real-time komponen layanan utama Seekitar.',
    'gambar'   => asset('img/web/status-baru.jpg'),
    'gambarAlt' => 'Ilustrasi pemantauan status layanan Seekitar',
  ])

  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-8 text-center" data-aos="fade-up" data-aos-duration="900">
          @if ($healthy)
            <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2 mb-4">
              <i class="ti ti-circle-check me-1"></i> Seluruh layanan berjalan normal
            </span>
            <h2 class="fs-9 fw-bolder mt-3 mb-0">Semua sistem berfungsi</h2>
          @else
            <span class="badge rounded-pill bg-danger-subtle text-danger px-3 py-2 mb-4">
              <i class="ti ti-alert-triangle me-1"></i> Beberapa komponen bermasalah
            </span>
            <h2 class="fs-9 fw-bolder mt-3 mb-0">Ada gangguan layanan</h2>
          @endif
          <div class="fs-4 text-muted mt-2">Pemeriksaan otomatis terakhir: {{ $checked_at->isoFormat('LLL') }}</div>
        </div>
      </div>

      <div class="row justify-content-center" data-aos="fade-up" data-aos-delay="150" data-aos-duration="900">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
              @foreach ([
                'database' => ['ti ti-database', 'Database', 'Koneksi dan operasi basis data'],
                'cache' => ['ti ti-server', 'Cache', 'Penyimpanan data sementara aplikasi'],
                'storage' => ['ti ti-cloud', 'Penyimpanan', 'Akses ke penyimpanan berkas'],
                'notifications' => ['ti ti-bell', 'Notifikasi (WA)', 'Pengiriman pesan WhatsApp'],
              ] as $key => [$ikon, $nama, $ket])
                @php
                  $check = $checks[$key];
                  $status_class = $check['ok'] === true ? 'success' : ($check['ok'] === false ? 'danger' : 'warning');
                  $status_text = $check['ok'] === true ? 'Berjalan Normal' : ($check['ok'] === false ? 'Bermasalah' : 'Tidak Dipantau');
                @endphp
                <div class="d-flex align-items-center justify-content-between gap-3 px-4 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                  <div class="d-flex align-items-center gap-3">
                    <span class="icon-soft"><i class="{{ $ikon }}"></i></span>
                    <div>
                      <div class="fw-semibold">{{ $nama }}</div>
                      <div class="fs-3 text-muted">{{ $ket }}</div>
                    </div>
                  </div>
                  <div class="text-end">
                    <span class="badge bg-{{ $status_class }}-subtle text-{{ $status_class }} px-3 py-2">{{ $status_text }}</span>
                    @if ($check['ok'] === false)
                      <div class="fs-3 text-muted mt-1">{{ $check['message'] }}</div>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4 mt-2">
        <div class="col-md-6" data-aos="fade-up" data-aos-delay="100" data-aos-duration="900">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
              <h5 class="fw-semibold mb-3"><i class="ti ti-history me-2 text-primary"></i>Riwayat Insiden</h5>
              <p class="mb-0 text-muted fs-4">Belum ada insiden tercatat.</p>
            </div>
          </div>
        </div>
        <div class="col-md-6" data-aos="fade-up" data-aos-delay="200" data-aos-duration="900">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
              <h5 class="fw-semibold mb-3"><i class="ti ti-clock me-2 text-primary"></i>Pemeliharaan Terjadwal</h5>
              <p class="mb-0 text-muted fs-4">Tidak ada pemeliharaan terjadwal.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="row justify-content-center mt-5">
        <div class="col-lg-8 text-center" data-aos="fade-up" data-aos-delay="150" data-aos-duration="900">
          <p class="mb-0 text-muted fs-4">
            Mengalami kendala padahal status normal?
            <a href="{{ route('web.contact') }}" class="text-primary fw-semibold">Laporkan lewat kanal pengaduan</a>.
          </p>
        </div>
      </div>
    </div>
  </section>

@endsection
