<?php
// Chemin: C:\smartdrinkstore\core\database\seeders\RolesAndPermissionsSeeder.php
// Seeder: Rôles, permissions et utilisateur admin par défaut

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ============================================
        // CRÉATION DES PERMISSIONS
        // ============================================
        
        $permissions = [
            // Gestion des produits
            ['name' => 'view_products', 'display_name' => 'Voir les produits', 'group' => 'products'],
            ['name' => 'create_product', 'display_name' => 'Créer un produit', 'group' => 'products'],
            ['name' => 'edit_product', 'display_name' => 'Modifier un produit', 'group' => 'products'],
            ['name' => 'delete_product', 'display_name' => 'Supprimer un produit', 'group' => 'products'],
            
            // Gestion des ventes
            ['name' => 'view_sales', 'display_name' => 'Voir les ventes', 'group' => 'sales'],
            ['name' => 'create_sale', 'display_name' => 'Créer une vente', 'group' => 'sales'],
            ['name' => 'edit_sale', 'display_name' => 'Modifier une vente', 'group' => 'sales'],
            ['name' => 'delete_sale', 'display_name' => 'Supprimer une vente', 'group' => 'sales'],
            ['name' => 'refund_sale', 'display_name' => 'Rembourser une vente', 'group' => 'sales'],
            
            // Gestion de la caisse
            ['name' => 'open_cash_register', 'display_name' => 'Ouvrir la caisse', 'group' => 'cash_register'],
            ['name' => 'close_cash_register', 'display_name' => 'Fermer la caisse', 'group' => 'cash_register'],
            ['name' => 'view_cash_register', 'display_name' => 'Voir les caisses', 'group' => 'cash_register'],
            
            // Gestion des clients
            ['name' => 'view_clients', 'display_name' => 'Voir les clients', 'group' => 'clients'],
            ['name' => 'create_client', 'display_name' => 'Créer un client', 'group' => 'clients'],
            ['name' => 'edit_client', 'display_name' => 'Modifier un client', 'group' => 'clients'],
            ['name' => 'delete_client', 'display_name' => 'Supprimer un client', 'group' => 'clients'],
            
            // Gestion des fournisseurs
            ['name' => 'view_suppliers', 'display_name' => 'Voir les fournisseurs', 'group' => 'suppliers'],
            ['name' => 'create_supplier', 'display_name' => 'Créer un fournisseur', 'group' => 'suppliers'],
            ['name' => 'edit_supplier', 'display_name' => 'Modifier un fournisseur', 'group' => 'suppliers'],
            ['name' => 'delete_supplier', 'display_name' => 'Supprimer un fournisseur', 'group' => 'suppliers'],
            
            // Gestion des mouvements de stock
            ['name' => 'view_stock_movements', 'display_name' => 'Voir les mouvements', 'group' => 'inventory'],
            ['name' => 'create_stock_movement', 'display_name' => 'Créer un mouvement', 'group' => 'inventory'],
            ['name' => 'adjust_stock', 'display_name' => 'Ajuster le stock', 'group' => 'inventory'],
            
            // Rapports
            ['name' => 'view_reports', 'display_name' => 'Voir les rapports', 'group' => 'reports'],
            ['name' => 'export_reports', 'display_name' => 'Exporter les rapports', 'group' => 'reports'],
            
            // Gestion des utilisateurs
            ['name' => 'view_users', 'display_name' => 'Voir les utilisateurs', 'group' => 'users'],
            ['name' => 'create_user', 'display_name' => 'Créer un utilisateur', 'group' => 'users'],
            ['name' => 'edit_user', 'display_name' => 'Modifier un utilisateur', 'group' => 'users'],
            ['name' => 'delete_user', 'display_name' => 'Supprimer un utilisateur', 'group' => 'users'],
            ['name' => 'manage_roles', 'display_name' => 'Gérer les rôles', 'group' => 'users'],
            
            // Paramètres
            ['name' => 'view_settings', 'display_name' => 'Voir les paramètres', 'group' => 'settings'],
            ['name' => 'edit_settings', 'display_name' => 'Modifier les paramètres', 'group' => 'settings'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // ============================================
        // CRÉATION DES RÔLES
        // ============================================
        
        // 1. ADMINISTRATEUR - Accès complet
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrateur',
                'description' => 'Accès complet à toutes les fonctionnalités',
            ]
        );
        $adminRole->permissions()->sync(Permission::all()->pluck('id'));

        // 2. GESTIONNAIRE - Gestion sans admin utilisateurs
        $managerRole = Role::firstOrCreate(
            ['name' => 'manager'],
            [
                'display_name' => 'Gestionnaire',
                'description' => 'Gestion des produits, ventes, stock et rapports',
            ]
        );
        $managerPermissions = Permission::whereIn('name', [
            'view_products', 'create_product', 'edit_product', 'delete_product',
            'view_sales', 'create_sale', 'edit_sale', 'delete_sale', 'refund_sale',
            'open_cash_register', 'close_cash_register', 'view_cash_register',
            'view_clients', 'create_client', 'edit_client', 'delete_client',
            'view_suppliers', 'create_supplier', 'edit_supplier', 'delete_supplier',
            'view_stock_movements', 'create_stock_movement', 'adjust_stock',
            'view_reports', 'export_reports',
            'view_users',
        ])->pluck('id');
        $managerRole->permissions()->sync($managerPermissions);

        // 3. CAISSIER - Ventes et consultation
        $cashierRole = Role::firstOrCreate(
            ['name' => 'cashier'],
            [
                'display_name' => 'Caissier',
                'description' => 'Gestion des ventes et de la caisse',
            ]
        );
        $cashierPermissions = Permission::whereIn('name', [
            'view_products',
            'view_sales', 'create_sale',
            'open_cash_register', 'close_cash_register', 'view_cash_register',
            'view_clients', 'create_client',
        ])->pluck('id');
        $cashierRole->permissions()->sync($cashierPermissions);

        // 4. GESTIONNAIRE DE STOCK - Inventaire uniquement
        $inventoryRole = Role::firstOrCreate(
            ['name' => 'inventory_manager'],
            [
                'display_name' => 'Gestionnaire de stock',
                'description' => 'Gestion des produits et du stock',
            ]
        );
        $inventoryPermissions = Permission::whereIn('name', [
            'view_products', 'create_product', 'edit_product',
            'view_suppliers', 'create_supplier', 'edit_supplier',
            'view_stock_movements', 'create_stock_movement', 'adjust_stock',
        ])->pluck('id');
        $inventoryRole->permissions()->sync($inventoryPermissions);

        // ============================================
        // CRÉATION DE L'UTILISATEUR ADMIN PAR DÉFAUT
        // ============================================
        
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@smartdrinkstore.com'],
            [
                'name' => 'Administrateur',
                'username' => 'admin',
                'password' => Hash::make('admin123'), // À CHANGER EN PRODUCTION !
                'phone' => '+237600000000',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Assigner le rôle admin
        $adminUser->assignRole($adminRole);

        $this->command->info('✅ Rôles, permissions et utilisateur admin créés avec succès !');
        $this->command->info('📧 Email: admin@smartdrinkstore.com');
        $this->command->info('🔑 Mot de passe: admin123');
        $this->command->warn('⚠️  CHANGEZ CE MOT DE PASSE EN PRODUCTION !');
    }
}
