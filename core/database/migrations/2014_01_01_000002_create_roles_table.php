<?php
// Chemin: C:\smartdrinkstore\core\database\migrations\2024_01_01_000002_create_roles_table.php
// Migration: Table des rôles (Admin, Manager, Caissier, etc.)

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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // admin, manager, cashier, inventory_manager
            $table->string('display_name'); // Administrateur, Gestionnaire, Caissier
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
