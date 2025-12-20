<?php
// database/migrations/2024_01_01_000003_create_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('barcode')->nullable()->unique();
            
            // Relations avec categories et subcategories
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->foreignId('subcategory_id')->nullable()->constrained('subcategories')->onDelete('set null');
            
            $table->string('brand')->nullable();
            $table->string('volume')->nullable();
            
            // Prix
            $table->decimal('unit_price', 10, 2);
            $table->decimal('cost_price', 10, 2)->default(0);
            
            // Stock
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(10);
            
            // Consignation
            $table->boolean('is_consigned')->default(false);
            $table->decimal('consignment_price', 10, 2)->nullable();
            
            // Autres
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour améliorer les performances
            $table->index(['category_id', 'is_active']);
            $table->index('subcategory_id');
            $table->index('stock');
            $table->index('sku');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
