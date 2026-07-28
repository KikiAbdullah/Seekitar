@extends('web.layout')

@section('title', 'Pusat Bantuan')
@section('description', 'Pertanyaan umum seputar Seekitar: cara memesan, pembayaran COD, dan penyelesaian masalah.')

@section('content')
<div class="container py-5" style="max-width: 820px">
    <h1 class="h2 fw-bold mb-4">Pusat Bantuan</h1>

    <div class="accordion" id="faq">
        @foreach ([
            [
                'Bagaimana cara masuk ke aplikasi?',
                'Masukkan nomor WhatsApp-mu, lalu kami kirim kode OTP 6 digit. Tidak ada kata sandi. Kode berlaku 5 menit dan hanya bisa dipakai sekali.',
            ],
            [
                'Seekitar tidak pernah meminta kode OTP saya, benar?',
                'Benar. Siapa pun yang meminta kode OTP-mu — termasuk yang mengaku petugas Seekitar — sedang berusaha mengambil alih akunmu. Jangan pernah membagikannya.',
            ],
            [
                'Bagaimana cara membayar?',
                'Pembayaran langsung antara kamu dan penjual: tunai saat bertemu (COD) atau transfer ke rekening penjual. Seekitar tidak menampung dana dan tidak menyediakan rekening bersama.',
            ],
            [
                'Bisakah uang saya dikembalikan?',
                'Karena dana tidak melewati Seekitar, kami tidak dapat mengembalikannya secara otomatis. Bila barang atau jasa tidak sesuai, buka pesanan lalu pilih Laporkan Masalah. Pesanan akan dibekukan dan admin menengahi dalam 1×24 jam.',
            ],
            [
                'Apa itu "Pasang Kebutuhan"?',
                'Kalau barang atau jasa yang kamu cari belum ada di katalog, tulis saja kebutuhanmu. Permintaan itu disiarkan ke penyedia terdekat yang kategorinya cocok, lalu mereka mengirim penawaran.',
            ],
            [
                'Kenapa permintaan saya tidak dilihat penyedia jauh?',
                'Pencocokan berlaku dua arah: penyedia harus berada dalam radius yang kamu pilih, dan kamu harus berada dalam radius layanan mereka. Warung dengan jangkauan 5 km tidak menerima permintaan dari 12 km.',
            ],
            [
                'Kenapa harus unggah KTP untuk membuka toko?',
                'Agar setiap penjual punya jejak identitas yang bisa dipertanggungjawabkan. Foto KTP disimpan pada penyimpanan privat dan tidak pernah ditampilkan ke pengguna lain.',
            ],
            [
                'Kapan nomor telepon saya terlihat penjual?',
                'Sebelum penawaran diterima, penyedia hanya melihat namamu yang disingkat dan lokasi yang dibulatkan. Nomor dan alamat lengkap baru terbuka setelah pesanan terbentuk.',
            ],
            [
                'Berapa lama saya bisa memberi ulasan?',
                '7 hari setelah pesanan berstatus selesai. Ulasan tidak dapat diubah setelah dikirim.',
            ],
            [
                'Bagaimana menghapus akun dan data saya?',
                'Kirim permintaan ke ' . config('seekitar.contacts.privacy') . '. Kami menanggapi dalam 3×24 jam. Data transaksi yang sudah selesai tetap disimpan untuk keperluan audit dan sengketa.',
            ],
        ] as $i => [$tanya, $jawab])
            <div class="accordion-item">
                <h2 class="accordion-header" id="faq-h{{ $i }}">
                    <button class="accordion-button {{ $i === 0 ? '' : 'collapsed' }}" type="button"
                            data-bs-toggle="collapse" data-bs-target="#faq-c{{ $i }}"
                            aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="faq-c{{ $i }}">
                        {{ $tanya }}
                    </button>
                </h2>
                <div id="faq-c{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                     aria-labelledby="faq-h{{ $i }}" data-bs-parent="#faq">
                    <div class="accordion-body">{{ $jawab }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="alert alert-info mt-4">
        Masih ada pertanyaan? Lihat <a href="{{ route('web.contact') }}">kanal kontak</a> kami.
    </div>
</div>
@endsection
