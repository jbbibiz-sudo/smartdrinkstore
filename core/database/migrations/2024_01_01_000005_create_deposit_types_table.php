<?php
// Chemin: database/migrations/2024_01_01_000005_create_deposit_types_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2)->comment('Montant de la caution');
            $table->string('category')->nullable()->comment('bouteille, casier, jerrycan, etc.');
            $table->integer('initial_stock')->default(0)->comment('Stock initial d\'emballages vides');
            $table->integer('current_stock')->default(0)->comment('Stock actuel d\'emballages vides');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_types');
    }
};
