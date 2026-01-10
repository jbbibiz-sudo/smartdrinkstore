<?php
// Chemin: database/migrations/2024_01_01_000009_create_purchase_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            
            // Quantités
            $table->integer('quantity')->default(1);
            $table->integer('quantity_received')->default(0)->comment('Pour réceptions partielles');
            
            // Prix
            $table->decimal('unit_cost', 15, 2)->comment('Prix d\'achat unitaire');
            $table->decimal('subtotal', 15, 2)->comment('quantity × unit_cost');
            
            // Consignes
            $table->boolean('is_consigned')->default(false);
            $table->foreignId('deposit_type_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('deposit_quantity')->nullable()->comment('Nombre d\'emballages consignés');
            $table->decimal('unit_deposit_amount', 15, 2)->nullable();
            $table->decimal('total_deposit_amount', 15, 2)->nullable();
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index('purchase_id');
            $table->index('product_id');
            $table->index(['purchase_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
