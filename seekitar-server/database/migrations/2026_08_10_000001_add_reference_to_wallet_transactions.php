<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Referensi pembayaran unik untuk top up (TOPT-{uuid}) & penarikan.
     *
     * Dipakai agar transaksi uang bisa di-trace & idempoten: reference unik
     * mencegah kredit/penarikan ganda untuk referensi pembayaran yang sama.
     */
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('reference', 64)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn('reference');
        });
    }
};
