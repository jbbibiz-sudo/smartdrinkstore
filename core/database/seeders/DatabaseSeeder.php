<?php
// Chemin: C:\smartdrinkstore\core\database\seeders\DatabaseSeeder.php
// Seeder principal - Inclut l'authentification + vos autres seeders

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // ⚠️ IMPORTANT: Le seeder d'authentification doit être en PREMIER
            // car les autres seeders pourraient avoir besoin des utilisateurs
            RolesAndPermissionsSeeder::class,
            
            // Vos seeders existants
            CategorySeeder::class,
            SubcategorySeeder::class,
            ProductSeeder::class,
            StockMovementSeeder::class,
            CustomerSeeder::class,
            SupplierSeeder::class,            
            SalesSeeder::class,
        ]);
    }    
}
