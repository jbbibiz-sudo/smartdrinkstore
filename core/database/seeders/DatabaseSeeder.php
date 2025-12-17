<?php

// core/database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Créer des catégories
        $categories = [
            ['name' => 'Boissons gazeuses', 'description' => 'Sodas et boissons pétillantes'],
            ['name' => 'Bières', 'description' => 'Bières locales et importées'],
            ['name' => 'Eaux', 'description' => 'Eaux minérales et de source'],
            ['name' => 'Jus', 'description' => 'Jus de fruits naturels'],
            ['name' => 'Vins', 'description' => 'Vins rouges, blancs et rosés'],
        ];
        
        foreach ($categories as $category) {
            DB::table('categories')->insert([
                'name' => $category['name'],
                'description' => $category['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Créer des produits de test
        $products = [
            // Boissons gazeuses
            ['name' => 'Coca-Cola 33cl', 'sku' => 'COCA-33', 'category_id' => 1, 'unit_price' => 300, 'stock' => 120, 'min_stock' => 50],
            ['name' => 'Coca-Cola 1.5L', 'sku' => 'COCA-15', 'category_id' => 1, 'unit_price' => 800, 'stock' => 85, 'min_stock' => 40],
            ['name' => 'Fanta Orange 33cl', 'sku' => 'FANT-33', 'category_id' => 1, 'unit_price' => 300, 'stock' => 95, 'min_stock' => 50],
            ['name' => 'Sprite 33cl', 'sku' => 'SPRT-33', 'category_id' => 1, 'unit_price' => 300, 'stock' => 8, 'min_stock' => 50], // Stock faible
            
            // Bières
            ['name' => '33 Export 65cl', 'sku' => '33EX-65', 'category_id' => 2, 'unit_price' => 600, 'stock' => 150, 'min_stock' => 60],
            ['name' => 'Castel 65cl', 'sku' => 'CAST-65', 'category_id' => 2, 'unit_price' => 550, 'stock' => 0, 'min_stock' => 60], // Rupture de stock
            ['name' => 'Beaufort 65cl', 'sku' => 'BEAF-65', 'category_id' => 2, 'unit_price' => 500, 'stock' => 45, 'min_stock' => 40],
            
            // Eaux
            ['name' => 'Tangui 50cl', 'sku' => 'TANG-50', 'category_id' => 3, 'unit_price' => 200, 'stock' => 200, 'min_stock' => 100],
            ['name' => 'Tangui 1.5L', 'sku' => 'TANG-15', 'category_id' => 3, 'unit_price' => 400, 'stock' => 15, 'min_stock' => 80], // Stock faible
            
            // Jus
            ['name' => 'Top Ananas 1L', 'sku' => 'TOPA-1L', 'category_id' => 4, 'unit_price' => 700, 'stock' => 60, 'min_stock' => 30],
            ['name' => 'Top Orange 1L', 'sku' => 'TOPO-1L', 'category_id' => 4, 'unit_price' => 700, 'stock' => 0, 'min_stock' => 30], // Rupture de stock
            
            // Vins
            ['name' => 'Cellier Rouge 75cl', 'sku' => 'CELR-75', 'category_id' => 5, 'unit_price' => 3500, 'stock' => 25, 'min_stock' => 20],
            ['name' => 'Cellier Blanc 75cl', 'sku' => 'CELB-75', 'category_id' => 5, 'unit_price' => 3500, 'stock' => 18, 'min_stock' => 20],
        ];
        
        foreach ($products as $product) {
            DB::table('products')->insert([
                'name' => $product['name'],
                'sku' => $product['sku'],
                'category_id' => $product['category_id'],
                'unit_price' => $product['unit_price'],
                'stock' => $product['stock'],
                'min_stock' => $product['min_stock'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Créer des fournisseurs
        $suppliers = [
            ['name' => 'SABC (Brasseries du Cameroun)', 'phone' => '+237 6 XX XX XX XX', 'email' => 'contact@sabc.cm'],
            ['name' => 'Guinness Cameroun', 'phone' => '+237 6 XX XX XX XX', 'email' => 'info@guinness.cm'],
            ['name' => 'Source du Pays', 'phone' => '+237 6 XX XX XX XX', 'email' => 'ventes@sourcepays.cm'],
        ];
        
        foreach ($suppliers as $supplier) {
            DB::table('suppliers')->insert([
                'name' => $supplier['name'],
                'phone' => $supplier['phone'],
                'email' => $supplier['email'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        $this->command->info('✅ Base de données initialisée avec succès !');
        $this->command->info('📦 ' . count($products) . ' produits créés');
        $this->command->info('📁 ' . count($categories) . ' catégories créées');
        $this->command->info('🏭 ' . count($suppliers) . ' fournisseurs créés');
    }
}
