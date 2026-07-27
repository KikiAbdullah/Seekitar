<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('reviewee_id')->constrained('users')->cascadeOnDelete();

            // store_id HANYA terisi untuk arah buyer_to_store. Inilah yang
            // memperbaiki bug rating lintas-toko (DATABASE.md §4.8).
            $table->foreignUuid('store_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('direction', 20);   // buyer_to_store | store_to_buyer
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            // Satu ulasan per ARAH per pesanan — bukan satu per pesanan,
            // karena PRD §5.5 mewajibkan penilaian dua arah.
            $table->unique(['order_id', 'direction'], 'reviews_order_direction_unique');
            $table->index('store_id', 'reviews_store_id_idx');
            $table->index('reviewee_id', 'reviews_reviewee_id_idx');
            $table->index(['store_id', 'direction'], 'reviews_store_direction_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
