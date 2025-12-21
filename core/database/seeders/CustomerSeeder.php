<?php

// Fichier 1: core/database/seeders/CustomerSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Client de passage',
                'phone' => null,
                'email' => null,
                'address' => null,
                'balance' => 0
            ],
            [
                'name' => 'Jean Kamdem',
                'phone' => '+237690000001',
                'email' => 'jean.kamdem@email.cm',
                'address' => 'Yaoundé, Bastos',
                'balance' => 0
            ],
            [
                'name' => 'Marie Fotso',
                'phone' => '+237690000002',
                'email' => 'marie.fotso@email.cm',
                'address' => 'Yaoundé, Essos',
                'balance' => 15000 // Dette en cours
            ],
            [
                'name' => 'Restaurant Le Palais',
                'phone' => '+237690000003',
                'email' => 'contact@lepalais.cm',
                'address' => 'Yaoundé, Centre-ville',
                'balance' => 50000 // Client gros avec dette
            ],
            [
                'name' => 'Épicerie Chez Paul',
                'phone' => '+237690000004',
                'email' => 'paul@email.cm',
                'address' => 'Yaoundé, Melen',
                'balance' => 0
            ],
            [
                'name' => 'Bar Le Rendez-vous',
                'phone' => '+237690000005',
                'email' => 'bar.rdv@email.cm',
                'address' => 'Yaoundé, Mokolo',
                'balance' => 25000
            ]
        ];

        foreach ($customers as $customer) {
            DB::table('customers')->insert([
                ...$customer,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $this->command->info('✅ ' . count($customers) . ' clients créés avec succès!');
    }
}