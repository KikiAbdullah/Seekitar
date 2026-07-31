<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained()->cascadeOnDelete();

            $table->enum('plan', ['pro_monthly', 'boost_listing'])->default('pro_monthly');
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])->default('pending');
            $table->decimal('amount', 12, 2);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('payment_ref', 100)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['status', 'ends_at'], 'subscriptions_active_idx');
            $table->index(['store_id', 'status'], 'subscriptions_store_idx');
            $table->index(['user_id', 'status', 'ends_at'], 'subscriptions_user_active_idx');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_period_chk
            CHECK (ends_at > starts_at)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
