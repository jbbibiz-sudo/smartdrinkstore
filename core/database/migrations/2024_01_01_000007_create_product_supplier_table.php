<?php
// Chemin: database/migrations/2024_01_01_000007_create_product_supplier_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            
            $table->decimal('cost_price', 10, 2)->nullable()->comment('Prix d\'achat chez ce fournisseur');
            $table->integer('delivery_days')->nullable()->comment('Délai de livraison en jours');
            $table->integer('minimum_order_quantity')->default(1)->comment('Quantité minimale de commande');
            $table->boolean('is_preferred')->default(false)->comment('Fournisseur principal');
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['product_id', 'supplier_id']);
            $table->unique(['product_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_supplier');
    }
};
