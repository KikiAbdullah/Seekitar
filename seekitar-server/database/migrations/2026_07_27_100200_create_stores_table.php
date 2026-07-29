<?php

use App\Enums\StoreStatus;
use App\Enums\StoreType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel toko — versi 2.3: kedudukan eksplisit + koordinat kolom terpisah.
 *
 * KENAPA `status` MENGGANTIKAN `verification_status`
 * --------------------------------------------------
 * Bukan sekadar ganti nama: nilainya kini empat keadaan saling eksklusif
 * — pending, verified, rejected, BLOCKED — dengan jejak audit tunggal per
 * keadaan (verifikasi pengguna sudah memakai pola yang sama). "Diblokir"
 * bukan turunan `is_active`: toko diblokir BERSAMA pemiliknya (orang yang
 * bermasalah = tokonya ikut bermasalah), dan membuka blokir orang
 * mengembalikan kedudukan tokonya yang sebenarnya.
 *
 * KENAPA LOKASI = latitude + longitude, BUKAN KOLOM POINT
 * -------------------------------------------------------
 * POINT SRID 4326 memaksa SEMUA pembacaan lewat fungsi spasial mentah
 * (ST_Latitude/ST_Distance_Sphere/MBRContains) — dan fungsi itu tidak bisa
 * ditulis lewat Eloquent murni. Padahal skala Seekitar hanya SATU
 * kabupaten: kotak pembatas DECIMAL berindeks (`whereBetween`, Eloquent
 * murni) menyaring kandidat dalam milidetik, lalu haversine di PHP
 * menyisakan lingkaran akuratnya. DECIMAL(11,8)/(12,8) presisinya ±1,1 mm
 * — JAUH lebih halus dari POINT bawaan — dan tidak ada lagi jebakan
 * urutan sumbu `axis-order=long-lat` yang bisa salah diam-diam.
 *
 * Jejak audit mengikuti kontrak tulis-sekali users: verified_* oleh admin
 * penyetuju, rejected_* oleh penolak, blocked_* oleh pemblokir — masing-
 * masing FK nullOnDelete supaya admin yang dihapus permanen tidak
 * menyeret jejaknya ikut hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);

            // Foto tampak depan TOKO — tepat SATU, dan itu saja. Etalase
            // tunggal menjaga tabel tetap ringan dan admin tidak perlu
            // menilai album. PUBLIK seperti avatar_url (bukan data pribadi).
            $table->string('photo', 500)->nullable();

            // Lingkup uniqueness nama toko + geofencing (DATABASE.md §4.2).
            $table->string('regency', 100);
            $table->char('regency_code', 4)->nullable();

            // SET asli MySQL: satu toko boleh menjual barang + jasa + sewa
            // sekaligus, dan engine menolak nilai di luar daftar.
            // DATABASE.md §4.2 menjelaskan kenapa SET, bukan JSON/pivot.
            $table->set('store_type', StoreType::values());
            $table->json('category_ids');
            $table->string('address', 255)->nullable();
            $table->decimal('service_radius_km', 5, 2)->default(5.00);

            // Kedudukan toko di peta — kolom BIASA, bukan POINT. 8 digit
            // desimal ≈ 1,1 mm: cukup untuk membedakan dua sisi jalan yang
            // sama, dan menyisakan ruang untuk pembulatan sumber GPS.
            // whereBetween pada pasangan terindeks ini adalah tahap kasar
            // pencarian radius (lihat Store::scopeWithinBox + Jarak).
            $table->decimal('latitude', 11, 8);
            $table->decimal('longitude', 12, 8);

            // Kapabilitas toko — sumber badge (BRANDING-GUIDELINE.md §4.2).
            $table->boolean('accepts_cod')->default(true);
            $table->boolean('offers_delivery')->default(false);
            $table->boolean('allows_pickup')->default(true);

            $table->json('operating_hours')->nullable();
            $table->string('npwp', 20)->nullable();

            // Rekening tujuan: bank_account = bank + nomor (mis. "BSI
            // 7112345678"), bank_account_name = ATAS NAMANYA — transfer
            // manual butuh keduanya untuk memastikan tidak salah kirim.
            $table->string('bank_account', 100)->nullable();
            $table->string('bank_account_name', 100)->nullable();

            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->boolean('is_active')->default(true);

            // Kedudukan: empat keadaan eksklusif, satu kata per keadaan.
            $table->enum('status', StoreStatus::values())->default(StoreStatus::Pending->value);

            // Jejak persetujuan — tulis-sekali, tidak pernah ditimpa.
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('verified_by')->nullable()
                ->constrained('users')->nullOnDelete();

            // Jejak penolakan — alasan tampil ke pemilik (wajib ada isinya).
            $table->timestamp('rejected_at')->nullable();
            $table->foreignUuid('rejected_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->text('rejected_reason')->nullable();

            // Jejak pemblokiran — toko diblokir BERSAMA pemiliknya;
            // triplet ini yang membedakan "nonaktif biasa" dari "bermasalah".
            $table->timestamp('blocked_at')->nullable();
            $table->foreignUuid('blocked_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('blocked_reason', 255)->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('user_id', 'stores_user_id_idx');
            $table->index('deleted_at', 'stores_deleted_at_idx');
            $table->index('is_active', 'stores_is_active_idx');
            $table->index('offers_delivery', 'stores_delivery_idx');
            $table->index('status', 'stores_status_idx');
            // Kotak pembatas pencarian radius membaca latitude dulu baru
            // longitude — urutan kolom indeks mengikuti urutan penyaringan.
            $table->index(['latitude', 'longitude'], 'stores_latlng_idx');
        });

        // Toko wajib bisa dijangkau lewat minimal satu cara (DATABASE.md §4.2).
        DB::statement(<<<'SQL'
            ALTER TABLE stores ADD CONSTRAINT stores_fulfilment_chk
            CHECK (offers_delivery = 1 OR allows_pickup = 1)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
