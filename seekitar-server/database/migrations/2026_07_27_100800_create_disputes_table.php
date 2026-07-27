<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('reported_by')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 40);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('open');

            // Tanpa kolom ini, SLA 1x24 jam di PRD §5.5 tidak bisa diukur.
            $table->timestamp('response_deadline');
            $table->timestamp('first_responded_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('order_id', 'disputes_order_id_idx');
            $table->index('status', 'disputes_status_idx');
            $table->index(['status', 'response_deadline'], 'disputes_sla_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
