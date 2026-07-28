<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Tabel token Sanctum — VERSI LOKAL menggantikan migrasi bawaan paket
 * (dinonaktifkan lewat Sanctum::ignoreMigrations() di AppServiceProvider).
 *
 * KENAPA: migrasi bawaan Sanctum memakai `$table->morphs('tokenable')` —
 * tokenable_id UNSIGNED BIGINT. Tabel users Seekitar memakai UUID, sehingga
 * createToken() pertama di AuthController::verifyOtp langsung gagal:
 * MySQL menolak string UUID di kolom integer (error 1366, HTTP 500) dan
 * TANPA pesan yang menunjuk sebab sebenarnya. Seluruh login API patah.
 *
 * uuidMorphs() mengubahnya menjadi CHAR(36) — satu-satunya perbedaan dari
 * skema bawaan; kolom lain identik agar perilaku Sanctum tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuidMorphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
