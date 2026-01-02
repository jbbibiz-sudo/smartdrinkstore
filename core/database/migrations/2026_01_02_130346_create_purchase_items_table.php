<?php

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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            
            // Quantités
            $table->integer('quantity')->default(1);
            $table->integer('quantity_received')->default(0); // Pour réceptions partielles
            
            // Prix
            $table->decimal('unit_cost', 15, 2); // Prix d'achat unitaire
            $table->decimal('subtotal', 15, 2);  // quantity * unit_cost
            
            // Consignes (si le produit a des emballages consignés)
            $table->boolean('is_consigned')->default(false);
            $table->foreignId('deposit_type_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('deposit_quantity')->nullable(); // Nombre d'emballages consignés
            $table->decimal('unit_deposit_amount', 15, 2)->nullable(); // Montant unitaire de consigne
            $table->decimal('total_deposit_amount', 15, 2)->nullable(); // Montant total des consignes
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index('purchase_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
