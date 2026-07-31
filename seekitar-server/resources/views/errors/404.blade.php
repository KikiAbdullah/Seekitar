@extends('web.layout')

@section('title', 'Halaman Tidak Ditemukan')
@section('description', 'Halaman yang kamu cari tidak tersedia.')

@section('content')
    <section class="py-5" style="min-height: 60vh;">
        <div class="container d-flex align-items-center justify-content-center" style="min-height: 60vh;">
            <div class="text-center sr-reveal">
                <span class="lp-pill mb-3" style="background: #FEF3C7; color: #92400E;">404</span>
                <h1 class="display-5 fw-bold mb-3">Halaman tidak ditemukan</h1>
                <p class="lead mb-4 mx-auto" style="color: var(--teks-secondary); max-width: 28rem;">
                    Halaman yang kamu cari mungkin sudah dihapus, dipindah, atau
                    alamatnya salah.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ route('web.home') }}" class="btn btn-seekitar btn-lg px-4">
                        <i class="fa-regular fa-arrow-alt-circle-left me-1" aria-hidden="true"></i> Ke Beranda
                    </a>
                    <a href="{{ route('web.contact') }}" class="btn btn-outline-dark btn-lg px-4">
                        Hubungi Kami
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
