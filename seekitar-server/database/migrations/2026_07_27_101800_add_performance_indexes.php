<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->index(['store_id', 'status'], 'offers_store_status_idx');
            $table->index('expires_at', 'offers_expires_at_idx');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->index('order_type', 'orders_order_type_idx');
            $table->index(['created_at', 'status'], 'orders_created_status_idx');
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->index(['reviewee_id', 'direction'], 'reviews_reviewee_direction_idx');
        });

        Schema::table('customer_requests', function (Blueprint $table): void {
            $table->index(['user_id', 'status'], 'requests_user_status_idx');
            $table->index(['status', 'expires_at'], 'requests_status_expires_idx');
            $table->index('category_id', 'requests_category_id_idx');
        });

        Schema::table('listings', function (Blueprint $table): void {
            $table->index(['store_id', 'status'], 'listings_store_status_idx');
            $table->index('listing_type', 'listings_listing_type_idx');
        });

        Schema::table('stores', function (Blueprint $table): void {
            $table->index(['status', 'is_active'], 'stores_status_active_idx');
        });

        Schema::table('disputes', function (Blueprint $table): void {
            $table->index(['order_id', 'status'], 'disputes_order_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('offers', fn (Blueprint $t) => $t->dropIndex('offers_store_status_idx'));
        Schema::table('offers', fn (Blueprint $t) => $t->dropIndex('offers_expires_at_idx'));

        Schema::table('orders', fn (Blueprint $t) => $t->dropIndex('orders_order_type_idx'));
        Schema::table('orders', fn (Blueprint $t) => $t->dropIndex('orders_created_status_idx'));

        Schema::table('reviews', fn (Blueprint $t) => $t->dropIndex('reviews_reviewee_direction_idx'));

        Schema::table('customer_requests', fn (Blueprint $t) => $t->dropIndex('requests_user_status_idx'));
        Schema::table('customer_requests', fn (Blueprint $t) => $t->dropIndex('requests_status_expires_idx'));
        Schema::table('customer_requests', fn (Blueprint $t) => $t->dropIndex('requests_category_id_idx'));

        Schema::table('listings', fn (Blueprint $t) => $t->dropIndex('listings_store_status_idx'));
        Schema::table('listings', fn (Blueprint $t) => $t->dropIndex('listings_listing_type_idx'));

        Schema::table('stores', fn (Blueprint $t) => $t->dropIndex('stores_status_active_idx'));

        Schema::table('disputes', fn (Blueprint $t) => $t->dropIndex('disputes_order_status_idx'));
    }
};
