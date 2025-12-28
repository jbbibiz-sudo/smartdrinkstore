<?php
// database/migrations/2025_12_28_205136_create_product_supplier_table.php

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
        Schema::create('product_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            
            // Prix d'achat chez ce fournisseur
            $table->decimal('cost_price', 10, 2)->nullable();
            
            // Délai de livraison en jours
            $table->integer('delivery_days')->nullable();
            
            // Quantité minimale de commande
            $table->integer('minimum_order_quantity')->default(1);
            
            // Fournisseur principal (préféré)
            $table->boolean('is_preferred')->default(false);
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Index pour optimiser les requêtes
            $table->index(['product_id', 'supplier_id']);
            $table->unique(['product_id', 'supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_supplier');
    }
};