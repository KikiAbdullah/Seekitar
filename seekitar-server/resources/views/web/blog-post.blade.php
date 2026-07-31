@extends('web.layout')

@section('title', $post['title'])
@section('description', $post['excerpt'])

@push('head')
    <meta property="article:published_time" content="{{ $post['date'] }}">
    <meta property="article:author" content="{{ $post['author'] }}">
    <meta property="article:section" content="{{ $post['category'] }}">
@endpush

@section('content')

    <section class="lp-hero lp-hero-berfoto">
        <span class="lp-blob lp-blob-hijau" aria-hidden="true"></span>
        <span class="lp-blob lp-blob-kuning" aria-hidden="true"></span>
        <span class="lp-dots lp-dots-atas" aria-hidden="true"></span>

        <div class="container position-relative">
            <nav aria-label="Remah roti" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('web.home') }}" class="text-decoration-none">Beranda</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('web.blog') }}" class="text-decoration-none">Blog</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $post['title'] }}</li>
                </ol>
            </nav>

            <span class="lp-pill mb-3">{{ $post['category'] }}</span>
            <h1 class="h2 fw-bold mb-2">{{ $post['title'] }}</h1>
            <p style="color: var(--teks-secondary); font-size: 14px;">
                {{ $post['date'] }} · Oleh {{ $post['author'] }}
            </p>
        </div>
    </section>

    <section class="py-5">
        <div class="container" style="max-width: 760px;">
            <div class="lp-doc sr-reveal">
                {!! $post['body'] !!}
            </div>

            <div class="text-center mt-4">
                <a href="{{ route('web.blog') }}" class="btn btn-outline-dark px-4">
                    <i class="ti ti-arrow-left me-1" aria-hidden="true"></i> Kembali ke Blog
                </a>
            </div>
        </div>
    </section>

@endsection