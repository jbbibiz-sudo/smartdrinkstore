<?php

// Fichier : database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Démarrage du seeding...');
        $this->command->newLine();

        $this->call([
            // 1️⃣ AUTHENTIFICATION (en premier)
            RolesAndPermissionsSeeder::class,
            
            // 2️⃣ DONNÉES DE BASE (catégories, produits, etc.)
            CategorySeeder::class,
            SubcategorySeeder::class,
            ProductUnitsSeeder::class,
            ProductSeeder::class,
            
            // 3️⃣ PARTENAIRES (avant les ventes)
            CustomerSeeder::class,
            SupplierSeeder::class,
            ProductSupplierSeeder::class,
            
            // 4️⃣ TRANSACTIONS (en dernier car dépendent des autres)
            StockMovementSeeder::class,
            SalesSeeder::class,

            // Seeders des consignes
            DepositTypesSeeder::class,
            DepositsSeeder::class,
            DepositReturnsSeeder::class,

            // Seeders des achats
            PurchasesSeeder::class,

            // Seeders des paiements
            CreditPaymentsSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('✅ Seeding terminé avec succès !');
        $this->command->newLine();
        
        // Afficher un résumé
        $this->displaySummary();
    }

    /**
     * Afficher un résumé des données créées
     */
    private function displaySummary(): void
    {
        $stats = [
            'Utilisateurs' => \App\Models\User::count(),
            'Catégories' => \App\Models\Category::count(),
            'Sous-catégories' => \App\Models\Subcategory::count(),
            'Produits' => \App\Models\Product::count(),
            'Clients' => \App\Models\Customer::count(),
            'Fournisseurs' => \App\Models\Supplier::count(),
            'Mouvements de stock' => \App\Models\StockMovement::count(),
            'Ventes' => \App\Models\Sale::count(),
            'Lignes de vente' => \App\Models\SaleItem::count(),
        ];

        $this->command->info('📊 RÉSUMÉ DES DONNÉES :');
        $this->command->table(
            ['Type', 'Nombre'],
            collect($stats)->map(fn($count, $type) => [$type, $count])->toArray()
        );
    }
}