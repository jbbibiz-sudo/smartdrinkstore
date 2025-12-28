<?php

// Fichier : database/migrations/2025_12_27_201937_create_sales_and_sale_items_tables.php

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
        // ========================
        // TABLE SALES (VENTES)
        // ========================
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique()->comment('Numéro de facture unique');
            
            // Relations
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->onDelete('set null')
                ->comment('Client (null = vente comptoir)');
            
            // Informations de vente
            $table->enum('type', ['counter', 'wholesale'])
                ->default('counter')
                ->comment('counter = comptoir, wholesale = gros');
                
            $table->enum('payment_method', ['cash', 'mobile', 'credit'])
                ->default('cash')
                ->comment('Mode de paiement');
            
            // Montants
            $table->decimal('total_amount', 15, 2)->comment('Montant total TTC');
            $table->decimal('discount_amount', 15, 2)->default(0)->comment('Montant de la remise');
            $table->decimal('paid_amount', 15, 2)->default(0)->comment('Montant payé');
            
            $table->timestamps();
            
            // Index pour performances
            $table->index('invoice_number');
            $table->index('customer_id');
            $table->index('type');
            $table->index('payment_method');
            $table->index('created_at');
        });

        // ========================
        // TABLE SALE_ITEMS (LIGNES DE VENTE)
        // ========================
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('sale_id')
                ->constrained('sales')
                ->onDelete('cascade')
                ->comment('Vente parente');
                
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('restrict')
                ->comment('Produit vendu');
            
            // Détails de la ligne
            $table->integer('quantity')->comment('Quantité vendue');
            $table->decimal('unit_price', 15, 2)->comment('Prix unitaire au moment de la vente');
            $table->decimal('subtotal', 15, 2)->comment('Sous-total de la ligne');
            
            $table->timestamps();
            
            // Index pour performances
            $table->index('sale_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};