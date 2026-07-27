<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('customer_requests')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();

            // price = nilai pekerjaan; ongkos antar TERPISAH agar pengurutan
            // "termurah" tidak menghukum penyedia yang jujur (DATABASE.md §4.6).
            $table->decimal('price', 12, 2);
            $table->decimal('additional_cost', 12, 2)->default(0);
            $table->string('additional_cost_note', 150)->nullable();

            $table->string('estimation_time', 100);              // teks untuk pembeli
            $table->unsignedSmallInteger('estimated_hours')->nullable(); // untuk sorting
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('expires_at');
            $table->timestamps();

            // Satu toko hanya boleh satu penawaran per permintaan.
            $table->unique(['request_id', 'store_id'], 'offers_req_store_unique');
            $table->index('status', 'offers_status_idx');
            $table->index(['request_id', 'status'], 'offers_request_status_idx');
            $table->index(['status', 'expires_at'], 'offers_status_expires_idx');
            $table->index('estimated_hours', 'offers_estimated_hours_idx');
        });

        // FK ditambahkan setelah offers ada (referensi melingkar).
        Schema::table('customer_requests', function (Blueprint $table) {
            $table->foreign('accepted_offer_id')->references('id')->on('offers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_requests', function (Blueprint $table) {
            $table->dropForeign(['accepted_offer_id']);
        });
        Schema::dropIfExists('offers');
    }
};
