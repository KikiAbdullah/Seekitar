<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Urutan nomor pesanan berbasis database, bukan cache.
 *
 * Sebelumnya `order_number` digenerate lewat counter cache (Redis). Bila cache
 * direset / ganti backend, counter mulai dari 1 lagi dan menabrak nomor yang
 * sudah ada di tabel `orders` → 1062 duplicate key → pesanan gagal dibuat
 * (DATABASE.md §4.7). Dengan urutan di database, nomor tidak mungkin lepas
 * dari baris pesanan yang nyata.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_sequences', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->unsignedBigInteger('seq')->default(0);
            $table->timestamps();
        });

        // Backfill: lanjutkan dari nomor tertinggi yang sudah terpakai,
        // supaya order berikutnya tidak menabrak baris yang sudah ada.
        $maxByDay = DB::table('orders')
            ->selectRaw('CAST(SUBSTRING(order_number, 5, 8) AS DATE) AS day')
            ->selectRaw('MAX(CAST(SUBSTRING_INDEX(order_number, "-", -1) AS UNSIGNED)) AS seq')
            ->where('order_number', 'like', 'SKT-%')
            ->groupBy('day')
            ->get();

        foreach ($maxByDay as $row) {
            if ($row->day !== null && $row->seq > 0) {
                DB::table('order_sequences')->insertOrIgnore([
                    'date' => $row->day,
                    'seq'  => $row->seq,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_sequences');
    }
};
