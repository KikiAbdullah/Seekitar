<?php

use App\Enums\ReviewDirection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

            $table->enum('direction', ReviewDirection::values());
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();

            // HANYA created_at. API_DOCUMENTATION.md §8 menyatakan ulasan
            // tidak bisa diubah setelah dikirim; menyediakan updated_at
            // menyiratkan sebaliknya. Model memakai `const UPDATED_AT = null`.
            $table->timestamp('created_at')->nullable();

            // Satu ulasan per ARAH per pesanan — bukan satu per pesanan,
            // karena PRD §5.5 mewajibkan penilaian dua arah.
            $table->unique(['order_id', 'direction'], 'reviews_order_direction_unique');
            $table->index('store_id', 'reviews_store_id_idx');
            $table->index('reviewee_id', 'reviews_reviewee_id_idx');
            $table->index(['store_id', 'direction'], 'reviews_store_direction_idx');
        });

        // store_id HANYA untuk arah buyer_to_store. Inilah yang mencegah
        // ulasan pembeli ikut menaikkan rating toko (DATABASE.md §4.8).
        DB::statement(<<<'SQL'
            ALTER TABLE reviews ADD CONSTRAINT reviews_store_direction_chk
            CHECK (
                (direction = 'buyer_to_store' AND store_id IS NOT NULL)
                OR
                (direction = 'store_to_buyer' AND store_id IS NULL)
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
