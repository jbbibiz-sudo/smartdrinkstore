<?php

// core/database/seeders/ResetCustomerBalancesSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResetCustomerBalancesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('customers')->update(['balance' => 0]);
        $this->command->info('✅ Soldes clients réinitialisés!');
    }
}