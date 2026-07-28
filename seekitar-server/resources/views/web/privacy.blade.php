@extends('web.layout')

@section('title', 'Kebijakan Privasi')
@section('description', 'Bagaimana Seekitar mengumpulkan, memakai, dan melindungi data pribadi sesuai UU PDP.')

@section('content')

    @include('web.partials._hero', [
        'kicker'   => 'Legal · UU PDP',
        'judul'    => 'Kebijakan Privasi',
        'subjudul' => 'Berlaku untuk aplikasi dan situs Seekitar.',
    ])

    <section class="py-5">
        <div class="container" style="max-width: 860px;">

            {{-- Daftar isi: dokumen panjang harus bisa dilompat — pil kecil
                 tetap membungkus rapi di layar sempit. --}}
            <nav class="lp-toc mb-4" aria-label="Daftar isi">
                @foreach ([
                    'Data yang dikumpulkan', 'Cara dilindungi', 'Data yang dibagikan',
                    'Tidak dijual', 'Hak subjek data', 'Kontak',
                ] as $i => $bagian)
                    <a href="#privasi-{{ $i + 1 }}"><span class="lp-no">{{ $i + 1 }}</span> {{ $bagian }}</a>
                @endforeach
            </nav>

            <div class="lp-doc">
                <div class="alert alert-info">
                    Dokumen ini menjelaskan pemrosesan data pribadi menurut
                    <strong>UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi</strong>.
                    Dengan memakai Seekitar, kamu menyetujui ketentuan di bawah ini.
                </div>

                <h2 id="privasi-1" class="h5 fw-bold mt-4">1. Data yang kami kumpulkan</h2>
                <ul>
                    <li><strong>Nomor telepon</strong> — identitas akun. Seekitar tidak memakai kata sandi untuk pengguna aplikasi; masuk dilakukan lewat kode OTP WhatsApp.</li>
                    <li><strong>Nama &amp; foto profil</strong> — ditampilkan ke pihak lawan transaksi.</li>
                    <li><strong>Lokasi</strong> — titik koordinat yang kamu tentukan sendiri, dipakai mencocokkan kebutuhan dengan penyedia terdekat.</li>
                    <li><strong>Foto KTP, swafoto, dan NIK</strong> — hanya bila kamu mengajukan verifikasi identitas untuk membuka toko.</li>
                    <li><strong>Data transaksi</strong> — pesanan, penawaran, ulasan, dan laporan masalah.</li>
                </ul>

                <h2 id="privasi-2" class="h5 fw-bold mt-4">2. Bagaimana data dilindungi</h2>
                <ul>
                    <li>Foto KTP dan swafoto disimpan pada penyimpanan <strong>privat</strong> dan tidak pernah dapat diakses lewat tautan publik.</li>
                    <li>NIK disimpan dalam <strong>bentuk terenkripsi</strong>, bukan teks biasa.</li>
                    <li>Kode OTP disimpan sebagai <strong>hash</strong> dengan masa berlaku 5 menit dan batas 5 kali percobaan.</li>
                    <li>Seluruh lalu lintas memakai HTTPS.</li>
                </ul>

                <h2 id="privasi-3" class="h5 fw-bold mt-4">3. Data yang dibagikan ke pengguna lain</h2>
                <p>Sebelum penawaran diterima, penyedia hanya melihat:</p>
                <ul>
                    <li>Nama yang disingkat (contoh: “Budi S.”)</li>
                    <li>Lokasi yang <strong>dibulatkan</strong>, bukan titik persis</li>
                </ul>
                <p>
                    Nomor telepon dan alamat lengkap baru dibuka setelah penawaran
                    diterima dan pesanan terbentuk — saat kedua pihak memang perlu
                    saling menghubungi.
                </p>

                <h2 id="privasi-4" class="h5 fw-bold mt-4">4. Kami tidak menjual data</h2>
                <p>
                    Data pribadi tidak diperjualbelikan kepada pihak ketiga mana pun.
                    Data hanya dibagikan bila diwajibkan hukum atau atas permintaan
                    aparat penegak hukum yang sah.
                </p>

                <h2 id="privasi-5" class="h5 fw-bold mt-4">5. Hak kamu sebagai subjek data</h2>
                <p>Menurut UU PDP, kamu berhak:</p>
                <ul>
                    <li>mengakses data pribadi yang kami simpan;</li>
                    <li>memperbaiki data yang keliru;</li>
                    <li>meminta <strong>penghapusan</strong> data, termasuk foto KTP dan swafoto;</li>
                    <li>menarik persetujuan pemrosesan data.</li>
                </ul>
                <p>
                    Ajukan lewat
                    <a href="mailto:{{ config('seekitar.contacts.privacy') }}">{{ config('seekitar.contacts.privacy') }}</a>.
                    Kami menanggapi dalam <strong>3×24 jam</strong>.
                </p>
                <p class="text-secondary">
                    Catatan: data transaksi yang sudah selesai dapat tetap disimpan untuk
                    keperluan audit dan penyelesaian sengketa, meski akunmu dihapus.
                </p>

                <h2 id="privasi-6" class="h5 fw-bold mt-4">6. Kontak</h2>
                <p class="mb-0">
                    {{ config('seekitar.company.name') }} —
                    <a href="mailto:{{ config('seekitar.contacts.privacy') }}">{{ config('seekitar.contacts.privacy') }}</a>
                </p>
            </div>
        </div>
    </section>

@endsection
