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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->onDelete('restrict');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'mobile_money', 'bank_transfer', 'check']);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->date('payment_date');
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index('customer_id');
            $table->index('payment_date');
            $table->index('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
