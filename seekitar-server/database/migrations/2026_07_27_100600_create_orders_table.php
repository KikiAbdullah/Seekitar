<?php

use App\Support\SpatialSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Referensi manusiawi: UUID tidak mungkin dibacakan lewat telepon.
            $table->string('order_number', 20)->unique();

            $table->foreignUuid('buyer_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('store_id')->constrained()->restrictOnDelete();

            // Sumber pesanan: listing_id XOR offer_id.
            $table->foreignUuid('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('listing_id')->nullable()->constrained()->nullOnDelete();

            // Nilainya SAMA dengan listing_type agar bisa disalin langsung.
            $table->string('order_type', 20);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('total_amount', 12, 2);
            $table->string('status', 30)->default('menunggu_konfirmasi');

            $table->string('payment_method', 20);
            $table->string('delivery_method', 20)->default('pickup');
            $table->text('shipping_address')->nullable();
            $table->string('payment_proof_url', 500)->nullable();
            $table->timestamp('payment_confirmed_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignUuid('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason', 255)->nullable();

            $table->timestamps();

            $table->index('buyer_id', 'orders_buyer_id_idx');
            $table->index('store_id', 'orders_store_id_idx');
            $table->index('status', 'orders_status_idx');
            $table->index('created_at', 'orders_created_at_idx');
            $table->index(['buyer_id', 'status', 'created_at'], 'orders_buyer_status_idx');
            $table->index(['store_id', 'status', 'created_at'], 'orders_store_status_idx');
        });

        // Koordinat tujuan antar (DATABASE.md §4.7). NULL-able karena hanya
        // terisi saat delivery_method = 'delivery'; pesanan pickup tidak punya
        // titik tujuan. Tanpa kolom ini penjual tak bisa dinavigasikan ke
        // alamat pembeli — shipping_address hanyalah teks bebas.
        SpatialSchema::addLocationColumn('orders', nullable: true, column: 'shipping_location');
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
