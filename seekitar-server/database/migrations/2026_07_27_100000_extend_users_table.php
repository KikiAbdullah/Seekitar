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

            /*
             * Kedudukan akun SATU kata (DATABASE.md §4.1):
             *   menunggu      — nomor sudah dibuktikan OTP, identitas belum
             *                   disetujui admin (nilai awal setiap akun);
             *   terverifikasi — admin menyetujui wajah+KTP+alamat+koordinat;
             *   ditolak       — berkas belum sesuai (boleh kirim ulang);
             *   diblokir      — akun bermasalah; tokonya ikut dinonaktifkan.
             * ENUM, bukan boolean berantai (is_blocked dkk.): empat keadaan
             * saling eksklusif, dan kolom boolean terpisah bisa menyatakan
             * dua-duanya sekaligus — kontradiksi yang tidak punya makna alur.
             */
            $table->enum('status', ['menunggu', 'terverifikasi', 'ditolak', 'diblokir'])
                ->default('menunggu')->index();

            // Data pribadi (UU PDP) — disimpan di bucket privat, path saja.
            $table->string('ktp_image', 500)->nullable();
            $table->string('selfie_image', 500)->nullable();
            $table->timestamp('ktp_submitted_at')->nullable();

            /*
             * Jejak audit SATU verifikasi (DATABASE.md §4.1):
             *   verified_* — admin menyetujui berkas identitas; sekaligus
             *                mengangkat status → terverifikasi;
             *   rejected_* — admin menolak berkas; status → ditolak;
             *   blocked_*  — admin memblokir akun; status → diblokir.
             *
             * TIDAK ADA stempel tahap nomor HP: nomor dibuktikan kode OTP
             * yang hanya dikirim ke nomornya sendiri — pengujian mata manusia
             * tidak menambah sinyal apa pun, jadi bukti OTP cukup dibaca dari
             * fakta akunnya ada, bukan dari kolom terpisah.
             *
             * Kontrak penulisan: verified_at/_by ditulis dalam transaksi
             * berkunci dan tidak pernah ditimpa (pengajuan ulang identitas
             * yang SUDAH disetujui mengosongkannya — berkas baru belum
             * diperiksa siapa pun). rejected_* justru dipertahankan sampai
             * berkas pengganti disetujui: ia satu-satunya konteks admin
             * apa yang harus diperiksa ulang. blocked_* dibersihkan saat
             * blokir dicabut. Relasi ke penyetuju nullOnDelete supaya
             * hard-delete admin tidak gagal hanya karena jejak audit — aman:
             * tabel ini soft-delete, FK baru menyala pada hapus permanen.
             */
            $table->foreignUuid('verified_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('rejected_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->foreignUuid('blocked_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('blocked_at')->nullable();
            $table->string('blocked_reason', 255)->nullable();

            // Reputasi pembeli — dihitung ulang ReviewObserver dari ulasan
            // store_to_buyer (reviewee = pembeli ini). Cermin
            // stores.rating_avg; bukan diisi manual.
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);

            // NIK terenkripsi; nik_hash agar duplikasi tetap terdeteksi
            // (kolom encrypted tidak bisa di-WHERE, DATABASE.md §4.1).
            $table->string('nik', 255)->nullable();
            $table->char('nik_hash', 64)->nullable()->unique();

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
