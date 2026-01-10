<?php
// Chemin: database/migrations/2024_01_01_000011_create_sale_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            
            // Détails
            $table->integer('quantity')->comment('Quantité vendue');
            $table->decimal('unit_price', 15, 2)->comment('Prix unitaire au moment de la vente');
            $table->decimal('subtotal', 15, 2)->comment('quantity × unit_price');
            
            $table->timestamps();
            
            // Index
            $table->index('sale_id');
            $table->index('product_id');
            $table->index(['sale_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
