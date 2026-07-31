<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->string('label', 50);
            $table->text('address');
            $table->decimal('latitude', 8)->nullable();
            $table->decimal('longitude', 8)->nullable();
            $table->string('regency', 100)->nullable();
            $table->string('regency_code', 10)->nullable();
            $table->string('recipient_name', 100)->nullable();
            $table->string('recipient_phone', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('user_id', 'user_addresses_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
