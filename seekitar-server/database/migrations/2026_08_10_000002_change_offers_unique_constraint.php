<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            // Drop old unique constraint
            $table->dropUnique('offers_req_store_unique');
            
            // Add new unique constraint including status
            // This allows multiple rejected offers for same (request_id, store_id)
            // but only one pending/accepted per store per request
            $table->unique(['request_id', 'store_id', 'status'], 'offers_req_store_status_unique');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropUnique('offers_req_store_status_unique');
            $table->unique(['request_id', 'store_id'], 'offers_req_store_unique');
        });
    }
};