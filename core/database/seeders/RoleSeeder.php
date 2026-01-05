<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Définition des rôles basés sur la logique de votre application
        // (admin, manager, cashier sont les clés utilisées dans vos contrôleurs)
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrateur',
                'description' => 'Accès complet à toutes les fonctionnalités du système.',
            ],
            [
                'name' => 'manager',
                'display_name' => 'Gérant',
                'description' => 'Gestion du magasin, des stocks, des achats et du personnel.',
            ],
            [
                'name' => 'cashier',
                'display_name' => 'Vendeur',
                'description' => 'Gestion des ventes au comptoir et des encaissements.',
            ],
        ];

        foreach ($roles as $role) {
            // Créer le rôle s'il n'existe pas déjà (basé sur le 'name')
            Role::firstOrCreate(
                ['name' => $role['name']], 
                $role
            );
        }
    }
}
