<?php
// Chemin: database/migrations/2024_01_01_000014_create_deposit_returns_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_returns', function (Blueprint $table) {
            $table->id();
            
            // Référence
            $table->string('reference')->unique()->comment('Ex: RET-2025-001');
            
            // Relations
            $table->foreignId('deposit_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->comment('Utilisateur qui traite le retour');
            
            // Quantités retournées
            $table->integer('quantity_returned');
            $table->decimal('refund_amount', 10, 2);
            
            // État des emballages
            $table->integer('quantity_good_condition')->default(0);
            $table->integer('quantity_damaged')->default(0);
            $table->integer('quantity_lost')->default(0);
            
            // Pénalités
            $table->decimal('damage_penalty', 10, 2)->default(0);
            $table->decimal('delay_penalty', 10, 2)->default(0);
            $table->decimal('total_penalty', 10, 2)->default(0);
            $table->decimal('net_refund', 10, 2);
            
            // Détails
            $table->timestamp('returned_at');
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('deposit_id');
            $table->index('returned_at');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_returns');
    }
};
