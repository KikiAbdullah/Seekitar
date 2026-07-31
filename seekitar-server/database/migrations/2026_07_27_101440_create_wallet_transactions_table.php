<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->decimal('amount', 12);
            $table->decimal('balance_before', 12);
            $table->decimal('balance_after', 12);
            $table->string('description', 255)->nullable();
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('status', 50)->default('completed');
            $table->timestamp('created_at')->nullable();

            $table->index(['wallet_id', 'created_at'], 'wallet_transactions_wallet_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
