<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('description');
            $table->string('listing_type', 20);       // product|service|rental
            $table->decimal('price', 12, 2)->nullable();
            $table->unsignedInteger('stock_qty')->nullable();   // product & rental
            $table->unsignedTinyInteger('slot')->nullable();     // service (kapasitas/hari)
            $table->json('images');
            $table->string('status', 20)->default('active');
            $table->softDeletes();
            $table->timestamps();

            $table->index('store_id', 'listings_store_id_idx');
            $table->index('deleted_at', 'listings_deleted_at_idx');
            $table->index('status', 'listings_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
