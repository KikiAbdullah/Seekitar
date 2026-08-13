<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table): void {
            $table->string('idempotency_key', 128)->nullable()->after('reference');
            $table->unique(['wallet_id', 'idempotency_key'], 'wallet_transactions_wallet_idempotency_unique');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('idempotency_key', 128)->nullable()->after('order_number');
            $table->unique(['buyer_id', 'idempotency_key'], 'orders_buyer_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table): void {
            $table->dropUnique('wallet_transactions_wallet_idempotency_unique');
            $table->dropColumn('idempotency_key');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique('orders_buyer_idempotency_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};
