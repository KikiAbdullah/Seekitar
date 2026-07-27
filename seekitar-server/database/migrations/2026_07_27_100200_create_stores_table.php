<?php

use App\Support\SpatialSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

            // SET di MySQL; string dipisah koma di SQLite.
            $table->string('store_type', 40);
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

            $table->string('verification_status', 20)->default('pending');
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
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
