@extends('web.layout')

@section('title', $post['title'] . ' — Seekitar')
@section('meta_description', $post['excerpt'])

@push('head')
  <meta property="article:published_time" content="{{ $post['date'] }}">
  <meta property="article:author" content="{{ $post['author'] }}">
  <meta property="article:section" content="{{ $post['category'] }}">
@endpush

@section('content')

  <section class="page-hero">
    <div class="container">
      <div class="pt-13 pb-11 pt-lg-13 pb-lg-12" data-aos="fade-up" data-aos-duration="900">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-3">
            <li class="breadcrumb-item"><a class="text-hover-primary" href="{{ route('web.home') }}">Beranda</a></li>
            <li class="breadcrumb-item"><a class="text-hover-primary" href="{{ route('web.blog') }}">Blog</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $post['title'] }}</li>
          </ol>
        </nav>
        <span class="badge rounded-pill bg-primary-subtle text-primary mb-3 px-3 py-2">{{ $post['category'] }}</span>
        <h1 class="fw-bolder mb-2 fs-9 lh-sm">{{ $post['title'] }}</h1>
        <p class="fs-4 text-muted mb-0">
          {{ $post['date'] }} · Oleh {{ $post['author'] }}
        </p>
      </div>
    </div>
  </section>

  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8" data-aos="fade-up" data-aos-delay="150" data-aos-duration="900">
          <div class="doc-content">
            {!! $post['body'] !!}
          </div>

          <div class="text-center mt-8">
            <a href="{{ route('web.blog') }}" class="btn btn-outline-primary px-4">
              <i class="ti ti-arrow-left me-1" aria-hidden="true"></i> Kembali ke Blog
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
