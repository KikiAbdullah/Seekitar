<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Akun Super Admin Pertama
    |--------------------------------------------------------------------------
    |
    | Nomor ini dipakai `RolesAndPermissionsSeeder` untuk membuat pemilik
    | pertama panel admin. Sengaja lewat env, bukan ditanam di kode: kalau
    | tidak, nomor contoh yang sama akan menjadi super-admin di produksi.
    |
    | Format wajib E.164 tanpa tanda plus (`62xxxxxxxxxx`), sama dengan
    | kolom `users.phone` (DATABASE.md §4.1).
    |
    */

    'super_admin_phone' => env('SEEKITAR_SUPER_ADMIN_PHONE', '6280000000000'),

    /*
    | Kredensial login PANEL ADMIN (web).
    |
    | Terpisah dari OTP: panel dibuka di browser desktop, sering tanpa
    | WhatsApp di perangkat yang sama (Server_Implementation_Guide §18A.5).
    |
    | ⚠️ WAJIB diganti di produksi. Kata sandi default hanya agar lingkungan
    | pengembangan bisa langsung dipakai setelah `db:seed`.
    */
    'super_admin_email'    => env('SEEKITAR_SUPER_ADMIN_EMAIL', 'admin@seekitar.test'),
    'super_admin_password' => env('SEEKITAR_SUPER_ADMIN_PASSWORD', 'password'),

    /*
    |--------------------------------------------------------------------------
    | Wilayah Operasi
    |--------------------------------------------------------------------------
    |
    | Seekitar dikunci pada satu kabupaten/kota (PRD §2). Kode wilayah
    | mengikuti BPS, mis. `3578` untuk Kota Surabaya.
    |
    */

    'regency'      => env('SEEKITAR_REGENCY', 'Kabupaten Pasuruan'),
    'regency_code' => env('SEEKITAR_REGENCY_CODE', '3514'),

    /*
    |--------------------------------------------------------------------------
    | Kanal Pengaduan Wajib
    |--------------------------------------------------------------------------
    |
    | BUKAN sekadar praktik baik: PSE terdaftar WAJIB menyediakan kanal
    | pengaduan yang mudah ditemukan, dan alamat yang tidak aktif dapat
    | menyebabkan penolakan pendaftaran (BRANDING-GUIDELINE.md §8.3).
    |
    | Wajib tercantum di footer situs, halaman Bantuan, dan email
    | transaksional — karena itu disentralkan di sini, bukan ditulis ulang
    | di tiap template.
    |
    | Tenggat tanggapan mengikat dan berbeda per kanal.
    |
    */

    'contacts' => [
        // 2x24 jam
        'complaint' => env('SEEKITAR_EMAIL_COMPLAINT', 'pengaduan@seekitar.id'),
        // 1x24 jam — pelaporan konten ilegal
        'abuse'     => env('SEEKITAR_EMAIL_ABUSE', 'abuse@seekitar.id'),
        // 3x24 jam — hak subjek data UU PDP (akses, koreksi, penghapusan)
        'privacy'   => env('SEEKITAR_EMAIL_PRIVACY', 'privasi@seekitar.id'),
        'security'  => env('SEEKITAR_EMAIL_SECURITY', 'security@seekitar.id'),
        'whatsapp'  => env('SEEKITAR_WHATSAPP', '6281234567890'),
    ],

    /*
    | Identitas penyelenggara — ditampilkan di footer & halaman legal.
    */
    'company' => [
        'name'    => env('SEEKITAR_COMPANY_NAME', 'PT Seekitar Digital Nusantara'),
        'address' => env('SEEKITAR_COMPANY_ADDRESS', 'Bangil, Kabupaten Pasuruan, Jawa Timur'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Registrasi PSE Kominfo
    |--------------------------------------------------------------------------
    |
    | Nomor tanda daftar PSE (Penyelenggara Sistem Elektronik) wajib
    | ditampilkan di situs dan aplikasi. Diisi setelah pendaftaran PSE
    | Lingkup Privat selesai (Permenkominfo 5/2020 ps. 7).
    |
    */

    'pse' => [
        'registration_number' => env('SEEKITAR_PSE_NUMBER', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Link Download Aplikasi
    |--------------------------------------------------------------------------
    |
    | Tautan unduhan aplikasi mobile — dipakai di section CTA di halaman
    | publik. Saat app belum terbit, isi dengan placeholder `#`; setelah
    | rilis, ganti dengan URL Google Play Store yang sebenarnya.
    |
    */

    'play_store_url' => env('SEEKITAR_PLAY_STORE_URL', '#'),

    /*
    |--------------------------------------------------------------------------
    | Monetisasi & Harga
    |--------------------------------------------------------------------------
    |
    | Seekitar Fase 1 gratis 100%. Harga di bawah mulai berlaku di Fase 2
    | dan dapat diubah lewat pengaturan admin.
    |
    */

    'monetization' => [
        // Pro Monthly (langganan toko)
        'pro_monthly_price'    => (int) env('SEEKITAR_PRO_MONTHLY_PRICE', 30000),
        'pro_monthly_duration' => 30, // hari

        // Boost Listing (per 7 hari)
        'boost_listing_price'    => (int) env('SEEKITAR_BOOST_LISTING_PRICE', 7500),
        'boost_listing_duration' => 7, // hari

        // Biaya Flat per Transaksi
        'service_fee_enabled' => (bool) env('SEEKITAR_SERVICE_FEE_ENABLED', false),
        'service_fee_amount'  => (int) env('SEEKITAR_SERVICE_FEE_AMOUNT', 1500),

        // Iklan Banner Lokal
        'banner_price_per_day' => (int) env('SEEKITAR_BANNER_PRICE_PER_DAY', 50000),
    ],

];
