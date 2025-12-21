<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            SubcategorySeeder::class,
            ProductSeeder::class,
            StockMovementSeeder::class,
            // Puis les clients
            CustomerSeeder::class,
            // Et les fournisseurs
            SupplierSeeder::class,            
            // Enfin les ventes
            SalesSeeder::class,
        ]);
    }    
}