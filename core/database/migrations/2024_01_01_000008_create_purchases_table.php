<?php
// Chemin: database/migrations/2024_01_01_000008_create_purchases_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            
            // Référence
            $table->string('reference')->unique()->comment('Ex: BON-ACH-20260102-ABC123');
            
            // Relations
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
            $table->foreignId('user_id')->constrained()->onDelete('restrict')->comment('Créateur');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Statut
            $table->enum('status', ['draft', 'awaiting_approval', 'confirmed', 'received', 'cancelled'])
                  ->default('draft');
            
            // Montants
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            
            // Paiement
            $table->enum('payment_method', ['cash', 'mobile', 'credit', 'bank_transfer'])->default('cash');
            $table->string('mobile_operator')->nullable()->comment('MTN, Orange, etc.');
            $table->string('mobile_reference')->nullable();
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->integer('credit_days')->nullable();
            
            // Consignes
            $table->decimal('total_deposit_amount', 15, 2)->default(0);
            $table->boolean('has_deposits')->default(false);
            
            // Dates
            $table->date('order_date')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->date('received_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('reference');
            $table->index('supplier_id');
            $table->index('status');
            $table->index('order_date');
            $table->index('due_date');
            $table->index(['status', 'order_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
