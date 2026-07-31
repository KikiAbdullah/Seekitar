@extends('web.layout')

@section('title', 'Kebijakan Verifikasi Identitas')
@section('description', 'Bagaimana Seekitar memverifikasi identitas pengguna — proses, keamanan, dan privasi data KTP.')

@section('content')

    @include('web.partials._hero', [
        'kicker'   => 'Legal · Verifikasi',
        'judul'    => 'Kebijakan Verifikasi Identitas',
        'subjudul' => 'Proses, tenggat, dan perlindungan data verifikasi toko di Seekitar.',
    ])

    <section class="py-5">
        <div class="container" style="max-width: 860px;">

            <nav class="lp-toc mb-4" aria-label="Daftar isi">
                @foreach ([
                    'Mengapa verifikasi', 'Data yang diperlukan', 'Proses verifikasi',
                    'Keamanan data', 'Tenggat', 'Penolakan', 'Pembaruan data',
                ] as $i => $bagian)
                    <a href="#verif-{{ $i + 1 }}"><span class="lp-no">{{ $i + 1 }}</span> {{ $bagian }}</a>
                @endforeach
            </nav>

            <div class="lp-doc sr-reveal">
                <p style="color: var(--teks-secondary); font-size: 13px;">Terakhir diperbarui: 30 Juli 2026</p>

                <div class="alert alert-info">
                    <i class="ti ti-id me-1" aria-hidden="true"></i>
                    Verifikasi identitas hanya diperlukan untuk <strong>membuka toko</strong>.
                    Pengguna biasa (pembeli) cukup diverifikasi lewat nomor WhatsApp.
                </div>

                <h2 id="verif-1" class="h5 fw-bold mt-4">1. Mengapa verifikasi diperlukan?</h2>
                <p>
                    Verifikasi identitas bertujuan menciptakan lingkungan transaksi yang
                    aman dan dapat dipertanggungjawabkan. Setiap penjual memiliki jejak
                    identitas nyata, sehingga:
                </p>
                <ul>
                    <li>Pembeli bisa bertransaksi dengan percaya diri.</li>
                    <li>Risiko penipuan berkurang secara signifikan.</li>
                    <li>Pelanggar dapat diidentifikasi dan dikenakan sanksi.</li>
                </ul>

                <h2 id="verif-2" class="h5 fw-bold mt-4">2. Data yang diperlukan</h2>
                <p>Untuk verifikasi, kamu perlu mengunggah:</p>
                <ul>
                    <li><strong>Foto KTP</strong> — sisi depan (foto dan data diri).</li>
                    <li><strong>Swafoto</strong> — foto diri memegang KTP di samping wajah, memastikan KTP benar milikmu.</li>
                    <li><strong>NIK</strong> — Nomor Induk Kependudukan, dicocokkan dengan foto KTP.</li>
                </ul>

                <h2 id="verif-3" class="h5 fw-bold mt-4">3. Proses verifikasi</h2>
                <ol>
                    <li>Kamu mengisi data dan mengunggah berkas lewat aplikasi.</li>
                    <li>Admin memeriksa kecocokan nama, wajah, dan NIK pada KTP dan swafoto.</li>
                    <li>Admin juga mencocokkan alamat dan titik lokasi toko dengan peta.</li>
                    <li>Bila lolos, toko mendapat badge "Terverifikasi" dan mulai tayang.</li>
                </ol>

                <h2 id="verif-4" class="h5 fw-bold mt-4">4. Keamanan data verifikasi</h2>
                <ul>
                    <li>Foto KTP dan swafoto disimpan di penyimpanan <strong>privat</strong> — tidak pernah dapat diakses lewat tautan publik.</li>
                    <li>NIK disimpan dalam <strong>bentuk terenkripsi</strong>, bukan teks biasa.</li>
                    <li>Berkas verifikasi tidak ditampilkan ke pengguna lain — termasuk pembeli.</li>
                    <li>Akses ke data verifikasi terbatas pada admin yang berwenang.</li>
                </ul>
                <p>
                    Data verifikasi kamu <strong>tidak akan pernah</strong> diperjualbelikan
                    atau dibagikan ke pihak ketiga tanpa persetujuanmu, kecuali diwajibkan
                    oleh hukum.
                </p>

                <h2 id="verif-5" class="h5 fw-bold mt-4">5. Tenggat verifikasi</h2>
                <p>
                    Admin meninjau berkas verifikasi dalam waktu <strong>maksimal 1×24 jam</strong>
                    sejak pengajuan. Kamu akan mendapat notifikasi hasilnya lewat aplikasi.
                </p>

                <h2 id="verif-6" class="h5 fw-bold mt-4">6. Penolakan dan pengajuan ulang</h2>
                <p>Verifikasi dapat ditolak bila:</p>
                <ul>
                    <li>Foto KTP buram, terpotong, atau tidak terbaca.</li>
                    <li>Wajah pada swafoto tidak cocok dengan KTP.</li>
                    <li>Data yang diisi tidak sesuai dengan KTP.</li>
                    <li>KTP milik orang lain atau tampak diedit.</li>
                </ul>
                <p class="mb-0">
                    Kamu bisa mengajukan ulang kapan saja setelah memperbaiki berkas.
                    Pengajuan ulang tidak dikenakan biaya.
                </p>

                <h2 id="verif-7" class="h5 fw-bold mt-4">7. Pembaruan data verifikasi</h2>
                <p class="mb-0">
                    Jika data KTP-mu berubah (nama, alamat), segera perbarui lewat pengaturan
                    toko. Admin akan meninjau ulang perubahan tersebut. KTP yang tidak
                    diperbarui dapat menyebabkan penonaktifan toko.
                </p>
            </div>
        </div>
    </section>

@endsection