<?php

/* ============================================================================
 * SEEDER 5: PAIEMENTS DE CRÉDIT (CreditPaymentsSeeder)
 * ============================================================================
 * Fichier: database/seeders/CreditPaymentsSeeder.php
 */

namespace Database\Seeders;

use App\Models\Sale;
use App\Models\CreditPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class CreditPaymentsSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            $this->command->warn('⚠️  Aucun utilisateur trouvé.');
            return;
        }

        // Récupérer les ventes à crédit
        $creditSales = Sale::where('payment_method', 'credit')
            ->where('paid_amount', '<', DB::raw('total_amount'))
            ->limit(5)
            ->get();

        foreach ($creditSales as $index => $sale) {
            $remaining = $sale->total_amount - $sale->paid_amount;
            $paymentAmount = min($remaining, rand(5000, 20000));

            CreditPayment::create([
                'sale_id' => $sale->id,
                'amount' => $paymentAmount,
                'payment_method' => ['cash', 'mobile', 'bank_transfer'][rand(0, 2)],
                'payment_date' => now()->subDays($index),
                'recorded_by' => $user->id,
                'notes' => 'Paiement partiel de crédit',
            ]);

            // Mettre à jour le montant payé
            $sale->increment('paid_amount', $paymentAmount);
        }

        $this->command->info('✅ Paiements de crédit créés');
    }
}
