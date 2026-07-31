<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('link_url', 500)->nullable();
            $table->string('position', 50)->default('feed');
            $table->decimal('price_per_day', 12, 2)->default(50000);
            $table->foreignUuid('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('available');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('impression_count')->default(0);
            $table->unsignedInteger('click_count')->default(0);

            $table->timestamps();

            $table->index(['status', 'position'], 'ads_active_idx');
            $table->index(['buyer_id', 'status'], 'ads_buyer_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
