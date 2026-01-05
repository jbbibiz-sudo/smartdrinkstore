<?php

/**
 * ============================================================================
 * SEEDER 3: RETOURS DE CONSIGNES (DepositReturnsSeeder)
 * ============================================================================
 * Fichier: database/seeders/DepositReturnsSeeder.php
 */

namespace Database\Seeders;

use App\Models\Deposit;
use App\Models\DepositReturn;
use App\Models\User;
use Illuminate\Database\Seeder;

class DepositReturnsSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            $this->command->warn('⚠️  Aucun utilisateur trouvé.');
            return;
        }

        // Récupérer quelques consignes avec retours
        $deposits = Deposit::where('quantity_returned', '>', 0)->get();

        foreach ($deposits as $index => $deposit) {
            if ($deposit->quantity_returned > 0) {
                DepositReturn::create([
                    'reference' => 'RET-' . date('Ymd') . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                    'deposit_id' => $deposit->id,
                    'user_id' => $user->id,
                    'quantity_returned' => $deposit->quantity_returned,
                    'quantity_good_condition' => (int)($deposit->quantity_returned * 0.8),
                    'quantity_damaged' => (int)($deposit->quantity_returned * 0.15),
                    'quantity_lost' => (int)($deposit->quantity_returned * 0.05),
                    'refund_amount' => $deposit->quantity_returned * $deposit->unit_deposit_amount,
                    'damage_penalty' => (int)($deposit->quantity_returned * 0.15) * $deposit->unit_deposit_amount * 0.5,
                    'delay_penalty' => 0,
                    'total_penalty' => (int)($deposit->quantity_returned * 0.15) * $deposit->unit_deposit_amount * 0.5,
                    'net_refund' => ($deposit->quantity_returned * $deposit->unit_deposit_amount) - ((int)($deposit->quantity_returned * 0.15) * $deposit->unit_deposit_amount * 0.5),
                    'returned_at' => now()->subDays($index),
                    'notes' => 'Retour partiel - quelques emballages endommagés',
                ]);
            }
        }

        $this->command->info('✅ Retours de consignes créés');
    }
}
