<?php

use App\Enums\StoreType;
use App\Enums\VerificationStatus;
use App\Support\SpatialSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);

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

            // Kapabilitas toko — sumber badge (BRANDING-GUIDELINE.md §4.2).
            $table->boolean('accepts_cod')->default(true);
            $table->boolean('offers_delivery')->default(false);
            $table->boolean('allows_pickup')->default(true);

            $table->json('operating_hours')->nullable();
            $table->string('npwp', 20)->nullable();
            $table->string('bank_account', 100)->nullable();

            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->boolean('is_active')->default(true);

            $table->enum('verification_status', VerificationStatus::values())->default(VerificationStatus::Pending->value);
            $table->text('rejected_reason')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('user_id', 'stores_user_id_idx');
            $table->index('deleted_at', 'stores_deleted_at_idx');
            $table->index('is_active', 'stores_is_active_idx');
            $table->index('offers_delivery', 'stores_delivery_idx');
        });

        SpatialSchema::addLocationColumn('stores', nullable: false);
        SpatialSchema::addSpatialIndex('stores', 'stores_location_spatial');

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
