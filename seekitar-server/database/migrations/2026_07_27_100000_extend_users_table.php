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

            // TIDAK ADA kolom verification_level: level pengguna adalah
            // TURUNAN murni (1 = terdaftar via OTP, 2 = verified2_at terisi,
            // 3 = pemilik toko verified). Menyimpannya sebagai kolom berarti
            // dua sumber kebenaran yang bisa berbeda pendapat — dan memohon
            // bug "kolom bilang 2, stempel KTP bilang belum".

            // Data pribadi (UU PDP) — disimpan di bucket privat, path saja.
            $table->string('ktp_image', 500)->nullable();
            $table->string('selfie_image', 500)->nullable();
            $table->timestamp('ktp_submitted_at')->nullable();
            $table->text('ktp_rejected_reason')->nullable();

            /*
             * Jejak audit DUA tahap verifikasi (DATABASE.md §4.1):
             *   tahap 1 nomor HP · tahap 2 KTP & NIK
             *   (level 2 adalah TURUNAN dari stempel tahap 2 ini)
             *
             * Kontrak penulisan: _at ditulis SEKALI dan tidak pernah ditimpa.
             * Tahap 1 khusus: stempelnya bisa ditulis SISTEM saat OTP daftar/
             * ganti nomor cocok (AuthController) — dalam hal itu _by sengaja
             * NULL, dan NULL tersebut ADALAH jejak "dibuktikan kode OTP,
             * bukan mata admin" (ditampilkan sebagai "Sistem (OTP)").
             * Stempel yang ditulis admin (VerificationController::verifyUser)
             * mengisi _by+_at berpasangan dalam transaksi berkunci. Karena
             * itu CHECK "keduanya NULL atau keduanya terisi" MUSTAHIL
             * dipasang — sah secara kontrak justru "_at terisi, _by NULL",
             * selain ia juga bertabrakan dengan nullOnDelete di bawah
             * (hard-delete admin akan gagal total hanya karena jejak audit).
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
        });

        // NULL-able: baris user harus ada sebelum lokasi diisi (alur OTP).
        SpatialSchema::addLocationColumn('users', nullable: true);
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
