<?php
// Chemin: database/migrations/2024_01_01_000006_create_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            // Identifiants
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('barcode')->nullable()->unique();
            
            // Relations
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->foreignId('subcategory_id')->nullable()->constrained('subcategories')->onDelete('set null');
            
            // Informations produit
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
            $table->boolean('has_deposit')->default(false)->comment('Produit nécessite une consigne');
            $table->foreignId('deposit_type_id')->nullable()->constrained('deposit_types')->onDelete('set null');
            $table->integer('units_per_deposit')->default(1)->comment('Nombre d\'unités par emballage');
            
            // Autres
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index(['category_id', 'is_active']);
            $table->index('subcategory_id');
            $table->index('stock');
            $table->index('sku');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
