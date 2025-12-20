<?php
// database/migrations/2024_01_01_000004_create_stock_movements_table.php

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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->enum('type', ['in', 'out', 'adjustment']);
            $table->integer('quantity');
            $table->integer('previous_stock')->nullable(); // Ajoutez cette ligne
            $table->integer('new_stock')->nullable(); // Ajoutez cette ligne
            $table->string('reason')->nullable();
            $table->string('reference')->nullable(); // Ajoutez cette ligne si vous voulez l'utiliser
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('product_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
