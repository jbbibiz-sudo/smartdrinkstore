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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // BON-ACH-20260102-ABC123
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
            $table->foreignId('user_id')->constrained()->onDelete('restrict'); // Utilisateur qui crée l'achat
            
            // Statut de la commande
            $table->enum('status', [
                'draft',      // Brouillon
                'awaiting_approval', // En attente
                'confirmed',  // Confirmée
                'received',   // Reçue
                'cancelled'   // Annulée
            ])->default('draft');
            
            // Montants
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            
            // Paiement
            $table->enum('payment_method', ['cash', 'mobile', 'credit', 'bank_transfer'])->default('cash');
            $table->string('mobile_operator')->nullable(); // MTN, Orange, etc.
            $table->string('mobile_reference')->nullable(); // Référence de transaction
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->integer('credit_days')->nullable();
            
            // Consignes
            $table->decimal('total_deposit_amount', 15, 2)->default(0); // Montant total des consignes
            $table->boolean('has_deposits')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            // Dates
            $table->date('order_date')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->date('received_date')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('supplier_id');
            $table->index('status');
            $table->index('order_date');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
