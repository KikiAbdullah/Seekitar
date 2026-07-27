<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // UNIQUE pada device_id SAJA: satu ponsel hanya boleh terikat ke
            // satu akun, kalau tidak notifikasi pemilik lama tetap masuk ke
            // ponsel bekas (DATABASE.md §4.9a).
            $table->string('device_id', 100)->unique();
            $table->string('fcm_token', 255);
            $table->string('platform', 10);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('user_id', 'user_devices_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
