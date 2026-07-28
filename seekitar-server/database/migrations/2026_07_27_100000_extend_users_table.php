<?php

use App\Support\SpatialSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyesuaikan tabel users bawaan Laravel dengan DATABASE.md §4.1.
 * Seekitar memakai UUID + nomor HP (tanpa email/password).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone', 15)->unique();

            /*
             * Kredensial khusus PANEL ADMIN (email + password). Pengguna
             * biasa masuk lewat OTP dan tidak pernah punya kata sandi, maka
             * ketiganya NULL-able. email tetap unik: MySQL memperlakukan
             * tiap NULL sebagai nilai berbeda, jadi ribuan baris kosong sah.
             * Tanpa kolom ini Auth::attempt() admin mustahil berhasil
             * (Server_Implementation_Guide.md §18A.5).
             */
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();

            $table->string('name', 100);
            $table->string('avatar_url', 500)->nullable();
            $table->string('address', 255)->nullable();

            // 1 = HP, 2 = KTP, 3 = Pro. TIDAK ADA level 0 atau 4.
            $table->unsignedTinyInteger('verification_level')->default(1);

            // Data pribadi (UU PDP) — disimpan di bucket privat, path saja.
            $table->string('ktp_image', 500)->nullable();
            $table->string('selfie_image', 500)->nullable();
            $table->timestamp('ktp_submitted_at')->nullable();
            $table->text('ktp_rejected_reason')->nullable();

            /*
             * Jejak audit DUA tahap verifikasi admin (DATABASE.md §4.1):
             *   tahap 1 nomor HP · tahap 2 KTP & NIK (→ level 2)
             *
             * Kontrak penulisan (ditegakkan VerificationController::verifyUser):
             * _by dan _at SELALU diisi berpasangan, SEKALI, dalam satu
             * transaksi berkunci — tidak pernah ditimpa. Karena itu CHECK
             * "keduanya NULL atau keduanya terisi" sengaja tidak dipasang:
             * ia bertabrakan dengan nullOnDelete di bawah (hard-delete admin
             * akan gagal total hanya karena jejak audit).
             *
             * nullOnDelete sendiri aman: tabel ini soft-delete, jadi FK baru
             * menyala saat admin benar-benar dihapus permanen.
             */
            $table->foreignUuid('verified1_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('verified1_at')->nullable();
            $table->foreignUuid('verified2_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('verified2_at')->nullable();

            // Reputasi pembeli — dihitung ulang ReviewObserver dari ulasan
            // store_to_buyer (reviewee = pembeli ini). Cermin
            // stores.rating_avg; bukan diisi manual.
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);

            // NIK terenkripsi; nik_hash agar duplikasi tetap terdeteksi
            // (kolom encrypted tidak bisa di-WHERE, DATABASE.md §4.1).
            $table->string('nik', 255)->nullable();
            $table->char('nik_hash', 64)->nullable()->unique();

            $table->boolean('is_blocked')->default(false);
            $table->string('blocked_reason', 255)->nullable();
            $table->timestamp('blocked_at')->nullable();

            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            $table->index('deleted_at', 'users_deleted_at_idx');
            $table->index('verification_level', 'users_verification_level_idx');
        });

        // NULL-able: baris user harus ada sebelum lokasi diisi (alur OTP).
        SpatialSchema::addLocationColumn('users', nullable: true);
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
