<?php
// smartdrinkstore\core\app\Http\Controllers\DatabaseController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;

class DatabaseController extends Controller
{
    /**
     * ✅ Helper : Récupérer le chemin de la base de données
     * Gère à la fois le développement et la production (Electron)
     */
    private function getDatabasePath()
    {
        // Chemin par défaut (développement)
        $defaultPath = database_path('smartdrinkstore.sqlite');
        
        // Si la base existe à l'emplacement standard, l'utiliser
        if (file_exists($defaultPath)) {
            return $defaultPath;
        }
        
        // Sinon, chercher dans les emplacements Electron possibles
        $possiblePaths = [
            // Chemin Electron en production (resources/laravel/database/)
            base_path('../database/smartdrinkstore.sqlite'),
            // Autre chemin possible
            dirname(base_path()) . '/database/smartdrinkstore.sqlite',
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // Par défaut, retourner le chemin standard
        return $defaultPath;
    }

    /**
     * ✅ Helper : Récupérer le dossier de backups
     */
    private function getBackupDirectory()
    {
        $backupDir = storage_path('app/backups');
        
        // Créer le dossier s'il n'existe pas
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        return $backupDir;
    }

    /**
     * Exporter la base de données
     */
    public function export()
    {
        try {
            $dbPath = $this->getDatabasePath();
            
            if (!file_exists($dbPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Base de données introuvable'
                ], 404);
            }

            $backupDir = $this->getBackupDirectory();
            $timestamp = date('Y-m-d_H-i-s');
            $backupName = "smartdrinkstore_backup_{$timestamp}.sqlite";
            $backupPath = $backupDir . '/' . $backupName;

            // Copier la base de données
            copy($dbPath, $backupPath);

            // Créer un ZIP avec des infos supplémentaires
            $zipName = "smartdrinkstore_backup_{$timestamp}.zip";
            $zipPath = $backupDir . '/' . $zipName;
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                // Ajouter la base de données
                $zip->addFile($backupPath, $backupName);
                
                // Ajouter un fichier info
                $info = json_encode([
                    'app_name' => 'SmartDrinkStore',
                    'app_version' => config('app.version', '1.0.0'),
                    'backup_date' => date('Y-m-d H:i:s'),
                    'database_size' => filesize($dbPath),
                    'tables_count' => count(DB::select('SELECT name FROM sqlite_master WHERE type="table"')),
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                ], JSON_PRETTY_PRINT);
                $zip->addFromString('backup_info.json', $info);
                
                $zip->close();
            }

            // Supprimer le fichier .sqlite temporaire
            unlink($backupPath);

            // Retourner le fichier en téléchargement
            return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Importer une base de données
     */
    public function import(Request $request)
    {
        try {
            $request->validate([
                'database' => 'required|file|mimes:sqlite,db,zip|max:51200' // Max 50MB
            ]);

            $file = $request->file('database');
            $dbPath = $this->getDatabasePath();
            
            // Créer un backup de la base actuelle avant import
            $backupDir = $this->getBackupDirectory();
            $backupPath = $backupDir . '/before_import_' . date('Y-m-d_H-i-s') . '.sqlite';
            
            if (file_exists($dbPath)) {
                copy($dbPath, $backupPath);
            }

            // Si c'est un ZIP, extraire
            if ($file->getClientOriginalExtension() === 'zip') {
                $zip = new ZipArchive();
                $tempDir = storage_path('app/temp_import');
                
                if (!file_exists($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }

                if ($zip->open($file->getPathname()) === TRUE) {
                    $zip->extractTo($tempDir);
                    $zip->close();
                    
                    // Trouver le fichier .sqlite dans le ZIP
                    $files = File::allFiles($tempDir);
                    $sqliteFile = null;
                    
                    foreach ($files as $f) {
                        if (in_array($f->getExtension(), ['sqlite', 'db'])) {
                            $sqliteFile = $f->getPathname();
                            break;
                        }
                    }
                    
                    if (!$sqliteFile) {
                        throw new \Exception('Aucune base de données trouvée dans le ZIP');
                    }
                    
                    // Copier la base de données
                    copy($sqliteFile, $dbPath);
                    
                    // Nettoyer
                    File::deleteDirectory($tempDir);
                } else {
                    throw new \Exception('Impossible d\'ouvrir le fichier ZIP');
                }
            } else {
                // Fichier SQLite direct
                $file->move(dirname($dbPath), basename($dbPath));
            }

            // Vérifier l'intégrité de la base importée
            try {
                $integrity = DB::select('PRAGMA integrity_check');
                if ($integrity[0]->integrity_check !== 'ok') {
                    throw new \Exception('Base de données corrompue');
                }
            } catch (\Exception $e) {
                // Restaurer le backup
                if (file_exists($backupPath)) {
                    copy($backupPath, $dbPath);
                }
                throw new \Exception('La base de données importée est corrompue: ' . $e->getMessage());
            }

            // Supprimer le backup temporaire après vérification réussie
            if (file_exists($backupPath)) {
                unlink($backupPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Base de données importée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les informations de la base de données
     */
    public function info()
    {
        try {
            $dbPath = $this->getDatabasePath();
            
            if (!file_exists($dbPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Base de données introuvable'
                ], 404);
            }

            $tables = DB::select('SELECT name FROM sqlite_master WHERE type="table" AND name NOT LIKE "sqlite_%"');
            $tableStats = [];
            
            foreach ($tables as $table) {
                $count = DB::table($table->name)->count();
                $tableStats[] = [
                    'name' => $table->name,
                    'rows' => $count
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'path' => $dbPath,
                    'size' => filesize($dbPath),
                    'size_human' => $this->formatBytes(filesize($dbPath)),
                    'tables' => $tableStats,
                    'total_tables' => count($tableStats),
                    'last_modified' => date('Y-m-d H:i:s', filemtime($dbPath)),
                    'version' => DB::select('SELECT sqlite_version() as version')[0]->version,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un backup automatique
     */
    public function backup()
    {
        try {
            $dbPath = $this->getDatabasePath();
            $backupDir = $this->getBackupDirectory();
            
            $timestamp = date('Y-m-d_H-i-s');
            $backupName = 'backup_' . $timestamp . '.zip';
            $backupPath = $backupDir . '/' . $backupName;
            
            // Créer un ZIP
            $zip = new ZipArchive();
            if ($zip->open($backupPath, ZipArchive::CREATE) === TRUE) {
                $zip->addFile($dbPath, 'smartdrinkstore.sqlite');
                $zip->close();
            } else {
                throw new \Exception('Impossible de créer le fichier ZIP');
            }

            // Garder seulement les 10 derniers backups
            $backups = glob($backupDir . '/*.{sqlite,zip}', GLOB_BRACE);
            if (count($backups) > 10) {
                usort($backups, function($a, $b) {
                    return filemtime($a) - filemtime($b);
                });
                
                $toDelete = array_slice($backups, 0, count($backups) - 10);
                foreach ($toDelete as $file) {
                    unlink($file);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Backup créé avec succès',
                'backup' => $backupName
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister les backups disponibles
     */
    public function listBackups()
    {
        try {
            $backupDir = $this->getBackupDirectory();
            
            $backups = glob($backupDir . '/*.{sqlite,zip}', GLOB_BRACE);
            $backupList = [];
            
            foreach ($backups as $backup) {
                $backupList[] = [
                    'name' => basename($backup),
                    'size' => filesize($backup),
                    'size_human' => $this->formatBytes(filesize($backup)),
                    'date' => date('Y-m-d H:i:s', filemtime($backup)),
                    'path' => $backup
                ];
            }

            // Trier par date (plus récent en premier)
            usort($backupList, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            return response()->json([
                'success' => true,
                'backups' => $backupList
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurer un backup
     */
    public function restore(Request $request)
    {
        try {
            $request->validate([
                'backup_name' => 'required|string'
            ]);

            $backupDir = $this->getBackupDirectory();
            $backupPath = $backupDir . '/' . $request->backup_name;
            
            if (!file_exists($backupPath)) {
                throw new \Exception('Backup introuvable');
            }

            $dbPath = $this->getDatabasePath();
            
            // Créer un backup de la base actuelle
            $timestamp = date('Y-m-d_H-i-s');
            $currentBackup = $backupDir . '/before_restore_' . $timestamp . '.zip';
            
            $zip = new ZipArchive();
            if ($zip->open($currentBackup, ZipArchive::CREATE) === TRUE) {
                if (file_exists($dbPath)) {
                    $zip->addFile($dbPath, 'smartdrinkstore.sqlite');
                }
                $zip->close();
            }

            // Restaurer le backup
            if (pathinfo($backupPath, PATHINFO_EXTENSION) === 'zip') {
                // C'est un ZIP, extraire
                $zip = new ZipArchive();
                if ($zip->open($backupPath) === TRUE) {
                    $tempDir = storage_path('app/temp_restore');
                    if (!file_exists($tempDir)) {
                        mkdir($tempDir, 0755, true);
                    }
                    
                    $zip->extractTo($tempDir);
                    $zip->close();
                    
                    // Trouver le fichier .sqlite
                    $files = File::allFiles($tempDir);
                    $sqliteFile = null;
                    
                    foreach ($files as $f) {
                        if (in_array($f->getExtension(), ['sqlite', 'db'])) {
                            $sqliteFile = $f->getPathname();
                            break;
                        }
                    }
                    
                    if (!$sqliteFile) {
                        throw new \Exception('Aucune base de données trouvée dans le backup');
                    }
                    
                    copy($sqliteFile, $dbPath);
                    File::deleteDirectory($tempDir);
                } else {
                    throw new \Exception('Impossible d\'ouvrir le fichier de backup');
                }
            } else {
                // Fichier SQLite direct
                copy($backupPath, $dbPath);
            }

            // Vérifier l'intégrité
            try {
                $integrity = DB::select('PRAGMA integrity_check');
                if ($integrity[0]->integrity_check !== 'ok') {
                    throw new \Exception('Base de données corrompue');
                }
            } catch (\Exception $e) {
                // Restaurer le backup de sécurité
                if (file_exists($currentBackup)) {
                    $zip = new ZipArchive();
                    if ($zip->open($currentBackup) === TRUE) {
                        $tempDir = storage_path('app/temp_restore_rollback');
                        if (!file_exists($tempDir)) {
                            mkdir($tempDir, 0755, true);
                        }
                        
                        $zip->extractTo($tempDir);
                        $zip->close();
                        
                        $files = File::allFiles($tempDir);
                        foreach ($files as $f) {
                            if (in_array($f->getExtension(), ['sqlite', 'db'])) {
                                copy($f->getPathname(), $dbPath);
                                break;
                            }
                        }
                        
                        File::deleteDirectory($tempDir);
                    }
                    unlink($currentBackup);
                }
                throw $e;
            }

            return response()->json([
                'success' => true,
                'message' => 'Base de données restaurée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formater les bytes en format lisible
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}