<?php

use App\Enums\RequestStatus;
use App\Support\SpatialSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('description');

            // RESTRICT: kategori tidak boleh dihapus jika masih dipakai.
            $table->unsignedInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();

            $table->decimal('budget_min', 12, 2)->nullable();
            $table->decimal('budget_max', 12, 2)->nullable();
            $table->json('images')->nullable();                  // maks 3 foto
            $table->decimal('radius_km', 5, 2)->default(15.00);  // default PRD
            $table->timestamp('required_date')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('extended_at')->nullable();
            $table->unsignedTinyInteger('extension_count')->default(0);
            $table->enum('status', RequestStatus::values())->default(RequestStatus::Open->value);
            $table->uuid('accepted_offer_id')->nullable();
            $table->timestamps();

            $table->index('user_id', 'cr_user_id_idx');
            $table->index('category_id', 'cr_category_id_idx');
            // Kesamaan (status) sebelum rentang (expires_at) — DATABASE.md §7.1
            $table->index(['status', 'expires_at'], 'cr_status_expires_idx');
            $table->index(['category_id', 'status'], 'cr_category_status_idx');
            $table->index('accepted_offer_id', 'cr_accepted_offer_id_idx');
        });

        SpatialSchema::addLocationColumn('customer_requests', nullable: false);
        SpatialSchema::addSpatialIndex('customer_requests', 'cr_location_spatial');
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_requests');
    }
};
