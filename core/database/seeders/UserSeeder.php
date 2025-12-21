<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Administrateur
        User::create([
            'name' => 'Administrateur',
            'email' => 'admin@smartdrink.cm',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'is_active' => true,
            'phone' => '+237 690 000 001',
        ]);

        // Gérant
        User::create([
            'name' => 'Jean Kamdem',
            'email' => 'gerant@smartdrink.cm',
            'password' => Hash::make('gerant123'),
            'role' => 'manager',
            'is_active' => true,
            'phone' => '+237 690 000 002',
        ]);

        // Caissier 1
        User::create([
            'name' => 'Marie Nkolo',
            'email' => 'caissier@smartdrink.cm',
            'password' => Hash::make('caissier123'),
            'role' => 'cashier',
            'is_active' => true,
            'phone' => '+237 690 000 003',
        ]);

        // Caissier 2
        User::create([
            'name' => 'Paul Mbida',
            'email' => 'caissier2@smartdrink.cm',
            'password' => Hash::make('caissier123'),
            'role' => 'cashier',
            'is_active' => true,
            'phone' => '+237 690 000 004',
        ]);
    }
}
