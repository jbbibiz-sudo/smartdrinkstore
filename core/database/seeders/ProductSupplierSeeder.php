<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class ProductSupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Ce seeder associe automatiquement chaque produit à un ou plusieurs fournisseurs
     */
    public function run(): void
    {
        $this->command->info('🔄 Association des produits aux fournisseurs...');

        // Récupérer tous les produits et fournisseurs
        $products = Product::all();
        $suppliers = Supplier::all();

        if ($suppliers->isEmpty()) {
            $this->command->warn('⚠️  Aucun fournisseur trouvé. Création de fournisseurs de test...');
            $this->createTestSuppliers();
            $suppliers = Supplier::all();
        }

        if ($products->isEmpty()) {
            $this->command->warn('⚠️  Aucun produit trouvé.');
            return;
        }

        $this->command->info("📦 {$products->count()} produits et {$suppliers->count()} fournisseurs trouvés");

        // Nettoyer les associations existantes
        DB::table('product_supplier')->truncate();

        $associationCount = 0;

        foreach ($products as $product) {
            // Déterminer combien de fournisseurs associer (1 à 3)
            $numSuppliers = rand(1, min(3, $suppliers->count()));
            
            // Sélectionner des fournisseurs aléatoires
            $selectedSuppliers = $suppliers->random($numSuppliers);

            foreach ($selectedSuppliers as $index => $supplier) {
                // Le premier fournisseur est toujours le préféré
                $isPreferred = ($index === 0);
                
                // Calculer un prix d'achat réaliste (60-80% du prix de vente)
                $costPrice = $product->unit_price * (rand(60, 80) / 100);
                
                // Attacher le fournisseur avec des informations réalistes
                $product->suppliers()->attach($supplier->id, [
                    'cost_price' => round($costPrice, 2),
                    'delivery_days' => rand(1, 7),
                    'minimum_order_quantity' => rand(1, 10) * 10, // 10, 20, 30... 100
                    'is_preferred' => $isPreferred,
                    'notes' => $isPreferred ? 'Fournisseur principal' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $associationCount++;

                $this->command->info(
                    "  ✓ {$product->name} → {$supplier->name}" . 
                    ($isPreferred ? ' [PRÉFÉRÉ]' : '')
                );
            }
        }

        $this->command->info("\n✅ {$associationCount} associations créées avec succès!");
    }

    /**
     * Crée des fournisseurs de test si aucun n'existe
     */
    private function createTestSuppliers(): void
    {
        $testSuppliers = [
            [
                'name' => 'Brasucam Boissons',
                'phone' => '+237 6 XX XX XX XX',
                'email' => 'contact@brasucam.cm',
                'address' => 'Zone Industrielle, Douala, Cameroun'
            ],
            [
                'name' => 'UCB (Union des Cafés et Boissons)',
                'phone' => '+237 6 YY YY YY YY',
                'email' => 'ventes@ucb.cm',
                'address' => 'Bépanda, Douala, Cameroun'
            ],
            [
                'name' => 'SABC (Société Anonyme des Brasseries du Cameroun)',
                'phone' => '+237 6 ZZ ZZ ZZ ZZ',
                'email' => 'commercial@sabc.cm',
                'address' => 'Rue de la Réunification, Douala, Cameroun'
            ],
            [
                'name' => 'Source du Pays',
                'phone' => '+237 6 AA AA AA AA',
                'email' => 'info@sourcedupays.cm',
                'address' => 'Akwa, Douala, Cameroun'
            ],
            [
                'name' => 'Guinness Cameroun',
                'phone' => '+237 6 BB BB BB BB',
                'email' => 'contact@guinness.cm',
                'address' => 'Bonapriso, Douala, Cameroun'
            ]
        ];

        foreach ($testSuppliers as $supplier) {
            Supplier::create($supplier);
            $this->command->info("  ✓ Fournisseur créé: {$supplier['name']}");
        }
    }
}
