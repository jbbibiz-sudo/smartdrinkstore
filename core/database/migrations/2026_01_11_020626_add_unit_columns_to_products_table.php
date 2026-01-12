<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up():void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('base_unit_id')->nullable()->index('idx_products_base_unit'); // REFERENCES product_units(id)
            $table->decimal('base_unit_volume', 8, 3)->nullable();
            $table->string('base_unit_volume_unit')->default('L');
            $table->integer('base_unit_quantity')->nullable();
            $table->foreignId('retail_unit_id')->nullable()->index('idx_products_retail_unit'); // REFERENCES product_units(id)
        });
    }

    public function down():void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['idx_products_base_unit']);
            $table->dropIndex(['idx_products_retail_unit']);
            $table->dropColumn([
                'base_unit_id',
                'base_unit_volume',
                'base_unit_volume_unit',
                'base_unit_quantity',
                'retail_unit_id'
            ]);
        });
    }
};
