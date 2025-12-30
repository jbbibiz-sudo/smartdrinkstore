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
        Schema::create('credit_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2); // Montant du paiement
            $table->enum('payment_method', ['cash', 'mobile', 'bank_transfer', 'check'])->default('cash');
            $table->date('payment_date'); // Date effective du paiement
            $table->text('notes')->nullable(); // Référence, notes
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null'); // Qui a enregistré le paiement
            $table->timestamps();
            $table->softDeletes(); // Pour permettre l'annulation de paiements si nécessaire

            // Index pour performances
            $table->index('sale_id');
            $table->index('payment_date');
        });

        // Ajouter des colonnes à la table sales pour tracking
        Schema::table('sales', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('payment_method'); // Date d'échéance pour les crédits
            $table->integer('credit_days')->default(30)->after('due_date'); // Nombre de jours de crédit
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_payments');
        
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'credit_days']);
        });
    }
};