@extends('web.layout')

@section('title', 'Kebijakan Privasi — Seekitar')
@section('meta_description', 'Bagaimana Seekitar mengumpulkan, memakai, dan melindungi data pribadi sesuai UU PDP.')

@section('content')

  @include('web.partials._hero', [
    'kicker'   => 'Legal · UU PDP',
    'judul'    => 'Kebijakan Privasi',
    'subjudul' => 'Berlaku untuk aplikasi dan situs Seekitar.',
  ])

  <section class="py-8 py-lg-11">
    <div class="container">
      <div class="row g-5">
        <div class="col-lg-4">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
              <div class="doc-toc" data-aos="fade-up" data-aos-duration="900">
                <span class="eyebrow">Daftar Isi</span>
                <nav class="d-grid gap-1 mt-4" aria-label="Daftar isi">
                  @foreach ([
                    'Data yang dikumpulkan', 'Cara dilindungi', 'Data yang dibagikan',
                    'Tidak dijual', 'Cookie & teknologi', 'Pelanggaran data',
                    'Hak subjek data', 'Kontak',
                  ] as $i => $bagian)
                    <a class="toc-link" href="#privasi-{{ $i + 1 }}">
                      <i class="ti ti-chevron-right"></i>{{ $bagian }}
                    </a>
                  @endforeach
                </nav>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-8">
          <div class="doc-content" data-aos="fade-up" data-aos-delay="150" data-aos-duration="900">
            <p class="fs-4 text-muted mb-4">Terakhir diperbarui: 30 Juli 2026</p>

            <div class="alert alert-info d-flex align-items-start gap-2">
              <i class="ti ti-circle-check mt-1" aria-hidden="true"></i>
              <div>
                Dokumen ini menjelaskan pemrosesan data pribadi menurut
                <strong>UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi</strong>.
                Dengan memakai Seekitar, kamu menyetujui ketentuan di bawah ini.
              </div>
            </div>

            <h2 id="privasi-1">1. Data yang kami kumpulkan</h2>
            <ul>
              <li><strong>Nomor telepon</strong> — identitas akun. Seekitar tidak memakai kata sandi untuk pengguna aplikasi; masuk dilakukan lewat kode OTP WhatsApp.</li>
              <li><strong>Nama &amp; foto profil</strong> — ditampilkan ke pihak lawan transaksi.</li>
              <li><strong>Lokasi</strong> — titik koordinat yang kamu tentukan sendiri, dipakai mencocokkan kebutuhan dengan penyedia terdekat.</li>
              <li><strong>Foto KTP, swafoto, dan NIK</strong> — hanya bila kamu mengajukan verifikasi identitas untuk membuka toko.</li>
              <li><strong>Data transaksi</strong> — pesanan, penawaran, ulasan, dan laporan masalah.</li>
            </ul>

            <h2 id="privasi-2">2. Bagaimana data dilindungi</h2>
            <ul>
              <li>Foto KTP dan swafoto disimpan pada penyimpanan <strong>privat</strong> dan tidak pernah dapat diakses lewat tautan publik.</li>
              <li>NIK disimpan dalam <strong>bentuk terenkripsi</strong>, bukan teks biasa.</li>
              <li>Kode OTP disimpan sebagai <strong>hash</strong> dengan masa berlaku 5 menit dan batas 5 kali percobaan.</li>
              <li>Seluruh lalu lintas memakai HTTPS.</li>
            </ul>

            <h2 id="privasi-3">3. Data yang dibagikan ke pengguna lain</h2>
            <p>Sebelum penawaran diterima, penyedia hanya melihat:</p>
            <ul>
              <li>Nama yang disingkat (contoh: "Budi S.")</li>
              <li>Lokasi yang <strong>dibulatkan</strong>, bukan titik persis</li>
            </ul>
            <p>
              Nomor telepon dan alamat lengkap baru dibuka setelah penawaran
              diterima dan pesanan terbentuk — saat kedua pihak memang perlu
              saling menghubungi.
            </p>

            <h2 id="privasi-4">4. Kami tidak menjual data</h2>
            <p>
              Data pribadi tidak diperjualbelikan kepada pihak ketiga mana pun.
              Data hanya dibagikan bila diwajibkan hukum atau atas permintaan
              aparat penegak hukum yang sah.
            </p>

            <h2 id="privasi-5">5. Cookie &amp; teknologi sejenis</h2>
            <p>
              Situs Seekitar memakai cookie dan teknologi penyimpanan lokal
              (<em>localStorage</em>) untuk:
            </p>
            <ul>
              <li>menyimpan preferensi cookie-mu;</li>
              <li>menjaga sesi masukmu di situs web.</li>
            </ul>
            <p>
              Kami tidak memakai cookie pelacakan lintas-situs, iklan
              tersegmentasi, atau alat analitik pihak ketiga yang merekam
              perilaku individu. Seluruh data yang tersimpan bersifat
              fungsional dan tidak dibagikan ke pihak luar.
            </p>
            <p>
              Kamu dapat menghapus cookie kapan saja lewat pengaturan
              peramban. Penghapusan tidak mengganggu fungsi aplikasi
              utama yang berjalan di ponsel.
            </p>

            <h2 id="privasi-6">6. Pelanggaran data</h2>
            <p>
              Bila terjadi insiden yang mengakibatkan kebocoran, akses
              tidak sah, atau kehilangan data pribadi, kami:
            </p>
            <ul>
              <li>mengidentifikasi dampak dan mengambil langkah pengamanan
                  dalam <strong>1×24 jam</strong> sejak insiden diketahui;</li>
              <li>memberitahukan subjek data yang terdampak dalam
                  <strong>3×24 jam</strong> lewat saluran yang tersedia
                  (notifikasi aplikasi atau SMS), sesuai Pasal 35 UU PDP;</li>
              <li>melaporkan insiden ke <strong>Kementerian Komunikasi dan
                  Digital</strong> dalam batas waktu yang ditetapkan
                  peraturan perundang-undangan.</li>
            </ul>
            <p class="text-muted">
              Laporan dugaan celah keamanan dapat dikirim langsung ke
              <a href="mailto:{{ config('seekitar.contacts.security') }}" class="text-primary fw-semibold">{{ config('seekitar.contacts.security') }}</a>
              — kami tanggapi dalam 1×24 jam.
            </p>

            <h2 id="privasi-7">7. Hak kamu sebagai subjek data</h2>
            <p>Menurut UU PDP, kamu berhak:</p>
            <ul>
              <li>mengakses data pribadi yang kami simpan;</li>
              <li>memperbaiki data yang keliru;</li>
              <li>meminta <strong>penghapusan</strong> data, termasuk foto KTP dan swafoto;</li>
              <li>menarik persetujuan pemrosesan data.</li>
            </ul>
            <p>
              Ajukan lewat
              <a href="mailto:{{ config('seekitar.contacts.privacy') }}" class="text-primary fw-semibold">{{ config('seekitar.contacts.privacy') }}</a>.
              Kami menanggapi dalam <strong>3×24 jam</strong>.
            </p>
            <p class="text-muted">
              Catatan: data transaksi yang sudah selesai dapat tetap disimpan untuk
              keperluan audit dan penyelesaian sengketa, meski akunmu dihapus.
            </p>

            <h2 id="privasi-8">8. Kontak</h2>
            <p>
              {{ config('seekitar.company.name') }} —
              <a href="mailto:{{ config('seekitar.contacts.privacy') }}" class="text-primary fw-semibold">{{ config('seekitar.contacts.privacy') }}</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
