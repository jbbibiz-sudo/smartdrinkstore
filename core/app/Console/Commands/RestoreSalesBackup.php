<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RestoreSalesBackup extends Command
{
    protected $signature = 'sales:restore-backup 
                            {backup? : Nom du fichier backup}
                            {--list : Liste tous les backups}';

    protected $description = 'Restaure un backup de la base de données';

    public function handle()
    {
        // Lister les backups
        if ($this->option('list')) {
            return $this->listBackups();
        }

        $this->info('');
        $this->info('🔄 RESTAURATION DE BACKUP');
        $this->info('=========================');
        $this->info('');

        // Récupérer le backup à restaurer
        $backupFile = $this->argument('backup');
        
        if (!$backupFile) {
            $backups = $this->getBackups();
            
            if ($backups->isEmpty()) {
                $this->error('❌ Aucun backup trouvé');
                return 1;
            }

            // Afficher la liste
            $this->info('Backups disponibles :');
            $this->info('');
            
            $choices = [];
            foreach ($backups as $index => $backup) {
                $size = File::size($backup['path']) / 1024;
                $date = $backup['date']->format('d/m/Y H:i:s');
                $label = ($index + 1) . ". {$backup['name']} - {$date} (" . number_format($size, 2) . " KB)";
                $this->line($label);
                $choices[] = (string)($index + 1);
            }
            
            $this->info('');
            $choice = $this->ask('Choisissez un backup (numéro)', '1');
            
            if (!in_array($choice, $choices)) {
                $this->error('❌ Choix invalide');
                return 1;
            }
            
            $backupFile = $backups[$choice - 1]['name'];
        }

        // Chemin du backup
        $backupPath = database_path($backupFile);
        
        if (!File::exists($backupPath)) {
            $this->error("❌ Backup introuvable : $backupFile");
            return 1;
        }

        // Confirmation
        $this->warn('⚠️  Cette opération va écraser la base actuelle !');
        if (!$this->confirm('Voulez-vous continuer ?', false)) {
            $this->error('❌ Restauration annulée');
            return 1;
        }

        // Restauration
        try {
            $dbPath = database_path('smartdrinkstore.sqlite');
            
            // Backup de la base actuelle avant restauration
            $currentBackup = database_path('smartdrinkstore.sqlite.before_restore_' . now()->format('Ymd_His'));
            File::copy($dbPath, $currentBackup);
            $this->line("📦 Backup de sécurité créé : " . basename($currentBackup));
            
            // Restaurer
            File::copy($backupPath, $dbPath);
            
            $this->info('');
            $this->info('✅ Backup restauré avec succès !');
            $this->info('');
            $this->line("📁 Source : $backupFile");
            $this->line("📦 Backup sécurité : " . basename($currentBackup));
            $this->info('');
            
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erreur : ' . $e->getMessage());
            return 1;
        }
    }

    private function listBackups()
    {
        $this->info('');
        $this->info('📦 BACKUPS DISPONIBLES');
        $this->info('======================');
        $this->info('');

        $backups = $this->getBackups();

        if ($backups->isEmpty()) {
            $this->warn('Aucun backup trouvé');
            return 0;
        }

        foreach ($backups as $backup) {
            $size = File::size($backup['path']) / 1024;
            $date = $backup['date']->format('d/m/Y H:i:s');
            
            $this->line("📁 {$backup['name']}");
            $this->line("   📅 Date : $date");
            $this->line("   💾 Taille : " . number_format($size, 2) . " KB");
            $this->line('');
        }

        return 0;
    }

    private function getBackups()
    {
        $files = File::glob(database_path('smartdrinkstore.sqlite.backup_*'));
        
        return collect($files)->map(function ($path) {
            return [
                'name' => basename($path),
                'path' => $path,
                'date' => \Carbon\Carbon::createFromTimestamp(File::lastModified($path))
            ];
        })->sortByDesc('date')->values();
    }
}