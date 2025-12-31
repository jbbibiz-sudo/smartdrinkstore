<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\User;
use App\Models\CreditPayment;
use Carbon\Carbon;

class CreditPaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer quelques clients, produits et utilisateurs
        $customers = Customer::take(5)->get();
        $products = Product::take(10)->get();
        $users = User::take(3)->get();

        if ($customers->isEmpty() || $products->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Assurez-vous d\'avoir des customers, products et users dans la base de données.');
            return;
        }

        $this->command->info('Création de ventes à crédit...');

        // Créer 10 ventes à crédit avec différents statuts de paiement
        for ($i = 1; $i <= 10; $i++) {
            $saleDate = Carbon::now()->subDays(rand(1, 60));
            $creditDays = [15, 30, 45, 60][rand(0, 3)];
            $dueDate = $saleDate->copy()->addDays($creditDays);
            
            $totalAmount = rand(50000, 500000);
            
            // Créer la vente
            $sale = Sale::create([
                'invoice_number' => 'INV-CREDIT-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'customer_id' => $customers->random()->id,
                'user_id' => $users->random()->id,
                'type' => rand(0, 1) ? 'counter' : 'wholesale',
                'payment_method' => 'credit',
                'total_amount' => $totalAmount,
                'discount' => rand(0, 10) > 7 ? rand(1000, 10000) : 0,
                'paid_amount' => 0, // Sera mis à jour après les paiements
                'due_date' => $dueDate,
                'credit_days' => $creditDays,
                'created_at' => $saleDate,
                'updated_at' => $saleDate,
            ]);

            // Créer 1-4 lignes de vente
            $numItems = rand(1, 4);
            for ($j = 0; $j < $numItems; $j++) {
                $product = $products->random();
                $quantity = rand(1, 10);
                $unitPrice = $product->selling_price ?? rand(5000, 50000);
                $subtotal = $quantity * $unitPrice;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate,
                ]);
            }

            // Créer des paiements selon différents scénarios
            $scenario = rand(1, 4);
            $paidAmount = 0;

            switch ($scenario) {
                case 1: // Complètement payé (25%)
                    $this->createFullPayments($sale, $users);
                    $paidAmount = $sale->total_amount;
                    $this->command->info("  ✓ Vente {$sale->invoice_number}: Payée intégralement");
                    break;

                case 2: // Partiellement payé - 1 paiement (25%)
                    $payment = rand(30, 70) / 100 * $sale->total_amount;
                    $this->createPayment($sale, $payment, $saleDate->copy()->addDays(rand(1, $creditDays)), $users->random());
                    $paidAmount = $payment;
                    $this->command->info("  ⚠ Vente {$sale->invoice_number}: Payée partiellement (" . round($payment/$sale->total_amount*100) . "%)");
                    break;

                case 3: // Partiellement payé - plusieurs paiements (25%)
                    $numPayments = rand(2, 4);
                    $remaining = $sale->total_amount;
                    for ($p = 0; $p < $numPayments - 1; $p++) {
                        $payment = rand(15, 40) / 100 * $remaining;
                        $this->createPayment($sale, $payment, $saleDate->copy()->addDays(rand(1, $creditDays)), $users->random());
                        $paidAmount += $payment;
                        $remaining -= $payment;
                    }
                    $this->command->info("  ⚠ Vente {$sale->invoice_number}: Payée partiellement avec {$numPayments} paiements (" . round($paidAmount/$sale->total_amount*100) . "%)");
                    break;

                case 4: // Non payé (25%)
                    $this->command->warn("  ✗ Vente {$sale->invoice_number}: Non payée (échue: " . ($dueDate->isPast() ? 'Oui' : 'Non') . ")");
                    break;
            }

            // Mettre à jour le montant payé
            $sale->update(['paid_amount' => $paidAmount]);
        }

        $this->command->info("\n✓ 10 ventes à crédit créées avec succès!");
        
        // Afficher les statistiques
        $totalSales = Sale::where('payment_method', 'credit')->count();
        $totalAmount = Sale::where('payment_method', 'credit')->sum('total_amount');
        $totalPaid = Sale::where('payment_method', 'credit')->sum('paid_amount');
        $totalPayments = CreditPayment::count();
        
        $this->command->info("\n--- Statistiques ---");
        $this->command->info("Ventes à crédit: {$totalSales}");
        $this->command->info("Montant total: " . number_format($totalAmount, 0, ',', ' ') . " FCFA");
        $this->command->info("Montant payé: " . number_format($totalPaid, 0, ',', ' ') . " FCFA");
        $this->command->info("Reste à payer: " . number_format($totalAmount - $totalPaid, 0, ',', ' ') . " FCFA");
        $this->command->info("Nombre de paiements: {$totalPayments}");
    }

    /**
     * Créer un paiement complet (en plusieurs fois ou en une fois)
     */
    private function createFullPayments($sale, $users)
    {
        $remaining = $sale->total_amount;
        $numPayments = rand(1, 3); // 1 à 3 paiements pour solder
        
        for ($i = 0; $i < $numPayments; $i++) {
            if ($i === $numPayments - 1) {
                // Dernier paiement = le reste
                $amount = $remaining;
            } else {
                $amount = rand(20, 50) / 100 * $remaining;
            }
            
            $paymentDate = $sale->created_at->copy()->addDays(rand(1, $sale->credit_days));
            $this->createPayment($sale, $amount, $paymentDate, $users->random());
            $remaining -= $amount;
        }
    }

    /**
     * Créer un paiement individuel
     */
    private function createPayment($sale, $amount, $paymentDate, $recordedBy)
    {
        $paymentMethods = ['cash', 'mobile', 'bank_transfer', 'check'];
        
        CreditPayment::create([
            'sale_id' => $sale->id,
            'amount' => round($amount, 2),
            'payment_method' => $paymentMethods[rand(0, 3)],
            'payment_date' => $paymentDate,
            'notes' => $this->generatePaymentNotes(),
            'recorded_by' => $recordedBy->id,
            'created_at' => $paymentDate,
            'updated_at' => $paymentDate,
        ]);
    }

    /**
     * Générer des notes de paiement réalistes
     */
    private function generatePaymentNotes()
    {
        $notes = [
            'Paiement partiel',
            'Règlement compte client',
            'Versement selon accord',
            'Mobile Money - Orange Money',
            'Mobile Money - MTN Mobile Money',
            'Virement bancaire UBA',
            'Chèque n° ' . rand(1000000, 9999999),
            'Espèces',
            null, // Parfois pas de notes
        ];
        
        return $notes[rand(0, count($notes) - 1)];
    }
}
