@extends('web.layout')

@section('title', 'Terjadi Kesalahan')
@section('description', 'Terjadi kesalahan pada server. Tim kami sedang menanganinya.')

@section('content')
    <section class="py-5" style="min-height: 60vh;">
        <div class="container d-flex align-items-center justify-content-center" style="min-height: 60vh;">
            <div class="text-center sr-reveal">
                <span class="lp-pill mb-3" style="background: #FEE2E2; color: #991B1B;">500</span>
                <h1 class="display-5 fw-bold mb-3">Terjadi kesalahan</h1>
                <p class="lead mb-4 mx-auto" style="color: var(--teks-secondary); max-width: 28rem;">
                    Maaf, terjadi kesalahan di sisi server. Tim kami sudah mendapat
                    notifikasi dan sedang memperbaikinya.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ route('web.home') }}" class="btn btn-seekitar btn-lg px-4">
                        <i class="ti ti-arrow-left me-1" aria-hidden="true"></i> Ke Beranda
                    </a>
                    <a href="{{ route('web.contact') }}" class="btn btn-outline-dark btn-lg px-4">
                        Hubungi Kami
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
