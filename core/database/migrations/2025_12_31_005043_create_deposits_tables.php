<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MIGRATION : Système de Consignes Bidirectionnel
 * ============================================================================
 * 
 * Tables créées :
 * 1. deposit_types       - Types d'emballages consignables
 * 2. deposits            - Transactions de consignes (sorties/entrées)
 * 3. deposit_returns     - Retours d'emballages
 */

return new class extends Migration
{
    /**
     * Exécuter les migrations.
     */
    public function up(): void
    {
        // ========================================
        // 1. TYPES D'EMBALLAGES CONSIGNABLES
        // ========================================
        Schema::create('deposit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // Ex: "Bouteille 1L", "Casier 24 bouteilles"
            $table->string('code')->unique();          // Ex: "BTL-1L", "CSR-24"
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);  // Montant de la caution
            $table->string('category')->nullable();    // bouteille, casier, jerrycan, etc.
            $table->integer('initial_stock')->default(0);  // Stock d'emballages vides disponibles
            $table->integer('current_stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // ========================================
        // 2. TRANSACTIONS DE CONSIGNES
        // ========================================
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();     // Ex: "DEP-2025-001"
            
            // Type de consigne
            $table->enum('type', ['outgoing', 'incoming'])
                  ->comment('outgoing = vers clients, incoming = des fournisseurs');
            
            // Relations
            $table->foreignId('deposit_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->comment('Utilisateur qui effectue la transaction');
            
            // Vente ou achat lié (optionnel)
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null')
                  ->comment('Vente liée si consigne sortante');
            $table->foreignId('purchase_id')->nullable()->constrained()->onDelete('set null')
                  ->comment('Achat lié si consigne entrante');
            
            // Détails de la consigne
            $table->integer('quantity');                         // Nombre d'emballages
            $table->decimal('unit_deposit_amount', 10, 2);       // Montant unitaire
            $table->decimal('total_deposit_amount', 10, 2);      // Montant total = quantity × unit
            
            // Statut
            $table->enum('status', ['active', 'partially_returned', 'fully_returned', 'written_off'])
                  ->default('active');
            $table->integer('quantity_returned')->default(0);     // Nombre déjà retournés
            $table->integer('quantity_pending')->default(0);      // Nombre en attente = quantity - returned
            
            // Dates
            $table->timestamp('issued_at');                       // Date de sortie/entrée
            $table->timestamp('expected_return_at')->nullable();  // Date de retour prévue
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour performances
            $table->index(['type', 'status']);
            $table->index('customer_id');
            $table->index('supplier_id');
        });

        // ========================================
        // 3. RETOURS D'EMBALLAGES
        // ========================================
        Schema::create('deposit_returns', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();     // Ex: "RET-2025-001"
            
            // Relation avec la consigne originale
            $table->foreignId('deposit_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->comment('Utilisateur qui traite le retour');
            
            // Détails du retour
            $table->integer('quantity_returned');                   // Nombre retournés
            $table->decimal('refund_amount', 10, 2);               // Montant remboursé
            
            // État des emballages
            $table->integer('quantity_good_condition')->default(0); // Bon état
            $table->integer('quantity_damaged')->default(0);        // Endommagés
            $table->integer('quantity_lost')->default(0);           // Perdus
            
            // Pénalités éventuelles
            $table->decimal('damage_penalty', 10, 2)->default(0);  // Pénalité casse
            $table->decimal('delay_penalty', 10, 2)->default(0);   // Pénalité retard
            $table->decimal('total_penalty', 10, 2)->default(0);   // Total des pénalités
            $table->decimal('net_refund', 10, 2);                  // Remboursement net
            
            // Détails
            $table->timestamp('returned_at');
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('deposit_id');
            $table->index('returned_at');
        });

        // ========================================
        // 4. AJOUT COLONNES AUX TABLES EXISTANTES
        // ========================================
        
        // ✅ CORRECTION: Vérifier si les colonnes existent avant de les ajouter
        
        // Ajouter colonnes à la table products
        Schema::table('products', function (Blueprint $table) {
            // Vérifier si la colonne n'existe pas déjà
            if (!Schema::hasColumn('products', 'has_deposit')) {
                $table->boolean('has_deposit')->default(false)->after('unit_price')
                      ->comment('Produit nécessite une consigne');
            }
            
            if (!Schema::hasColumn('products', 'deposit_type_id')) {
                $table->foreignId('deposit_type_id')->nullable()->after('has_deposit')
                      ->constrained()->onDelete('set null')
                      ->comment('Type d\'emballage consigné');
            }
            
            if (!Schema::hasColumn('products', 'units_per_deposit')) {
                $table->integer('units_per_deposit')->default(1)->after('deposit_type_id')
                      ->comment('Nombre d\'unités par emballage (ex: 24 bouteilles/casier)');
            }
        });

        // Ajouter colonnes à la table sales
        Schema::table('sales', function (Blueprint $table) {
            // ✅ Vérifier si les colonnes n'existent pas déjà
            if (!Schema::hasColumn('sales', 'deposit_amount')) {
                $table->decimal('deposit_amount', 10, 2)->default(0)->after('total_amount')
                      ->comment('Montant total des consignes');
            }
            
            if (!Schema::hasColumn('sales', 'grand_total')) {
                $table->decimal('grand_total', 10, 2)->default(0)->after('deposit_amount')
                      ->comment('Total = total_amount + deposit_amount');
            }
        });
    }

    /**
     * Annuler les migrations.
     */
    public function down(): void
    {
        // Supprimer colonnes ajoutées aux ventes
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'deposit_amount')) {
                $table->dropColumn('deposit_amount');
            }
            if (Schema::hasColumn('sales', 'grand_total')) {
                $table->dropColumn('grand_total');
            }
        });

        // Supprimer colonnes ajoutées aux produits
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'deposit_type_id')) {
                $table->dropForeign(['deposit_type_id']);
                $table->dropColumn('deposit_type_id');
            }
            if (Schema::hasColumn('products', 'has_deposit')) {
                $table->dropColumn('has_deposit');
            }
            if (Schema::hasColumn('products', 'units_per_deposit')) {
                $table->dropColumn('units_per_deposit');
            }
        });

        // Supprimer tables
        Schema::dropIfExists('deposit_returns');
        Schema::dropIfExists('deposits');
        Schema::dropIfExists('deposit_types');
    }
};