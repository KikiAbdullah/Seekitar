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

];
