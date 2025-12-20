<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Vider la table avant d'insérer
        Product::truncate();

        $products = [
            // Bières
            [
                'sku' => 'BIR-33EXP-33',
                'name' => '33 Export',
                'code' => 'BIR-33EXP-33',
                'barcode' => '6001010000001',
                'brand' => '33 Export',
                'volume' => '33cl',
                'unit_price' => 600,
                'cost_price' => 400,
                'stock' => 120,
                'min_stock' => 50,
                'is_consigned' => true,
                'consignment_price' => 50,
                'category_id' => 1, // Bières
            ],
            [
                'sku' => 'BIR-CAS-65',
                'name' => 'Castel',
                'code' => 'BIR-CAS-65',
                'barcode' => '6001010000002',
                'brand' => 'Castel',
                'volume' => '65cl',
                'unit_price' => 900,
                'cost_price' => 600,
                'stock' => 80,
                'min_stock' => 40,
                'is_consigned' => true,
                'consignment_price' => 100,
                'category_id' => 1,
            ],
            [
                'sku' => 'BIR-AMS-33',
                'name' => 'Amstel',
                'code' => 'BIR-AMS-33',
                'barcode' => '6001010000003', // Ajoutez un barcode
                'brand' => 'Amstel',
                'volume' => '33cl',
                'unit_price' => 650,
                'cost_price' => 450,
                'stock' => 8,
                'min_stock' => 30,
                'is_consigned' => true,
                'consignment_price' => 50,
                'category_id' => 1,
            ],

            // Sodas
            [
                'sku' => 'SOD-COCA-33',
                'name' => 'Coca-Cola',
                'code' => 'SOD-COCA-33',
                'barcode' => '6001010000004', // Ajoutez un barcode
                'brand' => 'Coca-Cola',
                'volume' => '33cl',
                'unit_price' => 400,
                'cost_price' => 250,
                'stock' => 150,
                'min_stock' => 60,
                'is_consigned' => true,
                'consignment_price' => 50,
                'category_id' => 2,
            ],
            [
                'sku' => 'SOD-FAN-50',
                'name' => 'Fanta Orange',
                'code' => 'SOD-FAN-50',
                'barcode' => '6001010000005', // Ajoutez un barcode
                'brand' => 'Fanta',
                'volume' => '50cl',
                'unit_price' => 450,
                'cost_price' => 300,
                'stock' => 90,
                'min_stock' => 50,
                'is_consigned' => false,
                'category_id' => 2,
            ],
            [
                'sku' => 'SOD-SPR-1.5L',
                'name' => 'Sprite',
                'code' => 'SOD-SPR-1.5L',
                'barcode' => '6001010000006', // Ajoutez un barcode
                'brand' => 'Sprite',
                'volume' => '1.5L',
                'unit_price' => 800,
                'cost_price' => 550,
                'stock' => 5,
                'min_stock' => 30,
                'is_consigned' => false,
                'category_id' => 2,
            ],

            // Eaux
            [
                'sku' => 'EAU-TAN-50',
                'name' => 'Tangui',
                'code' => 'EAU-TAN-50',
                'barcode' => '6001010000007', // Ajoutez un barcode
                'brand' => 'Tangui',
                'volume' => '50cl',
                'unit_price' => 250,
                'cost_price' => 150,
                'stock' => 200,
                'min_stock' => 100,
                'is_consigned' => false,
                'category_id' => 3,
            ],
            [
                'sku' => 'EAU-SUP-1.5L',
                'name' => 'Supermont',
                'code' => 'EAU-SUP-1.5L',
                'barcode' => '6001010000008', // Ajoutez un barcode
                'brand' => 'Supermont',
                'volume' => '1.5L',
                'unit_price' => 500,
                'cost_price' => 350,
                'stock' => 120,
                'min_stock' => 80,
                'is_consigned' => false,
                'category_id' => 3,
            ],

            // Jus
            [
                'sku' => 'JUS-TOP-1L',
                'name' => 'Top Ananas',
                'code' => 'JUS-TOP-1L',
                'barcode' => '6001010000009', // Ajoutez un barcode
                'brand' => 'Top',
                'volume' => '1L',
                'unit_price' => 1200,
                'cost_price' => 800,
                'stock' => 45,
                'min_stock' => 30,
                'is_consigned' => false,
                'category_id' => 4,
            ],

            // Vins & Spiritueux
            [
                'sku' => 'VIN-CIN-1L',
                'name' => 'Cinzano Rosso',
                'code' => 'VIN-CIN-1L',
                'barcode' => '6001010000010', // Ajoutez un barcode
                'brand' => 'Cinzano',
                'volume' => '1L',
                'unit_price' => 5000,
                'cost_price' => 3500,
                'stock' => 15,
                'min_stock' => 10,
                'is_consigned' => false,
                'category_id' => 5,
            ],
        ];

        foreach ($products as $productData) {
            // Assigner une sous-catégorie au hasard de la catégorie du produit
            $category = Category::find($productData['category_id']);
            if ($category && $category->subcategories->count() > 0) {
                $subcategory = $category->subcategories->random();
                $productData['subcategory_id'] = $subcategory->id;
            }

            Product::create($productData);
        }
        
        $this->command->info(count($products) . ' produits créés avec succès.');
    }
}