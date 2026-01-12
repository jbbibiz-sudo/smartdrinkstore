<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_units', function (Blueprint $table) {
            $table->id(); // INTEGER PRIMARY KEY AUTOINCREMENT
            $table->string('code')->unique();
            $table->string('name');
            $table->string('symbol');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps(); // created_at et updated_at TEXT avec localtime
        });

        // Indexes
        Schema::table('product_units', function (Blueprint $table) {
            $table->index('code', 'idx_product_units_code');
            $table->index('is_active', 'idx_product_units_is_active');
        });

        // Données initiales
        DB::table('product_units')->insert([
            ['code' => 'CASE_24', 'name' => 'Casier 24', 'symbol' => 'cs24', 'description' => 'Casier de 24 bouteilles/canettes', 'is_active' => true],
            ['code' => 'CASE_12', 'name' => 'Casier 12', 'symbol' => 'cs12', 'description' => 'Casier de 12 bouteilles/canettes', 'is_active' => true],
            ['code' => 'PACK_6', 'name' => 'Pack 6', 'symbol' => 'pk6', 'description' => 'Pack de 6 bouteilles/canettes', 'is_active' => true],
            ['code' => 'PACK_12', 'name' => 'Pack 12', 'symbol' => 'pk12', 'description' => 'Pack de 12 bouteilles/canettes', 'is_active' => true],
            ['code' => 'BOTTLE', 'name' => 'Bouteille', 'symbol' => 'btl', 'description' => 'Unité individuelle - bouteille', 'is_active' => true],
            ['code' => 'CAN', 'name' => 'Canette', 'symbol' => 'can', 'description' => 'Unité individuelle - canette', 'is_active' => true],
            ['code' => 'KEG_30L', 'name' => 'Fût 30L', 'symbol' => 'keg30', 'description' => 'Fût de 30 litres', 'is_active' => true],
            ['code' => 'KEG_50L', 'name' => 'Fût 50L', 'symbol' => 'keg50', 'description' => 'Fût de 50 litres', 'is_active' => true],
            ['code' => 'BARREL', 'name' => 'Bidon', 'symbol' => 'brl', 'description' => 'Grand contenant (5L à 20L)', 'is_active' => true],
        ]);

        // Vue
        DB::statement("
            CREATE VIEW IF NOT EXISTS v_products_with_units AS
            SELECT 
                p.*,
                bu.name as base_unit_name,
                bu.symbol as base_unit_symbol,
                ru.name as retail_unit_name,
                ru.symbol as retail_unit_symbol,
                p.name || ' (' || bu.symbol || ' de ' || p.base_unit_quantity || ')' as display_name,
                CAST(p.unit_price AS REAL) / CAST(p.base_unit_quantity AS REAL) as retail_unit_price
            FROM products p
            LEFT JOIN product_units bu ON p.base_unit_id = bu.id
            LEFT JOIN product_units ru ON p.retail_unit_id = ru.id
        ");

        // Trigger pour updated_at (SQLite)
        DB::statement("
            CREATE TRIGGER IF NOT EXISTS update_product_units_timestamp 
            AFTER UPDATE ON product_units
            FOR EACH ROW
            BEGIN
                UPDATE product_units SET updated_at = datetime('now', 'localtime')
                WHERE id = NEW.id;
            END
        ");
    }

    public function down()
    {
        DB::statement('DROP TRIGGER IF EXISTS update_product_units_timestamp');
        DB::statement('DROP VIEW IF EXISTS v_products_with_units');
        Schema::dropIfExists('product_units');
    }
};
