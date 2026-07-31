@extends('web.layout')

@section('title', 'Sesi Berakhir')
@section('description', 'Sesi kamu telah berakhir. Silakan muat ulang halaman.')

@section('content')
    <section class="py-5" style="min-height: 60vh;">
        <div class="container d-flex align-items-center justify-content-center" style="min-height: 60vh;">
            <div class="text-center sr-reveal">
                <span class="lp-pill mb-3" style="background: #FEF3C7; color: #92400E;">419</span>
                <h1 class="display-5 fw-bold mb-3">Sesi berakhir</h1>
                <p class="lead mb-4 mx-auto" style="color: var(--teks-secondary); max-width: 28rem;">
                    Halaman sudah terlalu lama terbuka. Silakan muat ulang
                    dan coba lagi.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ url()->current() }}" class="btn btn-seekitar btn-lg px-4">
                        <i class="ti ti-refresh me-1" aria-hidden="true"></i> Muat Ulang
                    </a>
                    <a href="{{ route('web.home') }}" class="btn btn-outline-dark btn-lg px-4">
                        Ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
