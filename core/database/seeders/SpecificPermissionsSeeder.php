<?php
// Chemin: C:\smartdrinkstore\core\database\seeders\SpecificPermissionsSeeder.php
// Seeder: Permissions spécifiques et granulaires

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * ============================================================================
 * SEEDER : Permissions spécifiques et granulaires
 * ============================================================================
 * 
 * Ce seeder ajoute des permissions plus granulaires pour un contrôle d'accès
 * précis. Par exemple : "supprimer_produit", "exporter_rapports", etc.
 * 
 * Utilisation :
 * php artisan db:seed --class=SpecificPermissionsSeeder
 */
class SpecificPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Création des permissions spécifiques...');

        // ============================================
        // PERMISSIONS GRANULAIRES PAR MODULE
        // ============================================

        // === PRODUITS ===
        $productPermissions = [
            ['name' => 'lister_produits', 'display_name' => 'Lister les produits', 'group' => 'products'],
            ['name' => 'voir_produit', 'display_name' => 'Voir détails produit', 'group' => 'products'],
            ['name' => 'creer_produit', 'display_name' => 'Créer un produit', 'group' => 'products'],
            ['name' => 'modifier_produit', 'display_name' => 'Modifier un produit', 'group' => 'products'],
            ['name' => 'supprimer_produit', 'display_name' => 'Supprimer un produit', 'group' => 'products'],
            ['name' => 'importer_produits', 'display_name' => 'Importer des produits (CSV/Excel)', 'group' => 'products'],
            ['name' => 'exporter_produits', 'display_name' => 'Exporter liste produits', 'group' => 'products'],
            ['name' => 'voir_cout_achat', 'display_name' => 'Voir coût d\'achat produits', 'group' => 'products'],
        ];

        // === VENTES ===
        $salesPermissions = [
            ['name' => 'lister_ventes', 'display_name' => 'Lister les ventes', 'group' => 'sales'],
            ['name' => 'voir_vente', 'display_name' => 'Voir détails vente', 'group' => 'sales'],
            ['name' => 'creer_vente', 'display_name' => 'Créer une vente', 'group' => 'sales'],
            ['name' => 'modifier_vente', 'display_name' => 'Modifier une vente', 'group' => 'sales'],
            ['name' => 'supprimer_vente', 'display_name' => 'Supprimer une vente', 'group' => 'sales'],
            ['name' => 'annuler_vente', 'display_name' => 'Annuler une vente', 'group' => 'sales'],
            ['name' => 'rembourser_vente', 'display_name' => 'Rembourser une vente', 'group' => 'sales'],
            ['name' => 'appliquer_remise', 'display_name' => 'Appliquer une remise', 'group' => 'sales'],
            ['name' => 'voir_marge_vente', 'display_name' => 'Voir marge bénéficiaire', 'group' => 'sales'],
        ];

        // === ACHATS ===
        $purchasePermissions = [
            ['name' => 'lister_achats', 'display_name' => 'Lister les achats', 'group' => 'purchases'],
            ['name' => 'voir_achat', 'display_name' => 'Voir détails achat', 'group' => 'purchases'],
            ['name' => 'creer_achat', 'display_name' => 'Créer un achat', 'group' => 'purchases'],
            ['name' => 'modifier_achat', 'display_name' => 'Modifier un achat', 'group' => 'purchases'],
            ['name' => 'supprimer_achat', 'display_name' => 'Supprimer un achat', 'group' => 'purchases'],
            ['name' => 'valider_achat', 'display_name' => 'Valider un bon d\'achat', 'group' => 'purchases'],
            ['name' => 'receptionner_achat', 'display_name' => 'Réceptionner marchandise', 'group' => 'purchases'],
            ['name' => 'annuler_achat', 'display_name' => 'Annuler un achat', 'group' => 'purchases'],
        ];

        // === STOCK / INVENTAIRE ===
        $inventoryPermissions = [
            ['name' => 'voir_stock', 'display_name' => 'Voir niveau de stock', 'group' => 'inventory'],
            ['name' => 'ajuster_stock', 'display_name' => 'Ajuster le stock', 'group' => 'inventory'],
            ['name' => 'transferer_stock', 'display_name' => 'Transférer le stock', 'group' => 'inventory'],
            ['name' => 'faire_inventaire', 'display_name' => 'Faire un inventaire', 'group' => 'inventory'],
            ['name' => 'voir_mouvements_stock', 'display_name' => 'Voir mouvements de stock', 'group' => 'inventory'],
            ['name' => 'voir_alertes_stock', 'display_name' => 'Voir alertes de stock', 'group' => 'inventory'],
        ];

        // === CAISSE ===
        $cashRegisterPermissions = [
            ['name' => 'ouvrir_caisse', 'display_name' => 'Ouvrir la caisse', 'group' => 'cash_register'],
            ['name' => 'fermer_caisse', 'display_name' => 'Fermer la caisse', 'group' => 'cash_register'],
            ['name' => 'voir_caisses', 'display_name' => 'Voir toutes les caisses', 'group' => 'cash_register'],
            ['name' => 'voir_ma_caisse', 'display_name' => 'Voir ma caisse uniquement', 'group' => 'cash_register'],
            ['name' => 'gerer_fond_caisse', 'display_name' => 'Gérer le fond de caisse', 'group' => 'cash_register'],
            ['name' => 'corriger_caisse', 'display_name' => 'Corriger écarts de caisse', 'group' => 'cash_register'],
        ];

        // === CLIENTS ===
        $clientPermissions = [
            ['name' => 'lister_clients', 'display_name' => 'Lister les clients', 'group' => 'clients'],
            ['name' => 'voir_client', 'display_name' => 'Voir détails client', 'group' => 'clients'],
            ['name' => 'creer_client', 'display_name' => 'Créer un client', 'group' => 'clients'],
            ['name' => 'modifier_client', 'display_name' => 'Modifier un client', 'group' => 'clients'],
            ['name' => 'supprimer_client', 'display_name' => 'Supprimer un client', 'group' => 'clients'],
            ['name' => 'voir_historique_client', 'display_name' => 'Voir historique achats client', 'group' => 'clients'],
        ];

        // === FOURNISSEURS ===
        $supplierPermissions = [
            ['name' => 'lister_fournisseurs', 'display_name' => 'Lister les fournisseurs', 'group' => 'suppliers'],
            ['name' => 'voir_fournisseur', 'display_name' => 'Voir détails fournisseur', 'group' => 'suppliers'],
            ['name' => 'creer_fournisseur', 'display_name' => 'Créer un fournisseur', 'group' => 'suppliers'],
            ['name' => 'modifier_fournisseur', 'display_name' => 'Modifier un fournisseur', 'group' => 'suppliers'],
            ['name' => 'supprimer_fournisseur', 'display_name' => 'Supprimer un fournisseur', 'group' => 'suppliers'],
            ['name' => 'voir_dettes_fournisseur', 'display_name' => 'Voir dettes fournisseurs', 'group' => 'suppliers'],
        ];

        // === CONSIGNES ===
        $depositPermissions = [
            ['name' => 'lister_consignes', 'display_name' => 'Lister les consignes', 'group' => 'deposits'],
            ['name' => 'creer_consigne_sortante', 'display_name' => 'Créer consigne sortante', 'group' => 'deposits'],
            ['name' => 'creer_consigne_entrante', 'display_name' => 'Créer consigne entrante', 'group' => 'deposits'],
            ['name' => 'traiter_retour', 'display_name' => 'Traiter retour emballage', 'group' => 'deposits'],
            ['name' => 'supprimer_consigne', 'display_name' => 'Supprimer une consigne', 'group' => 'deposits'],
            ['name' => 'voir_stats_consignes', 'display_name' => 'Voir statistiques consignes', 'group' => 'deposits'],
        ];

        // === RAPPORTS ===
        $reportPermissions = [
            ['name' => 'voir_tableau_bord', 'display_name' => 'Voir tableau de bord', 'group' => 'reports'],
            ['name' => 'voir_rapport_ventes', 'display_name' => 'Voir rapport de ventes', 'group' => 'reports'],
            ['name' => 'voir_rapport_achats', 'display_name' => 'Voir rapport d\'achats', 'group' => 'reports'],
            ['name' => 'voir_rapport_stock', 'display_name' => 'Voir rapport de stock', 'group' => 'reports'],
            ['name' => 'voir_rapport_financier', 'display_name' => 'Voir rapport financier', 'group' => 'reports'],
            ['name' => 'exporter_rapports', 'display_name' => 'Exporter les rapports (PDF/Excel)', 'group' => 'reports'],
            ['name' => 'voir_benefices', 'display_name' => 'Voir bénéfices et marges', 'group' => 'reports'],
        ];

        // === UTILISATEURS ===
        $userPermissions = [
            ['name' => 'lister_utilisateurs', 'display_name' => 'Lister les utilisateurs', 'group' => 'users'],
            ['name' => 'voir_utilisateur', 'display_name' => 'Voir détails utilisateur', 'group' => 'users'],
            ['name' => 'creer_utilisateur', 'display_name' => 'Créer un utilisateur', 'group' => 'users'],
            ['name' => 'modifier_utilisateur', 'display_name' => 'Modifier un utilisateur', 'group' => 'users'],
            ['name' => 'supprimer_utilisateur', 'display_name' => 'Supprimer un utilisateur', 'group' => 'users'],
            ['name' => 'activer_desactiver_utilisateur', 'display_name' => 'Activer/Désactiver utilisateur', 'group' => 'users'],
            ['name' => 'gerer_roles', 'display_name' => 'Gérer les rôles', 'group' => 'users'],
            ['name' => 'gerer_permissions', 'display_name' => 'Gérer les permissions', 'group' => 'users'],
        ];

        // === PARAMÈTRES ===
        $settingsPermissions = [
            ['name' => 'voir_parametres', 'display_name' => 'Voir les paramètres', 'group' => 'settings'],
            ['name' => 'modifier_parametres_generaux', 'display_name' => 'Modifier paramètres généraux', 'group' => 'settings'],
            ['name' => 'modifier_parametres_fiscaux', 'display_name' => 'Modifier paramètres fiscaux', 'group' => 'settings'],
            ['name' => 'gerer_categories', 'display_name' => 'Gérer les catégories', 'group' => 'settings'],
            ['name' => 'gerer_types_emballages', 'display_name' => 'Gérer types d\'emballages', 'group' => 'settings'],
            ['name' => 'voir_logs_systeme', 'display_name' => 'Voir logs système', 'group' => 'settings'],
            ['name' => 'sauvegarder_base_donnees', 'display_name' => 'Sauvegarder base de données', 'group' => 'settings'],
        ];

        // === COMBINER TOUTES LES PERMISSIONS ===
        $allPermissions = array_merge(
            $productPermissions,
            $salesPermissions,
            $purchasePermissions,
            $inventoryPermissions,
            $cashRegisterPermissions,
            $clientPermissions,
            $supplierPermissions,
            $depositPermissions,
            $reportPermissions,
            $userPermissions,
            $settingsPermissions
        );

        // Créer ou mettre à jour toutes les permissions
        $this->command->info('📝 Création de ' . count($allPermissions) . ' permissions...');
        
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('✅ Permissions créées avec succès !');

        // ============================================
        // ATTRIBUTION DES PERMISSIONS AUX RÔLES
        // ============================================
        
        $this->command->info('🔗 Attribution des permissions aux rôles...');

        // ADMINISTRATEUR - Toutes les permissions
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->permissions()->sync(Permission::all()->pluck('id'));
            $this->command->info('✅ Admin : Toutes les permissions');
        }

        // GESTIONNAIRE - Presque tout sauf gestion utilisateurs
        $managerRole = Role::where('name', 'manager')->first();
        if ($managerRole) {
            $managerPermissions = Permission::whereNotIn('name', [
                'creer_utilisateur',
                'supprimer_utilisateur',
                'gerer_roles',
                'gerer_permissions',
                'modifier_parametres_fiscaux',
                'sauvegarder_base_donnees',
                'voir_logs_systeme',
            ])->pluck('id');
            $managerRole->permissions()->sync($managerPermissions);
            $this->command->info('✅ Manager : ' . $managerPermissions->count() . ' permissions');
        }

        // CAISSIER - Ventes, caisse, clients
        $cashierRole = Role::where('name', 'cashier')->first();
        if ($cashierRole) {
            $cashierPermissions = Permission::whereIn('name', [
                // Produits
                'lister_produits',
                'voir_produit',
                // Ventes
                'lister_ventes',
                'voir_vente',
                'creer_vente',
                'annuler_vente',
                // Caisse
                'ouvrir_caisse',
                'fermer_caisse',
                'voir_ma_caisse',
                // Clients
                'lister_clients',
                'voir_client',
                'creer_client',
                'voir_historique_client',
                // Consignes
                'lister_consignes',
                'creer_consigne_sortante',
                'traiter_retour',
                // Rapports basiques
                'voir_tableau_bord',
            ])->pluck('id');
            $cashierRole->permissions()->sync($cashierPermissions);
            $this->command->info('✅ Cashier : ' . $cashierPermissions->count() . ' permissions');
        }

        // GESTIONNAIRE DE STOCK - Stock et inventaire uniquement
        $inventoryRole = Role::where('name', 'inventory_manager')->first();
        if ($inventoryRole) {
            $inventoryPermissions = Permission::whereIn('name', [
                // Produits
                'lister_produits',
                'voir_produit',
                'creer_produit',
                'modifier_produit',
                'importer_produits',
                'exporter_produits',
                // Stock
                'voir_stock',
                'ajuster_stock',
                'transferer_stock',
                'faire_inventaire',
                'voir_mouvements_stock',
                'voir_alertes_stock',
                // Fournisseurs
                'lister_fournisseurs',
                'voir_fournisseur',
                'creer_fournisseur',
                'modifier_fournisseur',
                // Achats
                'lister_achats',
                'voir_achat',
                'creer_achat',
                'receptionner_achat',
                // Rapports
                'voir_tableau_bord',
                'voir_rapport_stock',
                'voir_rapport_achats',
            ])->pluck('id');
            $inventoryRole->permissions()->sync($inventoryPermissions);
            $this->command->info('✅ Inventory Manager : ' . $inventoryPermissions->count() . ' permissions');
        }

        $this->command->newLine();
        $this->command->info('🎉 Toutes les permissions spécifiques ont été créées et attribuées !');
        $this->command->newLine();
        
        // Afficher un résumé
        $this->command->table(
            ['Rôle', 'Nombre de permissions'],
            [
                ['Admin', Permission::count()],
                ['Manager', $managerPermissions->count() ?? 0],
                ['Cashier', $cashierPermissions->count() ?? 0],
                ['Inventory Manager', $inventoryPermissions->count() ?? 0],
            ]
        );
    }
}
