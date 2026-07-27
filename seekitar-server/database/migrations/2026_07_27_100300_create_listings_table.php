<?php

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('description');
            $table->enum('listing_type', ListingType::values());
            $table->decimal('price', 12, 2)->nullable();
            $table->unsignedInteger('stock_qty')->nullable();   // product & rental
            $table->unsignedTinyInteger('slot')->nullable();     // service (kapasitas/hari)
            $table->json('images');
            $table->enum('status', ListingStatus::values())->default(ListingStatus::Active->value);
            $table->softDeletes();
            $table->timestamps();

            $table->index('store_id', 'listings_store_id_idx');
            $table->index('deleted_at', 'listings_deleted_at_idx');
            $table->index('status', 'listings_status_idx');
        });

        // CHECK ditegakkan engine, bukan hanya divalidasi aplikasi: data bisa
        // masuk lewat seeder, job, atau query manual (DATABASE.md §4.4).
        DB::statement(<<<'SQL'
            ALTER TABLE listings ADD CONSTRAINT listings_price_required_chk
            CHECK (listing_type = 'service' OR price IS NOT NULL)
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE listings ADD CONSTRAINT listings_qty_slot_chk
            CHECK (
                (listing_type IN ('product','rental') AND stock_qty IS NOT NULL AND slot IS NULL)
                OR
                (listing_type = 'service' AND slot IS NOT NULL AND stock_qty IS NULL)
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
