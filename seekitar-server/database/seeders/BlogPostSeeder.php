<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        BlogPost::firstOrCreate(
            ['slug' => 'apa-itu-marketplace-hyperlocal'],
            [
                'title'        => 'Apa Itu Marketplace Hyperlocal dan Kenapa Kamu Butuh?',
                'excerpt'      => 'Marketplace hyperlocal menghubungkan pembeli dan penjual dalam satu wilayah — lebih cepat, lebih murah, lebih percaya.',
                'author'       => 'Tim Seekitar',
                'category'     => 'Edukasi',
                'image'        => null,
                'image_alt'    => 'Ilustrasi peta dengan radius yang menghubungkan pembeli dan penjual di sekitar',
                'body'         => '<p>Hyperlocal bukan sekadar kata kunci — ini adalah pendekatan yang mengubah cara warga bertransaksi. Tidak seperti marketplace nasional yang mengandalkan logistik lintas kota, platform hyperlocal seperti Seekitar membatasi pencarian pada radius beberapa kilometer dari lokasimu.</p><p>Keuntungan utamanya: ongkos kirim yang hampir nol, waktu tunggu yang singkat, dan kemampuan untuk melihat barang secara langsung sebelum membayar. Bagi penjual, artinya pelanggan yang benar-benar dekat dan bisa dilayani tanpa perantara.</p><p>Di Indonesia, di mana kepercayaan masih menjadi hambatan utama transaksi daring, pendekatan hyperlocal memberi keuntungan: kamu bisa bertemu langsung dengan penjual, melihat barangnya, dan bertransaksi dengan aman.</p>',
                'published_at' => '2026-07-15 08:00:00',
            ],
        );

        BlogPost::firstOrCreate(
            ['slug' => 'tips-aman-transaksi-cod'],
            [
                'title'        => '5 Tips Aman Bertransaksi COD di Marketplace Lokal',
                'excerpt'      => 'Bayar di tempat memang nyaman, tapi tetap perlu kewaspadaan. Berikut panduan aman bertransaksi COD.',
                'author'       => 'Tim Seekitar',
                'category'     => 'Keamanan',
                'image'        => null,
                'image_alt'    => 'Ilustrasi dua orang bertransaksi dengan uang tunai di tangan',
                'body'         => '<p>COD (Cash on Delivery) adalah metode pembayaran paling populer di Indonesia — termasuk di marketplace lokal. Tapi kenyamanan ini perlu diimbangi dengan kewaspadaan. Berikut 5 tips agar transaksi COD-mu aman:</p><ol><li><strong>Temui di tempat umum</strong> — Pilih lokasi ramai seperti halaman masjid, depan toko, atau area perkantoran.</li><li><strong>Periksa barang sebelum bayar</strong> — Jangan ragu membuka kemasan dan memeriksa kondisi barang.</li><li><strong>Bawa uang pas</strong> — Hindari membawa uang besar untuk menghindari kesulitan kembalian.</li><li><strong>Gunakan fitur Laporkan Masalah</strong> — Jika barang tidak sesuai, segera laporkan lewat aplikasi.</li><li><strong>Ajukan di tempat yang terang</strong> — Transaksi di tempat gelap mempersulit pemeriksaan barang.</li></ol>',
                'published_at' => '2026-07-10 08:00:00',
            ],
        );

        BlogPost::firstOrCreate(
            ['slug' => 'cara-membuka-toko-online'],
            [
                'title'        => 'Cara Membuka Toko Online di Seekitar untuk UMKM',
                'excerpt'      => 'Ingin menjual barang atau jasa? Berikut panduan lengkap membuka toko di Seekitar — dari verifikasi KTP sampai listing pertama.',
                'author'       => 'Tim Seekitar',
                'category'     => 'Panduan',
                'image'        => null,
                'image_alt'    => 'Ilustrasi pemilik toko tersenyum di depan etalase digital',
                'body'         => '<p>Seekitar hadir untuk membantu UMKM di ' . config('seekitar.regency') . ' menjangkau pelanggan terdekat. Membuka toko di Seekitar mudah dan gratis. Berikut langkah-langkahnya:</p><ol><li><strong>Unduh aplikasi</strong> — Tersedia di Android dan iOS (segera).</li><li><strong>Daftar dengan nomor WhatsApp</strong> — Masukkan nomormu, verifikasi dengan OTP.</li><li><strong>Lengkapi profil</strong> — Nama, foto, dan alamat.</li><li><strong>Ajukan verifikasi toko</strong> — Unggah foto KTP dan swafoto untuk verifikasi identitas. Admin akan meninjau maksimal 1×24 jam.</li><li><strong>Atur toko</strong> — Tambahkan foto toko, jam buka, radius layanan, dan metode pembayaran.</li><li><strong>Pasang listing</strong> — Upload barang atau jasa yang ingin dijual atau disewakan.</li></ol><p>Setelah toko tayang, kamu akan mulai menerima siaran kebutuhan dari warga sekitar yang mencari barang atau jasa yang kamu tawarkan.</p>',
                'published_at' => '2026-07-05 08:00:00',
            ],
        );
    }
}
