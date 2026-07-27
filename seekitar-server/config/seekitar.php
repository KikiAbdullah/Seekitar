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

];
