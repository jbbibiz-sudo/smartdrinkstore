<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubcategorySeeder extends Seeder
{
    public function run(): void
    {
        // Vider les sous-catégories existantes
        DB::table('subcategories')->truncate();
        
        // Récupérer les IDs des catégories
        $categories = DB::table('categories')->get()->keyBy('code');
        
        $subcategories = [];
        
        // Bières (BIR)
        if (isset($categories['BIR'])) {
            $subcategories[] = [
                'category_id' => $categories['BIR']->id,
                'name' => 'Bières Locales',
                'slug' => 'bieres-locales',
                'code' => 'BIR-LOC',
                'description' => 'Bières produites localement',
                'color' => '#f39c12',
                'position' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $subcategories[] = [
                'category_id' => $categories['BIR']->id,
                'name' => 'Bières Importées',
                'slug' => 'bieres-importees',
                'code' => 'BIR-IMP',
                'description' => 'Bières importées de l\'étranger',
                'color' => '#e74c3c',
                'position' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $subcategories[] = [
                'category_id' => $categories['BIR']->id,
                'name' => 'Bières Spéciales',
                'slug' => 'bieres-speciales',
                'code' => 'BIR-SPE',
                'description' => 'Bières artisanales et spéciales',
                'color' => '#d35400',
                'position' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        
        // Sodas (SOD)
        if (isset($categories['SOD'])) {
            $subcategories[] = [
                'category_id' => $categories['SOD']->id,
                'name' => 'Colas',
                'slug' => 'colas',
                'code' => 'SOD-COL',
                'description' => 'Boissons au cola',
                'color' => '#1a1a1a',
                'position' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $subcategories[] = [
                'category_id' => $categories['SOD']->id,
                'name' => 'Citronnades',
                'slug' => 'citronnades',
                'code' => 'SOD-CIT',
                'description' => 'Boissons au citron',
                'color' => '#f1c40f',
                'position' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $subcategories[] = [
                'category_id' => $categories['SOD']->id,
                'name' => 'Boissons Énergisantes',
                'slug' => 'boissons-energisantes',
                'code' => 'SOD-ENE',
                'description' => 'Boissons énergisantes',
                'color' => '#e74c3c',
                'position' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $subcategories[] = [
                'category_id' => $categories['SOD']->id,
                'name' => 'Jus Gazéifiés',
                'slug' => 'jus-gazeifies',
                'code' => 'SOD-JUG',
                'description' => 'Jus avec gaz',
                'color' => '#27ae60',
                'position' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        
        // Eaux (EAU)
        if (isset($categories['EAU'])) {
            $subcategories[] = [
                'category_id' => $categories['EAU']->id,
                'name' => 'Eaux Plates',
                'slug' => 'eaux-plates',
                'code' => 'EAU-PLA',
                'description' => 'Eaux non gazeuses',
                'color' => '#3498db',
                'position' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $subcategories[] = [
                'category_id' => $categories['EAU']->id,
                'name' => 'Eaux Gazeuses',
                'slug' => 'eaux-gazeuses',
                'code' => 'EAU-GAZ',
                'description' => 'Eaux avec gaz',
                'color' => '#2980b9',
                'position' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $subcategories[] = [
                'category_id' => $categories['EAU']->id,
                'name' => 'Eaux Aromatisées',
                'slug' => 'eaux-aromatisees',
                'code' => 'EAU-ARO',
                'description' => 'Eaux avec saveurs',
                'color' => '#1abc9c',
                'position' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        
        // Jus (JUS)
        if (isset($categories['JUS'])) {
            $subcategories[] = [
                'category_id' => $categories['JUS']->id,
                'name' => 'Jus de Fruits',
                'slug' => 'jus-de-fruits',
                'code' => 'JUS-FRU',
                'description' => 'Jus 100% fruits',
                'color' => '#e67e22',
                'position' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $subcategories[] = [
                'category_id' => $categories['JUS']->id,
                'name' => 'Nectars',
                'slug' => 'nectars',
                'code' => 'JUS-NEC',
                'description' => 'Nectars de fruits',
                'color' => '#d35400',
                'position' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $subcategories[] = [
                'category_id' => $categories['JUS']->id,
                'name' => 'Smoothies',
                'slug' => 'smoothies',
                'code' => 'JUS-SMO',
                'description' => 'Smoothies et mélanges',
                'color' => '#e74c3c',
                'position' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        
        // Vins & Spiritueux (VIN)
        if (isset($categories['VIN'])) {
            $subcategories[] = [
                'category_id' => $categories['VIN']->id,
                'name' => 'Vins Rouges',
                'slug' => 'vins-rouges',
                'code' => 'VIN-ROU',
                'description' => 'Vins rouges',
                'color' => '#c0392b',
                'position' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $subcategories[] = [
                'category_id' => $categories['VIN']->id,
                'name' => 'Vins Blancs',
                'slug' => 'vins-blancs',
                'code' => 'VIN-BLA',
                'description' => 'Vins blancs',
                'color' => '#f1c40f',
                'position' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $subcategories[] = [
                'category_id' => $categories['VIN']->id,
                'name' => 'Spiritueux',
                'slug' => 'spiritueux',
                'code' => 'VIN-SPI',
                'description' => 'Whisky, rhum, vodka',
                'color' => '#8e44ad',
                'position' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $subcategories[] = [
                'category_id' => $categories['VIN']->id,
                'name' => 'Apéritifs',
                'slug' => 'aperitifs',
                'code' => 'VIN-APE',
                'description' => 'Apéritifs et digestifs',
                'color' => '#16a085',
                'position' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        
        // Insertion en masse
        if (!empty($subcategories)) {
            DB::table('subcategories')->insert($subcategories);
            $this->command->info('✅ ' . count($subcategories) . ' sous-catégories créées avec succès.');
        } else {
            $this->command->warn('⚠️ Aucune catégorie trouvée, sous-catégories non créées.');
        }
    }
}