<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Satu pintu untuk seluruh seeder.
 *
 * Urutan TIDAK boleh diubah: `RolesAndPermissionsSeeder` membuat akun
 * super-admin yang langsung diberi role, jadi role & permission harus ada
 * lebih dulu (`Server_Implementation_Guide.md` §19.2).
 *
 * Seeder bawaan Laravel di berkas ini sebelumnya membuat user dengan kolom
 * `email` — kolom itu tidak ada di Seekitar, yang memakai nomor telepon
 * sebagai identitas (DATABASE.md §4.1), sehingga `db:seed` selalu gagal.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,  // 1. role, permission, super-admin
            CategorySeeder::class,             // 2. 24 kategori (wajib produksi)
            SettingSeeder::class,              // 3. nilai default tabel settings
            BlogPostSeeder::class,             // 4. blog awal yang sudah ditulis
        ]);

        // Data contoh HANYA untuk pengembangan — jangan pernah di produksi.
        // AdminUserSeeder membuat akun dengan kata sandi yang tertulis di
        // repositori, jadi penjagaannya diulang di dalam seeder itu sendiri.
        if (app()->environment('local', 'testing')) {
            $this->call([
                AdminUserSeeder::class,   // akun contoh tiap peran
                DummyDataSeeder::class,   // data kecil & DETERMINISTIK
                DemoDataSeeder::class,    // ratusan baris untuk semua tabel
                StoreMapSeeder::class,    // 50 toko bertitik pasti untuk Peta Toko
            ]);
        }
    }
}
