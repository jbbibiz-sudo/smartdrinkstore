<?php
// Chemin: database/migrations/2024_01_01_000013_create_deposits_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            
            // Référence
            $table->string('reference')->unique()->comment('Ex: DEP-2025-001');
            
            // Type
            $table->enum('type', ['outgoing', 'incoming'])
                  ->comment('outgoing = vers clients, incoming = des fournisseurs');
            
            // Relations
            $table->foreignId('deposit_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->comment('Utilisateur');
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null')
                  ->comment('Vente liée si consigne sortante');
            $table->foreignId('purchase_id')->nullable()->constrained()->onDelete('set null')
                  ->comment('Achat lié si consigne entrante');
            
            // Quantités et montants
            $table->integer('quantity');
            $table->decimal('unit_deposit_amount', 10, 2);
            $table->decimal('total_deposit_amount', 10, 2);
            
            // Statut
            $table->enum('status', ['active', 'partially_returned', 'fully_returned', 'written_off'])
                  ->default('active');
            $table->integer('quantity_returned')->default(0);
            $table->integer('quantity_pending')->default(0);
            
            // Dates
            $table->timestamp('issued_at');
            $table->timestamp('expected_return_at')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index(['type', 'status']);
            $table->index('customer_id');
            $table->index('supplier_id');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
