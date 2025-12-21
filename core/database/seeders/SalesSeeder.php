<?php

// Fichier 2: core/database/seeders/SalesSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer les IDs des produits et clients existants
        $productIds = DB::table('products')->pluck('id')->toArray();
        $customerIds = DB::table('customers')->pluck('id')->toArray();

        if (empty($productIds)) {
            $this->command->error('❌ Aucun produit trouvé. Veuillez d\'abord exécuter les seeders de produits.');
            return;
        }

        if (empty($customerIds)) {
            $this->command->error('❌ Aucun client trouvé. Veuillez d\'abord exécuter CustomerSeeder.');
            return;
        }

        // Générer des ventes sur les 30 derniers jours
        $salesCount = 0;
        $saleItemsCount = 0;

        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            
            // Entre 3 et 8 ventes par jour
            $dailySales = rand(3, 8);
            
            for ($j = 0; $j < $dailySales; $j++) {
                $saleId = $this->createSale($date, $productIds, $customerIds);
                $itemsCreated = $this->createSaleItems($saleId, $productIds);
                
                $salesCount++;
                $saleItemsCount += $itemsCreated;
            }
        }

        $this->command->info("✅ $salesCount ventes créées avec $saleItemsCount lignes de vente!");
    }

    /**
     * Créer une vente
     */
    private function createSale($date, $productIds, $customerIds): int
    {
        $type = rand(1, 10) > 7 ? 'wholesale' : 'counter'; // 30% gros, 70% comptoir
        $paymentMethod = $this->getRandomPaymentMethod($type);
        
        // Client aléatoire (50% sans client pour comptoir)
        $customerId = null;
        if ($type === 'wholesale' || rand(1, 2) === 1) {
            $customerId = $customerIds[array_rand($customerIds)];
        }

        // Calculer le montant (sera mis à jour après création des items)
        $totalAmount = 0;
        $discountAmount = 0;

        // Remise aléatoire pour les ventes en gros
        if ($type === 'wholesale') {
            $discountAmount = 0; // Sera calculé après
        }

        $invoiceNumber = 'INV-' . $date->format('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $saleId = DB::table('sales')->insertGetId([
            'invoice_number' => $invoiceNumber,
            'customer_id' => $customerId,
            'type' => $type,
            'payment_method' => $paymentMethod,
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'paid_amount' => 0, // Sera mis à jour
            'created_at' => $date->addHours(rand(8, 20))->addMinutes(rand(0, 59)),
            'updated_at' => $date
        ]);

        return $saleId;
    }

    /**
     * Créer les lignes de vente
     */
    private function createSaleItems($saleId, $productIds): int
    {
        // Entre 1 et 5 produits différents par vente
        $itemsCount = rand(1, 5);
        $selectedProducts = array_rand(array_flip($productIds), min($itemsCount, count($productIds)));
        
        if (!is_array($selectedProducts)) {
            $selectedProducts = [$selectedProducts];
        }

        $totalAmount = 0;
        $itemsCreated = 0;

        foreach ($selectedProducts as $productId) {
            $product = DB::table('products')->where('id', $productId)->first();
            
            if (!$product) continue;

            // Quantité aléatoire
            $quantity = rand(1, 10);
            
            // Prix unitaire (peut varier légèrement)
            $unitPrice = $product->unit_price;
            
            $subtotal = $unitPrice * $quantity;
            $totalAmount += $subtotal;

            DB::table('sale_items')->insert([
                'sale_id' => $saleId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $itemsCreated++;
        }

        // Mettre à jour le montant total de la vente
        $sale = DB::table('sales')->where('id', $saleId)->first();
        
        $discountAmount = 0;
        if ($sale->type === 'wholesale') {
            $discountAmount = $totalAmount * 0.05; // 5% de remise pour gros
        }

        $finalTotal = $totalAmount - $discountAmount;
        $paidAmount = $sale->payment_method === 'credit' ? 0 : $finalTotal;

        DB::table('sales')->where('id', $saleId)->update([
            'total_amount' => $finalTotal,
            'discount_amount' => $discountAmount,
            'paid_amount' => $paidAmount
        ]);

        // Mettre à jour le solde client si crédit
        if ($sale->payment_method === 'credit' && $sale->customer_id) {
            DB::table('customers')
                ->where('id', $sale->customer_id)
                ->increment('balance', $finalTotal);
        }

        return $itemsCreated;
    }

    /**
     * Obtenir une méthode de paiement aléatoire
     */
    private function getRandomPaymentMethod($type): string
    {
        if ($type === 'wholesale') {
            // Gros: 50% crédit, 30% mobile, 20% cash
            $rand = rand(1, 10);
            if ($rand <= 5) return 'credit';
            if ($rand <= 8) return 'mobile';
            return 'cash';
        } else {
            // Comptoir: 60% cash, 30% mobile, 10% crédit
            $rand = rand(1, 10);
            if ($rand <= 6) return 'cash';
            if ($rand <= 9) return 'mobile';
            return 'credit';
        }
    }
}
