<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wishlist pengguna (PRD §5.1, API §4.4).
 *
 * TEMUAN: `API_DOCUMENTATION.md` §4.4 mendefinisikan tiga endpoint favorit
 * (`POST`/`DELETE /listings/{id}/favorite`, `GET /favorites`), tetapi
 * `DATABASE.md` §4 tidak pernah mendefinisikan tabelnya — kata "favorit"
 * bahkan tidak muncul sama sekali di sana. Tanpa tabel ini ketiga endpoint
 * itu mustahil dibuat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('listing_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Satu pengguna hanya bisa memfavoritkan satu listing sekali.
            // Inilah yang membuat POST bersifat idempoten seperti dijanjikan
            // API §4.4 — pengiriman ulang tidak menghasilkan baris ganda.
            $table->unique(['user_id', 'listing_id'], 'favorites_user_listing_unique');

            // Daftar favorit selalu diurutkan terbaru dulu, per pengguna.
            $table->index(['user_id', 'created_at'], 'favorites_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
