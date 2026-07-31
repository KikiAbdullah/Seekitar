@extends('web.layout')

@section('title', 'Karier')
@section('description', 'Bergabung dengan tim Seekitar — bantu warga Indonesia bertransaksi lebih dekat dan lebih percaya.')

@section('content')

    @include('web.partials._hero', [
        'kicker'   => 'Karier',
        'judul'    => 'Karier',
        'subjudul' => 'Bantu kami menghubungkan warga dengan yang mereka butuhkan, di sekitar.',
    ])

    <section class="py-5">
        <div class="container" style="max-width: 860px;">

            <div class="lp-doc sr-reveal">
                <div class="text-center mb-4">
                    <i class="fa-regular fa-address-book" style="font-size: 48px; color: var(--hijau-lokal); opacity: .6;" aria-hidden="true"></i>
                    <h2 class="h4 fw-bold mt-2">Belum ada lowongan terbuka</h2>
                    <p class="mb-0" style="color: var(--teks-secondary);">
                        Seekitar masih dalam tahap pengembangan. Belum ada posisi yang
                        dibuka saat ini.
                    </p>
                </div>

                <hr>

                <h2 class="h5 fw-bold">Tertarik bergabung?</h2>
                <p>
                    Meski belum ada lowongan, kami selalu senang berkenalan dengan talenta
                    potensial. Kirim CV dan portofolio ke
                    <a href="mailto:{{ config('seekitar.contacts.complaint') }}">{{ config('seekitar.contacts.complaint') }}</a>
                    — subjek: <em>"Lamaran — [Posisi yang diinginkan]"</em>.
                </p>
                <p class="mb-0">
                    Kami akan menyimpan lamaranmu dan menghubungi saat ada posisi yang cocok.
                </p>
            </div>
        </div>
    </section>

@endsection