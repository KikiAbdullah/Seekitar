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
