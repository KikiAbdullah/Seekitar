<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupon_usages', function (Blueprint $table): void {
            $table->unique('order_id', 'coupon_usages_order_id_unique');
            $table->unique(['coupon_id', 'user_id', 'order_id'], 'coupon_usages_coupon_user_order_unique');
        });
    }

    public function down(): void
    {
        Schema::table('coupon_usages', function (Blueprint $table): void {
            $table->dropUnique('coupon_usages_order_id_unique');
            $table->dropUnique('coupon_usages_coupon_user_order_unique');
        });
    }
};
