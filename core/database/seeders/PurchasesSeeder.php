<?php

/* ============================================================================
 * SEEDER 4: ACHATS DE DÉMONSTRATION (PurchasesSeeder)
 * ============================================================================
 * Fichier: database/seeders/PurchasesSeeder.php
 */

namespace Database\Seeders;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\DepositType;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PurchasesSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $suppliers = Supplier::all();
        $products = Product::limit(10)->get();
        
        if (!$user || $suppliers->isEmpty() || $products->isEmpty()) {
            $this->command->warn('⚠️  Données manquantes. Créez d\'abord des utilisateurs, fournisseurs et produits.');
            return;
        }

        $depositType = DepositType::where('code', 'BTL-1L')->first();

        // Créer 5 achats de démonstration
        $statuses = ['draft', 'awaiting_approval', 'confirmed', 'received', 'cancelled'];
        
        for ($i = 0; $i < 5; $i++) {
            $supplier = $suppliers->random();
            $status = $statuses[$i];
            
            // Calculer les montants
            $subtotal = 0;
            $tax = 0;
            $discount = rand(0, 5000);
            
            $purchase = Purchase::create([
                'reference' => 'BON-ACH-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'status' => $status,
                'payment_method' => $i % 2 == 0 ? 'cash' : 'credit',
                'mobile_operator' => $i == 1 ? 'MTN' : null,
                'mobile_reference' => $i == 1 ? 'MTN-' . rand(100000, 999999) : null,
                'paid_amount' => 0, // Sera calculé après
                'credit_days' => $i % 2 == 0 ? null : 30,
                'due_date' => $i % 2 == 0 ? null : now()->addDays(30),
                'order_date' => now()->subDays($i * 3),
                'expected_delivery_date' => now()->addDays(7 - $i),
                'received_date' => $status == 'received' ? now()->subDays($i) : null,
                'approved_by' => in_array($status, ['confirmed', 'received']) ? $user->id : null,
                'approved_at' => in_array($status, ['confirmed', 'received']) ? now()->subDays($i + 1) : null,
                'notes' => 'Achat de démonstration - Statut: ' . $status,
                'has_deposits' => $i % 2 == 0 && $depositType,
            ]);

            // Ajouter des items
            $numItems = rand(2, 4);
            for ($j = 0; $j < $numItems; $j++) {
                $product = $products->random();
                $quantity = rand(10, 50);
                $unitCost = $product->unit_price * 0.7; // Prix d'achat = 70% du prix de vente
                $itemSubtotal = $quantity * $unitCost;
                $subtotal += $itemSubtotal;

                $hasDeposit = $i % 2 == 0 && $depositType;
                
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'quantity_received' => $status == 'received' ? $quantity : 0,
                    'unit_cost' => $unitCost,
                    'subtotal' => $itemSubtotal,
                    'is_consigned' => $hasDeposit,
                    'deposit_type_id' => $hasDeposit ? $depositType->id : null,
                    'deposit_quantity' => $hasDeposit ? ceil($quantity / 24) : null,
                    'unit_deposit_amount' => $hasDeposit ? $depositType->amount : null,
                    'total_deposit_amount' => $hasDeposit ? ceil($quantity / 24) * $depositType->amount : null,
                    'notes' => $hasDeposit ? 'Emballages consignés' : null,
                ]);
            }

            // Mettre à jour les totaux de l'achat
            $totalAmount = $subtotal + $tax - $discount;
            $paidAmount = $status == 'received' ? $totalAmount : ($i % 2 == 0 ? $totalAmount : rand(0, $totalAmount));
            
            $purchase->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'total_deposit_amount' => PurchaseItem::where('purchase_id', $purchase->id)
                    ->sum('total_deposit_amount'),
            ]);
        }

        $this->command->info('✅ 5 achats de démonstration créés');
    }
}

