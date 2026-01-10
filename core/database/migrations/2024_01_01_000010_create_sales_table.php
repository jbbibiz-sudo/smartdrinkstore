<?php
// Chemin: database/migrations/2024_01_01_000010_create_sales_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            
            // Référence
            $table->string('invoice_number')->unique()->comment('Numéro de facture unique');
            
            // Relations
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null')
                  ->comment('Client (null = vente comptoir)');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')
                  ->comment('Vendeur');
            
            // Type et paiement
            $table->enum('type', ['counter', 'wholesale'])->default('counter')
                  ->comment('counter = comptoir, wholesale = gros');
            $table->enum('payment_method', ['cash', 'mobile', 'credit'])->default('cash');
            
            // Montants
            $table->decimal('total_amount', 15, 2)->comment('Montant total TTC');
            $table->decimal('discount', 15, 2)->default(0)->comment('Remise');
            $table->decimal('paid_amount', 15, 2)->default(0)->comment('Montant payé');
            $table->decimal('deposit_amount', 10, 2)->default(0)->comment('Montant consignes');
            $table->decimal('grand_total', 10, 2)->default(0)->comment('Total = total_amount + deposit_amount');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('invoice_number');
            $table->index('customer_id');
            $table->index('user_id');
            $table->index('type');
            $table->index('payment_method');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
