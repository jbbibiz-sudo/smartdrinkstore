<?php
// Chemin: database/migrations/2026_01_11_020627_add_retail_stock_remainder_to_products.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('retail_stock_remainder')->default(0)->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('retail_stock_remainder');
        });
    }
};