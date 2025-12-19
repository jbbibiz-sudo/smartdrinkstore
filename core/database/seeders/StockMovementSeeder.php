<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // Mouvements d'exemple pour les 30 derniers jours
        $movements = [
            // Aujourd'hui - Réapprovisionnements
            [
                'product_id' => 4, // Sprite 33cl
                'type' => 'in',
                'quantity' => 50,
                'reason' => 'Réapprovisionnement fournisseur',
                'created_at' => $now->copy(),
            ],
            [
                'product_id' => 6, // Castel 65cl (rupture)
                'type' => 'in',
                'quantity' => 100,
                'reason' => 'Réapprovisionnement urgent',
                'created_at' => $now->copy()->subHours(2),
            ],
            
            // Hier - Ventes
            [
                'product_id' => 1, // Coca-Cola 33cl
                'type' => 'out',
                'quantity' => 30,
                'reason' => 'Vente journalière',
                'created_at' => $now->copy()->subDay(),
            ],
            [
                'product_id' => 5, // 33 Export
                'type' => 'out',
                'quantity' => 24,
                'reason' => 'Vente journalière',
                'created_at' => $now->copy()->subDay(),
            ],
            
            // Il y a 3 jours - Réapprovisionnement
            [
                'product_id' => 2, // Coca-Cola 1.5L
                'type' => 'in',
                'quantity' => 60,
                'reason' => 'Livraison fournisseur',
                'created_at' => $now->copy()->subDays(3),
            ],
            [
                'product_id' => 8, // Tangui 50cl
                'type' => 'in',
                'quantity' => 150,
                'reason' => 'Stock de sécurité',
                'created_at' => $now->copy()->subDays(3),
            ],
            
            // Il y a 5 jours - Ventes importantes
            [
                'product_id' => 1, // Coca-Cola 33cl
                'type' => 'out',
                'quantity' => 45,
                'reason' => 'Commande grossiste',
                'created_at' => $now->copy()->subDays(5),
            ],
            [
                'product_id' => 5, // 33 Export
                'type' => 'out',
                'quantity' => 72,
                'reason' => 'Événement spécial',
                'created_at' => $now->copy()->subDays(5),
            ],
            
            // Il y a 7 jours - Ajustements d'inventaire
            [
                'product_id' => 3, // Fanta
                'type' => 'adjustment',
                'quantity' => -5,
                'reason' => 'Correction inventaire - casse',
                'created_at' => $now->copy()->subDays(7),
            ],
            [
                'product_id' => 7, // Beaufort
                'type' => 'adjustment',
                'quantity' => 3,
                'reason' => 'Correction inventaire',
                'created_at' => $now->copy()->subDays(7),
            ],
            
            // Il y a 10 jours - Réapprovisionnement mensuel
            [
                'product_id' => 10, // Top Ananas
                'type' => 'in',
                'quantity' => 80,
                'reason' => 'Réapprovisionnement mensuel',
                'created_at' => $now->copy()->subDays(10),
            ],
            [
                'product_id' => 11, // Top Orange
                'type' => 'in',
                'quantity' => 80,
                'reason' => 'Réapprovisionnement mensuel',
                'created_at' => $now->copy()->subDays(10),
            ],
            
            // Il y a 12 jours - Ventes
            [
                'product_id' => 12, // Cellier Rouge
                'type' => 'out',
                'quantity' => 8,
                'reason' => 'Vente détail',
                'created_at' => $now->copy()->subDays(12),
            ],
            [
                'product_id' => 13, // Cellier Blanc
                'type' => 'out',
                'quantity' => 5,
                'reason' => 'Vente détail',
                'created_at' => $now->copy()->subDays(12),
            ],
            
            // Il y a 15 jours - Grosse livraison
            [
                'product_id' => 1, // Coca-Cola 33cl
                'type' => 'in',
                'quantity' => 200,
                'reason' => 'Livraison mensuelle fournisseur',
                'created_at' => $now->copy()->subDays(15),
            ],
            [
                'product_id' => 4, // Sprite
                'type' => 'in',
                'quantity' => 150,
                'reason' => 'Livraison mensuelle fournisseur',
                'created_at' => $now->copy()->subDays(15),
            ],
            [
                'product_id' => 3, // Fanta
                'type' => 'in',
                'quantity' => 150,
                'reason' => 'Livraison mensuelle fournisseur',
                'created_at' => $now->copy()->subDays(15),
            ],
            
            // Il y a 18 jours - Ventes normales
            [
                'product_id' => 9, // Tangui 1.5L
                'type' => 'out',
                'quantity' => 40,
                'reason' => 'Vente journalière',
                'created_at' => $now->copy()->subDays(18),
            ],
            [
                'product_id' => 10, // Top Ananas
                'type' => 'out',
                'quantity' => 25,
                'reason' => 'Vente journalière',
                'created_at' => $now->copy()->subDays(18),
            ],
            
            // Il y a 20 jours - Retour fournisseur
            [
                'product_id' => 6, // Castel
                'type' => 'out',
                'quantity' => 48,
                'reason' => 'Retour fournisseur - péremption proche',
                'created_at' => $now->copy()->subDays(20),
            ],
            
            // Il y a 25 jours - Réapprovisionnement bières
            [
                'product_id' => 5, // 33 Export
                'type' => 'in',
                'quantity' => 240,
                'reason' => 'Stock mensuel - bières',
                'created_at' => $now->copy()->subDays(25),
            ],
            [
                'product_id' => 6, // Castel
                'type' => 'in',
                'quantity' => 180,
                'reason' => 'Stock mensuel - bières',
                'created_at' => $now->copy()->subDays(25),
            ],
            [
                'product_id' => 7, // Beaufort
                'type' => 'in',
                'quantity' => 120,
                'reason' => 'Stock mensuel - bières',
                'created_at' => $now->copy()->subDays(25),
            ],
            
            // Il y a 28 jours - Ventes diverses
            [
                'product_id' => 2, // Coca-Cola 1.5L
                'type' => 'out',
                'quantity' => 35,
                'reason' => 'Vente journalière',
                'created_at' => $now->copy()->subDays(28),
            ],
            [
                'product_id' => 8, // Tangui 50cl
                'type' => 'out',
                'quantity' => 80,
                'reason' => 'Vente grossiste',
                'created_at' => $now->copy()->subDays(28),
            ],
        ];
        
        // Insérer tous les mouvements
        foreach ($movements as $movement) {
            DB::table('stock_movements')->insert([
                'product_id' => $movement['product_id'],
                'type' => $movement['type'],
                'quantity' => $movement['quantity'],
                'reason' => $movement['reason'],
                'created_at' => $movement['created_at'],
                'updated_at' => $movement['created_at'],
            ]);
        }
        
        $this->command->info('✅ ' . count($movements) . ' mouvements de stock créés');
    }
}
