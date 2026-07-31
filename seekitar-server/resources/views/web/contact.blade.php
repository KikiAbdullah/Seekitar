@extends('web.layout')

@section('title', 'Kontak & Pengaduan — Seekitar')
@section('meta_description', 'Hubungi ' . config('seekitar.company.name') . ': kanal pengaduan, pelaporan konten ilegal, hak data pribadi (UU PDP), dan keamanan.')

@section('content')

  @include('web.partials._hero', [
    'kicker'    => 'Kontak & Pengaduan',
    'judul'     => 'Kami dengar, kami tangani',
    'subjudul'  => 'Setiap kanal punya tenggat tanggapan yang kami patuhi. Semua laporan ditinjau tim manusia.',
    'gambar'    => asset('img/web/kontak.webp'),
    'gambarAlt' => 'Ilustrasi kontak Seekitar',
  ])

  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row justify-content-center mb-6 mb-lg-9">
        <div class="col-lg-7 text-center" data-aos="fade-up" data-aos-duration="900">
          <span class="eyebrow">Kanal Resmi</span>
          <h2 class="fs-9 fw-bolder mt-3 mb-0">Pilih kanal sesuai kebutuhanmu</h2>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4">
              <span class="icon-soft mb-4"><i class="ti ti-headset"></i></span>
              <h5 class="fs-5 fw-semibold mb-1">Pengaduan Umum</h5>
              <span class="badge bg-primary-subtle text-primary mb-3">≤ 2×24 jam</span>
              <p class="mb-3 fs-4 text-muted">Masalah transaksi, penjual, atau keluhan layanan.</p>
              <a href="mailto:{{ config('seekitar.contacts.complaint') }}" class="fw-semibold text-primary text-decoration-none">
                {{ config('seekitar.contacts.complaint') }}
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4">
              <span class="icon-soft mb-4"><i class="ti ti-flag"></i></span>
              <h5 class="fs-5 fw-semibold mb-1">Konten Ilegal / Abuse</h5>
              <span class="badge bg-primary-subtle text-primary mb-3">≤ 1×24 jam</span>
              <p class="mb-3 fs-4 text-muted">Penipuan, konten terlarang, pelecehan, atau pelanggaran hukum.</p>
              <a href="mailto:{{ config('seekitar.contacts.abuse') }}" class="fw-semibold text-primary text-decoration-none">
                {{ config('seekitar.contacts.abuse') }}
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
              <p class="mb-3 fs-4 text-muted">Akses, koreksi, atau penghapusan data pribadimu.</p>
              <a href="mailto:{{ config('seekitar.contacts.privacy') }}" class="fw-semibold text-primary text-decoration-none">
                {{ config('seekitar.contacts.privacy') }}
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="400" data-aos-duration="900">
          <div class="card card-lift border-0 shadow-sm h-100">
            <div class="card-body p-4">
              <span class="icon-soft mb-4"><i class="ti ti-shield-lock"></i></span>
              <h5 class="fs-5 fw-semibold mb-1">Celah Keamanan</h5>
              <span class="badge bg-primary-subtle text-primary mb-3">≤ 1×24 jam</span>
              <p class="mb-3 fs-4 text-muted">Temuan kerentanan atau insiden keamanan sistem.</p>
              <a href="mailto:{{ config('seekitar.contacts.security') }}" class="fw-semibold text-primary text-decoration-none">
                {{ config('seekitar.contacts.security') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="bg-light py-8 py-lg-11">
    <div class="container">
      <div class="row g-4 align-items-stretch">
        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100" data-aos-duration="900">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
              <span class="icon-soft mb-4"><i class="ti ti-map-pin"></i></span>
              <h5 class="fs-5 fw-semibold mb-2">Alamat</h5>
              <p class="mb-0 text-muted fs-4">{{ config('seekitar.company.address') }}</p>
            </div>
          </div>
        </div>
        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200" data-aos-duration="900">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
              <span class="icon-soft mb-4"><i class="ti ti-clock"></i></span>
              <h5 class="fs-5 fw-semibold mb-2">Jam Operasional</h5>
              <p class="mb-0 text-muted fs-4">Senin–Jumat, 08.00–17.00 WIB.<br>Laporan darurat tetap dipantau di luar jam kerja.</p>
            </div>
          </div>
        </div>
        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300" data-aos-duration="900">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
              <span class="icon-soft mb-4"><i class="ti ti-brand-whatsapp"></i></span>
              <h5 class="fs-5 fw-semibold mb-2">WhatsApp</h5>
              <p class="mb-0 text-muted fs-4">Alternatif cepat untuk pertanyaan umum.</p>
              <a href="https://wa.me/{{ config('seekitar.contacts.whatsapp') }}"
                class="fw-semibold text-primary text-decoration-none" target="_blank" rel="noopener">
                +62 {{ ltrim(config('seekitar.contacts.whatsapp'), '62') }}
              </a>
            </div>
          </div>
        </div>
      </div>

      <div class="row justify-content-center mt-5">
        <div class="col-lg-8 text-center" data-aos="fade-up" data-aos-delay="150" data-aos-duration="900">
          <h2 class="fs-9 fw-bolder mt-3 mb-0">Atau kirim laporan online</h2>
          <p class="fs-5 text-muted mb-5">Gunakan form ini untuk laporan yang tidak memerlukan respon cepat. Laporan yang memerlukan respon cepat (transaksi) harap lewat aplikasi.</p>

          @if (session('success'))
            <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
              <i class="ti ti-circle-check fs-5"></i>
              <div>{{ session('success') }}</div>
            </div>
          @endif

          <form action="{{ route('web.contact.store') }}" method="POST" class="needs-validation text-start" novalidate>
            @csrf
            <div class="row g-4">
              <div class="col-md-6">
                <label for="name" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label for="email" class="form-label">Alamat Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12">
                <label for="category" class="form-label">Kategori Laporan</label>
                <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                  <option value="">Pilih Kategori</option>
                  <option value="umum" {{ old('category') == 'umum' ? 'selected' : '' }}>Pengaduan Umum</option>
                  <option value="abuse" {{ old('category') == 'abuse' ? 'selected' : '' }}>Konten Ilegal / Abuse</option>
                  <option value="privacy" {{ old('category') == 'privacy' ? 'selected' : '' }}>Data Pribadi (UU PDP)</option>
                  <option value="security" {{ old('category') == 'security' ? 'selected' : '' }}>Celah Keamanan</option>
                </select>
                @error('category')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12">
                <label for="message" class="form-label">Pesan Anda</label>
                <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="5" required>{{ old('message') }}</textarea>
                @error('message')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12 text-center">
                <button type="submit" class="btn btn-primary btn-lg px-5 btn-hover-shadow">Kirim Laporan</button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="card border-0 shadow-sm mt-4" data-aos="fade-up" data-aos-delay="200" data-aos-duration="900">
        <div class="card-body p-4 d-flex align-items-start gap-3">
          <i class="ti ti-info-circle fs-5 text-primary mt-1"></i>
          <p class="mb-0 fs-4 text-muted">
            Laporkan masalah transaksi langsung lewat aplikasi — laporan itu membekukan pesanan
            dan otomatis masuk antrian admin. Laporan lewat form ini tidak membekukan pesanan.
          </p>
        </div>
      </div>

    </div>
  </section>

@endsection
