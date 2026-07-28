<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kredensial khusus panel admin.
 *
 * TEMUAN YANG MEMICU MIGRASI INI
 * ------------------------------
 * `Server_Implementation_Guide.md` §18A.5 menetapkan login admin memakai
 * **email + kata sandi** (`admin-login:'.$request->input('email')`), dan
 * §6.1 menyebut panel admin memakai sesi Laravel biasa. Namun tabel `users`
 * di `DATABASE.md` §4.1 **tidak punya kolom `email` maupun `password`** —
 * identitas Seekitar berbasis nomor telepon + OTP.
 *
 * Akibatnya `Auth::attempt(['email' => ..., 'password' => ...])` mustahil
 * berhasil: `EloquentUserProvider::validateCredentials()` memanggil
 * `getAuthPassword()` yang mengembalikan NULL, sehingga akun super-admin
 * hasil `RolesAndPermissionsSeeder` tidak akan pernah bisa masuk.
 *
 * KENAPA KOLOM TERPISAH, BUKAN MENGUBAH ALUR OTP
 * ----------------------------------------------
 * Panel admin diakses lewat browser desktop, sering tanpa WhatsApp di
 * perangkat yang sama. Memaksa OTP di sini berarti admin harus meraih ponsel
 * setiap kali sesi 120 menit habis.
 *
 * Keduanya NULL-able: hanya segelintir akun yang punya kredensial ini.
 * Pengguna biasa tetap masuk lewat OTP dan tidak pernah punya kata sandi —
 * itulah alasan `email` unik tapi boleh kosong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // NULL-able + unique: MySQL memperlakukan setiap NULL sebagai
            // nilai berbeda, jadi ribuan pengguna tanpa email tetap sah.
            $table->string('email')->nullable()->unique()->after('phone');
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->string('password')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn(['email', 'email_verified_at', 'password']);
        });
    }
};
