<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        $suppliers = [
            [
                'name' => 'SABC - Brasseries du Cameroun',
                'phone' => '+237 233 42 42 42',
                'email' => 'contact@sabc.cm',
                'address' => 'Douala, Zone Industrielle',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'GUINNESS Cameroun',
                'phone' => '+237 233 50 50 50',
                'email' => 'info@guinness.cm',
                'address' => 'Douala, Bassa',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'UCB - Union des Brasseries',
                'phone' => '+237 233 45 67 89',
                'email' => 'commercial@ucb.cm',
                'address' => 'Yaoundé, Zone Industrielle',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Source du Pays',
                'phone' => '+237 222 33 44 55',
                'email' => 'contact@sourcedupays.cm',
                'address' => 'Bafoussam',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Coca-Cola CCBG',
                'phone' => '+237 233 40 50 60',
                'email' => 'info@cocacola.cm',
                'address' => 'Douala, Akwa',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Brasseries du Logone',
                'phone' => '+237 222 50 60 70',
                'email' => 'contact@brasserieslogone.cm',
                'address' => 'Garoua',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'TOP Vin Cameroun',
                'phone' => '+237 233 55 66 77',
                'email' => 'topvin@email.cm',
                'address' => 'Douala, Bonabéri',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Distribution KAMDEM',
                'phone' => '+237 699 12 34 56',
                'email' => 'kamdem.distrib@email.cm',
                'address' => 'Bafoussam, Centre Commercial',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Les Eaux Minérales du Cameroun',
                'phone' => '+237 222 77 88 99',
                'email' => 'emc@email.cm',
                'address' => 'Yaoundé, Mvan',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Import & Export SARL',
                'phone' => '+237 233 11 22 33',
                'email' => 'import.export@email.cm',
                'address' => 'Douala, Port',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('suppliers')->insert($suppliers);
        
        $this->command->info('✅ ' . count($suppliers) . ' fournisseurs ajoutés avec succès!');
    }
}
