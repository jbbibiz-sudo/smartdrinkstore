<?php
// Chemin: database/migrations/2024_01_01_000000_create_cash_registers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('status')->default(true)->comment('Ouvert/Fermé');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Index pour optimisation
            $table->index('code');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};