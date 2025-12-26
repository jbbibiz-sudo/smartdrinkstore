<?php
// Chemin: C:\smartdrinkstore\core\database\migrations\2024_01_01_000003_create_permissions_table.php
// Migration: Table des permissions (view_products, create_sale, etc.)

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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // view_products, create_sale, manage_users
            $table->string('display_name'); // Voir les produits, Créer une vente
            $table->text('description')->nullable();
            $table->string('group')->nullable(); // products, sales, users, reports
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
