<?php

namespace Database\Seeders;

use App\Models\CashRegister;
use Illuminate\Database\Seeder;

class CashRegisterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CashRegister::create([
            'name' => 'Caisse Principale',
            'code' => 'POS-001',
            'status' => true,
            'notes' => 'Caisse principale du magasin',
        ]);

        CashRegister::create([
            'name' => 'Caisse Secondaire',
            'code' => 'POS-002',
            'status' => true,
            'notes' => 'Caisse de secours',
        ]);
    }
}