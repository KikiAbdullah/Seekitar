<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->string('slug', 50)->unique();

            // RESTRICT, bukan SET NULL: menghapus induk dengan SET NULL akan
            // diam-diam mempromosikan seluruh subkategori (DATABASE.md §4.3).
            $table->unsignedInteger('parent_id')->nullable();
            $table->string('icon', 50)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('categories')->restrictOnDelete();
            $table->index('parent_id', 'categories_parent_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
