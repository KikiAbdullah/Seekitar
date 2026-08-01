@extends('web.layout')

@section('title', 'Kontak & Pengaduan — Seekitar')
@section('meta_description', 'Hubungi ' . config('seekitar.company.name') . ': kanal pengaduan, pelaporan konten ilegal, hak data pribadi (UU PDP), dan keamanan.')

@section('content')

  @include('web.partials._hero', [
    'kicker'    => 'Kontak & Pengaduan',
    'judul'     => 'Kami dengar, kami tangani',
    'subjudul'  => 'Setiap kanal punya tenggat tanggapan yang kami patuhi. Semua laporan ditinjau oleh tim manusia — bukan bot.',
    'gambar'    => asset('img/web/kontak-baru.jpg'),
    'gambarAlt' => 'Ilustrasi kontak Seekitar',
  ])

  {{-- ============================ KANAL RESMI ============================ --}}
  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-7 text-center" data-aos="fade-up" data-aos-duration="900">
          <span class="eyebrow">Kanal Resmi</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-2">Pilih kanal sesuai kebutuhanmu</h2>
          <p class="fs-5 text-muted mb-0">Setiap kanal memiliki fokus penanganan yang berbeda — pilih yang paling sesuai.</p>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4">
              <span class="icon-soft mb-4"><i class="ti ti-headset"></i></span>
              <h5 class="fs-5 fw-semibold mb-1">Pengaduan Umum</h5>
              <span class="badge bg-primary-subtle text-primary mb-3">≤ 2×24 jam</span>
              <p class="mb-3 fs-4 text-muted">Masalah transaksi, keluhan penjual, atau pertanyaan seputar layanan.</p>
              <a href="mailto:{{ config('seekitar.contacts.complaint') }}" class="fw-semibold text-primary text-decoration-none d-flex align-items-center gap-1">
                <i class="ti ti-mail"></i> {{ config('seekitar.contacts.complaint') }}
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4">
              <span class="icon-soft mb-4"><i class="ti ti-flag"></i></span>
              <h5 class="fs-5 fw-semibold mb-1">Konten Ilegal / Abuse</h5>
              <span class="badge bg-danger-subtle text-danger mb-3">≤ 1×24 jam</span>
              <p class="mb-3 fs-4 text-muted">Penipuan, konten terlarang, pelecehan, atau pelanggaran hukum.</p>
              <a href="mailto:{{ config('seekitar.contacts.abuse') }}" class="fw-semibold text-primary text-decoration-none d-flex align-items-center gap-1">
                <i class="ti ti-mail"></i> {{ config('seekitar.contacts.abuse') }}
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4">
              <span class="icon-soft mb-4"><i class="ti ti-lock"></i></span>
              <h5 class="fs-5 fw-semibold mb-1">Data Pribadi (UU PDP)</h5>
              <span class="badge bg-primary-subtle text-primary mb-3">≤ 3×24 jam</span>
              <p class="mb-3 fs-4 text-muted">Akses, koreksi, atau penghapusan data pribadimu sesuai UU PDP.</p>
              <a href="mailto:{{ config('seekitar.contacts.privacy') }}" class="fw-semibold text-primary text-decoration-none d-flex align-items-center gap-1">
                <i class="ti ti-mail"></i> {{ config('seekitar.contacts.privacy') }}
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="400" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4">
              <span class="icon-soft mb-4"><i class="ti ti-shield-lock"></i></span>
              <h5 class="fs-5 fw-semibold mb-1">Celah Keamanan</h5>
              <span class="badge bg-danger-subtle text-danger mb-3">≤ 1×24 jam</span>
              <p class="mb-3 fs-4 text-muted">Temuan kerentanan atau insiden keamanan pada sistem kami.</p>
              <a href="mailto:{{ config('seekitar.contacts.security') }}" class="fw-semibold text-primary text-decoration-none d-flex align-items-center gap-1">
                <i class="ti ti-mail"></i> {{ config('seekitar.contacts.security') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ============================ INFORMASI KONTAK ============================ --}}
  <section class="bg-light py-8 py-lg-11">
    <div class="container">
      <div class="row g-4 align-items-stretch mb-5">
        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
              <span class="icon-soft mb-4"><i class="ti ti-map-pin"></i></span>
              <h5 class="fs-5 fw-semibold mb-2">Alamat</h5>
              <p class="mb-0 text-muted fs-4">{{ config('seekitar.company.address') }}</p>
            </div>
          </div>
        </div>
        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
              <span class="icon-soft mb-4"><i class="ti ti-clock"></i></span>
              <h5 class="fs-5 fw-semibold mb-2">Jam Operasional</h5>
              <p class="mb-0 text-muted fs-4">Senin–Jumat, 08.00–17.00 WIB.<br>Laporan darurat tetap dipantau di luar jam kerja.</p>
            </div>
          </div>
        </div>
        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
              <span class="icon-soft mb-4"><i class="ti ti-brand-whatsapp"></i></span>
              <h5 class="fs-5 fw-semibold mb-2">WhatsApp</h5>
              <p class="mb-0 text-muted fs-4">Alternatif cepat untuk pertanyaan umum.</p>
              <a href="https://wa.me/{{ config('seekitar.contacts.whatsapp') }}"
                class="fw-semibold text-primary text-decoration-none d-flex align-items-center gap-1 mt-2" target="_blank" rel="noopener">
                <i class="ti ti-brand-whatsapp"></i> +62 {{ ltrim(config('seekitar.contacts.whatsapp'), '62') }}
              </a>
            </div>
          </div>
        </div>
      </div>

      {{-- ============================ FORM LAPORAN ONLINE ============================ --}}
      <div class="row justify-content-center mt-5">
        <div class="col-lg-8 text-center" data-aos="fade-up" data-aos-delay="150" data-aos-duration="900">
          <h2 class="fs-9 fw-bolder mb-2">Kirim laporan online</h2>
          <p class="fs-5 text-muted mb-5">Gunakan form ini untuk laporan yang tidak memerlukan respons cepat. Masalah transaksi harap dilaporkan langsung lewat aplikasi.</p>

          @if (session('success'))
            <div class="alert alert-success d-flex align-items-center gap-2 text-start" role="alert">
              <i class="ti ti-circle-check fs-5"></i>
              <div>{{ session('success') }}</div>
            </div>
          @endif

          <form action="{{ route('web.contact.store') }}" method="POST" class="needs-validation text-start" novalidate>
            @csrf
            <div class="card border-0 shadow-sm">
              <div class="card-body p-4 p-lg-5">
                <div class="row g-4">
                  <div class="col-md-6">
                    <label for="name" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-lg @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="Nama kamu">
                    @error('name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                  <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required placeholder="email@contoh.com">
                    @error('email')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                  <div class="col-12">
                    <label for="category" class="form-label fw-semibold">Kategori Laporan <span class="text-danger">*</span></label>
                    <select class="form-select form-select-lg @error('category') is-invalid @enderror" id="category" name="category" required>
                      <option value="">Pilih Kategori Laporan</option>
                      <option value="umum" {{ old('category') == 'umum' ? 'selected' : '' }}>📋 Pengaduan Umum</option>
                      <option value="abuse" {{ old('category') == 'abuse' ? 'selected' : '' }}>🚨 Konten Ilegal / Abuse</option>
                      <option value="privacy" {{ old('category') == 'privacy' ? 'selected' : '' }}>🔒 Data Pribadi (UU PDP)</option>
                      <option value="security" {{ old('category') == 'security' ? 'selected' : '' }}>🛡️ Celah Keamanan</option>
                    </select>
                    @error('category')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                  <div class="col-12">
                    <label for="message" class="form-label fw-semibold">Pesan Anda <span class="text-danger">*</span></label>
                    <textarea class="form-control form-control-lg @error('message') is-invalid @enderror" id="message" name="message" rows="5" required placeholder="Jelaskan laporan atau pertanyaanmu selengkap mungkin...">{{ old('message') }}</textarea>
                    @error('message')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                  <div class="col-12 text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5 btn-hover-shadow">
                      <i class="ti ti-send me-1"></i> Kirim Laporan
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>

      {{-- Info penting --}}
      <div class="row justify-content-center mt-4">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm" data-aos="fade-up" data-aos-delay="200" data-aos-duration="900">
            <div class="card-body p-4 d-flex align-items-start gap-3">
              <i class="ti ti-info-circle fs-5 text-primary mt-1 flex-shrink-0"></i>
              <div>
                <strong class="d-block mb-1">Penting!</strong>
                <p class="mb-0 fs-4 text-muted">
                  Laporkan masalah transaksi langsung lewat aplikasi — laporan di aplikasi otomatis
                  membekukan pesanan dan masuk antrian prioritas admin. Laporan lewat form di atas
                  <strong>tidak</strong> membekukan pesanan.
                </p>
              </div>
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
              <h3 class="fs-7 fw-semibold">Butuh jawaban cepat?</h3>
              <p class="mb-8 text-muted">
                Cek Pusat Bantuan kami — mungkin pertanyaanmu sudah terjawab di sana.
              </p>
              <a href="{{ route('web.help') }}" class="btn btn-primary px-5 btn-hover-shadow">Buka Pusat Bantuan</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
