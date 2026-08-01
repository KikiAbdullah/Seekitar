<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->uuidMorphs('reportable');
            $table->string('reason', 50);
            $table->text('description')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->uuid('resolved_by')->nullable();
            $table->timestamps();

            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
