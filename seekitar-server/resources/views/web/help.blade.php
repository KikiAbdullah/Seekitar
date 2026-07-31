@extends('web.layout')

@section('title', 'Pusat Bantuan')
@section('description', 'Pertanyaan umum seputar Seekitar: cara memesan, pembayaran COD, dan penyelesaian masalah.')

@section('content')

    @include('web.partials._hero', [
        'kicker'    => 'Pusat Bantuan',
        'judul'     => 'Pusat Bantuan',
        'subjudul'  => 'Pertanyaan yang paling sering ditanyakan, dijawab sejujurnya.',
        'gambar'    => 'img/web/bantuan.webp',
        'gambarAlt' => 'Ilustrasi petugas layanan Seekitar dengan headset di depan laptop, diapit gelembung tanya dan centang',
    ])

    @php
        $grupFaq = [
            'Akun & Keamanan' => [
                ['fa-regular fa-comment-dots', 'Bagaimana cara masuk ke aplikasi?',
                 'Masukkan nomor WhatsApp-mu, lalu kami kirim kode OTP 6 digit. Tidak ada kata sandi. Kode berlaku 5 menit dan hanya bisa dipakai sekali.'],
                ['fa-regular fa-circle-check', 'Seekitar tidak pernah meminta kode OTP saya, benar?',
                 'Benar. Siapa pun yang meminta kode OTP-mu — termasuk yang mengaku petugas Seekitar — sedang berusaha mengambil alih akunmu. Jangan pernah membagikannya.'],
                ['fa-regular fa-user-circle', 'Bagaimana menghapus akun dan data saya?',
                 'Kirim permintaan ke ' . config('seekitar.contacts.privacy') . '. Kami menanggapi dalam 3×24 jam. Data transaksi yang sudah selesai tetap disimpan untuk keperluan audit dan sengketa.'],
            ],
            'Pembayaran' => [
                ['fa-regular fa-money-bill-1', 'Bagaimana cara membayar?',
                 'Pembayaran langsung antara kamu dan penjual: tunai saat bertemu (COD) atau transfer ke rekening penjual. Seekitar tidak menampung dana dan tidak menyediakan rekening bersama.'],
                ['fa-regular fa-money-bill-1', 'Bisakah uang saya dikembalikan?',
                 'Karena dana tidak melewati Seekitar, kami tidak dapat mengembalikannya secara otomatis. Bila barang atau jasa tidak sesuai, buka pesanan lalu pilih Laporkan Masalah. Pesanan akan dibekukan dan admin menengahi dalam 1×24 jam.'],
            ],
            'Permintaan & Penawaran' => [
                ['fa-regular fa-bell', 'Apa itu "Pasang Kebutuhan"?',
                 'Kalau barang atau jasa yang kamu cari belum ada di katalog, tulis saja kebutuhanmu. Permintaan itu disiarkan ke penyedia terdekat yang kategorinya cocok, lalu mereka mengirim penawaran.'],
                ['fa-regular fa-map', 'Kenapa permintaan saya tidak dilihat penyedia jauh?',
                 'Pencocokan berlaku dua arah: penyedia harus berada dalam radius yang kamu pilih, dan kamu harus berada dalam radius layanan mereka. Warung dengan jangkauan 5 km tidak menerima permintaan dari 12 km.'],
                ['fa-regular fa-eye-slash', 'Kapan nomor telepon saya terlihat penjual?',
                 'Sebelum penawaran diterima, penyedia hanya melihat namamu yang disingkat dan lokasi yang dibulatkan. Nomor dan alamat lengkap baru terbuka setelah pesanan terbentuk.'],
            ],
            'Verifikasi & Ulasan' => [
                ['fa-regular fa-id-card', 'Kenapa harus unggah KTP untuk membuka toko?',
                 'Agar setiap penjual punya jejak identitas yang bisa dipertanggungjawabkan. Foto KTP disimpan pada penyimpanan privat dan tidak pernah ditampilkan ke pengguna lain.'],
                ['fa-regular fa-star', 'Berapa lama saya bisa memberi ulasan?',
                 '7 hari setelah pesanan berstatus selesai. Ulasan tidak dapat diubah setelah dikirim.'],
            ],
        ];
    @endphp

    <section class="py-5">
        <div class="container" style="max-width: 820px;">
            @foreach ($grupFaq as $gJudul => $items)
                <h2 class="h5 fw-bold {{ $loop->first ? 'mb-3' : 'mt-5 mb-3' }} sr-reveal">{{ $gJudul }}</h2>

                <div class="accordion accordion-seekitar mb-4" id="faq-{{ $loop->index }}">
                    @foreach ($items as $j => [$ikon, $tanya, $jawab])
                        @php $terbuka = $loop->parent->first && $loop->first; @endphp
                        <div class="accordion-item sr-reveal">
                            <h3 class="accordion-header" id="faq-{{ $loop->parent->index }}-h{{ $j }}">
                                <button class="accordion-button {{ $terbuka ? '' : 'collapsed' }}" type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#faq-{{ $loop->parent->index }}-c{{ $j }}"
                                        aria-expanded="{{ $terbuka ? 'true' : 'false' }}"
                                        aria-controls="faq-{{ $loop->parent->index }}-c{{ $j }}">
                                    <i class="{{ $ikon }} me-2" aria-hidden="true" style="color: var(--hijau-lokal);"></i>
                                    {{ $tanya }}
                                </button>
                            </h3>
                            <div id="faq-{{ $loop->parent->index }}-c{{ $j }}"
                                 class="accordion-collapse collapse {{ $terbuka ? 'show' : '' }}"
                                 aria-labelledby="faq-{{ $loop->parent->index }}-h{{ $j }}"
                                 data-bs-parent="#faq-{{ $loop->parent->index }}">
                                <div class="accordion-body">{{ $jawab }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA berlanjut ke kontak --}}
    <section class="pb-5">
        <div class="container" style="max-width: 820px;">
            <div class="lp-cta text-center px-4 py-5 sr-reveal">
                <div class="position-relative">
                    <h2 class="h4 fw-bold mb-2">Masih ada pertanyaan?</h2>
                    <p class="mb-4" style="color: var(--teks-secondary);">Kanal pengaduan kami punya tenggat tanggapan yang mengikat.</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="{{ route('web.contact') }}" class="btn btn-seekitar px-4">Lihat Kanal Kontak</a>
                        <a href="{{ route('web.home') }}#cara-kerja" class="btn btn-outline-dark px-4">Cara Kerja</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection