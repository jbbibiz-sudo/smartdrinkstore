<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class RecreateSalesTable extends Command
{
    protected $signature = 'sales:recreate-table 
                            {--no-backup : Skip backup creation}
                            {--force : Force execution without confirmation}';

    protected $description = 'Recrée la table sales avec la structure complète (user_id, discount)';

    private $backupPath;
    
    public function handle()
    {
        $this->info('');
        $this->info('🔧 RECRÉATION SÉCURISÉE DE LA TABLE SALES');
        $this->info('==========================================');
        $this->info('');

        // Confirmation
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  Cette opération va recréer la table sales. Continuer ?', true)) {
                $this->error('❌ Opération annulée');
                return 1;
            }
        }

        // ============================================
        // ÉTAPE 1 : BACKUP
        // ============================================
        $this->info('');
        $this->line('📦 ÉTAPE 1/6 : Sauvegarde...');
        
        if (!$this->option('no-backup')) {
            if (!$this->createBackup()) {
                return 1;
            }
        } else {
            $this->warn('⚠️  Backup ignoré (--no-backup)');
        }

        // ============================================
        // ÉTAPE 2 : EXPORT DES DONNÉES
        // ============================================
        $this->info('');
        $this->line('💾 ÉTAPE 2/6 : Export des ventes existantes...');
        
        $salesData = $this->exportSalesData();
        $this->info("✅ {$salesData->count()} ventes exportées en mémoire");

        // ============================================
        // ÉTAPE 3 : EXPORT DES ITEMS
        // ============================================
        $this->info('');
        $this->line('📦 ÉTAPE 3/6 : Export des articles...');
        
        $itemsData = $this->exportItemsData();
        $this->info("✅ {$itemsData->count()} articles exportés en mémoire");

        // ============================================
        // ÉTAPE 4 : SUPPRESSION ET RECRÉATION
        // ============================================
        $this->info('');
        $this->line('🏗️  ÉTAPE 4/6 : Recréation de la table...');
        
        if (!$this->recreateTable()) {
            $this->error('❌ Échec de la recréation');
            return 1;
        }

        // ============================================
        // ÉTAPE 5 : RÉIMPORT DES DONNÉES
        // ============================================
        $this->info('');
        $this->line('📥 ÉTAPE 5/6 : Réimport des données...');
        
        if (!$this->reimportData($salesData, $itemsData)) {
            $this->error('❌ Échec du réimport');
            $this->warn('🔄 Restaurez le backup avec : php artisan sales:restore-backup');
            return 1;
        }

        // ============================================
        // ÉTAPE 6 : VÉRIFICATIONS
        // ============================================
        $this->info('');
        $this->line('🔍 ÉTAPE 6/6 : Vérifications finales...');
        $this->runVerifications();

        // ============================================
        // RÉSUMÉ FINAL
        // ============================================
        $this->info('');
        $this->info('==========================================');
        $this->info('✅ MIGRATION TERMINÉE AVEC SUCCÈS !');
        $this->info('==========================================');
        $this->info('');
        
        if ($this->backupPath) {
            $this->line("📦 Backup : {$this->backupPath}");
        }
        $this->line("📊 Ventes migrées : {$salesData->count()}");
        $this->line("📦 Articles migrés : {$itemsData->count()}");
        
        $this->info('');
        $this->line('🎯 Prochaines étapes :');
        $this->line('   1. Vérifier : php artisan tinker');
        $this->line('      Schema::hasColumn(\'sales\', \'user_id\')');
        $this->line('   2. Tester une facture dans l\'application');
        $this->info('');

        return 0;
    }

    private function createBackup()
    {
        try {
            $dbPath = database_path('smartdrinkstore.sqlite');
            
            if (!File::exists($dbPath)) {
                $this->error("❌ Base introuvable : $dbPath");
                return false;
            }

            $timestamp = now()->format('Ymd_His');
            $this->backupPath = database_path("smartdrinkstore.sqlite.backup_$timestamp");

            File::copy($dbPath, $this->backupPath);
            
            $size = File::size($this->backupPath) / 1024;
            $this->info("✅ Backup créé : " . basename($this->backupPath));
            $this->line("   Taille : " . number_format($size, 2) . " KB");
            
            return true;

        } catch (\Exception $e) {
            $this->error("❌ Erreur backup : " . $e->getMessage());
            return false;
        }
    }

    private function exportSalesData()
    {
        return DB::table('sales')->get()->map(function ($sale) {
            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'customer_id' => $sale->customer_id,
                'user_id' => $sale->user_id ?? 1, // Défaut user_id = 1
                'type' => $sale->type,
                'payment_method' => $sale->payment_method,
                'total_amount' => $sale->total_amount,
                'discount' => $sale->discount ?? $sale->discount_amount ?? 0,
                'paid_amount' => $sale->paid_amount,
                'created_at' => $sale->created_at,
                'updated_at' => $sale->updated_at,
            ];
        });
    }

    private function exportItemsData()
    {
        return DB::table('sale_items')->get();
    }

    private function recreateTable()
    {
        try {
            DB::beginTransaction();

            // Désactiver foreign keys temporairement
            DB::statement('PRAGMA foreign_keys = OFF');

            // Supprimer l'ancienne table
            Schema::dropIfExists('sales');
            $this->line('   - Ancienne table supprimée');

            // Recréer avec la nouvelle structure
            Schema::create('sales', function ($table) {
                $table->id();
                $table->string('invoice_number')->unique();
                
                // Relations
                $table->foreignId('customer_id')
                    ->nullable()
                    ->constrained('customers')
                    ->onDelete('set null');
                
                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null');
                
                // Informations
                $table->enum('type', ['counter', 'wholesale'])->default('counter');
                $table->enum('payment_method', ['cash', 'mobile', 'credit'])->default('cash');
                
                // Montants
                $table->decimal('total_amount', 15, 2);
                $table->decimal('discount', 15, 2)->default(0);
                $table->decimal('paid_amount', 15, 2)->default(0);
                
                $table->timestamps();
                
                // Index
                $table->index('invoice_number');
                $table->index('customer_id');
                $table->index('user_id');
                $table->index('created_at');
            });

            $this->line('   - Nouvelle table créée');
            $this->line('   - Colonne user_id : ✅');
            $this->line('   - Colonne discount : ✅');
            $this->line('   - Foreign keys : ✅');

            // Réactiver foreign keys
            DB::statement('PRAGMA foreign_keys = ON');

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Erreur : " . $e->getMessage());
            return false;
        }
    }

    private function reimportData($salesData, $itemsData)
    {
        try {
            DB::beginTransaction();

            // Réimporter les ventes
            $bar = $this->output->createProgressBar($salesData->count());
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
            $bar->setMessage('Importation des ventes...');
            $bar->start();

            foreach ($salesData as $sale) {
                DB::table('sales')->insert($sale);
                $bar->advance();
            }

            $bar->setMessage('Ventes importées ✅');
            $bar->finish();
            $this->line('');

            // Réimporter les items
            if ($itemsData->count() > 0) {
                $bar2 = $this->output->createProgressBar($itemsData->count());
                $bar2->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
                $bar2->setMessage('Importation des articles...');
                $bar2->start();

                foreach ($itemsData->chunk(100) as $chunk) {
                    // Convertir chaque objet en tableau associatif
                    $itemsArray = $chunk->map(function($item) {
                        return (array) $item;
                    })->toArray();
                    
                    DB::table('sale_items')->insert($itemsArray);
                    $bar2->advance($chunk->count());
                }

                $bar2->setMessage('Articles importés ✅');
                $bar2->finish();
                $this->line('');
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Erreur : " . $e->getMessage());
            return false;
        }
    }

    private function runVerifications()
    {
        // Structure
        $this->line('');
        $this->line('📊 Structure de la table :');
        
        $columns = Schema::getColumnListing('sales');
        foreach ($columns as $col) {
            $hasCol = Schema::hasColumn('sales', $col);
            $icon = $hasCol ? '✅' : '❌';
            $this->line("   $icon $col");
        }

        // Vérifications spécifiques
        $this->line('');
        $this->line('🔍 Vérifications clés :');
        
        $checks = [
            'user_id existe' => Schema::hasColumn('sales', 'user_id'),
            'discount existe' => Schema::hasColumn('sales', 'discount'),
            'discount_amount absent' => !Schema::hasColumn('sales', 'discount_amount'),
        ];

        foreach ($checks as $label => $result) {
            $icon = $result ? '✅' : '❌';
            $this->line("   $icon $label");
        }

        // Comptage
        $this->line('');
        $this->line('📈 Données :');
        $total = DB::table('sales')->count();
        $withUser = DB::table('sales')->whereNotNull('user_id')->count();
        $withCustomer = DB::table('sales')->whereNotNull('customer_id')->count();
        
        $this->line("   📊 Total ventes : $total");
        $this->line("   👤 Avec vendeur : $withUser");
        $this->line("   🏪 Avec client : $withCustomer");
    }
}