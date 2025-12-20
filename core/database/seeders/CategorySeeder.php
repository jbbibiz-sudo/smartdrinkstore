<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Vider la table
        DB::table('categories')->truncate();
        
        $categories = [
            [
                'name' => 'Bières',
                'slug' => 'bieres',
                'code' => 'BIR',
                'description' => 'Toutes les bières locales et importées',
                'color' => '#f39c12',
                'position' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Sodas',
                'slug' => 'sodas',
                'code' => 'SOD',
                'description' => 'Boissons gazeuses et sodas',
                'color' => '#e74c3c',
                'position' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Eaux',
                'slug' => 'eaux',
                'code' => 'EAU',
                'description' => 'Eaux minérales et plates',
                'color' => '#3498db',
                'position' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Jus',
                'slug' => 'jus',
                'code' => 'JUS',
                'description' => 'Jus de fruits et nectars',
                'color' => '#2ecc71',
                'position' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Vins & Spiritueux',
                'slug' => 'vins-spiritueux',
                'code' => 'VIN',
                'description' => 'Vins, whisky, rhum, vodka, etc.',
                'color' => '#9b59b6',
                'position' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ];

        // Insertion directe avec DB::table
        DB::table('categories')->insert($categories);
        
        $this->command->info('✅ ' . count($categories) . ' catégories créées avec succès.');
    }
}