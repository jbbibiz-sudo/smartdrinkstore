<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ProductUnitsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now('Africa/Douala'); // Fuseau WAT pour SQLite localtime
        
        DB::table('product_units')->insert([
            [
                'code' => 'CASE_24', 
                'name' => 'Casier 24', 
                'symbol' => 'cs24', 
                'description' => 'Casier de 24 bouteilles/canettes', 
                'is_active' => 1, 
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'code' => 'CASE_12', 
                'name' => 'Casier 12', 
                'symbol' => 'cs12', 
                'description' => 'Casier de 12 bouteilles/canettes', 
                'is_active' => 1, 
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'code' => 'PACK_6', 
                'name' => 'Pack 6', 
                'symbol' => 'pk6', 
                'description' => 'Pack de 6 bouteilles/canettes', 
                'is_active' => 1, 
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'code' => 'PACK_12', 
                'name' => 'Pack 12', 
                'symbol' => 'pk12', 
                'description' => 'Pack de 12 bouteilles/canettes', 
                'is_active' => 1, 
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'code' => 'BOTTLE', 
                'name' => 'Bouteille', 
                'symbol' => 'btl', 
                'description' => 'Unité individuelle - bouteille', 
                'is_active' => 1, 
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'code' => 'CAN', 
                'name' => 'Canette', 
                'symbol' => 'can', 
                'description' => 'Unité individuelle - canette', 
                'is_active' => 1, 
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'code' => 'KEG_30L', 
                'name' => 'Fût 30L', 
                'symbol' => 'keg30', 
                'description' => 'Fût de 30 litres', 
                'is_active' => 1, 
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'code' => 'KEG_50L', 
                'name' => 'Fût 50L', 
                'symbol' => 'keg50', 
                'description' => 'Fût de 50 litres', 
                'is_active' => 1, 
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'code' => 'BARREL', 
                'name' => 'Bidon', 
                'symbol' => 'brl', 
                'description' => 'Grand contenant (5L à 20L)', 
                'is_active' => 1, 
                'created_at' => $now,
                'updated_at' => $now
            ]
        ]);
    }
}
