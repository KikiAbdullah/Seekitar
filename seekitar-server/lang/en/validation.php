<?php

/*
 * Pesan validasi berbahasa Indonesia.
 *
 * KENAPA DI FOLDER `en/`, BUKAN `id/`
 * -----------------------------------
 * `config('app.locale')` sengaja tetap `en`. Menggantinya ke `id` membuat
 * Laravel mencari SELURUH berkas terjemahan di `lang/id/` — dan setiap kunci
 * yang belum diterjemahkan muncul apa adanya ke pengguna sebagai
 * "validation.required". Menerjemahkan berkas yang memang dipakai jauh lebih
 * aman daripada memindahkan locale lalu menambal kekurangannya satu per satu.
 *
 * Hanya kunci yang benar-benar dipakai Seekitar yang diterjemahkan; sisanya
 * dibiarkan memakai bawaan framework lewat penggabungan array di bawah.
 */

$bawaan = require __DIR__.'/../../vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php';

return array_replace_recursive($bawaan, [

    'accepted'         => 'Kolom :attribute wajib disetujui.',
    'after'            => 'Kolom :attribute harus berisi tanggal setelah :date.',
    'after_or_equal'   => 'Kolom :attribute harus berisi tanggal setelah atau sama dengan :date.',
    'array'            => 'Kolom :attribute harus berupa array.',
    'before'           => 'Kolom :attribute harus berisi tanggal sebelum :date.',
    'between'          => [
        'array'   => 'Kolom :attribute harus memiliki antara :min sampai :max item.',
        'file'    => 'Ukuran berkas :attribute harus antara :min sampai :max kilobyte.',
        'numeric' => 'Kolom :attribute harus bernilai antara :min sampai :max.',
        'string'  => 'Kolom :attribute harus terdiri dari :min sampai :max karakter.',
    ],
    'boolean'          => 'Kolom :attribute harus bernilai benar atau salah.',
    'confirmed'        => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi yang dimasukkan salah.',
    'date'             => 'Kolom :attribute bukan tanggal yang valid.',
    'date_format'      => 'Kolom :attribute tidak cocok dengan format :format.',
    'different'        => 'Kolom :attribute dan :other harus berbeda.',
    'email'            => 'Kolom :attribute harus berupa alamat email yang valid.',
    'exists'           => 'Nilai :attribute yang dipilih tidak valid.',
    'file'             => 'Kolom :attribute harus berupa berkas.',
    'filled'           => 'Kolom :attribute wajib diisi.',
    'image'            => 'Kolom :attribute harus berupa gambar.',
    'in'               => 'Nilai :attribute yang dipilih tidak valid.',
    'integer'          => 'Kolom :attribute harus berupa bilangan bulat.',
    'max'              => [
        'array'   => 'Kolom :attribute tidak boleh lebih dari :max item.',
        'file'    => 'Ukuran berkas :attribute tidak boleh lebih dari :max kilobyte.',
        'numeric' => 'Kolom :attribute tidak boleh lebih dari :max.',
        'string'  => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
    ],
    'mimes'            => 'Kolom :attribute harus berupa berkas berjenis: :values.',
    'min'              => [
        'array'   => 'Kolom :attribute harus memiliki minimal :min item.',
        'file'    => 'Ukuran berkas :attribute minimal :min kilobyte.',
        'numeric' => 'Kolom :attribute minimal bernilai :min.',
        'string'  => 'Kolom :attribute harus terdiri dari minimal :min karakter.',
    ],
    'not_in'           => 'Nilai :attribute yang dipilih tidak valid.',
    'numeric'          => 'Kolom :attribute harus berupa angka.',
    'prohibited'       => 'Kolom :attribute dilarang diisi.',
    'regex'            => 'Format :attribute tidak valid.',
    'required'         => 'Kolom :attribute wajib diisi.',
    'required_if'      => 'Kolom :attribute wajib diisi bila :other bernilai :value.',
    'same'             => 'Kolom :attribute dan :other harus sama.',
    'size'             => [
        'array'   => 'Kolom :attribute harus berisi :size item.',
        'file'    => 'Ukuran berkas :attribute harus :size kilobyte.',
        'numeric' => 'Kolom :attribute harus bernilai :size.',
        'string'  => 'Kolom :attribute harus terdiri dari :size karakter.',
    ],
    'string'           => 'Kolom :attribute harus berupa teks.',
    'unique'           => 'Nilai :attribute sudah digunakan.',
    'uploaded'         => 'Berkas :attribute gagal diunggah.',
    'url'              => 'Format :attribute tidak valid.',

    // Kunci bersarang untuk Illuminate\Validation\Rules\Password.
    'password' => [
        'letters'       => 'Kolom :attribute harus memuat minimal satu huruf.',
        'mixed'         => 'Kolom :attribute harus memuat huruf besar dan huruf kecil.',
        'numbers'       => 'Kolom :attribute harus memuat minimal satu angka.',
        'symbols'       => 'Kolom :attribute harus memuat minimal satu simbol.',
        'uncompromised' => 'Kolom :attribute pernah muncul dalam kebocoran data. Pilih yang lain.',
    ],

    /*
     * Nama kolom yang dibacakan dalam pesan. Tanpa ini, pengguna melihat
     * "Kolom current_password wajib diisi" — nama teknis yang tidak ada di
     * label mana pun di layar.
     */
    'attributes' => [
        'name'                  => 'nama',
        'email'                 => 'email',
        'password'              => 'kata sandi',
        'current_password'      => 'kata sandi saat ini',
        'password_confirmation' => 'konfirmasi kata sandi',
        'phone'                 => 'nomor telepon',
        'reason'                => 'alasan',
        'resolution'            => 'hasil penyelesaian',
        'resolution_note'       => 'catatan keputusan',
        'slug'                  => 'slug',
        'icon'                  => 'ikon',
        'parent_id'             => 'kategori induk',
        'sort_order'            => 'urutan',
        'verification_level'    => 'level verifikasi',
        'title'                 => 'judul',
        'description'           => 'deskripsi',
        'price'                 => 'harga',
        'rating'                => 'rating',
        'comment'               => 'komentar',
        'settings'              => 'pengaturan',
    ],
]);
