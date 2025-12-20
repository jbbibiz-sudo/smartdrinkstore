<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

class StockMovementSeeder extends Seeder
{
    public function run(): void
    {
        // Vider la table avant d'insérer
        StockMovement::truncate();
        
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->warn('Aucun produit trouvé. Veuillez d\'abord exécuter ProductSeeder.');
            return;
        }

        foreach ($products as $product) {
            // Créer quelques mouvements de test
            
            // Entrée de stock initiale
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => 50,
                'reason' => 'Stock initial',
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ]);

            // Réapprovisionnement
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => 30,
                'reason' => 'Réapprovisionnement',
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(7),
            ]);

            // Sortie de stock (vente)
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => 10,
                'reason' => 'Vente',
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ]);

            // Ajustement de stock
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'adjustment',
                'quantity' => -2,
                'reason' => 'Inventaire - casse',
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ]);
        }

        $this->command->info(StockMovement::count() . ' mouvements de stock créés avec succès.');
    }
}