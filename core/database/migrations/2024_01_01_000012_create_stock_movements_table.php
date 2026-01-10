<?php
// Chemin: database/migrations/2024_01_01_000012_create_stock_movements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            
            // Produit concerné
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            // Type de mouvement
            $table->enum('type', ['in', 'out', 'adjustment'])->comment('Entrée, Sortie, Ajustement');
            $table->string('movement_type')->nullable()
                  ->comment('purchase, sale, adjustment, inventory, damage, loss, transfer, return, supplier_return');
            
            // Quantité et prix
            $table->integer('quantity');
            $table->integer('previous_stock')->nullable();
            $table->integer('new_stock')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable()->comment('Prix unitaire au moment du mouvement');
            
            // Traçabilité
            $table->string('reason')->nullable();
            $table->string('reference')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Relations avec achats/ventes
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->onDelete('set null')
                  ->comment('Si mouvement vient d\'un achat');
            $table->foreignId('sale_id')->nullable()->constrained('sales')->onDelete('set null')
                  ->comment('Si mouvement vient d\'une vente');
            
            $table->timestamps();
            
            // Index
            $table->index('product_id');
            $table->index('type');
            $table->index('movement_type');
            $table->index('purchase_id');
            $table->index('sale_id');
            $table->index('created_at');
            $table->index(['product_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
